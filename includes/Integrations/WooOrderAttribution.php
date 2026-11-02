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
 * The page always ships with tracking disabled, so it stays cacheable. A
 * small inline script before WooCommerce's own turns tracking on from the
 * consent cookie, so WooCommerce starts with the right state and does not wipe
 * the sbjs_* cookies on every page. The inline bridge after it follows later
 * consent changes through WooCommerce's setOrderTracking(), which starts
 * sourcebuster.js when allowed and removes its cookies when revoked.
 *
 * WooCommerce writes the checkout inputs' values before it defines their
 * custom element, which leaves an own `values` property that hides the
 * element's setter; the bridge moves it back so later updates fill the inputs.
 */
final class WooOrderAttribution {

	/**
	 * WooCommerce's order attribution script handle.
	 *
	 * @var string
	 */
	private const SCRIPT_HANDLE = 'wc-order-attribution';

	/**
	 * Runs before WooCommerce's script: starts it with tracking on when the
	 * visitor already allowed marketing (the inline guard is in the head).
	 *
	 * @var string
	 */
	private const BEFORE_JS = <<<'JS'
(function(){var oa=window.wc_order_attribution,g=window.__lwGuard;if(!oa||!oa.params||!g||typeof g.getConsent!=='function'){return;}
var c=g.getConsent();if(c&&c.marketing===true){oa.params.allowTracking=true;}})();
JS;

	/**
	 * Runs right after WooCommerce's script: unsticks the checkout inputs, and
	 * follows consent changes without re-initialising an already running tracker.
	 *
	 * @var string
	 */
	private const BRIDGE_JS = <<<'JS'
(function(){var oa=window.wc_order_attribution;if(!oa||typeof oa.setOrderTracking!=='function'){return;}
function unstick(){document.querySelectorAll('wc-order-attribution-inputs').forEach(function(e){if(Object.prototype.hasOwnProperty.call(e,'values')){var v=e.values;delete e.values;e.values=v||{};}});}
function apply(on){unstick();var cur=!!(oa.params&&oa.params.allowTracking===true);if(on!==cur){oa.setOrderTracking(on);}}
var g=window.__lwGuard,c=g&&g.getConsent?g.getConsent():null;apply(!!(c&&c.marketing===true));
window.addEventListener('lwCookieConsent',function(e){var d=e.detail&&e.detail.categories;apply(!!(d&&d.marketing===true));});})();
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
	 * Attach the consent scripts to WooCommerce's order attribution script.
	 *
	 * @return void
	 */
	public function add_consent_bridge(): void {
		if ( ! wp_script_is( self::SCRIPT_HANDLE, 'registered' ) ) {
			return;
		}

		wp_add_inline_script( self::SCRIPT_HANDLE, self::BEFORE_JS, 'before' );
		wp_add_inline_script( self::SCRIPT_HANDLE, self::BRIDGE_JS, 'after' );
	}
}
