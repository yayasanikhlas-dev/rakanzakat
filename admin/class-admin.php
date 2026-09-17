<?php
/**
 * WordPress admin dashboard.
 *
 * @package RakanZakat
 */

defined( 'ABSPATH' ) || exit;

class RakanZakat_Admin {

	public static function menu() {
		$cap = 'manage_options';
		add_menu_page(
			'Rakan Zakat',
			'Rakan Zakat',
			$cap,
			'rakanzakat',
			array( __CLASS__, 'page_dashboard' ),
			'dashicons-chart-area',
			26
		);
		add_submenu_page( 'rakanzakat', 'Dashboard', 'Dashboard', $cap, 'rakanzakat', array( __CLASS__, 'page_dashboard' ) );
		add_submenu_page( 'rakanzakat', 'Kutipan', 'Kutipan', $cap, 'rakanzakat-kutipan', array( __CLASS__, 'page_payments' ) );
		add_submenu_page( 'rakanzakat', 'Pelawat', 'Pelawat', $cap, 'rakanzakat-pelawat', array( __CLASS__, 'page_visitors' ) );
		add_submenu_page( 'rakanzakat', 'Iklan & ROI', 'Iklan & ROI', $cap, 'rakanzakat-roi', array( __CLASS__, 'page_roi' ) );
		add_submenu_page( 'rakanzakat', 'Tetapan', 'Tetapan', $cap, 'rakanzakat-settings', array( __CLASS__, 'page_settings' ) );
	}

	public static function assets( $hook ) {
		if ( strpos( (string) $hook, 'rakanzakat' ) === false ) {
			return;
		}
		wp_enqueue_style( 'rakanzakat-admin', RAKANZAKAT_URL . 'admin/css/admin.css', array(), RAKANZAKAT_VERSION );
		wp_enqueue_script( 'rakanzakat-admin', RAKANZAKAT_URL . 'admin/js/admin.js', array(), RAKANZAKAT_VERSION, true );
	}

	public static function handle_actions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_POST['rakanzakat_save_settings'] ) ) {
			check_admin_referer( 'rakanzakat_settings' );
			RakanZakat_Settings::update(
				array(
					'api_key'         => sanitize_text_field( wp_unslash( $_POST['api_key'] ?? '' ) ),
					'x_signature'     => sanitize_text_field( wp_unslash( $_POST['x_signature'] ?? '' ) ),
					'collection_id'   => sanitize_text_field( wp_unslash( $_POST['collection_id'] ?? '' ) ),
					'sandbox'         => empty( $_POST['sandbox'] ) ? 0 : 1,
					'min_amount'      => (float) ( $_POST['min_amount'] ?? 10 ),
					'thankyou_page'   => (int) ( $_POST['thankyou_page'] ?? 0 ),
					'failed_page'     => (int) ( $_POST['failed_page'] ?? 0 ),
					'track_admins'    => empty( $_POST['track_admins'] ) ? 0 : 1,
					'update_source'   => in_array( sanitize_key( wp_unslash( $_POST['update_source'] ?? 'github' ) ), array( 'github', 'json' ), true ) ? sanitize_key( wp_unslash( $_POST['update_source'] ?? 'github' ) ) : 'github',
					'github_repo'     => sanitize_text_field( wp_unslash( $_POST['github_repo'] ?? '' ) ),
					'update_json_url' => esc_url_raw( wp_unslash( $_POST['update_json_url'] ?? '' ) ),
					'auto_update'     => empty( $_POST['auto_update'] ) ? 0 : 1,
				)
			);

			$token = sanitize_text_field( wp_unslash( $_POST['github_token'] ?? '' ) );
			if ( '' !== $token ) {
				RakanZakat_Settings::update( array( 'github_token' => $token ) );
			}

			RakanZakat_Updater::clear_cache();
			add_settings_error( 'rakanzakat', 'saved', __( 'Tetapan disimpan.', 'rakanzakat' ), 'updated' );
		}

		if ( isset( $_POST['rakanzakat_test_billplz'] ) ) {
			check_admin_referer( 'rakanzakat_settings' );
			$result = RakanZakat_Billplz::get_collection();
			if ( is_wp_error( $result ) ) {
				add_settings_error( 'rakanzakat', 'billplz', $result->get_error_message(), 'error' );
			} else {
				$title = isset( $result['title'] ) ? $result['title'] : ( $result['id'] ?? '' );
				add_settings_error( 'rakanzakat', 'billplz', 'Sambungan Billplz berjaya. Collection: ' . $title, 'updated' );
			}
		}

		if ( isset( $_POST['rakanzakat_check_update'] ) ) {
			check_admin_referer( 'rakanzakat_settings' );
			RakanZakat_Updater::clear_cache();
			wp_clean_plugins_cache();
			$remote = RakanZakat_Updater::get_remote( true );
			if ( is_wp_error( $remote ) ) {
				add_settings_error( 'rakanzakat', 'update', $remote->get_error_message(), 'error' );
			} elseif ( version_compare( RAKANZAKAT_VERSION, $remote['version'], '<' ) ) {
				add_settings_error(
					'rakanzakat',
					'update',
					sprintf(
						/* translators: %s: new version */
						__( 'Update tersedia: v%s. Pergi ke Plugins untuk pasang, atau tunggu auto-update.', 'rakanzakat' ),
						$remote['version']
					),
					'updated'
				);
			} else {
				add_settings_error( 'rakanzakat', 'update', __( 'Plugin sudah versi terbaru.', 'rakanzakat' ), 'updated' );
			}
		}

		if ( isset( $_POST['rakanzakat_add_campaign'] ) ) {
			check_admin_referer( 'rakanzakat_roi' );
			$name = sanitize_text_field( wp_unslash( $_POST['campaign_name'] ?? '' ) );
			if ( $name ) {
				RakanZakat_Campaigns::create_campaign(
					array(
						'name'         => $name,
						'platform'     => sanitize_key( wp_unslash( $_POST['campaign_platform'] ?? 'other' ) ),
						'utm_source'   => sanitize_text_field( wp_unslash( $_POST['utm_source'] ?? '' ) ),
						'utm_medium'   => sanitize_text_field( wp_unslash( $_POST['utm_medium'] ?? '' ) ),
						'utm_campaign' => sanitize_text_field( wp_unslash( $_POST['utm_campaign'] ?? '' ) ),
						'notes'        => sanitize_textarea_field( wp_unslash( $_POST['campaign_notes'] ?? '' ) ),
					)
				);
				add_settings_error( 'rakanzakat', 'campaign', __( 'Kempen ditambah.', 'rakanzakat' ), 'updated' );
			}
		}

		if ( isset( $_POST['rakanzakat_add_spend'] ) ) {
			check_admin_referer( 'rakanzakat_roi' );
			$result = RakanZakat_Campaigns::add_spend(
				array(
					'campaign_id' => (int) ( $_POST['spend_campaign_id'] ?? 0 ),
					'platform'    => sanitize_key( wp_unslash( $_POST['spend_platform'] ?? 'other' ) ),
					'spend_date'  => sanitize_text_field( wp_unslash( $_POST['spend_date'] ?? '' ) ),
					'amount'      => wp_unslash( $_POST['spend_amount'] ?? 0 ),
					'notes'       => sanitize_textarea_field( wp_unslash( $_POST['spend_notes'] ?? '' ) ),
				)
			);
			if ( is_wp_error( $result ) ) {
				add_settings_error( 'rakanzakat', 'spend', $result->get_error_message(), 'error' );
			} else {
				add_settings_error( 'rakanzakat', 'spend', __( 'Ads spend direkodkan.', 'rakanzakat' ), 'updated' );
			}
		}

		if ( isset( $_GET['rz_delete_spend'] ) && isset( $_GET['_wpnonce'] ) ) {
			if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'rz_delete_spend' ) ) {
				RakanZakat_Campaigns::delete_spend( (int) $_GET['rz_delete_spend'] );
				add_settings_error( 'rakanzakat', 'spend', __( 'Rekod spend dipadam.', 'rakanzakat' ), 'updated' );
			}
		}
	}

	public static function page_dashboard() {
		$range    = self::date_range();
		$from     = $range['from'];
		$to       = $range['to'];
		$pay      = RakanZakat_Payments::summarize( $from, $to );
		$visits   = RakanZakat_Tracker::summarize( $from, $to );
		$spend    = RakanZakat_Campaigns::spend_between( $from, $to );
		$conv     = $visits['visitors'] > 0 ? round( ( $pay['paid_count'] / $visits['visitors'] ) * 100, 2 ) : 0;
		$roas     = $spend > 0 ? round( $pay['paid_sen'] / $spend, 2 ) : null;
		$roi      = $spend > 0 ? round( ( ( $pay['paid_sen'] - $spend ) / $spend ) * 100, 1 ) : null;
		$cpa      = $pay['paid_count'] > 0 && $spend > 0 ? (int) round( $spend / $pay['paid_count'] ) : null;
		$types    = RakanZakat_Payments::by_type( $from, $to );
		$labels   = RakanZakat_Settings::zakat_types();
		$daily_p  = RakanZakat_Payments::daily_paid( $from, $to );
		$daily_v  = RakanZakat_Tracker::daily_visits( $from, $to );
		$configured = RakanZakat_Settings::get( 'api_key' ) && RakanZakat_Settings::get( 'collection_id' );

		self::header( 'Dashboard kutipan' );
		settings_errors( 'rakanzakat' );
		self::range_nav( $range['preset'] );

		if ( ! $configured ) {
			echo '<div class="rz-banner">Billplz belum disambung. Buka <a href="' . esc_url( admin_url( 'admin.php?page=rakanzakat-settings' ) ) . '">Tetapan</a> untuk masukkan API Key, Collection ID dan X-Signature.</div>';
		}
		?>
		<div class="rz-kpis">
			<?php
			self::kpi( 'Kutipan berjaya', RakanZakat_Settings::format_money( $pay['paid_sen'] ), $pay['paid_count'] . ' transaksi' );
			self::kpi( 'Purata zakat', RakanZakat_Settings::format_money( $pay['avg_sen'] ), $pay['pending_count'] . ' belum bayar' );
			self::kpi( 'Pelawat unik', number_format_i18n( $visits['visitors'] ), number_format_i18n( $visits['pageviews'] ) . ' paparan' );
			self::kpi( 'Conversion', $conv . '%', number_format_i18n( $visits['sessions'] ) . ' sesi' );
			self::kpi( 'Ads spend', RakanZakat_Settings::format_money( $spend ), $cpa ? 'CPA ' . RakanZakat_Settings::format_money( $cpa ) : 'Tiada konversi berbayar' );
			self::kpi( 'ROAS / ROI', null !== $roas ? $roas . 'x' : '—', null !== $roi ? $roi . '% ROI' : 'Masukkan spend untuk kira ROI' );
			?>
		</div>
		<div class="rz-grid">
			<section class="rz-card">
				<h2>Kutipan harian</h2>
				<canvas id="rz-chart-kutipan" height="180" data-points="<?php echo esc_attr( wp_json_encode( self::chart_series( $daily_p, 'd', 'paid_sen' ) ) ); ?>"></canvas>
			</section>
			<section class="rz-card">
				<h2>Pelawat harian</h2>
				<canvas id="rz-chart-pelawat" height="180" data-points="<?php echo esc_attr( wp_json_encode( self::chart_series( $daily_v, 'd', 'visitors' ) ) ); ?>"></canvas>
			</section>
		</div>
		<section class="rz-card">
			<h2>Kutipan mengikut jenis zakat</h2>
			<table class="rz-table">
				<thead><tr><th>Jenis</th><th>Transaksi</th><th>Jumlah</th></tr></thead>
				<tbody>
				<?php if ( ! $types ) : ?>
					<tr><td colspan="3">Belum ada kutipan berjaya dalam tempoh ini.</td></tr>
				<?php else : ?>
					<?php foreach ( $types as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $labels[ $row->zakat_type ] ?? $row->zakat_type ); ?></td>
							<td><?php echo esc_html( number_format_i18n( $row->qty ) ); ?></td>
							<td><?php echo esc_html( RakanZakat_Settings::format_money( $row->paid_sen ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</section>
		<?php
		self::footer();
	}

	public static function page_payments() {
		$q = RakanZakat_Payments::query_payments(
			array(
				'status'     => sanitize_key( wp_unslash( $_GET['status'] ?? '' ) ),
				'zakat_type' => sanitize_key( wp_unslash( $_GET['zakat_type'] ?? '' ) ),
				'search'     => sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ),
				'page'       => (int) ( $_GET['paged'] ?? 1 ),
			)
		);
		$types  = RakanZakat_Settings::zakat_types();
		$export = admin_url( 'admin-post.php?action=rakanzakat_export' );

		self::header( 'Kutipan zakat' );
		settings_errors( 'rakanzakat' );
		?>
		<form class="rz-filters" method="get">
			<input type="hidden" name="page" value="rakanzakat-kutipan">
			<input type="search" name="s" value="<?php echo esc_attr( wp_unslash( $_GET['s'] ?? '' ) ); ?>" placeholder="Cari nama, emel, bill ID">
			<select name="status">
				<option value="">Semua status</option>
				<?php foreach ( array( 'paid' => 'Berjaya', 'pending' => 'Pending', 'failed' => 'Gagal' ) as $k => $label ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>" <?php selected( sanitize_key( wp_unslash( $_GET['status'] ?? '' ) ), $k ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<select name="zakat_type">
				<option value="">Semua jenis</option>
				<?php foreach ( $types as $k => $label ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>" <?php selected( sanitize_key( wp_unslash( $_GET['zakat_type'] ?? '' ) ), $k ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<button class="button">Tapis</button>
			<a class="button" href="<?php echo esc_url( $export ); ?>">Export CSV</a>
		</form>
		<section class="rz-card">
			<table class="rz-table">
				<thead>
					<tr>
						<th>Masa</th>
						<th>Pembayar</th>
						<th>Jenis</th>
						<th>Amaun</th>
						<th>Status</th>
						<th>Sumber</th>
					</tr>
				</thead>
				<tbody>
				<?php if ( ! $q['rows'] ) : ?>
					<tr><td colspan="6">Tiada rekod kutipan.</td></tr>
				<?php endif; ?>
				<?php foreach ( $q['rows'] as $row ) : ?>
					<tr>
						<td><?php echo esc_html( $row->created_at ); ?><div class="rz-muted"><?php echo esc_html( $row->bill_id ); ?></div></td>
						<td>
							<strong><?php echo esc_html( $row->payer_name ); ?></strong>
							<div class="rz-muted"><?php echo esc_html( $row->payer_email ); ?></div>
						</td>
						<td><?php echo esc_html( $types[ $row->zakat_type ] ?? $row->zakat_type ); ?></td>
						<td><?php echo esc_html( RakanZakat_Settings::format_money( 'paid' === $row->status ? $row->paid_amount_sen : $row->amount_sen ) ); ?></td>
						<td><span class="rz-pill rz-pill--<?php echo esc_attr( $row->status ); ?>"><?php echo esc_html( $row->status ); ?></span></td>
						<td><?php echo esc_html( $row->utm_source ?: 'direct' ); ?><?php echo $row->utm_campaign ? '<div class="rz-muted">' . esc_html( $row->utm_campaign ) . '</div>' : ''; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ( $q['pages'] > 1 ) : ?>
				<p class="rz-pager">
					<?php for ( $i = 1; $i <= $q['pages']; $i++ ) : ?>
						<a class="<?php echo $i === $q['page'] ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'paged', $i ) ); ?>"><?php echo esc_html( (string) $i ); ?></a>
					<?php endfor; ?>
				</p>
			<?php endif; ?>
		</section>
		<?php
		self::footer();
	}

	public static function page_visitors() {
		$range   = self::date_range();
		$summary = RakanZakat_Tracker::summarize( $range['from'], $range['to'] );
		$sources = RakanZakat_Tracker::sources( $range['from'], $range['to'] );
		$recent  = RakanZakat_Tracker::recent();

		self::header( 'Pelawat website' );
		self::range_nav( $range['preset'] );
		?>
		<div class="rz-kpis">
			<?php
			self::kpi( 'Unik', number_format_i18n( $summary['visitors'] ) );
			self::kpi( 'Sesi', number_format_i18n( $summary['sessions'] ) );
			self::kpi( 'Paparan halaman', number_format_i18n( $summary['pageviews'] ) );
			?>
		</div>
		<div class="rz-grid">
			<section class="rz-card">
				<h2>Sumber trafik</h2>
				<table class="rz-table">
					<thead><tr><th>Sumber</th><th>Pelawat</th><th>Paparan</th></tr></thead>
					<tbody>
					<?php if ( ! $sources ) : ?>
						<tr><td colspan="3">Belum ada data pelawat. Tracker aktif pada halaman awam selepas plugin diaktifkan.</td></tr>
					<?php endif; ?>
					<?php foreach ( $sources as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row->source ); ?></td>
							<td><?php echo esc_html( number_format_i18n( $row->visitors ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( $row->pageviews ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</section>
			<section class="rz-card">
				<h2>Lawatan terbaru</h2>
				<table class="rz-table">
					<thead><tr><th>Masa</th><th>Halaman</th><th>UTM</th></tr></thead>
					<tbody>
					<?php foreach ( $recent as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row->created_at ); ?></td>
							<td><?php echo esc_html( wp_parse_url( $row->page_url, PHP_URL_PATH ) ?: $row->page_url ); ?></td>
							<td><?php echo esc_html( trim( $row->utm_source . ' / ' . $row->utm_campaign, ' /' ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</section>
		</div>
		<?php
		self::footer();
	}

	public static function page_roi() {
		$range     = self::date_range();
		$pay       = RakanZakat_Payments::summarize( $range['from'], $range['to'] );
		$spend     = RakanZakat_Campaigns::spend_between( $range['from'], $range['to'] );
		$rows      = RakanZakat_Campaigns::roi_rows( $range['from'], $range['to'] );
		$campaigns = RakanZakat_Campaigns::all_campaigns();
		$spends    = RakanZakat_Campaigns::spend_rows();
		$platforms = RakanZakat_Settings::platforms();
		$roas      = $spend > 0 ? round( $pay['paid_sen'] / $spend, 2 ) : null;
		$roi       = $spend > 0 ? round( ( ( $pay['paid_sen'] - $spend ) / $spend ) * 100, 1 ) : null;

		self::header( 'Iklan, spend & ROI' );
		settings_errors( 'rakanzakat' );
		self::range_nav( $range['preset'] );
		?>
		<div class="rz-kpis">
			<?php
			self::kpi( 'Kutipan', RakanZakat_Settings::format_money( $pay['paid_sen'] ) );
			self::kpi( 'Ads spend', RakanZakat_Settings::format_money( $spend ) );
			self::kpi( 'ROAS', null !== $roas ? $roas . 'x' : '—' );
			self::kpi( 'ROI', null !== $roi ? $roi . '%' : '—' );
			?>
		</div>

		<div class="rz-grid">
			<section class="rz-card">
				<h2>Tambah kempen</h2>
				<p class="rz-help">Padankan <code>utm_campaign</code> iklan dengan kempen ni supaya kutipan auto-atribut.</p>
				<form method="post" class="rz-form-admin">
					<?php wp_nonce_field( 'rakanzakat_roi' ); ?>
					<input type="text" name="campaign_name" placeholder="Nama kempen" required>
					<select name="campaign_platform">
						<?php foreach ( $platforms as $k => $label ) : ?>
							<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<input type="text" name="utm_source" placeholder="utm_source (fb, google)">
					<input type="text" name="utm_medium" placeholder="utm_medium (cpc, paid)">
					<input type="text" name="utm_campaign" placeholder="utm_campaign" required>
					<button class="button button-primary" name="rakanzakat_add_campaign" value="1">Simpan kempen</button>
				</form>
				<?php if ( $campaigns ) : ?>
					<table class="rz-table">
						<thead><tr><th>Kempen</th><th>Platform</th><th>UTM</th></tr></thead>
						<tbody>
						<?php foreach ( $campaigns as $c ) : ?>
							<tr>
								<td><?php echo esc_html( $c->name ); ?></td>
								<td><?php echo esc_html( $platforms[ $c->platform ] ?? $c->platform ); ?></td>
								<td><code><?php echo esc_html( $c->utm_campaign ); ?></code></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</section>

			<section class="rz-card">
				<h2>Rekod ads spend</h2>
				<form method="post" class="rz-form-admin">
					<?php wp_nonce_field( 'rakanzakat_roi' ); ?>
					<select name="spend_campaign_id">
						<option value="0">Tanpa kempen / umum</option>
						<?php foreach ( $campaigns as $c ) : ?>
							<option value="<?php echo esc_attr( $c->id ); ?>"><?php echo esc_html( $c->name ); ?></option>
						<?php endforeach; ?>
					</select>
					<select name="spend_platform">
						<?php foreach ( $platforms as $k => $label ) : ?>
							<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<input type="date" name="spend_date" value="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>" required>
					<input type="number" step="0.01" min="0.01" name="spend_amount" placeholder="Amaun RM" required>
					<input type="text" name="spend_notes" placeholder="Nota (pilihan)">
					<button class="button button-primary" name="rakanzakat_add_spend" value="1">Simpan spend</button>
				</form>
				<table class="rz-table">
					<thead><tr><th>Tarikh</th><th>Kempen</th><th>Amaun</th><th></th></tr></thead>
					<tbody>
					<?php if ( ! $spends ) : ?>
						<tr><td colspan="4">Belum ada spend direkodkan.</td></tr>
					<?php endif; ?>
					<?php foreach ( $spends as $s ) : ?>
						<tr>
							<td><?php echo esc_html( $s->spend_date ); ?></td>
							<td><?php echo esc_html( $s->campaign_name ?: $s->platform ); ?></td>
							<td><?php echo esc_html( RakanZakat_Settings::format_money( $s->amount_sen ) ); ?></td>
							<td>
								<a class="rz-danger" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'rz_delete_spend', $s->id ), 'rz_delete_spend' ) ); ?>">Padam</a>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</section>
		</div>

		<section class="rz-card">
			<h2>Prestasi kempen</h2>
			<table class="rz-table">
				<thead>
					<tr>
						<th>Kempen</th>
						<th>Spend</th>
						<th>Kutipan</th>
						<th>Konversi</th>
						<th>CPA</th>
						<th>ROAS</th>
						<th>ROI</th>
					</tr>
				</thead>
				<tbody>
				<?php if ( ! $rows ) : ?>
					<tr><td colspan="7">Tambah kempen dan spend untuk nampak ROI.</td></tr>
				<?php endif; ?>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td><?php echo esc_html( $row['name'] ); ?></td>
						<td><?php echo esc_html( RakanZakat_Settings::format_money( $row['spend'] ) ); ?></td>
						<td><?php echo esc_html( RakanZakat_Settings::format_money( $row['revenue'] ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $row['conversions'] ) ); ?></td>
						<td><?php echo $row['cpa'] ? esc_html( RakanZakat_Settings::format_money( $row['cpa'] ) ) : '—'; ?></td>
						<td><?php echo null !== $row['roas'] ? esc_html( $row['roas'] . 'x' ) : '—'; ?></td>
						<td><?php echo null !== $row['roi'] ? esc_html( $row['roi'] . '%' ) : '—'; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</section>
		<?php
		self::footer();
	}

	public static function page_settings() {
		$s     = RakanZakat_Settings::get_all();
		$pages = get_option( 'rakanzakat_pages', array() );
		self::header( 'Tetapan Billplz & tracking' );
		settings_errors( 'rakanzakat' );
		?>
		<form method="post" class="rz-card rz-settings">
			<?php wp_nonce_field( 'rakanzakat_settings' ); ?>
			<h2>Billplz</h2>
			<p class="rz-help">Ambil kunci dari Billplz Dashboard → Settings. Tick <strong>Enable X Signature Payment Completion</strong>.</p>
			<label>API Secret Key
				<input type="password" name="api_key" value="<?php echo esc_attr( $s['api_key'] ); ?>" autocomplete="off">
			</label>
			<label>X-Signature Key
				<input type="password" name="x_signature" value="<?php echo esc_attr( $s['x_signature'] ); ?>" autocomplete="off">
			</label>
			<label>Collection ID
				<input type="text" name="collection_id" value="<?php echo esc_attr( $s['collection_id'] ); ?>">
			</label>
			<label class="rz-check">
				<input type="checkbox" name="sandbox" value="1" <?php checked( (int) $s['sandbox'], 1 ); ?>>
				Guna Billplz sandbox
			</label>
			<p>
				<button class="button" name="rakanzakat_test_billplz" value="1">Uji sambungan</button>
			</p>

			<h2>Pembayaran</h2>
			<label>Amaun minimum (RM)
				<input type="number" step="0.01" min="1" name="min_amount" value="<?php echo esc_attr( $s['min_amount'] ); ?>">
			</label>
			<label>Halaman terima kasih
				<?php
				wp_dropdown_pages(
					array(
						'name'              => 'thankyou_page',
						'selected'          => (int) $s['thankyou_page'],
						'show_option_none'  => '— Pilih —',
						'option_none_value' => 0,
					)
				);
				?>
			</label>
			<label>Halaman gagal (pilihan)
				<?php
				wp_dropdown_pages(
					array(
						'name'              => 'failed_page',
						'selected'          => (int) $s['failed_page'],
						'show_option_none'  => '— Guna halaman terima kasih —',
						'option_none_value' => 0,
					)
				);
				?>
			</label>

			<h2>Tracking</h2>
			<label class="rz-check">
				<input type="checkbox" name="track_admins" value="1" <?php checked( (int) $s['track_admins'], 1 ); ?>>
				Track pelawat yang login sebagai admin
			</label>

			<h2>Auto-update plugin</h2>
			<p class="rz-help">WordPress akan semak versi baru dari GitHub/JSON (HTTPS). Ini cara yang sama plugin rasmi update — tak perlu upload zip setiap kali. Token GitHub simpan dalam tetapan atau <code>wp-config.php</code>.</p>
			<p class="rz-help">Versi sekarang: <strong><?php echo esc_html( RAKANZAKAT_VERSION ); ?></strong></p>
			<label>Sumber update
				<select name="update_source">
					<option value="github" <?php selected( $s['update_source'], 'github' ); ?>>GitHub Releases</option>
					<option value="json" <?php selected( $s['update_source'], 'json' ); ?>>Fail JSON HTTPS sendiri</option>
				</select>
			</label>
			<label>GitHub repo <em>(owner/nama-repo)</em>
				<input type="text" name="github_repo" value="<?php echo esc_attr( $s['github_repo'] ); ?>" placeholder="rakanzakat/rakanzakat-plugin" autocomplete="off">
			</label>
			<label>GitHub token <em>(perlu jika repo private; kosongkan untuk kekalkan token sedia ada)</em>
				<input type="password" name="github_token" value="" autocomplete="new-password" placeholder="<?php echo $s['github_token'] ? 'Token sudah disimpan' : 'ghp_…'; ?>">
			</label>
			<label>URL JSON update
				<input type="url" name="update_json_url" value="<?php echo esc_attr( $s['update_json_url'] ); ?>" placeholder="https://example.com/rakanzakat-update.json">
			</label>
			<label class="rz-check">
				<input type="checkbox" name="auto_update" value="1" <?php checked( (int) $s['auto_update'], 1 ); ?>>
				Pasang update secara automatik (background)
			</label>
			<p>
				<button class="button" name="rakanzakat_check_update" value="1">Semak update sekarang</button>
			</p>

			<p><button class="button button-primary" name="rakanzakat_save_settings" value="1">Simpan tetapan</button></p>
		</form>

		<section class="rz-card">
			<h2>Shortcode & webhook</h2>
			<ul class="rz-list">
				<li>Landing page: template <strong>Rakan Zakat — Landing</strong><?php echo ! empty( $pages['landing'] ) ? ' — <a href="' . esc_url( get_permalink( $pages['landing'] ) ) . '">buka ' . esc_html( (string) get_permalink( $pages['landing'] ) ) . '</a>. Set sebagai homepage di Settings → Reading kalau nak jadi muka depan.' : ''; ?></li>
				<li>Borang bayar: <code>[rakanzakat_form]</code><?php echo ! empty( $pages['pay'] ) ? ' — <a href="' . esc_url( get_permalink( $pages['pay'] ) ) . '">buka halaman</a>' : ''; ?></li>
				<li>Elementor (kategori <strong>Rakan Zakat</strong>): Borang Zakat, Panduan &amp; Taksiran, Kategori Zakat, Tiga Langkah, Saluran Rasmi, Impak Komuniti, FAQ, CTA Bayar</li>
				<li>Widget WP: Appearance → Widgets → <strong>Rakan Zakat — Borang Bayar</strong></li>
				<li>Block editor: insert block <strong>Borang Zakat</strong></li>
				<li>Resit: <code>[rakanzakat_receipt]</code></li>
				<li>Callback URL (auto): <code><?php echo esc_html( rest_url( 'rakanzakat/v1/billplz/callback' ) ); ?></code></li>
				<li>Contoh URL iklan: <code><?php echo esc_html( home_url( '/bayar-zakat/?utm_source=fb&utm_medium=cpc&utm_campaign=ramadan' ) ); ?></code></li>
			</ul>
		</section>
		<?php
		self::footer();
	}

	private static function header( $title ) {
		echo '<div class="wrap rz-wrap"><h1>' . esc_html( $title ) . '</h1>';
	}

	private static function footer() {
		echo '</div>';
	}

	private static function kpi( $label, $value, $sub = '' ) {
		echo '<article class="rz-kpi"><p>' . esc_html( $label ) . '</p><strong>' . esc_html( $value ) . '</strong>';
		if ( $sub ) {
			echo '<span>' . esc_html( $sub ) . '</span>';
		}
		echo '</article>';
	}

	private static function range_nav( $preset ) {
		$items = array(
			'today' => 'Hari ini',
			'7d'    => '7 hari',
			'30d'   => '30 hari',
			'month' => 'Bulan ini',
		);
		echo '<nav class="rz-range">';
		foreach ( $items as $key => $label ) {
			$url = add_query_arg( 'range', $key );
			echo '<a class="' . ( $preset === $key ? 'is-active' : '' ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
		}
		echo '</nav>';
	}

	public static function date_range() {
		$preset = sanitize_key( wp_unslash( $_GET['range'] ?? '30d' ) );
		$today  = wp_date( 'Y-m-d' );
		$to     = wp_date( 'Y-m-d', strtotime( '+1 day', strtotime( $today ) ) ) . ' 00:00:00';

		switch ( $preset ) {
			case 'today':
				$from = $today . ' 00:00:00';
				break;
			case '7d':
				$from = wp_date( 'Y-m-d', strtotime( '-6 days', strtotime( $today ) ) ) . ' 00:00:00';
				break;
			case 'month':
				$from = wp_date( 'Y-m-01' ) . ' 00:00:00';
				break;
			default:
				$preset = '30d';
				$from   = wp_date( 'Y-m-d', strtotime( '-29 days', strtotime( $today ) ) ) . ' 00:00:00';
		}

		return compact( 'from', 'to', 'preset' );
	}

	private static function chart_series( $rows, $date_key, $value_key ) {
		$out = array();
		foreach ( (array) $rows as $row ) {
			$out[] = array(
				'label' => $row->{$date_key},
				'value' => (float) $row->{$value_key},
			);
		}
		return $out;
	}
}
