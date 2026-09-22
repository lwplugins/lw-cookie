<?php
/**
 * Tests for Entities — the domain and cookie maps handed to guard.js and the
 * Service Worker.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Blocking;

use LightweightPlugins\Cookie\Blocking\Entities;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\Cookie\Blocking\Entities
 */
final class EntitiesTest extends TestCase {

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
