<?php
/**
 * Complianz settings importer.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Migration;

use LightweightPlugins\Cookie\Admin\SettingsStore;
use LightweightPlugins\Cookie\Admin\TranslatableFields;
use LightweightPlugins\Cookie\Migration\Complianz\CookieMapper;
use LightweightPlugins\Cookie\Migration\Complianz\SettingsMapper;
use LightweightPlugins\Cookie\Migration\Complianz\Source;
use LightweightPlugins\Cookie\Options;

/**
 * Imports the Complianz settings into lw_cookie_options.
 *
 * Cookies Complianz has no category for are skipped and only counted:
 * declaring them under a guessed category would misinform visitors.
 *
 * The mapped values go through the regular settings merge/sanitizer, so the
 * import can store nothing the settings screen could not. Cookies already
 * declared (same name) are kept as they are; keys owned by an active
 * multilingual plugin are left alone and reported.
 */
final class Importer {

	/**
	 * Import (or, with $dry_run, only report) the Complianz settings.
	 *
	 * @param bool $dry_run Report without writing.
	 * @return array{settings: array<string, mixed>, locked: array<int, string>, cookies_added: array<int, string>, cookies_existing: array<int, string>, cookies_skipped: int}|null Null when no Complianz data exists.
	 */
	public static function run( bool $dry_run ): ?array {
		if ( ! Source::is_available() ) {
			return null;
		}

		$plan = self::plan(
			Source::read(),
			get_option( Options::OPTION_NAME, [] ),
			Options::get_defaults(),
			SettingsStore::locked_keys(),
			TranslatableFields::textarea_keys()
		);

		if ( ! $dry_run ) {
			update_option( Options::OPTION_NAME, $plan['options'] );
			Options::clear_cache();
		}

		return $plan['report'];
	}

	/**
	 * Compute the new options and the report.
	 *
	 * @param array<string, mixed> $data          Source::read() shape.
	 * @param mixed                $stored        Stored lw_cookie_options.
	 * @param array<string, mixed> $defaults      Option defaults.
	 * @param array<int, string>   $locked        Keys to leave alone.
	 * @param array<int, string>   $textarea_keys Multi-line text keys.
	 * @return array{options: array<string, mixed>, report: array{settings: array<string, mixed>, locked: array<int, string>, cookies_added: array<int, string>, cookies_existing: array<int, string>, cookies_skipped: int}}
	 */
	public static function plan( array $data, mixed $stored, array $defaults, array $locked, array $textarea_keys ): array {
		$before = SettingsStore::merge( [], $stored, $defaults, [], [] );
		$patch  = SettingsMapper::map( $data['banner'] ?? [], $data['options'] ?? [], (int) ( $data['privacy_page'] ?? 0 ) );

		$declared = is_array( $before['declared_cookies'] ?? null ) ? $before['declared_cookies'] : [];
		$known    = array_column( $declared, 'name' );
		$mapped   = CookieMapper::map( $data['cookies'] ?? [], $data['services'] ?? [], (string) ( $data['language'] ?? 'en' ) );
		$incoming = array_values( array_filter( $mapped, static fn( array $cookie ): bool => '' !== $cookie['category'] ) );
		$added    = array_values( array_filter( $incoming, static fn( array $cookie ): bool => ! in_array( $cookie['name'], $known, true ) ) );

		if ( [] !== $added ) {
			$patch['declared_cookies'] = array_merge( $declared, $added );
		}

		$after     = SettingsStore::merge( $patch, $stored, $defaults, $locked, $textarea_keys );
		$typed_old = SettingsStore::typed( $before, $defaults );
		$typed_new = SettingsStore::typed( $after, $defaults );
		$changed   = [];

		foreach ( array_keys( $patch ) as $key ) {
			if ( 'declared_cookies' !== $key && $typed_new[ $key ] !== $typed_old[ $key ] ) {
				$changed[ $key ] = $typed_new[ $key ];
			}
		}

		return [
			'options' => $after,
			'report'  => [
				'settings'         => $changed,
				'locked'           => array_values( array_intersect( array_keys( $patch ), $locked ) ),
				'cookies_added'    => array_column( $added, 'name' ),
				'cookies_existing' => array_values( array_intersect( array_column( $incoming, 'name' ), $known ) ),
				'cookies_skipped'  => count( $mapped ) - count( $incoming ),
			],
		];
	}
}
