<?php
/**
 * Guard Script — inlines guard.js into <head>.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Blocking;

use LightweightPlugins\Cookie\Options;
use LightweightPlugins\Cookie\Consent\Storage;

/**
 * Outputs the client-side guard as an inline <script> in wp_head.
 *
 * Priority 1 ensures it runs before any third-party tracking
 * script has a chance to execute or set cookies.
 */
final class GuardScript {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_head', [ $this, 'output_guard' ], 1 );
	}

	/**
	 * Output the inline guard script.
	 *
	 * @return void
	 */
	public function output_guard(): void {
		if ( is_admin() ) {
			return;
		}

		$config = $this->get_config();
		$guard  = $this->get_guard_source();

		if ( ! $guard ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted JS from local file, JSON via wp_json_encode.
		echo '<script id="lw-cookie-guard">window.__lwGuardCfg=' . wp_json_encode( $config ) . ';' . $guard . '</script>';
	}

	/**
	 * Get guard configuration for JavaScript.
	 *
	 * @return array<string, mixed>
	 */
	private function get_config(): array {
		$blocking = Entities::get_js_config();

		return [
			'cookieName'    => Storage::COOKIE_NAME,
			'policyVersion' => (string) Options::get( 'policy_version' ),
			'domains'       => $blocking['domains'],
			'cookies'       => $blocking['cookies'],
			'swUrl'         => ServiceWorkerManager::get_sw_url(),
		];
	}

	/**
	 * Read the guard.js source file.
	 *
	 * @return string|null JS content or null on failure.
	 */
	private function get_guard_source(): ?string {
		$file = LW_COOKIE_PATH . 'assets/js/guard.js';

		if ( ! file_exists( $file ) ) {
			return null;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( $file );

		return false !== $content ? $content : null;
	}
}
