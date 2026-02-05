<?php
/**
 * Banner Assets class.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Banner;

use LightweightPlugins\Cookie\Options;
use LightweightPlugins\Cookie\Consent\Manager as ConsentManager;
use LightweightPlugins\Cookie\Blocking\ContentBlocker;

/**
 * Handles CSS and JS assets for the banner.
 */
final class Assets {

	/**
	 * Consent manager instance.
	 *
	 * @var ConsentManager
	 */
	private ConsentManager $consent_manager;

	/**
	 * Constructor.
	 *
	 * @param ConsentManager $consent_manager Consent manager instance.
	 */
	public function __construct( ConsentManager $consent_manager ) {
		$this->consent_manager = $consent_manager;

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_head', [ $this, 'output_custom_css' ], 100 );
		add_action( 'wp_footer', [ $this, 'output_content_blocker_js' ], 100 );
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		wp_enqueue_style(
			'lw-cookie-banner',
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
	 * Output custom CSS for colors.
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
			<?php
			if ( Options::get( 'content_blocking' ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS is safe.
				echo ContentBlocker::get_placeholder_css();
			}
			?>
		</style>
		<?php
	}

	/**
	 * Output content blocker JavaScript.
	 *
	 * @return void
	 */
	public function output_content_blocker_js(): void {
		if ( ! Options::get( 'content_blocking' ) ) {
			return;
		}
		?>
		<script id="lw-cookie-content-blocker">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo ContentBlocker::get_placeholder_js();
		?>
		</script>
		<?php
	}

	/**
	 * Get JavaScript configuration.
	 *
	 * @return array<string, mixed>
	 */
	private function get_js_config(): array {
		return [
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'lw_cookie_consent' ),
			'hasConsent'    => $this->consent_manager->has_consent(),
			'isValid'       => $this->consent_manager->is_consent_valid(),
			'categories'    => $this->consent_manager->get_allowed_categories(),
			'policyVersion' => Options::get( 'policy_version' ),
		];
	}
}
