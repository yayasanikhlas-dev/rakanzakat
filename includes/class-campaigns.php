<?php
/**
 * Campaigns and ads spend.
 *
 * @package RakanZakat
 */

defined( 'ABSPATH' ) || exit;

class RakanZakat_Campaigns {

	public static function campaigns_table() {
		global $wpdb;
		return $wpdb->prefix . 'rz_campaigns';
	}

	public static function spend_table() {
		global $wpdb;
		return $wpdb->prefix . 'rz_ad_spend';
	}

	public static function all_campaigns() {
		global $wpdb;
		return $wpdb->get_results( 'SELECT * FROM ' . self::campaigns_table() . ' ORDER BY created_at DESC' );
	}

	public static function match_id( $utm_campaign, $utm_source = '' ) {
		global $wpdb;
		$table = self::campaigns_table();
		if ( $utm_campaign ) {
			$id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table} WHERE utm_campaign = %s AND status = 'active' ORDER BY id DESC LIMIT 1",
					$utm_campaign
				)
			);
			if ( $id ) {
				return (int) $id;
			}
		}
		if ( $utm_source ) {
			$id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table} WHERE utm_source = %s AND status = 'active' ORDER BY id DESC LIMIT 1",
					$utm_source
				)
			);
			if ( $id ) {
				return (int) $id;
			}
		}
		return 0;
	}

	public static function create_campaign( $data ) {
		global $wpdb;
		$wpdb->insert(
			self::campaigns_table(),
			array(
				'name'         => sanitize_text_field( $data['name'] ?? '' ),
				'platform'     => sanitize_key( $data['platform'] ?? 'other' ),
				'utm_source'   => sanitize_text_field( $data['utm_source'] ?? '' ),
				'utm_medium'   => sanitize_text_field( $data['utm_medium'] ?? '' ),
				'utm_campaign' => sanitize_text_field( $data['utm_campaign'] ?? '' ),
				'status'       => 'active',
				'notes'        => sanitize_textarea_field( $data['notes'] ?? '' ),
				'created_at'   => current_time( 'mysql' ),
			)
		);
		return (int) $wpdb->insert_id;
	}

	public static function add_spend( $data ) {
		global $wpdb;
		$amount = RakanZakat_Settings::to_sen( $data['amount'] ?? 0 );
		if ( $amount <= 0 ) {
			return new WP_Error( 'rz_spend', __( 'Amaun spend mesti lebih dari 0.', 'rakanzakat' ) );
		}

		$campaign_id = (int) ( $data['campaign_id'] ?? 0 );
		$wpdb->insert(
			self::spend_table(),
			array(
				'campaign_id' => $campaign_id ? $campaign_id : null,
				'platform'    => sanitize_key( $data['platform'] ?? 'other' ),
				'spend_date'  => sanitize_text_field( $data['spend_date'] ?? gmdate( 'Y-m-d' ) ),
				'amount_sen'  => $amount,
				'notes'       => sanitize_textarea_field( $data['notes'] ?? '' ),
				'created_at'  => current_time( 'mysql' ),
			)
		);
		return (int) $wpdb->insert_id;
	}

	public static function delete_spend( $id ) {
		global $wpdb;
		$wpdb->delete( self::spend_table(), array( 'id' => (int) $id ), array( '%d' ) );
	}

	public static function spend_between( $from, $to ) {
		global $wpdb;
		$table = self::spend_table();
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(amount_sen),0) FROM {$table} WHERE spend_date >= %s AND spend_date < %s",
				substr( $from, 0, 10 ),
				substr( $to, 0, 10 )
			)
		);
	}

	public static function spend_rows( $limit = 50 ) {
		global $wpdb;
		$spend = self::spend_table();
		$camp  = self::campaigns_table();
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT s.*, c.name AS campaign_name
				 FROM {$spend} s
				 LEFT JOIN {$camp} c ON c.id = s.campaign_id
				 ORDER BY s.spend_date DESC, s.id DESC
				 LIMIT %d",
				$limit
			)
		);
	}

	public static function roi_rows( $from, $to ) {
		global $wpdb;
		$payments = RakanZakat_Payments::table();
		$spend    = self::spend_table();
		$camp     = self::campaigns_table();

		$campaigns = $wpdb->get_results( 'SELECT * FROM ' . $camp . ' ORDER BY name ASC' );
		$out       = array();

		foreach ( $campaigns as $campaign ) {
			$rev = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COALESCE(SUM(paid_amount_sen),0) FROM {$payments}
					 WHERE status = 'paid' AND paid_at >= %s AND paid_at < %s AND campaign_id = %d",
					$from,
					$to,
					$campaign->id
				)
			);
			$conv = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$payments}
					 WHERE status = 'paid' AND paid_at >= %s AND paid_at < %s AND campaign_id = %d",
					$from,
					$to,
					$campaign->id
				)
			);
			$cost = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COALESCE(SUM(amount_sen),0) FROM {$spend}
					 WHERE campaign_id = %d AND spend_date >= %s AND spend_date < %s",
					$campaign->id,
					substr( $from, 0, 10 ),
					substr( $to, 0, 10 )
				)
			);

			$out[] = self::metrics_row( $campaign->name, $campaign->platform, $rev, $cost, $conv );
		}

		$unattr_rev = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(paid_amount_sen),0) FROM {$payments}
				 WHERE status = 'paid' AND paid_at >= %s AND paid_at < %s AND (campaign_id IS NULL OR campaign_id = 0)",
				$from,
				$to
			)
		);
		$unattr_conv = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$payments}
				 WHERE status = 'paid' AND paid_at >= %s AND paid_at < %s AND (campaign_id IS NULL OR campaign_id = 0)",
				$from,
				$to
			)
		);
		$unattr_cost = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(amount_sen),0) FROM {$spend}
				 WHERE (campaign_id IS NULL OR campaign_id = 0) AND spend_date >= %s AND spend_date < %s",
				substr( $from, 0, 10 ),
				substr( $to, 0, 10 )
			)
		);

		if ( $unattr_rev || $unattr_cost || $unattr_conv ) {
			$out[] = self::metrics_row( 'Tidak diatribut (organik / direct)', 'direct', $unattr_rev, $unattr_cost, $unattr_conv );
		}

		return $out;
	}

	private static function metrics_row( $name, $platform, $rev, $cost, $conv ) {
		$roas = $cost > 0 ? round( $rev / $cost, 2 ) : null;
		$roi  = $cost > 0 ? round( ( ( $rev - $cost ) / $cost ) * 100, 1 ) : null;
		$cpa  = $conv > 0 && $cost > 0 ? (int) round( $cost / $conv ) : null;

		return array(
			'name'     => $name,
			'platform' => $platform,
			'revenue'  => $rev,
			'spend'    => $cost,
			'conversions' => $conv,
			'roas'     => $roas,
			'roi'      => $roi,
			'cpa'      => $cpa,
		);
	}
}
