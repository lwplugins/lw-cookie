<?php
/**
 * Plugin Name:       LW Cookie
 * Plugin URI:        https://github.com/lwplugins/lw-cookie
 * Description:       Lightweight cookie consent — GDPR-compliant banner with minimal footprint.
 * Version:           1.6.8
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
define( 'LW_COOKIE_VERSION', '1.6.8' );
define( 'LW_COOKIE_FILE', __FILE__ );
define( 'LW_COOKIE_PATH', plugin_dir_path( __FILE__ ) );
define( 'LW_COOKIE_URL', plugin_dir_url( __FILE__ ) );

// Autoloader: local vendor (standalone/ZIP) or root Composer (dependency install).
if ( file_exists( LW_COOKIE_PATH . 'vendor/autoload.php' ) ) {
	require_once LW_COOKIE_PATH . 'vendor/autoload.php';
} elseif ( ! class_exists( Plugin::class ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			printf(
				'<div class="notice notice-error"><p><strong>LW Cookie:</strong> %s</p></div>',
				esc_html__( 'Autoloader not found. Please run "composer install" in the plugin directory, or re-install the plugin from a release ZIP.', 'lw-cookie' )
			);
		}
	);
	return;
}

// Activation and deactivation hooks.
register_activation_hook( __FILE__, [ Activator::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ Activator::class, 'deactivate' ] );

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
