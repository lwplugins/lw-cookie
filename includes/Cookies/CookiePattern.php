<?php
/**
 * Cookie name syntax shared by the declaration, the scanner and guard.js.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Cookies;

/**
 * A trailing "_" or "*" makes a name a prefix ("_ga_" and "lwfw_*" match
 * "_ga_ABC" and "lwfw_v"); any other name is exact. "*" anywhere else is
 * invalid. Matching is case-sensitive, like cookie names themselves.
 */
final class CookiePattern {

	/**
	 * Whether a name or pattern is usable.
	 *
	 * @param string $pattern Cookie name or prefix pattern.
	 */
	public static function is_valid( string $pattern ): bool {
		if ( '' === $pattern || 1 === preg_match( '/[\s;,=]/', $pattern ) ) {
			return false;
		}

		$star = strpos( $pattern, '*' );

		return false === $star || strlen( $pattern ) - 1 === $star;
	}

	/**
	 * How specifically a pattern matches a cookie name.
	 *
	 * @param string $pattern Cookie name or prefix pattern.
	 * @param string $name    Actual cookie name.
	 * @return int -1 for no match, the prefix length for a prefix match (the
	 *             "_" counts, the "*" does not), PHP_INT_MAX for an exact match.
	 */
	public static function specificity( string $pattern, string $name ): int {
		$last = substr( $pattern, -1 );

		if ( '*' !== $last && '_' !== $last ) {
			return $pattern === $name ? PHP_INT_MAX : -1;
		}

		$stem = '*' === $last ? substr( $pattern, 0, -1 ) : $pattern;

		return str_starts_with( $name, $stem ) ? strlen( $stem ) : -1;
	}
}
