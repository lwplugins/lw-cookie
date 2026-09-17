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
		return self::resolve( $key, (string) Options::get( $key, '' ) );
	}

	/**
	 * Resolve a value.
	 *
	 * The active multilingual plugin always wins: whatever the admin typed in
	 * Polylang's Strings table or WPML's String Translation is what the visitor
	 * must see. Those plugins echo the source back when they hold no translation
	 * for it, so an unchanged return means "not translated" — and only then do we
	 * fall back to the text-domain-localised default, which lets the built-in
	 * strings follow the site language on single-language sites.
	 *
	 * @param string $key   Option key.
	 * @param string $value Stored value.
	 * @return string
	 */
	private static function resolve( string $key, string $value ): string {
		$translated = self::translate( $key, $value );

		if ( $translated !== $value ) {
			return $translated;
		}

		$localized = self::localized_default( $key );

		if ( null !== $localized && self::english_default( $key ) === $value ) {
			return $localized;
		}

		return $value;
	}

	/**
	 * The plugin's built-in English default for an option key.
	 *
	 * @param string $key Option key.
	 * @return string
	 */
	private static function english_default( string $key ): string {
		return (string) ( Options::get_defaults()[ $key ] ?? '' );
	}

	/**
	 * Text-domain-localised default for a built-in string, or null if the key
	 * has no localisable default. Literal __() calls so the .pot picks them up.
	 *
	 * @param string $key Option key.
	 * @return string|null
	 */
	private static function localized_default( string $key ): ?string {
		$map = [
			'banner_title'        => __( 'We value your privacy', 'lw-cookie' ),
			'banner_message'      => __( 'We use cookies to enhance your browsing experience and analyze our traffic.', 'lw-cookie' ),
			'btn_accept_all'      => __( 'Accept All', 'lw-cookie' ),
			'btn_reject_all'      => __( 'Reject All', 'lw-cookie' ),
			'btn_customize'       => __( 'Customize', 'lw-cookie' ),
			'btn_save'            => __( 'Save Preferences', 'lw-cookie' ),
			'cat_necessary_name'  => __( 'Necessary', 'lw-cookie' ),
			'cat_necessary_desc'  => __( 'Essential cookies required for the website to function.', 'lw-cookie' ),
			'cat_functional_name' => __( 'Functional', 'lw-cookie' ),
			'cat_functional_desc' => __( 'These cookies enable enhanced functionality and personalization.', 'lw-cookie' ),
			'cat_analytics_name'  => __( 'Analytics', 'lw-cookie' ),
			'cat_analytics_desc'  => __( 'These cookies help us understand how visitors interact with our website.', 'lw-cookie' ),
			'cat_marketing_name'  => __( 'Marketing', 'lw-cookie' ),
			'cat_marketing_desc'  => __( 'These cookies are used to deliver relevant advertisements.', 'lw-cookie' ),
		];

		return $map[ $key ] ?? null;
	}

	/**
	 * Get the translated value for an option key, falling back to a default
	 * when the user has not set a custom value.
	 *
	 * Used for strings that have a sensible textdomain-translated default
	 * (e.g. "Privacy Policy") but can be overridden in the Texts tab.
	 *
	 * When the option is empty, the literal English source from Defaults is
	 * looked up via the multilingual plugin — matching what StringRegistry
	 * registered — so user translations of defaults take effect. Only when no
	 * such translation exists is the textdomain-translated default returned.
	 *
	 * @param string $key             Option key.
	 * @param string $textdomain_text The default text, already passed through __().
	 * @return string
	 */
	public static function get_or_default( string $key, string $textdomain_text ): string {
		$value = (string) Options::get( $key, '' );

		if ( '' !== $value ) {
			return self::translate( $key, $value );
		}

		$source = Defaults::source( $key );

		if ( null !== $source ) {
			$translated = self::translate( $key, $source );

			if ( $translated !== $source ) {
				return $translated;
			}
		}

		return $textdomain_text;
	}

	/**
	 * Get translated cookie category definitions.
	 *
	 * @return array<string, array{name: string, description: string, required: bool}>
	 */
	public static function get_categories(): array {
		$categories = Options::get_categories();

		foreach ( $categories as $key => $category ) {
			$categories[ $key ]['name']        = self::resolve( 'cat_' . $key . '_name', (string) $category['name'] );
			$categories[ $key ]['description'] = self::resolve( 'cat_' . $key . '_desc', (string) $category['description'] );
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

			$category  = ! empty( $cookie['category'] ) ? (string) $cookie['category'] : 'necessary';
			$name      = (string) $cookie['name'];
			$safe_name = sanitize_key( $name );
			if ( '' === $safe_name ) {
				$safe_name = md5( $name );
			}

			$grouped[ $category ][] = [
				'name'     => $name,
				'provider' => self::translate( 'cookie_' . $safe_name . '_provider', (string) ( $cookie['provider'] ?? '' ) ),
				'purpose'  => self::translate( 'cookie_' . $safe_name . '_purpose', (string) ( $cookie['purpose'] ?? '' ) ),
				'duration' => self::translate( 'cookie_' . $safe_name . '_duration', (string) ( $cookie['duration'] ?? '' ) ),
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
