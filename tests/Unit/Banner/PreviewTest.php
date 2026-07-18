<?php
/**
 * Tests for Banner\Preview.
 *
 * Covers issue #7 preview mode: ?lw-cookie-preview=1 activates a banner
 * preview, but only for users who can manage options.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Banner;

use Brain\Monkey\Functions;
use LightweightPlugins\Cookie\Banner\Preview;
use LightweightPlugins\Cookie\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Cookie\Banner\Preview
 */
final class PreviewTest extends MonkeyTestCase {

	protected function tearDown(): void {
		unset( $_GET[ Preview::QUERY_ARG ] );
		parent::tearDown();
	}

	public function test_inactive_without_the_query_arg(): void {
		unset( $_GET[ Preview::QUERY_ARG ] );

		$this->assertFalse( Preview::is_active() );
	}

	public function test_active_for_a_manager_with_the_query_arg(): void {
		$_GET[ Preview::QUERY_ARG ] = '1';
		Functions\when( 'current_user_can' )->justReturn( true );

		$this->assertTrue( Preview::is_active() );
	}

	public function test_inactive_for_a_non_manager_even_with_the_query_arg(): void {
		$_GET[ Preview::QUERY_ARG ] = '1';
		Functions\when( 'current_user_can' )->justReturn( false );

		$this->assertFalse( Preview::is_active() );
	}

	public function test_url_appends_the_preview_query_arg(): void {
		Functions\when( 'home_url' )->alias( static fn( $path = '' ) => 'https://example.test' . $path );
		Functions\when( 'add_query_arg' )->alias(
			static fn( $key, $value, $url ) => $url . ( str_contains( $url, '?' ) ? '&' : '?' ) . $key . '=' . $value
		);

		$this->assertSame(
			'https://example.test/?lw-cookie-preview=1',
			Preview::url()
		);
	}
}
