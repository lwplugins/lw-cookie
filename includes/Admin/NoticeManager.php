<?php
/**
 * Admin Notice Manager.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin;

/**
 * Isolates third-party admin notices on LW plugin pages.
 *
 * Wraps all notices in a hidden div so they don't break the layout.
 * Based on the WooCommerce admin notice isolation pattern.
 */
final class NoticeManager {

	/**
	 * Whether hooks have been registered.
	 *
	 * @var bool
	 */
	private static bool $initialized = false;

	/**
	 * Register hooks (safe to call multiple times).
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( self::$initialized ) {
			return;
		}

		self::$initialized = true;

		add_action( 'admin_head', [ self::class, 'inject_styles' ] );
		add_action( 'admin_notices', [ self::class, 'open_wrap' ], -9999 );
		add_action( 'admin_notices', [ self::class, 'close_wrap' ], PHP_INT_MAX );
		add_filter( 'admin_body_class', [ self::class, 'add_body_class' ] );
	}

	/**
	 * Check if the current screen is an LW plugin page.
	 *
	 * @return bool
	 */
	public static function is_lw_page(): bool {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return false;
		}

		return str_contains( $screen->id, 'lw-' );
	}

	/**
	 * Open the notice wrapper div.
	 *
	 * @return void
	 */
	public static function open_wrap(): void {
		if ( self::is_lw_page() ) {
			echo '<div class="lw-notice-wrap">';
		}
	}

	/**
	 * Close the notice wrapper div.
	 *
	 * @return void
	 */
	public static function close_wrap(): void {
		if ( self::is_lw_page() ) {
			echo '</div>';
		}
	}

	/**
	 * Inject inline styles to hide the notice wrapper.
	 *
	 * @return void
	 */
	public static function inject_styles(): void {
		if ( self::is_lw_page() ) {
			echo '<style>'
				. '.lw-notice-wrap{display:none}'
				. 'body.lw-plugins-admin-page .notice:not(.lw-notice){display:none!important}'
				. 'body.lw-plugins-admin-page .updated:not(.lw-notice){display:none!important}'
				. '</style>';
		}
	}

	/**
	 * Add body class to LW plugin pages.
	 *
	 * @param string $classes Space-separated body classes.
	 * @return string
	 */
	public static function add_body_class( string $classes ): string {
		if ( self::is_lw_page() ) {
			$classes .= ' lw-plugins-admin-page';
		}

		return $classes;
	}
}
