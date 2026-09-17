<?php
/**
 * REST API: checkout, webhook, tracking.
 *
 * @package RakanZakat
 */

defined( 'ABSPATH' ) || exit;

class RakanZakat_REST {

	public static function register() {
		register_rest_route(
			'rakanzakat/v1',
			'/checkout',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'checkout' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'rakanzakat/v1',
			'/track',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'track' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'rakanzakat/v1',
			'/billplz/callback',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'callback' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'rakanzakat/v1',
			'/billplz/redirect',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'redirect' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public static function checkout( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce ) {
			$nonce = $request->get_param( '_wpnonce' );
		}
		if ( ! wp_verify_nonce( (string) $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'rz_nonce', __( 'Sesi tamat. Sila muat semula halaman.', 'rakanzakat' ), array( 'status' => 403 ) );
		}

		$result = RakanZakat_Payments::create_checkout( $request->get_json_params() ?: $request->get_params() );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	public static function track( WP_REST_Request $request ) {
		RakanZakat_Tracker::record_visit( $request->get_json_params() ?: $request->get_params() );
		return rest_ensure_response( array( 'ok' => true ) );
	}

	public static function callback( WP_REST_Request $request ) {
		$params = $request->get_body_params();
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}
		if ( empty( $params['id'] ) && ! empty( $_POST['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$params = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		if ( ! RakanZakat_Billplz::verify_signature( $params ) ) {
			return new WP_Error( 'rz_sig', 'Invalid signature', array( 'status' => 403 ) );
		}

		RakanZakat_Payments::apply_callback( $params );

		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	public static function redirect( WP_REST_Request $request ) {
		$billplz = $request->get_param( 'billplz' );
		$params  = is_array( $billplz ) ? array( 'billplz' => $billplz ) : $request->get_query_params();

		$bill_id = '';
		$paid    = false;
		if ( is_array( $billplz ) ) {
			$bill_id = sanitize_text_field( $billplz['id'] ?? '' );
			$paid    = ! empty( $billplz['paid'] ) && in_array( (string) $billplz['paid'], array( 'true', '1' ), true );
		}

		$valid = RakanZakat_Billplz::verify_signature( $params );
		if ( $valid && $bill_id ) {
			$payload = array(
				'id'     => $bill_id,
				'paid'   => $paid ? 'true' : 'false',
				'paid_at'=> is_array( $billplz ) ? ( $billplz['paid_at'] ?? '' ) : '',
			);
			RakanZakat_Payments::apply_callback( $payload );
		}

		$settings = RakanZakat_Settings::get_all();
		$thanks   = (int) $settings['thankyou_page'];
		$failed   = (int) $settings['failed_page'];

		$url = $paid && $thanks ? get_permalink( $thanks ) : ( $failed ? get_permalink( $failed ) : home_url( '/' ) );
		if ( ! $url ) {
			$url = home_url( '/' );
		}

		$url = add_query_arg(
			array(
				'rz_bill'   => rawurlencode( $bill_id ),
				'rz_status' => $paid ? 'paid' : 'failed',
			),
			$url
		);

		wp_safe_redirect( $url );
		exit;
	}
}
