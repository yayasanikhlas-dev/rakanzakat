<?php
/**
 * Activation: tables, options, default pages.
 *
 * @package RakanZakat
 */

defined( 'ABSPATH' ) || exit;

class RakanZakat_Activator {

	const DB_VERSION = '1.4.0';

	public static function activate() {
		self::create_tables();
		self::seed_options();
		self::create_pages();
		self::register_roles();
		flush_rewrite_rules();
		require_once RAKANZAKAT_PATH . 'includes/class-landing.php';
		RakanZakat_Landing::maybe_create_page();
		if ( ! wp_next_scheduled( 'rakanzakat_daily_rollup' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'rakanzakat_daily_rollup' );
		}
		if ( ! class_exists( 'RakanZakat_Portal' ) ) {
			require_once RAKANZAKAT_PATH . 'includes/class-portal.php';
		}
		RakanZakat_Portal::rewrite();
		flush_rewrite_rules();
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( 'rakanzakat_daily_rollup' );
		flush_rewrite_rules();
	}

	public static function maybe_upgrade() {
		$mgr = get_role( 'rz_manager' );
		if ( $mgr && ! $mgr->has_cap( 'upload_files' ) ) {
			$mgr->add_cap( 'upload_files' );
		}
		if ( get_option( 'rakanzakat_db_version' ) !== self::DB_VERSION ) {
			self::create_tables();
			self::register_roles();
			if ( class_exists( 'RakanZakat_Portal' ) ) {
				RakanZakat_Portal::rewrite();
			}
			flush_rewrite_rules();
			delete_option( 'rakanzakat_rewrite' );
		}
	}

	public static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$p       = $wpdb->prefix;

		$sql = array();

		$sql[] = "CREATE TABLE {$p}rz_payments (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			bill_id varchar(64) NOT NULL DEFAULT '',
			collection_id varchar(64) NOT NULL DEFAULT '',
			payer_name varchar(191) NOT NULL DEFAULT '',
			payer_email varchar(191) NOT NULL DEFAULT '',
			payer_mobile varchar(32) NOT NULL DEFAULT '',
			zakat_type varchar(64) NOT NULL DEFAULT '',
			id_type varchar(64) NOT NULL DEFAULT '',
			id_number varchar(64) NOT NULL DEFAULT '',
			address_1 varchar(191) NOT NULL DEFAULT '',
			address_2 varchar(191) NOT NULL DEFAULT '',
			city varchar(100) NOT NULL DEFAULT '',
			state varchar(64) NOT NULL DEFAULT '',
			postcode varchar(16) NOT NULL DEFAULT '',
			haul_year varchar(8) NOT NULL DEFAULT '',
			payer_meta longtext NULL,
			amount_sen bigint(20) NOT NULL DEFAULT 0,
			paid_amount_sen bigint(20) NOT NULL DEFAULT 0,
			status varchar(32) NOT NULL DEFAULT 'pending',
			paid_at datetime NULL,
			bill_url varchar(255) NOT NULL DEFAULT '',
			transaction_id varchar(64) NOT NULL DEFAULT '',
			visitor_id varchar(64) NOT NULL DEFAULT '',
			session_id varchar(64) NOT NULL DEFAULT '',
			utm_source varchar(100) NOT NULL DEFAULT '',
			utm_medium varchar(100) NOT NULL DEFAULT '',
			utm_campaign varchar(100) NOT NULL DEFAULT '',
			utm_content varchar(100) NOT NULL DEFAULT '',
			utm_term varchar(100) NOT NULL DEFAULT '',
			landing_page varchar(255) NOT NULL DEFAULT '',
			referrer varchar(255) NOT NULL DEFAULT '',
			campaign_id bigint(20) unsigned NULL,
			affiliate_id bigint(20) unsigned NULL,
			payout_id bigint(20) unsigned NULL,
			raw_payload longtext NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY bill_id (bill_id),
			KEY status_paid (status, paid_at),
			KEY campaign_id (campaign_id),
			KEY affiliate_id (affiliate_id),
			KEY created_at (created_at)
		) {$charset};";

		$sql[] = "CREATE TABLE {$p}rz_visits (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			visitor_id varchar(64) NOT NULL DEFAULT '',
			session_id varchar(64) NOT NULL DEFAULT '',
			page_url varchar(500) NOT NULL DEFAULT '',
			page_title varchar(191) NOT NULL DEFAULT '',
			referrer varchar(500) NOT NULL DEFAULT '',
			utm_source varchar(100) NOT NULL DEFAULT '',
			utm_medium varchar(100) NOT NULL DEFAULT '',
			utm_campaign varchar(100) NOT NULL DEFAULT '',
			utm_content varchar(100) NOT NULL DEFAULT '',
			utm_term varchar(100) NOT NULL DEFAULT '',
			affiliate_id bigint(20) unsigned NULL,
			landing_page varchar(500) NOT NULL DEFAULT '',
			is_new_session tinyint(1) NOT NULL DEFAULT 0,
			ip_hash varchar(64) NOT NULL DEFAULT '',
			user_agent varchar(255) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY visitor_created (visitor_id, created_at),
			KEY session_id (session_id),
			KEY created_at (created_at),
			KEY affiliate_id (affiliate_id)
		) {$charset};";

		$sql[] = "CREATE TABLE {$p}rz_daily_stats (
			stat_date date NOT NULL,
			visitors int(11) NOT NULL DEFAULT 0,
			sessions int(11) NOT NULL DEFAULT 0,
			pageviews int(11) NOT NULL DEFAULT 0,
			payments int(11) NOT NULL DEFAULT 0,
			paid_amount_sen bigint(20) NOT NULL DEFAULT 0,
			PRIMARY KEY  (stat_date)
		) {$charset};";

		$sql[] = "CREATE TABLE {$p}rz_campaigns (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(191) NOT NULL DEFAULT '',
			platform varchar(64) NOT NULL DEFAULT '',
			utm_source varchar(100) NOT NULL DEFAULT '',
			utm_medium varchar(100) NOT NULL DEFAULT '',
			utm_campaign varchar(100) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'active',
			notes text NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY utm_campaign (utm_campaign)
		) {$charset};";

		$sql[] = "CREATE TABLE {$p}rz_ad_spend (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			campaign_id bigint(20) unsigned NULL,
			platform varchar(64) NOT NULL DEFAULT '',
			spend_date date NOT NULL,
			amount_sen bigint(20) NOT NULL DEFAULT 0,
			notes text NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY spend_date (spend_date),
			KEY campaign_id (campaign_id)
		) {$charset};";

		$sql[] = "CREATE TABLE {$p}rz_affiliates (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			code varchar(64) NOT NULL DEFAULT '',
			commission_bp int(11) NOT NULL DEFAULT 1000,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY code (code),
			KEY user_id (user_id)
		) {$charset};";

		$sql[] = "CREATE TABLE {$p}rz_payouts (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			affiliate_id bigint(20) unsigned NOT NULL DEFAULT 0,
			amount_sen bigint(20) NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT 'paid',
			notes text NULL,
			paid_at datetime NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY affiliate_id (affiliate_id)
		) {$charset};";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}

		update_option( 'rakanzakat_db_version', self::DB_VERSION );
	}

	private static function seed_options() {
		$existing = get_option( 'rakanzakat_settings' );
		if ( is_array( $existing ) ) {
			return;
		}

		add_option(
			'rakanzakat_settings',
			array(
				'api_key'        => '',
				'x_signature'    => '',
				'collection_id'  => '',
				'sandbox'        => 1,
				'min_amount'     => 10,
				'thankyou_page'  => 0,
				'failed_page'    => 0,
				'track_admins'   => 0,
			)
		);
	}

	private static function create_pages() {
		$pages = get_option( 'rakanzakat_pages', array() );
		if ( ! empty( $pages['pay'] ) && get_post( $pages['pay'] ) ) {
			return;
		}

		$pay_id = wp_insert_post(
			array(
				'post_title'   => 'Bayar Zakat',
				'post_name'    => 'bayar-zakat',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => "<!-- wp:shortcode -->\n[rakanzakat_form]\n<!-- /wp:shortcode -->",
			)
		);

		$thanks_id = wp_insert_post(
			array(
				'post_title'   => 'Terima Kasih',
				'post_name'    => 'terima-kasih',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => "<!-- wp:shortcode -->\n[rakanzakat_receipt]\n<!-- /wp:shortcode -->",
			)
		);

		$pages = array(
			'pay'    => (int) $pay_id,
			'thanks' => (int) $thanks_id,
		);
		update_option( 'rakanzakat_pages', $pages );

		$settings                   = get_option( 'rakanzakat_settings', array() );
		$settings['thankyou_page']  = (int) $thanks_id;
		update_option( 'rakanzakat_settings', $settings );
	}

	public static function register_roles() {
		add_role(
			'rz_manager',
			__( 'Rakan Zakat Admin', 'rakanzakat' ),
			array(
				'read'             => true,
				'upload_files'     => true,
				'rz_manage_portal' => true,
			)
		);
		add_role(
			'rz_affiliate',
			__( 'Rakan Zakat Affiliate', 'rakanzakat' ),
			array(
				'read'                => true,
				'rz_affiliate_portal' => true,
			)
		);
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin->add_cap( 'rz_manage_portal' );
			$admin->add_cap( 'rz_affiliate_portal' );
		}
	}
}
