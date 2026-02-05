<?php
/**
 * Cookie Database API client.
 *
 * @package LightweightPlugins\Cookie\Scanner
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Scanner;

/**
 * API class for cookie database lookups.
 */
class Api {

	/**
	 * API base URL.
	 */
	private const API_URL = 'https://api.lwplugins.com/v1/cookie_db';

	/**
	 * Request timeout in seconds.
	 */
	private const TIMEOUT = 15;

	/**
	 * Search for a cookie by name.
	 *
	 * @param string $cookie_name Cookie name to search.
	 * @return array|null Cookie data or null if not found.
	 */
	public static function search( string $cookie_name ): ?array {
		$response = self::request( '/search', [ 'q' => $cookie_name ] );

		if ( empty( $response['results'] ) ) {
			return null;
		}

		// Return best match (first result).
		return self::normalize_cookie( $response['results'][0] );
	}

	/**
	 * Search for multiple cookies at once.
	 *
	 * @param array $cookie_names Array of cookie names.
	 * @return array Associative array of cookie_name => cookie_data.
	 */
	public static function search_multiple( array $cookie_names ): array {
		$results = [];

		foreach ( $cookie_names as $name ) {
			$cookie = self::search( $name );
			if ( $cookie ) {
				$results[ $name ] = $cookie;
			}
		}

		return $results;
	}

	/**
	 * Get database statistics.
	 *
	 * @return array|null Stats data or null on error.
	 */
	public static function get_stats(): ?array {
		return self::request( '/stats' );
	}

	/**
	 * Make an API request.
	 *
	 * @param string $endpoint API endpoint.
	 * @param array  $params   Query parameters.
	 * @return array|null Response data or null on error.
	 */
	private static function request( string $endpoint, array $params = [] ): ?array {
		$url = self::API_URL . $endpoint;

		if ( ! empty( $params ) ) {
			$url = add_query_arg( $params, $url );
		}

		$response = wp_remote_get(
			$url,
			[
				'timeout'   => self::TIMEOUT,
				'sslverify' => true,
				'headers'   => [
					'Accept'     => 'application/json',
					'User-Agent' => 'LW-Cookie/' . LW_COOKIE_VERSION,
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return null;
		}

		return $data;
	}

	/**
	 * Normalize API cookie data to our format.
	 *
	 * @param array $api_cookie Cookie data from API.
	 * @return array Normalized cookie data.
	 */
	private static function normalize_cookie( array $api_cookie ): array {
		// Map API categories to our categories.
		$category_map = [
			'Necessary'       => 'necessary',
			'Functional'      => 'functional',
			'Analytics'       => 'analytics',
			'Marketing'       => 'marketing',
			'Personalization' => 'functional',
			'Security'        => 'necessary',
		];

		$api_category = $api_cookie['category'] ?? 'Functional';
		$category     = $category_map[ $api_category ] ?? 'functional';

		return [
			'name'     => $api_cookie['name'] ?? '',
			'provider' => $api_cookie['platform'] ?? '',
			'purpose'  => $api_cookie['description'] ?? '',
			'duration' => $api_cookie['retention'] ?? '',
			'category' => $category,
			'type'     => self::guess_type( $api_cookie['retention'] ?? '' ),
		];
	}

	/**
	 * Guess cookie type from retention period.
	 *
	 * @param string $retention Retention period string.
	 * @return string 'session' or 'persistent'.
	 */
	private static function guess_type( string $retention ): string {
		$retention_lower = strtolower( $retention );

		if (
			str_contains( $retention_lower, 'session' ) ||
			str_contains( $retention_lower, 'browser' ) ||
			str_contains( $retention_lower, 'when you close' )
		) {
			return 'session';
		}

		return 'persistent';
	}
}
