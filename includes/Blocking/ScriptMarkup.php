<?php
/**
 * Script Markup — reads and rewrites <script> start tags in printed HTML.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Blocking;

/**
 * Low-level helpers for the markup WordPress prints for a script handle.
 */
final class ScriptMarkup {

	/**
	 * Rewrite the start tag of every top-level <script> element.
	 *
	 * Whole elements are matched, so text inside an inline script body (even a
	 * literal "<script src=…>" string) is never touched. The body pattern is
	 * possessive, so a huge inline script cannot exhaust the backtrack limit;
	 * if matching fails anyway, the markup comes back unchanged.
	 *
	 * @param string                   $html     Markup of a handle.
	 * @param callable(string): string $callback Receives a start tag, returns its replacement.
	 * @return string
	 */
	public static function map_start_tags( string $html, callable $callback ): string {
		$out = preg_replace_callback(
			'/(<script\b[^>]*>)((?:[^<]++|<(?!\/script))*+<\/script\s*>)/i',
			static fn( array $m ): string => $callback( $m[1] ) . $m[2],
			$html
		);

		return null === $out ? $html : $out;
	}

	/**
	 * Read the attributes of a start tag (values are raw, still HTML-escaped).
	 *
	 * Boolean attributes map to an empty string.
	 *
	 * @param string $start_tag A `<script …>` start tag.
	 * @return array<string, string>
	 */
	public static function attributes( string $start_tag ): array {
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
	 * Whether a type attribute value is classic JavaScript (empty counts).
	 *
	 * Modules and data blocks (JSON, templates) are not.
	 *
	 * @param string $type Raw type attribute value.
	 * @return bool
	 */
	public static function is_classic_js( string $type ): bool {
		$type = strtolower( trim( $type ) );

		return '' === $type || 1 === preg_match( '/^(text|application)\/(x-)?(java|ecma)script$/', $type );
	}
}
