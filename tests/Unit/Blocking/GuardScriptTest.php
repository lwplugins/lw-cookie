<?php
/**
 * Tests for GuardScript.
 *
 * The Advanced tab's "Content Blocking" setting was saved but never read, so
 * embeds were blocked whenever the banner was on. The guard now receives it.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Blocking;

use Brain\Monkey\Functions;
use LightweightPlugins\Cookie\Blocking\GuardScript;
use LightweightPlugins\Cookie\Options;
use LightweightPlugins\Cookie\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Cookie\Blocking\GuardScript
 */
final class GuardScriptTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();

		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'is_admin' )->justReturn( false );
		Functions\when( 'wp_parse_args' )->alias(
			static fn( $args, $defaults = [] ) => array_merge( (array) $defaults, (array) $args )
		);
		Functions\when( 'home_url' )->alias( static fn( $path = '' ) => 'https://example.test' . $path );
		Functions\when( 'wp_json_encode' )->alias( static fn( $data ) => json_encode( $data ) );
		Functions\when( '__' )->returnArg();
		Functions\when( 'has_filter' )->justReturn( false );
		Functions\when( 'pll__' )->returnArg();
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * Render the guard and decode the config it hands to guard.js.
	 *
	 * @param array<string, mixed> $saved Saved plugin options.
	 * @return array<string, mixed>
	 */
	private function guard_config( array $saved ): array {
		Functions\when( 'get_option' )->justReturn( $saved );

		ob_start();
		( new GuardScript() )->output_guard();
		$html = (string) ob_get_clean();

		$this->assertSame( 1, preg_match( '#window\.__lwGuardCfg=(\{.*?\});/\*\*#s', $html, $match ) );

		return json_decode( $match[1], true );
	}

	public function test_content_blocking_is_on_by_default(): void {
		$this->assertTrue( $this->guard_config( [] )['contentBlocking'] );
	}

	public function test_content_blocking_off_reaches_the_guard(): void {
		$this->assertFalse( $this->guard_config( [ 'content_blocking' => false ] )['contentBlocking'] );
	}
}
