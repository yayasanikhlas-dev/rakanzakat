<?php
/**
 * Plugin bootstrap.
 *
 * @package RakanZakat
 */

defined( 'ABSPATH' ) || exit;

class RakanZakat_Plugin {

	private static $instance;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function activate() {
		require_once RAKANZAKAT_PATH . 'includes/class-activator.php';
		RakanZakat_Activator::activate();
	}

	public static function deactivate() {
		require_once RAKANZAKAT_PATH . 'includes/class-activator.php';
		RakanZakat_Activator::deactivate();
	}

	private function __construct() {
		$this->includes();
		RakanZakat_Activator::maybe_upgrade();
		add_action( 'rest_api_init', array( 'RakanZakat_REST', 'register' ) );
		add_action( 'init', array( 'RakanZakat_Shortcode', 'register' ) );
		add_action( 'init', array( 'RakanZakat_Blocks', 'register_block' ) );
		add_action( 'widgets_init', array( 'RakanZakat_Blocks', 'register_widget' ) );
		add_action( 'wp_enqueue_scripts', array( 'RakanZakat_Shortcode', 'enqueue_public' ) );
		RakanZakat_Elementor::init();
		RakanZakat_Landing::init();
		add_action( 'admin_init', array( 'RakanZakat_Landing', 'maybe_create_page' ) );
		add_action( 'admin_menu', array( 'RakanZakat_Admin', 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( 'RakanZakat_Admin', 'assets' ) );
		add_action( 'admin_init', array( 'RakanZakat_Admin', 'handle_actions' ) );
		add_action( 'admin_post_rakanzakat_export', array( 'RakanZakat_Payments', 'export_csv' ) );
		add_action( 'rakanzakat_daily_rollup', array( 'RakanZakat_Tracker', 'rollup_daily' ) );
		add_filter( 'plugin_action_links_' . RAKANZAKAT_BASENAME, array( $this, 'action_links' ) );
		RakanZakat_Updater::init();
	}

	private function includes() {
		require_once RAKANZAKAT_PATH . 'includes/class-activator.php';
		require_once RAKANZAKAT_PATH . 'includes/class-settings.php';
		require_once RAKANZAKAT_PATH . 'includes/class-billplz.php';
		require_once RAKANZAKAT_PATH . 'includes/class-campaigns.php';
		require_once RAKANZAKAT_PATH . 'includes/class-payments.php';
		require_once RAKANZAKAT_PATH . 'includes/class-tracker.php';
		require_once RAKANZAKAT_PATH . 'includes/class-rest.php';
		require_once RAKANZAKAT_PATH . 'includes/class-shortcode.php';
		require_once RAKANZAKAT_PATH . 'includes/class-widget.php';
		require_once RAKANZAKAT_PATH . 'includes/class-elementor.php';
		require_once RAKANZAKAT_PATH . 'includes/class-landing.php';
		require_once RAKANZAKAT_PATH . 'includes/class-updater.php';
		require_once RAKANZAKAT_PATH . 'admin/class-admin.php';
	}

	public function action_links( $links ) {
		$url     = admin_url( 'admin.php?page=rakanzakat-settings' );
		$links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Tetapan', 'rakanzakat' ) . '</a>';
		return $links;
	}
}
