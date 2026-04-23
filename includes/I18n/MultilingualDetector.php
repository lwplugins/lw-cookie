<?php
/**
 * Detects active multilingual plugins.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\I18n;

/**
 * Identifies Polylang, WPML, or TranslatePress and returns a link
 * to the matching string translation UI.
 */
final class MultilingualDetector {

	/**
	 * Detect the active multilingual plugin.
	 *
	 * @return array{slug: string, name: string, url: string}|null
	 */
	public static function active(): ?array {
		if ( self::is_polylang() ) {
			return [
				'slug' => 'polylang',
				'name' => 'Polylang',
				'url'  => admin_url( 'admin.php?page=mlang_strings&group=' . rawurlencode( Strings::CONTEXT ) ),
			];
		}

		if ( self::is_wpml() ) {
			return [
				'slug' => 'wpml',
				'name' => 'WPML',
				'url'  => admin_url( 'admin.php?page=wpml-string-translation%2Fmenu%2Fstring-translation.php&context=' . rawurlencode( Strings::CONTEXT ) ),
			];
		}

		if ( self::is_translatepress() ) {
			return [
				'slug' => 'translatepress',
				'name' => 'TranslatePress',
				'url'  => admin_url( 'options-general.php?page=translate-press' ),
			];
		}

		return null;
	}

	/**
	 * Whether any supported multilingual plugin is active.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return null !== self::active();
	}

	/**
	 * Polylang detection.
	 *
	 * @return bool
	 */
	private static function is_polylang(): bool {
		return defined( 'POLYLANG_VERSION' ) || function_exists( 'pll__' );
	}

	/**
	 * WPML detection.
	 *
	 * @return bool
	 */
	private static function is_wpml(): bool {
		return defined( 'ICL_SITEPRESS_VERSION' );
	}

	/**
	 * TranslatePress detection.
	 *
	 * @return bool
	 */
	private static function is_translatepress(): bool {
		return defined( 'TRP_PLUGIN_VERSION' ) || class_exists( 'TRP_Translate_Press', false );
	}
}
