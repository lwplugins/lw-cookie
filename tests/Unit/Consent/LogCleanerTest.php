<?php
/**
 * LogCleaner unit tests.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Consent;

use LightweightPlugins\Cookie\Consent\LogCleaner;
use LightweightPlugins\Cookie\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Cookie\Consent\LogCleaner
 */
final class LogCleanerTest extends MonkeyTestCase {

	/**
	 * @dataProvider provide_valid_days
	 *
	 * @param mixed $value    Raw --older-than value.
	 * @param int   $expected Parsed days.
	 */
	public function test_parse_days_accepts_a_positive_whole_number( $value, int $expected ): void {
		$this->assertSame( $expected, LogCleaner::parse_days( $value ) );
	}

	/**
	 * @return array<string, array{0: mixed, 1: int}>
	 */
	public static function provide_valid_days(): array {
		return [
			'one day'          => [ '1', 1 ],
			'a year'           => [ '365', 365 ],
			'integer'          => [ 30, 30 ],
			'surrounding blank' => [ ' 90 ', 90 ],
			'upper bound'      => [ (string) LogCleaner::MAX_DAYS, LogCleaner::MAX_DAYS ],
		];
	}

	/**
	 * @dataProvider provide_invalid_days
	 *
	 * @param mixed $value Raw --older-than value.
	 */
	public function test_parse_days_rejects_anything_else( $value ): void {
		$this->assertNull( LogCleaner::parse_days( $value ) );
	}

	/**
	 * @return array<string, array{0: mixed}>
	 */
	public static function provide_invalid_days(): array {
		return [
			'zero'                => [ '0' ],
			'negative'            => [ '-30' ],
			'not a number'        => [ 'abc' ],
			'number with unit'    => [ '30d' ],
			'decimal'             => [ '1.5' ],
			'empty string'        => [ '' ],
			'flag without value'  => [ true ],
			'null'                => [ null ],
			'over the upper bound' => [ (string) ( LogCleaner::MAX_DAYS + 1 ) ],
			'array'               => [ [ '30' ] ],
		];
	}

	/**
	 * @dataProvider provide_cutoffs
	 *
	 * @param int    $days     Days to keep.
	 * @param string $now      Local "now" (current_time( 'mysql' )).
	 * @param string $expected Cutoff.
	 */
	public function test_cutoff_is_now_minus_the_days_on_the_same_clock( int $days, string $now, string $expected ): void {
		$this->assertSame( $expected, LogCleaner::cutoff( $days, $now ) );
	}

	/**
	 * @return array<string, array{0: int, 1: string, 2: string}>
	 */
	public static function provide_cutoffs(): array {
		return [
			'thirty days'          => [ 30, '2026-11-25 10:15:00', '2026-10-26 10:15:00' ],
			'over a month end'     => [ 1, '2026-03-01 00:00:05', '2026-02-28 00:00:05' ],
			'across a DST change'  => [ 1, '2026-03-30 02:30:00', '2026-03-29 02:30:00' ],
			'a leap year'          => [ 365, '2028-12-31 23:59:59', '2028-01-01 23:59:59' ],
		];
	}
}
