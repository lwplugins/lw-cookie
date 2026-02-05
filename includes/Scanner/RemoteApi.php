<?php
/**
 * Remote Scanner API client.
 *
 * @package LightweightPlugins\Cookie\Scanner
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Scanner;

/**
 * Communicates with the LW Plugins Cookie Scanner API.
 */
class RemoteApi {

	/**
	 * API base URL.
	 */
	private const API_URL = 'https://api.lwplugins.com/v1/cookie-scan';

	/**
	 * Request timeout in seconds.
	 */
	private const TIMEOUT = 60;

	/**
	 * Scan a URL using the remote headless browser.
	 *
	 * @param string $url URL to scan.
	 * @return array|null Scan results or null on failure.
	 */
	public static function scan( string $url ): ?array {
		$response = wp_remote_post(
			self::API_URL,
			[
				'timeout' => self::TIMEOUT,
				'headers' => [
					'Content-Type' => 'application/json',
					'X-Site'       => home_url(),
				],
				'body'    => wp_json_encode( [ 'url' => $url ] ),
			]
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) || empty( $data['success'] ) ) {
			return null;
		}

		return $data;
	}

	/**
	 * Scan multiple URLs and aggregate results.
	 *
	 * @param array $urls    URLs to scan.
	 * @param int   $max_urls Maximum URLs to scan (default 5).
	 * @return array Aggregated results with cookies and domains.
	 */
	public static function scan_multiple( array $urls, int $max_urls = 5 ): array {
		$all_cookies = [];
		$all_domains = [];
		$scanned     = 0;

		foreach ( $urls as $url ) {
			if ( $scanned >= $max_urls ) {
				break;
			}

			$result = self::scan( $url );

			if ( $result ) {
				// Collect cookie names.
				if ( ! empty( $result['cookies'] ) && is_array( $result['cookies'] ) ) {
					foreach ( $result['cookies'] as $cookie ) {
						if ( ! empty( $cookie['name'] ) ) {
							$all_cookies[] = $cookie['name'];
						}
					}
				}

				// Collect external domains.
				if ( ! empty( $result['domains'] ) && is_array( $result['domains'] ) ) {
					foreach ( $result['domains'] as $domain ) {
						if ( ! empty( $domain['host'] ) ) {
							$all_domains[] = $domain['host'];
						}
					}
				}
			}

			++$scanned;
		}

		return [
			'cookies' => array_unique( $all_cookies ),
			'domains' => array_unique( $all_domains ),
			'scanned' => $scanned,
		];
	}
}
