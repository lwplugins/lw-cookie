<?php
/**
 * Guard Script — inlines guard.js into <head>.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Blocking;

use LightweightPlugins\Cookie\Banner\Preview;
use LightweightPlugins\Cookie\I18n\Strings;
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

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, self-authored CSS.
		echo '<style id="lw-cookie-guard-css">' . self::PLACEHOLDER_CSS . '</style>';

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted JS from local file, JSON via wp_json_encode.
		echo '<script id="lw-cookie-guard">window.__lwGuardCfg=' . wp_json_encode( $config ) . ';' . $guard . '</script>';
	}

	/**
	 * Placeholder styling for blocked embeds.
	 *
	 * Inlined (not enqueued) so it is in place before the parser reaches the
	 * body and guard.js inserts the first placeholder — no flash of unstyled box.
	 *
	 * @var string
	 */
	private const PLACEHOLDER_CSS = '.lw-cookie-embed-block{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;min-height:180px;padding:24px;margin:0 auto;box-sizing:border-box;text-align:center;background:#f0f0f1;border:1px solid #dcdcde;border-radius:6px;color:#1d2327;font-size:14px;line-height:1.5}.lw-cookie-embed-block__msg{margin:0}.lw-cookie-embed-block__btn{cursor:pointer;border:0;border-radius:4px;padding:10px 18px;background:#2271b1;color:#fff;font-size:14px;line-height:1.2}.lw-cookie-embed-block__btn:hover{background:#135e96}';

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
			'preview'       => Preview::is_active(),
			'text'          => [
				'blockedMessage' => Strings::get_or_default(
					'blocked_embed_message',
					__( 'This content is blocked until you accept the required cookies.', 'lw-cookie' )
				),
				'blockedButton'  => Strings::get_or_default(
					'blocked_embed_button',
					__( 'Accept & load content', 'lw-cookie' )
				),
			],
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
