<?php
/**
 * Standalone landing page. Theme header/footer are skipped on purpose.
 *
 * @package RakanZakat
 */

defined( 'ABSPATH' ) || exit;
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
	<?php echo RakanZakat_Sections::full_landing(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>

<?php wp_footer(); ?>
</body>
</html>
