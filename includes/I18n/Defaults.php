<?php
/**
 * Default English source strings for translatable options.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\I18n;

/**
 * Stable English source strings for option keys whose stored default is empty.
 *
 * These strings are the originals registered with Polylang / WPML and the keys
 * looked up at render time. Keeping them as literals (not __()-wrapped) is
 * intentional: registration must use the same value on every request even when
 * the active locale changes, otherwise multilingual plugins re-key translations
 * and previous edits orphan.
 */
final class Defaults {

	/**
	 * Get the literal English source for an option key, or null if none defined.
	 *
	 * @param string $key Option key.
	 * @return string|null
	 */
	public static function source( string $key ): ?string {
		return self::map()[ $key ] ?? null;
	}

	/**
	 * Get all option-key → English source pairs.
	 *
	 * @return array<string, string>
	 */
	public static function all(): array {
		return self::map();
	}

	/**
	 * Internal source map.
	 *
	 * @return array<string, string>
	 */
	private static function map(): array {
		return [
			'link_privacy_policy'    => 'Privacy Policy',
			'modal_title'            => 'Cookie Preferences',
			'label_required'         => '(Required)',
			'col_cookie'             => 'Cookie',
			'col_provider'           => 'Provider',
			'col_purpose'            => 'Purpose',
			'col_duration'           => 'Duration',
			'col_type'               => 'Type',
			'btn_manage_preferences' => 'Manage Cookie Preferences',
			'btn_delete_all'         => 'Delete All Cookies',
		];
	}
}
