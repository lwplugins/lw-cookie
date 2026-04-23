<?php
/**
 * Translation lookup for user-configurable strings.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\I18n;

use LightweightPlugins\Cookie\Options;

/**
 * Returns translated values for admin-editable strings via Polylang/WPML.
 *
 * Registration happens in StringRegistry. This class is the read-side
 * counterpart used by frontend renderers.
 */
final class Strings {

	/**
	 * Context label shown in Polylang/WPML string tables.
	 */
	public const CONTEXT = 'LW Cookie';

	/**
	 * Register the registration side.
	 *
	 * @return void
	 */
	public static function init(): void {
		StringRegistry::init();
	}

	/**
	 * Get the translated value for an option key.
	 *
	 * @param string $key Option key.
	 * @return string
	 */
	public static function get( string $key ): string {
		$value = (string) Options::get( $key, '' );

		return self::translate( $key, $value );
	}

	/**
	 * Get translated cookie category definitions.
	 *
	 * @return array<string, array{name: string, description: string, required: bool}>
	 */
	public static function get_categories(): array {
		$categories = Options::get_categories();

		foreach ( $categories as $key => $category ) {
			$categories[ $key ]['name']        = self::translate( 'cat_' . $key . '_name', (string) $category['name'] );
			$categories[ $key ]['description'] = self::translate( 'cat_' . $key . '_desc', (string) $category['description'] );
		}

		return $categories;
	}

	/**
	 * Get declared cookies grouped by category, with translated text fields.
	 *
	 * @return array<string, array<int, array<string, string>>>
	 */
	public static function get_cookies_by_category(): array {
		$cookies = Options::get( 'declared_cookies', [] );
		$grouped = [];

		if ( ! is_array( $cookies ) ) {
			return $grouped;
		}

		foreach ( $cookies as $cookie ) {
			if ( empty( $cookie['name'] ) ) {
				continue;
			}

			$category = ! empty( $cookie['category'] ) ? (string) $cookie['category'] : 'necessary';
			$name     = (string) $cookie['name'];

			$grouped[ $category ][] = [
				'name'     => $name,
				'provider' => self::translate( 'cookie_' . $name . '_provider', (string) ( $cookie['provider'] ?? '' ) ),
				'purpose'  => self::translate( 'cookie_' . $name . '_purpose', (string) ( $cookie['purpose'] ?? '' ) ),
				'duration' => self::translate( 'cookie_' . $name . '_duration', (string) ( $cookie['duration'] ?? '' ) ),
				'type'     => (string) ( $cookie['type'] ?? 'persistent' ),
			];
		}

		return $grouped;
	}

	/**
	 * Translate a raw string using the first available multilingual plugin.
	 *
	 * @param string $key   String key.
	 * @param string $value Raw value.
	 * @return string
	 */
	private static function translate( string $key, string $value ): string {
		if ( '' === $value ) {
			return '';
		}

		if ( function_exists( 'pll__' ) ) {
			return (string) pll__( $value );
		}

		if ( has_filter( 'wpml_translate_single_string' ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML's own API filter.
			return (string) apply_filters( 'wpml_translate_single_string', $value, self::CONTEXT, $key );
		}

		return $value;
	}
}
