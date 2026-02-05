<?php
/**
 * Database Schema class.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Database;

/**
 * Handles database table creation.
 */
final class Schema {

	/**
	 * Consents table name (without prefix).
	 */
	public const TABLE_CONSENTS = 'lw_cookie_consents';

	/**
	 * Create all database tables.
	 *
	 * @return void
	 */
	public static function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . self::TABLE_CONSENTS;

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			consent_id VARCHAR(36) NOT NULL,
			ip_hash VARCHAR(64) NOT NULL,
			categories JSON NOT NULL,
			policy_version VARCHAR(20) NOT NULL,
			action_type ENUM('accept_all','reject_all','customize') NOT NULL,
			user_agent VARCHAR(255) DEFAULT '',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			INDEX idx_consent_id (consent_id),
			INDEX idx_created_at (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Drop all database tables.
	 *
	 * @return void
	 */
	public static function drop_tables(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . self::TABLE_CONSENTS;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
	}
}
