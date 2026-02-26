<?php
/**
 * Google Consent Mode v2 Integration (cache-safe).
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Integrations;

/**
 * Handles Google Consent Mode v2 integration.
 *
 * Always outputs 'denied' defaults for all optional categories (v2.0).
 * The guard.js script reads the consent cookie client-side and issues
 * a `gtag('consent', 'update', ...)` call if valid consent exists.
 * This makes the output fully cacheable.
 */
final class GoogleConsentMode {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_head', [ $this, 'output_consent_defaults' ], -PHP_INT_MAX );
	}

	/**
	 * Output Google Consent Mode default values.
	 *
	 * All optional categories default to 'denied'.
	 * guard.js will update them client-side if the user has consented.
	 *
	 * @return void
	 */
	public function output_consent_defaults(): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}

		gtag('consent', 'default', {
			'analytics_storage': 'denied',
			'ad_storage': 'denied',
			'ad_user_data': 'denied',
			'ad_personalization': 'denied',
			'functionality_storage': 'denied',
			'personalization_storage': 'denied',
			'security_storage': 'granted',
			'wait_for_update': 500
		});

		gtag('consent', 'default', {
			'analytics_storage': 'denied',
			'ad_storage': 'denied',
			'ad_user_data': 'denied',
			'ad_personalization': 'denied',
			'region': ['AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IE','IT','LV','LT','LU','MT','NL','PL','PT','RO','SK','SI','ES','SE','IS','LI','NO','GB','CH']
		});

		if (typeof fbq === 'function') {
			fbq('consent', 'revoke');
		}
		</script>
		<?php
	}
}
