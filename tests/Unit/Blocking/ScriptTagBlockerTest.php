<?php
/**
 * Tests for ScriptTagBlocker.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Blocking;

use Brain\Monkey\Functions;
use LightweightPlugins\Cookie\Blocking\GuardScript;
use LightweightPlugins\Cookie\Blocking\ScriptTagBlocker;
use LightweightPlugins\Cookie\Options;
use LightweightPlugins\Cookie\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Cookie\Blocking\ScriptTagBlocker
 */
final class ScriptTagBlockerTest extends MonkeyTestCase {

	private const SRC = 'https://www.googletagmanager.com/gtag/js?id=G-1&#038;l=dataLayer';

	protected function setUp(): void {
		parent::setUp();
		Options::clear_cache();
		$this->set_printed( true );
		ScriptTagBlocker::clear_cache();

		Functions\when( 'add_filter' )->justReturn( true );
		Functions\when( 'wp_parse_args' )->alias(
			static fn( $args, $defaults = [] ) => array_merge( (array) $defaults, (array) $args )
		);
		Functions\when( 'home_url' )->alias( static fn( $path = '' ) => 'https://example.test' . $path );
		Functions\when( 'wp_parse_url' )->alias( static fn( $url, $component = -1 ) => parse_url( $url, $component ) );
		Functions\when( 'esc_attr' )->alias( static fn( $text ) => htmlspecialchars( (string) $text, ENT_QUOTES ) );
		$this->saved( [] );
	}

	protected function tearDown(): void {
		$this->set_printed( false );
		unset( $GLOBALS['wp_scripts'] );
		ScriptTagBlocker::clear_cache();
		Options::clear_cache();
		parent::tearDown();
	}

	private function set_printed( bool $value ): void {
		$prop = new \ReflectionProperty( GuardScript::class, 'printed' );
		$prop->setValue( null, $value );
	}

	/**
	 * @param array<string, mixed> $saved Saved plugin options.
	 */
	private function saved( array $saved ): void {
		Options::clear_cache();
		Functions\when( 'get_option' )->justReturn( $saved );
	}

	private function tag( string $attrs = 'async', string $src = self::SRC ): string {
		return '<script id="gt-js-before">window.dataLayer=window.dataLayer||[];</script>' . "\n"
			. '<script src="' . $src . '" id="gt-js" ' . $attrs . '></script>' . "\n"
			. '<script id="gt-js-after">gtag("config","G-1");</script>' . "\n";
	}

	private function run_filter( string $tag, string $src = self::SRC ): string {
		return ( new ScriptTagBlocker() )->filter_tag( $tag, 'gt', html_entity_decode( $src ) );
	}

	public function test_blocked_tag_has_no_src_and_carries_placeholder_attributes(): void {
		$out = $this->run_filter( $this->tag() );

		$this->assertDoesNotMatchRegularExpression( '/\ssrc=/', $out );
		$this->assertStringContainsString( 'type="text/plain"', $out );
		$this->assertStringContainsString( 'data-lw-blocked="1"', $out );
		$this->assertStringContainsString( 'data-lw-category="analytics"', $out );
		$this->assertStringContainsString( 'id="gt-js"', $out );
		$this->assertStringContainsString( 'async', $out );
		$this->assertStringContainsString( '<script data-lw-hold="1" id="gt-js-before">window.dataLayer=window.dataLayer||[];</script>', $out );
		$this->assertStringContainsString( '<script data-lw-hold="1" id="gt-js-after">gtag("config","G-1");</script>', $out );
	}

	public function test_encoded_src_round_trips(): void {
		$out = $this->run_filter( $this->tag() );

		$this->assertSame(
			1,
			preg_match( '/data-lw-original-src="([^"]*)"/', $out, $m )
		);
		$this->assertSame(
			'https://www.googletagmanager.com/gtag/js?id=G-1&l=dataLayer',
			html_entity_decode( $m[1] )
		);
	}

	public function test_module_type_is_preserved(): void {
		$out = $this->run_filter( $this->tag( 'type="module"' ) );
		$this->assertStringContainsString( 'data-lw-original-type="module"', $out );
		$this->assertStringContainsString( 'type="text/plain"', $out );
		$this->assertDoesNotMatchRegularExpression( '/\stype="module"/', $out );

		$out = $this->run_filter( $this->tag( "type='text/javascript'" ) );
		$this->assertStringNotContainsString( 'data-lw-original-type', $out );
		$this->assertStringContainsString( 'type="text/plain"', $out );
	}

	/**
	 * @return array<string, array{0: array<string, mixed>, 1: bool, 2: string}>
	 */
	public static function untouched_cases(): array {
		return [
			'script blocking off' => [ [ 'script_blocking' => false ], true, self::SRC ],
			'guard not printed'   => [ [], false, self::SRC ],
			'necessary rule'      => [
				[
					'custom_blocking_rules' => [
						[
							'pattern'  => 'googletagmanager.com',
							'category' => 'necessary',
						],
					],
				],
				true,
				self::SRC,
			],
			'same origin'         => [ [], true, 'https://example.test/wp-content/app.js' ],
			'unknown host'        => [ [], true, 'https://cdn.example.org/lib.js' ],
		];
	}

	/**
	 * @dataProvider untouched_cases
	 *
	 * @param array<string, mixed> $saved   Saved options.
	 * @param bool                 $printed Whether the guard was printed.
	 * @param string               $src     Script URL.
	 */
	public function test_left_untouched_when( array $saved, bool $printed, string $src ): void {
		$this->saved( $saved );
		ScriptTagBlocker::clear_cache();
		$this->set_printed( $printed );

		$tag = $this->tag( 'async', $src );
		$this->assertSame( $tag, $this->run_filter( $tag, $src ) );
	}

	/**
	 * @param array<string, array<int, string>> $deps Handle → registered deps.
	 */
	private function register( array $deps ): void {
		$registered = [];
		foreach ( $deps as $handle => $list ) {
			$registered[ $handle ] = (object) [ 'deps' => $list ];
		}
		$GLOBALS['wp_scripts'] = (object) [ 'registered' => $registered ];
	}

	private function local_tag( string $handle ): string {
		return '<script id="' . $handle . '-js-translations">wp.i18n.setLocaleData({});</script>' . "\n"
			. '<script id="' . $handle . '-js-before">var cfg = 1;</script>' . "\n"
			. '<script src="https://example.test/wp-content/' . $handle . '.js?ver=1" id="' . $handle . '-js"></script>' . "\n"
			. '<script id="' . $handle . '-js-after">init();</script>' . "\n";
	}

	private function filter_local( string $handle ): string {
		return ( new ScriptTagBlocker() )->filter_tag( $this->local_tag( $handle ), $handle, 'https://example.test/wp-content/' . $handle . '.js?ver=1' );
	}

	public function test_dependent_of_blocked_script_becomes_necessary_placeholder_with_held_inline_scripts(): void {
		$this->register(
			[
				'gt'  => [],
				'dep' => [ 'jquery', 'gt' ],
			]
		);
		$this->run_filter( $this->tag() );

		$out = $this->filter_local( 'dep' );

		$this->assertDoesNotMatchRegularExpression( '/\ssrc=/', $out );
		$this->assertStringContainsString( 'data-lw-category="necessary"', $out );
		$this->assertStringContainsString( 'data-lw-original-src="https://example.test/wp-content/dep.js?ver=1"', $out );
		$this->assertStringContainsString( '<script data-lw-hold="1" id="dep-js-translations">', $out );
		$this->assertStringContainsString( '<script data-lw-hold="1" id="dep-js-before">', $out );
		$this->assertStringContainsString( '<script data-lw-hold="1" id="dep-js-after">', $out );
		$this->assertSame( 3, substr_count( $out, 'data-lw-hold' ) );
	}

	public function test_dependent_with_its_own_tracker_category_keeps_it(): void {
		$this->register(
			[
				'gt'  => [],
				'fbp' => [ 'gt' ],
			]
		);
		$this->run_filter( $this->tag() );

		$src = 'https://connect.facebook.net/en_US/fbevents.js';
		$out = ( new ScriptTagBlocker() )->filter_tag( '<script src="' . $src . '" id="fbp-js"></script>', 'fbp', $src );

		$this->assertStringContainsString( 'data-lw-category="marketing"', $out );
	}

	public function test_handle_without_blocked_deps_is_untouched(): void {
		$this->register(
			[
				'gt'    => [],
				'other' => [ 'jquery' ],
			]
		);
		$this->run_filter( $this->tag() );

		$this->assertSame( $this->local_tag( 'other' ), $this->filter_local( 'other' ) );
	}

	public function test_dependent_placeholder_is_a_blocked_dependency_of_its_own_dependents(): void {
		$this->register(
			[
				'gt'  => [],
				'mid' => [ 'gt' ],
				'top' => [ 'mid' ],
			]
		);
		$this->run_filter( $this->tag() );
		$this->filter_local( 'mid' );

		$top = $this->filter_local( 'top' );

		$this->assertStringContainsString( 'data-lw-category="necessary"', $top );
		$this->assertStringContainsString( '<script data-lw-hold="1" id="top-js-after">', $top );
	}

	public function test_resource_hints_drop_blocked_hosts(): void {
		$urls = [
			'https://www.googletagmanager.com',
			[
				'href' => 'https://connect.facebook.net',
				'crossorigin',
			],
			'https://fonts.gstatic.com',
		];

		$this->assertSame(
			[ 'https://fonts.gstatic.com' ],
			( new ScriptTagBlocker() )->filter_hints( $urls, 'preconnect' )
		);

		$this->saved( [ 'script_blocking' => false ] );
		$this->assertSame( $urls, ( new ScriptTagBlocker() )->filter_hints( $urls, 'preconnect' ) );
	}
}
