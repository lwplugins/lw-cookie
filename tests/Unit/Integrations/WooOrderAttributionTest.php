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
		Functions\expect( 'wp_add_inline_script' )
			->once()
			->with( 'wc-order-attribution', \Mockery::pattern( '/params\.allowTracking=true/' ), 'before' );

		( new WooOrderAttribution() )->add_consent_bridge();
	}

	/**
	 * WooCommerce must start with tracking on for a consenting visitor, or it
	 * wipes the sbjs_* cookies on every page (#19).
	 */
	public function test_before_script_starts_tracking_from_stored_consent(): void {
		$scripts = $this->captured_scripts();

		$this->assertStringContainsString( 'getConsent()', $scripts['before'] );
		$this->assertStringContainsString( 'marketing===true', $scripts['before'] );
		$this->assertStringNotContainsString( 'setOrderTracking', $scripts['before'] );
	}

	/**
	 * The bridge moves the stuck own `values` property back to the element's
	 * setter, and only switches tracking when the state changes (#19).
	 */
	public function test_bridge_unsticks_inputs_and_skips_redundant_switches(): void {
		$scripts = $this->captured_scripts();

		$this->assertStringContainsString( "hasOwnProperty.call(e,'values')", $scripts['after'] );
		$this->assertStringContainsString( 'delete e.values', $scripts['after'] );
		$this->assertStringContainsString( 'if(on!==cur){oa.setOrderTracking(on);}', $scripts['after'] );
	}

	/**
	 * Run the bridge and capture the inline scripts by position.
	 *
	 * @return array<string, string>
	 */
	private function captured_scripts(): array {
		$scripts = [];
		Functions\when( 'wp_script_is' )->justReturn( true );
		Functions\when( 'wp_add_inline_script' )->alias(
			static function ( string $handle, string $data, string $position ) use ( &$scripts ): bool {
				$scripts[ $position ] = $data;
				return true;
			}
		);

		( new WooOrderAttribution() )->add_consent_bridge();

		return $scripts;
	}

	public function test_bridge_is_skipped_without_order_attribution(): void {
		Functions\when( 'wp_script_is' )->justReturn( false );
		Functions\expect( 'wp_add_inline_script' )->never();

		( new WooOrderAttribution() )->add_consent_bridge();
	}
}
