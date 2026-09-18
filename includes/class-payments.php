<?php
/**
 * Payment records and checkout.
 *
 * @package RakanZakat
 */

defined( 'ABSPATH' ) || exit;

class RakanZakat_Payments {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'rz_payments';
	}

	public static function create_checkout( $input ) {
		$types    = RakanZakat_Settings::zakat_types();
		$id_types = RakanZakat_Settings::id_types();
		$states   = RakanZakat_Settings::states();
		$hauls    = RakanZakat_Settings::haul_years();
		$name     = sanitize_text_field( $input['name'] ?? '' );
		$email    = sanitize_email( $input['email'] ?? '' );
		$mobile   = RakanZakat_Billplz::normalize_mobile( $input['mobile'] ?? '' );
		$id_type  = sanitize_key( $input['id_type'] ?? '' );
		$id_number = strtoupper( preg_replace( '/[\s-]+/', '', sanitize_text_field( $input['id_number'] ?? '' ) ) );
		$address1 = sanitize_text_field( $input['address_1'] ?? '' );
		$address2 = sanitize_text_field( $input['address_2'] ?? '' );
		$city     = sanitize_text_field( $input['city'] ?? '' );
		$state    = sanitize_text_field( $input['state'] ?? '' );
		$postcode = sanitize_text_field( $input['postcode'] ?? '' );
		$type     = sanitize_key( $input['zakat_type'] ?? '' );
		$haul     = sanitize_text_field( $input['haul_year'] ?? '' );
		$niat     = ! empty( $input['niat'] );
		$amount   = RakanZakat_Settings::to_sen( $input['amount'] ?? 0 );
		$min      = RakanZakat_Settings::to_sen( RakanZakat_Settings::get( 'min_amount', 10 ) );

		if ( strlen( $name ) < 2 ) {
			return new WP_Error( 'rz_name', __( 'Sila masukkan nama penuh / nama syarikat.', 'rakanzakat' ), array( 'status' => 400 ) );
		}
		if ( ! $mobile ) {
			return new WP_Error( 'rz_mobile', __( 'Sila masukkan no. telefon.', 'rakanzakat' ), array( 'status' => 400 ) );
		}
		if ( ! isset( $id_types[ $id_type ] ) ) {
			return new WP_Error( 'rz_id_type', __( 'Sila pilih jenis pengenalan.', 'rakanzakat' ), array( 'status' => 400 ) );
		}
		if ( strlen( $id_number ) < 4 ) {
			return new WP_Error( 'rz_id_number', __( 'Sila masukkan no. pengenalan.', 'rakanzakat' ), array( 'status' => 400 ) );
		}
		if ( 'mykad' === $id_type && ! preg_match( '/^[0-9]{12}$/', $id_number ) ) {
			return new WP_Error( 'rz_id_number', __( 'No. MyKad mesti 12 digit tanpa sengkang.', 'rakanzakat' ), array( 'status' => 400 ) );
		}
		if ( strlen( $address1 ) < 3 ) {
			return new WP_Error( 'rz_address', __( 'Sila masukkan alamat baris 1.', 'rakanzakat' ), array( 'status' => 400 ) );
		}
		if ( strlen( $city ) < 2 ) {
			return new WP_Error( 'rz_city', __( 'Sila masukkan bandar.', 'rakanzakat' ), array( 'status' => 400 ) );
		}
		if ( ! isset( $states[ $state ] ) ) {
			return new WP_Error( 'rz_state', __( 'Sila pilih negeri.', 'rakanzakat' ), array( 'status' => 400 ) );
		}
		if ( ! preg_match( '/^[0-9]{5}$/', $postcode ) ) {
			return new WP_Error( 'rz_postcode', __( 'Sila masukkan poskod 5 digit.', 'rakanzakat' ), array( 'status' => 400 ) );
		}
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'rz_email', __( 'Sila masukkan emel yang sah.', 'rakanzakat' ), array( 'status' => 400 ) );
		}
		if ( ! isset( $types[ $type ] ) ) {
			return new WP_Error( 'rz_type', __( 'Sila pilih jenis zakat.', 'rakanzakat' ), array( 'status' => 400 ) );
		}
		if ( ! isset( $hauls[ $haul ] ) ) {
			return new WP_Error( 'rz_haul', __( 'Sila pilih haul/tahun.', 'rakanzakat' ), array( 'status' => 400 ) );
		}
		if ( ! $niat ) {
			return new WP_Error( 'rz_niat', __( 'Sila tick niat membayar zakat.', 'rakanzakat' ), array( 'status' => 400 ) );
		}
		if ( $amount < $min ) {
			return new WP_Error(
				'rz_amount',
				sprintf(
					/* translators: %s: minimum amount */
					__( 'Amaun minimum ialah %s.', 'rakanzakat' ),
					RakanZakat_Settings::format_money( $min )
				),
				array( 'status' => 400 )
			);
		}

		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		if ( self::rate_limited( $ip ) ) {
			return new WP_Error( 'rz_rate', __( 'Terlalu banyak cubaan. Sila cuba sebentar lagi.', 'rakanzakat' ), array( 'status' => 429 ) );
		}

		$utm         = self::sanitize_utm( $input );
		$campaign_id = RakanZakat_Campaigns::match_id( $utm['utm_campaign'], $utm['utm_source'] );
		$ref         = sanitize_title( $input['ref'] ?? '' );
		if ( ! $ref ) {
			$ref = RakanZakat_Affiliates::request_code();
		}
		$affiliate_id = RakanZakat_Affiliates::match_id( $ref, $utm['utm_source'], $utm['utm_campaign'] );
		$now        = current_time( 'mysql' );
		$temp_bill  = 'tmp_' . wp_generate_password( 16, false, false );

		global $wpdb;
		$wpdb->insert(
			self::table(),
			array(
				'bill_id'         => $temp_bill,
				'payer_name'      => $name,
				'payer_email'     => $email,
				'payer_mobile'    => $mobile,
				'zakat_type'      => $type,
				'id_type'         => $id_type,
				'id_number'       => $id_number,
				'address_1'       => $address1,
				'address_2'       => $address2,
				'city'            => $city,
				'state'           => $state,
				'postcode'        => $postcode,
				'haul_year'       => $haul,
				'payer_meta'      => wp_json_encode( array( 'niat' => true ) ),
				'amount_sen'      => $amount,
				'status'          => 'pending',
				'visitor_id'      => sanitize_text_field( $input['visitor_id'] ?? '' ),
				'session_id'      => sanitize_text_field( $input['session_id'] ?? '' ),
				'utm_source'      => $utm['utm_source'],
				'utm_medium'      => $utm['utm_medium'],
				'utm_campaign'    => $utm['utm_campaign'],
				'utm_content'     => $utm['utm_content'],
				'utm_term'        => $utm['utm_term'],
				'landing_page'    => esc_url_raw( $input['landing_page'] ?? '' ),
				'referrer'        => esc_url_raw( $input['referrer'] ?? '' ),
				'campaign_id'     => $campaign_id ? $campaign_id : null,
				'affiliate_id'    => $affiliate_id ? $affiliate_id : null,
				'created_at'      => $now,
				'updated_at'      => $now,
			)
		);

		$local_id = (int) $wpdb->insert_id;
		if ( ! $local_id ) {
			return new WP_Error( 'rz_db', __( 'Gagal menyimpan rekod pembayaran.', 'rakanzakat' ), array( 'status' => 500 ) );
		}

		$bill = RakanZakat_Billplz::create_bill(
			array(
				'name'        => $name,
				'email'       => $email,
				'mobile'      => $mobile,
				'amount_sen'  => $amount,
				'description' => substr( 'Zakat ' . $types[ $type ] . ' ' . $haul . ' - ' . $name . ' (' . $id_number . ')', 0, 200 ),
				'reference_1' => $types[ $type ],
				'reference_2' => $haul,
			)
		);

		if ( is_wp_error( $bill ) ) {
			$wpdb->update( self::table(), array( 'status' => 'failed', 'updated_at' => $now ), array( 'id' => $local_id ) );
			return $bill;
		}

		$wpdb->update(
			self::table(),
			array(
				'bill_id'       => sanitize_text_field( $bill['id'] ?? '' ),
				'collection_id' => sanitize_text_field( $bill['collection_id'] ?? '' ),
				'bill_url'      => esc_url_raw( $bill['url'] ?? '' ),
				'updated_at'    => current_time( 'mysql' ),
			),
			array( 'id' => $local_id )
		);

		return array(
			'payment_id' => $local_id,
			'bill_id'    => $bill['id'] ?? '',
			'url'        => $bill['url'] ?? '',
		);
	}

	public static function apply_callback( $params ) {
		$bill_id = sanitize_text_field( $params['id'] ?? '' );
		if ( ! $bill_id ) {
			return new WP_Error( 'rz_no_bill', 'Missing bill id' );
		}

		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE bill_id = %s', $bill_id ) );
		if ( ! $row ) {
			return new WP_Error( 'rz_unknown_bill', 'Unknown bill' );
		}

		$paid        = self::is_truthy( $params['paid'] ?? false );
		$paid_amount = isset( $params['paid_amount'] ) ? (int) $params['paid_amount'] : (int) $row->amount_sen;
		$paid_at     = ! empty( $params['paid_at'] ) ? self::parse_billplz_date( $params['paid_at'] ) : current_time( 'mysql' );
		$was_paid    = 'paid' === $row->status;

		$data = array(
			'status'          => $paid ? 'paid' : ( isset( $params['state'] ) ? sanitize_key( $params['state'] ) : 'pending' ),
			'paid_amount_sen' => $paid ? $paid_amount : 0,
			'paid_at'         => $paid ? $paid_at : null,
			'transaction_id'  => sanitize_text_field( $params['transaction_id'] ?? $params['transactionid'] ?? '' ),
			'raw_payload'     => wp_json_encode( $params ),
			'updated_at'      => current_time( 'mysql' ),
		);

		if ( ! empty( $params['state'] ) && ! $paid ) {
			$data['status'] = sanitize_key( $params['state'] );
		}

		$wpdb->update( self::table(), $data, array( 'id' => (int) $row->id ) );

		if ( $paid && ! $was_paid ) {
			RakanZakat_Tracker::record_conversion( (int) $row->amount_sen, $paid_at );
		}

		return true;
	}

	public static function get_by_bill( $bill_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE bill_id = %s', $bill_id ) );
	}

	public static function query_payments( $args = array() ) {
		global $wpdb;
		$table  = self::table();
		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$params[] = $args['status'];
		}
		if ( ! empty( $args['zakat_type'] ) ) {
			$where[]  = 'zakat_type = %s';
			$params[] = $args['zakat_type'];
		}
		if ( ! empty( $args['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[]  = '(payer_name LIKE %s OR payer_email LIKE %s OR bill_id LIKE %s OR id_number LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$where_sql = implode( ' AND ', $where );
		$page      = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per_page  = 20;
		$offset    = ( $page - 1 ) * $per_page;

		$sql_count = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		$sql_rows  = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d";

		if ( $params ) {
			$total = (int) $wpdb->get_var( $wpdb->prepare( $sql_count, $params ) );
			$rows  = $wpdb->get_results( $wpdb->prepare( $sql_rows, array_merge( $params, array( $per_page, $offset ) ) ) );
		} else {
			$total = (int) $wpdb->get_var( $sql_count );
			$rows  = $wpdb->get_results( $wpdb->prepare( $sql_rows, $per_page, $offset ) );
		}

		return array(
			'rows'     => $rows,
			'total'    => $total,
			'page'     => $page,
			'pages'    => max( 1, (int) ceil( $total / $per_page ) ),
		);
	}

	public static function summarize( $from, $to ) {
		global $wpdb;
		$table = self::table();
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COUNT(*) AS bills,
					SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) AS paid_count,
					SUM(CASE WHEN status = 'paid' THEN paid_amount_sen ELSE 0 END) AS paid_sen,
					SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count
				FROM {$table}
				WHERE created_at >= %s AND created_at < %s",
				$from,
				$to
			)
		);

		$avg = ( $row && $row->paid_count ) ? (int) round( $row->paid_sen / $row->paid_count ) : 0;

		return array(
			'bills'         => (int) ( $row->bills ?? 0 ),
			'paid_count'    => (int) ( $row->paid_count ?? 0 ),
			'paid_sen'      => (int) ( $row->paid_sen ?? 0 ),
			'pending_count' => (int) ( $row->pending_count ?? 0 ),
			'avg_sen'       => $avg,
		);
	}

	public static function by_type( $from, $to ) {
		global $wpdb;
		$table = self::table();
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT zakat_type, COUNT(*) AS qty, SUM(paid_amount_sen) AS paid_sen
				 FROM {$table}
				 WHERE status = 'paid' AND paid_at >= %s AND paid_at < %s
				 GROUP BY zakat_type
				 ORDER BY paid_sen DESC",
				$from,
				$to
			)
		);
	}

	public static function daily_paid( $from, $to ) {
		global $wpdb;
		$table = self::table();
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(paid_at) AS d, COUNT(*) AS qty, SUM(paid_amount_sen) AS paid_sen
				 FROM {$table}
				 WHERE status = 'paid' AND paid_at >= %s AND paid_at < %s
				 GROUP BY DATE(paid_at)
				 ORDER BY d ASC",
				$from,
				$to
			)
		);
	}

	public static function export_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Akses ditolak.', 'rakanzakat' ) );
		}

		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT * FROM ' . self::table() . ' ORDER BY created_at DESC', ARRAY_A );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=rakanzakat-kutipan.csv' );

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'id', 'bill_id', 'nama', 'emel', 'telefon', 'id_type', 'id_number', 'jenis', 'amaun', 'status', 'dibayar_pada', 'utm_source', 'utm_campaign' ) );
		foreach ( $rows as $row ) {
			fputcsv(
				$out,
				array(
					$row['id'],
					$row['bill_id'],
					$row['payer_name'],
					$row['payer_email'],
					$row['payer_mobile'],
					$row['id_type'] ?? '',
					$row['id_number'] ?? '',
					$row['zakat_type'],
					number_format( ( (int) $row['paid_amount_sen'] ?: (int) $row['amount_sen'] ) / 100, 2, '.', '' ),
					$row['status'],
					$row['paid_at'],
					$row['utm_source'],
					$row['utm_campaign'],
				)
			);
		}
		fclose( $out );
		exit;
	}

	private static function sanitize_utm( $input ) {
		$keys = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term' );
		$out  = array();
		foreach ( $keys as $key ) {
			$out[ $key ] = sanitize_text_field( $input[ $key ] ?? '' );
		}
		return $out;
	}

	private static function rate_limited( $ip ) {
		$key   = 'rz_pay_' . md5( $ip );
		$count = (int) get_transient( $key );
		if ( $count >= 10 ) {
			return true;
		}
		set_transient( $key, $count + 1, 10 * MINUTE_IN_SECONDS );
		return false;
	}

	private static function is_truthy( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}
		return in_array( strtolower( (string) $value ), array( '1', 'true', 'yes', 'paid' ), true );
	}

	private static function parse_billplz_date( $value ) {
		$ts = strtotime( (string) $value );
		if ( ! $ts ) {
			return current_time( 'mysql' );
		}
		return wp_date( 'Y-m-d H:i:s', $ts );
	}
}
