<?php
/**
 * Picks the hub copy that owns the LW Plugins page.
 *
 * Synced from lwplugins/admin-hub 1.0.0 by bin/sync.php. Do not edit
 * this copy: change the admin-hub repo and sync again.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin\Hub;

/**
 * Pure selection logic, no WordPress calls: the highest version wins, a tie
 * goes to the lowest class name so every copy reaches the same verdict.
 */
final class Negotiator {

	/**
	 * The winning candidate, or null when there is no valid one.
	 *
	 * @param array<int|string, mixed> $candidates Items shaped
	 *                                             { version: string, class: string, file?: string }.
	 * @return array{version: string, class: string, file: string}|null
	 */
	public static function winner( array $candidates ): ?array {
		$best = null;

		foreach ( $candidates as $candidate ) {
			$candidate = self::validate( $candidate );

			if ( null !== $candidate && ( null === $best || self::beats( $candidate, $best ) ) ) {
				$best = $candidate;
			}
		}

		return $best;
	}

	/**
	 * Whether candidate $a ranks above candidate $b.
	 *
	 * @param array{version: string, class: string, file: string} $a Candidate.
	 * @param array{version: string, class: string, file: string} $b Candidate.
	 * @return bool
	 */
	private static function beats( array $a, array $b ): bool {
		$order = version_compare( $a['version'], $b['version'] );

		if ( 0 !== $order ) {
			return $order > 0;
		}

		return strcmp( $a['class'], $b['class'] ) < 0;
	}

	/**
	 * Normalize one candidate, or null when it is malformed.
	 *
	 * @param mixed $candidate Raw candidate.
	 * @return array{version: string, class: string, file: string}|null
	 */
	private static function validate( $candidate ): ?array {
		if ( ! is_array( $candidate ) || ! isset( $candidate['version'], $candidate['class'] ) ) {
			return null;
		}

		$version = is_string( $candidate['version'] ) ? $candidate['version'] : '';
		$class   = is_string( $candidate['class'] ) ? $candidate['class'] : '';

		if ( ! preg_match( '/^\d+(\.\d+){0,3}$/', $version ) || '' === $class ) {
			return null;
		}

		return array(
			'version' => $version,
			'class'   => $class,
			'file'    => isset( $candidate['file'] ) && is_string( $candidate['file'] ) ? $candidate['file'] : '',
		);
	}
}
