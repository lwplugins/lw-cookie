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

	/**
	 * Fake Polylang string table: source => translation.
	 *
	 * @var array<string, string>
	 */
	private static array $pll_translations = [];

	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();
		self::$pll_translations = [];

		Functions\when( 'wp_parse_args' )->alias(
			static fn( $args, $defaults = [] ) => array_merge( (array) $defaults, (array) $args )
		);
		// WPML is never active in these tests; Polylang is emulated via pll__().
		Functions\when( 'has_filter' )->justReturn( false );
		// __() applies a fake "site language" translation for one known default.
		Functions\when( '__' )->alias(
			static fn( $text, $domain = null ) => 'We value your privacy' === $text ? 'Adatvédelem' : $text
		);
		// Polylang's pll__() returns the source unchanged when it holds no
		// translation for it — exactly like the real function.
		Functions\when( 'pll__' )->alias(
			static fn( $text ) => self::$pll_translations[ $text ] ?? $text
		);
	}

	protected function tearDown(): void {
		self::$pll_translations = [];
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

	public function test_polylang_translation_wins_over_the_localized_default(): void {
		Functions\when( 'get_option' )->justReturn( [] ); // Texts tab untouched (locked by Polylang).
		self::$pll_translations = [ 'We value your privacy' => 'Tiszteljük a magánszféráját' ];

		$this->assertSame( 'Tiszteljük a magánszféráját', Strings::get( 'banner_title' ) );
	}

	public function test_localized_default_is_used_when_polylang_holds_no_translation(): void {
		Functions\when( 'get_option' )->justReturn( [] );

		$this->assertSame( 'Adatvédelem', Strings::get( 'banner_title' ) );
	}

	public function test_polylang_translation_wins_for_a_customized_value(): void {
		Functions\when( 'get_option' )->justReturn( [ 'banner_title' => 'My custom title' ] );
		self::$pll_translations = [ 'My custom title' => 'Saját címem' ];

		$this->assertSame( 'Saját címem', Strings::get( 'banner_title' ) );
	}

	public function test_get_or_default_uses_the_polylang_translation(): void {
		Functions\when( 'get_option' )->justReturn( [] );
		self::$pll_translations = [ 'Cookie Preferences' => 'Süti beállítások' ];

		$this->assertSame(
			'Süti beállítások',
			Strings::get_or_default( 'modal_title', 'Textdomain modal title' )
		);
	}

	public function test_get_or_default_falls_back_to_the_textdomain_default_when_untranslated(): void {
		Functions\when( 'get_option' )->justReturn( [] );

		$this->assertSame(
			'Textdomain modal title',
			Strings::get_or_default( 'modal_title', 'Textdomain modal title' )
		);
	}
}
