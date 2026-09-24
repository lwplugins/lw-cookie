<?php
/**
 * Settings store for the admin API.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin;

use LightweightPlugins\Cookie\I18n\MultilingualDetector;
use LightweightPlugins\Cookie\Options;

/**
 * Reads the settings as typed values and applies partial updates.
 *
 * A partial update merges the submitted keys onto the stored options, so a
 * key the client did not send keeps its stored value (never "absent bool =
 * false"). Unknown keys are dropped, and the translatable keys are ignored
 * while a multilingual plugin owns them.
 */
final class SettingsStore {

	/**
	 * Current settings, every default key typed like its default.
	 *
	 * @return array<string, mixed>
	 */
	public static function current(): array {
		Options::clear_cache();

		return self::typed( Options::get_all(), Options::get_defaults() );
	}

	/**
	 * Keys the client may not write right now.
	 *
	 * @return array<int, string>
	 */
	public static function locked_keys(): array {
		return MultilingualDetector::is_active() ? TranslatableFields::keys() : [];
	}

	/**
	 * Apply a partial update and return the new settings. With
	 * `unlock_source_text` set, the translatable keys are written even
	 * while a multilingual plugin is active (the source-language text it
	 * translates from), like the classic screen's "Unlock" button.
	 *
	 * @param array<string, mixed> $body Submitted option keys.
	 * @return array<string, mixed>
	 */
	public static function save( array $body ): array {
		$merged = self::merge(
			$body,
			get_option( Options::OPTION_NAME, [] ),
			Options::get_defaults(),
			empty( $body['unlock_source_text'] ) ? self::locked_keys() : [],
			TranslatableFields::textarea_keys()
		);

		update_option( Options::OPTION_NAME, $merged );
		Options::clear_cache();

		return self::current();
	}

	/**
	 * Merge submitted keys onto the stored options. Only the submitted keys
	 * are sanitized; stored values are kept as they are.
	 *
	 * @param array<string, mixed> $body          Submitted option keys.
	 * @param mixed                $stored        Stored option value.
	 * @param array<string, mixed> $defaults      Option defaults.
	 * @param array<int, string>   $locked_keys   Keys to ignore.
	 * @param array<int, string>   $textarea_keys Keys holding multi-line text.
	 * @return array<string, mixed>
	 */
	public static function merge( array $body, mixed $stored, array $defaults, array $locked_keys, array $textarea_keys ): array {
		$stored    = is_array( $stored ) ? array_intersect_key( $stored, $defaults ) : [];
		$current   = array_merge( $defaults, $stored );
		$submitted = array_diff_key( array_intersect_key( $body, $defaults ), array_flip( $locked_keys ) );

		return array_merge( $current, SettingsSanitizer::sanitize( $submitted, $current, $defaults, $textarea_keys ) );
	}

	/**
	 * Cast every default key to its default's type for the JSON response.
	 *
	 * @param array<string, mixed> $values   Option values.
	 * @param array<string, mixed> $defaults Option defaults.
	 * @return array<string, mixed>
	 */
	public static function typed( array $values, array $defaults ): array {
		$typed = [];

		foreach ( $defaults as $key => $default ) {
			$value = array_key_exists( $key, $values ) ? $values[ $key ] : $default;

			if ( is_bool( $default ) ) {
				$typed[ $key ] = (bool) $value;
			} elseif ( is_int( $default ) ) {
				$typed[ $key ] = is_numeric( $value ) ? (int) $value : $default;
			} elseif ( is_array( $default ) ) {
				$typed[ $key ] = is_array( $value ) ? array_values( array_filter( $value, 'is_array' ) ) : [];
			} else {
				$typed[ $key ] = is_scalar( $value ) ? (string) $value : (string) $default;
			}
		}

		return $typed;
	}
}
