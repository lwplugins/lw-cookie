<?php
/**
 * Settings sanitizer.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin;

/**
 * Sanitizes submitted settings against the option defaults.
 *
 * The type of each default decides how its value is cleaned. A value that
 * cannot be cleaned into something valid (bad colour, unknown choice, wrong
 * shape) keeps the current value instead of being reset.
 */
final class SettingsSanitizer {

	/**
	 * Keys restricted to fixed values.
	 */
	private const CHOICES = [
		'banner_position'      => [ 'bottom', 'top', 'modal' ],
		'banner_layout'        => [ 'bar', 'box' ],
		'banner_box_alignment' => [ 'right', 'left' ],
		'floating_button_pos'  => [ 'bottom-left', 'bottom-right' ],
	];

	/**
	 * Sanitize the submitted keys.
	 *
	 * @param array<string, mixed> $submitted     Submitted values (known keys only).
	 * @param array<string, mixed> $current       Current values of every key.
	 * @param array<string, mixed> $defaults      Option defaults.
	 * @param array<int, string>   $textarea_keys Keys holding multi-line text.
	 * @return array<string, mixed> Sanitized values for the submitted keys.
	 */
	public static function sanitize( array $submitted, array $current, array $defaults, array $textarea_keys ): array {
		$sanitized = [];

		foreach ( $submitted as $key => $value ) {
			if ( ! array_key_exists( $key, $defaults ) ) {
				continue;
			}

			$key               = (string) $key;
			$fallback          = $current[ $key ] ?? $defaults[ $key ];
			$sanitized[ $key ] = self::value( $key, $value, $fallback, $defaults[ $key ], in_array( $key, $textarea_keys, true ) );
		}

		return $sanitized;
	}

	/**
	 * Sanitize one value according to its key and default's type.
	 *
	 * @param string $key      Option key.
	 * @param mixed  $value    Submitted value.
	 * @param mixed  $fallback Current value, kept when $value is invalid.
	 * @param mixed  $default  Default value.
	 * @param bool   $textarea Whether the key holds multi-line text.
	 * @return mixed
	 */
	private static function value( string $key, mixed $value, mixed $fallback, mixed $default, bool $textarea ): mixed {
		if ( is_bool( $default ) ) {
			return is_array( $value ) ? (bool) $fallback : filter_var( $value, FILTER_VALIDATE_BOOLEAN );
		}

		if ( is_int( $default ) ) {
			return is_scalar( $value ) ? absint( $value ) : $fallback;
		}

		if ( is_array( $default ) ) {
			return is_array( $value ) ? DeclaredCookiesSanitizer::sanitize( $value ) : $fallback;
		}

		if ( ! is_scalar( $value ) ) {
			return $fallback;
		}

		$value = (string) $value;

		if ( isset( self::CHOICES[ $key ] ) ) {
			return in_array( $value, self::CHOICES[ $key ], true ) ? $value : $fallback;
		}

		if ( str_contains( $key, 'color' ) ) {
			$color = sanitize_hex_color( $value );
			return is_string( $color ) && '' !== $color ? $color : $fallback;
		}

		return $textarea ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
	}
}
