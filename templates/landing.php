<?php
/**
 * Standalone landing page. Theme header/footer are skipped on purpose.
 *
 * @package RakanZakat
 */

defined( 'ABSPATH' ) || exit;

$types = RakanZakat_Settings::zakat_types();
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'rz-landing-body' ); ?>>
<?php wp_body_open(); ?>

<div class="rz-lp">
	<header class="rz-lp__nav">
		<a class="rz-lp__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">Rakanzakat</a>
		<nav>
			<a href="#cara">Cara bayar</a>
			<a href="#jenis">Jenis zakat</a>
			<a href="#soalan">Soalan</a>
			<a class="rz-lp__nav-cta" href="#bayar">Bayar zakat</a>
		</nav>
	</header>

	<section class="rz-lp__hero">
		<p class="rz-lp__eyebrow">Kutipan zakat · Malaysia</p>
		<h1>Tunaikan zakat dengan yakin.</h1>
		<p class="rz-lp__lead">Isi borang, bayar melalui Billplz (FPX, kad atau e-wallet), dan terima resit terus ke emel. Setiap kutipan direkod dalam dashboard Rakanzakat.</p>
		<div class="rz-lp__hero-actions">
			<a class="rz-lp__btn" href="#bayar">Bayar sekarang</a>
			<a class="rz-lp__btn rz-lp__btn--ghost" href="#cara">Lihat cara bayar</a>
		</div>
		<ul class="rz-lp__trust">
			<li>Billplz · FPX</li>
			<li>Kad debit / kredit</li>
			<li>E-wallet</li>
			<li>Resit automatik</li>
		</ul>
	</section>

	<section class="rz-lp__form" id="bayar">
		<div class="rz-lp__form-copy">
			<p class="rz-lp__eyebrow">Langkah 1</p>
			<h2>Borang pembayaran</h2>
			<p>Masukkan maklumat pembayar, jenis zakat, haul dan amaun. Selepas niat, anda akan dibawa ke halaman Billplz yang selamat.</p>
		</div>
		<?php
		echo RakanZakat_Shortcode::form( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			array(
				'kicker'      => '',
				'title'       => '',
				'description' => '',
				'button'      => 'Bayar Sekarang',
				'note'        => '',
				'presets'     => 'yes',
			)
		);
		?>
	</section>

	<section class="rz-lp__steps" id="cara">
		<p class="rz-lp__eyebrow">Mudah &amp; selamat</p>
		<h2>Tiga langkah sahaja</h2>
		<ol>
			<li>
				<strong>Isi borang</strong>
				<span>Nama, alamat, jenis zakat, haul dan amaun.</span>
			</li>
			<li>
				<strong>Bayar di Billplz</strong>
				<span>Pilih FPX, kad atau e-wallet. Transaksi disahkan dengan X-Signature.</span>
			</li>
			<li>
				<strong>Terima resit</strong>
				<span>Billplz hantar resit ke emel. Rekod masuk dashboard kutipan.</span>
			</li>
		</ol>
	</section>

	<section class="rz-lp__types" id="jenis">
		<p class="rz-lp__eyebrow">Pilihan</p>
		<h2>Jenis zakat yang diterima</h2>
		<ul>
			<?php foreach ( $types as $label ) : ?>
				<li><?php echo esc_html( $label ); ?></li>
			<?php endforeach; ?>
		</ul>
	</section>

	<section class="rz-lp__faq" id="soalan">
		<p class="rz-lp__eyebrow">FAQ</p>
		<h2>Soalan lazim</h2>
		<details>
			<summary>Pembayaran ni selamat ke?</summary>
			<p>Ya. Pembayaran diproses oleh Billplz, gateway berlesen di Malaysia. Plugin Rakan Zakat tidak menyimpan nombor kad anda.</p>
		</details>
		<details>
			<summary>Saya dapat resit ke?</summary>
			<p>Billplz hantar resit ke emel yang diisi dalam borang. Anda juga akan kembali ke halaman terima kasih selepas bayaran berjaya.</p>
		</details>
		<details>
			<summary>Boleh bayar untuk syarikat?</summary>
			<p>Boleh. Isi nama syarikat pada ruangan nama, pilih jenis pengenalan SSM, dan lengkapkan alamat organisasi.</p>
		</details>
		<details>
			<summary>Apa itu haul/tahun?</summary>
			<p>Haul ialah tahun kewajipan zakat yang anda tunaikan — contohnya pendapatan 2025 dibayar pada 2026.</p>
		</details>
	</section>

	<footer class="rz-lp__foot">
		<p>Rakanzakat.com · Kutipan zakat melalui Billplz</p>
		<p>Sila pastikan niat dan amaun betul sebelum membayar. Untuk bantuan, hubungi pentadbir laman.</p>
	</footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
