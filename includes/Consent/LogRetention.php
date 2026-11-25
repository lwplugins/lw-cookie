<?php
/**
 * Consent log retention.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Consent;

use LightweightPlugins\Cookie\Options;

/**
 * Deletes consent log rows older than the "log_retention_days" setting once
 * a day. 0 (the default) keeps the log forever and schedules nothing, so an
 * update never deletes anything until an admin sets a value.
 */
final class LogRetention {

	/**
	 * Option key holding the retention in days.
	 */
	public const OPTION = 'log_retention_days';

	/**
	 * WP-Cron hook of the daily cleanup.
	 */
	public const HOOK = 'lw_cookie_log_retention';

	/**
	 * Largest retention the setting accepts (about ten years).
	 */
	public const MAX_DAYS = 3650;

	/**
	 * Batches per daily run (BATCH_SIZE rows each); the rest is deleted the next day.
	 */
	public const MAX_BATCHES = 100;

	public const CHANGE_NONE       = '';
	public const CHANGE_SCHEDULE   = 'schedule';
	public const CHANGE_UNSCHEDULE = 'unschedule';

	/**
	 * Hook the cleanup and keep the schedule in step with the setting.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( self::HOOK, [ self::class, 'run' ] );
		add_action( 'init', [ self::class, 'sync_schedule' ] );
	}

	/**
	 * Retention in days from a stored or submitted value.
	 *
	 * @param mixed $value Raw value.
	 * @return int 0 (keep forever) to MAX_DAYS.
	 */
	public static function sanitize_days( mixed $value ): int {
		if ( ! is_numeric( $value ) ) {
			return 0;
		}

		return max( 0, min( self::MAX_DAYS, (int) $value ) );
	}

	/**
	 * Configured retention in days.
	 *
	 * @return int
	 */
	public static function days(): int {
		return self::sanitize_days( Options::get( self::OPTION, 0 ) );
	}

	/**
	 * What to change on the schedule.
	 *
	 * @param int  $days      Configured retention.
	 * @param bool $scheduled Whether the event is scheduled now.
	 * @return string One of the CHANGE_* constants.
	 */
	public static function schedule_change( int $days, bool $scheduled ): string {
		if ( $days > 0 && ! $scheduled ) {
			return self::CHANGE_SCHEDULE;
		}

		if ( $days <= 0 && $scheduled ) {
			return self::CHANGE_UNSCHEDULE;
		}

		return self::CHANGE_NONE;
	}

	/**
	 * Schedule the daily event when retention is on, remove it when off.
	 *
	 * @return void
	 */
	public static function sync_schedule(): void {
		$change = self::schedule_change( self::days(), false !== wp_next_scheduled( self::HOOK ) );

		if ( self::CHANGE_SCHEDULE === $change ) {
			wp_schedule_event( time(), 'daily', self::HOOK );
		} elseif ( self::CHANGE_UNSCHEDULE === $change ) {
			self::unschedule();
		}
	}

	/**
	 * Remove the daily event (deactivation, uninstall, retention off).
	 *
	 * @return void
	 */
	public static function unschedule(): void {
		wp_clear_scheduled_hook( self::HOOK );
	}

	/**
	 * Daily cleanup.
	 *
	 * @return void
	 */
	public static function run(): void {
		$days = self::days();

		if ( $days <= 0 ) {
			return;
		}

		if ( null === LogCleaner::delete_older_than( $days, self::MAX_BATCHES ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- debug-only failure report.
			error_log( 'LW Cookie: consent log retention cleanup failed (database error).' );
		}
	}
}
