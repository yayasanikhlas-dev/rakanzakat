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
				'header'      => 'yes',
				'kicker'      => 'Cara Pembayaran',
				'title'       => 'Bayar Zakat Secara Online',
				'description' => 'Sistem pembayaran zakat secara online ini menyediakan platform pembayaran zakat dengan lebih efisyen dan bersistematik.',
				'step_1'      => '1. Isi Maklumat Pembayaran',
				'step_2'      => '2. Pilih Kaedah Pembayaran',
				'step_3'      => '3. Resit bayaran zakat',
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
		$show_header  = 'no' !== $atts['header'] && '0' !== (string) $atts['header'];
		if ( $show_header ) {
			if ( '' === trim( (string) $atts['kicker'] ) ) {
				$atts['kicker'] = 'Cara Pembayaran';
			}
			if ( '' === trim( (string) $atts['title'] ) ) {
				$atts['title'] = 'Bayar Zakat Secara Online';
			}
			if ( '' === trim( (string) $atts['description'] ) ) {
				$atts['description'] = 'Sistem pembayaran zakat secara online ini menyediakan platform pembayaran zakat dengan lebih efisyen dan bersistematik.';
			}
			if ( '' === trim( (string) $atts['step_1'] ) ) {
				$atts['step_1'] = '1. Isi Maklumat Pembayaran';
			}
			if ( '' === trim( (string) $atts['step_2'] ) ) {
				$atts['step_2'] = '2. Pilih Kaedah Pembayaran';
			}
			if ( '' === trim( (string) $atts['step_3'] ) ) {
				$atts['step_3'] = '3. Resit bayaran zakat';
			}
		}

		ob_start();
		?>
		<div class="js-rakanzakat-form" id="bayar">
			<?php if ( $show_header ) : ?>
			<div class="rzf-hero">
				<p class="rzf-hero__badge">
					<svg viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
					<?php echo esc_html( $atts['kicker'] ); ?>
				</p>
				<?php if ( $atts['title'] ) : ?>
					<h2 class="rzf-hero__title"><?php echo esc_html( $atts['title'] ); ?></h2>
				<?php endif; ?>
				<?php if ( $atts['description'] ) : ?>
					<p class="rzf-hero__lead"><?php echo esc_html( $atts['description'] ); ?></p>
				<?php endif; ?>
				<div class="rzf-hero__steps">
					<div class="rzf-hero__step">
						<span class="rzf-hero__icon" aria-hidden="true">
							<svg viewBox="0 0 48 48" fill="none"><rect x="12" y="8" width="24" height="32" rx="3" stroke="#60A5FA" stroke-width="2"/><path d="M18 18h12M18 24h12M18 30h8" stroke="#60A5FA" stroke-width="2" stroke-linecap="round"/></svg>
						</span>
						<span><?php echo esc_html( $atts['step_1'] ); ?></span>
					</div>
					<div class="rzf-hero__step">
						<span class="rzf-hero__icon" aria-hidden="true">
							<svg viewBox="0 0 48 48" fill="none"><rect x="10" y="16" width="28" height="20" rx="3" stroke="#60A5FA" stroke-width="2"/><path d="M16 16v-2a8 8 0 0116 0v2" stroke="#60A5FA" stroke-width="2" stroke-linecap="round"/><circle cx="30" cy="26" r="2" fill="#60A5FA"/></svg>
						</span>
						<span><?php echo esc_html( $atts['step_2'] ); ?></span>
					</div>
					<div class="rzf-hero__step">
						<span class="rzf-hero__icon" aria-hidden="true">
							<svg viewBox="0 0 48 48" fill="none"><path d="M16 10h16l6 6v22a3 3 0 01-3 3H16a3 3 0 01-3-3V13a3 3 0 013-3z" stroke="#60A5FA" stroke-width="2"/><path d="M32 10v7h7M20 24h12M20 30h8" stroke="#60A5FA" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
						</span>
						<span><?php echo esc_html( $atts['step_3'] ); ?></span>
					</div>
				</div>
			</div>
			<?php endif; ?>
			<div class="rzf-card">
			<form method="post" novalidate>
				<div class="rzf-grid-2">
					<div>
						<label class="rzf-label" for="rz-name">
							<span>Nama Penuh / Nama Syarikat@Organisasi</span>
							<span class="rzf-req">*</span>
							<button type="button" class="rzf-help" title="Sila masukkan nama penuh mengikut MyKad atau nama syarikat seperti pendaftaran SSM" aria-label="Bantuan nama">
								<svg viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
							</button>
						</label>
						<input class="custom-input" id="rz-name" type="text" name="name" required autocomplete="name" placeholder="Nama penuh pembayar zakat | Syarikat seperti SSM">
					</div>
					<div>
						<label class="rzf-label" for="rz-mobile">No. Telefon <span class="rzf-req">*</span></label>
						<div class="rzf-phone">
							<div class="rzf-phone__prefix" aria-hidden="true">
								<svg viewBox="0 0 640 480"><path fill="#cc0000" d="M0 0h640v480H0z"/><path stroke="#fff" stroke-width="34.3" d="M0 51.4h640M0 120h640M0 188.6h640M0 257.1h640M0 325.7h640M0 394.3h640M0 462.9h640"/><path fill="#000066" d="M0 0h320v274.3H0z"/><circle cx="160" cy="137.1" r="91.4" fill="#fc0"/><circle cx="182.9" cy="137.1" r="80" fill="#000066"/><polygon fill="#fc0" points="194.3,64 199.5,91.4 224,79 217.1,105.7 244.3,105.1 226.7,126.1 251.4,137.1 226.7,148.2 244.3,169.2 217.1,168.6 224,195.3 199.5,182.9 194.3,210.3 182.9,185.7 168.6,206.9 171.4,179.8 144.3,188.6 158.6,165.3 133.3,160.9 154.3,143.6 131.4,137.1 154.3,130.7 133.3,113.4 158.6,109 144.3,85.7 171.4,94.5 168.6,67.4 182.9,88.6"/></svg>
								<span>+60</span>
							</div>
							<input class="custom-input" id="rz-mobile" type="tel" name="mobile" required autocomplete="tel" inputmode="numeric" placeholder="Contoh: 0129876543">
						</div>
					</div>
				</div>

				<div>
					<label class="rzf-label" for="rz-id-type">Jenis Pengenalan <span class="rzf-req">*</span></label>
					<select class="custom-input" id="rz-id-type" name="id_type" required>
						<option value="" disabled selected>- Sila Pilih -</option>
						<?php foreach ( $id_types as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="rzf-address">
					<div class="rzf-address__head">
						<p class="rzf-address__title">Alamat Penuh</p>
						<div class="rzf-address__line"></div>
					</div>
					<div class="rzf-grid-2">
						<div>
							<label class="rzf-label" for="rz-address-1">Alamat Baris 1 <span class="rzf-req">*</span></label>
							<input class="custom-input" id="rz-address-1" type="text" name="address_1" required autocomplete="address-line1" placeholder="Alamat Baris 1">
						</div>
						<div>
							<label class="rzf-label" for="rz-address-2">Alamat Baris 2</label>
							<input class="custom-input" id="rz-address-2" type="text" name="address_2" autocomplete="address-line2" placeholder="Alamat Baris 2">
						</div>
					</div>
					<div class="rzf-grid-city">
						<div>
							<label class="rzf-label" for="rz-city">Bandar <span class="rzf-req">*</span></label>
							<input class="custom-input" id="rz-city" type="text" name="city" required autocomplete="address-level2" placeholder="Bandar">
						</div>
						<div>
							<label class="rzf-label" for="rz-state">Negeri <span class="rzf-req">*</span></label>
							<select class="custom-input" id="rz-state" name="state" required>
								<?php foreach ( $states as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, 'Selangor' ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div>
							<label class="rzf-label" for="rz-postcode">Poskod <span class="rzf-req">*</span></label>
							<input class="custom-input" id="rz-postcode" type="text" name="postcode" required inputmode="numeric" maxlength="5" pattern="[0-9]{5}" placeholder="Poskod" autocomplete="postal-code">
						</div>
					</div>
				</div>

				<div>
					<label class="rzf-label" for="rz-email">Emel <span class="rzf-req">*</span></label>
					<input class="custom-input" id="rz-email" type="email" name="email" required autocomplete="email" placeholder="ali@email.com">
				</div>

				<div class="rzf-grid-zakat">
					<div>
						<label class="rzf-label" for="rz-zakat-type">
							<span>Jenis Zakat</span>
							<span class="rzf-req">*</span>
							<button type="button" class="rzf-help" title="Pilih kategori zakat yang ingin ditunaikan" aria-label="Bantuan jenis zakat">
								<svg viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
							</button>
						</label>
						<select class="custom-input" id="rz-zakat-type" name="zakat_type" required>
							<option value="" disabled selected>- Sila Pilih Jenis Zakat -</option>
							<?php foreach ( $types as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div>
						<label class="rzf-label" for="rz-haul">Haul/Tahun <span class="rzf-req">*</span></label>
						<select class="custom-input" id="rz-haul" name="haul_year" required>
							<option value="" disabled selected>- Tahun -</option>
							<?php foreach ( $hauls as $year ) : ?>
								<option value="<?php echo esc_attr( $year ); ?>"><?php echo esc_html( $year ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<div>
					<label class="rzf-label" for="rz-amount">Amaun (RM) <span class="rzf-req">*</span></label>
					<div class="rzf-amount">
						<span class="rzf-amount__prefix">RM</span>
						<input class="custom-input" id="rz-amount" type="number" name="amount" min="<?php echo esc_attr( $min ); ?>" step="0.01" required placeholder="0.00" inputmode="decimal">
					</div>
					<?php if ( $show_presets ) : ?>
					<div class="rzf-chips">
						<?php foreach ( $presets as $preset ) : ?>
							<button type="button" class="chip-btn" data-amount="<?php echo esc_attr( $preset ); ?>">RM<?php echo esc_html( (string) $preset ); ?></button>
						<?php endforeach; ?>
					</div>
					<?php endif; ?>
				</div>

				<div class="rzf-niat-wrap">
					<div class="rzf-niat-box">
						<label class="rzf-niat">
							<input type="checkbox" name="niat" value="1" required>
							<span>Niat Membayar Zakat <span class="rzf-req">*</span></span>
						</label>
						<p class="rzf-niat-text">Inilah wang sebanyak <strong class="js-rz-niat-amount">RM0.00</strong> sebagai menunaikan zakat yang wajib ke atas diri saya kerana ALLAH Ta'ala.</p>
					</div>
				</div>

				<p class="rz-error" hidden></p>
				<div class="rzf-actions">
					<p class="rzf-note"><?php echo $atts['note'] ? esc_html( $atts['note'] ) : 'Transaksi selamat &amp; rasmi melalui Rakanzakat.com'; ?></p>
					<button type="submit" class="rzf-submit"><?php echo esc_html( $atts['button'] ); ?></button>
				</div>
			</form>
			</div>
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
		<div class="js-rakanzakat-form rz-receipt">
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
