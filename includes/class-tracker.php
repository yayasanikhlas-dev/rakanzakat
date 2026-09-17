<?php
/**
 * Visitor tracking and daily rollups.
 *
 * @package RakanZakat
 */

defined( 'ABSPATH' ) || exit;

class RakanZakat_Tracker {

	public static function visits_table() {
		global $wpdb;
		return $wpdb->prefix . 'rz_visits';
	}

	public static function stats_table() {
		global $wpdb;
		return $wpdb->prefix . 'rz_daily_stats';
	}

	public static function record_visit( $input ) {
		if ( is_admin() ) {
			return false;
		}

		$settings = RakanZakat_Settings::get_all();
		if ( empty( $settings['track_admins'] ) && current_user_can( 'manage_options' ) ) {
			return false;
		}

		$visitor_id = sanitize_text_field( $input['visitor_id'] ?? '' );
		$session_id = sanitize_text_field( $input['session_id'] ?? '' );
		if ( ! $visitor_id || ! $session_id ) {
			return false;
		}

		if ( ! preg_match( '/^[a-zA-Z0-9_-]{8,64}$/', $visitor_id ) || ! preg_match( '/^[a-zA-Z0-9_-]{8,64}$/', $session_id ) ) {
			return false;
		}

		$page_url = esc_url_raw( $input['page_url'] ?? '' );
		if ( ! $page_url ) {
			return false;
		}

		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 ) : '';

		global $wpdb;
		$is_new = (int) ( $input['is_new_session'] ?? 0 ) ? 1 : 0;

		$existing_session = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM ' . self::visits_table() . ' WHERE session_id = %s LIMIT 1',
				$session_id
			)
		);
		if ( $existing_session ) {
			$is_new = 0;
		}

		$wpdb->insert(
			self::visits_table(),
			array(
				'visitor_id'     => $visitor_id,
				'session_id'     => $session_id,
				'page_url'       => substr( $page_url, 0, 500 ),
				'page_title'     => sanitize_text_field( substr( $input['page_title'] ?? '', 0, 191 ) ),
				'referrer'       => esc_url_raw( $input['referrer'] ?? '' ),
				'utm_source'     => sanitize_text_field( $input['utm_source'] ?? '' ),
				'utm_medium'     => sanitize_text_field( $input['utm_medium'] ?? '' ),
				'utm_campaign'   => sanitize_text_field( $input['utm_campaign'] ?? '' ),
				'utm_content'    => sanitize_text_field( $input['utm_content'] ?? '' ),
				'utm_term'       => sanitize_text_field( $input['utm_term'] ?? '' ),
				'landing_page'   => esc_url_raw( $input['landing_page'] ?? $page_url ),
				'is_new_session' => $is_new,
				'ip_hash'        => hash( 'sha256', $ip . wp_salt( 'auth' ) ),
				'user_agent'     => $ua,
				'created_at'     => current_time( 'mysql' ),
			)
		);

		self::bump_daily( 1, $is_new ? 1 : 0, self::is_new_visitor_today( $visitor_id ) ? 1 : 0, 0, 0 );
		self::prune_old_visits();

		return true;
	}

	public static function record_conversion( $amount_sen, $paid_at = null ) {
		self::bump_daily( 0, 0, 0, 1, (int) $amount_sen, $paid_at );
	}

	public static function summarize( $from, $to ) {
		global $wpdb;
		$table = self::visits_table();

		$pageviews = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE created_at >= %s AND created_at < %s", $from, $to )
		);
		$sessions = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(DISTINCT session_id) FROM {$table} WHERE created_at >= %s AND created_at < %s", $from, $to )
		);
		$visitors = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(DISTINCT visitor_id) FROM {$table} WHERE created_at >= %s AND created_at < %s", $from, $to )
		);

		return compact( 'pageviews', 'sessions', 'visitors' );
	}

	public static function daily_visits( $from, $to ) {
		global $wpdb;
		$table = self::visits_table();
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(created_at) AS d,
				        COUNT(*) AS pageviews,
				        COUNT(DISTINCT session_id) AS sessions,
				        COUNT(DISTINCT visitor_id) AS visitors
				 FROM {$table}
				 WHERE created_at >= %s AND created_at < %s
				 GROUP BY DATE(created_at)
				 ORDER BY d ASC",
				$from,
				$to
			)
		);
	}

	public static function sources( $from, $to ) {
		global $wpdb;
		$table = self::visits_table();
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					CASE
						WHEN utm_source <> '' THEN utm_source
						WHEN referrer = '' THEN 'direct'
						ELSE 'referral'
					END AS source,
					COUNT(DISTINCT visitor_id) AS visitors,
					COUNT(*) AS pageviews
				 FROM {$table}
				 WHERE created_at >= %s AND created_at < %s
				 GROUP BY source
				 ORDER BY visitors DESC
				 LIMIT 12",
				$from,
				$to
			)
		);
	}

	public static function recent( $limit = 40 ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . self::visits_table() . ' ORDER BY created_at DESC LIMIT %d',
				$limit
			)
		);
	}

	public static function rollup_daily() {
		global $wpdb;
		$yesterday = wp_date( 'Y-m-d', strtotime( '-1 day', current_time( 'timestamp' ) ) );
		$from      = $yesterday . ' 00:00:00';
		$to        = wp_date( 'Y-m-d', current_time( 'timestamp' ) ) . ' 00:00:00';
		$visits    = self::summarize( $from, $to );
		$payments  = RakanZakat_Payments::summarize( $from, $to );

		$wpdb->replace(
			self::stats_table(),
			array(
				'stat_date'       => $yesterday,
				'visitors'        => $visits['visitors'],
				'sessions'        => $visits['sessions'],
				'pageviews'       => $visits['pageviews'],
				'payments'        => $payments['paid_count'],
				'paid_amount_sen' => $payments['paid_sen'],
			)
		);
	}

	private static function is_new_visitor_today( $visitor_id ) {
		global $wpdb;
		$start = wp_date( 'Y-m-d' ) . ' 00:00:00';
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . self::visits_table() . ' WHERE visitor_id = %s AND created_at >= %s',
				$visitor_id,
				$start
			)
		);
		return $count <= 1;
	}

	private static function bump_daily( $pageviews, $sessions, $visitors, $payments, $paid_sen, $when = null ) {
		global $wpdb;
		$date = $when ? substr( $when, 0, 10 ) : wp_date( 'Y-m-d' );
		$table = self::stats_table();
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (stat_date, visitors, sessions, pageviews, payments, paid_amount_sen)
				 VALUES (%s, %d, %d, %d, %d, %d)
				 ON DUPLICATE KEY UPDATE
				    visitors = visitors + VALUES(visitors),
				    sessions = sessions + VALUES(sessions),
				    pageviews = pageviews + VALUES(pageviews),
				    payments = payments + VALUES(payments),
				    paid_amount_sen = paid_amount_sen + VALUES(paid_amount_sen)",
				$date,
				$visitors,
				$sessions,
				$pageviews,
				$payments,
				$paid_sen
			)
		);
	}

	private static function prune_old_visits() {
		if ( wp_rand( 1, 50 ) !== 1 ) {
			return;
		}
		global $wpdb;
		$cutoff = wp_date( 'Y-m-d H:i:s', strtotime( '-90 days', current_time( 'timestamp' ) ) );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::visits_table() . ' WHERE created_at < %s', $cutoff ) );
	}
}
