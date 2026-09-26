<?php
/**
 * Validated partial settings update for automation.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\SiteManager;

use LightweightPlugins\Cookie\Admin\SettingsSanitizer;
use LightweightPlugins\Cookie\Admin\SettingsStore;
use LightweightPlugins\Cookie\Options;

/**
 * Checks each submitted key against the automation policy, the multilingual
 * lock and the admin sanitizer's validity rules, then saves the valid keys
 * through SettingsStore::save() — the same sanitized partial-update path as
 * the React admin API. Invalid keys are not stored and are reported back.
 */
final class OptionsWriter {

	public const UNKNOWN_KEY = 'unknown_key';
	public const LOCKED_KEY  = 'locked';

	/**
	 * Apply a partial update.
	 *
	 * @param array<mixed, mixed> $options Submitted option keys and values.
	 * @return array{updated: array<int, string>, rejected: array<string, string>, options: array<string, mixed>}
	 *         `rejected` maps a key to its problem code (UNKNOWN_KEY, LOCKED_KEY
	 *         or a SettingsSanitizer::PROBLEM_* code).
	 */
	public static function apply( array $options ): array {
		$defaults = Options::get_defaults();
		$locked   = SettingsStore::locked_keys();
		$accepted = [];
		$rejected = [];

		foreach ( $options as $key => $value ) {
			$key     = (string) $key;
			$problem = self::problem( $key, $value, $defaults, $locked );

			if ( null === $problem ) {
				$accepted[ $key ] = $value;
			} else {
				$rejected[ $key ] = $problem;
			}
		}

		return [
			'updated'  => array_keys( $accepted ),
			'rejected' => $rejected,
			'options'  => [] === $accepted ? SettingsStore::current() : SettingsStore::save( $accepted ),
		];
	}

	/**
	 * Why one key/value cannot be written, or null when it can.
	 *
	 * @param string               $key      Option key.
	 * @param mixed                $value    Submitted value.
	 * @param array<string, mixed> $defaults Option defaults.
	 * @param array<int, string>   $locked   Keys owned by a multilingual plugin.
	 * @return string|null
	 */
	private static function problem( string $key, mixed $value, array $defaults, array $locked ): ?string {
		if ( ! AutomationPolicy::allows( $key ) || ! array_key_exists( $key, $defaults ) ) {
			return self::UNKNOWN_KEY;
		}

		if ( in_array( $key, $locked, true ) ) {
			return self::LOCKED_KEY;
		}

		return SettingsSanitizer::problem( $key, $value, $defaults[ $key ] );
	}
}
