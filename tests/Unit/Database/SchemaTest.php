<?php
/**
 * Schema unit tests.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Database;

use LightweightPlugins\Cookie\Database\Schema;
use PHPUnit\Framework\TestCase;

/**
 * The consents table SQL must be in the form dbDelta() parses: otherwise
 * every activation tries to redefine the primary key and fails with
 * "Multiple primary key defined".
 */
final class SchemaTest extends TestCase {

	private function sql(): string {
		return Schema::consents_table_sql( 'wp_lw_cookie_consents', 'DEFAULT CHARACTER SET utf8mb4' );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function dbdelta_line_provider(): array {
		return [
			'id without inline key' => [ 'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,' ],
			'primary key, two spaces' => [ 'PRIMARY KEY  (id),' ],
			'consent id key'          => [ 'KEY idx_consent_id (consent_id),' ],
			'created at key'          => [ 'KEY idx_created_at (created_at)' ],
		];
	}

	/**
	 * @dataProvider dbdelta_line_provider
	 *
	 * @param string $line Line dbDelta() needs.
	 */
	public function test_consents_sql_has_the_dbdelta_line( string $line ): void {
		$this->assertContains( $line, array_map( 'trim', explode( "\n", $this->sql() ) ) );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function forbidden_provider(): array {
		return [
			'inline primary key' => [ '/AUTO_INCREMENT PRIMARY KEY/' ],
			'INDEX keyword'      => [ '/^\s*INDEX\b/m' ],
		];
	}

	/**
	 * @dataProvider forbidden_provider
	 *
	 * @param string $pattern Pattern dbDelta() cannot handle.
	 */
	public function test_consents_sql_avoids_what_dbdelta_misreads( string $pattern ): void {
		$this->assertDoesNotMatchRegularExpression( $pattern, $this->sql() );
	}

	public function test_consents_sql_keeps_the_columns(): void {
		$sql = $this->sql();

		foreach ( [
			'CREATE TABLE wp_lw_cookie_consents (',
			'consent_id VARCHAR(36) NOT NULL,',
			'ip_hash VARCHAR(64) NOT NULL,',
			'categories JSON NOT NULL,',
			'policy_version VARCHAR(20) NOT NULL,',
			"action_type ENUM('accept_all','reject_all','customize') NOT NULL,",
			"user_agent VARCHAR(255) DEFAULT '',",
			'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,',
			') DEFAULT CHARACTER SET utf8mb4;',
		] as $fragment ) {
			$this->assertStringContainsString( $fragment, $sql );
		}
	}
}
