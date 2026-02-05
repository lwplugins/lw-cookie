<?php
/**
 * Google Consent Mode v2 Integration.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Integrations;

use LightweightPlugins\Cookie\Consent\Manager as ConsentManager;

/**
 * Handles Google Consent Mode v2 integration.
 */
final class GoogleConsentMode {

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

		add_action( 'wp_head', [ $this, 'output_consent_defaults' ], 1 );
	}

	/**
	 * Output Google Consent Mode default values.
	 *
	 * @return void
	 */
	public function output_consent_defaults(): void {
		if ( is_admin() ) {
			return;
		}

		$categories = $this->consent_manager->get_allowed_categories();
		?>
		<script>
		// Google Consent Mode v2 - Default values
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}

		gtag('consent', 'default', {
			'analytics_storage': '<?php echo $categories['analytics'] ? 'granted' : 'denied'; ?>',
			'ad_storage': '<?php echo $categories['marketing'] ? 'granted' : 'denied'; ?>',
			'ad_user_data': '<?php echo $categories['marketing'] ? 'granted' : 'denied'; ?>',
			'ad_personalization': '<?php echo $categories['marketing'] ? 'granted' : 'denied'; ?>',
			'functionality_storage': '<?php echo $categories['functional'] ? 'granted' : 'denied'; ?>',
			'personalization_storage': '<?php echo $categories['functional'] ? 'granted' : 'denied'; ?>',
			'security_storage': 'granted',
			'wait_for_update': 500
		});

		// Set region-specific defaults (EEA requires stricter defaults)
		gtag('consent', 'default', {
			'analytics_storage': 'denied',
			'ad_storage': 'denied',
			'ad_user_data': 'denied',
			'ad_personalization': 'denied',
			'region': ['AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'IS', 'LI', 'NO', 'GB', 'CH']
		});
		</script>
		<?php
	}
}
