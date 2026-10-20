<?php
/**
 * URL → consent category lookup (PHP port of guard.js getCategoryForUrl()).
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Blocking;

/**
 * Maps a URL to the consent category of the first matching domain rule.
 *
 * Keep this in step with getCategoryForUrl() in assets/js/guard.js: the
 * server rewrites exactly the tags the guard would have blocked.
 */
final class UrlCategory {

	/**
	 * Category for a URL, or null when it is not subject to blocking.
	 *
	 * @param string                    $url       Absolute, protocol-relative or relative URL.
	 * @param array<int|string, string> $domains   Domain (or path pattern) => category, in priority order.
	 * @param string                    $site_host Host of this site (same-origin is always allowed).
	 * @return string|null
	 */
	public static function for_url( string $url, array $domains, string $site_host ): ?string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Pure helper, no WordPress needed.
		$host = parse_url( $url, PHP_URL_HOST );

		if ( ! is_string( $host ) || '' === $host ) {
			return null;
		}

		$host = strtolower( $host );

		if ( strtolower( $site_host ) === $host ) {
			return null;
		}

		$host = (string) preg_replace( '/^www\./', '', $host );

		foreach ( $domains as $domain => $category ) {
			$domain = (string) $domain;

			if ( $host === $domain || str_ends_with( $host, '.' . $domain ) ) {
				return $category;
			}

			// Path-based (e.g. "google.com/maps").
			if ( str_contains( $domain, '/' ) && str_contains( $url, $domain ) ) {
				return $category;
			}
		}

		return null;
	}
}
