<?php
/**
 * Registers user-configurable strings with Polylang and WPML.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\I18n;

use LightweightPlugins\Cookie\Options;

/**
 * Announces admin-editable strings to active multilingual plugins so
 * they appear in Polylang's Strings table and WPML's String Translation UI.
 */
final class StringRegistry {

	/**
	 * Single-line translatable option keys.
	 *
	 * @var array<int, string>
	 */
	private const SINGLE_LINE_KEYS = [
		'banner_title',
		'btn_accept_all',
		'btn_reject_all',
		'btn_customize',
		'btn_save',
		'cat_necessary_name',
		'cat_functional_name',
		'cat_analytics_name',
		'cat_marketing_name',
	];

	/**
	 * Multi-line translatable option keys.
	 *
	 * @var array<int, string>
	 */
	private const MULTI_LINE_KEYS = [
		'banner_message',
		'cat_necessary_desc',
		'cat_functional_desc',
		'cat_analytics_desc',
		'cat_marketing_desc',
	];

	/**
	 * Register hooks. Safe to call every request.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'init', [ self::class, 'register_all' ] );
	}

	/**
	 * Register every editable string with active multilingual plugins.
	 *
	 * @return void
	 */
	public static function register_all(): void {
		$pll  = function_exists( 'pll_register_string' );
		$wpml = has_action( 'wpml_register_single_string' ) || function_exists( 'icl_register_string' );

		if ( ! $pll && ! $wpml ) {
			return;
		}

		foreach ( self::SINGLE_LINE_KEYS as $key ) {
			self::register( $key, false, $pll, $wpml );
		}

		foreach ( self::MULTI_LINE_KEYS as $key ) {
			self::register( $key, true, $pll, $wpml );
		}

		self::register_declared_cookies( $pll, $wpml );
	}

	/**
	 * Register declared cookie text fields (purpose, provider, duration).
	 *
	 * @param bool $pll  Polylang is active.
	 * @param bool $wpml WPML is active.
	 * @return void
	 */
	private static function register_declared_cookies( bool $pll, bool $wpml ): void {
		$cookies = Options::get( 'declared_cookies', [] );
		if ( ! is_array( $cookies ) ) {
			return;
		}

		foreach ( $cookies as $cookie ) {
			if ( empty( $cookie['name'] ) ) {
				continue;
			}
			$name = (string) $cookie['name'];

			foreach ( [ 'provider', 'purpose', 'duration' ] as $field ) {
				if ( empty( $cookie[ $field ] ) ) {
					continue;
				}
				$key = 'cookie_' . $name . '_' . $field;
				self::register( $key, 'purpose' === $field, $pll, $wpml, (string) $cookie[ $field ] );
			}
		}
	}

	/**
	 * Register a single string with Polylang and/or WPML.
	 *
	 * @param string      $key       Option key or synthetic cookie key (used as display name).
	 * @param bool        $multiline Whether the string spans multiple lines.
	 * @param bool        $pll       Polylang is active.
	 * @param bool        $wpml      WPML is active.
	 * @param string|null $value     Override value (for non-option strings).
	 * @return void
	 */
	private static function register( string $key, bool $multiline, bool $pll, bool $wpml, ?string $value = null ): void {
		if ( null === $value ) {
			$value = (string) Options::get( $key, '' );
		}

		if ( '' === $value ) {
			return;
		}

		if ( $pll ) {
			pll_register_string( $key, $value, Strings::CONTEXT, $multiline );
		}

		if ( $wpml ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML's own API action.
			do_action( 'wpml_register_single_string', Strings::CONTEXT, $key, $value );
		}
	}
}
