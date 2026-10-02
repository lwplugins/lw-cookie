<?php
/**
 * Complianz data source.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Migration\Complianz;

/**
 * Reads the settings Complianz left in the database: the cmplz_options
 * option, the default banner row and the cookie/service tables. Works while
 * Complianz is inactive — only its data is needed, never its code.
 */
final class Source {

	/**
	 * Whether Complianz data exists on this site.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return false !== get_option( 'cmplz_options', false ) && self::table_exists( 'cmplz_cookiebanners' );
	}

	/**
	 * Everything the mappers need.
	 *
	 * @return array{options: array<string, mixed>, privacy_page: int, banner: array<string, mixed>, cookies: array<int, array<string, mixed>>, services: array<int, array<string, mixed>>, language: string}
	 */
	public static function read(): array {
		$options = get_option( 'cmplz_options', [] );

		return [
			'options'      => is_array( $options ) ? $options : [],
			'privacy_page' => absint( get_option( 'cmplz_privacy-statement_custom_page', 0 ) ),
			'banner'       => self::banner(),
			'cookies'      => self::rows( 'cmplz_cookies', 'deleted = 0 AND ignored = 0 AND showOnPolicy = 1' ),
			'services'     => array_column( self::rows( 'cmplz_services', '1 = 1' ), null, 'ID' ),
			'language'     => substr( (string) get_locale(), 0, 2 ),
		];
	}

	/**
	 * The default banner (or the first one), with its serialized columns
	 * decoded.
	 *
	 * @return array<string, mixed>
	 */
	private static function banner(): array {
		$rows = self::rows( 'cmplz_cookiebanners', '1 = 1 ORDER BY `default` DESC, ID ASC LIMIT 1' );
		$row  = $rows[0] ?? [];

		foreach ( $row as $column => $value ) {
			if ( is_string( $value ) && is_serialized( $value ) ) {
				// Arrays only: allowed_classes=false never instantiates an object from the column.
				$decoded        = unserialize( $value, [ 'allowed_classes' => false ] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
				$row[ $column ] = is_array( $decoded ) ? $decoded : [];
			}
		}

		return $row;
	}

	/**
	 * Rows of a Complianz table, or none when the table is missing.
	 *
	 * @param string $table Table name without prefix.
	 * @param string $where Constant SQL condition (never user input).
	 * @return array<int, array<string, mixed>>
	 */
	private static function rows( string $table, string $where ): array {
		global $wpdb;

		if ( ! self::table_exists( $table ) ) {
			return [];
		}

		$name = $wpdb->prefix . $table;
		// Table name from $wpdb->prefix + a constant, condition is a constant: nothing to prepare.
		$rows = $wpdb->get_results( "SELECT * FROM `{$name}` WHERE {$where}", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * Whether a prefixed table exists.
	 *
	 * @param string $table Table name without prefix.
	 * @return bool
	 */
	private static function table_exists( string $table ): bool {
		global $wpdb;

		$name = $wpdb->prefix . $table;
		$like = $wpdb->esc_like( $name );

		return $name === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}
}
