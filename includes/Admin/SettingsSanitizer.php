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
 * shape — see problem()) keeps the current value instead of being reset.
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

	public const PROBLEM_BOOLEAN = 'not_boolean';
	public const PROBLEM_NUMBER  = 'not_number';
	public const PROBLEM_LIST    = 'not_list';
	public const PROBLEM_TEXT    = 'not_text';
	public const PROBLEM_CHOICE  = 'not_choice';
	public const PROBLEM_COLOR   = 'not_color';

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
	 * Why a submitted value cannot be stored, or null when it is valid. The
	 * admin API keeps the current value for an invalid one; automation (the
	 * Site Manager set-options ability) reports it back instead.
	 *
	 * @param string $key     Option key.
	 * @param mixed  $value   Submitted value.
	 * @param mixed  $default Default value.
	 * @return string|null One of the PROBLEM_* codes, or null.
	 */
	public static function problem( string $key, mixed $value, mixed $default ): ?string {
		if ( is_bool( $default ) ) {
			$valid = ! is_array( $value ) && null !== filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
			return $valid ? null : self::PROBLEM_BOOLEAN;
		}

		if ( is_int( $default ) ) {
			return is_numeric( $value ) ? null : self::PROBLEM_NUMBER;
		}

		if ( is_array( $default ) ) {
			return is_array( $value ) ? null : self::PROBLEM_LIST;
		}

		if ( ! is_scalar( $value ) ) {
			return self::PROBLEM_TEXT;
		}

		if ( isset( self::CHOICES[ $key ] ) ) {
			return in_array( (string) $value, self::CHOICES[ $key ], true ) ? null : self::PROBLEM_CHOICE;
		}

		if ( str_contains( $key, 'color' ) ) {
			$color = sanitize_hex_color( (string) $value );
			return is_string( $color ) && '' !== $color ? null : self::PROBLEM_COLOR;
		}

		return null;
	}

	/**
	 * Allowed values of a fixed-choice key (empty for free-form keys).
	 *
	 * @param string $key Option key.
	 * @return array<int, string>
	 */
	public static function choices( string $key ): array {
		return self::CHOICES[ $key ] ?? [];
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
		if ( null !== self::problem( $key, $value, $default ) ) {
			return is_bool( $default ) ? (bool) $fallback : $fallback;
		}

		if ( is_bool( $default ) ) {
			return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
		}

		if ( is_int( $default ) ) {
			return absint( $value );
		}

		if ( is_array( $default ) ) {
			return DeclaredCookiesSanitizer::sanitize( $value );
		}

		$value = (string) $value;

		if ( isset( self::CHOICES[ $key ] ) ) {
			return $value;
		}

		if ( str_contains( $key, 'color' ) ) {
			return (string) sanitize_hex_color( $value );
		}

		return $textarea ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
	}
}
