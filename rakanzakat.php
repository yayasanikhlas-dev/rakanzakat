<?php
/**
 * Plugin Name: Rakan Zakat
 * Plugin URI: https://rakanzakat.com
 * Description: Dashboard kutipan zakat untuk Rakanzakat.com. Pembayaran Billplz, tracking pelawat, ads spend, dan ROI.
 * Version: 1.9.5
 * Author: Rakan Zakat
 * Author URI: https://rakanzakat.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rakanzakat
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package RakanZakat
 */

defined( 'ABSPATH' ) || exit;

define( 'RAKANZAKAT_VERSION', '1.9.5' );
define( 'RAKANZAKAT_FILE', __FILE__ );
define( 'RAKANZAKAT_PATH', plugin_dir_path( __FILE__ ) );
define( 'RAKANZAKAT_URL', plugin_dir_url( __FILE__ ) );
define( 'RAKANZAKAT_BASENAME', plugin_basename( __FILE__ ) );

require_once RAKANZAKAT_PATH . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'RakanZakat_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'RakanZakat_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'RakanZakat_Plugin', 'instance' ) );
