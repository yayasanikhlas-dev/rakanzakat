<?php
/**
 * Cleanup on plugin delete.
 *
 * @package RakanZakat
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

$tables = array(
	$wpdb->prefix . 'rz_payments',
	$wpdb->prefix . 'rz_visits',
	$wpdb->prefix . 'rz_daily_stats',
	$wpdb->prefix . 'rz_campaigns',
	$wpdb->prefix . 'rz_ad_spend',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

delete_option( 'rakanzakat_settings' );
delete_option( 'rakanzakat_db_version' );
delete_option( 'rakanzakat_pages' );
wp_clear_scheduled_hook( 'rakanzakat_daily_rollup' );
