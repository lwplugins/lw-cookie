<?php
/**
 * Tests for PlaceholderRegistry.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Blocking;

use LightweightPlugins\Cookie\Blocking\PlaceholderRegistry;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\Cookie\Blocking\PlaceholderRegistry
 */
final class PlaceholderRegistryTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		PlaceholderRegistry::clear();
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wp_scripts'] );
		PlaceholderRegistry::clear();
		parent::tearDown();
	}

	/**
	 * @param array<string, array<int, mixed>> $deps Handle → registered deps.
	 */
	private function register( array $deps ): void {
		$registered = [];
		foreach ( $deps as $handle => $list ) {
			$registered[ $handle ] = (object) [ 'deps' => $list ];
		}
		$GLOBALS['wp_scripts'] = (object) [ 'registered' => $registered ];
	}

	public function test_direct_dependency_returns_its_category(): void {
		$this->register( [ 'dep' => [ 'jquery', 'gt' ] ] );
		PlaceholderRegistry::remember( 'gt', 'analytics' );

		$this->assertSame( 'analytics', PlaceholderRegistry::blocked_dependency( 'dep' ) );
	}

	public function test_transitive_dependency_through_unrecorded_handle_is_found(): void {
		$this->register(
			[
				'alias' => [ 'gt' ],
				'mid'   => [ 'alias' ],
				'top'   => [ 'mid' ],
			]
		);
		PlaceholderRegistry::remember( 'gt', 'marketing' );

		$this->assertSame( 'marketing', PlaceholderRegistry::blocked_dependency( 'top' ) );
	}

	public function test_first_blocked_dependency_wins(): void {
		$this->register( [ 'dep' => [ 'a', 'b' ] ] );
		PlaceholderRegistry::remember( 'b', 'marketing' );
		PlaceholderRegistry::remember( 'a', 'analytics' );

		$this->assertSame( 'analytics', PlaceholderRegistry::blocked_dependency( 'dep' ) );
	}

	public function test_dependency_cycle_terminates(): void {
		$this->register(
			[
				'a' => [ 'b' ],
				'b' => [ 'a' ],
			]
		);
		PlaceholderRegistry::remember( 'other', 'analytics' );

		$this->assertNull( PlaceholderRegistry::blocked_dependency( 'a' ) );
	}

	/**
	 * @return array<string, array{0: mixed}>
	 */
	public static function broken_registries(): array {
		return [
			'no global'         => [ null ],
			'not an object'     => [ 'wp_scripts' ],
			'no registered'     => [ (object) [] ],
			'deps not an array' => [ (object) [ 'registered' => [ 'dep' => (object) [ 'deps' => 'gt' ] ] ] ],
		];
	}

	/**
	 * @dataProvider broken_registries
	 *
	 * @param mixed $scripts Value of the wp_scripts global.
	 */
	public function test_missing_or_broken_registry_gives_null( $scripts ): void {
		if ( null !== $scripts ) {
			$GLOBALS['wp_scripts'] = $scripts;
		}
		PlaceholderRegistry::remember( 'gt', 'analytics' );

		$this->assertNull( PlaceholderRegistry::blocked_dependency( 'dep' ) );
	}

	public function test_clear_forgets_recorded_handles(): void {
		$this->register( [ 'dep' => [ 'gt' ] ] );
		PlaceholderRegistry::remember( 'gt', 'analytics' );
		PlaceholderRegistry::clear();

		$this->assertNull( PlaceholderRegistry::blocked_dependency( 'dep' ) );
	}

	public function test_non_string_deps_are_ignored(): void {
		$this->register( [ 'dep' => [ 7, 'gt' ] ] );
		PlaceholderRegistry::remember( 'gt', 'analytics' );

		$this->assertSame( 'analytics', PlaceholderRegistry::blocked_dependency( 'dep' ) );
	}
}
