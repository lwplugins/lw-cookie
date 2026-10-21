<?php
/**
 * Tests for CookiePattern — the cookie name syntax shared by PHP and guard.js.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Cookies;

use LightweightPlugins\Cookie\Cookies\CookiePattern;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\Cookie\Cookies\CookiePattern
 */
final class CookiePatternTest extends TestCase {

	public function test_exact_name_matches_only_itself(): void {
		$this->assertSame( PHP_INT_MAX, CookiePattern::specificity( 'fr', 'fr' ) );
		$this->assertSame( -1, CookiePattern::specificity( 'fr', 'fruit_pref' ) );
		$this->assertSame( -1, CookiePattern::specificity( 'fr', 'frontend_lang' ) );
	}

	public function test_trailing_underscore_is_a_prefix_and_counts_the_underscore(): void {
		$this->assertSame( 4, CookiePattern::specificity( '_ga_', '_ga_ABC' ) );
		$this->assertSame( -1, CookiePattern::specificity( '_ga_', '_ga' ) );
	}

	public function test_trailing_star_is_a_prefix_and_is_not_counted(): void {
		$this->assertSame( 5, CookiePattern::specificity( 'lwfw_*', 'lwfw_v' ) );
		$this->assertSame( -1, CookiePattern::specificity( 'lwfw_*', 'lwfw' ) );
	}

	public function test_matching_is_case_sensitive(): void {
		$this->assertSame( -1, CookiePattern::specificity( 'IDE', 'ide' ) );
	}

	public function test_is_valid(): void {
		$this->assertFalse( CookiePattern::is_valid( 'a*b' ) );
		$this->assertFalse( CookiePattern::is_valid( 'a b' ) );
		$this->assertFalse( CookiePattern::is_valid( 'a=b' ) );
		$this->assertFalse( CookiePattern::is_valid( 'a;b' ) );
		$this->assertFalse( CookiePattern::is_valid( 'a,b' ) );
		$this->assertFalse( CookiePattern::is_valid( '' ) );
		$this->assertTrue( CookiePattern::is_valid( 'lwfw_*' ) );
		$this->assertTrue( CookiePattern::is_valid( '_ga_' ) );
		$this->assertTrue( CookiePattern::is_valid( 'fr' ) );
	}

	public function test_prefix_stem_must_be_at_least_two_characters(): void {
		foreach ( [ '*', '_', 'a*', 'a_' ] as $bad ) {
			$this->assertFalse( CookiePattern::is_valid( $bad ), $bad );
		}
		foreach ( [ 'ab_', 'ab*', 'ab' ] as $good ) {
			$this->assertTrue( CookiePattern::is_valid( $good ), $good );
		}
	}

	public function test_longer_prefix_and_exact_name_win(): void {
		$name = '_ga_custom_x';
		$this->assertGreaterThan(
			CookiePattern::specificity( '_ga_', $name ),
			CookiePattern::specificity( '_ga_custom_', $name )
		);
		$this->assertGreaterThan(
			CookiePattern::specificity( '_ga_custom_*', $name ),
			CookiePattern::specificity( $name, $name )
		);
	}
}
