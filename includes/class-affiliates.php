<?php
/**
 * Affiliates, referrals, and payouts.
 *
 * @package RakanZakat
 */

defined( 'ABSPATH' ) || exit;

class RakanZakat_Affiliates {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'capture_ref' ), 1 );
	}

	public static function capture_ref() {
		if ( empty( $_GET['ref'] ) ) {
			return;
		}
		$code = sanitize_title( wp_unslash( $_GET['ref'] ) );
		if ( ! $code || ! self::by_code( $code ) ) {
			return;
		}
		$expire = time() + ( 30 * DAY_IN_SECONDS );
		$path   = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
		$domain = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '';
		setcookie( 'rz_ref', $code, $expire, $path, $domain, is_ssl(), true );
		$_COOKIE['rz_ref'] = $code;
	}

	public static function request_code() {
		$candidates = array();
		if ( ! empty( $_GET['ref'] ) ) {
			$candidates[] = sanitize_title( wp_unslash( $_GET['ref'] ) );
		}
		if ( ! empty( $_COOKIE['rz_ref'] ) ) {
			$candidates[] = sanitize_title( wp_unslash( $_COOKIE['rz_ref'] ) );
		}
		foreach ( $candidates as $code ) {
			if ( $code && self::by_code( $code ) ) {
				return $code;
			}
		}
		return '';
	}

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'rz_affiliates';
	}

	public static function payouts_table() {
		global $wpdb;
		return $wpdb->prefix . 'rz_payouts';
	}

	public static function all() {
		global $wpdb;
		return $wpdb->get_results(
			'SELECT a.*, u.display_name, u.user_email
			 FROM ' . self::table() . ' a
			 LEFT JOIN ' . $wpdb->users . ' u ON u.ID = a.user_id
			 ORDER BY a.id DESC'
		);
	}

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', (int) $id ) );
	}

	public static function by_user( $user_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE user_id = %d', (int) $user_id ) );
	}

	public static function by_code( $code ) {
		$code = sanitize_title( $code );
		if ( ! $code ) {
			return null;
		}
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM " . self::table() . " WHERE code = %s AND status = 'active'",
				$code
			)
		);
	}

	public static function match_id( $ref = '', $utm_source = '', $utm_campaign = '' ) {
		foreach ( array( $ref, $utm_source, $utm_campaign ) as $code ) {
			$row = self::by_code( $code );
			if ( $row ) {
				return (int) $row->id;
			}
		}
		return 0;
	}

	public static function unique_code( $seed ) {
		$base = sanitize_title( $seed );
		if ( strlen( $base ) < 3 ) {
			$base = 'aff' . wp_generate_password( 6, false, false );
		}
		$base = substr( $base, 0, 24 );
		$code = $base;
		$i    = 2;
		while ( self::by_code( $code ) ) {
			$code = $base . $i;
			++$i;
		}
		return $code;
	}

	public static function create( $data ) {
		$email    = sanitize_email( $data['email'] ?? '' );
		$name     = sanitize_text_field( $data['name'] ?? '' );
		$password = (string) ( $data['password'] ?? '' );
		$rate     = (float) ( $data['commission_pct'] ?? 10 );
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'rz_aff_email', __( 'Emel affiliate tidak sah.', 'rakanzakat' ) );
		}
		if ( strlen( $name ) < 2 ) {
			return new WP_Error( 'rz_aff_name', __( 'Sila masukkan nama affiliate.', 'rakanzakat' ) );
		}
		if ( strlen( $password ) < 8 ) {
			return new WP_Error( 'rz_aff_pass', __( 'Katalaluan sekurang-kurangnya 8 aksara.', 'rakanzakat' ) );
		}

		$user_id = wp_insert_user(
			array(
				'user_login'   => sanitize_user( strtok( $email, '@' ) . wp_generate_password( 3, false, false ), true ),
				'user_email'   => $email,
				'user_pass'    => $password,
				'display_name' => $name,
				'role'         => 'rz_affiliate',
			)
		);
		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		$code = self::unique_code( ! empty( $data['code'] ) ? $data['code'] : $name );
		$bp   = max( 0, min( 10000, (int) round( $rate * 100 ) ) );

		global $wpdb;
		$wpdb->insert(
			self::table(),
			array(
				'user_id'        => (int) $user_id,
				'code'           => $code,
				'commission_bp'  => $bp,
				'status'         => 'active',
				'created_at'     => current_time( 'mysql' ),
			)
		);
		return (int) $wpdb->insert_id;
	}

	public static function commission_sen( $paid_amount_sen, $bp ) {
		return (int) floor( ( (int) $paid_amount_sen * (int) $bp ) / 10000 );
	}

	public static function link( $code ) {
		$pages = get_option( 'rakanzakat_pages', array() );
		$pay   = ! empty( $pages['pay'] ) ? get_permalink( $pages['pay'] ) : home_url( '/' );
		return add_query_arg( 'ref', $code, $pay );
	}

	public static function stats( $affiliate_id, $from = null, $to = null ) {
		global $wpdb;
		$aff      = self::get( $affiliate_id );
		$payments = RakanZakat_Payments::table();
		$visits   = RakanZakat_Tracker::visits_table();
		$payouts  = self::payouts_table();
		$id       = (int) $affiliate_id;
		$bp       = $aff ? (int) $aff->commission_bp : 1000;

		$visit_sql = "SELECT COUNT(*) FROM {$visits} WHERE affiliate_id = %d AND is_new_session = 1";
		$pay_sql   = "SELECT COUNT(*) FROM {$payments} WHERE affiliate_id = %d AND status = 'paid'";
		$rev_sql   = "SELECT COALESCE(SUM(paid_amount_sen),0) FROM {$payments} WHERE affiliate_id = %d AND status = 'paid'";
		$params    = array( $id );
		if ( $from && $to ) {
			$visit_sql .= ' AND created_at >= %s AND created_at < %s';
			$pay_sql   .= ' AND paid_at >= %s AND paid_at < %s';
			$rev_sql   .= ' AND paid_at >= %s AND paid_at < %s';
			$params[]   = $from;
			$params[]   = $to;
		}

		$visits_n = (int) $wpdb->get_var( $wpdb->prepare( $visit_sql, $params ) );
		$refs     = (int) $wpdb->get_var( $wpdb->prepare( $pay_sql, $params ) );
		$rev      = (int) $wpdb->get_var( $wpdb->prepare( $rev_sql, $params ) );
		$earn     = self::commission_sen( $rev, $bp );
		$conv     = $visits_n > 0 ? round( ( $refs / $visits_n ) * 100, 2 ) : 0;

		$unpaid_refs = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$payments} WHERE affiliate_id = %d AND status = 'paid' AND (payout_id IS NULL OR payout_id = 0)",
				$id
			)
		);
		$paid_refs   = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$payments} WHERE affiliate_id = %d AND status = 'paid' AND payout_id IS NOT NULL AND payout_id > 0",
				$id
			)
		);
		$paid_out    = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(amount_sen),0) FROM {$payouts} WHERE affiliate_id = %d AND status = 'paid'",
				$id
			)
		);
		$total_earn  = self::commission_sen(
			(int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COALESCE(SUM(paid_amount_sen),0) FROM {$payments} WHERE affiliate_id = %d AND status = 'paid'",
					$id
				)
			),
			$bp
		);
		$unpaid_earn = max( 0, $total_earn - $paid_out );

		return array(
			'visits'          => $visits_n,
			'referrals'       => $refs,
			'conversion'      => $conv,
			'revenue_sen'     => $rev,
			'earnings_sen'    => $earn,
			'unpaid_refs'     => $unpaid_refs,
			'paid_refs'       => $paid_refs,
			'unpaid_earn_sen' => $unpaid_earn,
			'total_earn_sen'  => $total_earn,
			'paid_out_sen'    => $paid_out,
			'commission_bp'   => $bp,
		);
	}

	public static function referrals( $affiliate_id, $limit = 50 ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . RakanZakat_Payments::table() . '
				 WHERE affiliate_id = %d AND status = %s
				 ORDER BY paid_at DESC, id DESC
				 LIMIT %d',
				(int) $affiliate_id,
				'paid',
				(int) $limit
			)
		);
	}

	public static function visits( $affiliate_id, $limit = 50 ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . RakanZakat_Tracker::visits_table() . '
				 WHERE affiliate_id = %d
				 ORDER BY created_at DESC
				 LIMIT %d',
				(int) $affiliate_id,
				(int) $limit
			)
		);
	}

	public static function payouts( $affiliate_id ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . self::payouts_table() . ' WHERE affiliate_id = %d ORDER BY created_at DESC',
				(int) $affiliate_id
			)
		);
	}

	public static function pay_unpaid( $affiliate_id, $notes = '' ) {
		$stats = self::stats( $affiliate_id );
		if ( $stats['unpaid_earn_sen'] <= 0 ) {
			return new WP_Error( 'rz_payout', __( 'Tiada komisen tertunggak.', 'rakanzakat' ) );
		}
		global $wpdb;
		$now = current_time( 'mysql' );
		$wpdb->insert(
			self::payouts_table(),
			array(
				'affiliate_id' => (int) $affiliate_id,
				'amount_sen'   => $stats['unpaid_earn_sen'],
				'status'       => 'paid',
				'notes'        => sanitize_textarea_field( $notes ),
				'paid_at'      => $now,
				'created_at'   => $now,
			)
		);
		$payout_id = (int) $wpdb->insert_id;
		$wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . RakanZakat_Payments::table() . '
				 SET payout_id = %d
				 WHERE affiliate_id = %d AND status = %s AND (payout_id IS NULL OR payout_id = 0)',
				$payout_id,
				(int) $affiliate_id,
				'paid'
			)
		);
		return $payout_id;
	}

	public static function daily( $affiliate_id, $from, $to ) {
		global $wpdb;
		$id       = (int) $affiliate_id;
		$visits   = RakanZakat_Tracker::visits_table();
		$payments = RakanZakat_Payments::table();
		$v_rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(created_at) AS d, COUNT(*) AS n
				 FROM {$visits}
				 WHERE affiliate_id = %d AND created_at >= %s AND created_at < %s
				 GROUP BY DATE(created_at)",
				$id,
				$from,
				$to
			)
		);
		$p_rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(paid_at) AS d, COUNT(*) AS n
				 FROM {$payments}
				 WHERE affiliate_id = %d AND status = 'paid' AND paid_at >= %s AND paid_at < %s
				 GROUP BY DATE(paid_at)",
				$id,
				$from,
				$to
			)
		);
		$map = array();
		$start = strtotime( substr( $from, 0, 10 ) . ' 00:00:00' );
		$end   = strtotime( substr( $to, 0, 10 ) . ' 00:00:00' );
		for ( $ts = $start; $ts < $end; $ts += DAY_IN_SECONDS ) {
			$key         = wp_date( 'Y-m-d', $ts );
			$map[ $key ] = array(
				'date'      => $key,
				'visits'    => 0,
				'referrals' => 0,
			);
		}
		foreach ( $v_rows as $row ) {
			if ( isset( $map[ $row->d ] ) ) {
				$map[ $row->d ]['visits'] = (int) $row->n;
			}
		}
		foreach ( $p_rows as $row ) {
			if ( isset( $map[ $row->d ] ) ) {
				$map[ $row->d ]['referrals'] = (int) $row->n;
			}
		}
		return array_values( $map );
	}
}
