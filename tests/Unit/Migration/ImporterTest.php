<?php
/**
 * Importer unit tests.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Migration;

use Brain\Monkey\Functions;
use LightweightPlugins\Cookie\Migration\Importer;
use LightweightPlugins\Cookie\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Cookie\Migration\Importer
 */
final class ImporterTest extends MonkeyTestCase {

	private const DEFAULTS = [
		'banner_title'     => 'We value your privacy',
		'btn_accept_all'   => 'Accept All',
		'primary_color'    => '#2271b1',
		'declared_cookies' => [],
	];

	private const DATA = [
		'banner'   => [
			'header'                     => [ 'text' => 'Sütik' ],
			'accept'                     => 'Accept All',
			'colorpalette_button_accept' => [ 'background' => '#e63946' ],
		],
		'cookies'  => [
			[
				'ID'      => 1,
				'name'    => '_ga',
				'purpose' => 'Statistics',
			],
			[
				'ID'      => 2,
				'name'    => '_fbp',
				'purpose' => 'Marketing',
			],
			[
				'ID'      => 3,
				'name'    => 'mystery',
				'purpose' => '',
			],
		],
		'services' => [],
		'language' => 'en',
	];

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'wp_strip_all_tags' )->alias( static fn( $v ): string => strip_tags( (string) $v ) );
		Functions\when( 'sanitize_text_field' )->alias( static fn( $v ): string => trim( (string) $v ) );
		Functions\when( 'sanitize_textarea_field' )->alias( static fn( $v ): string => trim( (string) $v ) );
		Functions\when( 'sanitize_hex_color' )->returnArg();
	}

	/**
	 * Plan against a stored option with one declared cookie.
	 *
	 * @param array<int, string> $locked Locked keys.
	 * @return array<string, mixed>
	 */
	private function plan( array $locked = [] ): array {
		$stored = [
			'declared_cookies' => [
				[
					'name'     => '_ga',
					'purpose'  => 'Own text',
					'category' => 'analytics',
				],
			],
		];

		return Importer::plan( self::DATA, $stored, self::DEFAULTS, $locked, [] );
	}

	public function test_reports_only_values_that_change(): void {
		$this->assertSame(
			[
				'banner_title'  => 'Sütik',
				'primary_color' => '#e63946',
			],
			$this->plan()['report']['settings']
		);
	}

	public function test_keeps_existing_cookies_and_appends_new_ones(): void {
		$plan = $this->plan();

		$this->assertSame( [ '_ga', '_fbp' ], array_column( $plan['options']['declared_cookies'], 'name' ) );
		$this->assertSame( 'Own text', $plan['options']['declared_cookies'][0]['purpose'] );
		$this->assertSame( [ '_fbp' ], $plan['report']['cookies_added'] );
		$this->assertSame( [ '_ga' ], $plan['report']['cookies_existing'] );
	}

	public function test_skips_and_counts_cookies_without_category(): void {
		$plan = $this->plan();

		$this->assertNotContains( 'mystery', array_column( $plan['options']['declared_cookies'], 'name' ) );
		$this->assertSame( 1, $plan['report']['cookies_skipped'] );
	}

	public function test_leaves_locked_keys_alone_and_reports_them(): void {
		$plan = $this->plan( [ 'banner_title' ] );

		$this->assertSame( 'We value your privacy', $plan['options']['banner_title'] );
		$this->assertSame( [ 'banner_title' ], $plan['report']['locked'] );
	}
}
