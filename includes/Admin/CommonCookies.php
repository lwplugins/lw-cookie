<?php
/**
 * Common cookies suggested for the declaration.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin;

/**
 * The cookies every site running this plugin sets (the consent cookie and
 * the WordPress login cookies), offered as a one-click addition to the
 * cookie declaration.
 */
final class CommonCookies {

	/**
	 * Common cookie rows, translated.
	 *
	 * @param string $site_name Provider name for the consent cookie.
	 * @return array<int, array{name: string, provider: string, purpose: string, duration: string, category: string, type: string}>
	 */
	public static function all( string $site_name ): array {
		return [
			[
				'name'     => 'lw_cookie_consent',
				'provider' => $site_name,
				'purpose'  => __( 'Stores cookie consent preferences', 'lw-cookie' ),
				'duration' => __( '1 year', 'lw-cookie' ),
				'category' => 'necessary',
				'type'     => 'persistent',
			],
			[
				'name'     => 'wordpress_sec_*',
				'provider' => 'WordPress',
				'purpose'  => __( 'Authentication cookie for logged-in users', 'lw-cookie' ),
				'duration' => __( 'Session', 'lw-cookie' ),
				'category' => 'necessary',
				'type'     => 'session',
			],
			[
				'name'     => 'wordpress_logged_in_*',
				'provider' => 'WordPress',
				'purpose'  => __( 'Indicates when user is logged in', 'lw-cookie' ),
				'duration' => __( 'Session', 'lw-cookie' ),
				'category' => 'necessary',
				'type'     => 'session',
			],
		];
	}
}
