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
	 * Copy the SW file to ABSPATH (site root).
	 *
	 * @return bool True on success.
	 */
	public static function install(): bool {
		$source = LW_COOKIE_PATH . 'assets/js/' . self::SW_FILENAME;
		$dest   = ABSPATH . self::SW_FILENAME;

		if ( ! file_exists( $source ) ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy
		return copy( $source, $dest );
	}

	/**
	 * Remove the SW file from ABSPATH.
	 *
	 * @return void
	 */
	public static function uninstall(): void {
		$file = ABSPATH . self::SW_FILENAME;

		if ( file_exists( $file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $file );
		}
	}

	/**
	 * Register the dynamic fallback route.
	 *
	 * Called during init — serves the SW dynamically if the
	 * static file does not exist in ABSPATH.
	 *
	 * @return void
	 */
	public static function register_fallback(): void {
		if ( file_exists( ABSPATH . self::SW_FILENAME ) ) {
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

		header( 'Content-Type: application/javascript; charset=utf-8' );
		header( 'Service-Worker-Allowed: /' );
		header( 'Cache-Control: no-cache' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.Security.EscapeOutput.OutputNotEscaped -- JS source file.
		echo file_get_contents( $source );
		exit;
	}
}
