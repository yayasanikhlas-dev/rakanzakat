<?php
/**
 * Full-page landing template (ads / homepage).
 *
 * @package RakanZakat
 */

defined( 'ABSPATH' ) || exit;

class RakanZakat_Landing {

	const TEMPLATE = 'rakanzakat-landing.php';

	public static function init() {
		add_filter( 'theme_page_templates', array( __CLASS__, 'register_template' ) );
		add_filter( 'template_include', array( __CLASS__, 'include_template' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ), 20 );
	}

	public static function register_template( $templates ) {
		$templates[ self::TEMPLATE ] = 'Rakan Zakat — Landing';
		return $templates;
	}

	public static function include_template( $template ) {
		if ( ! is_singular( 'page' ) ) {
			return $template;
		}
		if ( self::TEMPLATE !== get_page_template_slug( get_queried_object_id() ) ) {
			return $template;
		}
		$file = RAKANZAKAT_PATH . 'templates/landing.php';
		return file_exists( $file ) ? $file : $template;
	}

	public static function is_landing() {
		return is_singular( 'page' ) && self::TEMPLATE === get_page_template_slug( get_queried_object_id() );
	}

	public static function assets() {
		if ( ! self::is_landing() ) {
			return;
		}
		wp_enqueue_style(
			'rakanzakat-landing',
			RAKANZAKAT_URL . 'public/css/landing.css',
			array( 'rakanzakat-form' ),
			RAKANZAKAT_VERSION
		);
	}

	public static function maybe_create_page() {
		$pages = get_option( 'rakanzakat_pages', array() );
		if ( ! empty( $pages['landing'] ) && get_post( $pages['landing'] ) ) {
			return (int) $pages['landing'];
		}

		$id = wp_insert_post(
			array(
				'post_title'   => 'Rakan Zakat',
				'post_name'    => 'zakat',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			)
		);

		if ( is_wp_error( $id ) || ! $id ) {
			return 0;
		}

		update_post_meta( (int) $id, '_wp_page_template', self::TEMPLATE );
		$pages['landing'] = (int) $id;
		update_option( 'rakanzakat_pages', $pages );

		return (int) $id;
	}
}
