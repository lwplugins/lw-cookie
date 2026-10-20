<?php
/**
 * Tests for ScriptMarkup.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Blocking;

use LightweightPlugins\Cookie\Blocking\ScriptMarkup;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\Cookie\Blocking\ScriptMarkup
 */
final class ScriptMarkupTest extends TestCase {

	public function test_map_start_tags_touches_only_top_level_start_tags(): void {
		$html = '<script id="a">var s = "<script id=\"inner\">";</script><script id="b" src="x.js"></script>';

		$out = ScriptMarkup::map_start_tags( $html, static fn( string $start ): string => strtoupper( $start ) );

		$this->assertSame( '<SCRIPT ID="A">var s = "<script id=\"inner\">";</script><SCRIPT ID="B" SRC="X.JS"></script>', $out );
	}

	public function test_huge_inline_body_is_kept_and_start_tags_still_rewritten(): void {
		$body = str_repeat( 'var a = "x < y"; ', 66000 ); // About 1.1 MB.
		$html = '<script id="a">' . $body . '</script><script id="b" src="x.js"></script>';

		$out = ScriptMarkup::map_start_tags( $html, static fn( string $start ): string => '<script data-x="1"' . substr( $start, 7 ) );

		$this->assertSame( '<script data-x="1" id="a">' . $body . '</script><script data-x="1" id="b" src="x.js"></script>', $out );
	}

	public function test_failed_match_returns_the_markup_unchanged(): void {
		$html  = '<script id="a">' . str_repeat( 'x', 1000 ) . '</script>';
		$limit = ini_get( 'pcre.backtrack_limit' );
		$jit   = ini_get( 'pcre.jit' );
		ini_set( 'pcre.backtrack_limit', '1' );
		ini_set( 'pcre.jit', '0' );

		try {
			$out = ScriptMarkup::map_start_tags( $html, static fn( string $start ): string => 'CHANGED' );
		} finally {
			ini_set( 'pcre.backtrack_limit', (string) $limit );
			ini_set( 'pcre.jit', (string) $jit );
		}

		$this->assertSame( $html, $out );
	}

	public function test_attributes_reads_quoted_unquoted_and_boolean_values(): void {
		$this->assertSame(
			[
				'id'    => 'a',
				'src'   => 'x.js?a=1&#038;b=2',
				'async' => '',
				'type'  => 'module',
			],
			ScriptMarkup::attributes( '<script id="a" src=\'x.js?a=1&#038;b=2\' async type=module>' )
		);
	}

	/**
	 * @return array<string, array{0: string, 1: bool}>
	 */
	public static function types(): array {
		return [
			'empty'                  => [ '', true ],
			'text/javascript'        => [ 'text/javascript', true ],
			'application/javascript' => [ 'Application/JavaScript', true ],
			'x-javascript'           => [ 'application/x-javascript', true ],
			'ecmascript'             => [ 'text/ecmascript', true ],
			'module'                 => [ 'module', false ],
			'json'                   => [ 'application/json', false ],
			'ld+json'                => [ 'application/ld+json', false ],
			'template'               => [ 'text/template', false ],
		];
	}

	/**
	 * @dataProvider types
	 *
	 * @param string $type     Type attribute value.
	 * @param bool   $expected Whether it is classic JavaScript.
	 */
	public function test_is_classic_js( string $type, bool $expected ): void {
		$this->assertSame( $expected, ScriptMarkup::is_classic_js( $type ) );
	}
}
