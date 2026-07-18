<?php
/**
 * Tests for Banner\Visibility.
 *
 * Covers issue #7: the banner must stay hidden inside page-builder editors
 * (Bricks/Elementor) and, optionally, for logged-in users.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Banner;

use Brain\Monkey\Functions;
use LightweightPlugins\Cookie\Banner\Visibility;
use LightweightPlugins\Cookie\Options;
use LightweightPlugins\Cookie\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Cookie\Banner\Visibility
 */
final class VisibilityTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();

		Functions\when( 'wp_parse_args' )->alias(
			static fn( $args, $defaults = [] ) => array_merge( (array) $defaults, (array) $args )
		);
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'sanitize_key' )->alias( static fn( $key ) => strtolower( (string) $key ) );

		unset( $_GET['bricks'], $_GET['elementor-preview'] );
	}

	protected function tearDown(): void {
		Options::clear_cache();
		unset( $_GET['bricks'], $_GET['elementor-preview'] );
		parent::tearDown();
	}

	/**
	 * Point Options at a given saved-options array.
	 *
	 * @param array<string, mixed> $saved Saved options.
	 */
	private function stub_options( array $saved ): void {
		Functions\when( 'get_option' )->justReturn( $saved );
	}

	public function test_displays_by_default(): void {
		$this->stub_options( [] );
		Functions\when( 'is_user_logged_in' )->justReturn( false );

		$this->assertTrue( Visibility::should_display() );
	}

	public function test_hidden_in_bricks_builder(): void {
		$_GET['bricks'] = 'run';

		$this->assertFalse( Visibility::should_display() );
	}

	public function test_hidden_in_elementor_preview(): void {
		$_GET['elementor-preview'] = '123';

		$this->assertFalse( Visibility::should_display() );
	}

	public function test_hidden_for_logged_in_when_option_enabled(): void {
		$this->stub_options( [ 'hide_for_logged_in' => true ] );
		Functions\when( 'is_user_logged_in' )->justReturn( true );

		$this->assertFalse( Visibility::should_display() );
	}

	public function test_shown_for_logged_in_when_option_disabled(): void {
		$this->stub_options( [ 'hide_for_logged_in' => false ] );
		Functions\when( 'is_user_logged_in' )->justReturn( true );

		$this->assertTrue( Visibility::should_display() );
	}

	public function test_shown_for_anonymous_even_when_option_enabled(): void {
		$this->stub_options( [ 'hide_for_logged_in' => true ] );
		Functions\when( 'is_user_logged_in' )->justReturn( false );

		$this->assertTrue( Visibility::should_display() );
	}
}
