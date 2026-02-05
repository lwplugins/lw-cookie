<?php
/**
 * WP-CLI Commands.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\CLI;

use LightweightPlugins\Cookie\Options;
use LightweightPlugins\Cookie\Database\Schema;
use WP_CLI;

/**
 * Manage LW Cookie settings and consent data.
 *
 * ## EXAMPLES
 *
 *     # Get all settings
 *     wp lw-cookie settings list
 *
 *     # Get a specific setting
 *     wp lw-cookie settings get primary_color
 *
 *     # Set a setting
 *     wp lw-cookie settings set primary_color "#ff0000"
 *
 *     # View consent statistics
 *     wp lw-cookie stats
 *
 *     # Clear all consent logs
 *     wp lw-cookie clear-logs --yes
 */
class Commands {

	/**
	 * Register CLI commands.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		WP_CLI::add_command( 'lw-cookie', self::class );
	}

	/**
	 * Manage plugin settings.
	 *
	 * ## OPTIONS
	 *
	 * <action>
	 * : The action to perform (list, get, set, reset).
	 *
	 * [<key>]
	 * : The setting key (required for get/set).
	 *
	 * [<value>]
	 * : The setting value (required for set).
	 *
	 * [--format=<format>]
	 * : Output format (table, json, yaml). Default: table.
	 *
	 * ## EXAMPLES
	 *
	 *     wp lw-cookie settings list
	 *     wp lw-cookie settings list --format=json
	 *     wp lw-cookie settings get enabled
	 *     wp lw-cookie settings set enabled 1
	 *     wp lw-cookie settings set primary_color "#2271b1"
	 *     wp lw-cookie settings reset
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function settings( array $args, array $assoc_args ): void {
		$action = $args[0] ?? 'list';
		$format = $assoc_args['format'] ?? 'table';

		switch ( $action ) {
			case 'list':
				$this->settings_list( $format );
				break;

			case 'get':
				if ( empty( $args[1] ) ) {
					WP_CLI::error( 'Please specify a setting key.' );
				}
				$this->settings_get( $args[1] );
				break;

			case 'set':
				if ( empty( $args[1] ) || ! isset( $args[2] ) ) {
					WP_CLI::error( 'Please specify both key and value.' );
				}
				$this->settings_set( $args[1], $args[2] );
				break;

			case 'reset':
				$this->settings_reset();
				break;

			default:
				WP_CLI::error( "Unknown action: {$action}. Use: list, get, set, reset." );
		}
	}

	/**
	 * List all settings.
	 *
	 * @param string $format Output format.
	 * @return void
	 */
	private function settings_list( string $format ): void {
		$options  = Options::get_all();
		$defaults = Options::get_defaults();
		$items    = [];

		foreach ( $options as $key => $value ) {
			$items[] = [
				'key'        => $key,
				'value'      => is_bool( $value ) ? ( $value ? 'true' : 'false' ) : (string) $value,
				'default'    => is_bool( $defaults[ $key ] ?? '' ) ? ( ( $defaults[ $key ] ?? false ) ? 'true' : 'false' ) : (string) ( $defaults[ $key ] ?? '' ),
				'is_default' => ( ( $defaults[ $key ] ?? null ) === $value ) ? 'yes' : 'no',
			];
		}

		WP_CLI\Utils\format_items( $format, $items, [ 'key', 'value', 'default', 'is_default' ] );
	}

	/**
	 * Get a single setting.
	 *
	 * @param string $key Setting key.
	 * @return void
	 */
	private function settings_get( string $key ): void {
		$defaults = Options::get_defaults();

		if ( ! array_key_exists( $key, $defaults ) ) {
			WP_CLI::error( "Unknown setting: {$key}" );
		}

		$value = Options::get( $key );

		if ( is_bool( $value ) ) {
			$value = $value ? 'true' : 'false';
		}

		WP_CLI::log( (string) $value );
	}

	/**
	 * Set a single setting.
	 *
	 * @param string $key   Setting key.
	 * @param string $value Setting value.
	 * @return void
	 */
	private function settings_set( string $key, string $value ): void {
		$defaults = Options::get_defaults();

		if ( ! array_key_exists( $key, $defaults ) ) {
			WP_CLI::error( "Unknown setting: {$key}" );
		}

		// Convert value types.
		$default_value = $defaults[ $key ];

		if ( is_bool( $default_value ) ) {
			$value = in_array( strtolower( $value ), [ '1', 'true', 'yes', 'on' ], true );
		} elseif ( is_int( $default_value ) ) {
			$value = (int) $value;
		}

		Options::set( $key, $value );
		WP_CLI::success( "Setting '{$key}' updated." );
	}

	/**
	 * Reset all settings to defaults.
	 *
	 * @return void
	 */
	private function settings_reset(): void {
		Options::reset();
		WP_CLI::success( 'All settings reset to defaults.' );
	}

	/**
	 * Display consent statistics.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format (table, json, yaml). Default: table.
	 *
	 * [--days=<days>]
	 * : Number of days to include in stats. Default: 30.
	 *
	 * ## EXAMPLES
	 *
	 *     wp lw-cookie stats
	 *     wp lw-cookie stats --days=7
	 *     wp lw-cookie stats --format=json
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function stats( array $args, array $assoc_args ): void {
		global $wpdb;

		$format = $assoc_args['format'] ?? 'table';
		$days   = (int) ( $assoc_args['days'] ?? 30 );
		$table  = $wpdb->prefix . Schema::TABLE_CONSENTS;

		// Check if table exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
		);

		if ( ! $table_exists ) {
			WP_CLI::error( 'Consent table does not exist. Activate the plugin first.' );
		}

		// Get statistics.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total = (int) $wpdb->get_var(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT COUNT(*) FROM {$table}"
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$recent = (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(*) FROM {$table} WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$by_action = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT action_type, COUNT(*) as count FROM {$table} WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY) GROUP BY action_type",
				$days
			),
			ARRAY_A
		);

		$action_stats = [
			'accept_all' => 0,
			'reject_all' => 0,
			'customize'  => 0,
		];

		foreach ( $by_action as $row ) {
			$action_stats[ $row['action_type'] ] = (int) $row['count'];
		}

		$items = [
			[
				'metric' => 'Total consents (all time)',
				'value'  => $total,
			],
			[
				'metric' => "Consents (last {$days} days)",
				'value'  => $recent,
			],
			[
				'metric' => "Accept All (last {$days} days)",
				'value'  => $action_stats['accept_all'],
			],
			[
				'metric' => "Reject All (last {$days} days)",
				'value'  => $action_stats['reject_all'],
			],
			[
				'metric' => "Customize (last {$days} days)",
				'value'  => $action_stats['customize'],
			],
		];

		if ( $recent > 0 ) {
			$accept_rate = round( ( $action_stats['accept_all'] / $recent ) * 100, 1 );
			$reject_rate = round( ( $action_stats['reject_all'] / $recent ) * 100, 1 );

			$items[] = [
				'metric' => 'Accept All rate',
				'value'  => "{$accept_rate}%",
			];
			$items[] = [
				'metric' => 'Reject All rate',
				'value'  => "{$reject_rate}%",
			];
		}

		WP_CLI\Utils\format_items( $format, $items, [ 'metric', 'value' ] );
	}

	/**
	 * Clear consent logs from database.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Skip confirmation prompt.
	 *
	 * [--older-than=<days>]
	 * : Only delete logs older than X days.
	 *
	 * ## EXAMPLES
	 *
	 *     wp lw-cookie clear-logs --yes
	 *     wp lw-cookie clear-logs --older-than=365 --yes
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function clear_logs( array $args, array $assoc_args ): void {
		global $wpdb;

		$table      = $wpdb->prefix . Schema::TABLE_CONSENTS;
		$older_than = isset( $assoc_args['older-than'] ) ? (int) $assoc_args['older-than'] : null;

		// Confirm action.
		if ( $older_than ) {
			$message = "Delete consent logs older than {$older_than} days?";
		} else {
			$message = 'Delete ALL consent logs? This cannot be undone.';
		}

		WP_CLI::confirm( $message, $assoc_args );

		// Delete logs.
		if ( $older_than ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$deleted = $wpdb->query(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"DELETE FROM {$table} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
					$older_than
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$deleted = $wpdb->query( "TRUNCATE TABLE {$table}" );
		}

		if ( false === $deleted ) {
			WP_CLI::error( 'Failed to delete logs.' );
		}

		WP_CLI::success( "Deleted {$deleted} consent log(s)." );
	}

	/**
	 * Export consent logs.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format (table, csv, json). Default: table.
	 *
	 * [--days=<days>]
	 * : Export logs from last X days. Default: all.
	 *
	 * [--limit=<limit>]
	 * : Maximum number of records. Default: 100.
	 *
	 * ## EXAMPLES
	 *
	 *     wp lw-cookie export
	 *     wp lw-cookie export --format=csv --days=30 > consents.csv
	 *     wp lw-cookie export --format=json --limit=1000
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function export( array $args, array $assoc_args ): void {
		global $wpdb;

		$format = $assoc_args['format'] ?? 'table';
		$days   = isset( $assoc_args['days'] ) ? (int) $assoc_args['days'] : null;
		$limit  = (int) ( $assoc_args['limit'] ?? 100 );
		$table  = $wpdb->prefix . Schema::TABLE_CONSENTS;

		$where = '';
		if ( $days ) {
			$where = $wpdb->prepare(
				'WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)',
				$days
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT consent_id, action_type, policy_version, categories, created_at FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		if ( empty( $results ) ) {
			WP_CLI::warning( 'No consent logs found.' );
			return;
		}

		WP_CLI\Utils\format_items(
			$format,
			$results,
			[ 'consent_id', 'action_type', 'policy_version', 'categories', 'created_at' ]
		);
	}

	/**
	 * Find and export consent data for GDPR requests.
	 *
	 * ## OPTIONS
	 *
	 * [--consent-id=<id>]
	 * : Find by consent ID (UUID).
	 *
	 * [--ip=<ip>]
	 * : Find by IP address (will be hashed for lookup).
	 *
	 * [--format=<format>]
	 * : Output format (table, json, csv). Default: table.
	 *
	 * [--delete]
	 * : Delete the found records (GDPR erasure request).
	 *
	 * ## EXAMPLES
	 *
	 *     wp lw-cookie consent --consent-id=abc123-def456
	 *     wp lw-cookie consent --ip=192.168.1.1
	 *     wp lw-cookie consent --ip=192.168.1.1 --format=json
	 *     wp lw-cookie consent --consent-id=abc123 --delete
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function consent( array $args, array $assoc_args ): void {
		global $wpdb;

		unset( $args ); // Unused.

		$consent_id = $assoc_args['consent-id'] ?? null;
		$ip         = $assoc_args['ip'] ?? null;
		$format     = $assoc_args['format'] ?? 'table';
		$delete     = isset( $assoc_args['delete'] );
		$table      = $wpdb->prefix . Schema::TABLE_CONSENTS;

		if ( ! $consent_id && ! $ip ) {
			WP_CLI::error( 'Please provide --consent-id or --ip to search.' );
		}

		// Build query.
		$where_clauses = [];
		$where_values  = [];

		if ( $consent_id ) {
			$where_clauses[] = 'consent_id = %s';
			$where_values[]  = $consent_id;
		}

		if ( $ip ) {
			$ip_hash         = hash( 'sha256', $ip );
			$where_clauses[] = 'ip_hash = %s';
			$where_values[]  = $ip_hash;
		}

		$where = implode( ' OR ', $where_clauses );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				"SELECT id, consent_id, ip_hash, action_type, policy_version, categories, user_agent, created_at FROM {$table} WHERE {$where} ORDER BY created_at DESC",
				...$where_values
			),
			ARRAY_A
		);

		if ( empty( $results ) ) {
			WP_CLI::warning( 'No consent records found.' );
			return;
		}

		WP_CLI::success( sprintf( 'Found %d consent record(s).', count( $results ) ) );

		// Display results.
		WP_CLI\Utils\format_items(
			$format,
			$results,
			[ 'id', 'consent_id', 'action_type', 'policy_version', 'categories', 'created_at' ]
		);

		// Handle deletion (GDPR erasure).
		if ( $delete ) {
			WP_CLI::confirm( 'Delete these consent records? This cannot be undone.', $assoc_args );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$deleted = $wpdb->query(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
					"DELETE FROM {$table} WHERE {$where}",
					...$where_values
				)
			);

			if ( false === $deleted ) {
				WP_CLI::error( 'Failed to delete records.' );
			}

			WP_CLI::success( sprintf( 'Deleted %d consent record(s).', $deleted ) );
		}
	}

	/**
	 * Show available setting keys and their descriptions.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format (table, json, csv). Default: table.
	 *
	 * ## EXAMPLES
	 *
	 *     wp lw-cookie keys
	 *     wp lw-cookie keys --format=json
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function keys( array $args, array $assoc_args ): void {
		unset( $args ); // Unused.
		$format       = $assoc_args['format'] ?? 'table';
		$descriptions = [
			'enabled'              => 'Enable/disable cookie banner',
			'privacy_policy_page'  => 'Privacy policy page ID',
			'policy_version'       => 'Current policy version',
			'banner_position'      => 'Banner position (bottom, top, modal)',
			'banner_layout'        => 'Banner layout (bar, box)',
			'primary_color'        => 'Primary button color',
			'text_color'           => 'Text color',
			'background_color'     => 'Background color',
			'border_radius'        => 'Border radius in pixels',
			'cat_functional_name'  => 'Functional category name',
			'cat_functional_desc'  => 'Functional category description',
			'cat_analytics_name'   => 'Analytics category name',
			'cat_analytics_desc'   => 'Analytics category description',
			'cat_marketing_name'   => 'Marketing category name',
			'cat_marketing_desc'   => 'Marketing category description',
			'banner_title'         => 'Banner title text',
			'banner_message'       => 'Banner message text',
			'btn_accept_all'       => 'Accept All button text',
			'btn_reject_all'       => 'Reject All button text',
			'btn_customize'        => 'Customize button text',
			'btn_save'             => 'Save Preferences button text',
			'consent_duration'     => 'Consent cookie duration (days)',
			'script_blocking'      => 'Enable script blocking',
			'gcm_enabled'          => 'Enable Google Consent Mode',
			'show_floating_button' => 'Show floating preferences button',
			'floating_button_pos'  => 'Floating button position',
		];

		$defaults = Options::get_defaults();
		$items    = [];

		foreach ( $descriptions as $key => $desc ) {
			$default = $defaults[ $key ] ?? '';
			if ( is_bool( $default ) ) {
				$default = $default ? 'true' : 'false';
			}

			$items[] = [
				'key'         => $key,
				'description' => $desc,
				'default'     => (string) $default,
			];
		}

		WP_CLI\Utils\format_items( $format, $items, [ 'key', 'description', 'default' ] );
	}
}
