<?php
/**
 * Tests for ServiceWorkerManager.
 *
 * Reproduces issue #5: on Bedrock/Radicle (subdir-core) installs the SW file
 * must be written to the public webroot (home path), not to ABSPATH (the core
 * subdirectory), otherwise the advertised URL 404s.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Blocking;

use Brain\Monkey\Functions;
use LightweightPlugins\Cookie\Blocking\ServiceWorkerManager;
use LightweightPlugins\Cookie\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Cookie\Blocking\ServiceWorkerManager
 */
final class ServiceWorkerManagerTest extends MonkeyTestCase {

	/**
	 * install() must copy the SW to the public webroot (get_home_path()),
	 * which on subdir-core installs differs from ABSPATH.
	 */
	public function test_install_copies_to_the_webroot_not_abspath(): void {
		Functions\when( 'get_home_path' )->justReturn( '/var/www/webroot/' );
		Functions\when( 'file_exists' )->justReturn( true );

		$dest = null;
		Functions\when( 'copy' )->alias(
			static function ( $source, $target ) use ( &$dest ) {
				$dest = $target;
				return true;
			}
		);

		$result = ServiceWorkerManager::install();

		$this->assertTrue( $result );
		$this->assertSame( '/var/www/webroot/lw-cookie-sw.js', $dest );
		$this->assertNotSame( ABSPATH . 'lw-cookie-sw.js', $dest );
	}

	/**
	 * The advertised SW URL is home-root relative.
	 */
	public function test_sw_url_is_served_from_the_site_root(): void {
		Functions\when( 'home_url' )->alias( static fn( $path = '' ) => 'https://example.test' . $path );

		$this->assertSame( 'https://example.test/lw-cookie-sw.js', ServiceWorkerManager::get_sw_url() );
	}

	/**
	 * The dynamic fallback is skipped only when the static file already sits in
	 * the webroot (get_home_path()), not ABSPATH.
	 */
	public function test_fallback_registered_when_no_static_file_in_webroot(): void {
		Functions\when( 'get_home_path' )->justReturn( '/var/www/webroot/' );

		$checked = null;
		Functions\when( 'file_exists' )->alias(
			static function ( $path ) use ( &$checked ) {
				$checked = $path;
				return false;
			}
		);

		$registered = false;
		Functions\when( 'add_action' )->alias(
			static function ( $hook ) use ( &$registered ) {
				if ( 'template_redirect' === $hook ) {
					$registered = true;
				}
			}
		);

		ServiceWorkerManager::register_fallback();

		$this->assertSame( '/var/www/webroot/lw-cookie-sw.js', $checked );
		$this->assertTrue( $registered );
	}
}
