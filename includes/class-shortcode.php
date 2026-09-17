<?php
/**
 * Public shortcodes: payment form and receipt.
 *
 * @package RakanZakat
 */

defined( 'ABSPATH' ) || exit;

class RakanZakat_Shortcode {

	public static function register() {
		add_shortcode( 'rakanzakat_form', array( __CLASS__, 'form' ) );
		add_shortcode( 'rakanzakat_receipt', array( __CLASS__, 'receipt' ) );
	}

	public static function enqueue_public() {
		wp_enqueue_script(
			'rakanzakat-tracker',
			RAKANZAKAT_URL . 'public/js/tracker.js',
			array(),
			RAKANZAKAT_VERSION,
			true
		);
		wp_localize_script(
			'rakanzakat-tracker',
			'RakanZakatTrack',
			array(
				'endpoint' => rest_url( 'rakanzakat/v1/track' ),
			)
		);

		wp_enqueue_style(
			'rakanzakat-inter',
			'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
			array(),
			null
		);
		wp_enqueue_style( 'rakanzakat-form', RAKANZAKAT_URL . 'public/css/form.css', array( 'rakanzakat-inter' ), RAKANZAKAT_VERSION );
		wp_register_style(
			'rakanzakat-landing',
			RAKANZAKAT_URL . 'public/css/landing.css',
			array( 'rakanzakat-form' ),
			RAKANZAKAT_VERSION
		);
		wp_enqueue_script( 'rakanzakat-form', RAKANZAKAT_URL . 'public/js/form.js', array(), RAKANZAKAT_VERSION, true );
		wp_localize_script(
			'rakanzakat-form',
			'RakanZakatPay',
			array(
				'endpoint' => rest_url( 'rakanzakat/v1/checkout' ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	public static function form( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'kicker'      => '',
				'title'       => '',
				'description' => '',
				'button'      => 'Bayar Sekarang',
				'note'        => '',
				'presets'     => 'yes',
			),
			is_array( $atts ) ? $atts : array(),
			'rakanzakat_form'
		);

		$types    = RakanZakat_Settings::zakat_types();
		$id_types = RakanZakat_Settings::id_types();
		$states   = RakanZakat_Settings::states();
		$hauls    = RakanZakat_Settings::haul_years();
		$min      = (float) RakanZakat_Settings::get( 'min_amount', 10 );
		$presets  = array( 50, 100, 250, 500, 1000 );
		$show_presets = 'yes' === $atts['presets'] || '1' === (string) $atts['presets'];

		ob_start();
		?>
		<div class="rz-form-wrap js-rakanzakat-form" id="bayar">
			<form class="rz-form" method="post" novalidate>
				<?php if ( $atts['kicker'] || $atts['title'] || $atts['description'] ) : ?>
				<div class="rz-form__intro">
					<?php if ( $atts['kicker'] ) : ?>
						<p class="rz-form__kicker"><?php echo esc_html( $atts['kicker'] ); ?></p>
					<?php endif; ?>
					<?php if ( $atts['title'] ) : ?>
						<h2><?php echo esc_html( $atts['title'] ); ?></h2>
					<?php endif; ?>
					<?php if ( $atts['description'] ) : ?>
						<p><?php echo esc_html( $atts['description'] ); ?></p>
					<?php endif; ?>
				</div>
				<?php endif; ?>

				<div class="rz-row">
					<label class="rz-field">
						<span class="rz-label">
							Nama Penuh / Nama Syarikat@Organisasi <span class="rz-req">*</span>
							<button type="button" class="rz-help" title="Sila masukkan nama penuh mengikut MyKad atau nama syarikat seperti pendaftaran SSM" aria-label="Bantuan nama">i</button>
						</span>
						<input type="text" name="name" required autocomplete="name" placeholder="Nama penuh pembayar zakat | Syarikat seperti SSM">
					</label>
					<label class="rz-field">
						<span class="rz-label">No. Telefon <span class="rz-req">*</span></span>
						<span class="rz-phone">
							<span class="rz-phone__prefix" aria-hidden="true">
								<svg viewBox="0 0 640 480" width="20" height="14"><path fill="#cc0000" d="M0 0h640v480H0z"/><path stroke="#fff" stroke-width="34.3" d="M0 51.4h640M0 120h640M0 188.6h640M0 257.1h640M0 325.7h640M0 394.3h640M0 462.9h640"/><path fill="#000066" d="M0 0h320v274.3H0z"/><circle cx="160" cy="137.1" r="91.4" fill="#fc0"/><circle cx="182.9" cy="137.1" r="80" fill="#000066"/></svg>
								+60
							</span>
							<input type="tel" name="mobile" required autocomplete="tel" inputmode="numeric" placeholder="Contoh: 0129876543">
						</span>
					</label>
				</div>

				<label class="rz-field">
					<span class="rz-label">Jenis Pengenalan <span class="rz-req">*</span></span>
					<select name="id_type" required>
						<option value="" disabled selected>- Sila Pilih -</option>
						<?php foreach ( $id_types as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>

				<div class="rz-address">
					<p class="rz-address__title">Alamat Penuh</p>
					<div class="rz-row">
						<label class="rz-field">
							<span class="rz-label">Alamat Baris 1 <span class="rz-req">*</span></span>
							<input type="text" name="address_1" required autocomplete="address-line1" placeholder="Alamat Baris 1">
						</label>
						<label class="rz-field">
							<span class="rz-label">Alamat Baris 2</span>
							<input type="text" name="address_2" autocomplete="address-line2" placeholder="Alamat Baris 2">
						</label>
					</div>
					<div class="rz-row rz-row--address">
						<label class="rz-field">
							<span class="rz-label">Bandar <span class="rz-req">*</span></span>
							<input type="text" name="city" required autocomplete="address-level2" placeholder="Bandar">
						</label>
						<label class="rz-field">
							<span class="rz-label">Negeri <span class="rz-req">*</span></span>
							<select name="state" required>
								<?php foreach ( $states as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, 'Selangor' ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<label class="rz-field">
							<span class="rz-label">Poskod <span class="rz-req">*</span></span>
							<input type="text" name="postcode" required inputmode="numeric" maxlength="5" pattern="[0-9]{5}" placeholder="Poskod" autocomplete="postal-code">
						</label>
					</div>
				</div>

				<label class="rz-field">
					<span class="rz-label">Emel <span class="rz-req">*</span></span>
					<input type="email" name="email" required autocomplete="email" placeholder="ali@email.com">
				</label>

				<div class="rz-row rz-row--zakat">
					<label class="rz-field">
						<span class="rz-label">
							Jenis Zakat <span class="rz-req">*</span>
							<button type="button" class="rz-help" title="Pilih kategori zakat yang ingin ditunaikan" aria-label="Bantuan jenis zakat">i</button>
						</span>
						<select name="zakat_type" required>
							<option value="" disabled selected>- Sila Pilih Jenis Zakat -</option>
							<?php foreach ( $types as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="rz-field">
						<span class="rz-label">Haul/Tahun <span class="rz-req">*</span></span>
						<select name="haul_year" required>
							<option value="" disabled selected>- Tahun -</option>
							<?php foreach ( $hauls as $year ) : ?>
								<option value="<?php echo esc_attr( $year ); ?>"><?php echo esc_html( $year ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				</div>

				<label class="rz-field">
					<span class="rz-label">Amaun (RM) <span class="rz-req">*</span></span>
					<span class="rz-amount">
						<span class="rz-amount__prefix">RM</span>
						<input type="number" name="amount" min="<?php echo esc_attr( $min ); ?>" step="0.01" required placeholder="0.00" inputmode="decimal">
					</span>
					<?php if ( $show_presets ) : ?>
					<span class="rz-presets">
						<?php foreach ( $presets as $preset ) : ?>
							<button type="button" class="rz-preset" data-amount="<?php echo esc_attr( $preset ); ?>">RM<?php echo esc_html( (string) $preset ); ?></button>
						<?php endforeach; ?>
					</span>
					<?php endif; ?>
				</label>

				<div class="rz-niat-box">
					<label class="rz-niat">
						<input type="checkbox" name="niat" value="1" required>
						<span>Niat Membayar Zakat <span class="rz-req">*</span></span>
					</label>
					<p class="rz-niat-text">Inilah wang sebanyak <strong class="js-rz-niat-amount">RM0.00</strong> sebagai menunaikan zakat yang wajib ke atas diri saya kerana ALLAH Ta'ala.</p>
				</div>

				<p class="rz-error" hidden></p>
				<div class="rz-actions">
					<p class="rz-note"><?php echo $atts['note'] ? esc_html( $atts['note'] ) : 'Transaksi selamat &amp; rasmi melalui Rakanzakat.com'; ?></p>
					<button type="submit" class="rz-submit"><?php echo esc_html( $atts['button'] ); ?></button>
				</div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function receipt() {
		$bill_id = isset( $_GET['rz_bill'] ) ? sanitize_text_field( wp_unslash( $_GET['rz_bill'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status  = isset( $_GET['rz_status'] ) ? sanitize_key( wp_unslash( $_GET['rz_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$row     = $bill_id ? RakanZakat_Payments::get_by_bill( $bill_id ) : null;
		$types   = RakanZakat_Settings::zakat_types();

		ob_start();
		?>
		<div class="rz-form-wrap rz-receipt">
			<?php if ( $row && 'paid' === $row->status ) : ?>
				<p class="rz-form__kicker">Pembayaran berjaya</p>
				<h2>Jazakallahu khairan</h2>
				<p>Kutipan zakat anda telah direkodkan. Resit juga dihantar oleh Billplz ke emel anda.</p>
				<dl class="rz-receipt__meta">
					<div><dt>Rujukan</dt><dd><?php echo esc_html( $row->bill_id ); ?></dd></div>
					<div><dt>Nama</dt><dd><?php echo esc_html( $row->payer_name ); ?></dd></div>
					<div><dt>Jenis</dt><dd><?php echo esc_html( $types[ $row->zakat_type ] ?? $row->zakat_type ); ?></dd></div>
					<div><dt>Amaun</dt><dd><?php echo esc_html( RakanZakat_Settings::format_money( $row->paid_amount_sen ?: $row->amount_sen ) ); ?></dd></div>
				</dl>
			<?php elseif ( $row ) : ?>
				<p class="rz-form__kicker">Belum selesai</p>
				<h2>Pembayaran belum berjaya</h2>
				<p>Status semasa: <strong><?php echo esc_html( $row->status ); ?></strong>. Anda boleh cuba semula dari halaman bayar zakat.</p>
			<?php elseif ( 'paid' === $status ) : ?>
				<h2>Terima kasih</h2>
				<p>Pembayaran sedang disahkan. Sila semak emel anda sebentar lagi.</p>
			<?php else : ?>
				<h2>Tiada rekod pembayaran</h2>
				<p>Jika anda baru selesai membayar, tunggu sebentar dan muat semula halaman ini.</p>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
