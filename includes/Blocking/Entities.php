<?php
/**
 * Blocking Entities — unified domain/cookie data for client-side guard.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Blocking;

/**
 * Provides domain-to-category and cookie-to-category mappings
 * as a single JSON-ready structure for the client-side guard script.
 */
final class Entities {

	/**
	 * Get blocked domains with their consent categories.
	 *
	 * Merges KnownScripts URL patterns (extracted domains) with
	 * the iframe/embed host list.
	 *
	 * @return array<string, string> domain => category
	 */
	public static function get_domains(): array {
		$domains = self::get_iframe_domains();

		foreach ( KnownScripts::get_scripts() as $script ) {
			foreach ( $script['patterns'] as $pattern ) {
				$domain = self::extract_domain( $pattern );
				if ( $domain && ! isset( $domains[ $domain ] ) ) {
					$domains[ $domain ] = $script['category'];
				}
			}
		}

		return $domains;
	}

	/**
	 * Get known tracking cookie patterns with their categories.
	 *
	 * @return array<string, string> cookie name/pattern => category
	 */
	public static function get_cookies(): array {
		return [
			// Analytics cookies.
			'_ga'                    => 'analytics',
			'_ga_'                   => 'analytics',
			'_gid'                   => 'analytics',
			'_gat'                   => 'analytics',
			'_gac_'                  => 'analytics',
			'__utma'                 => 'analytics',
			'__utmb'                 => 'analytics',
			'__utmc'                 => 'analytics',
			'__utmz'                 => 'analytics',
			'__utmt'                 => 'analytics',
			'_hjid'                  => 'analytics',
			'_hjSession'             => 'analytics',
			'_hjSessionUser'         => 'analytics',
			'_clck'                  => 'analytics',
			'_clsk'                  => 'analytics',
			// Marketing cookies.
			'_fbp'                   => 'marketing',
			'_fbc'                   => 'marketing',
			'fr'                     => 'marketing',
			'_gcl_au'                => 'marketing',
			'_gcl_aw'                => 'marketing',
			'IDE'                    => 'marketing',
			'test_cookie'            => 'marketing',
			'li_fat_id'              => 'marketing',
			'li_sugr'                => 'marketing',
			'_pin_unauth'            => 'marketing',
			'muc_ads'                => 'marketing',
			'personalization_id'     => 'marketing',
			'tt_pixel_session_index' => 'marketing',
			'_ttp'                   => 'marketing',
		];
	}

	/**
	 * Get combined config for JavaScript guard.
	 *
	 * @return array{domains: array<string, string>, cookies: array<string, string>}
	 */
	public static function get_js_config(): array {
		return [
			'domains' => self::get_domains(),
			'cookies' => self::get_cookies(),
		];
	}

	/**
	 * Iframe/embed blocked hosts and their categories.
	 *
	 * @return array<string, string> domain => category
	 */
	private static function get_iframe_domains(): array {
		return [
			'youtube.com'          => 'marketing',
			'youtube-nocookie.com' => 'marketing',
			'youtu.be'             => 'marketing',
			'vimeo.com'            => 'marketing',
			'player.vimeo.com'     => 'marketing',
			'dailymotion.com'      => 'marketing',
			'twitch.tv'            => 'marketing',
			'tiktok.com'           => 'marketing',
			'facebook.com'         => 'marketing',
			'instagram.com'        => 'marketing',
			'twitter.com'          => 'marketing',
			'x.com'                => 'marketing',
			'linkedin.com'         => 'marketing',
			'pinterest.com'        => 'marketing',
			'google.com/maps'      => 'functional',
			'maps.google.com'      => 'functional',
			'openstreetmap.org'    => 'functional',
			'soundcloud.com'       => 'functional',
			'spotify.com'          => 'functional',
			'codepen.io'           => 'functional',
			'jsfiddle.net'         => 'functional',
		];
	}

	/**
	 * Extract domain from a URL pattern string.
	 *
	 * @param string $pattern URL or partial URL pattern.
	 * @return string|null Extracted domain or null.
	 */
	private static function extract_domain( string $pattern ): ?string {
		// Remove paths — keep only the domain portion.
		$parts  = explode( '/', $pattern, 2 );
		$domain = $parts[0];

		// Must look like a domain (contains a dot).
		if ( ! str_contains( $domain, '.' ) ) {
			return null;
		}

		return $domain;
	}
}
