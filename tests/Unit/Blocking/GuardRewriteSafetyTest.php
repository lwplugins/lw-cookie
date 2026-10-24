<?php
/**
 * The inline guard must survive a plugin that rewrites the page output.
 *
 * Some plugins buffer the whole page on template_redirect and rename the
 * Google data layer with regexes that also reach into inline scripts. The
 * regexes below are PHP ports of that rewriter, applied in the same order.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Blocking;

use Brain\Monkey\Functions;
use LightweightPlugins\Cookie\Blocking\GuardScript;
use LightweightPlugins\Cookie\Options;
use LightweightPlugins\Cookie\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Cookie\Blocking\GuardScript
 */
final class GuardRewriteSafetyTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();

		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'is_admin' )->justReturn( false );
		Functions\when( 'wp_parse_args' )->alias(
			static fn( $args, $defaults = [] ) => array_merge( (array) $defaults, (array) $args )
		);
		Functions\when( 'home_url' )->alias( static fn( $path = '' ) => 'https://example.test' . $path );
		Functions\when( 'wp_parse_url' )->alias( static fn( $url, $component = -1 ) => parse_url( $url, $component ) );
		Functions\when( 'trailingslashit' )->alias( static fn( $value ) => rtrim( $value, "/\\" ) . '/' );
		Functions\when( 'wp_json_encode' )->alias( static fn( $data ) => json_encode( $data ) );
		Functions\when( '__' )->returnArg();
		Functions\when( 'has_filter' )->justReturn( false );
		Functions\when( 'pll__' )->returnArg();
		Functions\when( 'get_option' )->justReturn( [] );
	}

	protected function tearDown(): void {
		Options::clear_cache();
		parent::tearDown();
	}

	/**
	 * The guard exactly as GuardScript emits it into the page.
	 */
	private function rendered_guard(): string {
		ob_start();
		( new GuardScript() )->output_guard();

		return (string) ob_get_clean();
	}

	/**
	 * Port of the output rewriter, with the layer name it substitutes.
	 */
	private function rewrite( string $buffer, string $name ): string {
		$buffer = preg_replace_callback(
			'/window\.dataLayer\s*=\s*window\.dataLayer\s*\|\|\s*\[\];|window\[\'dataLayer\'\]\s*=\s*window\[\'dataLayer\'\]\s*\|\|\s*\[\];/s',
			static fn( $m ) => str_replace( 'dataLayer', $name, $m[0] ),
			$buffer
		);

		return (string) preg_replace_callback(
			'/gtag\((.*?)\);/s',
			static fn( $m ) => str_replace( 'dataLayer', $name, $m[0] ),
			$buffer
		);
	}

	public function test_the_rewriter_leaves_the_inline_guard_unchanged(): void {
		$html = $this->rendered_guard();

		$this->assertSame( $html, $this->rewrite( $html, 'dataLayerPYS' ) );
	}

	public function test_the_rewriter_leaves_the_guard_unchanged_for_a_custom_layer_name(): void {
		$html = $this->rendered_guard();

		$this->assertSame( $html, $this->rewrite( $html, 'myLayerdataLayer' ) );
	}

	public function test_the_guard_source_never_contains_a_gtag_call_literal(): void {
		$this->assertStringNotContainsString( 'gtag(', $this->rendered_guard() );
		$this->assertStringNotContainsString( 'gtag(', (string) file_get_contents( LW_COOKIE_PATH . 'assets/js/guard.js' ) );
	}

	public function test_the_guard_source_never_names_the_data_layer_as_one_literal(): void {
		$this->assertStringNotContainsString( 'dataLayer', (string) file_get_contents( LW_COOKIE_PATH . 'assets/js/guard.js' ) );
	}
}
