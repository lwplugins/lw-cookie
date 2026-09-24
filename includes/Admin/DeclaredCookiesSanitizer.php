<?php
/**
 * Declared cookies sanitizer.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin;

/**
 * Cleans the cookie declaration list submitted from the settings screen.
 */
final class DeclaredCookiesSanitizer {

	/**
	 * Allowed categories; the first one is the fallback.
	 */
	private const CATEGORIES = [ 'necessary', 'functional', 'analytics', 'marketing' ];

	/**
	 * Allowed cookie types; the second one is the fallback.
	 */
	private const TYPES = [ 'session', 'persistent' ];

	/**
	 * Sanitize the list: rows without a name are dropped, text fields are
	 * cleaned, category/type fall back to necessary/persistent.
	 *
	 * @param array<mixed> $cookies Raw rows.
	 * @return array<int, array{name: string, provider: string, purpose: string, duration: string, category: string, type: string}>
	 */
	public static function sanitize( array $cookies ): array {
		$sanitized = [];

		foreach ( $cookies as $cookie ) {
			if ( ! is_array( $cookie ) ) {
				continue;
			}

			$name = self::text( $cookie['name'] ?? '' );
			if ( '' === $name ) {
				continue;
			}

			$sanitized[] = [
				'name'     => $name,
				'provider' => self::text( $cookie['provider'] ?? '' ),
				'purpose'  => self::text( $cookie['purpose'] ?? '' ),
				'duration' => self::text( $cookie['duration'] ?? '' ),
				'category' => in_array( $cookie['category'] ?? '', self::CATEGORIES, true ) ? $cookie['category'] : 'necessary',
				'type'     => in_array( $cookie['type'] ?? '', self::TYPES, true ) ? $cookie['type'] : 'persistent',
			];
		}

		return $sanitized;
	}

	/**
	 * Clean a single-line text value; non-scalars become ''.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private static function text( mixed $value ): string {
		return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
	}
}
