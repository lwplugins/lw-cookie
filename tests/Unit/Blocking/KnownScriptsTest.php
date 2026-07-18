<?php
/**
 * Characterization tests for KnownScripts.
 *
 * Pins down the current script-categorisation behaviour (see
 * .claude/rules/tests.md) — pure logic, no WordPress required.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Blocking;

use LightweightPlugins\Cookie\Blocking\KnownScripts;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\Cookie\Blocking\KnownScripts
 */
final class KnownScriptsTest extends TestCase {

	/**
	 * A URL containing a known pattern maps to that script's category.
	 *
	 * @dataProvider provide_known_urls
	 *
	 * @param string $url      Script URL.
	 * @param string $expected Expected category.
	 */
	public function test_known_url_maps_to_category( string $url, string $expected ): void {
		$this->assertSame( $expected, KnownScripts::get_category_for_url( $url ) );
	}

	/**
	 * @return array<string, array{string, string}>
	 */
	public static function provide_known_urls(): array {
		return array(
			'google analytics.js' => array( 'https://www.google-analytics.com/analytics.js', 'analytics' ),
			'gtag'                 => array( 'https://www.googletagmanager.com/gtag/js?id=G-XXesc', 'analytics' ),
			'gtm'                  => array( 'https://www.googletagmanager.com/gtm.js?id=GTM-XXesc', 'analytics' ),
			'facebook pixel'       => array( 'https://connect.facebook.net/en_US/fbevents.js', 'marketing' ),
			'hotjar'               => array( 'https://static.hotjar.com/c/hotjar-123.js', 'analytics' ),
			'linkedin'             => array( 'https://snap.licdn.com/li.lms-analytics/insight.min.js', 'marketing' ),
		);
	}

	/**
	 * Unknown or empty URLs yield null.
	 *
	 * @dataProvider provide_unknown_urls
	 *
	 * @param string $url Script URL.
	 */
	public function test_unknown_url_returns_null( string $url ): void {
		$this->assertNull( KnownScripts::get_category_for_url( $url ) );
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function provide_unknown_urls(): array {
		return array(
			'own script'   => array( 'https://example.com/wp-content/themes/x/app.js' ),
			'empty'        => array( '' ),
			'partial host' => array( 'https://analytics.example.org/x.js' ),
		);
	}

	/**
	 * Matching is a plain substring test, so a pattern anywhere in the URL hits.
	 */
	public function test_match_is_substring_anywhere_in_the_url(): void {
		$this->assertSame(
			'marketing',
			KnownScripts::get_category_for_url( 'https://cdn.example.com/proxy?src=connect.facebook.net/x' )
		);
	}

	public function test_patterns_for_category_collects_all_matching_scripts(): void {
		$analytics = KnownScripts::get_patterns_for_category( 'analytics' );

		$this->assertContains( 'google-analytics.com/analytics.js', $analytics );
		$this->assertContains( 'static.hotjar.com', $analytics );
		$this->assertNotContains( 'connect.facebook.net', $analytics );
	}

	public function test_patterns_for_unknown_category_is_empty(): void {
		$this->assertSame( array(), KnownScripts::get_patterns_for_category( 'does-not-exist' ) );
	}
}
