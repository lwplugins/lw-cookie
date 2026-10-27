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
	 * Attributes that keep a script out of cache plugins' defer/delay/combine.
	 *
	 * Covers LiteSpeed Cache, Cloudflare Rocket Loader and Google PageSpeed.
	 * `true` is a bare attribute.
	 *
	 * @var array<string, string|true>
	 */
	public const ATTRIBUTES = [
		'data-no-defer'           => '1',
		'data-no-optimize'        => '1',
		'data-no-lazy'            => '1',
		'data-cfasync'            => 'false',
		'data-pagespeed-no-defer' => true,
	];

	/**
	 * Inline script holding the consent config (wp_localize_script output).
	 *
	 * @var string
	 */
	private const CONFIG_SCRIPT_ID = 'lw-cookie-consent-js-extra';

	/**
	 * Strings that identify the consent config and the guard inline scripts.
	 *
	 * Cache plugins match inline scripts by their content, not by an id.
	 *
	 * @var array<int, string>
	 */
	private const INLINE_MARKERS = [ 'lwCookieConfig', '__lwGuardCfg' ];

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'script_loader_tag', [ $this, 'add_script_attributes' ], 10, 2 );
		add_filter( 'wp_inline_script_attributes', [ $this, 'inline_script_attributes' ] );

		// WP Rocket exclusions.
		add_filter( 'rocket_delay_js_exclusions', [ $this, 'wp_rocket_exclude' ] );
		add_filter( 'rocket_exclude_defer_js', [ $this, 'wp_rocket_exclude' ] );

		// LiteSpeed Cache exclusions.
		add_filter( 'litespeed_optimize_js_excludes', [ $this, 'litespeed_exclude' ] );
		add_filter( 'litespeed_optm_js_defer_exc', [ $this, 'litespeed_defer_exclude' ] );
	}

	/**
	 * The cache-plugin attributes as HTML, for scripts printed by hand.
	 *
	 * @return string
	 */
	public static function attribute_markup(): string {
		$parts = [];

		foreach ( self::ATTRIBUTES as $name => $value ) {
			$parts[] = true === $value ? $name : $name . '="' . $value . '"';
		}

		return implode( ' ', $parts );
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

		foreach ( explode( ' ', self::attribute_markup() ) as $attr ) {
			if ( strpos( $tag, $attr ) === false ) {
				$tag = str_replace( '<script ', '<script ' . $attr . ' ', $tag );
			}
		}

		return $tag;
	}

	/**
	 * Mark the consent config script so cache plugins do not delay it.
	 *
	 * The consent script runs at once; if its config arrives later, it saves a
	 * consent cookie without a policy version, which then reads as no consent.
	 *
	 * @param array<string, mixed> $attributes Inline script attributes.
	 * @return array<string, mixed>
	 */
	public function inline_script_attributes( array $attributes ): array {
		if ( self::CONFIG_SCRIPT_ID !== ( $attributes['id'] ?? '' ) ) {
			return $attributes;
		}

		return array_merge( $attributes, self::ATTRIBUTES );
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
		$exclusions[] = '__lwGuardCfg';

		return $exclusions;
	}

	/**
	 * Exclude consent script from LiteSpeed Cache JS optimization.
	 *
	 * Covers JS combine, defer, and delay features.
	 *
	 * @param array<int, string> $exclusions Existing exclusions.
	 * @return array<int, string>
	 */
	public function litespeed_exclude( array $exclusions ): array {
		$exclusions[] = self::SCRIPT_FILE;
		$exclusions[] = 'lwCookieConfig';
		$exclusions[] = 'lw-cookie';

		return $exclusions;
	}

	/**
	 * Exclude the consent config and the guard from LiteSpeed's JS defer/delay.
	 *
	 * The optimize excludes above do not reach "Load JS Deferred", which has
	 * its own list and matches inline scripts by content.
	 *
	 * @param array<int, string> $exclusions Existing exclusions.
	 * @return array<int, string>
	 */
	public function litespeed_defer_exclude( array $exclusions ): array {
		return array_merge( $exclusions, self::INLINE_MARKERS );
	}
}
