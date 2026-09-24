<?php
/**
 * SettingsStore unit tests.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use LightweightPlugins\Cookie\Admin\SettingsStore;
use LightweightPlugins\Cookie\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Cookie\Admin\SettingsStore
 * @covers \LightweightPlugins\Cookie\Admin\SettingsSanitizer
 */
final class SettingsStoreTest extends MonkeyTestCase {

	private const DEFAULTS = [
		'enabled'            => true,
		'gcm_enabled'        => false,
		'consent_duration'   => 365,
		'banner_position'    => 'bottom',
		'primary_color'      => '#2271b1',
		'cat_analytics_name' => 'Analytics',
		'cat_analytics_desc' => 'Analytics desc',
		'banner_title'       => 'We value your privacy',
		'banner_message'     => 'We use cookies.',
		'declared_cookies'   => [],
	];

	private const LOCKED = [ 'cat_analytics_name', 'cat_analytics_desc', 'banner_title', 'banner_message' ];

	private const TEXTAREA = [ 'cat_analytics_desc', 'banner_message' ];

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'sanitize_text_field' )->alias( static fn( $v ): string => trim( (string) preg_replace( '/\s+/', ' ', strip_tags( (string) $v ) ) ) );
		Functions\when( 'sanitize_textarea_field' )->alias( static fn( $v ): string => trim( strip_tags( (string) $v ) ) );
		Functions\when( 'absint' )->alias( static fn( $v ): int => abs( (int) $v ) );
		Functions\when( 'sanitize_hex_color' )->alias(
			static fn( $v ) => '' === $v ? '' : ( preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', (string) $v ) ? $v : null )
		);
	}

	/**
	 * Merge with no locked keys.
	 *
	 * @param array<string, mixed> $body   Submitted keys.
	 * @param mixed                $stored Stored option.
	 * @return array<string, mixed>
	 */
	private function merge( array $body, $stored = [] ): array {
		return SettingsStore::merge( $body, $stored, self::DEFAULTS, [], self::TEXTAREA );
	}

	public function test_merge_keeps_stored_values_for_absent_keys(): void {
		$stored = [
			'enabled'      => false,
			'gcm_enabled'  => true,
			'banner_title' => 'Custom title',
		];

		$result = $this->merge( [ 'consent_duration' => 30 ], $stored );

		$this->assertSame(
			array_merge( self::DEFAULTS, $stored, [ 'consent_duration' => 30 ] ),
			$result
		);
	}

	public function test_merge_applies_submitted_false(): void {
		$result = $this->merge( [ 'enabled' => false ], [ 'enabled' => true ] );

		$this->assertFalse( $result['enabled'] );
	}

	public function test_merge_drops_unknown_keys(): void {
		$result = $this->merge( [ 'evil' => 'x', '_locale' => 'user' ], [ 'legacy' => 'y' ] );

		$this->assertSame( array_keys( self::DEFAULTS ), array_keys( $result ) );
	}

	public function test_merge_ignores_locked_keys(): void {
		$stored = [ 'banner_title' => 'Source title' ];

		$result = SettingsStore::merge(
			[ 'banner_title' => 'Changed', 'cat_analytics_desc' => 'Changed', 'gcm_enabled' => true ],
			$stored,
			self::DEFAULTS,
			self::LOCKED,
			self::TEXTAREA
		);

		$this->assertSame(
			[ 'Source title', 'Analytics desc', true ],
			[ $result['banner_title'], $result['cat_analytics_desc'], $result['gcm_enabled'] ]
		);
	}

	public function test_merge_writes_translatable_keys_when_nothing_is_locked(): void {
		$result = $this->merge( [ 'banner_title' => 'Changed' ] );

		$this->assertSame( 'Changed', $result['banner_title'] );
	}

	/**
	 * @return array<string, array{0: mixed, 1: string}>
	 */
	public static function colour_provider(): array {
		return [
			'valid colour stored'   => [ '#ff0000', '#ff0000' ],
			'short colour stored'   => [ '#f00', '#f00' ],
			'invalid keeps stored'  => [ 'red', '#123456' ],
			'empty keeps stored'    => [ '', '#123456' ],
			'array keeps stored'    => [ [ '#fff' ], '#123456' ],
		];
	}

	/**
	 * @dataProvider colour_provider
	 *
	 * @param mixed  $submitted Submitted colour.
	 * @param string $expected  Stored colour.
	 */
	public function test_merge_sanitizes_colours( $submitted, string $expected ): void {
		$result = $this->merge( [ 'primary_color' => $submitted ], [ 'primary_color' => '#123456' ] );

		$this->assertSame( $expected, $result['primary_color'] );
	}

	/**
	 * @return array<string, array{0: mixed, 1: string}>
	 */
	public static function choice_provider(): array {
		return [
			'allowed value stored' => [ 'modal', 'modal' ],
			'unknown keeps stored' => [ 'left', 'top' ],
			'markup keeps stored'  => [ '<b>top</b>', 'top' ],
		];
	}

	/**
	 * @dataProvider choice_provider
	 *
	 * @param mixed  $submitted Submitted value.
	 * @param string $expected  Stored value.
	 */
	public function test_merge_whitelists_select_values( $submitted, string $expected ): void {
		$result = $this->merge( [ 'banner_position' => $submitted ], [ 'banner_position' => 'top' ] );

		$this->assertSame( $expected, $result['banner_position'] );
	}

	public function test_merge_keeps_line_breaks_in_textarea_fields(): void {
		$result = $this->merge( [ 'banner_message' => "Line one\nLine two", 'cat_analytics_desc' => "A\nB" ] );

		$this->assertSame( [ "Line one\nLine two", "A\nB" ], [ $result['banner_message'], $result['cat_analytics_desc'] ] );
	}

	public function test_merge_flattens_single_line_text_fields(): void {
		$result = $this->merge( [ 'banner_title' => "<b>Hello</b>\nworld" ] );

		$this->assertSame( 'Hello world', $result['banner_title'] );
	}

	public function test_merge_casts_ints_with_absint(): void {
		$result = $this->merge( [ 'consent_duration' => '-30' ] );

		$this->assertSame( 30, $result['consent_duration'] );
	}

	public function test_merge_replaces_declared_cookies_as_a_whole(): void {
		$stored = [ 'declared_cookies' => [ [ 'name' => 'old', 'provider' => '', 'purpose' => '', 'duration' => '', 'category' => 'necessary', 'type' => 'session' ] ] ];
		$body   = [
			'declared_cookies' => [
				[ 'name' => '<i>_ga</i>', 'provider' => 'Google', 'purpose' => 'Stats', 'duration' => '2 years', 'category' => 'analytics', 'type' => 'persistent' ],
				[ 'name' => '', 'provider' => 'Nameless' ],
				'not a row',
				[ 'name' => 'x', 'category' => 'evil', 'type' => 'forever', 'purpose' => [ 'bad' ] ],
			],
		];

		$result = $this->merge( $body, $stored );

		$this->assertSame(
			[
				[ 'name' => '_ga', 'provider' => 'Google', 'purpose' => 'Stats', 'duration' => '2 years', 'category' => 'analytics', 'type' => 'persistent' ],
				[ 'name' => 'x', 'provider' => '', 'purpose' => '', 'duration' => '', 'category' => 'necessary', 'type' => 'persistent' ],
			],
			$result['declared_cookies']
		);
	}

	public function test_merge_keeps_declared_cookies_when_value_is_not_a_list(): void {
		$stored = [ 'declared_cookies' => [ [ 'name' => 'keep' ] ] ];

		$result = $this->merge( [ 'declared_cookies' => 'oops' ], $stored );

		$this->assertSame( [ [ 'name' => 'keep' ] ], $result['declared_cookies'] );
	}

	public function test_merge_treats_a_non_array_stored_value_as_empty(): void {
		$result = $this->merge( [], false );

		$this->assertSame( self::DEFAULTS, $result );
	}

	public function test_typed_casts_values_like_their_defaults(): void {
		$values = [
			'enabled'          => '1',
			'consent_duration' => '30',
			'banner_title'     => 5,
			'declared_cookies' => [ 3 => [ 'name' => 'a' ], 4 => 'junk' ],
		];

		$result = SettingsStore::typed( $values, self::DEFAULTS );

		$this->assertSame(
			[ true, 30, '5', [ [ 'name' => 'a' ] ] ],
			[ $result['enabled'], $result['consent_duration'], $result['banner_title'], $result['declared_cookies'] ]
		);
	}

	public function test_typed_fills_missing_keys_with_defaults(): void {
		$this->assertSame( self::DEFAULTS, SettingsStore::typed( [], self::DEFAULTS ) );
	}
}
