<?php
/**
 * Tests that the Site Manager abilities opt in to MCP discovery.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\SiteManager;

use Brain\Monkey\Functions;
use LightweightPlugins\Cookie\SiteManager\CookieAbilities;
use LightweightPlugins\Cookie\Tests\Unit\MonkeyTestCase;

/**
 * Regression: the abilities carried show_in_rest but no `mcp` meta, so LW
 * Site Manager's MCP server (which only auto-exposes site-manager/*) never
 * listed them.
 *
 * @covers \LightweightPlugins\Cookie\SiteManager\CookieAbilities
 */
final class CookieAbilitiesMcpMetaTest extends MonkeyTestCase {

	/**
	 * Registers the abilities with a deny-all permission manager.
	 *
	 * @return array<string, array<string, mixed>> Registration args by ability name.
	 */
	private function registered(): array {
		$registered = [];

		Functions\stubTranslationFunctions();
		Functions\when( 'wp_register_ability' )->alias(
			static function ( string $name, array $args ) use ( &$registered ): void {
				$registered[ $name ] = $args;
			}
		);

		$permissions = new class() {
			public function callback( string $check ): callable {
				return static fn (): bool => false;
			}
		};

		CookieAbilities::register( $permissions );

		return $registered;
	}

	public function test_every_ability_is_a_public_mcp_tool(): void {
		$registered = $this->registered();

		$this->assertNotEmpty( $registered );
		foreach ( $registered as $name => $args ) {
			$this->assertSame(
				[
					'public' => true,
					'type'   => 'tool',
				],
				$args['meta']['mcp'] ?? null,
				$name
			);
			$this->assertTrue( $args['meta']['show_in_rest'], $name );
		}
	}

	public function test_mcp_exposure_does_not_bypass_the_permission_callbacks(): void {
		Functions\when( 'current_user_can' )->justReturn( false );

		foreach ( $this->registered() as $name => $args ) {
			$this->assertFalse( (bool) call_user_func( $args['permission_callback'] ), $name );
		}
	}
}
