<?php
/**
 * Billplz API v3 client and X-Signature verification.
 *
 * @package RakanZakat
 */

defined( 'ABSPATH' ) || exit;

class RakanZakat_Billplz {

	public static function base_url() {
		$sandbox = (int) RakanZakat_Settings::get( 'sandbox', 1 );
		return $sandbox
			? 'https://www.billplz-sandbox.com/api/v3'
			: 'https://www.billplz.com/api/v3';
	}

	public static function create_bill( $args ) {
		$api_key       = RakanZakat_Settings::get( 'api_key' );
		$collection_id = RakanZakat_Settings::get( 'collection_id' );

		if ( ! $api_key || ! $collection_id ) {
			return new WP_Error( 'rz_not_configured', __( 'Billplz belum dikonfigurasi. Masukkan API Key dan Collection ID.', 'rakanzakat' ) );
		}

		$body = array(
			'collection_id' => $collection_id,
			'email'         => $args['email'],
			'name'          => $args['name'],
			'amount'        => (int) $args['amount_sen'],
			'description'   => $args['description'],
			'callback_url'  => rest_url( 'rakanzakat/v1/billplz/callback' ),
			'redirect_url'  => rest_url( 'rakanzakat/v1/billplz/redirect' ),
		);

		if ( ! empty( $args['mobile'] ) ) {
			$body['mobile'] = $args['mobile'];
		}
		if ( ! empty( $args['reference_1'] ) ) {
			$body['reference_1_label'] = 'Jenis Zakat';
			$body['reference_1']       = $args['reference_1'];
		}
		if ( ! empty( $args['reference_2'] ) ) {
			$body['reference_2_label'] = 'Kempen';
			$body['reference_2']       = $args['reference_2'];
		}

		return self::request( 'POST', '/bills', $body );
	}

	public static function get_bill( $bill_id ) {
		$bill_id = rawurlencode( (string) $bill_id );
		return self::request( 'GET', '/bills/' . $bill_id );
	}

	public static function get_collection( $collection_id = '' ) {
		$id = $collection_id ? $collection_id : RakanZakat_Settings::get( 'collection_id' );
		if ( ! $id ) {
			return new WP_Error( 'rz_no_collection', __( 'Collection ID kosong.', 'rakanzakat' ) );
		}
		return self::request( 'GET', '/collections/' . rawurlencode( $id ) );
	}

	/**
	 * Verify Billplz X-Signature (HMAC-SHA256).
	 *
	 * @param array $params Raw callback/redirect params including x_signature.
	 * @return bool
	 */
	public static function verify_signature( $params ) {
		$key = RakanZakat_Settings::get( 'x_signature' );
		if ( ! $key ) {
			return false;
		}

		$signature = '';
		if ( isset( $params['x_signature'] ) ) {
			$signature = (string) $params['x_signature'];
		} elseif ( isset( $params['billplz']['x_signature'] ) ) {
			$signature = (string) $params['billplz']['x_signature'];
		}

		if ( '' === $signature ) {
			return false;
		}

		$elements = self::flatten_for_signature( $params );
		usort(
			$elements,
			static function ( $a, $b ) {
				return strcasecmp( $a, $b );
			}
		);
		$source   = implode( '|', $elements );
		$computed = hash_hmac( 'sha256', $source, $key );

		return hash_equals( $computed, $signature );
	}

	private static function flatten_for_signature( $data, $prefix = '' ) {
		$out = array();
		foreach ( (array) $data as $key => $value ) {
			if ( 'x_signature' === $key ) {
				continue;
			}
			$full = '' === $prefix ? (string) $key : $prefix . $key;
			if ( is_array( $value ) ) {
				$out = array_merge( $out, self::flatten_for_signature( $value, $full ) );
			} else {
				if ( is_bool( $value ) ) {
					$value = $value ? 'true' : 'false';
				} elseif ( null === $value ) {
					$value = '';
				}
				$out[] = $full . $value;
			}
		}
		return $out;
	}

	public static function normalize_mobile( $mobile ) {
		$digits = preg_replace( '/\D+/', '', (string) $mobile );
		if ( ! $digits ) {
			return '';
		}
		if ( 0 === strpos( $digits, '60' ) ) {
			return '+' . $digits;
		}
		if ( 0 === strpos( $digits, '0' ) ) {
			return '+60' . substr( $digits, 1 );
		}
		return '+60' . $digits;
	}

	private static function request( $method, $path, $body = null ) {
		$api_key = RakanZakat_Settings::get( 'api_key' );
		if ( ! $api_key ) {
			return new WP_Error( 'rz_no_key', __( 'API Key Billplz kosong.', 'rakanzakat' ) );
		}

		$args = array(
			'method'  => $method,
			'timeout' => 30,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $api_key . ':' ),
				'Accept'        => 'application/json',
			),
		);

		if ( null !== $body ) {
			$args['body'] = $body;
		}

		$response = wp_remote_request( self::base_url() . $path, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( $code < 200 || $code >= 300 ) {
			$message = __( 'Ralat Billplz.', 'rakanzakat' );
			if ( is_array( $data ) && isset( $data['error']['message'] ) ) {
				$message = is_array( $data['error']['message'] )
					? implode( ' ', $data['error']['message'] )
					: (string) $data['error']['message'];
			}
			return new WP_Error( 'rz_billplz', $message, array( 'status' => $code, 'body' => $data ) );
		}

		return $data;
	}
}
