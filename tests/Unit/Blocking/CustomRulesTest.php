<?php
/**
 * CustomRules unit tests.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Blocking;

use LightweightPlugins\Cookie\Blocking\CustomRules;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\Cookie\Blocking\CustomRules
 */
final class CustomRulesTest extends TestCase {

	/**
	 * @dataProvider provide_patterns
	 *
	 * @param mixed       $raw      Submitted pattern.
	 * @param string|null $expected Normalized pattern.
	 */
	public function test_normalizes_pattern( $raw, ?string $expected ): void {
		$this->assertSame( $expected, CustomRules::pattern( $raw ) );
	}

	/**
	 * @return array<string, array{0: mixed, 1: string|null}>
	 */
	public static function provide_patterns(): array {
		return [
			'plain domain'           => [ 'code.tidio.co', 'code.tidio.co' ],
			'url with scheme, www'   => [ 'https://www.Example.com/', 'example.com' ],
			'protocol-relative path' => [ '//pixel.barion.com/bp.js?v=2#x', 'pixel.barion.com/bp.js' ],
			'path kept'              => [ 'example.com/tracking/pixel.js', 'example.com/tracking/pixel.js' ],
			'no dot'                 => [ 'localhost', null ],
			'wildcard'               => [ '*.example.com', null ],
			'quote in path'          => [ 'example.com/a"b', null ],
			'empty'                  => [ '', null ],
			'not a string'           => [ [ 'x' ], null ],
		];
	}

	public function test_sanitize_drops_invalid_rows_and_keeps_first_duplicate(): void {
		$rules = [
			[
				'pattern'  => 'code.tidio.co',
				'category' => 'functional',
			],
			[
				'pattern'  => 'https://code.tidio.co/',
				'category' => 'marketing',
			],
			[
				'pattern'  => 'example.com',
				'category' => 'ads',
			],
			[
				'pattern'  => 'nodot',
				'category' => 'marketing',
			],
			'not a row',
		];

		$this->assertSame(
			[
				[
					'pattern'  => 'code.tidio.co',
					'category' => 'functional',
				],
			],
			CustomRules::sanitize( $rules )
		);
	}

	public function test_to_domains_maps_pattern_to_category(): void {
		$rules = [
			[
				'pattern'  => 'pixel.barion.com',
				'category' => 'marketing',
			],
		];

		$this->assertSame( [ 'pixel.barion.com' => 'marketing' ], CustomRules::to_domains( $rules ) );
	}

	public function test_to_domains_ignores_non_array(): void {
		$this->assertSame( [], CustomRules::to_domains( 'garbage' ) );
	}
}
