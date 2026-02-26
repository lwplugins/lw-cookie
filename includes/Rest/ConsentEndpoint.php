<?php
/**
 * REST API endpoint for consent logging.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Rest;

use LightweightPlugins\Cookie\Consent\Logger;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Registers POST /wp-json/lw-cookie/v1/consent.
 *
 * This endpoint replaces the old admin-ajax handler.
 * No nonce is used because nonces are page-specific and
 * get cached by full-page cache plugins. Instead we rely
 * on the consent cookie itself for validation and basic
 * rate limiting via the Retry-After header.
 */
final class ConsentEndpoint {

	/**
	 * REST namespace.
	 */
	private const NAMESPACE = 'lw-cookie/v1';

	/**
	 * Transient prefix for rate limiting.
	 */
	private const RATE_PREFIX = 'lw_cookie_rate_';

	/**
	 * Rate limit window in seconds.
	 */
	private const RATE_WINDOW = 5;

	/**
	 * Constructor — registers the route on rest_api_init.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/consent',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'handle_consent' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'categories'  => [
						'required'          => true,
						'validate_callback' => [ $this, 'validate_categories' ],
					],
					'action_type' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);
	}

	/**
	 * Validate categories parameter.
	 *
	 * @param mixed $value Parameter value.
	 * @return bool|WP_Error
	 */
	public function validate_categories( mixed $value ): bool|WP_Error {
		if ( ! is_array( $value ) ) {
			return new WP_Error(
				'invalid_categories',
				__( 'Categories must be an object.', 'lw-cookie' ),
				[ 'status' => 400 ]
			);
		}

		$allowed_keys = [ 'necessary', 'functional', 'analytics', 'marketing' ];

		foreach ( array_keys( $value ) as $key ) {
			if ( ! in_array( $key, $allowed_keys, true ) ) {
				return new WP_Error(
					'invalid_category',
					/* translators: %s: category key */
					sprintf( __( 'Unknown category: %s', 'lw-cookie' ), $key ),
					[ 'status' => 400 ]
				);
			}
		}

		return true;
	}

	/**
	 * Handle the consent POST request.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_consent( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		// Simple rate limiting by IP.
		$ip_hash  = $this->get_ip_hash();
		$rate_key = self::RATE_PREFIX . $ip_hash;

		if ( get_transient( $rate_key ) ) {
			return new WP_REST_Response(
				[ 'message' => 'Rate limited' ],
				429
			);
		}

		set_transient( $rate_key, 1, self::RATE_WINDOW );

		$categories  = $request->get_param( 'categories' );
		$action_type = $request->get_param( 'action_type' );

		// Sanitize: cast all values to bool.
		$clean_categories = [];
		foreach ( $categories as $key => $val ) {
			$clean_categories[ $key ] = (bool) $val;
		}

		// Necessary is always true.
		$clean_categories['necessary'] = true;

		// Generate consent ID (matches JS format).
		$consent_id = wp_generate_uuid4();

		// Log to database.
		$logger = new Logger();
		$logger->log( $consent_id, $clean_categories, $action_type );

		return new WP_REST_Response(
			[
				'message'    => 'Consent saved',
				'consent_id' => $consent_id,
			],
			200
		);
	}

	/**
	 * Get anonymized IP hash for rate limiting.
	 *
	 * @return string
	 */
	private function get_ip_hash(): string {
		$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' ) );

		return substr( wp_hash( $ip ), 0, 12 );
	}
}
