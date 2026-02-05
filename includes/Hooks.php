<?php
/**
 * WordPress Hooks for third-party plugin integration.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie;

use LightweightPlugins\Cookie\Consent\Manager;

/**
 * Provides WordPress hooks for other plugins to integrate with LW Cookie.
 *
 * Usage examples for third-party plugins:
 *
 * // Get consent categories
 * $categories = apply_filters( 'lw_cookie_consent_categories', [] );
 * if ( $categories['marketing'] ) { // load marketing scripts }
 *
 * // Check if user has given consent
 * $has_consent = apply_filters( 'lw_cookie_has_consent', false );
 *
 * // Check if specific category is allowed
 * $analytics_allowed = apply_filters( 'lw_cookie_is_category_allowed', false, 'analytics' );
 *
 * // Prevent blocking specific scripts (e.g., if another plugin handles consent)
 * add_filter( 'lw_cookie_should_block_script', function( $should_block, $handle, $src, $category ) {
 *     if ( $handle === 'my-plugin-pixel' ) return false; // Don't block, I handle it myself
 *     return $should_block;
 * }, 10, 4 );
 */
final class Hooks {

	/**
	 * Consent manager instance.
	 *
	 * @var Manager
	 */
	private Manager $consent_manager;

	/**
	 * Constructor.
	 *
	 * @param Manager $consent_manager Consent manager instance.
	 */
	public function __construct( Manager $consent_manager ) {
		$this->consent_manager = $consent_manager;
		$this->register_filters();
	}

	/**
	 * Register all filters.
	 *
	 * @return void
	 */
	private function register_filters(): void {
		add_filter( 'lw_cookie_consent_categories', [ $this, 'get_consent_categories' ] );
		add_filter( 'lw_cookie_has_consent', [ $this, 'has_consent' ] );
		add_filter( 'lw_cookie_is_category_allowed', [ $this, 'is_category_allowed' ], 10, 2 );
		add_filter( 'lw_cookie_consent_id', [ $this, 'get_consent_id' ] );
	}

	/**
	 * Get all consent categories and their states.
	 *
	 * @param array $_default Default value (ignored, required by filter).
	 * @return array<string, bool> Category states.
	 */
	public function get_consent_categories( array $_default ): array {
		unset( $_default );
		return $this->consent_manager->get_allowed_categories();
	}

	/**
	 * Check if user has given any consent.
	 *
	 * @param bool $_default Default value (ignored, required by filter).
	 * @return bool
	 */
	public function has_consent( bool $_default ): bool {
		unset( $_default );
		return $this->consent_manager->has_consent();
	}

	/**
	 * Check if specific category is allowed.
	 *
	 * @param bool   $_default Default value (ignored, required by filter).
	 * @param string $category Category to check.
	 * @return bool
	 */
	public function is_category_allowed( bool $_default, string $category ): bool {
		unset( $_default );
		return $this->consent_manager->is_category_allowed( $category );
	}

	/**
	 * Get current consent ID.
	 *
	 * @param string|null $_default Default value (ignored, required by filter).
	 * @return string|null
	 */
	public function get_consent_id( ?string $_default ): ?string {
		unset( $_default );
		return $this->consent_manager->get_consent_id();
	}
}
