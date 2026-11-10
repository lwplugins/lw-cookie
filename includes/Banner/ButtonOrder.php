<?php
/**
 * Order of the first-layer banner buttons.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Banner;

/**
 * The banner_button_order option: the three first-layer buttons as a
 * comma-separated list, each exactly once. The markup follows this order, so
 * the Tab and screen-reader order match what visitors see.
 */
final class ButtonOrder {

	/**
	 * Option key.
	 */
	public const OPTION = 'banner_button_order';

	/**
	 * The buttons in their default order.
	 */
	public const DEFAULT_ORDER = [ 'customize', 'reject', 'accept' ];

	/**
	 * Whether a value lists every button exactly once.
	 *
	 * @param mixed $value Submitted or stored value.
	 * @return bool
	 */
	public static function is_valid( mixed $value ): bool {
		if ( ! is_string( $value ) ) {
			return false;
		}

		$order  = self::split( $value );
		$sorted = self::DEFAULT_ORDER;
		sort( $order );
		sort( $sorted );

		return $order === $sorted;
	}

	/**
	 * The stored order, or the default one when it is not valid.
	 *
	 * @param mixed $value Stored value.
	 * @return array<int, string>
	 */
	public static function resolve( mixed $value ): array {
		return self::is_valid( $value ) ? self::split( (string) $value ) : self::DEFAULT_ORDER;
	}

	/**
	 * A valid value in its stored form (no spaces).
	 *
	 * @param string $value Valid value.
	 * @return string
	 */
	public static function normalize( string $value ): string {
		return implode( ',', self::split( $value ) );
	}

	/**
	 * Split a value into trimmed button keys.
	 *
	 * @param string $value Value.
	 * @return array<int, string>
	 */
	private static function split( string $value ): array {
		return array_map( 'trim', explode( ',', $value ) );
	}
}
