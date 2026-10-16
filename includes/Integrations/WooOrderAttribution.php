<?php
/**
 * WooCommerce Order Attribution consent bridge (cache-safe).
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Integrations;

/**
 * Keeps WooCommerce Order Attribution (sourcebuster.js, sbjs_* cookies) off
 * until the visitor allows the marketing category.
 *
 * The page always ships with tracking disabled, so it stays cacheable; the
 * inline bridge turns it on client-side from the consent cookie, and follows
 * later consent changes. WooCommerce's own setOrderTracking() starts
 * sourcebuster.js when allowed, and removes its cookies when revoked.
 */
final class WooOrderAttribution {

	/**
	 * WooCommerce's order attribution script handle.
	 *
	 * @var string
	 */
	private const SCRIPT_HANDLE = 'wc-order-attribution';

	/**
	 * Inline bridge, run right after WooCommerce's script. Reads the consent
	 * through the inline guard (always on the page before it).
	 *
	 * @var string
	 */
	private const BRIDGE_JS = <<<'JS'
(function(){var oa=window.wc_order_attribution;if(!oa||typeof oa.setOrderTracking!=='function'){return;}
var g=window.__lwGuard,c=g&&g.getConsent?g.getConsent():null;if(c&&c.marketing===true){oa.setOrderTracking(true);}
window.addEventListener('lwCookieConsent',function(e){var d=e.detail&&e.detail.categories;oa.setOrderTracking(!!(d&&d.marketing===true));});})();
JS;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// After WooCommerce's own WP Consent API filter (priority 10), which reads consent server-side.
		add_filter( 'wc_order_attribution_allow_tracking', '__return_false', 20 );
		// WooCommerce enqueues its script at the default priority.
		add_action( 'wp_enqueue_scripts', [ $this, 'add_consent_bridge' ], 20 );
	}

	/**
	 * Attach the consent bridge to WooCommerce's order attribution script.
	 *
	 * @return void
	 */
	public function add_consent_bridge(): void {
		if ( ! wp_script_is( self::SCRIPT_HANDLE, 'registered' ) ) {
			return;
		}

		wp_add_inline_script( self::SCRIPT_HANDLE, self::BRIDGE_JS, 'after' );
	}
}
