<?php
/**
 * Front-end admin portal (/admin) and affiliate area (/affiliate-area).
 *
 * @package RakanZakat
 */

defined( 'ABSPATH' ) || exit;

class RakanZakat_Portal {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'rewrite' ) );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'parse_request', array( __CLASS__, 'parse_request' ) );
		add_action( 'template_redirect', array( __CLASS__, 'render' ) );
		add_action( 'admin_init', array( __CLASS__, 'block_wp_admin' ) );
		add_filter( 'show_admin_bar', array( __CLASS__, 'admin_bar' ) );
		add_filter( 'login_redirect', array( __CLASS__, 'login_redirect' ), 10, 3 );
		if ( get_option( 'rakanzakat_rewrite' ) !== '1.9.0' ) {
			add_action( 'init', array( __CLASS__, 'maybe_flush' ), 99 );
		}
	}

	public static function parse_request( $wp ) {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$path = (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
		$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		if ( $home && '/' !== $home && 0 === strpos( $path, rtrim( $home, '/' ) ) ) {
			$path = substr( $path, strlen( rtrim( $home, '/' ) ) );
		}
		$path = trim( $path, '/' );
		if ( preg_match( '#^admin(?:/([a-z0-9_-]+))?$#i', $path, $m ) ) {
			$wp->query_vars['rz_portal'] = 'admin';
			$wp->query_vars['rz_view']   = ! empty( $m[1] ) ? sanitize_key( $m[1] ) : 'dashboard';
			unset( $wp->query_vars['error'], $wp->query_vars['pagename'], $wp->query_vars['name'] );
		} elseif ( preg_match( '#^affiliate-area(?:/([a-z0-9_-]+))?$#i', $path, $m ) ) {
			$wp->query_vars['rz_portal'] = 'affiliate';
			$wp->query_vars['rz_view']   = ! empty( $m[1] ) ? sanitize_key( $m[1] ) : 'dashboard';
			unset( $wp->query_vars['error'], $wp->query_vars['pagename'], $wp->query_vars['name'] );
		}
	}

	public static function login_redirect( $redirect, $requested, $user ) {
		if ( ! $user instanceof WP_User ) {
			return $redirect;
		}
		if ( user_can( $user, 'manage_options' ) ) {
			return $requested ? $requested : $redirect;
		}
		if ( user_can( $user, 'rz_manage_portal' ) ) {
			return self::url( 'admin' );
		}
		if ( user_can( $user, 'rz_affiliate_portal' ) ) {
			return self::url( 'affiliate' );
		}
		return $redirect;
	}

	public static function maybe_flush() {
		self::rewrite();
		flush_rewrite_rules( false );
		update_option( 'rakanzakat_rewrite', '1.9.0' );
	}

	public static function rewrite() {
		add_rewrite_rule( '^admin/?$', 'index.php?rz_portal=admin&rz_view=dashboard', 'top' );
		add_rewrite_rule( '^admin/([^/]+)/?$', 'index.php?rz_portal=admin&rz_view=$matches[1]', 'top' );
		add_rewrite_rule( '^affiliate-area/?$', 'index.php?rz_portal=affiliate&rz_view=dashboard', 'top' );
		add_rewrite_rule( '^affiliate-area/([^/]+)/?$', 'index.php?rz_portal=affiliate&rz_view=$matches[1]', 'top' );
	}

	public static function query_vars( $vars ) {
		$vars[] = 'rz_portal';
		$vars[] = 'rz_view';
		return $vars;
	}

	public static function url( $portal, $view = 'dashboard' ) {
		$base = 'admin' === $portal ? 'admin' : 'affiliate-area';
		$path = 'dashboard' === $view ? $base . '/' : $base . '/' . $view . '/';
		return home_url( '/' . $path );
	}

	public static function can_admin() {
		return current_user_can( 'manage_options' ) || current_user_can( 'rz_manage_portal' );
	}

	public static function can_affiliate() {
		return current_user_can( 'rz_affiliate_portal' ) || self::can_admin();
	}

	public static function admin_bar( $show ) {
		if ( is_user_logged_in() && ! current_user_can( 'manage_options' ) && ( self::can_admin() || self::can_affiliate() ) ) {
			return false;
		}
		return $show;
	}

	public static function block_wp_admin() {
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( is_user_logged_in() && current_user_can( 'rz_manage_portal' ) ) {
			wp_safe_redirect( self::url( 'admin' ) );
			exit;
		}
		if ( is_user_logged_in() && current_user_can( 'rz_affiliate_portal' ) ) {
			wp_safe_redirect( self::url( 'affiliate' ) );
			exit;
		}
	}

	public static function render() {
		$portal = get_query_var( 'rz_portal' );
		if ( ! $portal ) {
			return;
		}
		$view = sanitize_key( get_query_var( 'rz_view' ) ?: 'dashboard' );

		if ( 'logout' === $view ) {
			wp_logout();
			wp_safe_redirect( self::url( $portal ) );
			exit;
		}

		self::handle_post( $portal );

		if ( ! is_user_logged_in() ) {
			self::login_screen( $portal );
			exit;
		}

		if ( 'admin' === $portal && ! self::can_admin() ) {
			if ( self::can_affiliate() ) {
				wp_safe_redirect( self::url( 'affiliate' ) );
				exit;
			}
			wp_die( esc_html__( 'Akses ditolak.', 'rakanzakat' ), 403 );
		}
		if ( 'affiliate' === $portal && ! self::can_affiliate() ) {
			wp_die( esc_html__( 'Akses ditolak.', 'rakanzakat' ), 403 );
		}

		status_header( 200 );
		nocache_headers();
		if ( 'admin' === $portal ) {
			self::admin_shell( $view );
		} else {
			self::affiliate_shell( $view );
		}
		exit;
	}

	private static function handle_post( $portal ) {
		if ( empty( $_POST['rz_portal_action'] ) ) {
			return;
		}
		$action = sanitize_key( wp_unslash( $_POST['rz_portal_action'] ) );
		if ( 'login' === $action ) {
			check_admin_referer( 'rz_portal_login' );
			$raw = wp_unslash( $_POST['log'] ?? '' );
			if ( is_email( $raw ) ) {
				$found = get_user_by( 'email', $raw );
				$log   = $found ? $found->user_login : $raw;
			} else {
				$log = sanitize_user( $raw, true );
			}
			$login = wp_signon(
				array(
					'user_login'    => $log,
					'user_password' => (string) ( $_POST['pwd'] ?? '' ),
					'remember'      => true,
				),
				is_ssl()
			);
			if ( is_wp_error( $login ) ) {
				set_transient( 'rz_portal_login_error_' . self::client_key(), $login->get_error_message(), 60 );
				wp_safe_redirect( self::url( $portal ) );
				exit;
			}
			wp_safe_redirect( self::url( $portal ) );
			exit;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( 'save_settings' === $action && self::can_admin() ) {
			check_admin_referer( 'rz_portal_settings' );
			RakanZakat_Settings::update(
				array(
					'api_key'       => sanitize_text_field( wp_unslash( $_POST['api_key'] ?? '' ) ),
					'x_signature'   => sanitize_text_field( wp_unslash( $_POST['x_signature'] ?? '' ) ),
					'collection_id' => sanitize_text_field( wp_unslash( $_POST['collection_id'] ?? '' ) ),
					'sandbox'       => empty( $_POST['sandbox'] ) ? 0 : 1,
					'min_amount'    => (float) ( $_POST['min_amount'] ?? 10 ),
				)
			);
			wp_safe_redirect( add_query_arg( 'ok', '1', self::url( 'admin', 'tetapan' ) ) );
			exit;
		}

		if ( 'add_affiliate' === $action && self::can_admin() ) {
			check_admin_referer( 'rz_portal_affiliate' );
			$result = RakanZakat_Affiliates::create( $_POST );
			$args   = is_wp_error( $result ) ? array( 'err' => $result->get_error_message() ) : array( 'ok' => '1' );
			wp_safe_redirect( add_query_arg( $args, self::url( 'admin', 'affiliates' ) ) );
			exit;
		}

		if ( 'payout' === $action && self::can_admin() ) {
			check_admin_referer( 'rz_portal_payout' );
			$result = RakanZakat_Affiliates::pay_unpaid( (int) ( $_POST['affiliate_id'] ?? 0 ), wp_unslash( $_POST['notes'] ?? '' ) );
			$args   = is_wp_error( $result ) ? array( 'err' => $result->get_error_message() ) : array( 'ok' => '1' );
			wp_safe_redirect( add_query_arg( $args, self::url( 'admin', 'affiliates' ) ) );
			exit;
		}

		if ( 'add_campaign' === $action && self::can_admin() ) {
			check_admin_referer( 'rz_portal_roi' );
			RakanZakat_Campaigns::create_campaign( $_POST );
			wp_safe_redirect( add_query_arg( 'ok', '1', self::url( 'admin', 'iklan' ) ) );
			exit;
		}

		if ( 'add_spend' === $action && self::can_admin() ) {
			check_admin_referer( 'rz_portal_roi' );
			RakanZakat_Campaigns::add_spend(
				array(
					'campaign_id' => (int) ( $_POST['spend_campaign_id'] ?? 0 ),
					'platform'    => sanitize_key( wp_unslash( $_POST['spend_platform'] ?? 'other' ) ),
					'spend_date'  => sanitize_text_field( wp_unslash( $_POST['spend_date'] ?? '' ) ),
					'amount'      => $_POST['spend_amount'] ?? 0,
					'notes'       => wp_unslash( $_POST['spend_notes'] ?? '' ),
				)
			);
			wp_safe_redirect( add_query_arg( 'ok', '1', self::url( 'admin', 'iklan' ) ) );
			exit;
		}
	}

	private static function client_key() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return md5( $ip );
	}

	private static function login_screen( $portal ) {
		$error = get_transient( 'rz_portal_login_error_' . self::client_key() );
		if ( $error ) {
			delete_transient( 'rz_portal_login_error_' . self::client_key() );
		}
		$title = 'admin' === $portal ? 'Admin Rakan Zakat' : 'Affiliate Area';
		status_header( 200 );
		self::assets();
		?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( $title ); ?></title>
	<?php wp_print_styles( array( 'rakanzakat-jakarta', 'rakanzakat-portal' ) ); ?>
</head>
<body class="rzp-login-body">
	<main class="rzp-login">
		<p class="rzp-login__brand">Rakan Zakat</p>
		<h1><?php echo esc_html( $title ); ?></h1>
		<?php if ( $error ) : ?>
			<p class="rzp-alert rzp-alert--err"><?php echo esc_html( $error ); ?></p>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'rz_portal_login' ); ?>
			<input type="hidden" name="rz_portal_action" value="login">
			<label>Emel / Username<input type="text" name="log" required autocomplete="username"></label>
			<label>Katalaluan<input type="password" name="pwd" required autocomplete="current-password"></label>
			<button type="submit">Log masuk</button>
		</form>
		<p class="rzp-login__back"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Kembali ke laman</a></p>
	</main>
</body>
</html>
		<?php
	}

	private static function assets() {
		wp_enqueue_style(
			'rakanzakat-jakarta',
			'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap',
			array(),
			null
		);
		wp_enqueue_style(
			'rakanzakat-portal',
			RAKANZAKAT_URL . 'public/css/portal.css',
			array( 'rakanzakat-jakarta' ),
			RAKANZAKAT_VERSION
		);
	}

	private static function shell( $portal, $view, $nav, $title, $callback ) {
		self::assets();
		$user = wp_get_current_user();
		$ok   = ! empty( $_GET['ok'] );
		$err  = isset( $_GET['err'] ) ? sanitize_text_field( wp_unslash( $_GET['err'] ) ) : '';
		?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( $title ); ?> — Rakan Zakat</title>
	<?php wp_print_styles( array( 'rakanzakat-jakarta', 'rakanzakat-portal' ) ); ?>
</head>
<body class="rzp">
	<aside class="rzp-side">
		<div class="rzp-side__brand">Rakan Zakat</div>
		<a class="rzp-side__back" href="<?php echo esc_url( home_url( '/' ) ); ?>">← Back to site</a>
		<nav>
			<?php foreach ( $nav as $key => $item ) : ?>
				<a class="<?php echo $view === $key ? 'is-active' : ''; ?>" href="<?php echo esc_url( self::url( $portal, $key ) ); ?>">
					<?php echo $item['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php echo esc_html( $item['label'] ); ?></span>
				</a>
			<?php endforeach; ?>
		</nav>
		<a class="rzp-side__out" href="<?php echo esc_url( self::url( $portal, 'logout' ) ); ?>">Log keluar</a>
	</aside>
	<main class="rzp-main">
		<div class="rzp-top">
			<span class="rzp-avatar" aria-hidden="true"><?php echo esc_html( strtoupper( substr( $user->display_name, 0, 1 ) ) ); ?></span>
		</div>
		<?php if ( $ok ) : ?><p class="rzp-alert rzp-alert--ok">Berjaya disimpan.</p><?php endif; ?>
		<?php if ( $err ) : ?><p class="rzp-alert rzp-alert--err"><?php echo esc_html( $err ); ?></p><?php endif; ?>
		<?php call_user_func( $callback ); ?>
	</main>
	<script>
	document.addEventListener("click", function (e) {
		var btn = e.target.closest && e.target.closest(".js-rzp-copy");
		if (!btn || !navigator.clipboard) return;
		navigator.clipboard.writeText(btn.getAttribute("data-copy") || "").then(function () {
			btn.textContent = "Disalin";
			setTimeout(function () { btn.textContent = "Salin"; }, 1400);
		});
	});
	</script>
</body>
</html>
		<?php
	}

	private static function icon( $d ) {
		return '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="' . $d . '"/></svg>';
	}

	private static function admin_shell( $view ) {
		$nav = array(
			'dashboard'  => array( 'label' => 'Dashboard', 'icon' => self::icon( 'M4 10l8-6 8 6v9a1 1 0 01-1 1h-5v-6H10v6H5a1 1 0 01-1-1z' ) ),
			'kutipan'    => array( 'label' => 'Kutipan', 'icon' => self::icon( 'M12 3v18M5 8h14M5 16h14' ) ),
			'pelawat'    => array( 'label' => 'Pelawat', 'icon' => self::icon( 'M3 12h4l3 8 4-16 3 8h4' ) ),
			'iklan'      => array( 'label' => 'Iklan & ROI', 'icon' => self::icon( 'M4 19V5m0 14h16M8 15l3-4 3 3 4-6' ) ),
			'affiliates' => array( 'label' => 'Affiliates', 'icon' => self::icon( 'M16 11a4 4 0 10-8 0 4 4 0 008 0zM4 20a8 8 0 0116 0' ) ),
			'tetapan'    => array( 'label' => 'Tetapan', 'icon' => self::icon( 'M12 8a4 4 0 100 8 4 4 0 000-8zm8 4h.01M4 12h.01M12 4v.01M12 20v.01' ) ),
		);
		if ( ! isset( $nav[ $view ] ) ) {
			$view = 'dashboard';
		}
		self::shell( 'admin', $view, $nav, $nav[ $view ]['label'], array( __CLASS__, 'view_admin_' . $view ) );
	}

	private static function affiliate_shell( $view ) {
		$nav = array(
			'dashboard' => array( 'label' => 'Dashboard', 'icon' => self::icon( 'M4 10l8-6 8 6v9a1 1 0 01-1 1h-5v-6H10v6H5a1 1 0 01-1-1z' ) ),
			'urls'      => array( 'label' => 'Affiliate URLs', 'icon' => self::icon( 'M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71' ) ),
			'statistik' => array( 'label' => 'Statistics', 'icon' => self::icon( 'M4 19V5m4 14V9m4 10V7m4 12V3' ) ),
			'graphs'    => array( 'label' => 'Graphs', 'icon' => self::icon( 'M4 19V5m0 14h16M8 15l3-4 3 3 4-6' ) ),
			'referrals' => array( 'label' => 'Referrals', 'icon' => self::icon( 'M12 3v18M5 8h14M5 16h14' ) ),
			'payouts'   => array( 'label' => 'Payouts', 'icon' => self::icon( 'M12 8c-3 0-4 2-4 3s2 3 4 3 4 1 4 2-1 3-4 3m0-12v-2m0 16v-2' ) ),
			'visits'    => array( 'label' => 'Visits', 'icon' => self::icon( 'M3 12h4l3 8 4-16 3 8h4' ) ),
			'creatives' => array( 'label' => 'Creatives', 'icon' => self::icon( 'M4 7h16v10H4zM8 7V5h8v2' ) ),
		);
		if ( ! isset( $nav[ $view ] ) ) {
			$view = 'dashboard';
		}
		self::shell( 'affiliate', $view, $nav, $nav[ $view ]['label'], array( __CLASS__, 'view_aff_' . $view ) );
	}

	public static function view_admin_dashboard() {
		$range  = RakanZakat_Admin::date_range();
		$pay    = RakanZakat_Payments::summarize( $range['from'], $range['to'] );
		$visits = RakanZakat_Tracker::summarize( $range['from'], $range['to'] );
		$spend  = RakanZakat_Campaigns::spend_between( $range['from'], $range['to'] );
		$conv   = $visits['visitors'] > 0 ? round( ( $pay['paid_count'] / $visits['visitors'] ) * 100, 2 ) : 0;
		$roas   = $spend > 0 ? round( $pay['paid_sen'] / $spend, 2 ) : null;
		echo '<h1>Dashboard</h1><p class="rzp-muted">Urus kutipan, pelawat, iklan dan affiliate tanpa wp-admin.</p>';
		self::cards(
			array(
				array( 'label' => 'Kutipan berjaya', 'value' => RakanZakat_Settings::format_money( $pay['paid_sen'] ), 'meta' => $pay['paid_count'] . ' transaksi', 'href' => self::url( 'admin', 'kutipan' ), 'icon' => 'money' ),
				array( 'label' => 'Pelawat unik', 'value' => number_format_i18n( $visits['visitors'] ), 'meta' => number_format_i18n( $visits['pageviews'] ) . ' paparan', 'href' => self::url( 'admin', 'pelawat' ), 'icon' => 'visits' ),
				array( 'label' => 'Conversion', 'value' => $conv . '%', 'meta' => number_format_i18n( $visits['sessions'] ) . ' sesi', 'icon' => 'rate' ),
				array( 'label' => 'Ads spend', 'value' => RakanZakat_Settings::format_money( $spend ), 'meta' => $roas ? 'ROAS ' . $roas . 'x' : 'Masukkan spend', 'href' => self::url( 'admin', 'iklan' ), 'icon' => 'money' ),
			)
		);
	}

	public static function view_admin_kutipan() {
		$q     = RakanZakat_Payments::query_payments( array( 'page' => (int) ( $_GET['paged'] ?? 1 ), 'search' => sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ) ) );
		$types = RakanZakat_Settings::zakat_types();
		echo '<h1>Kutipan</h1>';
		echo '<form class="rzp-filter" method="get"><input type="search" name="s" value="' . esc_attr( wp_unslash( $_GET['s'] ?? '' ) ) . '" placeholder="Cari nama, emel, IC"><button>Cari</button></form>';
		echo '<div class="rzp-table-wrap"><table class="rzp-table"><thead><tr><th>Masa</th><th>Pembayar</th><th>Jenis</th><th>Amaun</th><th>Status</th></tr></thead><tbody>';
		if ( ! $q['rows'] ) {
			echo '<tr><td colspan="5">Tiada rekod.</td></tr>';
		}
		foreach ( $q['rows'] as $row ) {
			echo '<tr><td>' . esc_html( $row->created_at ) . '</td><td><strong>' . esc_html( $row->payer_name ) . '</strong><div class="rzp-muted">' . esc_html( $row->payer_email ) . ( ! empty( $row->id_number ) ? ' · ' . esc_html( $row->id_number ) : '' ) . '</div></td><td>' . esc_html( $types[ $row->zakat_type ] ?? $row->zakat_type ) . '</td><td>' . esc_html( RakanZakat_Settings::format_money( 'paid' === $row->status ? $row->paid_amount_sen : $row->amount_sen ) ) . '</td><td>' . esc_html( $row->status ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	public static function view_admin_pelawat() {
		$range  = RakanZakat_Admin::date_range();
		$visits = RakanZakat_Tracker::summarize( $range['from'], $range['to'] );
		$recent = RakanZakat_Tracker::recent( 40 );
		echo '<h1>Pelawat</h1>';
		self::cards(
			array(
				array( 'label' => 'Pelawat', 'value' => number_format_i18n( $visits['visitors'] ), 'icon' => 'visits' ),
				array( 'label' => 'Sesi', 'value' => number_format_i18n( $visits['sessions'] ), 'icon' => 'visits' ),
				array( 'label' => 'Paparan', 'value' => number_format_i18n( $visits['pageviews'] ), 'icon' => 'rate' ),
			)
		);
		echo '<div class="rzp-table-wrap"><table class="rzp-table"><thead><tr><th>Masa</th><th>Halaman</th><th>Sumber</th></tr></thead><tbody>';
		if ( ! $recent ) {
			echo '<tr><td colspan="3">Belum ada lawatan.</td></tr>';
		}
		foreach ( $recent as $row ) {
			echo '<tr><td>' . esc_html( $row->created_at ) . '</td><td>' . esc_html( $row->page_title ?: $row->page_url ) . '</td><td>' . esc_html( $row->utm_source ?: 'direct' ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	public static function view_admin_iklan() {
		$range     = RakanZakat_Admin::date_range();
		$campaigns = RakanZakat_Campaigns::all_campaigns();
		$rows      = RakanZakat_Campaigns::roi_rows( $range['from'], $range['to'] );
		echo '<h1>Iklan &amp; ROI</h1>';
		echo '<div class="rzp-grid-2"><form method="post" class="rzp-card-form">';
		wp_nonce_field( 'rz_portal_roi' );
		echo '<input type="hidden" name="rz_portal_action" value="add_campaign"><h2>Kempen baru</h2>';
		echo '<input name="name" placeholder="Nama kempen" required><input name="utm_campaign" placeholder="utm_campaign" required><input name="utm_source" placeholder="utm_source"><button>Simpan kempen</button></form>';
		echo '<form method="post" class="rzp-card-form">';
		wp_nonce_field( 'rz_portal_roi' );
		echo '<input type="hidden" name="rz_portal_action" value="add_spend"><h2>Ads spend</h2><select name="spend_campaign_id"><option value="0">Umum</option>';
		foreach ( $campaigns as $c ) {
			echo '<option value="' . esc_attr( $c->id ) . '">' . esc_html( $c->name ) . '</option>';
		}
		echo '</select><input type="date" name="spend_date" value="' . esc_attr( wp_date( 'Y-m-d' ) ) . '"><input type="number" step="0.01" name="spend_amount" placeholder="RM" required><button>Simpan spend</button></form></div>';
		echo '<div class="rzp-table-wrap"><table class="rzp-table"><thead><tr><th>Kempen</th><th>Spend</th><th>Kutipan</th><th>ROAS</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			echo '<tr><td>' . esc_html( $row['name'] ) . '</td><td>' . esc_html( RakanZakat_Settings::format_money( $row['spend'] ) ) . '</td><td>' . esc_html( RakanZakat_Settings::format_money( $row['revenue'] ) ) . '</td><td>' . ( null !== $row['roas'] ? esc_html( $row['roas'] . 'x' ) : '—' ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	public static function view_admin_affiliates() {
		$rows = RakanZakat_Affiliates::all();
		echo '<h1>Affiliates</h1><div class="rzp-grid-2"><form method="post" class="rzp-card-form">';
		wp_nonce_field( 'rz_portal_affiliate' );
		echo '<input type="hidden" name="rz_portal_action" value="add_affiliate"><h2>Tambah affiliate</h2>';
		echo '<input name="name" placeholder="Nama" required><input type="email" name="email" placeholder="Emel" required><input type="password" name="password" placeholder="Katalaluan" required><input name="code" placeholder="Kod (pilihan)"><input type="number" step="0.1" name="commission_pct" value="10" placeholder="% komisen"><button>Cipta akaun</button></form></div>';
		echo '<div class="rzp-table-wrap"><table class="rzp-table"><thead><tr><th>Nama</th><th>Kod</th><th>Link</th><th>Komisen tertunggak</th><th></th></tr></thead><tbody>';
		if ( ! $rows ) {
			echo '<tr><td colspan="5">Belum ada affiliate.</td></tr>';
		}
		foreach ( $rows as $row ) {
			$st   = RakanZakat_Affiliates::stats( $row->id );
			$link = RakanZakat_Affiliates::link( $row->code );
			echo '<tr><td><strong>' . esc_html( $row->display_name ?: $row->code ) . '</strong><div class="rzp-muted">' . esc_html( $row->user_email ) . '</div></td><td><code>' . esc_html( $row->code ) . '</code></td><td><a href="' . esc_url( $link ) . '">' . esc_html( $link ) . '</a></td><td>' . esc_html( RakanZakat_Settings::format_money( $st['unpaid_earn_sen'] ) ) . '</td><td>';
			if ( $st['unpaid_earn_sen'] > 0 ) {
				echo '<form method="post" class="rzp-inline">' . wp_nonce_field( 'rz_portal_payout', '_wpnonce', true, false ) . '<input type="hidden" name="rz_portal_action" value="payout"><input type="hidden" name="affiliate_id" value="' . esc_attr( $row->id ) . '"><button>Bayar komisen</button></form>';
			} else {
				echo '—';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	public static function view_admin_tetapan() {
		$s = RakanZakat_Settings::get_all();
		echo '<h1>Tetapan Billplz</h1><form method="post" class="rzp-card-form rzp-card-form--wide">';
		wp_nonce_field( 'rz_portal_settings' );
		echo '<input type="hidden" name="rz_portal_action" value="save_settings">';
		echo '<label>API Key<input name="api_key" value="' . esc_attr( $s['api_key'] ) . '"></label>';
		echo '<label>X-Signature<input name="x_signature" value="' . esc_attr( $s['x_signature'] ) . '"></label>';
		echo '<label>Collection ID<input name="collection_id" value="' . esc_attr( $s['collection_id'] ) . '"></label>';
		echo '<label>Amaun minimum (RM)<input type="number" step="0.01" name="min_amount" value="' . esc_attr( $s['min_amount'] ) . '"></label>';
		echo '<label class="rzp-check"><input type="checkbox" name="sandbox" value="1"' . checked( ! empty( $s['sandbox'] ), true, false ) . '> Sandbox</label>';
		echo '<button>Simpan tetapan</button></form>';
		echo '<p class="rzp-muted">Portal admin: <code>' . esc_html( self::url( 'admin' ) ) . '</code> · Affiliate: <code>' . esc_html( self::url( 'affiliate' ) ) . '</code></p>';
	}

	private static function affiliate_or_die() {
		$aff = RakanZakat_Affiliates::by_user( get_current_user_id() );
		if ( ! $aff && self::can_admin() ) {
			echo '<h1>Affiliate Area</h1><p>Akaun admin ini belum didaftarkan sebagai affiliate. Cipta affiliate di <a href="' . esc_url( self::url( 'admin', 'affiliates' ) ) . '">/admin/affiliates</a>.</p>';
			return null;
		}
		if ( ! $aff ) {
			echo '<h1>Akaun affiliate belum siap</h1><p>Sila hubungi admin.</p>';
			return null;
		}
		return $aff;
	}

	public static function view_aff_dashboard() {
		$aff = self::affiliate_or_die();
		if ( ! $aff ) {
			return;
		}
		$user  = wp_get_current_user();
		$range = RakanZakat_Admin::date_range();
		$m30   = RakanZakat_Affiliates::stats( $aff->id, $range['from'], $range['to'] );
		$span  = strtotime( $range['to'] ) - strtotime( $range['from'] );
		$prev  = RakanZakat_Affiliates::stats(
			$aff->id,
			wp_date( 'Y-m-d H:i:s', strtotime( $range['from'] ) - $span ),
			$range['from']
		);
		$all   = RakanZakat_Affiliates::stats( $aff->id );
		echo '<h1>Welcome ' . esc_html( $user->display_name ) . '</h1>';
		echo '<h2 class="rzp-h2">Last 30 days</h2>';
		self::cards(
			array(
				array( 'label' => 'Referrals', 'value' => number_format_i18n( $m30['referrals'] ), 'href' => self::url( 'affiliate', 'referrals' ), 'icon' => 'money', 'delta' => self::delta_pct( $m30['referrals'], $prev['referrals'] ) ),
				array( 'label' => 'Visits', 'value' => number_format_i18n( $m30['visits'] ), 'href' => self::url( 'affiliate', 'visits' ), 'icon' => 'visits', 'delta' => self::delta_pct( $m30['visits'], $prev['visits'] ) ),
				array( 'label' => 'Conversion Rate', 'value' => $m30['conversion'] . '%', 'icon' => 'rate' ),
			)
		);
		echo '<h2 class="rzp-h2">All-time</h2>';
		self::cards(
			array(
				array( 'label' => 'Referrals', 'value' => number_format_i18n( $all['referrals'] ), 'href' => self::url( 'affiliate', 'referrals' ), 'icon' => 'money' ),
				array( 'label' => 'Visits', 'value' => number_format_i18n( $all['visits'] ), 'href' => self::url( 'affiliate', 'visits' ), 'icon' => 'visits' ),
				array( 'label' => 'Conversion Rate', 'value' => $all['conversion'] . '%', 'icon' => 'rate' ),
				array( 'label' => 'Unpaid Referrals', 'value' => number_format_i18n( $all['unpaid_refs'] ), 'href' => self::url( 'affiliate', 'referrals' ), 'icon' => 'visits' ),
				array( 'label' => 'Paid Referrals', 'value' => number_format_i18n( $all['paid_refs'] ), 'icon' => 'money' ),
				array( 'label' => 'Unpaid Earnings', 'value' => RakanZakat_Settings::format_money( $all['unpaid_earn_sen'] ), 'icon' => 'money' ),
				array( 'label' => 'Total Earnings', 'value' => RakanZakat_Settings::format_money( $all['total_earn_sen'] ), 'icon' => 'money' ),
			)
		);
	}

	public static function view_aff_urls() {
		$aff = self::affiliate_or_die();
		if ( ! $aff ) {
			return;
		}
		$link = RakanZakat_Affiliates::link( $aff->code );
		echo '<h1>Affiliate URLs</h1><div class="rzp-card-form"><p>Kongsi link ini. Setiap bayaran melalui link akan dikira sebagai referral.</p>';
		echo '<div class="rzp-copy"><input id="rzp-link" type="text" readonly value="' . esc_attr( $link ) . '"><button type="button" class="js-rzp-copy" data-copy="' . esc_attr( $link ) . '">Salin</button></div>';
		echo '<p class="rzp-muted">Kod: <strong>' . esc_html( $aff->code ) . '</strong> · Komisen ' . esc_html( number_format( $aff->commission_bp / 100, 1 ) ) . '%</p></div>';
	}

	public static function view_aff_graphs() {
		$aff = self::affiliate_or_die();
		if ( ! $aff ) {
			return;
		}
		$range = RakanZakat_Admin::date_range();
		$rows  = RakanZakat_Affiliates::daily( $aff->id, $range['from'], $range['to'] );
		$max   = 1;
		foreach ( $rows as $row ) {
			$max = max( $max, (int) $row['visits'], (int) $row['referrals'] );
		}
		echo '<h1>Graphs</h1><p class="rzp-muted">Lawatan dan referral 30 hari terakhir.</p><div class="rzp-chart">';
		foreach ( $rows as $row ) {
			$vh = max( 4, round( ( $row['visits'] / $max ) * 100 ) );
			$rh = max( 4, round( ( $row['referrals'] / $max ) * 100 ) );
			echo '<div class="rzp-chart__col" title="' . esc_attr( $row['date'] . ': ' . $row['visits'] . ' visits, ' . $row['referrals'] . ' referrals' ) . '">';
			echo '<span class="rzp-chart__bar rzp-chart__bar--v" style="height:' . esc_attr( $vh ) . '%"></span>';
			echo '<span class="rzp-chart__bar rzp-chart__bar--r" style="height:' . esc_attr( $rh ) . '%"></span>';
			echo '</div>';
		}
		echo '</div><p class="rzp-legend"><span class="rzp-dot rzp-dot--v"></span> Visits <span class="rzp-dot rzp-dot--r"></span> Referrals</p>';
	}

	public static function view_aff_statistik() {
		$aff = self::affiliate_or_die();
		if ( ! $aff ) {
			return;
		}
		$all = RakanZakat_Affiliates::stats( $aff->id );
		echo '<h1>Statistics</h1>';
		self::cards(
			array(
				array( 'Visits', number_format_i18n( $all['visits'] ), '' ),
				array( 'Referrals', number_format_i18n( $all['referrals'] ), '' ),
				array( 'Conversion', $all['conversion'] . '%', '' ),
				array( 'Kutipan dirujuk', RakanZakat_Settings::format_money( $all['revenue_sen'] ), '' ),
			)
		);
	}

	public static function view_aff_referrals() {
		$aff = self::affiliate_or_die();
		if ( ! $aff ) {
			return;
		}
		$rows = RakanZakat_Affiliates::referrals( $aff->id );
		echo '<h1>Referrals</h1><div class="rzp-table-wrap"><table class="rzp-table"><thead><tr><th>Tarikh</th><th>Pembayar</th><th>Amaun</th><th>Komisen</th><th>Payout</th></tr></thead><tbody>';
		if ( ! $rows ) {
			echo '<tr><td colspan="5">Belum ada referral.</td></tr>';
		}
		foreach ( $rows as $row ) {
			$kom = RakanZakat_Affiliates::commission_sen( $row->paid_amount_sen ?: $row->amount_sen, $aff->commission_bp );
			echo '<tr><td>' . esc_html( $row->paid_at ?: $row->created_at ) . '</td><td>' . esc_html( $row->payer_name ) . '</td><td>' . esc_html( RakanZakat_Settings::format_money( $row->paid_amount_sen ?: $row->amount_sen ) ) . '</td><td>' . esc_html( RakanZakat_Settings::format_money( $kom ) ) . '</td><td>' . ( $row->payout_id ? 'Paid' : 'Unpaid' ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	public static function view_aff_payouts() {
		$aff = self::affiliate_or_die();
		if ( ! $aff ) {
			return;
		}
		$rows = RakanZakat_Affiliates::payouts( $aff->id );
		echo '<h1>Payouts</h1><div class="rzp-table-wrap"><table class="rzp-table"><thead><tr><th>Tarikh</th><th>Amaun</th><th>Status</th></tr></thead><tbody>';
		if ( ! $rows ) {
			echo '<tr><td colspan="3">Belum ada payout.</td></tr>';
		}
		foreach ( $rows as $row ) {
			echo '<tr><td>' . esc_html( $row->paid_at ?: $row->created_at ) . '</td><td>' . esc_html( RakanZakat_Settings::format_money( $row->amount_sen ) ) . '</td><td>' . esc_html( $row->status ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	public static function view_aff_visits() {
		$aff = self::affiliate_or_die();
		if ( ! $aff ) {
			return;
		}
		$rows = RakanZakat_Affiliates::visits( $aff->id );
		echo '<h1>Visits</h1><div class="rzp-table-wrap"><table class="rzp-table"><thead><tr><th>Masa</th><th>Halaman</th><th>UTM</th></tr></thead><tbody>';
		if ( ! $rows ) {
			echo '<tr><td colspan="3">Belum ada lawatan.</td></tr>';
		}
		foreach ( $rows as $row ) {
			echo '<tr><td>' . esc_html( $row->created_at ) . '</td><td>' . esc_html( $row->page_title ?: $row->page_url ) . '</td><td>' . esc_html( $row->utm_source ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	public static function view_aff_creatives() {
		$aff = self::affiliate_or_die();
		if ( ! $aff ) {
			return;
		}
		$link = RakanZakat_Affiliates::link( $aff->code );
		echo '<h1>Creatives</h1><div class="rzp-card-form"><p>Contoh teks untuk dikongsi:</p>';
		echo '<textarea readonly rows="4">Tunaikan zakat melalui saluran rasmi Rakan Zakat. ' . esc_textarea( $link ) . '</textarea></div>';
	}

	private static function delta_pct( $now, $prev ) {
		$now  = (float) $now;
		$prev = (float) $prev;
		if ( $prev > 0 ) {
			return (int) round( ( ( $now - $prev ) / $prev ) * 100 );
		}
		return $now > 0 ? 100 : 0;
	}

	private static function card_icon( $type ) {
		$map = array(
			'money'  => 'M12 8c-3 0-4 2-4 3s2 3 4 3 4 1 4 2-1 3-4 3m0-12v-2m0 16v-2',
			'visits' => 'M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5z',
			'rate'   => 'M7 10h10M12 5v14M5 8h2m10 0h2M5 16h2m10 0h2',
		);
		$d = $map[ $type ] ?? $map['money'];
		return '<span class="rzp-card__icon">' . self::icon( $d ) . '</span>';
	}

	private static function cards( $items ) {
		echo '<div class="rzp-cards">';
		foreach ( $items as $item ) {
			if ( isset( $item['label'] ) ) {
				$label = $item['label'];
				$value = $item['value'];
				$meta  = $item['meta'] ?? '';
				$href  = $item['href'] ?? '';
				$icon  = $item['icon'] ?? 'money';
				$delta = $item['delta'] ?? null;
			} else {
				$label = $item[0];
				$value = $item[1];
				$meta  = $item[2] ?? '';
				$href  = $item[3] ?? '';
				$icon  = 'money';
				$delta = $item[4] ?? null;
			}
			echo '<article class="rzp-card">';
			echo self::card_icon( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<p>' . esc_html( $label ) . '</p><strong>' . esc_html( $value );
			if ( null !== $delta && 0 !== (int) $delta ) {
				$cls = (int) $delta >= 0 ? 'is-up' : 'is-down';
				echo ' <em class="rzp-delta ' . esc_attr( $cls ) . '">' . ( (int) $delta >= 0 ? '↑' : '↓' ) . esc_html( number_format_i18n( abs( (int) $delta ) ) ) . '%</em>';
			}
			echo '</strong>';
			if ( $meta ) {
				echo '<span>' . esc_html( $meta ) . '</span>';
			}
			if ( $href ) {
				echo '<a href="' . esc_url( $href ) . '">View all</a>';
			}
			echo '</article>';
		}
		echo '</div>';
	}
}
