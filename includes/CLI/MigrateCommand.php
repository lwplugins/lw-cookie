<?php
/**
 * WP-CLI migration command.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\CLI;

use LightweightPlugins\Cookie\Migration\Importer;
use WP_CLI;

/**
 * Import settings from another cookie consent plugin.
 */
final class MigrateCommand {

	/**
	 * Register the command.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		WP_CLI::add_command( 'lw-cookie migrate', self::class );
	}

	/**
	 * Import the Complianz banner texts, colours, position, consent settings
	 * and declared cookies. Complianz may be inactive; only its data is read.
	 * Cookies already declared in LW Cookie are kept.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Show what would change without saving.
	 *
	 * ## EXAMPLES
	 *
	 *     wp lw-cookie migrate complianz --dry-run
	 *     wp lw-cookie migrate complianz
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 * @return void
	 */
	public function complianz( array $args, array $assoc_args ): void {
		$dry_run = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$report  = Importer::run( $dry_run );

		if ( null === $report ) {
			WP_CLI::error( 'No Complianz settings were found on this site.' );
		}

		foreach ( $report['settings'] as $key => $value ) {
			WP_CLI::log( sprintf( '%s: %s', $key, is_bool( $value ) ? ( $value ? 'true' : 'false' ) : (string) $value ) );
		}

		foreach ( $report['locked'] as $key ) {
			WP_CLI::warning( sprintf( '%s: skipped, managed by the active multilingual plugin.', $key ) );
		}

		WP_CLI::log( 'Cookies to declare: ' . self::names( $report['cookies_added'] ) );
		WP_CLI::log( 'Already declared (kept): ' . self::names( $report['cookies_existing'] ) );
		WP_CLI::log( sprintf( 'Skipped, no category in Complianz: %d', $report['cookies_skipped'] ) );

		$total = count( $report['settings'] ) + count( $report['cookies_added'] );

		if ( $dry_run ) {
			WP_CLI::success( sprintf( 'Dry run: %d change(s) would be imported.', $total ) );
			return;
		}

		WP_CLI::success( sprintf( 'Imported %d change(s). Deactivate Complianz so only one banner is shown.', $total ) );
	}

	/**
	 * Comma-separated names, or a dash for none.
	 *
	 * @param array<int, string> $names Cookie names.
	 * @return string
	 */
	private static function names( array $names ): string {
		return [] === $names ? '-' : implode( ', ', $names );
	}
}
