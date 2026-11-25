<?php
/**
 * WP-CLI command: clear consent logs.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\CLI;

use LightweightPlugins\Cookie\Consent\LogCleaner;
use WP_CLI;

/**
 * `wp lw-cookie clear-logs`, with `clear_logs` kept as an alias: 1.9.15 and
 * older registered the command only under that name.
 */
final class ClearLogsCommand {

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		WP_CLI::add_command( 'lw-cookie clear-logs', self::class );
	}

	/**
	 * Clear consent logs from the database.
	 *
	 * ## OPTIONS
	 *
	 * [--older-than[=<days>]]
	 * : Only delete logs older than this many days (a whole number, at least 1). Without it, ALL logs are deleted.
	 *
	 * [--yes]
	 * : Skip the confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     wp lw-cookie clear-logs --older-than=365 --yes
	 *     wp lw-cookie clear-logs --yes
	 *
	 * @alias clear_logs
	 *
	 * @param array<int, string>         $args       Positional arguments.
	 * @param array<string, string|bool> $assoc_args Associative arguments.
	 * @return void
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		unset( $args ); // Unused.

		// The synopsis makes the value optional on purpose: with a required
		// `=<days>`, WP-CLI drops a bare --older-than with only a warning and
		// the command would fall through to deleting ALL logs.
		if ( ! array_key_exists( 'older-than', $assoc_args ) ) {
			WP_CLI::confirm( 'Delete ALL consent logs? This cannot be undone.', $assoc_args );
			self::report( LogCleaner::delete_all() );
			return;
		}

		$days = LogCleaner::parse_days( $assoc_args['older-than'] );

		if ( null === $days ) {
			WP_CLI::error( sprintf( '--older-than must be a whole number of days from 1 to %d. Nothing was deleted.', LogCleaner::MAX_DAYS ) );
		}

		WP_CLI::confirm( "Delete consent logs older than {$days} days?", $assoc_args );
		self::report( LogCleaner::delete_older_than( (int) $days, PHP_INT_MAX ) );
	}

	/**
	 * Print the outcome.
	 *
	 * @param int|null $deleted Rows deleted, or null on failure.
	 * @return void
	 */
	private static function report( ?int $deleted ): void {
		if ( null === $deleted ) {
			WP_CLI::error( 'Failed to delete logs.' );
		}

		WP_CLI::success( "Deleted {$deleted} consent log(s)." );
	}
}
