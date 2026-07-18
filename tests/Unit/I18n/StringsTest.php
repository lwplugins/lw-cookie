<?php
/**
 * Tests for I18n\Strings default localisation.
 *
 * Covers issue #7: built-in English defaults must follow the site language
 * (text-domain translated) until the admin overrides them.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\I18n;

use Brain\Monkey\Functions;
use LightweightPlugins\Cookie\I18n\Strings;
use LightweightPlugins\Cookie\Options;
use LightweightPlugins\Cookie\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Cookie\I18n\Strings
 */
final class StringsTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();

		Functions\when( 'wp_parse_args' )->alias(
			static fn( $args, $defaults = [] ) => array_merge( (array) $defaults, (array) $args )
		);
		// No multilingual plugin active in these tests.
		Functions\when( 'has_filter' )->justReturn( false );
		// __() applies a fake "site language" translation for one known default.
		Functions\when( '__' )->alias(
			static fn( $text, $domain = null ) => 'We value your privacy' === $text ? 'Adatvédelem' : $text
		);
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	public function test_untouched_default_is_returned_localized(): void {
		Functions\when( 'get_option' )->justReturn( [] ); // Nothing customised.

		$this->assertSame( 'Adatvédelem', Strings::get( 'banner_title' ) );
	}

	public function test_customized_value_is_not_replaced_by_the_localized_default(): void {
		Functions\when( 'get_option' )->justReturn( [ 'banner_title' => 'My custom title' ] );

		$this->assertSame( 'My custom title', Strings::get( 'banner_title' ) );
	}

	public function test_key_without_a_localizable_default_returns_the_stored_value(): void {
		Functions\when( 'get_option' )->justReturn( [ 'policy_version' => '2.0' ] );

		$this->assertSame( '2.0', Strings::get( 'policy_version' ) );
	}
}
