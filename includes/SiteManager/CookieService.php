<?php
/**
 * Cookie Service for LW Site Manager abilities.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\SiteManager;

use LightweightPlugins\Cookie\Database\Schema;
use LightweightPlugins\Cookie\Options;
use LightweightPlugins\Cookie\Scanner\Scanner;

/**
 * Executes Cookie abilities for the Site Manager.
 */
final class CookieService {

	/**
	 * Allowed option keys that can be written via set-options.
	 */
	private const WRITABLE_KEYS = [
		'enabled',
		'privacy_policy_page',
		'policy_version',
		'banner_position',
		'banner_layout',
		'primary_color',
		'text_color',
		'background_color',
		'border_radius',
		'cat_functional_name',
		'cat_functional_desc',
		'cat_analytics_name',
		'cat_analytics_desc',
		'cat_marketing_name',
		'cat_marketing_desc',
		'banner_title',
		'banner_message',
		'btn_accept_all',
		'btn_reject_all',
		'btn_customize',
		'btn_save',
		'consent_duration',
		'script_blocking',
		'content_blocking',
		'gcm_enabled',
		'show_floating_button',
		'floating_button_pos',
	];

	/**
	 * Get all LW Cookie options.
	 *
	 * @param array<string, mixed> $input Input parameters (unused).
	 * @return array<string, mixed>
	 */
	public static function get_options( array $input ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Required by ability callback interface.
		return [
			'success' => true,
			'options' => Options::get_all(),
		];
	}

	/**
	 * Update LW Cookie options.
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

		$current = Options::get_all();
		$updated = [];

		foreach ( $new_options as $key => $value ) {
			if ( ! in_array( $key, self::WRITABLE_KEYS, true ) ) {
				continue;
			}
			$current[ $key ] = $value;
			$updated[]       = $key;
		}

		if ( empty( $updated ) ) {
			return new \WP_Error(
				'no_valid_keys',
				__( 'No valid option keys provided.', 'lw-cookie' ),
				[ 'status' => 400 ]
			);
		}

		Options::save( $current );

		return [
			'success' => true,
			'message' => sprintf(
				/* translators: %d: number of options updated */
				__( '%d option(s) updated.', 'lw-cookie' ),
				count( $updated )
			),
			'updated' => $updated,
		];
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
