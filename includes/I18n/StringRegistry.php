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
		'link_privacy_policy',
		'modal_title',
		'label_required',
		'col_cookie',
		'col_provider',
		'col_purpose',
		'col_duration',
		'col_type',
		'btn_manage_preferences',
		'btn_delete_all',
		'blocked_embed_button',
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
		'blocked_embed_message',
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
	 * Polylang's store for strings registered through the WPML API.
	 */
	private const WPML_COMPAT_OPTION = 'polylang_wpml_strings';

	/**
	 * Register every editable string with the active multilingual plugin.
	 *
	 * Exactly one API is used. Polylang ships a WPML compatibility layer, so
	 * `wpml_register_single_string` has a listener even when WPML is not
	 * installed — sending our strings to both APIs registered each of them
	 * twice. See self::unregister_wpml_duplicates() for what that broke.
	 *
	 * @return void
	 */
	public static function register_all(): void {
		$pll  = function_exists( 'pll_register_string' );
		$wpml = ! $pll && ( has_action( 'wpml_register_single_string' ) || function_exists( 'icl_register_string' ) );

		if ( ! $pll && ! $wpml ) {
			return;
		}

		if ( $pll ) {
			self::unregister_wpml_duplicates();
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
	 * Drop the duplicate rows a previous version left in Polylang's table.
	 *
	 * Until 1.7.5 every string was sent to Polylang *and* to the WPML action,
	 * which Polylang's own compatibility layer answers. Polylang keys its
	 * native registrations by md5( $string ) but the WPML ones by
	 * md5( "$context | $name" ), so each string showed up twice in the Strings
	 * table with identical source text. Both rows write the same translation
	 * entry on save, and the copy the admin had not touched — submitted with
	 * its stale value — overwrote the one they had just edited. The page came
	 * back showing the old translation, as if nothing had been saved.
	 *
	 * Runs only with Polylang as the provider; under real WPML its own store
	 * is the one holding our strings and must be left alone.
	 *
	 * @return void
	 */
	private static function unregister_wpml_duplicates(): void {
		if ( ! function_exists( 'icl_unregister_string' ) || defined( 'ICL_SITEPRESS_VERSION' ) ) {
			return;
		}

		$stored = get_option( self::WPML_COMPAT_OPTION );

		if ( ! is_array( $stored ) ) {
			return;
		}

		foreach ( $stored as $string ) {
			if ( ! is_array( $string ) || Strings::CONTEXT !== ( $string['context'] ?? '' ) ) {
				continue;
			}

			icl_unregister_string( Strings::CONTEXT, (string) ( $string['name'] ?? '' ) );
		}
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
			$name      = (string) $cookie['name'];
			$safe_name = sanitize_key( $name );
			if ( '' === $safe_name ) {
				$safe_name = md5( $name );
			}

			foreach ( [ 'provider', 'purpose', 'duration' ] as $field ) {
				if ( empty( $cookie[ $field ] ) ) {
					continue;
				}
				$key = 'cookie_' . $safe_name . '_' . $field;
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
			// Fall back to the literal English source so the string still
			// appears in Polylang / WPML translation tables. Stable across
			// requests so previously-saved translations are not orphaned.
			$value = Defaults::source( $key ) ?? '';
			if ( '' === $value ) {
				return;
			}
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
