<?php
/**
 * LogRetention unit tests.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Consent;

use LightweightPlugins\Cookie\Consent\LogRetention;
use LightweightPlugins\Cookie\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Cookie\Consent\LogRetention
 */
final class LogRetentionTest extends MonkeyTestCase {

	/**
	 * @dataProvider provide_days
	 *
	 * @param mixed $value    Stored or submitted value.
	 * @param int   $expected Days used.
	 */
	public function test_sanitize_days_clamps_to_zero_and_the_maximum( $value, int $expected ): void {
		$this->assertSame( $expected, LogRetention::sanitize_days( $value ) );
	}

	/**
	 * @return array<string, array{0: mixed, 1: int}>
	 */
	public static function provide_days(): array {
		return [
			'missing keeps forever' => [ null, 0 ],
			'zero keeps forever'    => [ 0, 0 ],
			'numeric string'        => [ '180', 180 ],
			'integer'               => [ 30, 30 ],
			'negative keeps forever' => [ '-30', 0 ],
			'not a number'          => [ 'forever', 0 ],
			'above the maximum'     => [ 99999, LogRetention::MAX_DAYS ],
			'decimal is truncated'  => [ '7.9', 7 ],
			'array'                 => [ [ 30 ], 0 ],
		];
	}

	/**
	 * @dataProvider provide_schedule_states
	 *
	 * @param int    $days      Configured retention.
	 * @param bool   $scheduled Whether the event is scheduled now.
	 * @param string $expected  Change to make.
	 */
	public function test_schedule_change_follows_the_setting( int $days, bool $scheduled, string $expected ): void {
		$this->assertSame( $expected, LogRetention::schedule_change( $days, $scheduled ) );
	}

	/**
	 * @return array<string, array{0: int, 1: bool, 2: string}>
	 */
	public static function provide_schedule_states(): array {
		return [
			'on, not scheduled yet'  => [ 30, false, LogRetention::CHANGE_SCHEDULE ],
			'on, already scheduled'  => [ 30, true, LogRetention::CHANGE_NONE ],
			'off, still scheduled'   => [ 0, true, LogRetention::CHANGE_UNSCHEDULE ],
			'off, nothing scheduled' => [ 0, false, LogRetention::CHANGE_NONE ],
		];
	}
}
