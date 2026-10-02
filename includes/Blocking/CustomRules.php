<?php
/**
 * Custom blocking rules.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Blocking;

/**
 * Site-specific blocking rules (Advanced → Custom Blocking Rules): a domain
 * or URL pattern and the consent category it needs.
 *
 * A domain matches itself and its subdomains; a pattern with a path matches
 * any URL containing it — the same matching the built-in list uses. Rules
 * come before the built-in list, so they override it; "necessary" means
 * "never block" (e.g. to let Google Tag Manager load before consent).
 */
final class CustomRules {

	/**
	 * Allowed categories.
	 */
	public const CATEGORIES = [ 'necessary', 'functional', 'analytics', 'marketing' ];

	/**
	 * Clean submitted rules: invalid patterns and categories are dropped,
	 * the first rule for a pattern wins.
	 *
	 * @param array<mixed> $rules Raw rows.
	 * @return array<int, array{pattern: string, category: string}>
	 */
	public static function sanitize( array $rules ): array {
		$clean = [];

		foreach ( $rules as $rule ) {
			if ( ! is_array( $rule ) || ! in_array( $rule['category'] ?? '', self::CATEGORIES, true ) ) {
				continue;
			}

			$pattern = self::pattern( $rule['pattern'] ?? '' );
			if ( null !== $pattern && ! isset( $clean[ $pattern ] ) ) {
				$clean[ $pattern ] = [
					'pattern'  => $pattern,
					'category' => $rule['category'],
				];
			}
		}

		return array_values( $clean );
	}

	/**
	 * Pattern => category map for the guard, from stored rules.
	 *
	 * @param mixed $rules Stored custom_blocking_rules value.
	 * @return array<string, string>
	 */
	public static function to_domains( mixed $rules ): array {
		$domains = [];

		foreach ( self::sanitize( is_array( $rules ) ? $rules : [] ) as $rule ) {
			$domains[ $rule['pattern'] ] = $rule['category'];
		}

		return $domains;
	}

	/**
	 * Normalize a pattern: no scheme, "www.", query, fragment or trailing
	 * slash; lowercase host. Null when it is not a domain (with optional path).
	 *
	 * @param mixed $raw Submitted pattern, e.g. "https://code.tidio.co/".
	 * @return string|null E.g. "code.tidio.co".
	 */
	public static function pattern( mixed $raw ): ?string {
		if ( ! is_string( $raw ) ) {
			return null;
		}

		$pattern = (string) preg_replace( '#^([a-z][a-z0-9+.-]*:)?//#i', '', trim( $raw ) );
		$pattern = (string) preg_replace( '/[?#].*$/', '', $pattern );
		$parts   = explode( '/', $pattern, 2 );
		$host    = (string) preg_replace( '/^www\./', '', strtolower( $parts[0] ) );
		$path    = rtrim( $parts[1] ?? '', '/' );

		if ( ! preg_match( '/^[a-z0-9-]+(\.[a-z0-9-]+)+$/', $host ) || preg_match( '#[\s"\'<>\\\\]#', $path ) ) {
			return null;
		}

		return '' === $path ? $host : $host . '/' . $path;
	}
}
