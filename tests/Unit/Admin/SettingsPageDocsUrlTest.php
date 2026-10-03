<?php
/**
 * SettingsPage docs link unit tests.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use LightweightPlugins\Cookie\Admin\SettingsPage;
use LightweightPlugins\Cookie\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Cookie\Admin\SettingsPage::docs_url
 */
final class SettingsPageDocsUrlTest extends MonkeyTestCase {

	public function test_hungarian_admin_gets_the_hu_page(): void {
		Functions\when( 'get_user_locale' )->justReturn( 'hu_HU' );

		$this->assertSame( 'https://docs.lwplugins.com/hu/plugins/lw-cookie', SettingsPage::docs_url() );
	}

	public function test_english_admin_gets_the_en_page(): void {
		Functions\when( 'get_user_locale' )->justReturn( 'en_US' );

		$this->assertSame( 'https://docs.lwplugins.com/en/plugins/lw-cookie', SettingsPage::docs_url() );
	}

	public function test_other_locales_fall_back_to_en(): void {
		Functions\when( 'get_user_locale' )->justReturn( 'de_DE' );

		$this->assertSame( 'https://docs.lwplugins.com/en/plugins/lw-cookie', SettingsPage::docs_url() );
	}
}
