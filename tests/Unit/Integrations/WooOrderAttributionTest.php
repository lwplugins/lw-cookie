<?php
/**
 * Tests for the WooCommerce Order Attribution consent bridge.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Integrations;

use Brain\Monkey\Functions;
use LightweightPlugins\Cookie\Integrations\WooOrderAttribution;
use LightweightPlugins\Cookie\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Cookie\Integrations\WooOrderAttribution
 */
final class WooOrderAttributionTest extends MonkeyTestCase {

	/**
	 * Tracking starts off in the (cacheable) page; only consent turns it on.
	 */
	public function test_tracking_is_off_server_side(): void {
		new WooOrderAttribution();

		$this->assertTrue( has_filter( 'wc_order_attribution_allow_tracking', '__return_false' ) > 10 );
	}

	public function test_bridge_attaches_to_the_order_attribution_script(): void {
		Functions\when( 'wp_script_is' )->justReturn( true );
		Functions\expect( 'wp_add_inline_script' )
			->once()
			->with( 'wc-order-attribution', \Mockery::pattern( '/setOrderTracking.*lwCookieConsent/s' ), 'after' );

		( new WooOrderAttribution() )->add_consent_bridge();
	}

	public function test_bridge_is_skipped_without_order_attribution(): void {
		Functions\when( 'wp_script_is' )->justReturn( false );
		Functions\expect( 'wp_add_inline_script' )->never();

		( new WooOrderAttribution() )->add_consent_bridge();
	}
}
