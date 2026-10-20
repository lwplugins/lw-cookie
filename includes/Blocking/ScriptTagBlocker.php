<?php
/**
 * Script Tag Blocker — turns enqueued tracker scripts into inert placeholders.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Blocking;

use LightweightPlugins\Cookie\Options;

/**
 * Rewrites enqueued third-party <script src> tags on the server.
 *
 * Why: the browser's preload scanner fetches a static <script src> before any
 * JavaScript runs, so the guard cannot stop it, and on the first visit the
 * Service Worker does not control the page yet. A tag that never carries a
 * src (type="text/plain", original URL in a data attribute) is not fetched at
 * all; guard.js restores it once the category is consented.
 *
 * The markup depends only on the rule set, never on the visitor's consent, so
 * the output is the same for everyone and safe to page-cache. Resource hints
 * (preconnect, dns-prefetch, …) for blocked hosts are dropped for the same
 * reason.
 *
 * A script that depends on a blocked one becomes a placeholder too (category
 * "necessary" unless its own URL has another), and the inline scripts of every
 * placeholdered handle are marked data-lw-hold, so guard.js runs them in their
 * printed order once the scripts before them have loaded.
 */
final class ScriptTagBlocker {

	/**
	 * Request-level cache of the domain map.
	 *
	 * @var array<int|string, string>|null
	 */
	private static ?array $domains = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'script_loader_tag', [ $this, 'filter_tag' ], 20, 3 );
		add_filter( 'wp_resource_hints', [ $this, 'filter_hints' ], 20, 2 );
	}

	/**
	 * Forget the cached domain map and the placeholdered handles.
	 *
	 * @return void
	 */
	public static function clear_cache(): void {
		self::$domains = null;
		PlaceholderRegistry::clear();
	}

	/**
	 * Replace the src of a blocked script with placeholder attributes.
	 *
	 * @param string $tag    Full markup WordPress built for the handle.
	 * @param string $handle Script handle.
	 * @param string $src    Script URL.
	 * @return string
	 */
	public function filter_tag( string $tag, string $handle, string $src ): string {
		unset( $src ); // Read from the tag itself: it may hold several scripts.
		if ( ! $this->is_active() || false === stripos( $tag, 'src' ) ) {
			return $tag;
		}

		$has_blocked_dep = null !== PlaceholderRegistry::blocked_dependency( $handle );
		$blocked         = null;

		$tag = (string) preg_replace_callback(
			'/<script\b[^>]*>/i',
			function ( array $m ) use ( $handle, $has_blocked_dep, &$blocked ): string {
				$attrs = self::parse_attributes( $m[0] );

				if ( ! isset( $attrs['src'] ) ) {
					return $m[0];
				}

				$category = $this->category_for( html_entity_decode( $attrs['src'], ENT_QUOTES ) );

				if ( null === $category || 'necessary' === $category ) {
					if ( ! $has_blocked_dep ) {
						return $m[0];
					}
					// Needs a blocked script: placeholdered only to keep the
					// order; as "necessary" it loads even without consent.
					$category = 'necessary';
				}

				if ( ( $attrs['id'] ?? '' ) === $handle . '-js' ) {
					$blocked = $category;
				}

				return self::build_placeholder( $attrs, $category );
			},
			$tag
		);

		if ( null === $blocked ) {
			return $tag;
		}

		PlaceholderRegistry::remember( $handle, $blocked );

		return self::hold_inline_scripts( $tag );
	}

	/**
	 * Mark the inline scripts (before, after, translations) of a placeholdered
	 * handle. They stay executable; guard.js holds them while a placeholder
	 * printed before them is still loading.
	 *
	 * @param string $tag Markup of the handle.
	 * @return string
	 */
	private static function hold_inline_scripts( string $tag ): string {
		return (string) preg_replace_callback(
			'/<script\b[^>]*>/i',
			static function ( array $m ): string {
				$attrs = self::parse_attributes( $m[0] );

				if ( isset( $attrs['src'] ) || isset( $attrs['data-lw-original-src'] ) ) {
					return $m[0];
				}

				return '<script data-lw-hold="1"' . substr( $m[0], 7 );
			},
			$tag
		);
	}

	/**
	 * Drop resource hints that would contact a blocked host.
	 *
	 * @param array<int, string|array<int|string, mixed>> $urls     Hint entries.
	 * @param string                                      $relation Hint relation (unused: all are dropped).
	 * @return array<int, string|array<int|string, mixed>>
	 */
	public function filter_hints( array $urls, string $relation ): array {
		unset( $relation );

		if ( ! $this->is_active() ) {
			return $urls;
		}

		$kept = array_filter(
			$urls,
			function ( $entry ): bool {
				$href     = is_array( $entry ) ? ( $entry['href'] ?? '' ) : $entry;
				$category = is_string( $href ) ? $this->category_for( $href ) : null;

				return null === $category || 'necessary' === $category;
			}
		);

		return array_values( $kept );
	}

	/**
	 * Rewrites apply only with Script Blocking on and the guard on the page.
	 *
	 * @return bool
	 */
	private function is_active(): bool {
		return (bool) Options::get( 'script_blocking' ) && GuardScript::was_printed();
	}

	/**
	 * Consent category of a URL (null: not blocked).
	 *
	 * @param string $url URL.
	 * @return string|null
	 */
	private function category_for( string $url ): ?string {
		if ( null === self::$domains ) {
			self::$domains = Entities::get_domains();
		}

		return UrlCategory::for_url( $url, self::$domains, (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	}

	/**
	 * Read the attributes of a start tag (values are raw, still HTML-escaped).
	 *
	 * Boolean attributes map to an empty string.
	 *
	 * @param string $start_tag A `<script …>` start tag.
	 * @return array<string, string>
	 */
	private static function parse_attributes( string $start_tag ): array {
		$inner = substr( $start_tag, 7, -1 );
		$attrs = [];

		preg_match_all(
			'/([^\s"\'<>\/=]+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'>]+)))?/',
			$inner,
			$found,
			PREG_SET_ORDER
		);

		foreach ( $found as $m ) {
			$name = strtolower( $m[1] );
			if ( isset( $attrs[ $name ] ) ) {
				continue;
			}
			$value = '' !== ( $m[2] ?? '' ) ? $m[2] : ( '' !== ( $m[3] ?? '' ) ? str_replace( '"', '&quot;', $m[3] ) : ( $m[4] ?? '' ) );

			$attrs[ $name ] = $value;
		}

		return $attrs;
	}

	/**
	 * Build the inert placeholder start tag.
	 *
	 * @param array<string, string> $attrs    Original attributes.
	 * @param string                $category Consent category.
	 * @return string
	 */
	private static function build_placeholder( array $attrs, string $category ): string {
		$src  = html_entity_decode( $attrs['src'], ENT_QUOTES );
		$type = strtolower( trim( $attrs['type'] ?? '' ) );
		unset( $attrs['src'], $attrs['type'] );

		$out  = '<script type="text/plain" data-lw-blocked="1"';
		$out .= ' data-lw-category="' . esc_attr( $category ) . '"';
		$out .= ' data-lw-original-src="' . esc_attr( $src ) . '"';

		if ( '' !== $type && 'text/javascript' !== $type ) {
			$out .= ' data-lw-original-type="' . esc_attr( $type ) . '"';
		}

		foreach ( $attrs as $name => $value ) {
			$out .= '' === $value ? ' ' . $name : ' ' . $name . '="' . $value . '"';
		}

		return $out . '>';
	}
}
