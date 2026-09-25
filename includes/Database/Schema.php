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

		$sql = self::consents_table_sql( $wpdb->prefix . self::TABLE_CONSENTS, $wpdb->get_charset_collate() );

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * CREATE TABLE statement of the consents table, in the form dbDelta()
	 * parses: NOT NULL AUTO_INCREMENT on the column, `PRIMARY KEY  (id)` with
	 * two spaces, and `KEY` rather than `INDEX`. Anything else makes dbDelta()
	 * try to redefine the primary key on every activation.
	 *
	 * @param string $table_name      Prefixed table name.
	 * @param string $charset_collate Charset and collation clause.
	 * @return string
	 */
	public static function consents_table_sql( string $table_name, string $charset_collate ): string {
		return "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			consent_id VARCHAR(36) NOT NULL,
			ip_hash VARCHAR(64) NOT NULL,
			categories JSON NOT NULL,
			policy_version VARCHAR(20) NOT NULL,
			action_type ENUM('accept_all','reject_all','customize') NOT NULL,
			user_agent VARCHAR(255) DEFAULT '',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_consent_id (consent_id),
			KEY idx_created_at (created_at)
		) {$charset_collate};";
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
