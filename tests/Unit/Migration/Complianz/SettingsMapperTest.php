<?php
/**
 * SettingsMapper unit tests.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Migration\Complianz;

use Brain\Monkey\Functions;
use LightweightPlugins\Cookie\Migration\Complianz\SettingsMapper;
use LightweightPlugins\Cookie\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Cookie\Migration\Complianz\SettingsMapper
 */
final class SettingsMapperTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'wp_strip_all_tags' )->alias( static fn( $v ): string => strip_tags( (string) $v ) );
	}

	public function test_maps_banner_texts_including_text_checkbox_values(): void {
		$banner = [
			'header'         => [
				'text' => 'Sütik kezelése',
				'show' => 1,
			],
			'message_optin'  => 'Sütiket használunk a <a href="#">jobb élményért</a> &amp; mérésért.',
			'accept'         => 'Elfogadom',
			'dismiss'        => [
				'text' => 'Elutasítom',
				'show' => 1,
			],
			'category_stats' => [
				'text' => 'Statisztika',
				'show' => 1,
			],
		];

		$patch = SettingsMapper::map( $banner, [], 0 );

		$this->assertSame(
			[
				'banner_title'       => 'Sütik kezelése',
				'banner_message'     => 'Sütiket használunk a jobb élményért & mérésért.',
				'btn_accept_all'     => 'Elfogadom',
				'btn_reject_all'     => 'Elutasítom',
				'cat_analytics_name' => 'Statisztika',
			],
			$patch
		);
	}

	public function test_maps_complianz_functional_category_to_necessary(): void {
		$patch = SettingsMapper::map(
			[
				'category_functional' => 'Funkcionális',
				'category_prefs'      => [ 'text' => 'Beállítások' ],
			],
			[],
			0
		);

		$this->assertSame( 'Funkcionális', $patch['cat_necessary_name'] );
		$this->assertSame( 'Beállítások', $patch['cat_functional_name'] );
	}

	/**
	 * @dataProvider provide_positions
	 *
	 * @param string               $position Complianz position.
	 * @param array<string, mixed> $expected Expected keys.
	 */
	public function test_maps_banner_position( string $position, array $expected ): void {
		$this->assertSame( $expected, SettingsMapper::map( [ 'position' => $position ], [], 0 ) );
	}

	/**
	 * @return array<string, array{0: string, 1: array<string, mixed>}>
	 */
	public static function provide_positions(): array {
		return [
			'center'       => [ 'center', [ 'banner_position' => 'modal' ] ],
			'bottom bar'   => [
				'bottom',
				[
					'banner_position' => 'bottom',
					'banner_layout'   => 'bar',
				],
			],
			'bottom left'  => [
				'bottom-left',
				[
					'banner_position'      => 'bottom',
					'banner_layout'        => 'box',
					'banner_box_alignment' => 'left',
				],
			],
			'unknown'      => [ 'top-ish', [] ],
		];
	}

	public function test_maps_valid_colors_and_pixel_radius_only(): void {
		$banner = [
			'colorpalette_background'    => [ 'color' => '#fafafa' ],
			'colorpalette_text'          => [ 'color' => 'red' ],
			'colorpalette_button_accept' => [ 'background' => '#E63946' ],
			'colorpalette_border_radius' => [
				'top'  => '8',
				'type' => 'px',
			],
		];

		$patch = SettingsMapper::map( $banner, [], 0 );

		$this->assertSame(
			[
				'background_color' => '#fafafa',
				'primary_color'    => '#E63946',
				'border_radius'    => '8',
			],
			$patch
		);
	}

	public function test_ignores_percent_border_radius(): void {
		$patch = SettingsMapper::map(
			[
				'colorpalette_border_radius' => [
					'top'  => '50',
					'type' => '%',
				],
			],
			[],
			0
		);

		$this->assertSame( [], $patch );
	}

	public function test_maps_general_options(): void {
		$options = [
			'cookie_expiry'     => '1000',
			'consent-mode'      => 'yes',
			'privacy-statement' => 'custom',
		];

		$patch = SettingsMapper::map( [], $options, 3 );

		$this->assertSame(
			[
				'consent_duration'    => 730,
				'gcm_enabled'         => true,
				'privacy_policy_page' => 3,
			],
			$patch
		);
	}

	public function test_skips_generated_privacy_statement(): void {
		$patch = SettingsMapper::map( [], [ 'privacy-statement' => 'generated' ], 3 );

		$this->assertArrayNotHasKey( 'privacy_policy_page', $patch );
	}
}
