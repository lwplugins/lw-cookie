<?php
/**
 * CommonCookies unit tests.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use LightweightPlugins\Cookie\Admin\CommonCookies;
use LightweightPlugins\Cookie\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Cookie\Admin\CommonCookies
 */
final class CommonCookiesTest extends MonkeyTestCase {

	public function test_lists_the_consent_and_login_cookies_as_necessary(): void {
		Functions\stubTranslationFunctions();

		$cookies = CommonCookies::all( 'My Site' );

		$this->assertSame(
			[
				[ 'lw_cookie_consent', 'My Site', 'necessary', 'persistent' ],
				[ 'wordpress_sec_*', 'WordPress', 'necessary', 'session' ],
				[ 'wordpress_logged_in_*', 'WordPress', 'necessary', 'session' ],
			],
			array_map( static fn( array $c ): array => [ $c['name'], $c['provider'], $c['category'], $c['type'] ], $cookies )
		);
	}
}
