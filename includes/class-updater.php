<?php
/**
 * WordPress-native plugin updates from GitHub Releases or a JSON feed.
 *
 * @package RakanZakat
 */

defined( 'ABSPATH' ) || exit;

class RakanZakat_Updater {

	const SLUG      = 'rakanzakat';
	const CACHE_KEY = 'rakanzakat_remote_update';
	const CACHE_TTL = 6 * HOUR_IN_SECONDS;

	public static function init() {
		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'inject_update' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'plugins_api' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( __CLASS__, 'rename_source' ), 10, 4 );
		add_filter( 'http_request_args', array( __CLASS__, 'auth_github_request' ), 10, 2 );
		add_filter( 'upgrader_pre_download', array( __CLASS__, 'verify_download' ), 10, 3 );
		add_filter( 'auto_update_plugin', array( __CLASS__, 'auto_update' ), 10, 2 );
	}

	public static function is_configured() {
		$source = RakanZakat_Settings::get( 'update_source', 'github' );
		if ( 'json' === $source ) {
			return (bool) self::config_value( 'update_json_url' );
		}
		return (bool) self::github_repo();
	}

	public static function get_remote( $force = false ) {
		if ( ! $force ) {
			$cached = get_site_transient( self::CACHE_KEY );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		if ( ! self::is_configured() ) {
			return new WP_Error( 'rz_no_source', __( 'Sumber update belum dikonfigurasi.', 'rakanzakat' ) );
		}

		$source = RakanZakat_Settings::get( 'update_source', 'github' );
		$remote = 'json' === $source ? self::fetch_json() : self::fetch_github();

		if ( is_wp_error( $remote ) ) {
			return $remote;
		}

		set_site_transient( self::CACHE_KEY, $remote, self::CACHE_TTL );
		return $remote;
	}

	public static function inject_update( $transient ) {
		if ( ! is_object( $transient ) || empty( $transient->checked ) ) {
			return $transient;
		}

		$remote = self::get_remote();
		if ( is_wp_error( $remote ) || empty( $remote['version'] ) || empty( $remote['package'] ) ) {
			return $transient;
		}

		if ( version_compare( RAKANZAKAT_VERSION, $remote['version'], '>=' ) ) {
			unset( $transient->response[ RAKANZAKAT_BASENAME ] );
			$transient->no_update[ RAKANZAKAT_BASENAME ] = self::as_plugin_object( $remote, false );
			return $transient;
		}

		if ( ! self::is_allowed_package( $remote['package'] ) ) {
			return $transient;
		}

		$transient->response[ RAKANZAKAT_BASENAME ] = self::as_plugin_object( $remote, true );
		return $transient;
	}

	public static function plugins_api( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || self::SLUG !== $args->slug ) {
			return $result;
		}

		$remote = self::get_remote();
		if ( is_wp_error( $remote ) ) {
			return $result;
		}

		return (object) array(
			'name'          => 'Rakan Zakat',
			'slug'          => self::SLUG,
			'version'       => $remote['version'],
			'author'        => '<a href="https://rakanzakat.com">Rakan Zakat</a>',
			'homepage'      => 'https://rakanzakat.com',
			'requires'      => $remote['requires'],
			'requires_php'  => $remote['requires_php'],
			'tested'        => $remote['tested'],
			'download_link' => $remote['package'],
			'sections'      => array(
				'description' => 'Dashboard kutipan zakat, Billplz, tracking pelawat dan ROI untuk rakanzakat.com.',
				'changelog'   => $remote['changelog'] ? wp_kses_post( $remote['changelog'] ) : 'Tiada changelog.',
			),
		);
	}

	public static function rename_source( $source, $remote_source, $upgrader, $hook_extra ) {
		$plugin = isset( $hook_extra['plugin'] ) ? $hook_extra['plugin'] : '';
		if ( RAKANZAKAT_BASENAME !== $plugin ) {
			return $source;
		}

		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			return $source;
		}

		$source = trailingslashit( $source );
		if ( $wp_filesystem->exists( $source . 'rakanzakat.php' ) ) {
			$desired = trailingslashit( $remote_source ) . self::SLUG . '/';
			if ( untrailingslashit( $source ) === untrailingslashit( $desired ) ) {
				return $source;
			}
			if ( $wp_filesystem->move( $source, $desired ) ) {
				return $desired;
			}
		}

		return $source;
	}

	public static function auth_github_request( $args, $url ) {
		if ( ! self::is_github_url( $url ) ) {
			return $args;
		}

		if ( ! is_array( $args ) ) {
			$args = array();
		}
		if ( empty( $args['headers'] ) || ! is_array( $args['headers'] ) ) {
			$args['headers'] = array();
		}

		$args['headers']['User-Agent'] = 'RakanZakat/' . RAKANZAKAT_VERSION;
		$args['headers']['Accept']     = 'application/vnd.github+json';
		$args['sslverify']             = true;

		$token = self::github_token();
		if ( $token ) {
			$args['headers']['Authorization'] = 'Bearer ' . $token;
		}

		return $args;
	}

	public static function verify_download( $reply, $package, $upgrader ) {
		$remote = self::get_remote();
		if ( is_wp_error( $remote ) || empty( $remote['package'] ) || $package !== $remote['package'] ) {
			return $reply;
		}

		if ( ! self::is_allowed_package( $package ) ) {
			return new WP_Error( 'rz_bad_package', __( 'Pakej update ditolak: URL mesti HTTPS dari GitHub atau host JSON yang sah.', 'rakanzakat' ) );
		}

		if ( empty( $remote['sha256'] ) ) {
			return $reply;
		}

		$file = download_url( $package, 60 );
		if ( is_wp_error( $file ) ) {
			return $file;
		}

		$hash = hash_file( 'sha256', $file );
		if ( ! hash_equals( strtolower( $remote['sha256'] ), strtolower( (string) $hash ) ) ) {
			wp_delete_file( $file );
			return new WP_Error( 'rz_checksum', __( 'Checksum SHA256 pakej tidak sepadan. Update dibatalkan.', 'rakanzakat' ) );
		}

		return $file;
	}

	public static function auto_update( $update, $item ) {
		$plugin = '';
		if ( is_object( $item ) && ! empty( $item->plugin ) ) {
			$plugin = $item->plugin;
		}
		if ( RAKANZAKAT_BASENAME !== $plugin ) {
			return $update;
		}
		if ( ! self::is_configured() ) {
			return false;
		}
		return (int) RakanZakat_Settings::get( 'auto_update', 1 ) === 1;
	}

	public static function clear_cache() {
		delete_site_transient( self::CACHE_KEY );
		delete_site_transient( 'update_plugins' );
	}

	private static function fetch_github() {
		$repo = self::github_repo();
		if ( ! $repo ) {
			return new WP_Error( 'rz_repo', __( 'GitHub repo tidak sah. Guna format owner/nama-repo.', 'rakanzakat' ) );
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . $repo . '/releases/latest',
			array(
				'timeout'   => 15,
				'sslverify' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( 200 !== $code || ! is_array( $data ) ) {
			$message = isset( $data['message'] ) ? $data['message'] : __( 'Gagal baca GitHub release.', 'rakanzakat' );
			return new WP_Error( 'rz_github', $message );
		}

		$version = isset( $data['tag_name'] ) ? ltrim( (string) $data['tag_name'], 'vV' ) : '';
		$package = self::github_package_url( $data );
		if ( ! $version || ! $package ) {
			return new WP_Error( 'rz_github_empty', __( 'Release GitHub tiada tag atau fail zip.', 'rakanzakat' ) );
		}

		$changelog = isset( $data['body'] ) ? $data['body'] : '';
		if ( $changelog && function_exists( 'wp_markdown_to_html' ) ) {
			$changelog = wp_markdown_to_html( $changelog );
		} else {
			$changelog = wpautop( esc_html( $changelog ) );
		}

		return array(
			'version'      => $version,
			'package'      => $package,
			'changelog'    => $changelog,
			'requires'     => '6.0',
			'requires_php' => '7.4',
			'tested'       => '6.8',
			'sha256'       => '',
		);
	}

	private static function fetch_json() {
		$url = self::config_value( 'update_json_url' );
		if ( ! $url || 0 !== strpos( $url, 'https://' ) ) {
			return new WP_Error( 'rz_json_https', __( 'Update JSON mesti HTTPS.', 'rakanzakat' ) );
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 15,
				'sslverify' => true,
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || empty( $data['version'] ) || empty( $data['download_url'] ) ) {
			return new WP_Error( 'rz_json', __( 'Fail update JSON tidak lengkap (perlu version & download_url).', 'rakanzakat' ) );
		}

		return array(
			'version'      => sanitize_text_field( $data['version'] ),
			'package'      => esc_url_raw( $data['download_url'] ),
			'changelog'    => isset( $data['changelog'] ) ? wp_kses_post( $data['changelog'] ) : '',
			'requires'     => sanitize_text_field( $data['requires'] ?? '6.0' ),
			'requires_php' => sanitize_text_field( $data['requires_php'] ?? '7.4' ),
			'tested'       => sanitize_text_field( $data['tested'] ?? '6.8' ),
			'sha256'       => sanitize_text_field( $data['sha256'] ?? '' ),
		);
	}

	private static function github_package_url( $release ) {
		if ( ! empty( $release['assets'] ) && is_array( $release['assets'] ) ) {
			foreach ( $release['assets'] as $asset ) {
				$name = isset( $asset['name'] ) ? strtolower( (string) $asset['name'] ) : '';
				$url  = $asset['browser_download_url'] ?? '';
				if ( $url && substr( $name, -4 ) === '.zip' ) {
					return $url;
				}
			}
		}

		return isset( $release['zipball_url'] ) ? $release['zipball_url'] : '';
	}

	private static function as_plugin_object( $remote, $has_update ) {
		return (object) array(
			'slug'           => self::SLUG,
			'plugin'         => RAKANZAKAT_BASENAME,
			'name'           => 'Rakan Zakat',
			'version'        => $has_update ? RAKANZAKAT_VERSION : $remote['version'],
			'new_version'    => $remote['version'],
			'url'            => 'https://rakanzakat.com',
			'package'        => $has_update ? $remote['package'] : '',
			'tested'         => $remote['tested'],
			'requires'       => $remote['requires'],
			'requires_php'   => $remote['requires_php'],
			'icons'          => array(),
		);
	}

	private static function is_allowed_package( $url ) {
		if ( 0 !== strpos( (string) $url, 'https://' ) ) {
			return false;
		}

		$host = wp_parse_url( $url, PHP_URL_HOST );
		$ok   = array(
			'github.com',
			'api.github.com',
			'codeload.github.com',
			'objects.githubusercontent.com',
			'release-assets.githubusercontent.com',
			'github-releases.githubusercontent.com',
		);

		$json = self::config_value( 'update_json_url' );
		if ( $json ) {
			$json_host = wp_parse_url( $json, PHP_URL_HOST );
			if ( $json_host ) {
				$ok[] = $json_host;
			}
		}

		return $host && in_array( strtolower( (string) $host ), $ok, true );
	}

	private static function is_github_url( $url ) {
		$host = wp_parse_url( (string) $url, PHP_URL_HOST );
		if ( ! $host ) {
			return false;
		}
		$host = strtolower( $host );
		return in_array(
			$host,
			array(
				'github.com',
				'api.github.com',
				'codeload.github.com',
				'objects.githubusercontent.com',
				'release-assets.githubusercontent.com',
				'github-releases.githubusercontent.com',
			),
			true
		);
	}

	private static function github_repo() {
		$repo = self::config_value( 'github_repo' );
		if ( ! $repo || ! preg_match( '/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $repo ) ) {
			return '';
		}
		return $repo;
	}

	private static function github_token() {
		if ( defined( 'RAKANZAKAT_GITHUB_TOKEN' ) && RAKANZAKAT_GITHUB_TOKEN ) {
			return (string) RAKANZAKAT_GITHUB_TOKEN;
		}
		return (string) RakanZakat_Settings::get( 'github_token', '' );
	}

	private static function config_value( $key ) {
		$map = array(
			'github_repo'     => 'RAKANZAKAT_GITHUB_REPO',
			'update_json_url' => 'RAKANZAKAT_UPDATE_JSON',
		);
		if ( isset( $map[ $key ] ) && defined( $map[ $key ] ) && constant( $map[ $key ] ) ) {
			return (string) constant( $map[ $key ] );
		}
		return (string) RakanZakat_Settings::get( $key, '' );
	}
}
