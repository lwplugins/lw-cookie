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
	 * Option holding the plugin version whose SW copy sits in the webroot.
	 */
	private const VERSION_OPTION = 'lw_cookie_sw_version';

	/**
	 * Get the public URL for the Service Worker.
	 *
	 * @return string
	 */
	public static function get_sw_url(): string {
		return home_url( '/' . self::SW_FILENAME );
	}

	/**
	 * Registration scope for the Service Worker: the home path.
	 *
	 * A worker served from below a subdirectory home (e.g. /hu/lw-cookie-sw.js)
	 * may only control that subdirectory unless the response carries a
	 * Service-Worker-Allowed header, which a statically served copy does not.
	 *
	 * @return string Home path with a trailing slash ("/" on a root install).
	 */
	public static function get_sw_scope(): string {
		return trailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );
	}

	/**
	 * Filesystem path of the public webroot the SW URL resolves to.
	 *
	 * On subdirectory-core installs (Bedrock/Radicle) the webroot is the site
	 * home directory, not ABSPATH (which is the `wp/` core subdirectory), so
	 * ABSPATH would place the file where `get_sw_url()` cannot reach it.
	 *
	 * Derived from the home/site URL path delta rather than `get_home_path()`:
	 * that core helper reads `$_SERVER['SCRIPT_FILENAME']` and returns "/" on
	 * exactly these installs whenever the entry point sits outside the core
	 * directory — every front-end request and every WP-CLI run.
	 *
	 * @return string Webroot path with a trailing slash.
	 */
	private static function get_root_path(): string {
		$abspath = rtrim( str_replace( '\\', '/', ABSPATH ), '/' );
		$home    = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
		$site    = trim( (string) wp_parse_url( site_url( '/' ), PHP_URL_PATH ), '/' );

		if ( $home === $site ) {
			return trailingslashit( $abspath );
		}

		// The core directory as seen from the home URL, e.g. "wp" on Bedrock.
		$prefix = '' === $home ? '' : $home . '/';

		if ( '' !== $prefix && ! str_starts_with( $site . '/', $prefix ) ) {
			return trailingslashit( $abspath );
		}

		$core = substr( $site, strlen( $prefix ) );

		// Unexpected layout (symlinks, custom WP_SITEURL) — stay on ABSPATH
		// rather than guessing a path that may sit outside open_basedir.
		if ( '' === $core || ! str_ends_with( $abspath, '/' . $core ) ) {
			return trailingslashit( $abspath );
		}

		return trailingslashit( substr( $abspath, 0, - strlen( '/' . $core ) ) );
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
	 * Refresh the webroot copy once after a plugin update, and restore it
	 * when it went missing.
	 *
	 * Updates do not fire the activation hook, and the web server serves the
	 * webroot copy directly, so without this an updated worker would never
	 * reach browsers. Atomic-release deploys (a fresh release directory per
	 * deploy) drop the copy while the recorded version stays current. Costs
	 * one autoloaded option read and one stat per request.
	 *
	 * @return void
	 */
	public static function maybe_upgrade(): void {
		if ( get_option( self::VERSION_OPTION ) === LW_COOKIE_VERSION ) {
			self::restore_missing_copy();
			return;
		}

		// Record first, so an unwritable webroot is not retried on every request
		// (the dynamic fallback then serves the current file).
		update_option( self::VERSION_OPTION, LW_COOKIE_VERSION );
		self::install();
	}

	/**
	 * Copy the SW back when the webroot lost it. An unwritable webroot is not
	 * retried (the copy would warn on every request); the dynamic fallback
	 * serves the worker there.
	 *
	 * @return void
	 */
	private static function restore_missing_copy(): void {
		$root = self::get_root_path();

		if ( file_exists( $root . self::SW_FILENAME ) || ! wp_is_writable( $root ) ) {
			return;
		}

		self::install();
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
	 * Called during init on every request, so it does no filesystem work: the
	 * cheap URI check in `serve_sw()` gates everything. When the static file
	 * really is in the webroot the web server answers before WordPress boots,
	 * so the callback never fires anyway.
	 *
	 * @return void
	 */
	public static function register_fallback(): void {
		add_action( 'template_redirect', [ __CLASS__, 'serve_sw' ], 0 );
	}

	/**
	 * Whether a request URI asks for the Service Worker: its path must match
	 * the path of `get_sw_url()`, e.g. /hu/lw-cookie-sw.js on a subdirectory home.
	 *
	 * @param string $request_uri Request URI, query string allowed.
	 * @return bool
	 */
	public static function is_sw_request( string $request_uri ): bool {
		return (string) wp_parse_url( self::get_sw_url(), PHP_URL_PATH ) === strtok( $request_uri, '?' );
	}

	/**
	 * Serve the Service Worker file dynamically.
	 *
	 * @return void
	 */
	public static function serve_sw(): void {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$request_uri = wp_unslash( $_SERVER['REQUEST_URI'] ?? '' );

		if ( ! self::is_sw_request( (string) $request_uri ) ) {
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
