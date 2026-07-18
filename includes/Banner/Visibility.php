<?php
/**
 * Banner visibility rules.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Banner;

use LightweightPlugins\Cookie\Options;

/**
 * Decides whether the consent banner UI should be shown for the current
 * request — kept separate so the banner and the floating button share one
 * rule set.
 */
final class Visibility {

	/**
	 * Whether the banner / floating button may render for this request.
	 *
	 * @return bool
	 */
	public static function should_display(): bool {
		// An authorised preview always shows the banner, overriding every hide
		// rule below (already-consented, hidden-for-logged-in, disabled).
		if ( Preview::is_active() ) {
			return true;
		}

		// Never inside a page-builder editor canvas — the banner would clutter
		// the builder and is not part of the edited content (issue #7).
		if ( self::is_builder_context() ) {
			return false;
		}

		// Optionally hide the banner for logged-in users (e.g. so it does not
		// pop up for administrators while they work).
		if ( Options::get( 'hide_for_logged_in' ) && is_user_logged_in() ) {
			return false;
		}

		return true;
	}

	/**
	 * Detect a page-builder editor / preview canvas (Bricks, Elementor).
	 *
	 * @return bool
	 */
	private static function is_builder_context(): bool {
		// Bricks loads the edited page in an iframe with ?bricks=run.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context detection.
		$bricks = isset( $_GET['bricks'] ) ? sanitize_key( wp_unslash( $_GET['bricks'] ) ) : '';
		if ( 'run' === $bricks ) {
			return true;
		}

		// Elementor editor preview.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only context detection.
		if ( isset( $_GET['elementor-preview'] ) ) {
			return true;
		}

		return false;
	}
}
