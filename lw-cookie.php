<?php
/**
 * Plugin Name:       Lightweight Cookie
 * Plugin URI:        https://github.com/lwplugins/lw-cookie
 * Description:       GDPR-compliant cookie consent banner for WordPress - minimal footprint, full compliance.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            LW Plugins
 * Author URI:        https://lwplugins.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       lw-cookie
 * Domain Path:       /languages
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'LW_COOKIE_VERSION', '1.1.0' );
define( 'LW_COOKIE_FILE', __FILE__ );
define( 'LW_COOKIE_PATH', plugin_dir_path( __FILE__ ) );
define( 'LW_COOKIE_URL', plugin_dir_url( __FILE__ ) );

// Autoloader (required for PSR-4 class loading).
if ( file_exists( LW_COOKIE_PATH . 'vendor/autoload.php' ) ) {
	require_once LW_COOKIE_PATH . 'vendor/autoload.php';
}

// Activation hook.
register_activation_hook( __FILE__, [ Activator::class, 'activate' ] );

/**
 * Returns the main plugin instance.
 *
 * @return Plugin
 */
function lw_cookie(): Plugin {
	static $instance = null;

	if ( null === $instance ) {
		$instance = new Plugin();
	}

	return $instance;
}

// Initialize the plugin.
add_action( 'plugins_loaded', __NAMESPACE__ . '\\lw_cookie' );
