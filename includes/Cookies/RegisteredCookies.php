<?php
/**
 * Cookies that other plugins register through the lw_cookie_known_cookies filter.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Cookies;

/**
 * Reads and validates the registered cookies. Nothing is cached or stored, so
 * translated strings follow the current locale and the declaration is only
 * merged at read time.
 *
 * @phpstan-type Entry array{name: string, provider: string, purpose: string, duration: string, category: string, type: string}
 */
final class RegisteredCookies {

	public const FILTER = 'lw_cookie_known_cookies';

	private const CATEGORIES = [ 'necessary', 'functional', 'analytics', 'marketing' ];

	private const TYPES = [ 'session', 'persistent' ];

	/**
	 * All valid registered entries; bad ones are dropped with a notice.
	 *
	 * @return array<int, Entry>
	 */
	public static function all(): array {
		$raw = apply_filters( 'lw_cookie_known_cookies', [] );

		if ( ! is_array( $raw ) ) {
			self::wrong( 'The callback must return an array of cookie entries.' );
			return [];
		}

		$entries = [];
		$seen    = [];

		foreach ( $raw as $item ) {
			$entry = self::clean( $item );

			if ( null === $entry || isset( $seen[ $entry['name'] ] ) ) {
				continue;
			}

			$seen[ $entry['name'] ] = true;
			$entries[]              = $entry;
		}

		return $entries;
	}

	/**
	 * The most specific registered entry for a cookie name.
	 *
	 * @param string $name Actual cookie name.
	 * @return Entry|null
	 */
	public static function find( string $name ): ?array {
		$best       = null;
		$best_score = -1;

		foreach ( self::all() as $entry ) {
			$score = CookiePattern::specificity( $entry['name'], $name );

			if ( $score > $best_score ) {
				$best       = $entry;
				$best_score = $score;
			}
		}

		return $best;
	}

	/**
	 * The registered entry for a scanned cookie, marked as coming from a plugin.
	 *
	 * @param string $name Actual cookie name.
	 * @return (Entry&array{source: string})|null
	 */
	public static function scan_lookup( string $name ): ?array {
		$entry = self::find( $name );

		return null === $entry ? null : $entry + [ 'source' => 'plugin' ];
	}

	/**
	 * The declaration rows: manual rows first, registered ones appended.
	 * A manual row wins, except that a registered "necessary" category is
	 * kept so the declaration matches what the guard lets through.
	 *
	 * @param array<int|string, mixed> $declared Manual rows (stored data, so not guaranteed complete).
	 * @return array<int, mixed>
	 */
	public static function merge_into( array $declared ): array {
		$registered = [];

		foreach ( self::all() as $entry ) {
			$registered[ $entry['name'] ] = $entry;
		}

		foreach ( $declared as $i => $row ) {
			$name = is_array( $row ) ? (string) ( $row['name'] ?? '' ) : '';

			if ( '' !== $name && isset( $registered[ $name ] ) ) {
				if ( 'necessary' === $registered[ $name ]['category'] ) {
					$declared[ $i ]['category'] = 'necessary';
				}
				unset( $registered[ $name ] );
			}
		}

		return array_merge( array_values( $declared ), array_values( $registered ) );
	}

	/**
	 * The guard's cookie config: built-in keys with registered entries on top.
	 *
	 * @param array<string, string> $builtin Built-in name/prefix to category map.
	 * @return array<string, string>
	 */
	public static function guard_map( array $builtin ): array {
		foreach ( self::all() as $entry ) {
			$builtin[ $entry['name'] ] = $entry['category'];
		}

		return $builtin;
	}

	/**
	 * Validate and normalize one registered entry.
	 *
	 * @param mixed $item Raw entry.
	 * @return Entry|null Null (after a notice) when unusable.
	 */
	private static function clean( mixed $item ): ?array {
		if ( ! is_array( $item ) ) {
			self::wrong( 'Each cookie entry must be an array.' );
			return null;
		}

		$name = self::text( $item['name'] ?? '' );

		if ( ! CookiePattern::is_valid( $name ) ) {
			self::wrong( 'A cookie entry needs a valid "name": no spaces, ";", "," or "=", and "*" only at the end.' );
			return null;
		}

		$category = $item['category'] ?? '';

		if ( ! in_array( $category, self::CATEGORIES, true ) ) {
			self::wrong( 'A cookie entry needs a "category" of necessary, functional, analytics or marketing.' );
			return null;
		}

		return [
			'name'     => $name,
			'provider' => self::text( $item['provider'] ?? '' ),
			'purpose'  => self::text( $item['purpose'] ?? '' ),
			'duration' => self::text( $item['duration'] ?? '' ),
			'category' => $category,
			'type'     => in_array( $item['type'] ?? '', self::TYPES, true ) ? $item['type'] : 'persistent',
		];
	}

	/**
	 * Clean a single-line text value; non-scalars become ''.
	 *
	 * @param mixed $value Raw value.
	 */
	private static function text( mixed $value ): string {
		return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
	}

	/**
	 * Tell the developer of the registering plugin what is wrong.
	 *
	 * @param string $message Plain English message.
	 */
	private static function wrong( string $message ): void {
		_doing_it_wrong( self::FILTER, $message, '1.9.7' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed developer strings, not user input.
	}
}
