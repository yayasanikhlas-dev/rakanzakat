<?php
/**
 * Shared landing sections (Elementor widgets + standalone template).
 *
 * @package RakanZakat
 */

defined( 'ABSPATH' ) || exit;

class RakanZakat_Sections {

	public static function hero( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'eyebrow'     => 'Kutipan zakat · Malaysia',
				'title'       => 'Tunaikan zakat dengan yakin.',
				'lead'        => 'Isi borang, bayar melalui Billplz (FPX, kad atau e-wallet), dan terima resit terus ke emel. Setiap kutipan direkod dalam dashboard Rakanzakat.',
				'btn_text'    => 'Bayar sekarang',
				'btn_url'     => '#bayar',
				'btn2_text'   => 'Lihat cara bayar',
				'btn2_url'    => '#cara',
				'trust'       => "Billplz · FPX\nKad debit / kredit\nE-wallet\nResit automatik",
			)
		);
		$trust = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $args['trust'] ) ) );
		ob_start();
		?>
		<section class="rz-el rz-lp__hero">
			<?php if ( $args['eyebrow'] ) : ?>
				<p class="rz-lp__eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p>
			<?php endif; ?>
			<?php if ( $args['title'] ) : ?>
				<h1><?php echo esc_html( $args['title'] ); ?></h1>
			<?php endif; ?>
			<?php if ( $args['lead'] ) : ?>
				<p class="rz-lp__lead"><?php echo esc_html( $args['lead'] ); ?></p>
			<?php endif; ?>
			<div class="rz-lp__hero-actions">
				<?php if ( $args['btn_text'] ) : ?>
					<a class="rz-lp__btn" href="<?php echo esc_url( $args['btn_url'] ? $args['btn_url'] : '#bayar' ); ?>"><?php echo esc_html( $args['btn_text'] ); ?></a>
				<?php endif; ?>
				<?php if ( $args['btn2_text'] ) : ?>
					<a class="rz-lp__btn rz-lp__btn--ghost" href="<?php echo esc_url( $args['btn2_url'] ? $args['btn2_url'] : '#cara' ); ?>"><?php echo esc_html( $args['btn2_text'] ); ?></a>
				<?php endif; ?>
			</div>
			<?php if ( $trust ) : ?>
				<ul class="rz-lp__trust">
					<?php foreach ( $trust as $item ) : ?>
						<li><?php echo esc_html( $item ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
		<?php
		return ob_get_clean();
	}

	public static function form_section( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'eyebrow'     => 'Langkah 1',
				'title'       => 'Borang pembayaran',
				'description' => 'Masukkan maklumat pembayar, jenis zakat, haul dan amaun. Selepas niat, anda akan dibawa ke halaman Billplz yang selamat.',
				'button'      => 'Bayar Sekarang',
			)
		);
		ob_start();
		?>
		<section class="rz-el rz-lp__form" id="bayar">
			<div class="rz-lp__form-copy">
				<?php if ( $args['eyebrow'] ) : ?>
					<p class="rz-lp__eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p>
				<?php endif; ?>
				<?php if ( $args['title'] ) : ?>
					<h2><?php echo esc_html( $args['title'] ); ?></h2>
				<?php endif; ?>
				<?php if ( $args['description'] ) : ?>
					<p><?php echo esc_html( $args['description'] ); ?></p>
				<?php endif; ?>
			</div>
			<?php
			echo self::safe_form( $args['button'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</section>
		<?php
		return ob_get_clean();
	}

	public static function steps( $args = array() ) {
		$defaults = array(
			array(
				'title' => 'Isi borang',
				'text'  => 'Nama, alamat, jenis zakat, haul dan amaun.',
			),
			array(
				'title' => 'Bayar di Billplz',
				'text'  => 'Pilih FPX, kad atau e-wallet. Transaksi disahkan dengan X-Signature.',
			),
			array(
				'title' => 'Terima resit',
				'text'  => 'Billplz hantar resit ke emel. Rekod masuk dashboard kutipan.',
			),
		);
		$args = wp_parse_args(
			$args,
			array(
				'eyebrow' => 'Mudah & selamat',
				'title'   => 'Tiga langkah sahaja',
				'items'   => $defaults,
			)
		);
		$items = $args['items'] ? $args['items'] : $defaults;
		ob_start();
		?>
		<section class="rz-el rz-lp__steps" id="cara">
			<?php if ( $args['eyebrow'] ) : ?>
				<p class="rz-lp__eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p>
			<?php endif; ?>
			<?php if ( $args['title'] ) : ?>
				<h2><?php echo esc_html( $args['title'] ); ?></h2>
			<?php endif; ?>
			<ol>
				<?php foreach ( $items as $item ) : ?>
					<li>
						<strong><?php echo esc_html( $item['title'] ?? '' ); ?></strong>
						<span><?php echo esc_html( $item['text'] ?? '' ); ?></span>
					</li>
				<?php endforeach; ?>
			</ol>
		</section>
		<?php
		return ob_get_clean();
	}

	public static function types( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'eyebrow' => 'Pilihan',
				'title'   => 'Jenis zakat yang diterima',
			)
		);
		$types = RakanZakat_Settings::zakat_types();
		ob_start();
		?>
		<section class="rz-el rz-lp__types" id="jenis">
			<?php if ( $args['eyebrow'] ) : ?>
				<p class="rz-lp__eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p>
			<?php endif; ?>
			<?php if ( $args['title'] ) : ?>
				<h2><?php echo esc_html( $args['title'] ); ?></h2>
			<?php endif; ?>
			<ul>
				<?php foreach ( $types as $label ) : ?>
					<li><?php echo esc_html( $label ); ?></li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php
		return ob_get_clean();
	}

	public static function faq( $args = array() ) {
		$defaults = array(
			array(
				'q' => 'Pembayaran ni selamat ke?',
				'a' => 'Ya. Pembayaran diproses oleh Billplz, gateway berlesen di Malaysia. Plugin Rakan Zakat tidak menyimpan nombor kad anda.',
			),
			array(
				'q' => 'Saya dapat resit ke?',
				'a' => 'Billplz hantar resit ke emel yang diisi dalam borang. Anda juga akan kembali ke halaman terima kasih selepas bayaran berjaya.',
			),
			array(
				'q' => 'Boleh bayar untuk syarikat?',
				'a' => 'Boleh. Isi nama syarikat pada ruangan nama, pilih jenis pengenalan SSM, dan lengkapkan alamat organisasi.',
			),
			array(
				'q' => 'Apa itu haul/tahun?',
				'a' => 'Haul ialah tahun kewajipan zakat yang anda tunaikan — contohnya pendapatan 2025 dibayar pada 2026.',
			),
		);
		$args  = wp_parse_args(
			$args,
			array(
				'eyebrow' => 'FAQ',
				'title'   => 'Soalan lazim',
				'items'   => $defaults,
			)
		);
		$items = $args['items'] ? $args['items'] : $defaults;
		ob_start();
		?>
		<section class="rz-el rz-lp__faq" id="soalan">
			<?php if ( $args['eyebrow'] ) : ?>
				<p class="rz-lp__eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p>
			<?php endif; ?>
			<?php if ( $args['title'] ) : ?>
				<h2><?php echo esc_html( $args['title'] ); ?></h2>
			<?php endif; ?>
			<?php foreach ( $items as $item ) : ?>
				<details>
					<summary><?php echo esc_html( $item['q'] ?? '' ); ?></summary>
					<p><?php echo esc_html( $item['a'] ?? '' ); ?></p>
				</details>
			<?php endforeach; ?>
		</section>
		<?php
		return ob_get_clean();
	}

	public static function footer( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'line1' => 'Rakanzakat.com · Kutipan zakat melalui Billplz',
				'line2' => 'Sila pastikan niat dan amaun betul sebelum membayar. Untuk bantuan, hubungi pentadbir laman.',
			)
		);
		ob_start();
		?>
		<footer class="rz-el rz-lp__foot">
			<?php if ( $args['line1'] ) : ?>
				<p><?php echo esc_html( $args['line1'] ); ?></p>
			<?php endif; ?>
			<?php if ( $args['line2'] ) : ?>
				<p><?php echo esc_html( $args['line2'] ); ?></p>
			<?php endif; ?>
		</footer>
		<?php
		return ob_get_clean();
	}

	public static function full_landing() {
		return self::hero()
			. self::form_section()
			. self::steps()
			. self::types()
			. self::faq()
			. self::footer();
	}

	private static function safe_form( $button ) {
		return RakanZakat_Shortcode::form(
			array(
				'kicker'      => '',
				'title'       => '',
				'description' => '',
				'button'      => $button ? $button : 'Bayar Sekarang',
				'note'        => '',
				'presets'     => 'yes',
			)
		);
	}
}
