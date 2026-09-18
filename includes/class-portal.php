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
		global $pagenow;
		if ( 'admin-post.php' === $pagenow ) {
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
					'remember'      => ! empty( $_POST['remember'] ),
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
			$tab = sanitize_key( wp_unslash( $_POST['tab'] ?? 'jenama' ) );
			RakanZakat_Settings::update( self::settings_from_post( $tab ) );
			wp_safe_redirect( add_query_arg( array( 'ok' => '1', 'tab' => $tab ), self::url( 'admin', 'tetapan' ) ) );
			exit;
		}

		if ( 'reset_brand' === $action && self::can_admin() ) {
			check_admin_referer( 'rz_portal_settings' );
			RakanZakat_Settings::update(
				array(
					'brand_name'         => 'Rakan Zakat',
					'brand_tagline'      => 'Saluran rasmi bayar zakat',
					'brand_badge'        => 'LZS (PA 2928)',
					'brand_footer'       => 'Rakan rasmi Lembaga Zakat Selangor',
					'sidebar_color'      => '#0A2540',
					'primary_color'      => '#0B5EDA',
					'accent_color'       => '#FEE506',
					'bg_color'           => '#FAF8FF',
					'show_partner_badge' => 1,
				)
			);
			wp_safe_redirect( add_query_arg( array( 'ok' => '1', 'tab' => 'jenama' ), self::url( 'admin', 'tetapan' ) ) );
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

	private static function s( $key, $default = '' ) {
		$v = RakanZakat_Settings::get( $key, $default );
		return '' === $v || null === $v ? $default : $v;
	}

	private static function logo_url() {
		$id = (int) self::s( 'brand_logo_id', 0 );
		if ( $id ) {
			$url = wp_get_attachment_image_url( $id, 'medium' );
			if ( $url ) {
				return $url;
			}
		}
		return RAKANZAKAT_URL . 'public/img/logo.png';
	}

	private static function hex( $value, $fallback ) {
		$value = ltrim( (string) $value, '#' );
		return preg_match( '/^[0-9a-fA-F]{6}$/', $value ) ? '#' . $value : $fallback;
	}

	private static function mat( $name ) {
		return '<span class="material-symbols-outlined">' . esc_html( $name ) . '</span>';
	}

	private static function brand_style() {
		$navy  = self::hex( self::s( 'sidebar_color', '#0A2540' ), '#0A2540' );
		$blue  = self::hex( self::s( 'primary_color', '#0B5EDA' ), '#0B5EDA' );
		$gold  = self::hex( self::s( 'accent_color', '#FEE506' ), '#FEE506' );
		$bg    = self::hex( self::s( 'bg_color', '#FAF8FF' ), '#FAF8FF' );
		return '--rzp-navy:' . $navy . ';--rzp-blue:' . $blue . ';--rzp-gold:' . $gold . ';--rzp-bg:' . $bg . ';';
	}

	private static function settings_from_post( $tab ) {
		$p = wp_unslash( $_POST );
		if ( 'billplz' === $tab ) {
			return array(
				'api_key'       => sanitize_text_field( $p['api_key'] ?? '' ),
				'x_signature'   => sanitize_text_field( $p['x_signature'] ?? '' ),
				'collection_id' => sanitize_text_field( $p['collection_id'] ?? '' ),
				'sandbox'       => empty( $p['sandbox'] ) ? 0 : 1,
			);
		}
		if ( 'pembayaran' === $tab ) {
			return array(
				'min_amount'     => (float) ( $p['min_amount'] ?? 10 ),
				'thankyou_page'  => (int) ( $p['thankyou_page'] ?? 0 ),
				'failed_page'    => (int) ( $p['failed_page'] ?? 0 ),
				'amount_presets' => sanitize_text_field( $p['amount_presets'] ?? '' ),
			);
		}
		if ( 'affiliate' === $tab ) {
			return array(
				'default_commission_pct' => (float) ( $p['default_commission_pct'] ?? 10 ),
				'cookie_days'            => (int) ( $p['cookie_days'] ?? 30 ),
				'creative_text'          => sanitize_textarea_field( $p['creative_text'] ?? '' ),
			);
		}
		if ( 'tracking' === $tab ) {
			return array( 'track_admins' => empty( $p['track_admins'] ) ? 0 : 1 );
		}
		if ( 'notifikasi' === $tab ) {
			return array(
				'notify_receipt' => empty( $p['notify_receipt'] ) ? 0 : 1,
				'notify_admin'   => empty( $p['notify_admin'] ) ? 0 : 1,
				'notify_emails'  => sanitize_textarea_field( $p['notify_emails'] ?? '' ),
			);
		}
		$data = array(
			'brand_name'         => sanitize_text_field( $p['brand_name'] ?? 'Rakan Zakat' ),
			'brand_tagline'      => sanitize_text_field( $p['brand_tagline'] ?? '' ),
			'brand_badge'        => sanitize_text_field( $p['brand_badge'] ?? '' ),
			'brand_footer'       => sanitize_text_field( $p['brand_footer'] ?? '' ),
			'support_email'      => sanitize_email( $p['support_email'] ?? '' ),
			'support_phone'      => sanitize_text_field( $p['support_phone'] ?? '' ),
			'sidebar_color'      => self::hex( $p['sidebar_color'] ?? '', '#0A2540' ),
			'primary_color'      => self::hex( $p['primary_color'] ?? '', '#0B5EDA' ),
			'accent_color'       => self::hex( $p['accent_color'] ?? '', '#FEE506' ),
			'bg_color'           => self::hex( $p['bg_color'] ?? '', '#FAF8FF' ),
			'show_partner_badge' => empty( $p['show_partner_badge'] ) ? 0 : 1,
		);
		if ( ! empty( $_FILES['brand_logo']['name'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			$id = media_handle_upload( 'brand_logo', 0 );
			if ( ! is_wp_error( $id ) ) {
				$data['brand_logo_id'] = (int) $id;
			}
		}
		return $data;
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
	<?php wp_print_styles( array( 'rakanzakat-jakarta', 'rakanzakat-icons', 'rakanzakat-portal' ) ); ?>
</head>
<body class="rzp-login-body" style="<?php echo esc_attr( self::brand_style() ); ?>">
	<main class="rzp-login">
		<div class="rzp-login__brand">
			<img src="<?php echo esc_url( self::logo_url() ); ?>" alt="<?php echo esc_attr( self::s( 'brand_name', 'Rakan Zakat' ) ); ?>">
			<h1><?php echo esc_html( $title ); ?></h1>
			<p class="rzp-muted"><?php echo esc_html( self::s( 'brand_tagline', 'Log masuk untuk teruskan operasi sistem' ) ); ?></p>
		</div>
		<?php if ( $error ) : ?>
			<p class="rzp-alert rzp-alert--err"><?php echo self::mat( 'error' ); ?> <?php echo esc_html( $error ); ?></p>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'rz_portal_login' ); ?>
			<input type="hidden" name="rz_portal_action" value="login">
			<label>Emel / Username<input type="text" name="log" required autocomplete="username" placeholder="admin@rakanzakat.com"></label>
			<label>Katalaluan
				<div class="rzp-pw">
					<input id="rzp-pwd" type="password" name="pwd" required autocomplete="current-password">
					<button type="button" class="js-rzp-eye" aria-label="Tunjuk katalaluan"><?php echo self::mat( 'visibility' ); ?></button>
				</div>
			</label>
			<div class="rzp-login__row">
				<label class="rzp-check" style="display:flex;gap:8px;font-weight:500"><input type="checkbox" name="remember" value="1" checked> Ingat saya</label>
				<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>">Lupa katalaluan?</a>
			</div>
			<button type="submit"><?php echo self::mat( 'lock_open' ); ?> Log masuk</button>
		</form>
		<div class="rzp-login__back">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo self::mat( 'arrow_back' ); ?> Kembali ke laman utama</a>
			<div class="rzp-trust"><?php echo self::mat( 'verified_user' ); ?> <?php echo esc_html( self::s( 'brand_footer', 'Saluran Rasmi Lembaga Zakat Selangor' ) ); ?></div>
		</div>
	</main>
	<?php self::portal_js(); ?>
</body>
</html>
		<?php
	}

	private static function assets() {
		wp_enqueue_style( 'rakanzakat-jakarta', 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap', array(), null );
		wp_enqueue_style( 'rakanzakat-icons', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200', array(), null );
		wp_enqueue_style( 'rakanzakat-portal', RAKANZAKAT_URL . 'public/css/portal.css', array( 'rakanzakat-jakarta', 'rakanzakat-icons' ), RAKANZAKAT_VERSION );
	}

	private static function admin_nav() {
		return array(
			'dashboard'  => array( 'label' => 'Dashboard', 'icon' => 'dashboard' ),
			'kutipan'    => array( 'label' => 'Kutipan', 'icon' => 'payments' ),
			'pelawat'    => array( 'label' => 'Pelawat', 'icon' => 'group' ),
			'iklan'      => array( 'label' => 'Iklan & ROI', 'icon' => 'ads_click' ),
			'affiliates' => array( 'label' => 'Affiliates', 'icon' => 'badge' ),
			'tetapan'    => array( 'label' => 'Tetapan', 'icon' => 'settings' ),
		);
	}

	private static function affiliate_nav() {
		return array(
			'dashboard' => array( 'label' => 'Dashboard', 'icon' => 'monitoring' ),
			'urls'      => array( 'label' => 'Pautan', 'icon' => 'link' ),
			'statistik' => array( 'label' => 'Statistik', 'icon' => 'analytics' ),
			'graphs'    => array( 'label' => 'Graf', 'icon' => 'ssid_chart' ),
			'referrals' => array( 'label' => 'Rujukan', 'icon' => 'handshake' ),
			'payouts'   => array( 'label' => 'Bayaran', 'icon' => 'account_balance_wallet' ),
			'visits'    => array( 'label' => 'Lawatan', 'icon' => 'traffic' ),
			'creatives' => array( 'label' => 'Creative', 'icon' => 'photo_library' ),
		);
	}

	private static function render_nav( $portal, $view, $items ) {
		foreach ( $items as $key => $item ) {
			$href = self::url( $portal, $key );
			echo '<a class="' . ( $view === $key ? 'is-active' : '' ) . '" href="' . esc_url( $href ) . '">' . self::mat( $item['icon'] ) . '<span>' . esc_html( $item['label'] ) . '</span></a>';
		}
	}

	private static function shell( $portal, $view, $title, $callback ) {
		self::assets();
		$user   = wp_get_current_user();
		$ok     = ! empty( $_GET['ok'] );
		$err    = isset( $_GET['err'] ) ? sanitize_text_field( wp_unslash( $_GET['err'] ) ) : '';
		$is_adm = 'admin' === $portal;
		?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( $title ); ?> — <?php echo esc_html( self::s( 'brand_name', 'Rakan Zakat' ) ); ?></title>
	<?php wp_print_styles( array( 'rakanzakat-jakarta', 'rakanzakat-icons', 'rakanzakat-portal' ) ); ?>
</head>
<body class="rzp" style="<?php echo esc_attr( self::brand_style() ); ?>">
	<aside class="rzp-side">
		<div class="rzp-side__head">
			<div class="rzp-side__logo"><img src="<?php echo esc_url( self::logo_url() ); ?>" alt=""></div>
			<?php if ( self::s( 'show_partner_badge', 1 ) ) : ?>
				<div class="rzp-badge"><span><?php echo self::mat( 'verified' ); ?> <?php echo esc_html( self::s( 'brand_badge', 'LZS (PA 2928)' ) ); ?></span><span class="rzp-dot rzp-dot--gold"></span></div>
			<?php endif; ?>
			<a class="rzp-side__back" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo self::mat( 'arrow_back' ); ?> Laman utama</a>
		</div>
		<?php if ( $is_adm || self::can_admin() ) : ?>
			<div class="rzp-nav-wrap">
				<span class="rzp-nav-label">Mod Pentadbir</span>
				<nav><?php self::render_nav( 'admin', $is_adm ? $view : '', self::admin_nav() ); ?></nav>
			</div>
		<?php endif; ?>
		<div class="rzp-nav-wrap">
			<span class="rzp-nav-label">Portal Affiliate</span>
			<nav><?php self::render_nav( 'affiliate', $is_adm ? '' : $view, self::affiliate_nav() ); ?></nav>
		</div>
		<div class="rzp-userbox">
			<div class="rzp-userbox__row">
				<div class="rzp-avatar"><?php echo esc_html( strtoupper( substr( $user->display_name, 0, 1 ) ) ); ?></div>
				<div>
					<strong><?php echo esc_html( $user->display_name ); ?></strong>
					<span><?php echo $is_adm ? 'Amil Bertauliah' : 'Affiliate'; ?></span>
				</div>
			</div>
			<a class="rzp-side__out" href="<?php echo esc_url( self::url( $portal, 'logout' ) ); ?>"><?php echo self::mat( 'logout' ); ?> Log keluar</a>
		</div>
	</aside>
	<div class="rzp-frame">
		<header class="rzp-topbar">
			<div class="rzp-pill"><span class="rzp-dot"></span> Sistem Operasi Agihan &amp; Kutipan Aktif</div>
			<div class="rzp-topbar__right">
				<div class="rzp-pill"><?php echo self::mat( 'calendar_today' ); ?> <?php echo esc_html( wp_date( 'Y' ) ); ?> Operasi Bersepadu</div>
				<div class="rzp-top-avatar"><?php echo self::mat( 'person' ); ?></div>
			</div>
		</header>
		<main class="rzp-main">
			<?php if ( $ok ) : ?><p class="rzp-alert rzp-alert--ok">Berjaya disimpan.</p><?php endif; ?>
			<?php if ( $err ) : ?><p class="rzp-alert rzp-alert--err"><?php echo esc_html( $err ); ?></p><?php endif; ?>
			<?php call_user_func( $callback ); ?>
		</main>
	</div>
	<?php self::portal_js(); ?>
</body>
</html>
		<?php
	}

	private static function portal_js() {
		?>
<script>
document.addEventListener("click", function (e) {
	var copy = e.target.closest && e.target.closest(".js-rzp-copy");
	if (copy && navigator.clipboard) {
		navigator.clipboard.writeText(copy.getAttribute("data-copy") || "");
		var old = copy.textContent;
		copy.textContent = "Disalin";
		setTimeout(function () { copy.textContent = old; }, 1400);
	}
	var eye = e.target.closest && e.target.closest(".js-rzp-eye");
	if (eye) {
		var input = document.getElementById("rzp-pwd") || eye.parentNode.querySelector("input");
		if (input) input.type = input.type === "password" ? "text" : "password";
	}
	var pay = e.target.closest && e.target.closest(".js-rzp-pay");
	if (pay) {
		var m = document.getElementById("rzp-payout");
		if (!m) return;
		m.querySelector("[name='affiliate_id']").value = pay.getAttribute("data-id");
		document.getElementById("rzp-pay-name").textContent = pay.getAttribute("data-name") || "";
		document.getElementById("rzp-pay-amt").textContent = pay.getAttribute("data-amt") || "";
		m.classList.add("is-open");
	}
	if (e.target.closest && e.target.closest(".js-rzp-close")) {
		var modal = document.getElementById("rzp-payout");
		if (modal) modal.classList.remove("is-open");
	}
});
</script>
		<?php
	}

	private static function admin_shell( $view ) {
		$nav = self::admin_nav();
		if ( ! isset( $nav[ $view ] ) ) {
			$view = 'dashboard';
		}
		self::shell( 'admin', $view, $nav[ $view ]['label'], array( __CLASS__, 'view_admin_' . $view ) );
	}

	private static function affiliate_shell( $view ) {
		$nav = self::affiliate_nav();
		if ( ! isset( $nav[ $view ] ) ) {
			$view = 'dashboard';
		}
		self::shell( 'affiliate', $view, $nav[ $view ]['label'], array( __CLASS__, 'view_aff_' . $view ) );
	}

	private static function page_head( $title, $lead = '', $kicker = '', $with_range = false ) {
		echo '<div class="rzp-head"><div>';
		if ( $kicker ) {
			echo '<div class="rzp-kicker">' . self::mat( 'verified' ) . ' ' . esc_html( $kicker ) . '</div>';
		}
		echo '<h1>' . esc_html( $title ) . '</h1>';
		if ( $lead ) {
			echo '<p class="rzp-lead">' . esc_html( $lead ) . '</p>';
		}
		echo '</div>';
		if ( $with_range ) {
			$preset = RakanZakat_Admin::date_range()['preset'];
			echo '<nav class="rzp-range">';
			foreach ( array( 'today' => 'Hari ini', '7d' => '7 hari', '30d' => '30 hari', 'month' => 'Bulan ini' ) as $key => $label ) {
				echo '<a class="' . ( $preset === $key ? 'is-active' : '' ) . '" href="' . esc_url( add_query_arg( 'range', $key ) ) . '">' . esc_html( $label ) . '</a>';
			}
			echo '</nav>';
		}
		echo '</div>';
	}

	private static function status_chip( $status ) {
		$map = array(
			'paid'    => array( 'Berjaya', '' ),
			'pending' => array( 'Menunggu', 'is-pending' ),
			'failed'  => array( 'Gagal', 'is-failed' ),
		);
		$item = $map[ $status ] ?? array( ucfirst( (string) $status ), 'is-pending' );
		return '<span class="rzp-status ' . esc_attr( $item[1] ) . '">' . esc_html( $item[0] ) . '</span>';
	}

	public static function view_admin_dashboard() {
		$range  = RakanZakat_Admin::date_range();
		$pay    = RakanZakat_Payments::summarize( $range['from'], $range['to'] );
		$visits = RakanZakat_Tracker::summarize( $range['from'], $range['to'] );
		$spend  = RakanZakat_Campaigns::spend_between( $range['from'], $range['to'] );
		$conv   = $visits['visitors'] > 0 ? round( ( $pay['paid_count'] / $visits['visitors'] ) * 100, 2 ) : 0;
		$roas   = $spend > 0 ? round( $pay['paid_sen'] / $spend, 2 ) : null;
		$recent = RakanZakat_Payments::query_payments( array( 'page' => 1 ) );
		$types  = RakanZakat_Settings::zakat_types();
		$srcs   = RakanZakat_Tracker::sources( $range['from'], $range['to'] );
		$total_v = max( 1, (int) $visits['visitors'] );
		self::page_head( 'Dashboard', 'Kutipan, pelawat dan ROI — 30 hari terakhir', 'Ringkasan Operasi', true );
		self::cards(
			array(
				array( 'label' => 'Kutipan berjaya', 'value' => RakanZakat_Settings::format_money( $pay['paid_sen'] ), 'meta' => $pay['paid_count'] . ' transaksi sah', 'href' => self::url( 'admin', 'kutipan' ), 'icon' => 'payments', 'tag' => 'Real-time' ),
				array( 'label' => 'Pelawat unik', 'value' => number_format_i18n( $visits['visitors'] ), 'meta' => number_format_i18n( $visits['pageviews'] ) . ' paparan halaman', 'href' => self::url( 'admin', 'pelawat' ), 'icon' => 'group' ),
				array( 'label' => 'Kadar Penukaran', 'value' => $conv . '%', 'meta' => number_format_i18n( $visits['sessions'] ) . ' sesi aktif', 'icon' => 'conversion_path' ),
				array( 'label' => 'Ads spend', 'value' => RakanZakat_Settings::format_money( $spend ), 'meta' => $roas ? 'ROAS ' . $roas . 'x' : 'Masukkan spend', 'href' => self::url( 'admin', 'iklan' ), 'icon' => 'campaign', 'tag' => $roas ? 'ROAS ' . $roas . 'x' : '', 'tag_gold' => true ),
			)
		);
		echo '<div class="rzp-split"><div class="rzp-card"><div class="rzp-card__title"><span class="rzp-card__bar"></span><div><h2>Kutipan terkini</h2><p>Transaksi melalui gerbang rasmi</p></div></div>';
		echo '<div class="rzp-table-wrap"><table class="rzp-table"><thead><tr><th>Masa</th><th>Pembayar</th><th>Jenis Zakat</th><th>Amaun</th><th>Status</th></tr></thead><tbody>';
		$rows = array_slice( (array) $recent['rows'], 0, 5 );
		if ( ! $rows ) {
			echo '<tr><td colspan="5">Belum ada kutipan dalam tempoh ini.</td></tr>';
		}
		foreach ( $rows as $row ) {
			echo '<tr><td>' . esc_html( $row->created_at ) . '</td><td class="rzp-name">' . esc_html( $row->payer_name ) . '<small>' . esc_html( $row->payer_email ) . '</small></td><td><span class="rzp-type">' . esc_html( $types[ $row->zakat_type ] ?? $row->zakat_type ) . '</span></td><td><strong>' . esc_html( RakanZakat_Settings::format_money( 'paid' === $row->status ? $row->paid_amount_sen : $row->amount_sen ) ) . '</strong></td><td>' . self::status_chip( $row->status ) . '</td></tr>';
		}
		echo '</tbody></table></div><a class="rzp-more" href="' . esc_url( self::url( 'admin', 'kutipan' ) ) . '">Kutipan Penuh</a></div>';
		echo '<div class="rzp-card"><div class="rzp-card__title"><span class="rzp-card__bar rzp-card__bar--gold"></span><div><h2>Sumber Pelawat</h2><p>Pengagihan saluran trafik</p></div></div>';
		if ( ! $srcs ) {
			echo '<p class="rzp-muted">Belum ada data sumber.</p>';
		}
		$i = 0;
		foreach ( (array) $srcs as $src ) {
			$pct = round( ( (int) $src->visitors / $total_v ) * 100 );
			$cls = 0 === $i ? '' : ( 1 === $i ? 'is-gold' : ( 2 === $i ? 'is-mid' : 'is-navy' ) );
			echo '<div class="rzp-source"><div class="rzp-source__row"><span>' . esc_html( $src->source ) . '</span><span>' . esc_html( $pct ) . '% <span class="rzp-muted">(' . esc_html( number_format_i18n( $src->visitors ) ) . ')</span></span></div><div class="rzp-track"><span class="' . esc_attr( $cls ) . '" style="width:' . esc_attr( $pct ) . '%"></span></div></div>';
			++$i;
		}
		echo '</div></div>';
	}

	public static function view_admin_kutipan() {
		$q     = RakanZakat_Payments::query_payments( array( 'page' => (int) ( $_GET['paged'] ?? 1 ), 'search' => sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ) ) );
		$types = RakanZakat_Settings::zakat_types();
		self::page_head( 'Kutipan', 'Senarai rekod transaksi bayaran zakat melalui saluran rasmi.', 'Kutipan Berpusat' );
		echo '<form class="rzp-filter" method="get" action="' . esc_url( self::url( 'admin', 'kutipan' ) ) . '"><input type="search" name="s" value="' . esc_attr( wp_unslash( $_GET['s'] ?? '' ) ) . '" placeholder="Cari nama, emel, IC"><button class="rzp-btn rzp-btn--blue" type="submit">Cari</button><a class="rzp-btn rzp-btn--ghost" href="' . esc_url( admin_url( 'admin-post.php?action=rakanzakat_export' ) ) . '">Eksport CSV</a></form>';
		echo '<div class="rzp-card"><table class="rzp-table"><thead><tr><th>Masa</th><th>Pembayar</th><th>Jenis</th><th>Amaun</th><th>Status</th></tr></thead><tbody>';
		if ( ! $q['rows'] ) {
			echo '<tr><td colspan="5">Tiada rekod.</td></tr>';
		}
		foreach ( $q['rows'] as $row ) {
			echo '<tr><td>' . esc_html( $row->created_at ) . '</td><td class="rzp-name">' . esc_html( $row->payer_name ) . '<small>' . esc_html( $row->payer_email ) . ( ! empty( $row->id_number ) ? ' · ' . esc_html( $row->id_number ) : '' ) . '</small></td><td><span class="rzp-type">' . esc_html( $types[ $row->zakat_type ] ?? $row->zakat_type ) . '</span></td><td><strong>' . esc_html( RakanZakat_Settings::format_money( 'paid' === $row->status ? $row->paid_amount_sen : $row->amount_sen ) ) . '</strong></td><td>' . self::status_chip( $row->status ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	public static function view_admin_pelawat() {
		$range  = RakanZakat_Admin::date_range();
		$visits = RakanZakat_Tracker::summarize( $range['from'], $range['to'] );
		$recent = RakanZakat_Tracker::recent( 40 );
		$srcs   = RakanZakat_Tracker::sources( $range['from'], $range['to'] );
		$total_v = max( 1, (int) $visits['visitors'] );
		self::page_head( 'Pelawat', 'Sumber trafik, sesi dan paparan halaman.', 'Analitik Trafik', true );
		self::cards(
			array(
				array( 'label' => 'Pelawat unik', 'value' => number_format_i18n( $visits['visitors'] ), 'icon' => 'group' ),
				array( 'label' => 'Sesi', 'value' => number_format_i18n( $visits['sessions'] ), 'icon' => 'monitoring' ),
				array( 'label' => 'Paparan', 'value' => number_format_i18n( $visits['pageviews'] ), 'icon' => 'visibility' ),
			)
		);
		echo '<div class="rzp-split"><div class="rzp-card"><div class="rzp-card__title"><span class="rzp-card__bar"></span><div><h2>Lawatan terkini</h2><p>40 rekod terakhir</p></div></div>';
		echo '<div class="rzp-table-wrap"><table class="rzp-table"><thead><tr><th>Masa</th><th>Halaman</th><th>Sumber</th></tr></thead><tbody>';
		if ( ! $recent ) {
			echo '<tr><td colspan="3">Belum ada lawatan.</td></tr>';
		}
		foreach ( $recent as $row ) {
			echo '<tr><td>' . esc_html( $row->created_at ) . '</td><td class="rzp-name">' . esc_html( $row->page_title ?: $row->page_url ) . '</td><td>' . esc_html( $row->utm_source ?: 'direct' ) . '</td></tr>';
		}
		echo '</tbody></table></div></div>';
		echo '<div class="rzp-card"><div class="rzp-card__title"><span class="rzp-card__bar rzp-card__bar--gold"></span><div><h2>Sumber</h2><p>Pengagihan saluran</p></div></div>';
		if ( ! $srcs ) {
			echo '<p class="rzp-muted">Belum ada data sumber.</p>';
		}
		$i = 0;
		foreach ( (array) $srcs as $src ) {
			$pct = round( ( (int) $src->visitors / $total_v ) * 100 );
			$cls = 0 === $i ? '' : ( 1 === $i ? 'is-gold' : ( 2 === $i ? 'is-mid' : 'is-navy' ) );
			echo '<div class="rzp-source"><div class="rzp-source__row"><span>' . esc_html( $src->source ) . '</span><span>' . esc_html( $pct ) . '%</span></div><div class="rzp-track"><span class="' . esc_attr( $cls ) . '" style="width:' . esc_attr( $pct ) . '%"></span></div></div>';
			++$i;
		}
		echo '</div></div>';
	}

	public static function view_admin_iklan() {
		$range     = RakanZakat_Admin::date_range();
		$campaigns = RakanZakat_Campaigns::all_campaigns();
		$rows      = RakanZakat_Campaigns::roi_rows( $range['from'], $range['to'] );
		self::page_head( 'Iklan & ROI', 'Kempen, ads spend dan pulangan kutipan.', 'Pengurusan Iklan', true );
		echo '<div class="rzp-grid-2"><form method="post" class="rzp-card rzp-card-form">';
		wp_nonce_field( 'rz_portal_roi' );
		echo '<input type="hidden" name="rz_portal_action" value="add_campaign"><h2>Kempen baru</h2>';
		echo '<label>Nama<input name="name" placeholder="Nama kempen" required></label>';
		echo '<label>utm_campaign<input name="utm_campaign" required></label>';
		echo '<label>utm_source<input name="utm_source"></label>';
		echo '<button class="rzp-btn rzp-btn--gold" type="submit">Simpan kempen</button></form>';
		echo '<form method="post" class="rzp-card rzp-card-form">';
		wp_nonce_field( 'rz_portal_roi' );
		echo '<input type="hidden" name="rz_portal_action" value="add_spend"><h2>Ads spend</h2>';
		echo '<label>Kempen<select name="spend_campaign_id"><option value="0">Umum</option>';
		foreach ( $campaigns as $c ) {
			echo '<option value="' . esc_attr( $c->id ) . '">' . esc_html( $c->name ) . '</option>';
		}
		echo '</select></label><label>Tarikh<input type="date" name="spend_date" value="' . esc_attr( wp_date( 'Y-m-d' ) ) . '"></label>';
		echo '<label>Amaun (RM)<input type="number" step="0.01" name="spend_amount" required></label>';
		echo '<button class="rzp-btn rzp-btn--gold" type="submit">Simpan spend</button></form></div>';
		echo '<div class="rzp-card"><table class="rzp-table"><thead><tr><th>Kempen</th><th>Spend</th><th>Kutipan</th><th>ROAS</th></tr></thead><tbody>';
		if ( ! $rows ) {
			echo '<tr><td colspan="4">Belum ada data ROI.</td></tr>';
		}
		foreach ( $rows as $row ) {
			echo '<tr><td class="rzp-name">' . esc_html( $row['name'] ) . '</td><td>' . esc_html( RakanZakat_Settings::format_money( $row['spend'] ) ) . '</td><td><strong>' . esc_html( RakanZakat_Settings::format_money( $row['revenue'] ) ) . '</strong></td><td>' . ( null !== $row['roas'] ? esc_html( $row['roas'] . 'x' ) : '—' ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	public static function view_admin_affiliates() {
		$rows = RakanZakat_Affiliates::all();
		$unpaid = 0;
		foreach ( $rows as $row ) {
			$unpaid += RakanZakat_Affiliates::stats( $row->id )['unpaid_earn_sen'];
		}
		self::page_head( 'Affiliates', 'Pengurusan rakan affiliate, kod rujukan dan pembayaran komisen. Tertunggak: ' . RakanZakat_Settings::format_money( $unpaid ), 'Sistem Pengurusan Rakan Strategik' );
		echo '<div class="rzp-card" style="margin-bottom:16px"><div class="rzp-card__title">' . self::mat( 'person_add' ) . '<div><h2>Tambah Affiliate Baru</h2><p>Kadar piawai ' . esc_html( self::s( 'default_commission_pct', 10 ) ) . '%</p></div></div>';
		echo '<form method="post" class="rzp-aff-form">';
		wp_nonce_field( 'rz_portal_affiliate' );
		echo '<input type="hidden" name="rz_portal_action" value="add_affiliate">';
		echo '<label>Nama Penuh<input name="name" required placeholder="Nama penuh amil"></label>';
		echo '<label>Emel Operasi<input type="email" name="email" required></label>';
		echo '<label>Katalaluan<input type="password" name="password" required minlength="8"></label>';
		echo '<label>Kod Rujukan<input name="code" placeholder="pilihan"></label>';
		echo '<label>Komisen %<input type="number" step="0.1" name="commission_pct" value="' . esc_attr( self::s( 'default_commission_pct', 10 ) ) . '"><button type="submit" class="rzp-btn rzp-btn--gold" style="margin-top:8px">Cipta Akaun</button></label></form></div>';
		echo '<div class="rzp-card"><table class="rzp-table"><thead><tr><th>Nama &amp; Emel</th><th>Kod</th><th>Link</th><th>Pelawat</th><th>Rujukan</th><th>Tertunggak</th><th></th></tr></thead><tbody>';
		if ( ! $rows ) {
			echo '<tr><td colspan="7">Belum ada affiliate.</td></tr>';
		}
		foreach ( $rows as $row ) {
			$st   = RakanZakat_Affiliates::stats( $row->id );
			$link = RakanZakat_Affiliates::link( $row->code );
			$amt  = RakanZakat_Settings::format_money( $st['unpaid_earn_sen'] );
			echo '<tr><td class="rzp-name">' . esc_html( $row->display_name ?: $row->code ) . '<small>' . esc_html( $row->user_email ) . '</small></td><td><span class="rzp-code">' . esc_html( $row->code ) . '</span></td><td><button type="button" class="js-rzp-copy rzp-btn rzp-btn--ghost" data-copy="' . esc_attr( $link ) . '">Salin</button></td><td>' . esc_html( number_format_i18n( $st['visits'] ) ) . '</td><td>' . esc_html( number_format_i18n( $st['referrals'] ) ) . '</td><td><strong>' . esc_html( $amt ) . '</strong></td><td>';
			if ( $st['unpaid_earn_sen'] > 0 ) {
				echo '<button type="button" class="rzp-btn rzp-btn--gold js-rzp-pay" data-id="' . esc_attr( $row->id ) . '" data-name="' . esc_attr( $row->display_name ) . '" data-amt="' . esc_attr( $amt ) . '">Bayar Komisen</button>';
			} else {
				echo '—';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table></div>';
		echo '<div class="rzp-modal" id="rzp-payout"><div class="rzp-modal__box"><h2>Sahkan Pembayaran Komisen</h2><p class="rzp-muted">Bayar <strong id="rzp-pay-amt"></strong> kepada <strong id="rzp-pay-name"></strong>?</p><form method="post">';
		wp_nonce_field( 'rz_portal_payout' );
		echo '<input type="hidden" name="rz_portal_action" value="payout"><input type="hidden" name="affiliate_id" value=""><div style="display:flex;gap:8px;margin-top:16px"><button type="button" class="rzp-btn rzp-btn--ghost js-rzp-close">Batal</button><button class="rzp-btn rzp-btn--gold">Ya, Sahkan &amp; Bayar</button></div></form></div></div>';
	}

	public static function view_admin_tetapan() {
		$s    = RakanZakat_Settings::get_all();
		$tab  = sanitize_key( wp_unslash( $_GET['tab'] ?? 'jenama' ) );
		$tabs = array(
			'jenama'      => 'Jenama',
			'billplz'     => 'Billplz',
			'pembayaran'  => 'Pembayaran',
			'affiliate'   => 'Affiliate',
			'tracking'    => 'Tracking',
			'notifikasi'  => 'Notifikasi',
		);
		if ( ! isset( $tabs[ $tab ] ) ) {
			$tab = 'jenama';
		}
		self::page_head( 'Tetapan', 'Jenama, pembayaran, affiliate dan tracking. Perubahan jenama nampak di login, admin dan affiliate area.', 'Konfigurasi Sistem' );
		echo '<nav class="rzp-tabs">';
		foreach ( $tabs as $key => $label ) {
			echo '<a class="' . ( $tab === $key ? 'is-active' : '' ) . '" href="' . esc_url( add_query_arg( 'tab', $key, self::url( 'admin', 'tetapan' ) ) ) . '">' . esc_html( $label ) . '</a>';
		}
		echo '</nav><form method="post" enctype="multipart/form-data">';
		wp_nonce_field( 'rz_portal_settings' );
		echo '<input type="hidden" name="rz_portal_action" value="save_settings"><input type="hidden" name="tab" value="' . esc_attr( $tab ) . '">';
		if ( 'jenama' === $tab ) {
			echo '<div class="rzp-split"><div class="rzp-card rzp-card-form"><h2>Identiti &amp; Jenama</h2>';
			echo '<label>Nama Jenama<input name="brand_name" value="' . esc_attr( $s['brand_name'] ) . '"></label>';
			echo '<label>Tagline<input name="brand_tagline" value="' . esc_attr( $s['brand_tagline'] ) . '"></label>';
			echo '<label>Badge / Kod Amil<input name="brand_badge" value="' . esc_attr( $s['brand_badge'] ) . '"></label>';
			echo '<label>Emel Sokongan<input type="email" name="support_email" value="' . esc_attr( $s['support_email'] ) . '"></label>';
			echo '<label>Telefon Sokongan<input name="support_phone" value="' . esc_attr( $s['support_phone'] ) . '"></label>';
			echo '<label>Teks Footer Login<input name="brand_footer" value="' . esc_attr( $s['brand_footer'] ) . '"></label>';
			echo '<label>Logo Utama<input type="file" name="brand_logo" accept="image/*"></label>';
			echo '<label class="rzp-check"><input type="checkbox" name="show_partner_badge" value="1"' . checked( ! empty( $s['show_partner_badge'] ), true, false ) . '> Tunjuk badge rakan di sidebar</label>';
			echo '<div class="rzp-colors">';
			foreach ( array( 'sidebar_color' => 'Sidebar', 'primary_color' => 'Primary', 'accent_color' => 'Aksen / Butang', 'bg_color' => 'Latar' ) as $key => $lab ) {
				$hex = esc_attr( $s[ $key ] );
				echo '<div class="rzp-color"><div class="rzp-swatch" style="background:' . $hex . '"></div><div><strong>' . esc_html( $lab ) . '</strong><input name="' . esc_attr( $key ) . '" value="' . $hex . '"></div></div>';
			}
			echo '</div></div><aside class="rzp-card"><h2>Pratonton</h2><div class="rzp-preview"><strong>' . esc_html( $s['brand_name'] ) . '</strong><div>' . esc_html( $s['brand_badge'] ) . '</div><a class="is-on">' . self::mat( 'dashboard' ) . ' Dashboard</a><a>' . self::mat( 'payments' ) . ' Kutipan</a></div></aside></div>';
		} elseif ( 'billplz' === $tab ) {
			echo '<div class="rzp-card rzp-card-form"><h2>Gerbang Pembayaran Billplz</h2>';
			echo '<label>API Secret Key<input type="password" name="api_key" value="' . esc_attr( $s['api_key'] ) . '"></label>';
			echo '<label>X-Signature Key<input type="password" name="x_signature" value="' . esc_attr( $s['x_signature'] ) . '"></label>';
			echo '<label>Collection ID<input name="collection_id" value="' . esc_attr( $s['collection_id'] ) . '"></label>';
			echo '<label class="rzp-check"><input type="checkbox" name="sandbox" value="1"' . checked( ! empty( $s['sandbox'] ), true, false ) . '> Guna persekitaran Sandbox</label>';
			echo '<div class="rzp-copy"><input readonly value="' . esc_attr( rest_url( 'rakanzakat/v1/billplz/callback' ) ) . '"><button type="button" class="js-rzp-copy rzp-btn rzp-btn--ghost" data-copy="' . esc_attr( rest_url( 'rakanzakat/v1/billplz/callback' ) ) . '">Salin</button></div></div>';
		} elseif ( 'pembayaran' === $tab ) {
			echo '<div class="rzp-card rzp-card-form"><h2>Pembayaran</h2>';
			echo '<label>Amaun minimum (RM)<input type="number" step="0.01" name="min_amount" value="' . esc_attr( $s['min_amount'] ) . '"></label>';
			echo '<label>ID halaman terima kasih<input type="number" name="thankyou_page" value="' . esc_attr( $s['thankyou_page'] ) . '"></label>';
			echo '<label>ID halaman gagal<input type="number" name="failed_page" value="' . esc_attr( $s['failed_page'] ) . '"></label>';
			echo '<label>Chip amaun (pisah koma)<input name="amount_presets" value="' . esc_attr( $s['amount_presets'] ) . '"></label></div>';
		} elseif ( 'affiliate' === $tab ) {
			echo '<div class="rzp-card rzp-card-form"><h2>Affiliate</h2>';
			echo '<label>Komisen lalai (%)<input type="number" step="0.1" name="default_commission_pct" value="' . esc_attr( $s['default_commission_pct'] ) . '"></label>';
			echo '<label>Cookie referral (hari)<input type="number" name="cookie_days" value="' . esc_attr( $s['cookie_days'] ) . '"></label>';
			echo '<label>Teks creative lalai<textarea name="creative_text" rows="4">' . esc_textarea( $s['creative_text'] ) . '</textarea></label></div>';
		} elseif ( 'tracking' === $tab ) {
			echo '<div class="rzp-card rzp-card-form"><h2>Tracking</h2>';
			echo '<label class="rzp-check"><input type="checkbox" name="track_admins" value="1"' . checked( ! empty( $s['track_admins'] ), true, false ) . '> Track pelawat yang login sebagai admin</label></div>';
		} else {
			echo '<div class="rzp-card rzp-card-form"><h2>Notifikasi</h2>';
			echo '<label class="rzp-check"><input type="checkbox" name="notify_receipt" value="1"' . checked( ! empty( $s['notify_receipt'] ), true, false ) . '> Emel resit kepada pembayar</label>';
			echo '<label class="rzp-check"><input type="checkbox" name="notify_admin" value="1"' . checked( ! empty( $s['notify_admin'] ), true, false ) . '> Alert kutipan baru kepada admin</label>';
			echo '<label>Emel admin (pisah koma)<textarea name="notify_emails" rows="3">' . esc_textarea( $s['notify_emails'] ) . '</textarea></label></div>';
		}
		echo '<div class="rzp-sticky">';
		if ( 'jenama' === $tab ) {
			echo '<button class="rzp-btn rzp-btn--ghost" name="rz_portal_action" value="reset_brand" formnovalidate>Reset ke lalai</button>';
		}
		echo '<button class="rzp-btn rzp-btn--gold" type="submit">Simpan tetapan</button></div></form>';
	}

	private static function affiliate_or_die() {
		$aff = RakanZakat_Affiliates::by_user( get_current_user_id() );
		if ( ! $aff && self::can_admin() ) {
			self::page_head( 'Portal Affiliate', 'Akaun admin ini belum didaftarkan sebagai affiliate.', 'Akses Affiliate' );
			echo '<p>Cipta akaun di <a href="' . esc_url( self::url( 'admin', 'affiliates' ) ) . '">Affiliates</a>.</p>';
			return null;
		}
		if ( ! $aff ) {
			self::page_head( 'Akaun belum siap', 'Sila hubungi pentadbir untuk daftar affiliate.', 'Portal Affiliate' );
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
		self::page_head( 'Selamat datang, ' . $user->display_name, 'Prestasi rujukan, lawatan dan komisen anda.', 'Portal Affiliate', true );
		echo '<h2 class="rzp-h2">Tempoh dipilih</h2>';
		self::cards(
			array(
				array( 'label' => 'Rujukan', 'value' => number_format_i18n( $m30['referrals'] ), 'href' => self::url( 'affiliate', 'referrals' ), 'icon' => 'handshake', 'delta' => self::delta_pct( $m30['referrals'], $prev['referrals'] ) ),
				array( 'label' => 'Lawatan', 'value' => number_format_i18n( $m30['visits'] ), 'href' => self::url( 'affiliate', 'visits' ), 'icon' => 'group', 'delta' => self::delta_pct( $m30['visits'], $prev['visits'] ) ),
				array( 'label' => 'Kadar penukaran', 'value' => $m30['conversion'] . '%', 'icon' => 'conversion_path' ),
				array( 'label' => 'Komisen tertunggak', 'value' => RakanZakat_Settings::format_money( $all['unpaid_earn_sen'] ), 'icon' => 'account_balance_wallet', 'tag' => 'Tertunggak', 'tag_gold' => true ),
			)
		);
		echo '<h2 class="rzp-h2">Keseluruhan</h2>';
		self::cards(
			array(
				array( 'label' => 'Rujukan', 'value' => number_format_i18n( $all['referrals'] ), 'href' => self::url( 'affiliate', 'referrals' ), 'icon' => 'handshake' ),
				array( 'label' => 'Lawatan', 'value' => number_format_i18n( $all['visits'] ), 'href' => self::url( 'affiliate', 'visits' ), 'icon' => 'group' ),
				array( 'label' => 'Kadar penukaran', 'value' => $all['conversion'] . '%', 'icon' => 'conversion_path' ),
				array( 'label' => 'Rujukan belum dibayar', 'value' => number_format_i18n( $all['unpaid_refs'] ), 'href' => self::url( 'affiliate', 'referrals' ), 'icon' => 'pending' ),
				array( 'label' => 'Rujukan dibayar', 'value' => number_format_i18n( $all['paid_refs'] ), 'icon' => 'verified' ),
				array( 'label' => 'Pendapatan tertunggak', 'value' => RakanZakat_Settings::format_money( $all['unpaid_earn_sen'] ), 'icon' => 'payments' ),
				array( 'label' => 'Jumlah pendapatan', 'value' => RakanZakat_Settings::format_money( $all['total_earn_sen'] ), 'icon' => 'savings' ),
			)
		);
	}

	public static function view_aff_urls() {
		$aff = self::affiliate_or_die();
		if ( ! $aff ) {
			return;
		}
		$link = RakanZakat_Affiliates::link( $aff->code );
		self::page_head( 'Pautan Affiliate', 'Kongsi pautan ini. Setiap bayaran melalui pautan dikira sebagai rujukan.', 'Kod Rujukan' );
		echo '<div class="rzp-card rzp-card-form"><label>Pautan rujukan</label>';
		echo '<div class="rzp-copy"><input id="rzp-link" type="text" readonly value="' . esc_attr( $link ) . '"><button type="button" class="js-rzp-copy rzp-btn rzp-btn--ghost" data-copy="' . esc_attr( $link ) . '">Salin</button></div>';
		echo '<p class="rzp-muted">Kod: <span class="rzp-code">' . esc_html( $aff->code ) . '</span> · Komisen ' . esc_html( number_format( $aff->commission_bp / 100, 1 ) ) . '%</p></div>';
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
		self::page_head( 'Graf', 'Lawatan dan rujukan mengikut hari.', 'Analitik Prestasi', true );
		echo '<div class="rzp-card"><div class="rzp-chart">';
		foreach ( $rows as $row ) {
			$vh = max( 4, round( ( $row['visits'] / $max ) * 100 ) );
			$rh = max( 4, round( ( $row['referrals'] / $max ) * 100 ) );
			echo '<div class="rzp-chart__col" title="' . esc_attr( $row['date'] . ': ' . $row['visits'] . ' lawatan, ' . $row['referrals'] . ' rujukan' ) . '">';
			echo '<span class="rzp-chart__bar rzp-chart__bar--v" style="height:' . esc_attr( $vh ) . '%"></span>';
			echo '<span class="rzp-chart__bar rzp-chart__bar--r" style="height:' . esc_attr( $rh ) . '%"></span>';
			echo '</div>';
		}
		echo '</div><p class="rzp-legend"><span class="rzp-dot rzp-dot--v"></span> Lawatan <span class="rzp-dot rzp-dot--r"></span> Rujukan</p></div>';
	}

	public static function view_aff_statistik() {
		$aff = self::affiliate_or_die();
		if ( ! $aff ) {
			return;
		}
		$all = RakanZakat_Affiliates::stats( $aff->id );
		self::page_head( 'Statistik', 'Ringkasan prestasi affiliate sepanjang masa.', 'Portal Affiliate' );
		self::cards(
			array(
				array( 'label' => 'Lawatan', 'value' => number_format_i18n( $all['visits'] ), 'icon' => 'group' ),
				array( 'label' => 'Rujukan', 'value' => number_format_i18n( $all['referrals'] ), 'icon' => 'handshake' ),
				array( 'label' => 'Penukaran', 'value' => $all['conversion'] . '%', 'icon' => 'conversion_path' ),
				array( 'label' => 'Kutipan dirujuk', 'value' => RakanZakat_Settings::format_money( $all['revenue_sen'] ), 'icon' => 'payments' ),
			)
		);
	}

	public static function view_aff_referrals() {
		$aff = self::affiliate_or_die();
		if ( ! $aff ) {
			return;
		}
		$rows = RakanZakat_Affiliates::referrals( $aff->id );
		self::page_head( 'Rujukan', 'Bayaran yang datang melalui kod affiliate anda.', 'Portal Affiliate' );
		echo '<div class="rzp-card"><table class="rzp-table"><thead><tr><th>Tarikh</th><th>Pembayar</th><th>Amaun</th><th>Komisen</th><th>Payout</th></tr></thead><tbody>';
		if ( ! $rows ) {
			echo '<tr><td colspan="5">Belum ada rujukan.</td></tr>';
		}
		foreach ( $rows as $row ) {
			$kom = RakanZakat_Affiliates::commission_sen( $row->paid_amount_sen ?: $row->amount_sen, $aff->commission_bp );
			echo '<tr><td>' . esc_html( $row->paid_at ?: $row->created_at ) . '</td><td class="rzp-name">' . esc_html( $row->payer_name ) . '</td><td>' . esc_html( RakanZakat_Settings::format_money( $row->paid_amount_sen ?: $row->amount_sen ) ) . '</td><td><strong>' . esc_html( RakanZakat_Settings::format_money( $kom ) ) . '</strong></td><td>' . self::status_chip( $row->payout_id ? 'paid' : 'pending' ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	public static function view_aff_payouts() {
		$aff = self::affiliate_or_die();
		if ( ! $aff ) {
			return;
		}
		$rows = RakanZakat_Affiliates::payouts( $aff->id );
		self::page_head( 'Bayaran komisen', 'Rekod payout yang telah diproses.', 'Portal Affiliate' );
		echo '<div class="rzp-card"><table class="rzp-table"><thead><tr><th>Tarikh</th><th>Amaun</th><th>Status</th></tr></thead><tbody>';
		if ( ! $rows ) {
			echo '<tr><td colspan="3">Belum ada payout.</td></tr>';
		}
		foreach ( $rows as $row ) {
			echo '<tr><td>' . esc_html( $row->paid_at ?: $row->created_at ) . '</td><td><strong>' . esc_html( RakanZakat_Settings::format_money( $row->amount_sen ) ) . '</strong></td><td>' . self::status_chip( $row->status ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	public static function view_aff_visits() {
		$aff = self::affiliate_or_die();
		if ( ! $aff ) {
			return;
		}
		$rows = RakanZakat_Affiliates::visits( $aff->id );
		self::page_head( 'Lawatan', 'Sesi yang masuk melalui pautan rujukan anda.', 'Portal Affiliate' );
		echo '<div class="rzp-card"><table class="rzp-table"><thead><tr><th>Masa</th><th>Halaman</th><th>UTM</th></tr></thead><tbody>';
		if ( ! $rows ) {
			echo '<tr><td colspan="3">Belum ada lawatan.</td></tr>';
		}
		foreach ( $rows as $row ) {
			echo '<tr><td>' . esc_html( $row->created_at ) . '</td><td class="rzp-name">' . esc_html( $row->page_title ?: $row->page_url ) . '</td><td>' . esc_html( $row->utm_source ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	public static function view_aff_creatives() {
		$aff = self::affiliate_or_die();
		if ( ! $aff ) {
			return;
		}
		$link = RakanZakat_Affiliates::link( $aff->code );
		$text = trim( (string) self::s( 'creative_text', 'Tunaikan zakat melalui saluran rasmi Rakan Zakat.' ) ) . ' ' . $link;
		self::page_head( 'Creative', 'Teks sedia untuk dikongsi bersama pautan rujukan.', 'Portal Affiliate' );
		echo '<div class="rzp-card rzp-card-form"><label>Teks creative</label><textarea readonly rows="4">' . esc_textarea( $text ) . '</textarea>';
		echo '<button type="button" class="js-rzp-copy rzp-btn rzp-btn--gold" data-copy="' . esc_attr( $text ) . '">Salin teks</button></div>';
	}

	private static function delta_pct( $now, $prev ) {
		$now  = (float) $now;
		$prev = (float) $prev;
		if ( $prev > 0 ) {
			return (int) round( ( ( $now - $prev ) / $prev ) * 100 );
		}
		return $now > 0 ? 100 : 0;
	}

	private static function cards( $items ) {
		$legacy = array(
			'money'  => 'payments',
			'visits' => 'group',
			'rate'   => 'conversion_path',
		);
		echo '<div class="rzp-kpis">';
		foreach ( $items as $item ) {
			if ( isset( $item['label'] ) ) {
				$label = $item['label'];
				$value = $item['value'];
				$meta  = $item['meta'] ?? '';
				$href  = $item['href'] ?? '';
				$icon  = $item['icon'] ?? 'payments';
				$delta = $item['delta'] ?? null;
				$tag   = $item['tag'] ?? '';
				$gold  = ! empty( $item['tag_gold'] );
			} else {
				$label = $item[0];
				$value = $item[1];
				$meta  = $item[2] ?? '';
				$href  = $item[3] ?? '';
				$icon  = 'payments';
				$delta = $item[4] ?? null;
				$tag   = '';
				$gold  = false;
			}
			$icon = $legacy[ $icon ] ?? $icon;
			echo '<article class="rzp-kpi">';
			echo '<div class="rzp-kpi__top"><span class="rzp-kpi__icon">' . self::mat( $icon ) . '</span>';
			if ( $tag ) {
				echo '<span class="rzp-tag' . ( $gold ? ' rzp-tag--gold' : '' ) . '">' . esc_html( $tag ) . '</span>';
			} elseif ( null !== $delta && 0 !== (int) $delta ) {
				$up = (int) $delta >= 0;
				echo '<span class="rzp-tag' . ( $up ? '' : ' rzp-tag--down' ) . '">' . ( $up ? '+' : '' ) . esc_html( (int) $delta ) . '%</span>';
			}
			echo '</div><label>' . esc_html( $label ) . '</label><strong>' . esc_html( $value ) . '</strong>';
			if ( $meta ) {
				echo '<em>' . esc_html( $meta ) . '</em>';
			}
			if ( $href ) {
				echo '<footer><a href="' . esc_url( $href ) . '">Lihat semua</a></footer>';
			}
			echo '</article>';
		}
		echo '</div>';
	}
}
