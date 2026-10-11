<?php
/**
 * Tests for DataLayerNames.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Blocking;

use LightweightPlugins\Cookie\Blocking\DataLayerNames;
use LightweightPlugins\Cookie\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Cookie\Blocking\DataLayerNames
 */
final class DataLayerNamesTest extends MonkeyTestCase {

	/**
	 * Stand in for the rewriting plugin's settings object.
	 *
	 * @param bool                $enabled Whether its Google tag is configured.
	 * @param array<string,mixed> $options Its saved options.
	 * @return array<int, string>
	 */
	private function extra_for( bool $enabled, array $options ): array {
		$settings = new class( $enabled, $options ) {
			/** @var array<string,mixed> */
			private array $options;
			private bool $enabled;

			/** @param array<string,mixed> $options */
			public function __construct( bool $enabled, array $options ) {
				$this->enabled = $enabled;
				$this->options = $options;
			}

			public function enabled(): bool {
				return $this->enabled;
			}

			/** @return mixed */
			public function getOption( string $key ) {
				return $this->options[ $key ] ?? null;
			}
		};

		return DataLayerNames::from_settings( $settings );
	}

	public function test_no_extra_layer_when_the_plugin_is_not_active(): void {
		$this->assertSame( [], DataLayerNames::from_settings( null ) );
	}

	public function test_no_extra_layer_for_an_object_without_the_expected_methods(): void {
		$this->assertSame( [], DataLayerNames::from_settings( new \stdClass() ) );
	}

	public function test_default_setting_adds_the_renamed_layer(): void {
		$this->assertSame( [ 'dataLayerPYS' ], $this->extra_for( true, [ 'gtag_datalayer_type' => 'default' ] ) );
	}

	public function test_custom_setting_adds_the_chosen_name(): void {
		$this->assertSame(
			[ 'shopLayer' ],
			$this->extra_for(
				true,
				[
					'gtag_datalayer_type' => 'custom',
					'gtag_datalayer_name' => 'shopLayer',
				]
			)
		);
	}

	public function test_disabled_renaming_adds_nothing(): void {
		$this->assertSame( [], $this->extra_for( true, [ 'gtag_datalayer_type' => 'disable' ] ) );
	}

	public function test_unconfigured_google_tag_adds_nothing(): void {
		$this->assertSame( [], $this->extra_for( false, [ 'gtag_datalayer_type' => 'default' ] ) );
	}

	/**
	 * @dataProvider unusable_names
	 */
	public function test_unusable_custom_names_are_dropped( mixed $name ): void {
		$this->assertSame(
			[],
			$this->extra_for(
				true,
				[
					'gtag_datalayer_type' => 'custom',
					'gtag_datalayer_name' => $name,
				]
			)
		);
	}

	/**
	 * @return array<string, array{mixed}>
	 */
	public static function unusable_names(): array {
		return [
			'empty'              => [ '' ],
			'null'               => [ null ],
			'not a string'       => [ [ 'x' ] ],
			'plain data layer'   => [ 'dataLayer' ],
			'has a quote'        => [ "a'b" ],
			'starts with digit'  => [ '1layer' ],
			'has a space'        => [ 'my layer' ],
		];
	}
}
