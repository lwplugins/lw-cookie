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
}
