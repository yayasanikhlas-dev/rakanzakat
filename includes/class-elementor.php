<?php
/**
 * Register Elementor category + widget when Elementor is active.
 *
 * @package RakanZakat
 */

defined( 'ABSPATH' ) || exit;

class RakanZakat_Elementor {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ), 5 );
		add_action( 'elementor/frontend/before_enqueue_styles', array( __CLASS__, 'register_assets' ) );
		add_action( 'elementor/editor/before_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'category' ) );
		add_action( 'elementor/widgets/register', array( __CLASS__, 'widgets' ) );
		add_action( 'elementor/preview/enqueue_scripts', array( 'RakanZakat_Shortcode', 'enqueue_public' ) );
		add_action( 'elementor/frontend/after_enqueue_scripts', array( 'RakanZakat_Shortcode', 'enqueue_public' ) );
		add_action( 'elementor/frontend/after_enqueue_styles', array( __CLASS__, 'styles' ) );
		add_action( 'elementor/preview/enqueue_styles', array( __CLASS__, 'styles' ) );
	}

	public static function register_assets() {
		wp_register_style(
			'rakanzakat-jakarta',
			'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap',
			array(),
			null
		);
		wp_register_style(
			'rakanzakat-stitch',
			RAKANZAKAT_URL . 'public/css/stitch-sections.css',
			array( 'rakanzakat-jakarta' ),
			RAKANZAKAT_VERSION
		);
		wp_register_script(
			'rakanzakat-stitch',
			RAKANZAKAT_URL . 'public/js/stitch-sections.js',
			array(),
			RAKANZAKAT_VERSION,
			true
		);
		wp_register_style(
			'rakanzakat-landing',
			RAKANZAKAT_URL . 'public/css/landing.css',
			array( 'rakanzakat-form' ),
			RAKANZAKAT_VERSION
		);
	}

	public static function styles() {
		self::register_assets();
		wp_enqueue_style( 'rakanzakat-jakarta' );
		wp_enqueue_style( 'rakanzakat-stitch' );
		wp_enqueue_script( 'rakanzakat-stitch' );
		wp_enqueue_style( 'rakanzakat-landing' );
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
		require_once RAKANZAKAT_PATH . 'includes/class-stitch-sections.php';
		require_once RAKANZAKAT_PATH . 'includes/class-elementor-stitch.php';
		$widgets_manager->register( new RakanZakat_Elementor_Form_Widget() );
		$widgets_manager->register( new RakanZakat_Elementor_Guide_Widget() );
		$widgets_manager->register( new RakanZakat_Elementor_Cats_Widget() );
		$widgets_manager->register( new RakanZakat_Elementor_Tiga_Widget() );
		$widgets_manager->register( new RakanZakat_Elementor_Official_Widget() );
		$widgets_manager->register( new RakanZakat_Elementor_Impact_Widget() );
		$widgets_manager->register( new RakanZakat_Elementor_Faq2_Widget() );
		$widgets_manager->register( new RakanZakat_Elementor_Cta_Widget() );
	}
}
