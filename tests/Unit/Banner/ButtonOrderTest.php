<?php
/**
 * Tests for Banner\ButtonOrder.
 *
 * Issue #27: the order of the first-layer banner buttons is configurable.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Banner;

use LightweightPlugins\Cookie\Banner\ButtonOrder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LightweightPlugins\Cookie\Banner\ButtonOrder
 */
final class ButtonOrderTest extends TestCase {

	/**
	 * @dataProvider provide_valid_orders
	 *
	 * @param mixed              $value    Stored value.
	 * @param array<int, string> $expected Resolved order.
	 */
	public function test_resolves_a_valid_order( mixed $value, array $expected ): void {
		$this->assertSame( $expected, ButtonOrder::resolve( $value ) );
	}

	/**
	 * @return array<string, array{0: mixed, 1: array<int, string>}>
	 */
	public static function provide_valid_orders(): array {
		return [
			'default'         => [ 'customize,reject,accept', [ 'customize', 'reject', 'accept' ] ],
			'accept first'    => [ 'accept,customize,reject', [ 'accept', 'customize', 'reject' ] ],
			'spaces trimmed'  => [ ' reject , accept,customize ', [ 'reject', 'accept', 'customize' ] ],
		];
	}

	/**
	 * @dataProvider provide_invalid_orders
	 *
	 * @param mixed $value Stored value.
	 */
	public function test_falls_back_to_the_default_order_for_an_invalid_value( mixed $value ): void {
		$this->assertSame( [ 'customize', 'reject', 'accept' ], ButtonOrder::resolve( $value ) );
	}

	/**
	 * @dataProvider provide_invalid_orders
	 *
	 * @param mixed $value Submitted value.
	 */
	public function test_rejects_an_invalid_value( mixed $value ): void {
		$this->assertFalse( ButtonOrder::is_valid( $value ) );
	}

	/**
	 * @return array<string, array{0: mixed}>
	 */
	public static function provide_invalid_orders(): array {
		return [
			'empty'          => [ '' ],
			'missing reject' => [ 'accept,customize' ],
			'duplicate'      => [ 'accept,accept,reject' ],
			'unknown button' => [ 'accept,customize,close' ],
			'extra button'   => [ 'accept,customize,reject,save' ],
			'not a string'   => [ [ 'accept', 'customize', 'reject' ] ],
			'null'           => [ null ],
		];
	}

	public function test_normalize_strips_spaces(): void {
		$this->assertSame( 'reject,accept,customize', ButtonOrder::normalize( ' reject , accept,customize ' ) );
	}
}
