<?php
/**
 * Placeholder Registry — the handles printed as placeholders in this request.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Blocking;

/**
 * Remembers which script handles became placeholders, and finds whether a
 * handle depends (directly or transitively) on one of them.
 *
 * WordPress prints dependencies first, so a dependency that became a
 * placeholder is already recorded when its dependents are printed.
 */
final class PlaceholderRegistry {

	/**
	 * Handles printed as placeholders, with their category.
	 *
	 * @var array<string, string>
	 */
	private static array $handles = [];

	/**
	 * Record a handle printed as a placeholder.
	 *
	 * @param string $handle   Script handle.
	 * @param string $category Category of the placeholder.
	 * @return void
	 */
	public static function remember( string $handle, string $category ): void {
		self::$handles[ $handle ] = $category;
	}

	/**
	 * Forget every recorded handle.
	 *
	 * @return void
	 */
	public static function clear(): void {
		self::$handles = [];
	}

	/**
	 * Category of the first placeholdered handle among the transitive deps.
	 *
	 * @param string $handle Script handle.
	 * @return string|null Null when no dependency is a placeholder.
	 */
	public static function blocked_dependency( string $handle ): ?string {
		if ( [] === self::$handles ) {
			return null;
		}

		$queue = self::deps_of( $handle );
		$seen  = [ $handle => true ];

		while ( [] !== $queue ) {
			$dep = array_shift( $queue );
			if ( isset( $seen[ $dep ] ) ) {
				continue;
			}
			$seen[ $dep ] = true;

			if ( isset( self::$handles[ $dep ] ) ) {
				return self::$handles[ $dep ];
			}

			$queue = array_merge( $queue, self::deps_of( $dep ) );
		}

		return null;
	}

	/**
	 * Registered dependencies of a script handle (empty when unknown).
	 *
	 * @param string $handle Script handle.
	 * @return array<int, string>
	 */
	private static function deps_of( string $handle ): array {
		$scripts = $GLOBALS['wp_scripts'] ?? null;

		if ( ! is_object( $scripts ) || ! isset( $scripts->registered ) || ! is_array( $scripts->registered ) ) {
			return [];
		}

		$item = $scripts->registered[ $handle ] ?? null;

		if ( ! is_object( $item ) || ! isset( $item->deps ) || ! is_array( $item->deps ) ) {
			return [];
		}

		return array_values( array_filter( $item->deps, 'is_string' ) );
	}
}
