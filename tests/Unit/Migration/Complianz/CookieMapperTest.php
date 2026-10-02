<?php
/**
 * CookieMapper unit tests.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Migration\Complianz;

use LightweightPlugins\Cookie\Migration\Complianz\CookieMapper;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\Cookie\Migration\Complianz\CookieMapper
 */
final class CookieMapperTest extends TestCase {

	private const SERVICES = [
		1 => [
			'ID'       => 1,
			'name'     => 'Google Analytics',
			'category' => 'Statistics',
		],
	];

	/**
	 * Cookie row with defaults.
	 *
	 * @param array<string, mixed> $row Overrides.
	 * @return array<string, mixed>
	 */
	private static function cookie( array $row ): array {
		return array_merge(
			[
				'ID'                => 1,
				'name'              => '_ga',
				'serviceID'         => 1,
				'purpose'           => 'Statistics',
				'retention'         => '2 years',
				'cookieFunction'    => 'store and count pageviews',
				'language'          => 'en',
				'isTranslationFrom' => 0,
			],
			$row
		);
	}

	public function test_maps_parent_row_with_service_provider(): void {
		$result = CookieMapper::map( [ self::cookie( [] ) ], self::SERVICES, 'en' );

		$this->assertSame(
			[
				[
					'name'     => '_ga',
					'provider' => 'Google Analytics',
					'purpose'  => 'store and count pageviews',
					'duration' => '2 years',
					'category' => 'analytics',
					'type'     => 'persistent',
				],
			],
			$result
		);
	}

	public function test_prefers_site_language_texts_but_keeps_parent_category(): void {
		$rows = [
			self::cookie( [] ),
			self::cookie(
				[
					'ID'                => 2,
					'language'          => 'hu',
					'isTranslationFrom' => 1,
					'purpose'           => 'Statisztika',
					'retention'         => '2 év',
					'cookieFunction'    => 'oldalmegtekintések számlálása',
				]
			),
		];

		$result = CookieMapper::map( $rows, self::SERVICES, 'hu' );

		$this->assertCount( 1, $result );
		$this->assertSame( 'oldalmegtekintések számlálása', $result[0]['purpose'] );
		$this->assertSame( '2 év', $result[0]['duration'] );
		$this->assertSame( 'analytics', $result[0]['category'] );
	}

	public function test_falls_back_to_service_category_and_detects_session(): void {
		$row = self::cookie(
			[
				'purpose'   => '',
				'retention' => 'session',
			]
		);

		$result = CookieMapper::map( [ $row ], self::SERVICES, 'en' );

		$this->assertSame( 'analytics', $result[0]['category'] );
		$this->assertSame( 'session', $result[0]['type'] );
	}

	public function test_skips_orphan_translations_and_duplicate_names(): void {
		$rows = [
			self::cookie( [] ),
			self::cookie( [ 'ID' => 5 ] ),
			self::cookie(
				[
					'ID'                => 9,
					'name'              => '_orphan',
					'isTranslationFrom' => 42,
				]
			),
		];

		$this->assertSame( [ '_ga' ], array_column( CookieMapper::map( $rows, [], 'en' ), 'name' ) );
	}

	/**
	 * @dataProvider provide_labels
	 *
	 * @param string $label    Complianz label.
	 * @param string $expected LW Cookie category.
	 */
	public function test_maps_category_label( string $label, string $expected ): void {
		$this->assertSame( $expected, CookieMapper::category( $label ) );
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function provide_labels(): array {
		return [
			'marketing/tracking'     => [ 'Marketing/Tracking', 'marketing' ],
			'statistics (anonymous)' => [ 'Statistics (anonymous)', 'analytics' ],
			'preferences'            => [ 'Preferences', 'functional' ],
			'functional'             => [ 'Functional', 'necessary' ],
			'unknown label'          => [ 'Security', 'necessary' ],
			'empty'                  => [ '', '' ],
		];
	}
}
