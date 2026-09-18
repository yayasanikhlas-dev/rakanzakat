<?php
/**
 * Plugin settings helper.
 *
 * @package RakanZakat
 */

defined( 'ABSPATH' ) || exit;

class RakanZakat_Settings {

	const OPTION = 'rakanzakat_settings';

	public static function get_all() {
		$defaults = array(
			'api_key'       => '',
			'x_signature'   => '',
			'collection_id' => '',
			'sandbox'       => 1,
			'min_amount'    => 10,
			'thankyou_page' => 0,
			'failed_page'   => 0,
			'track_admins'    => 0,
			'update_source'   => 'github',
			'github_repo'     => 'yayasanikhlas-dev/rakanzakat',
			'github_token'    => '',
			'update_json_url' => '',
			'auto_update'     => 1,
			'brand_name'      => 'Rakan Zakat',
			'brand_tagline'   => 'Saluran rasmi bayar zakat',
			'brand_badge'     => 'LZS (PA 2928)',
			'brand_footer'    => 'Rakan rasmi Lembaga Zakat Selangor',
			'support_email'   => '',
			'support_phone'   => '',
			'brand_logo_id'   => 0,
			'brand_logo_dark_id' => 0,
			'sidebar_color'   => '#0A2540',
			'primary_color'   => '#0B5EDA',
			'accent_color'    => '#FEE506',
			'bg_color'        => '#FAF8FF',
			'show_partner_badge' => 1,
			'default_commission_pct' => 10,
			'cookie_days'     => 30,
			'creative_text'   => 'Tunaikan zakat melalui saluran rasmi Rakan Zakat.',
			'notify_receipt'  => 0,
			'notify_admin'    => 0,
			'notify_emails'   => '',
			'amount_presets'  => '50,100,250,500,1000',
		);
		$saved = get_option( self::OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return array_merge( $defaults, $saved );
	}

	public static function get( $key, $default = '' ) {
		$all = self::get_all();
		return isset( $all[ $key ] ) ? $all[ $key ] : $default;
	}

	public static function update( $data ) {
		$current = self::get_all();
		update_option( self::OPTION, array_merge( $current, $data ) );
	}

	public static function id_types() {
		return array(
			'mykad'    => __( 'MyKad / NRIC', 'rakanzakat' ),
			'passport' => __( 'Passport', 'rakanzakat' ),
			'ssm'      => __( 'SSM (Syarikat)', 'rakanzakat' ),
			'polis'    => __( 'No. Polis', 'rakanzakat' ),
			'tentera'  => __( 'No. Tentera', 'rakanzakat' ),
			'lain'     => __( 'Lain-lain', 'rakanzakat' ),
		);
	}

	public static function states() {
		return array(
			'Johor'           => 'Johor',
			'Kedah'           => 'Kedah',
			'Kelantan'        => 'Kelantan',
			'Melaka'          => 'Melaka',
			'Negeri Sembilan' => 'Negeri Sembilan',
			'Pahang'          => 'Pahang',
			'Perak'           => 'Perak',
			'Perlis'          => 'Perlis',
			'Pulau Pinang'    => 'Pulau Pinang',
			'Sabah'           => 'Sabah',
			'Sarawak'         => 'Sarawak',
			'Selangor'        => 'Selangor',
			'Terengganu'      => 'Terengganu',
			'WP Kuala Lumpur' => 'WP Kuala Lumpur',
			'WP Labuan'       => 'WP Labuan',
			'WP Putrajaya'    => 'WP Putrajaya',
		);
	}

	public static function haul_years() {
		$current = (int) wp_date( 'Y' );
		$years   = array();
		for ( $y = $current; $y >= $current - 5; $y-- ) {
			$years[ (string) $y ] = (string) $y;
		}
		return $years;
	}

	public static function zakat_types() {
		return array(
			'pendapatan'  => __( 'Zakat Pendapatan', 'rakanzakat' ),
			'fitrah'      => __( 'Zakat Fitrah', 'rakanzakat' ),
			'perniagaan'  => __( 'Zakat Perniagaan', 'rakanzakat' ),
			'simpanan'    => __( 'Zakat Simpanan', 'rakanzakat' ),
			'emas'        => __( 'Zakat Emas', 'rakanzakat' ),
			'saham'       => __( 'Zakat Saham', 'rakanzakat' ),
			'kwsp'        => __( 'Zakat KWSP', 'rakanzakat' ),
			'qada'        => __( 'Qada Zakat', 'rakanzakat' ),
			'pertanian'   => __( 'Zakat Pertanian', 'rakanzakat' ),
			'lain-lain'   => __( 'Lain-lain / Sumbangan', 'rakanzakat' ),
		);
	}

	public static function platforms() {
		return array(
			'meta'    => 'Meta (Facebook / Instagram)',
			'google'  => 'Google Ads',
			'tiktok'  => 'TikTok Ads',
			'youtube' => 'YouTube',
			'organic' => 'Organik / SEO',
			'direct'  => 'Direct',
			'other'   => 'Lain-lain',
		);
	}

	public static function format_money( $sen ) {
		$sen = (int) $sen;
		return 'RM ' . number_format( $sen / 100, 2, '.', ',' );
	}

	public static function to_sen( $amount ) {
		$amount = str_replace( ',', '', (string) $amount );
		return (int) round( (float) $amount * 100 );
	}
}
