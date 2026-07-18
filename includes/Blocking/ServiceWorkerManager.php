<?php
/**
 * Service Worker Manager — handles SW file deployment.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Blocking;

/**
 * Manages the Service Worker file lifecycle.
 *
 * The SW must be served from the site root (ABSPATH) to control
 * the full scope. On activation we copy it there; on deactivation
 * we clean it up. If ABSPATH is not writable we fall back to
 * serving it dynamically via template_redirect.
 */
final class ServiceWorkerManager {

	/**
	 * SW filename in the site root.
	 */
	private const SW_FILENAME = 'lw-cookie-sw.js';

	/**
	 * Get the public URL for the Service Worker.
	 *
	 * @return string
	 */
	public static function get_sw_url(): string {
		return home_url( '/' . self::SW_FILENAME );
	}

	/**
	 * Filesystem path of the public webroot the SW URL resolves to.
	 *
	 * On subdirectory-core installs (Bedrock/Radicle) the webroot is the site
	 * home directory, not ABSPATH (which is the `wp/` core subdirectory), so
	 * ABSPATH would place the file where `get_sw_url()` cannot reach it.
	 *
	 * @return string Webroot path with a trailing slash.
	 */
	private static function get_root_path(): string {
		if ( ! function_exists( 'get_home_path' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		return get_home_path();
	}

	/**
	 * Copy the SW file to the public webroot.
	 *
	 * @return bool True on success.
	 */
	public static function install(): bool {
		$source = LW_COOKIE_PATH . 'assets/js/' . self::SW_FILENAME;
		$dest   = self::get_root_path() . self::SW_FILENAME;

		if ( ! file_exists( $source ) ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy
		return copy( $source, $dest );
	}

	/**
	 * Remove the SW file from the public webroot.
	 *
	 * @return void
	 */
	public static function uninstall(): void {
		$file = self::get_root_path() . self::SW_FILENAME;

		if ( file_exists( $file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $file );
		}
	}

	/**
	 * Register the dynamic fallback route.
	 *
	 * Called during init — serves the SW dynamically if the static file does
	 * not exist in the public webroot.
	 *
	 * @return void
	 */
	public static function register_fallback(): void {
		if ( file_exists( self::get_root_path() . self::SW_FILENAME ) ) {
			return;
		}

		add_action( 'template_redirect', [ __CLASS__, 'serve_sw' ], 0 );
	}

	/**
	 * Serve the Service Worker file dynamically.
	 *
	 * @return void
	 */
	public static function serve_sw(): void {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$request_uri = wp_unslash( $_SERVER['REQUEST_URI'] ?? '' );

		if ( '/' . self::SW_FILENAME !== strtok( $request_uri, '?' ) ) {
			return;
		}

		$source = LW_COOKIE_PATH . 'assets/js/' . self::SW_FILENAME;

		if ( ! file_exists( $source ) ) {
			return;
		}

		// The main query already 404'd for this virtual URL; override the
		// status so browsers accept the Service Worker registration.
		status_header( 200 );
		header( 'Content-Type: application/javascript; charset=utf-8' );
		header( 'Service-Worker-Allowed: /' );
		header( 'Cache-Control: no-cache' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.Security.EscapeOutput.OutputNotEscaped -- JS source file.
		echo file_get_contents( $source );
		exit;
	}
}
