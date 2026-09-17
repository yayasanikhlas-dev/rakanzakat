<?php
/**
 * Register Elementor category + widget when Elementor is active.
 *
 * @package RakanZakat
 */

defined( 'ABSPATH' ) || exit;

class RakanZakat_Elementor {

	public static function init() {
		add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'category' ) );
		add_action( 'elementor/widgets/register', array( __CLASS__, 'widgets' ) );
		add_action( 'elementor/preview/enqueue_scripts', array( 'RakanZakat_Shortcode', 'enqueue_public' ) );
		add_action( 'elementor/frontend/after_enqueue_scripts', array( 'RakanZakat_Shortcode', 'enqueue_public' ) );
	}

	public static function category( $elements_manager ) {
		$elements_manager->add_category(
			'rakanzakat',
			array(
				'title' => 'Rakan Zakat',
				'icon'  => 'fa fa-heart',
			)
		);
	}

	public static function widgets( $widgets_manager ) {
		require_once RAKANZAKAT_PATH . 'includes/class-elementor-form.php';
		$widgets_manager->register( new RakanZakat_Elementor_Form_Widget() );
	}
}
