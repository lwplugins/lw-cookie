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
use LightweightPlugins\Cookie\Cookies\CookiePattern;
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

	public function test_custom_rules_come_first_and_override_the_built_in_list(): void {
		Functions\when( 'get_option' )->justReturn(
			[
				'custom_blocking_rules' => [
					[
						'pattern'  => 'code.tidio.co',
						'category' => 'functional',
					],
					[
						'pattern'  => 'googletagmanager.com',
						'category' => 'necessary',
					],
				],
			]
		);
		Options::clear_cache();

		$domains = Entities::get_domains();

		$this->assertSame( [ 'code.tidio.co', 'googletagmanager.com' ], array_slice( array_keys( $domains ), 0, 2 ) );
		$this->assertSame( 'necessary', $domains['googletagmanager.com'] );
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
		$this->assertSame( 'marketing', Entities::get_cookies()[ $cookie . '*' ] ?? null );
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
	 * WooCommerce Order Attribution (sourcebuster.js) cookies wait for
	 * marketing consent (#15). The guard matches by prefix, so one key
	 * covers sbjs_session, sbjs_current, sbjs_first, sbjs_udata and the rest.
	 */
	public function test_woocommerce_order_attribution_cookies_are_marketing(): void {
		$this->assertSame( 'marketing', Entities::get_cookies()['sbjs_'] ?? null );
	}

	/**
	 * snap.licdn.com is LinkedIn's Insight Tag host, not Snapchat's.
	 */
	public function test_snap_licdn_is_linkedin_marketing(): void {
		$this->assertSame( 'marketing', Entities::get_domains()['snap.licdn.com'] ?? null );
	}

	public function test_every_1_9_6_cookie_name_is_still_matched_except_fr_which_is_exact(): void {
		$cookies = Entities::get_cookies();
		$old     = [
			'_ga', '_ga_', '_gid', '_gat', '_gac_', '__utma', '__utmb', '__utmc', '__utmz', '__utmt',
			'_hjid', '_hjSession', '_hjSessionUser', '_clck', '_clsk', '_fbp', '_fbc', '_gcl_au',
			'_gcl_aw', 'IDE', 'test_cookie', 'li_fat_id', 'li_sugr', '_pin_unauth', 'muc_ads',
			'personalization_id', 'tt_pixel_session_index', '_ttp', '_scid', '_sctr', 'sbjs_',
		];

		foreach ( $old as $name ) {
			// The name itself and a suffixed variant, as the old prefix rule matched both.
			foreach ( [ $name, $name . 'Rejected' ] as $variant ) {
				$best = -1;
				foreach ( array_keys( $cookies ) as $key ) {
					$best = max( $best, CookiePattern::specificity( (string) $key, $variant ) );
				}
				$this->assertGreaterThanOrEqual( 0, $best, $variant );
			}
		}
	}

	public function test_fr_is_exact_and_gads_is_marketing(): void {
		$cookies = Entities::get_cookies();

		$this->assertSame( 'marketing', $cookies['fr'] );
		$this->assertSame( PHP_INT_MAX, CookiePattern::specificity( 'fr', 'fr' ) );
		$this->assertSame( -1, CookiePattern::specificity( 'fr', 'fruit_pref' ) );
		$this->assertSame( 'marketing', $cookies['_gads'] );
		$this->assertGreaterThan(
			CookiePattern::specificity( '_ga*', '_gads' ),
			CookiePattern::specificity( '_gads', '_gads' )
		);
	}

	public function test_hotjar_session_variants_stay_analytics_and_all_keys_are_valid(): void {
		$cookies = Entities::get_cookies();

		$this->assertSame( 'analytics', $cookies['_hjSession*'] );
		$this->assertGreaterThanOrEqual( 0, CookiePattern::specificity( '_hjSession*', '_hjSessionRejected' ) );

		foreach ( array_keys( $cookies ) as $key ) {
			$this->assertTrue( CookiePattern::is_valid( (string) $key ), (string) $key );
		}
	}
}
