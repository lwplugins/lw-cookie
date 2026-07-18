<?php
/**
 * Banner preview mode.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Banner;

/**
 * Lets an administrator preview the consent banner on the live front-end —
 * even after they have already consented, and even while the banner is not
 * yet enabled — by appending `?lw-cookie-preview=1` to any URL.
 */
final class Preview {

	/**
	 * Query argument that triggers preview mode.
	 */
	public const QUERY_ARG = 'lw-cookie-preview';

	/**
	 * Whether the current request is an authorised banner preview.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only preview flag, gated by capability below.
		if ( empty( $_GET[ self::QUERY_ARG ] ) ) {
			return false;
		}

		return current_user_can( 'manage_options' );
	}

	/**
	 * Build a front-end preview URL for a given page.
	 *
	 * @param string $url Base URL (defaults to the site home).
	 * @return string
	 */
	public static function url( string $url = '' ): string {
		$base = '' !== $url ? $url : home_url( '/' );

		return add_query_arg( self::QUERY_ARG, '1', $base );
	}
}
