<?php
/**
 * Tests for I18n\StringRegistry.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\I18n;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use LightweightPlugins\Cookie\I18n\StringRegistry;
use LightweightPlugins\Cookie\Options;
use LightweightPlugins\Cookie\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Cookie\I18n\StringRegistry
 */
final class StringRegistryTest extends MonkeyTestCase {

	/**
	 * Strings handed to pll_register_string(), as name => source.
	 *
	 * @var array<string, string>
	 */
	private static array $registered = [];

	/**
	 * Names handed to icl_unregister_string().
	 *
	 * @var array<int, string>
	 */
	private static array $unregistered = [];

	/**
	 * Fake contents of Polylang's WPML-compatibility store.
	 *
	 * @var array<string, array<string, string>>
	 */
	private static array $wpml_store = [];

	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();
		self::$registered   = [];
		self::$unregistered = [];
		self::$wpml_store   = [];

		Functions\when( 'wp_parse_args' )->alias(
			static fn( $args, $defaults = [] ) => array_merge( (array) $defaults, (array) $args )
		);
		Functions\when( 'get_option' )->alias(
			static fn( $name, $fallback = false ) => 'polylang_wpml_strings' === $name ? self::$wpml_store : []
		);
		Functions\when( 'sanitize_key' )->alias(
			static fn( $key ) => strtolower( (string) preg_replace( '/[^a-zA-Z0-9_\-]/', '', (string) $key ) )
		);
		// Polylang is active.
		Functions\when( 'pll_register_string' )->alias(
			static function ( $name, $string, $context = '', $multiline = false ): void {
				self::$registered[ (string) $name ] = (string) $string;
			}
		);
		// Polylang's WPML compatibility layer provides both of these even
		// though WPML itself is not installed.
		Functions\when( 'has_action' )->justReturn( true );
		Functions\when( 'icl_unregister_string' )->alias(
			static function ( $context, $name ): void {
				self::$unregistered[] = (string) $name;
			}
		);
	}

	protected function tearDown(): void {
		self::$registered   = [];
		self::$unregistered = [];
		self::$wpml_store   = [];
		Options::clear_cache();
		parent::tearDown();
	}

	public function test_does_not_also_register_through_the_wpml_api_when_polylang_is_active(): void {
		Actions\expectDone( 'wpml_register_single_string' )->never();

		StringRegistry::register_all();

		$this->assertSame( 'We value your privacy', self::$registered['banner_title'] ?? null );
	}

	public function test_removes_the_wpml_duplicates_polylang_stored_for_our_strings(): void {
		self::$wpml_store = [
			md5( 'LW Cookie | banner_title' ) => [
				'context' => 'LW Cookie',
				'name'    => 'banner_title',
				'string'  => 'We value your privacy',
			],
			md5( 'Widget | Widget title' )    => [
				'context' => 'Widget',
				'name'    => 'Widget title',
				'string'  => 'Shop',
			],
		];

		StringRegistry::register_all();

		$this->assertSame( [ 'banner_title' ], self::$unregistered );
	}
}
