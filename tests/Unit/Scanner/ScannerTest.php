<?php
/**
 * Tests for Scanner — the fallback category/provider guess for cookies the
 * cookie database does not know.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Scanner;

use Brain\Monkey\Functions;
use LightweightPlugins\Cookie\Options;
use LightweightPlugins\Cookie\Scanner\Scanner;
use LightweightPlugins\Cookie\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Cookie\Scanner\Scanner
 */
final class ScannerTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();
		Functions\when( 'get_option' )->justReturn( [] );
		Functions\when( 'wp_parse_args' )->alias( static fn( $args, $defaults ): array => array_merge( $defaults, (array) $args ) );
		// The cookie database is unreachable: the scanner falls back to guessing.
		Functions\when( 'add_query_arg' )->alias( static fn( $params, $url ) => $url );
		Functions\when( 'wp_remote_get' )->justReturn( new \WP_Error( 'offline' ) );
		Functions\when( 'is_wp_error' )->justReturn( true );
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * Sourcebuster.js cookies are marketing, even sbjs_session, whose name
	 * would otherwise read as a necessary session cookie (#15).
	 *
	 * @dataProvider provide_sourcebuster_cookies
	 *
	 * @param string $name Cookie name.
	 */
	public function test_sourcebuster_cookies_are_guessed_as_woocommerce_marketing( string $name ): void {
		$cookie = Scanner::enrich_cookies( [ $name ] )[0];

		$this->assertSame( [ 'marketing', 'WooCommerce' ], [ $cookie['category'], $cookie['provider'] ] );
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function provide_sourcebuster_cookies(): array {
		return array(
			'session'    => array( 'sbjs_session' ),
			'current'    => array( 'sbjs_current' ),
			'first add'  => array( 'sbjs_first_add' ),
			'migrations' => array( 'sbjs_migrations' ),
		);
	}

	public function test_other_session_cookies_stay_necessary(): void {
		$this->assertSame( 'necessary', Scanner::enrich_cookies( [ 'wp_woocommerce_session_1a2b' ] )[0]['category'] );
	}
}
