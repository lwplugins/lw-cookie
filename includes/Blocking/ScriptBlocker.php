<?php
/**
 * Script Blocker class.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Blocking;

use LightweightPlugins\Cookie\Consent\Manager as ConsentManager;

/**
 * Blocks tracking scripts until consent is given.
 */
final class ScriptBlocker {

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

		// Only block if consent is not given for all categories.
		if ( ! $this->should_block() ) {
			return;
		}

		add_filter( 'script_loader_tag', [ $this, 'filter_script_tag' ], 10, 3 );
		add_action( 'wp_head', [ $this, 'output_blocker_script' ], 1 );
	}

	/**
	 * Check if we should block scripts.
	 *
	 * @return bool
	 */
	private function should_block(): bool {
		// Don't block in admin.
		if ( is_admin() ) {
			return false;
		}

		// Block if any optional category is not allowed.
		return ! $this->consent_manager->is_category_allowed( 'analytics' )
			|| ! $this->consent_manager->is_category_allowed( 'marketing' )
			|| ! $this->consent_manager->is_category_allowed( 'functional' );
	}

	/**
	 * Filter script tags to block known tracking scripts.
	 *
	 * @param string $tag    Script tag HTML.
	 * @param string $handle Script handle.
	 * @param string $src    Script source URL.
	 * @return string
	 */
	public function filter_script_tag( string $tag, string $handle, string $src ): string {
		$category = KnownScripts::get_category_for_url( $src );

		if ( null === $category ) {
			return $tag;
		}

		// Check if this category is allowed.
		if ( $this->consent_manager->is_category_allowed( $category ) ) {
			return $tag;
		}

		// Block by changing type and adding data attribute.
		return $this->convert_to_blocked( $tag, $category );
	}

	/**
	 * Convert a script tag to blocked version.
	 *
	 * @param string $tag      Script tag HTML.
	 * @param string $category Category key.
	 * @return string
	 */
	private function convert_to_blocked( string $tag, string $category ): string {
		// Change type to text/plain to prevent execution.
		$tag = str_replace( 'type="text/javascript"', 'type="text/plain"', $tag );

		// If no type attribute, add one.
		if ( ! str_contains( $tag, 'type=' ) ) {
			$tag = str_replace( '<script', '<script type="text/plain"', $tag );
		}

		// Add data attribute for category.
		$tag = str_replace( '<script', '<script data-lw-cookie-category="' . esc_attr( $category ) . '"', $tag );

		return $tag;
	}

	/**
	 * Output script that handles unblocking.
	 *
	 * @return void
	 */
	public function output_blocker_script(): void {
		?>
		<script>
		(function() {
			'use strict';

			/**
			 * Unblock scripts when consent is given.
			 */
			function unblockScripts(categories) {
				var blockedScripts = document.querySelectorAll('script[data-lw-cookie-category]');

				blockedScripts.forEach(function(script) {
					var category = script.getAttribute('data-lw-cookie-category');

					if (categories[category]) {
						var newScript = document.createElement('script');

						// Copy attributes (except type and data-lw-cookie-category)
						Array.from(script.attributes).forEach(function(attr) {
							if (attr.name !== 'type' && attr.name !== 'data-lw-cookie-category') {
								newScript.setAttribute(attr.name, attr.value);
							}
						});

						newScript.type = 'text/javascript';

						// Copy inline content safely using textContent
						if (script.textContent) {
							newScript.textContent = script.textContent;
						}

						// Replace blocked script with executable one
						script.parentNode.replaceChild(newScript, script);
					}
				});
			}

			// Listen for consent events
			window.addEventListener('lwCookieConsent', function(e) {
				unblockScripts(e.detail.categories);
			});
		})();
		</script>
		<?php
	}
}
