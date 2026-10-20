<?php
/**
 * Tests for UrlCategory.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Blocking;

use LightweightPlugins\Cookie\Blocking\UrlCategory;
use LightweightPlugins\Cookie\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Cookie\Blocking\UrlCategory
 */
final class UrlCategoryTest extends MonkeyTestCase {

	public function test_exact_and_subdomain_match(): void {
		$this->assertSame(
			'analytics',
			UrlCategory::for_url( 'https://www.googletagmanager.com/gtag/js?id=G-1', [ 'googletagmanager.com' => 'analytics' ], 'example.test' )
		);
		$this->assertSame(
			'analytics',
			UrlCategory::for_url( 'https://a.b.hotjar.com/x.js', [ 'hotjar.com' => 'analytics' ], 'example.test' )
		);
		$this->assertNull(
			UrlCategory::for_url( 'https://nothotjar.com/x.js', [ 'hotjar.com' => 'analytics' ], 'example.test' )
		);
	}

	public function test_path_pattern_matches_anywhere_in_url(): void {
		$this->assertSame(
			'functional',
			UrlCategory::for_url( 'https://www.google.com/maps/embed?pb=1', [ 'google.com/maps' => 'functional' ], 'example.test' )
		);
	}

	public function test_first_match_wins(): void {
		$this->assertSame(
			'necessary',
			UrlCategory::for_url(
				'https://code.tidio.co/x.js',
				[
					'code.tidio.co' => 'necessary',
					'tidio.co'      => 'marketing',
				],
				'example.test'
			)
		);
	}

	public function test_same_origin_relative_and_protocol_relative(): void {
		$domains = [ 'connect.facebook.net' => 'marketing' ];

		$this->assertNull( UrlCategory::for_url( 'https://example.test/wp-content/x.js', $domains, 'example.test' ) );
		$this->assertNull( UrlCategory::for_url( '/wp-content/x.js', $domains, 'example.test' ) );
		$this->assertSame(
			'marketing',
			UrlCategory::for_url( '//connect.facebook.net/en_US/fbevents.js', $domains, 'example.test' )
		);
	}
}
