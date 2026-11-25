<?php
/**
 * Consent log cleanup.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Consent;

use DateTimeImmutable;
use DateTimeZone;
use LightweightPlugins\Cookie\Database\Schema;

/**
 * Deletes consent log rows. Shared by the daily retention cleanup and the
 * `wp lw-cookie clear-logs` command, so both use the same cutoff.
 *
 * Rows store created_at as current_time( 'mysql' ) (site-local time), so the
 * cutoff is computed in PHP from the same clock instead of MySQL's NOW(),
 * which follows the database server's time zone.
 */
final class LogCleaner {

	/**
	 * Rows deleted per query.
	 */
	public const BATCH_SIZE = 1000;

	/**
	 * Largest day count accepted on the command line (100 years).
	 */
	public const MAX_DAYS = 36500;

	/**
	 * Parse a day count given on the command line.
	 *
	 * @param mixed $value Raw value (e.g. the --older-than string).
	 * @return int|null Days, or null unless it is a whole number from 1 to MAX_DAYS.
	 */
	public static function parse_days( mixed $value ): ?int {
		if ( is_int( $value ) ) {
			$value = (string) $value;
		}

		if ( ! is_string( $value ) ) {
			return null;
		}

		$value = trim( $value );

		if ( '' === $value || ! ctype_digit( $value ) || strlen( $value ) > 6 ) {
			return null;
		}

		$days = (int) $value;

		return $days >= 1 && $days <= self::MAX_DAYS ? $days : null;
	}

	/**
	 * Timestamp before which rows are deleted.
	 *
	 * @param int    $days Days to keep.
	 * @param string $now  Site-local "now" in MySQL format (current_time( 'mysql' )).
	 * @return string Cutoff in MySQL format, on the same clock as $now.
	 */
	public static function cutoff( int $days, string $now ): string {
		// UTC only as a fixed calendar: $now is a wall-clock time, so a DST
		// switch in the site's time zone must not shift the cutoff by an hour.
		$utc = new DateTimeZone( 'UTC' );

		return ( new DateTimeImmutable( $now, $utc ) )
			->modify( sprintf( '-%d days', $days ) )
			->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Delete rows older than the given number of days, in batches.
	 *
	 * @param int $days        Days to keep (at least 1).
	 * @param int $max_batches Most batches to run; the rest waits for the next run.
	 * @return int|null Rows deleted, or null when a query failed.
	 */
	public static function delete_older_than( int $days, int $max_batches ): ?int {
		global $wpdb;

		$table   = $wpdb->prefix . Schema::TABLE_CONSENTS;
		$cutoff  = self::cutoff( max( 1, $days ), current_time( 'mysql' ) );
		$deleted = 0;

		for ( $batch = 0; $batch < $max_batches; $batch++ ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->query(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix and a constant.
					"DELETE FROM {$table} WHERE created_at < %s LIMIT %d",
					$cutoff,
					self::BATCH_SIZE
				)
			);

			if ( false === $rows ) {
				return null;
			}

			$deleted += (int) $rows;

			if ( $rows < self::BATCH_SIZE ) {
				break;
			}
		}

		return $deleted;
	}

	/**
	 * Delete every row.
	 *
	 * @return int|null Rows deleted, or null when a query failed.
	 */
	public static function delete_all(): ?int {
		global $wpdb;

		$table = $wpdb->prefix . Schema::TABLE_CONSENTS;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix and a constant.
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix and a constant.
		if ( null === $count || false === $wpdb->query( "TRUNCATE TABLE {$table}" ) ) {
			return null;
		}

		return (int) $count;
	}
}
