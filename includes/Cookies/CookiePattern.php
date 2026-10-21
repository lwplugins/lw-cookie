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
 *
 * The stem is the part a cookie name must start with: the pattern without a
 * trailing "*". A trailing "_" is part of the stem ("_ga_" stays "_ga_",
 * "lwfw_*" becomes "lwfw_"), and the stem needs at least 2 characters before
 * that trailing "_" or "*".
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

		if ( false !== $star && strlen( $pattern ) - 1 !== $star ) {
			return false;
		}

		// A prefix needs at least 2 stem characters before the trailing "_" or "*", or it would match nearly everything.
		$last = substr( $pattern, -1 );
		return ( '*' !== $last && '_' !== $last ) || strlen( $pattern ) >= 3;
	}

	/**
	 * How specifically a pattern matches a cookie name.
	 *
	 * @param string $pattern Cookie name or prefix pattern.
	 * @param string $name    Actual cookie name.
	 * @return int -1 for no match, the stem length for a prefix match (a
	 *             trailing "_" counts, the "*" does not), PHP_INT_MAX for an exact match.
	 */
	public static function specificity( string $pattern, string $name ): int {
		$stem = self::stem( $pattern );

		if ( null === $stem ) {
			return $pattern === $name ? PHP_INT_MAX : -1;
		}

		return str_starts_with( $name, $stem ) ? strlen( $stem ) : -1;
	}

	/**
	 * The prefix a matching cookie name must start with.
	 *
	 * @param string $pattern Cookie name or prefix pattern.
	 * @return string|null The pattern without a trailing "*" (a trailing "_"
	 *                     stays); null for an exact name.
	 */
	public static function stem( string $pattern ): ?string {
		$last = substr( $pattern, -1 );

		if ( '*' === $last ) {
			return substr( $pattern, 0, -1 );
		}

		return '_' === $last ? $pattern : null;
	}
}
