<?php
/**
 * Tests for Entities — the domain and cookie maps handed to guard.js and the
 * Service Worker.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Blocking;

use Brain\Monkey\Functions;
use LightweightPlugins\Cookie\Blocking\Entities;
use LightweightPlugins\Cookie\Options;
use LightweightPlugins\Cookie\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Cookie\Blocking\Entities
 */
final class EntitiesTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();
		Functions\when( 'wp_parse_args' )->alias( static fn( $args, $defaults ): array => array_merge( $defaults, (array) $args ) );
		Functions\when( 'get_option' )->justReturn( [] );
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * Store the "Load youtube-nocookie.com embeds without consent" setting.
	 *
	 * @param bool $allow Setting value.
	 */
	private function allow_nocookie( bool $allow ): void {
		Functions\when( 'get_option' )->justReturn( [ 'allow_youtube_nocookie' => $allow ] );
		Options::clear_cache();
	}

	public function test_youtube_nocookie_is_blocked_by_default(): void {
		$domains = Entities::get_domains();

		$this->assertSame( 'marketing', $domains['youtube-nocookie.com'] ?? null );
		$this->assertSame( [], Entities::get_exempt_hosts() );
	}

	public function test_allowing_nocookie_drops_only_the_nocookie_host(): void {
		$this->allow_nocookie( true );

		$domains = Entities::get_domains();

		$this->assertArrayNotHasKey( 'youtube-nocookie.com', $domains );
		$this->assertSame( 'marketing', $domains['youtube.com'] ?? null );
		$this->assertSame( 'marketing', $domains['youtu.be'] ?? null );
	}

	public function test_js_config_carries_the_exemption_to_guard_and_worker(): void {
		$this->allow_nocookie( true );

		$this->assertArrayNotHasKey( 'youtube-nocookie.com', Entities::get_js_config()['domains'] );
	}

	/**
	 * Snap Pixel: the script host and the event hosts its script sends to.
	 *
	 * @dataProvider provide_snap_hosts
	 *
	 * @param string $host Host.
	 */
	public function test_snap_pixel_hosts_are_marketing( string $host ): void {
		$this->assertSame( 'marketing', Entities::get_domains()[ $host ] ?? null );
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function provide_snap_hosts(): array {
		return array(
			'script'      => array( 'sc-static.net' ),
			'events'      => array( 'tr.snapchat.com' ),
			'events ipv6' => array( 'tr6.snapchat.com' ),
			'events test' => array( 'tr-shadow.snapchat.com' ),
		);
	}

	/**
	 * Snap Pixel's first-party cookies wait for marketing consent.
	 *
	 * @dataProvider provide_snap_cookies
	 *
	 * @param string $cookie Cookie name.
	 */
	public function test_snap_pixel_cookies_are_marketing( string $cookie ): void {
		$this->assertSame( 'marketing', Entities::get_cookies()[ $cookie ] ?? null );
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function provide_snap_cookies(): array {
		return array(
			'visitor id' => array( '_scid' ),
			'tag check'  => array( '_sctr' ),
		);
	}

	/**
	 * snap.licdn.com is LinkedIn's Insight Tag host, not Snapchat's.
	 */
	public function test_snap_licdn_is_linkedin_marketing(): void {
		$this->assertSame( 'marketing', Entities::get_domains()['snap.licdn.com'] ?? null );
	}
}
