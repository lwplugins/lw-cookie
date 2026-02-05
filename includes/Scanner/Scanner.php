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
	 * Option keys for scanned data.
	 */
	private const SCANNED_COOKIES = 'lw_cookie_scanned';
	private const SCANNED_DOMAINS = 'lw_cookie_scanned_domains';
	private const SCANNED_FONTS   = 'lw_cookie_scanned_fonts';

	/**
	 * Initialize scanner hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Handle frontend scan mode.
		add_action( 'template_redirect', [ __CLASS__, 'handle_scan_mode' ], 1 );

		// Register REST endpoints.
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

		// Collect cookies from $_COOKIE superglobal (server-side, sees HttpOnly too!).
		$cookie_names = array_keys( $_COOKIE );
		if ( ! empty( $cookie_names ) ) {
			self::save_scanned_data( self::SCANNED_COOKIES, $cookie_names );
		}

		// Add scan scripts to footer.
		add_action( 'wp_footer', [ __CLASS__, 'print_scan_scripts' ], PHP_INT_MAX );
	}

	/**
	 * Save scanned data to options.
	 *
	 * @param string $option_key Option key.
	 * @param array  $data       Data to save.
	 * @return void
	 */
	private static function save_scanned_data( string $option_key, array $data ): void {
		$existing = get_option( $option_key, [] );
		if ( ! is_array( $existing ) ) {
			$existing = [];
		}
		$merged = array_unique( array_merge( $existing, $data ) );
		update_option( $option_key, $merged, false );
	}

	/**
	 * Get scanned cookies.
	 *
	 * @return array
	 */
	public static function get_scanned_cookies(): array {
		$cookies = get_option( self::SCANNED_COOKIES, [] );
		return is_array( $cookies ) ? $cookies : [];
	}

	/**
	 * Get scanned domains.
	 *
	 * @return array
	 */
	public static function get_scanned_domains(): array {
		$domains = get_option( self::SCANNED_DOMAINS, [] );
		return is_array( $domains ) ? $domains : [];
	}

	/**
	 * Get scanned fonts.
	 *
	 * @return array
	 */
	public static function get_scanned_fonts(): array {
		$fonts = get_option( self::SCANNED_FONTS, [] );
		return is_array( $fonts ) ? $fonts : [];
	}

	/**
	 * Clear all scanned data.
	 *
	 * @return void
	 */
	public static function clear_scanned_data(): void {
		delete_option( self::SCANNED_COOKIES );
		delete_option( self::SCANNED_DOMAINS );
		delete_option( self::SCANNED_FONTS );
	}

	/**
	 * Scan URL for Set-Cookie headers via HTTP HEAD request.
	 *
	 * @param string $url URL to scan.
	 * @return array Array of cookie names found in headers.
	 */
	public static function scan_http_headers( string $url ): array {
		$cookie_names = [];

		$response = wp_remote_head(
			$url,
			[
				'timeout'     => 10,
				'sslverify'   => false,
				'redirection' => 3,
			]
		);

		if ( is_wp_error( $response ) ) {
			return $cookie_names;
		}

		$headers = wp_remote_retrieve_headers( $response );

		if ( empty( $headers['set-cookie'] ) ) {
			return $cookie_names;
		}

		$set_cookies = (array) $headers['set-cookie'];

		foreach ( $set_cookies as $set_cookie ) {
			$parts = explode( '=', $set_cookie, 2 );
			if ( ! empty( $parts[0] ) ) {
				$cookie_names[] = trim( $parts[0] );
			}
		}

		return $cookie_names;
	}

	/**
	 * Pre-scan all URLs for HTTP header cookies.
	 *
	 * @param array $urls URLs to scan.
	 * @return void
	 */
	public static function prescan_http_cookies( array $urls ): void {
		$all_cookies = [];

		foreach ( $urls as $url ) {
			$cookies = self::scan_http_headers( $url );
			if ( ! empty( $cookies ) ) {
				$all_cookies = array_merge( $all_cookies, $cookies );
			}
		}

		if ( ! empty( $all_cookies ) ) {
			self::save_scanned_data( self::SCANNED_COOKIES, array_unique( $all_cookies ) );
		}
	}

	/**
	 * Get URLs to scan.
	 *
	 * @return array Array of URLs.
	 */
	public static function get_scan_urls(): array {
		$urls      = [ home_url( '/' ) ];
		$site_host = wp_parse_url( home_url(), PHP_URL_HOST );

		// Random pages (5).
		$pages = get_posts(
			[
				'numberposts' => 5,
				'post_type'   => 'page',
				'post_status' => 'publish',
				'orderby'     => 'rand',
			]
		);
		foreach ( $pages as $page ) {
			$urls[] = get_permalink( $page );
		}

		// Random posts (5).
		$posts = get_posts(
			[
				'numberposts' => 5,
				'post_type'   => 'post',
				'post_status' => 'publish',
				'orderby'     => 'rand',
			]
		);
		foreach ( $posts as $post ) {
			$urls[] = get_permalink( $post );
		}

		// WooCommerce URLs.
		if ( function_exists( 'WC' ) && post_type_exists( 'product' ) ) {
			// Random products (5).
			$products = get_posts(
				[
					'numberposts' => 5,
					'post_type'   => 'product',
					'post_status' => 'publish',
					'orderby'     => 'rand',
				]
			);
			foreach ( $products as $product ) {
				$urls[] = get_permalink( $product );
			}

			// Add-to-cart URL (triggers WooCommerce cookies).
			$in_stock_product = get_posts(
				[
					'numberposts' => 1,
					'post_type'   => 'product',
					'post_status' => 'publish',
					'meta_query'  => [
						[
							'key'   => '_stock_status',
							'value' => 'instock',
						],
					],
				]
			);
			if ( ! empty( $in_stock_product ) ) {
				$urls[] = add_query_arg( 'add-to-cart', $in_stock_product[0]->ID, home_url() );
			}

			// Cart and checkout.
			if ( function_exists( 'wc_get_cart_url' ) ) {
				$urls[] = wc_get_cart_url();
			}
			if ( function_exists( 'wc_get_checkout_url' ) ) {
				$urls[] = wc_get_checkout_url();
			}
		}

		// EDD checkout.
		if ( function_exists( 'edd_get_checkout_uri' ) ) {
			$urls[] = edd_get_checkout_uri();
		}

		// Find pages with external content (YouTube, Vimeo, iframes, etc.).
		$external_hosts = [];
		$external_query = new \WP_Query(
			[
				'post_type'      => 'any',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				's'              => '//',
			]
		);

		if ( $external_query->have_posts() ) {
			while ( $external_query->have_posts() ) {
				$external_query->the_post();
				$content = get_the_content();

				// Remove <a> tags to avoid false positives.
				$content = preg_replace( '/<a[^>]*>.*?<\/a>/is', '', $content );

				// Find all URLs.
				preg_match_all( '/((https?:)?\/\/[^\s"\'<>]+)/i', $content, $matches );

				if ( ! empty( $matches[0] ) ) {
					foreach ( $matches[0] as $url ) {
						if ( strpos( $url, '//' ) === 0 ) {
							$url = 'https:' . $url;
						}
						$host = wp_parse_url( $url, PHP_URL_HOST );
						if ( $host && $host !== $site_host && ! in_array( $host, $external_hosts, true ) ) {
							$external_hosts[] = $host;
							$urls[]           = get_permalink();
						}
					}
				}
			}
			wp_reset_postdata();
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
			$is_declared = self::is_cookie_declared( $name, $declared_names );
			$api_data    = Api::search( $name );

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

		if ( str_starts_with( $name_lower, '_ga' ) || str_starts_with( $name_lower, '_gid' ) || str_starts_with( $name_lower, '_hj' ) ) {
			return 'analytics';
		}
		if ( str_starts_with( $name_lower, '_fb' ) || str_starts_with( $name_lower, '_gcl' ) || str_starts_with( $name_lower, 'fr' ) ) {
			return 'marketing';
		}
		if ( str_starts_with( $name_lower, 'wordpress_' ) || str_starts_with( $name_lower, 'wp-' ) || str_contains( $name_lower, 'session' ) ) {
			return 'necessary';
		}
		if ( str_starts_with( $name_lower, 'wc_' ) || str_starts_with( $name_lower, 'woocommerce' ) ) {
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

		register_rest_route(
			'lw-cookie/v1',
			'/report-scan',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'rest_report_scan' ],
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			'lw-cookie/v1',
			'/prescan-headers',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'rest_prescan_headers' ],
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
		$cookies = self::enrich_cookies( self::get_scanned_cookies() );
		$domains = self::get_scanned_domains();
		$fonts   = self::get_scanned_fonts();

		return new \WP_REST_Response(
			[
				'success' => true,
				'cookies' => $cookies,
				'domains' => $domains,
				'fonts'   => $fonts,
				'count'   => count( $cookies ),
			]
		);
	}

	/**
	 * REST endpoint: Clear scan results.
	 *
	 * @return \WP_REST_Response
	 */
	public static function rest_clear_scan(): \WP_REST_Response {
		self::clear_scanned_data();
		return new \WP_REST_Response( [ 'success' => true ] );
	}

	/**
	 * REST endpoint: Report scan data from JS.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public static function rest_report_scan( \WP_REST_Request $request ): \WP_REST_Response {
		$data = $request->get_json_params();

		if ( ! empty( $data['cookies'] ) && is_array( $data['cookies'] ) ) {
			self::save_scanned_data( self::SCANNED_COOKIES, $data['cookies'] );
		}
		if ( ! empty( $data['domains'] ) && is_array( $data['domains'] ) ) {
			self::save_scanned_data( self::SCANNED_DOMAINS, $data['domains'] );
		}
		if ( ! empty( $data['fonts'] ) && is_array( $data['fonts'] ) ) {
			self::save_scanned_data( self::SCANNED_FONTS, $data['fonts'] );
		}

		return new \WP_REST_Response( [ 'success' => true ] );
	}

	/**
	 * REST endpoint: Pre-scan URLs for HTTP header cookies.
	 *
	 * @return \WP_REST_Response
	 */
	public static function rest_prescan_headers(): \WP_REST_Response {
		$urls = self::get_scan_urls();
		self::prescan_http_cookies( $urls );

		return new \WP_REST_Response(
			[
				'success'    => true,
				'urls_count' => count( $urls ),
			]
		);
	}

	/**
	 * Print scan scripts (networkidle, domain tracking, font detection).
	 *
	 * @return void
	 */
	public static function print_scan_scripts(): void {
		$report_url = rest_url( 'lw-cookie/v1/report-scan' );
		$nonce      = wp_create_nonce( 'wp_rest' );
		$site_host  = wp_parse_url( home_url(), PHP_URL_HOST );
		?>
		<script>
		(function() {
			var siteHost = <?php echo wp_json_encode( $site_host ); ?>;
			var reportUrl = <?php echo wp_json_encode( $report_url ); ?>;
			var restNonce = <?php echo wp_json_encode( $nonce ); ?>;
			var collectedDomains = [];
			var collectedFonts = [];
			var collectedCookies = [];

			// Collect cookies from JS (non-HttpOnly).
			function collectCookies() {
				document.cookie.split(';').forEach(function(c) {
					var name = c.trim().split('=')[0];
					if (name && collectedCookies.indexOf(name) === -1) {
						collectedCookies.push(name);
					}
				});
			}

			// Collect external domains from resources.
			function collectDomains() {
				var resources = performance.getEntriesByType('resource');
				resources.forEach(function(r) {
					try {
						var url = new URL(r.name);
						var host = url.hostname;
						if (host && host !== siteHost && host.indexOf('.') !== -1 && collectedDomains.indexOf(host) === -1) {
							collectedDomains.push(host);
						}
					} catch(e) {}
				});
			}

			// Collect fonts from stylesheets.
			async function collectFonts() {
				for (var sheet of document.styleSheets) {
					try {
						if (!sheet.href) continue;
						var response = await fetch(sheet.href);
						var css = await response.text();
						var fontFaceRegex = /@font-face\s*\{[^}]*\}/g;
						var matches = css.match(fontFaceRegex);
						if (matches) {
							matches.forEach(function(fontFace) {
								var familyMatch = fontFace.match(/font-family\s*:\s*['"]?([^;'"]+)['"]?/);
								var urlMatch = fontFace.match(/url\s*\(\s*['"]?([^'"\)]+)['"]?\s*\)/);
								if (familyMatch && urlMatch) {
									var family = familyMatch[1].trim();
									var fontUrl = urlMatch[1];
									try {
										var fullUrl = new URL(fontUrl, sheet.href).href;
										var fontHost = new URL(fullUrl).hostname;
										if (fontHost !== siteHost) {
											var fontData = family + '|' + fontHost;
											if (collectedFonts.indexOf(fontData) === -1) {
												collectedFonts.push(fontData);
											}
										}
									} catch(e) {}
								}
							});
						}
					} catch(e) {
						console.log('Could not parse stylesheet:', sheet.href);
					}
				}
			}

			// Report data to server.
			function reportData() {
				var data = {
					cookies: collectedCookies,
					domains: collectedDomains,
					fonts: collectedFonts
				};

				if (data.cookies.length || data.domains.length || data.fonts.length) {
					fetch(reportUrl, {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json',
							'X-WP-Nonce': restNonce
						},
						body: JSON.stringify(data),
						keepalive: true
					});
				}
			}

			// Notify parent (admin iframe) that scan is complete.
			function notifyParent(msg) {
				window.parent.postMessage(msg, '*');
			}

			// Wait for network to be idle.
			function waitForNetworkIdle(timeout) {
				timeout = timeout || 500;
				var pending = 0;
				var timer;
				var observer = new PerformanceObserver(function(list) {
					list.getEntries().forEach(function(entry) {
						if (entry.entryType === 'resource') {
							pending++;
							clearTimeout(timer);
							timer = setTimeout(function() {
								if (--pending === 0) {
									finishScan();
								}
							}, timeout);
						}
					});
				});
				observer.observe({ entryTypes: ['resource'] });

				timer = setTimeout(function() {
					if (pending === 0) {
						finishScan();
					}
				}, timeout);
			}

			// Finish scan and report.
			async function finishScan() {
				collectCookies();
				collectDomains();
				await collectFonts();
				reportData();
				notifyParent('networkidle0');
			}

			// Start scanning on page load.
			window.addEventListener('load', function() {
				window.scrollTo(0, document.body.scrollHeight);
				waitForNetworkIdle();
			});
		})();
		</script>
		<?php
	}
}
