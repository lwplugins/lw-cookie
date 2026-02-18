<?php
/**
 * Cache plugin compatibility class.
 *
 * Excludes the consent script from JS defer/delay features
 * in LiteSpeed Cache, WP Rocket, Cloudflare, and others.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Integrations;

/**
 * Ensures the cookie consent script is not delayed or deferred by cache plugins.
 */
final class CacheCompat {

	/**
	 * Script handle to protect.
	 *
	 * @var string
	 */
	private const SCRIPT_HANDLE = 'lw-cookie-consent';

	/**
	 * Script filename for pattern matching.
	 *
	 * @var string
	 */
	private const SCRIPT_FILE = 'consent.js';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'script_loader_tag', [ $this, 'add_script_attributes' ], 10, 2 );

		// WP Rocket exclusions.
		add_filter( 'rocket_delay_js_exclusions', [ $this, 'wp_rocket_exclude' ] );
		add_filter( 'rocket_exclude_defer_js', [ $this, 'wp_rocket_exclude' ] );

		// LiteSpeed Cache exclusions.
		add_filter( 'litespeed_optimize_js_excludes', [ $this, 'litespeed_exclude' ] );
	}

	/**
	 * Add no-defer/no-delay attributes to the consent script tag.
	 *
	 * Covers LiteSpeed Cache, Cloudflare Rocket Loader,
	 * and Google PageSpeed module.
	 *
	 * @param string $tag    Script HTML tag.
	 * @param string $handle Script handle.
	 * @return string
	 */
	public function add_script_attributes( string $tag, string $handle ): string {
		if ( self::SCRIPT_HANDLE !== $handle ) {
			return $tag;
		}

		$attributes = [
			'data-no-defer="1"',
			'data-no-optimize="1"',
			'data-cfasync="false"',
			'data-pagespeed-no-defer',
		];

		foreach ( $attributes as $attr ) {
			if ( strpos( $tag, $attr ) === false ) {
				$tag = str_replace( '<script ', '<script ' . $attr . ' ', $tag );
			}
		}

		return $tag;
	}

	/**
	 * Exclude consent script from WP Rocket delay/defer.
	 *
	 * @param array<int, string> $exclusions Existing exclusions.
	 * @return array<int, string>
	 */
	public function wp_rocket_exclude( array $exclusions ): array {
		$exclusions[] = self::SCRIPT_FILE;
		$exclusions[] = 'lwCookieConfig';

		return $exclusions;
	}

	/**
	 * Exclude consent script from LiteSpeed Cache JS optimization.
	 *
	 * @param array<int, string> $exclusions Existing exclusions.
	 * @return array<int, string>
	 */
	public function litespeed_exclude( array $exclusions ): array {
		$exclusions[] = self::SCRIPT_FILE;

		return $exclusions;
	}
}
