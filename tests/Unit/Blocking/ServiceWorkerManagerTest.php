<?php
/**
 * Tests for ServiceWorkerManager.
 *
 * Covers issue #5 (the SW file must land in the public webroot, not in
 * ABSPATH, on subdir-core installs such as Bedrock/Radicle) and the follow-up
 * regression it introduced: resolving that webroot must not depend on
 * get_home_path(), which returns "/" outside wp-admin on those very installs.
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
	 * Stub the URL pair describing the install layout.
	 *
	 * Deliberately does NOT stub get_home_path(): the manager must resolve the
	 * webroot without it, so any call would blow up the test.
	 *
	 * @param string $home Home URL, no trailing slash.
	 * @param string $site Site (core) URL, no trailing slash.
	 * @return void
	 */
	private function stub_urls( string $home, string $site ): void {
		Functions\when( 'home_url' )->alias( static fn( $path = '' ) => $home . $path );
		Functions\when( 'site_url' )->alias( static fn( $path = '' ) => $site . $path );
		Functions\when( 'wp_parse_url' )->alias( static fn( $url, $component = -1 ) => parse_url( $url, $component ) );
		Functions\when( 'trailingslashit' )->alias( static fn( $value ) => rtrim( $value, "/\\" ) . '/' );
	}

	/**
	 * install() must copy the SW to the public webroot, which on subdir-core
	 * installs is ABSPATH minus the core subdirectory.
	 */
	public function test_install_copies_to_the_webroot_not_abspath(): void {
		// ABSPATH is /var/www/wp/ (see tests/bootstrap.php) — webroot is /var/www/.
		$this->stub_urls( 'https://example.test', 'https://example.test/wp' );
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
		$this->assertSame( '/var/www/lw-cookie-sw.js', $dest );
		$this->assertNotSame( ABSPATH . 'lw-cookie-sw.js', $dest );
	}

	/**
	 * On a plain install (home === siteurl) the webroot is ABSPATH itself.
	 */
	public function test_install_uses_abspath_on_a_plain_install(): void {
		$this->stub_urls( 'https://example.test', 'https://example.test' );
		Functions\when( 'file_exists' )->justReturn( true );

		$dest = null;
		Functions\when( 'copy' )->alias(
			static function ( $source, $target ) use ( &$dest ) {
				$dest = $target;
				return true;
			}
		);

		ServiceWorkerManager::install();

		$this->assertSame( ABSPATH . 'lw-cookie-sw.js', $dest );
	}

	/**
	 * WordPress-in-its-own-directory under a subdirectory home: only the core
	 * segment is stripped, the subdirectory stays.
	 */
	public function test_install_strips_only_the_core_subdirectory(): void {
		$this->stub_urls( 'https://example.test/blog', 'https://example.test/blog/wp' );
		Functions\when( 'file_exists' )->justReturn( true );

		$dest = null;
		Functions\when( 'copy' )->alias(
			static function ( $source, $target ) use ( &$dest ) {
				$dest = $target;
				return true;
			}
		);

		ServiceWorkerManager::install();

		$this->assertSame( '/var/www/lw-cookie-sw.js', $dest );
	}

	/**
	 * When ABSPATH does not end with the core segment (symlinked or otherwise
	 * unusual layout) we fall back to ABSPATH rather than guessing.
	 */
	public function test_install_falls_back_to_abspath_on_an_unexpected_layout(): void {
		$this->stub_urls( 'https://example.test', 'https://example.test/cms' );
		Functions\when( 'file_exists' )->justReturn( true );

		$dest = null;
		Functions\when( 'copy' )->alias(
			static function ( $source, $target ) use ( &$dest ) {
				$dest = $target;
				return true;
			}
		);

		ServiceWorkerManager::install();

		$this->assertSame( ABSPATH . 'lw-cookie-sw.js', $dest );
	}

	/**
	 * The advertised SW URL is home-root relative.
	 */
	public function test_sw_url_is_served_from_the_site_root(): void {
		Functions\when( 'home_url' )->alias( static fn( $path = '' ) => 'https://example.test' . $path );

		$this->assertSame( 'https://example.test/lw-cookie-sw.js', ServiceWorkerManager::get_sw_url() );
	}

	/**
	 * register_fallback() runs on every front-end request, so it must not touch
	 * the filesystem: a stat of a mis-resolved root path floods the error log
	 * with open_basedir warnings.
	 */
	public function test_fallback_registration_does_not_touch_the_filesystem(): void {
		$stat = false;
		Functions\when( 'file_exists' )->alias(
			static function ( $path ) use ( &$stat ) {
				$stat = true;
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

		$this->assertFalse( $stat, 'register_fallback() must not stat the filesystem.' );
		$this->assertTrue( $registered );
	}
}
