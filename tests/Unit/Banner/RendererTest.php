<?php
/**
 * Tests for Banner\Renderer.
 *
 * Covers issue #14: search engines must not quote the banner or the
 * preferences modal in result snippets, so both outer containers carry
 * Google's `data-nosnippet` attribute.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Banner;

use Brain\Monkey\Functions;
use LightweightPlugins\Cookie\Banner\Renderer;
use LightweightPlugins\Cookie\Options;
use LightweightPlugins\Cookie\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Cookie\Banner\Renderer
 */
final class RendererTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();

		Functions\stubTranslationFunctions();
		Functions\stubEscapeFunctions();
		Functions\when( 'get_option' )->justReturn( [] );
		Functions\when( 'wp_parse_args' )->alias(
			static fn( $args, $defaults = [] ) => array_merge( (array) $defaults, (array) $args )
		);
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'checked' )->justReturn( '' );
		Functions\when( 'disabled' )->justReturn( '' );
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * @dataProvider provide_container_ids
	 */
	public function test_container_is_excluded_from_search_snippets( string $id ): void {
		ob_start();
		( new Renderer() )->render_banner();
		$html = (string) ob_get_clean();

		$this->assertMatchesRegularExpression(
			'/<div id="' . $id . '"[^>]*\sdata-nosnippet[\s>]/',
			$html
		);
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function provide_container_ids(): array {
		return [
			'banner'            => [ 'lw-cookie-notice' ],
			'preferences modal' => [ 'lw-cookie-preferences' ],
		];
	}

	/**
	 * @dataProvider provide_button_orders
	 *
	 * @param array<string, mixed> $saved    Saved options.
	 * @param array<int, string>   $expected Button order in the markup.
	 */
	public function test_banner_buttons_follow_the_configured_order( array $saved, array $expected ): void {
		Functions\when( 'get_option' )->justReturn( $saved );

		ob_start();
		( new Renderer() )->render_banner();
		$html = (string) ob_get_clean();

		preg_match( '/<div class="lw-cookie-actions">(.*?)<\/div>/s', $html, $actions );
		preg_match_all( '/data-lw-cookie-(accept|reject|customize)\b/', $actions[1] ?? '', $found );

		$this->assertSame( $expected, $found[1] );
	}

	/**
	 * @return array<string, array{0: array<string, mixed>, 1: array<int, string>}>
	 */
	public static function provide_button_orders(): array {
		return [
			'default (unchanged)' => [ [], [ 'customize', 'reject', 'accept' ] ],
			'accept first'        => [ [ 'banner_button_order' => 'accept,customize,reject' ], [ 'accept', 'customize', 'reject' ] ],
			'invalid saved value' => [ [ 'banner_button_order' => 'accept,accept' ], [ 'customize', 'reject', 'accept' ] ],
		];
	}
}
