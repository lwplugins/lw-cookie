<?php
/**
 * Tests for RegisteredCookies — the lw_cookie_known_cookies filter and its validation.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Cookies;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use LightweightPlugins\Cookie\Cookies\RegisteredCookies;
use LightweightPlugins\Cookie\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Cookie\Cookies\RegisteredCookies
 */
final class RegisteredCookiesTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'sanitize_text_field' )->returnArg();
	}

	/**
	 * Make the filter return the given value.
	 *
	 * @param mixed $value Filter result.
	 */
	private function register( mixed $value ): void {
		Filters\expectApplied( 'lw_cookie_known_cookies' )->with( [] )->zeroOrMoreTimes()->andReturn( $value );
	}

	/**
	 * Expect N _doing_it_wrong calls for the filter.
	 *
	 * @param int $times Number of calls.
	 */
	private function expect_wrong( int $times ): void {
		Functions\expect( '_doing_it_wrong' )
			->times( $times )
			->with( 'lw_cookie_known_cookies', \Mockery::type( 'string' ), '1.9.7' );
	}

	/**
	 * Build a valid entry.
	 *
	 * @param string $name     Name.
	 * @param string $category Category.
	 * @return array<string, string>
	 */
	private function entry( string $name, string $category = 'necessary' ): array {
		return [
			'name'     => $name,
			'provider' => 'LW',
			'purpose'  => 'Plugin',
			'duration' => '1 year',
			'category' => $category,
			'type'     => 'persistent',
		];
	}

	public function test_all_returns_a_valid_entry_with_all_keys(): void {
		$this->register( [ $this->entry( 'lwfw_v' ) ] );
		$this->expect_wrong( 0 );

		$this->assertSame( [ $this->entry( 'lwfw_v' ) ], RegisteredCookies::all() );
	}

	public function test_all_fills_missing_type_and_text_fields(): void {
		$this->register( [ [ 'name' => 'lwfw_v', 'category' => 'functional' ] ] );

		$this->assertSame(
			[
				[
					'name'     => 'lwfw_v',
					'provider' => '',
					'purpose'  => '',
					'duration' => '',
					'category' => 'functional',
					'type'     => 'persistent',
				],
			],
			RegisteredCookies::all()
		);
	}

	/**
	 * @dataProvider provide_bad_entries
	 * @param mixed $bad Bad entry.
	 */
	public function test_all_drops_a_bad_entry_with_one_notice( mixed $bad ): void {
		$this->register( [ $bad ] );
		$this->expect_wrong( 1 );

		$this->assertSame( [], RegisteredCookies::all() );
	}

	/**
	 * @return array<string, array<int, mixed>>
	 */
	public static function provide_bad_entries(): array {
		return [
			'unknown category' => [ [ 'name' => 'lwfw_v', 'category' => 'tracking' ] ],
			'star in middle'   => [ [ 'name' => 'a*b', 'category' => 'necessary' ] ],
			'missing name'     => [ [ 'category' => 'necessary' ] ],
			'non-array entry'  => [ 'lwfw_v' ],
		];
	}

	public function test_all_returns_empty_for_a_non_array_filter_result(): void {
		$this->register( 'x' );
		$this->expect_wrong( 1 );

		$this->assertSame( [], RegisteredCookies::all() );
	}

	public function test_duplicate_name_first_wins_silently(): void {
		$second            = $this->entry( 'lwfw_v', 'marketing' );
		$second['purpose'] = 'Second';
		$this->register( [ $this->entry( 'lwfw_v' ), $second ] );
		$this->expect_wrong( 0 );

		$all = RegisteredCookies::all();

		$this->assertCount( 1, $all );
		$this->assertSame( 'necessary', $all[0]['category'] );
	}

	public function test_find_returns_the_exact_entry(): void {
		$this->register( [ $this->entry( 'lwfw_v' ) ] );

		$this->assertSame( 'lwfw_v', RegisteredCookies::find( 'lwfw_v' )['name'] ?? null );
	}

	public function test_find_prefers_exact_over_prefix(): void {
		$this->register( [ $this->entry( 'lwfw_*', 'functional' ), $this->entry( 'lwfw_v' ) ] );

		$this->assertSame( 'lwfw_v', RegisteredCookies::find( 'lwfw_v' )['name'] ?? null );
	}

	public function test_find_prefers_the_longer_prefix(): void {
		$this->register( [ $this->entry( '_ga_', 'analytics' ), $this->entry( '_ga_custom_' ) ] );

		$this->assertSame( '_ga_custom_', RegisteredCookies::find( '_ga_custom_x' )['name'] ?? null );
	}

	public function test_find_returns_null_without_a_match(): void {
		$this->register( [ $this->entry( 'lwfw_v' ) ] );

		$this->assertNull( RegisteredCookies::find( 'other' ) );
	}

	public function test_scan_lookup_adds_the_plugin_source(): void {
		$this->register( [ $this->entry( 'lwfw_v' ) ] );

		$this->assertSame( 'plugin', RegisteredCookies::scan_lookup( 'lwfw_v' )['source'] ?? null );
		$this->assertNull( RegisteredCookies::scan_lookup( 'other' ) );
	}

	public function test_merge_keeps_the_manual_row_but_takes_the_necessary_category(): void {
		$this->register( [ array_merge( $this->entry( 'lwfw_v' ), [ 'purpose' => 'Plugin' ] ) ] );
		$manual = [ array_merge( $this->entry( 'lwfw_v', 'functional' ), [ 'purpose' => 'Manual' ] ) ];

		$merged = RegisteredCookies::merge_into( $manual );

		$this->assertCount( 1, $merged );
		$this->assertSame( 'Manual', $merged[0]['purpose'] );
		$this->assertSame( 'necessary', $merged[0]['category'] );
	}

	public function test_merge_keeps_a_manual_analytics_row_over_a_marketing_entry(): void {
		$this->register( [ $this->entry( 'lwfw_v', 'marketing' ) ] );
		$manual = [ $this->entry( 'lwfw_v', 'analytics' ) ];

		$this->assertSame( $manual, RegisteredCookies::merge_into( $manual ) );
	}

	public function test_merge_appends_unmatched_entries_after_manual_rows(): void {
		$this->register( [ $this->entry( 'lwfw_v' ), $this->entry( 'ABC' ) ] );
		$manual = [ $this->entry( 'abc', 'functional' ) ];

		$names = array_column( RegisteredCookies::merge_into( $manual ), 'name' );

		$this->assertSame( [ 'abc', 'lwfw_v', 'ABC' ], $names );
	}

	public function test_merge_without_registered_cookies_returns_the_declaration_unchanged(): void {
		$manual = [ $this->entry( 'abc', 'functional' ) ];

		$this->assertSame( $manual, RegisteredCookies::merge_into( $manual ) );
	}

	public function test_guard_map_overrides_builtin_keys_and_appends_new_ones(): void {
		$this->register( [ $this->entry( '_ga_custom_' ), $this->entry( 'fr' ) ] );

		$this->assertSame(
			[
				'_ga_'        => 'analytics',
				'fr'          => 'necessary',
				'_ga_custom_' => 'necessary',
			],
			RegisteredCookies::guard_map( [ '_ga_' => 'analytics', 'fr' => 'marketing' ] )
		);
	}

	public function test_merge_into_tolerates_incomplete_manual_rows(): void {
		$this->register( [ $this->entry( 'lwfw_v' ) ] );

		$merged = RegisteredCookies::merge_into( [ [ 'category' => 'functional' ], 'junk' ] );

		$this->assertCount( 3, $merged );
		$this->assertSame( 'lwfw_v', $merged[2]['name'] );
	}
}
