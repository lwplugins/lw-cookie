<?php
/**
 * Tests for CacheCompat.
 *
 * LiteSpeed Cache's "Load JS Deferred: Delayed" turned the consent config
 * (lwCookieConfig) and the inline guard into type="litespeed/javascript",
 * while consent.js itself ran at once. consent.js then saved a consent cookie
 * without a policy version, the guard treated it as missing, and every
 * "Accept" reloaded the page into the banner again.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Integrations;

use Brain\Monkey\Functions;
use LightweightPlugins\Cookie\Integrations\CacheCompat;
use LightweightPlugins\Cookie\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Cookie\Integrations\CacheCompat
 */
final class CacheCompatTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'add_filter' )->justReturn( true );
	}

	public function test_litespeed_never_delays_the_consent_config_or_the_guard(): void {
		$excluded = ( new CacheCompat() )->litespeed_defer_exclude( [ 'jquery.js' ] );

		$this->assertSame( [ 'jquery.js', 'lwCookieConfig', '__lwGuardCfg' ], $excluded );
	}

	public function test_the_consent_config_script_is_marked_not_to_be_delayed(): void {
		$attributes = ( new CacheCompat() )->inline_script_attributes(
			[ 'id' => 'lw-cookie-consent-js-extra' ]
		);

		$this->assertSame( '1', $attributes['data-no-defer'] );
		$this->assertSame( '1', $attributes['data-no-optimize'] );
		$this->assertSame( 'false', $attributes['data-cfasync'] );
	}

	public function test_other_inline_scripts_are_left_alone(): void {
		$attributes = ( new CacheCompat() )->inline_script_attributes( [ 'id' => 'other-js-extra' ] );

		$this->assertSame( [ 'id' => 'other-js-extra' ], $attributes );
	}

	public function test_the_attribute_markup_lists_every_cache_plugin_marker(): void {
		$this->assertSame(
			'data-no-defer="1" data-no-optimize="1" data-no-lazy="1" data-cfasync="false" data-pagespeed-no-defer',
			CacheCompat::attribute_markup()
		);
	}
}
