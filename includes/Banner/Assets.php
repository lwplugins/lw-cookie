<?php
/**
 * Banner Assets class.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Banner;

use LightweightPlugins\Cookie\Options;
use LightweightPlugins\Cookie\Consent\Storage;
use LightweightPlugins\Cookie\Blocking\Entities;
use LightweightPlugins\Cookie\Blocking\ServiceWorkerManager;

/**
 * Handles CSS and JS assets for the banner.
 *
 * No consent state in the config (v2.0) — everything is
 * cache-safe. Consent is read from the browser cookie
 * by guard.js and consent.js.
 */
final class Assets {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_head', [ $this, 'output_custom_css' ], 100 );
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		wp_enqueue_style(
			'lw-cookie-notice',
			LW_COOKIE_URL . 'assets/css/banner.css',
			[],
			LW_COOKIE_VERSION
		);

		wp_enqueue_script(
			'lw-cookie-consent',
			LW_COOKIE_URL . 'assets/js/consent.js',
			[],
			LW_COOKIE_VERSION,
			true
		);

		wp_localize_script(
			'lw-cookie-consent',
			'lwCookieConfig',
			$this->get_js_config()
		);
	}

	/**
	 * Output custom CSS for colors and placeholder styles.
	 *
	 * @return void
	 */
	public function output_custom_css(): void {
		$primary_color    = Options::get( 'primary_color' );
		$text_color       = Options::get( 'text_color' );
		$background_color = Options::get( 'background_color' );
		$border_radius    = Options::get( 'border_radius' );
		?>
		<style id="lw-cookie-custom-css">
			:root {
				--lw-cookie-primary: <?php echo esc_attr( $primary_color ); ?>;
				--lw-cookie-text: <?php echo esc_attr( $text_color ); ?>;
				--lw-cookie-bg: <?php echo esc_attr( $background_color ); ?>;
				--lw-cookie-radius: <?php echo esc_attr( $border_radius ); ?>px;
			}
		</style>
		<?php
	}

	/**
	 * Get JavaScript configuration (cache-safe — no consent state).
	 *
	 * @return array<string, mixed>
	 */
	private function get_js_config(): array {
		return [
			'cookieName'      => Storage::COOKIE_NAME,
			'policyVersion'   => (string) Options::get( 'policy_version' ),
			'restUrl'         => rest_url( 'lw-cookie/v1/consent' ),
			'blocking'        => Entities::get_js_config(),
			'swUrl'           => ServiceWorkerManager::get_sw_url(),
			'categories'      => Options::get_categories(),
			'consentDuration' => (int) Options::get( 'consent_duration' ),
		];
	}
}
