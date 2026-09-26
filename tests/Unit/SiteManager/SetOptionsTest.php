<?php
/**
 * Tests for the lw-cookie/get-options and lw-cookie/set-options abilities.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\SiteManager;

use Brain\Monkey\Functions;
use LightweightPlugins\Cookie\Options;
use LightweightPlugins\Cookie\SiteManager\AutomationPolicy;
use LightweightPlugins\Cookie\SiteManager\CookieService;
use LightweightPlugins\Cookie\Tests\Unit\MonkeyTestCase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Regression (#12): set-options kept its own drifting key list and saved the
 * raw values with Options::save(), so e.g. an unknown banner_position or a
 * non-colour primary_color was stored as-is.
 *
 * @covers \LightweightPlugins\Cookie\SiteManager\CookieService
 * @covers \LightweightPlugins\Cookie\SiteManager\OptionsWriter
 * @covers \LightweightPlugins\Cookie\SiteManager\AutomationPolicy
 * @covers \LightweightPlugins\Cookie\Admin\SettingsSanitizer
 *
 * Each test runs in its own process: other suites define pll__() (Polylang),
 * which would lock the text settings for the rest of the run.
 */
#[RunTestsInSeparateProcesses]
final class SetOptionsTest extends MonkeyTestCase {

	/**
	 * The stored lw_cookie_options value.
	 *
	 * @var array<string, mixed>
	 */
	private array $stored = [];

	/**
	 * Number of update_option() calls.
	 *
	 * @var int
	 */
	private int $writes = 0;

	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();
		$this->stored = [];
		$this->writes = 0;

		Functions\stubTranslationFunctions();
		Functions\when( 'wp_parse_args' )->alias( static fn( $args, $defaults ): array => array_merge( $defaults, (array) $args ) );
		Functions\when( 'get_option' )->alias( fn(): array => $this->stored );
		Functions\when( 'update_option' )->alias(
			function ( string $name, array $value ): bool {
				$this->stored = $value;
				++$this->writes;
				return true;
			}
		);
		Functions\when( 'sanitize_text_field' )->alias( static fn( $v ): string => trim( strip_tags( (string) $v ) ) );
		Functions\when( 'sanitize_textarea_field' )->alias( static fn( $v ): string => trim( strip_tags( (string) $v ) ) );
		Functions\when( 'absint' )->alias( static fn( $v ): int => abs( (int) $v ) );
		Functions\when( 'sanitize_key' )->alias( static fn( $v ): string => strtolower( (string) $v ) );
		Functions\when( 'sanitize_hex_color' )->alias(
			static fn( $v ) => '' === $v ? '' : ( preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', (string) $v ) ? $v : null )
		);
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	public function test_the_issue_example_is_rejected_and_nothing_is_stored(): void {
		$result = CookieService::set_options(
			[
				'options' => [
					'banner_position' => 'invalid-value',
					'primary_color'   => 'not-a-colour',
				],
			]
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'no_valid_options', $result->get_error_code() );
		$this->assertSame( 0, $this->writes );

		$data = $result->get_error_data();
		$this->assertSame( 400, $data['status'] );
		$this->assertSame( [ 'banner_position', 'primary_color' ], array_keys( $data['rejected'] ) );
		$this->assertStringContainsString( 'bottom, top, modal', $data['rejected']['banner_position'] );
		$this->assertStringContainsString( 'hex colour', $data['rejected']['primary_color'] );
		$this->assertStringContainsString( 'banner_position', $result->get_error_message() );
	}

	public function test_valid_keys_are_saved_and_invalid_ones_reported(): void {
		$this->stored = [ 'primary_color' => '#123456' ];

		$result = CookieService::set_options(
			[
				'options' => [
					'banner_position' => 'top',
					'primary_color'   => 'not-a-colour',
					'no_such_setting' => 'x',
				],
			]
		);

		$this->assertIsArray( $result );
		$this->assertSame( [ 'banner_position' ], $result['updated'] );
		$this->assertSame( [ 'primary_color', 'no_such_setting' ], array_keys( (array) $result['rejected'] ) );
		$this->assertSame( 'top', $this->stored['banner_position'] );
		$this->assertSame( '#123456', $this->stored['primary_color'] );
		$this->assertArrayNotHasKey( 'no_such_setting', $this->stored );
		$this->assertSame( 'top', $result['options']['banner_position'] );
	}

	public function test_settings_added_in_1_8_are_writable_and_sanitized(): void {
		$result = CookieService::set_options(
			[
				'options' => [
					'banner_box_alignment' => 'left',
					'cat_necessary_name'   => '<b>Required</b>',
					'hide_for_logged_in'   => 'true',
					'blocked_embed_button' => 'Load video',
					'declared_cookies'     => [
						[
							'name'     => '_ga',
							'provider' => 'Google',
							'purpose'  => 'Stats',
							'duration' => '2 years',
							'category' => 'analytics',
							'type'     => 'persistent',
						],
					],
				],
			]
		);

		$this->assertIsArray( $result );
		$this->assertCount( 5, $result['updated'] );
		$this->assertSame( 'left', $this->stored['banner_box_alignment'] );
		$this->assertSame( 'Required', $this->stored['cat_necessary_name'] );
		$this->assertTrue( $this->stored['hide_for_logged_in'] );
		$this->assertSame( '_ga', $this->stored['declared_cookies'][0]['name'] );
	}

	public function test_wrongly_typed_values_are_rejected(): void {
		$result = CookieService::set_options(
			[
				'options' => [
					'enabled'          => 'banana',
					'consent_duration' => 'forever',
					'declared_cookies' => 'none',
					'banner_title'     => [ 'not', 'text' ],
				],
			]
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame(
			[ 'enabled', 'consent_duration', 'declared_cookies', 'banner_title' ],
			array_keys( $result->get_error_data()['rejected'] )
		);
		$this->assertSame( 0, $this->writes );
	}

	public function test_an_empty_options_object_is_an_error(): void {
		$result = CookieService::set_options( [ 'options' => [] ] );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'invalid_options', $result->get_error_code() );
	}

	public function test_policy_covers_exactly_the_settings_model(): void {
		$keys = AutomationPolicy::KEYS;
		sort( $keys );
		$model = array_keys( Options::get_defaults() );
		sort( $model );

		$this->assertSame( $model, $keys );
	}

	public function test_get_options_matches_what_set_options_can_write(): void {
		$result = CookieService::get_options( [] );

		$this->assertSame( array_keys( Options::get_defaults() ), array_keys( $result['options'] ) );
		$this->assertSame( AutomationPolicy::KEYS, $result['writable_keys'] );
	}

	public function test_text_owned_by_a_multilingual_plugin_is_locked(): void {
		Functions\when( 'pll__' )->returnArg();
		Functions\when( 'admin_url' )->returnArg();

		$result = CookieService::set_options(
			[
				'options' => [
					'banner_title' => 'New title',
					'gcm_enabled'  => true,
				],
			]
		);

		$this->assertIsArray( $result );
		$this->assertSame( [ 'gcm_enabled' ], $result['updated'] );
		$this->assertArrayHasKey( 'banner_title', (array) $result['rejected'] );
		$this->assertSame( 'We value your privacy', $this->stored['banner_title'] );
		$this->assertNotContains( 'banner_title', CookieService::get_options( [] )['writable_keys'] );
	}
}
