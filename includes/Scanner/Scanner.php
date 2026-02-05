<?php
/**
 * Cookie Scanner.
 *
 * @package LightweightPlugins\Cookie\Scanner
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Scanner;

use LightweightPlugins\Cookie\Options;

/**
 * Scanner class for detecting cookies on the website.
 */
class Scanner {

	/**
	 * Scan mode query parameter.
	 */
	public const SCAN_PARAM = 'lw_cookie_scan';

	/**
	 * Option key for scanned cookies.
	 */
	private const SCANNED_OPTION = 'lw_cookie_scanned';

	/**
	 * Initialize scanner hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Handle frontend scan mode.
		add_action( 'template_redirect', [ __CLASS__, 'handle_scan_mode' ], 1 );

		// Register REST endpoint for scan results.
		add_action( 'rest_api_init', [ __CLASS__, 'register_rest_routes' ] );
	}

	/**
	 * Check if we're in scan mode.
	 *
	 * @return bool
	 */
	public static function is_scanning(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET[ self::SCAN_PARAM ] );
	}

	/**
	 * Handle scan mode on frontend.
	 *
	 * @return void
	 */
	public static function handle_scan_mode(): void {
		if ( ! self::is_scanning() ) {
			return;
		}

		// Only allow admins to scan.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Collect cookies from $_COOKIE superglobal.
		$cookie_names = array_keys( $_COOKIE );

		if ( ! empty( $cookie_names ) ) {
			self::save_scanned_cookies( $cookie_names );
		}

		// Add networkidle script to signal scan completion.
		add_action( 'wp_footer', [ __CLASS__, 'print_networkidle_script' ], PHP_INT_MAX );
	}

	/**
	 * Save scanned cookie names.
	 *
	 * @param array $cookie_names Array of cookie names.
	 * @return void
	 */
	private static function save_scanned_cookies( array $cookie_names ): void {
		$existing = get_option( self::SCANNED_OPTION, [] );

		if ( ! is_array( $existing ) ) {
			$existing = [];
		}

		// Merge with existing, keeping unique values.
		$merged = array_unique( array_merge( $existing, $cookie_names ) );

		update_option( self::SCANNED_OPTION, $merged, false );
	}

	/**
	 * Get scanned cookies and clear the list.
	 *
	 * @return array Array of cookie names.
	 */
	public static function get_scanned_cookies(): array {
		$cookies = get_option( self::SCANNED_OPTION, [] );

		if ( ! is_array( $cookies ) ) {
			return [];
		}

		return $cookies;
	}

	/**
	 * Clear scanned cookies.
	 *
	 * @return void
	 */
	public static function clear_scanned_cookies(): void {
		delete_option( self::SCANNED_OPTION );
	}

	/**
	 * Get URLs to scan.
	 *
	 * @return array Array of URLs.
	 */
	public static function get_scan_urls(): array {
		$urls = [ home_url( '/' ) ];

		// Add WooCommerce URLs if available.
		if ( function_exists( 'WC' ) ) {
			if ( function_exists( 'wc_get_cart_url' ) ) {
				$urls[] = wc_get_cart_url();
			}
			if ( function_exists( 'wc_get_checkout_url' ) ) {
				$urls[] = wc_get_checkout_url();
			}
		}

		// Add a sample post/page if exists.
		$sample_post = get_posts(
			[
				'numberposts' => 1,
				'post_type'   => 'post',
				'post_status' => 'publish',
			]
		);

		if ( ! empty( $sample_post ) ) {
			$urls[] = get_permalink( $sample_post[0] );
		}

		// Add scan parameter to all URLs.
		$urls = array_map(
			function ( $url ) {
				return add_query_arg( self::SCAN_PARAM, '1', $url );
			},
			array_filter( array_unique( $urls ) )
		);

		return array_values( $urls );
	}

	/**
	 * Enrich scanned cookies with API data.
	 *
	 * @param array $cookie_names Array of cookie names.
	 * @return array Enriched cookie data.
	 */
	public static function enrich_cookies( array $cookie_names ): array {
		$enriched       = [];
		$declared       = Options::get( 'declared_cookies', [] );
		$declared_names = array_column( $declared, 'name' );

		foreach ( $cookie_names as $name ) {
			// Check if already declared.
			$is_declared = self::is_cookie_declared( $name, $declared_names );

			// Look up in API.
			$api_data = Api::search( $name );

			if ( $api_data ) {
				$enriched[] = array_merge(
					$api_data,
					[
						'original_name' => $name,
						'is_declared'   => $is_declared,
						'source'        => 'api',
					]
				);
			} else {
				// Unknown cookie - try to guess.
				$enriched[] = [
					'name'          => $name,
					'original_name' => $name,
					'provider'      => self::guess_provider( $name ),
					'purpose'       => '',
					'duration'      => '',
					'category'      => self::guess_category( $name ),
					'type'          => 'persistent',
					'is_declared'   => $is_declared,
					'source'        => 'unknown',
				];
			}
		}

		return $enriched;
	}

	/**
	 * Check if a cookie is already declared.
	 *
	 * @param string $name           Cookie name.
	 * @param array  $declared_names Declared cookie names.
	 * @return bool
	 */
	private static function is_cookie_declared( string $name, array $declared_names ): bool {
		foreach ( $declared_names as $declared ) {
			if ( $declared === $name ) {
				return true;
			}

			// Check wildcard patterns.
			if ( str_contains( $declared, '*' ) ) {
				$pattern = '/^' . str_replace( '\*', '.*', preg_quote( $declared, '/' ) ) . '$/';
				if ( preg_match( $pattern, $name ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Guess provider from cookie name.
	 *
	 * @param string $name Cookie name.
	 * @return string Provider name.
	 */
	private static function guess_provider( string $name ): string {
		$prefixes = [
			'_ga'         => 'Google Analytics',
			'_gid'        => 'Google Analytics',
			'_gat'        => 'Google Analytics',
			'_gcl'        => 'Google Ads',
			'_fb'         => 'Facebook',
			'_hj'         => 'Hotjar',
			'wp-'         => 'WordPress',
			'wordpress_'  => 'WordPress',
			'wc_'         => 'WooCommerce',
			'woocommerce' => 'WooCommerce',
		];

		$name_lower = strtolower( $name );

		foreach ( $prefixes as $prefix => $provider ) {
			if ( str_starts_with( $name_lower, strtolower( $prefix ) ) ) {
				return $provider;
			}
		}

		return '';
	}

	/**
	 * Guess category from cookie name.
	 *
	 * @param string $name Cookie name.
	 * @return string Category.
	 */
	private static function guess_category( string $name ): string {
		$name_lower = strtolower( $name );

		// Analytics patterns.
		if (
			str_starts_with( $name_lower, '_ga' ) ||
			str_starts_with( $name_lower, '_gid' ) ||
			str_starts_with( $name_lower, '_hj' )
		) {
			return 'analytics';
		}

		// Marketing patterns.
		if (
			str_starts_with( $name_lower, '_fb' ) ||
			str_starts_with( $name_lower, '_gcl' ) ||
			str_starts_with( $name_lower, 'fr' )
		) {
			return 'marketing';
		}

		// Necessary patterns.
		if (
			str_starts_with( $name_lower, 'wordpress_' ) ||
			str_starts_with( $name_lower, 'wp-' ) ||
			str_contains( $name_lower, 'session' ) ||
			str_contains( $name_lower, 'csrf' ) ||
			str_contains( $name_lower, 'nonce' )
		) {
			return 'necessary';
		}

		// WooCommerce - functional.
		if (
			str_starts_with( $name_lower, 'wc_' ) ||
			str_starts_with( $name_lower, 'woocommerce' )
		) {
			return 'functional';
		}

		return 'functional';
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public static function register_rest_routes(): void {
		register_rest_route(
			'lw-cookie/v1',
			'/scan-results',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'rest_get_scan_results' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			'lw-cookie/v1',
			'/clear-scan',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'rest_clear_scan' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);
	}

	/**
	 * REST endpoint: Get scan results.
	 *
	 * @return \WP_REST_Response
	 */
	public static function rest_get_scan_results(): \WP_REST_Response {
		$scanned  = self::get_scanned_cookies();
		$enriched = self::enrich_cookies( $scanned );

		return new \WP_REST_Response(
			[
				'success' => true,
				'count'   => count( $enriched ),
				'cookies' => $enriched,
			]
		);
	}

	/**
	 * REST endpoint: Clear scan results.
	 *
	 * @return \WP_REST_Response
	 */
	public static function rest_clear_scan(): \WP_REST_Response {
		self::clear_scanned_cookies();

		return new \WP_REST_Response( [ 'success' => true ] );
	}

	/**
	 * Print networkidle script for scan completion detection.
	 *
	 * @return void
	 */
	public static function print_networkidle_script(): void {
		?>
		<script>
		(function() {
			function notifyParent(msg) {
				window.parent.postMessage(msg, '*');
			}

			function waitForNetworkIdle(timeout) {
				timeout = timeout || 500;
				var pending = 0;
				var timer;
				var observer;

				observer = new PerformanceObserver(function(list) {
					list.getEntries().forEach(function(entry) {
						if (entry.entryType === 'resource') {
							pending++;
							clearTimeout(timer);
							timer = setTimeout(function() {
								if (--pending === 0) {
									notifyParent('networkidle0');
									if (observer) observer.disconnect();
								}
							}, timeout);
						}
					});
				});

				observer.observe({ entryTypes: ['resource'] });

				timer = setTimeout(function() {
					if (pending === 0) {
						notifyParent('networkidle0');
						observer.disconnect();
					}
				}, timeout);
			}

			window.addEventListener('load', function() {
				window.scrollTo(0, document.body.scrollHeight);
				waitForNetworkIdle();
			});
		})();
		</script>
		<?php
	}
}
