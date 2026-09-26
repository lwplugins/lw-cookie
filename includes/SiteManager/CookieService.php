<?php
/**
 * Cookie Service for LW Site Manager abilities.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\SiteManager;

use LightweightPlugins\Cookie\Admin\SettingsSanitizer;
use LightweightPlugins\Cookie\Admin\SettingsStore;
use LightweightPlugins\Cookie\Database\Schema;
use LightweightPlugins\Cookie\Scanner\Scanner;

/**
 * Executes Cookie abilities for the Site Manager.
 */
final class CookieService {

	/**
	 * Get all LW Cookie options, typed like the admin API returns them, plus
	 * the keys set-options may currently write.
	 *
	 * @param array<string, mixed> $input Input parameters (unused).
	 * @return array<string, mixed>
	 */
	public static function get_options( array $input ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Required by ability callback interface.
		return [
			'success'       => true,
			'options'       => SettingsStore::current(),
			'writable_keys' => AutomationPolicy::writable_keys(),
		];
	}

	/**
	 * Update LW Cookie options through the admin API's sanitized partial
	 * update. Valid keys are saved; invalid, unknown or locked keys are not
	 * stored and come back in `rejected` with the reason.
	 *
	 * @param array<string, mixed> $input Input parameters.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function set_options( array $input ): array|\WP_Error {
		$new_options = $input['options'] ?? [];

		if ( ! is_array( $new_options ) || empty( $new_options ) ) {
			return new \WP_Error(
				'invalid_options',
				__( 'Provide an options object with at least one key.', 'lw-cookie' ),
				[ 'status' => 400 ]
			);
		}

		$result   = OptionsWriter::apply( $new_options );
		$rejected = [];

		foreach ( $result['rejected'] as $key => $code ) {
			$rejected[ $key ] = self::problem_message( $key, $code );
		}

		if ( empty( $result['updated'] ) ) {
			return new \WP_Error(
				'no_valid_options',
				sprintf(
					/* translators: %s: list of "key: reason" pairs */
					__( 'No option was updated. %s', 'lw-cookie' ),
					self::describe( $rejected )
				),
				[
					'status'   => 400,
					'rejected' => $rejected,
				]
			);
		}

		return [
			'success'  => true,
			'message'  => sprintf(
				/* translators: 1: number of options updated, 2: number of options rejected */
				__( '%1$d option(s) updated, %2$d rejected.', 'lw-cookie' ),
				count( $result['updated'] ),
				count( $rejected )
			),
			'updated'  => $result['updated'],
			'rejected' => (object) $rejected,
			'options'  => $result['options'],
		];
	}

	/**
	 * Human-readable reason for a rejected key.
	 *
	 * @param string $key  Option key.
	 * @param string $code Problem code from OptionsWriter.
	 * @return string
	 */
	private static function problem_message( string $key, string $code ): string {
		switch ( $code ) {
			case OptionsWriter::UNKNOWN_KEY:
				return __( 'Unknown setting, or not writable through automation.', 'lw-cookie' );
			case OptionsWriter::LOCKED_KEY:
				return __( 'Managed by the active multilingual plugin; translate it there.', 'lw-cookie' );
			case SettingsSanitizer::PROBLEM_BOOLEAN:
				return __( 'Must be true or false.', 'lw-cookie' );
			case SettingsSanitizer::PROBLEM_NUMBER:
				return __( 'Must be a whole number.', 'lw-cookie' );
			case SettingsSanitizer::PROBLEM_LIST:
				return __( 'Must be a list of cookie rows.', 'lw-cookie' );
			case SettingsSanitizer::PROBLEM_CHOICE:
				/* translators: %s: comma-separated list of allowed values */
				return sprintf( __( 'Must be one of: %s.', 'lw-cookie' ), implode( ', ', SettingsSanitizer::choices( $key ) ) );
			case SettingsSanitizer::PROBLEM_COLOR:
				return __( 'Must be a hex colour such as #2271b1.', 'lw-cookie' );
			default:
				return __( 'Must be a text value.', 'lw-cookie' );
		}
	}

	/**
	 * Join rejected keys into one line for an error message.
	 *
	 * @param array<string, string> $rejected Reason by key.
	 * @return string
	 */
	private static function describe( array $rejected ): string {
		$parts = [];

		foreach ( $rejected as $key => $reason ) {
			$parts[] = $key . ': ' . $reason;
		}

		return implode( ' ', $parts );
	}

	/**
	 * Get consent statistics from the database.
	 *
	 * @param array<string, mixed> $input Input parameters.
	 * @return array<string, mixed>
	 */
	public static function get_consent_stats( array $input ): array {
		global $wpdb;

		$days       = isset( $input['days'] ) ? max( 1, (int) $input['days'] ) : 30;
		$table_name = $wpdb->prefix . Schema::TABLE_CONSENTS;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT action_type, policy_version, COUNT(*) AS count
				FROM {$table_name}
				WHERE created_at >= DATE_SUB( NOW(), INTERVAL %d DAY )
				GROUP BY action_type, policy_version
				ORDER BY count DESC",
				$days
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$stats = [
			'accept_all' => 0,
			'reject_all' => 0,
			'customize'  => 0,
		];
		$total = 0;

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$action = $row['action_type'] ?? '';
				$count  = (int) ( $row['count'] ?? 0 );
				if ( isset( $stats[ $action ] ) ) {
					$stats[ $action ] += $count;
				}
				$total += $count;
			}
		}

		return [
			'success'     => true,
			'stats'       => $stats,
			'total'       => $total,
			'period_days' => $days,
		];
	}

	/**
	 * Trigger an HTTP header pre-scan across site URLs.
	 *
	 * @param array<string, mixed> $input Input parameters (unused).
	 * @return array<string, mixed>
	 */
	public static function scan_cookies( array $input ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Required by ability callback interface.
		$urls = Scanner::get_scan_urls();
		Scanner::prescan_http_cookies( $urls );

		return [
			'success'    => true,
			'cookies'    => Scanner::get_scanned_cookies(),
			'domains'    => Scanner::get_scanned_domains(),
			'urls_count' => count( $urls ),
		];
	}
}
