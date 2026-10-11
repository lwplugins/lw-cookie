<?php
/**
 * Data layer names used by plugins that rename the Google data layer.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Blocking;

/**
 * Finds the extra data layer the guard must send Consent Mode signals to.
 *
 * A plugin that rewrites the page output can move the site's Google tag onto
 * a renamed data layer. Tags then read their consent state from that layer,
 * so the guard has to push its default and update commands there as well.
 * Only that plugin's own settings are read, and only while it is active with
 * the renaming switched on; otherwise the guard keeps using `dataLayer` alone.
 */
final class DataLayerNames {

	/**
	 * Function that returns the plugin's Google tag settings object.
	 */
	private const SETTINGS_FUNCTION = 'PixelYourSite\GATags';

	/**
	 * Name the plugin uses when the renaming option is "default".
	 */
	private const DEFAULT_NAME = 'dataLayerPYS';

	/**
	 * Extra data layer names to receive Consent Mode commands.
	 *
	 * @return array<int, string>
	 */
	public static function extra(): array {
		if ( ! function_exists( self::SETTINGS_FUNCTION ) ) {
			return [];
		}

		$settings_function = self::SETTINGS_FUNCTION;

		// Third-party function, absent from the analysed code (no stub): checked just above.
		return self::from_settings( $settings_function() ); // @phpstan-ignore callable.nonCallable
	}

	/**
	 * Work out the extra layer from the plugin's settings object.
	 *
	 * @param mixed $settings Whatever the plugin's settings function returned.
	 * @return array<int, string>
	 */
	public static function from_settings( $settings ): array {
		if ( ! is_object( $settings ) || ! method_exists( $settings, 'enabled' ) || ! method_exists( $settings, 'getOption' ) ) {
			return [];
		}

		// The plugin only rewrites the page when its Google tag is configured.
		if ( ! $settings->enabled() ) {
			return [];
		}

		switch ( $settings->getOption( 'gtag_datalayer_type' ) ) {
			case 'default':
				$name = self::DEFAULT_NAME;
				break;
			case 'custom':
				$name = $settings->getOption( 'gtag_datalayer_name' );
				break;
			default:
				return [];
		}

		return self::is_usable( $name ) ? [ $name ] : [];
	}

	/**
	 * Whether a value is a safe JavaScript identifier other than `dataLayer`.
	 *
	 * @param mixed $name Candidate layer name.
	 * @phpstan-assert-if-true string $name
	 * @return bool
	 */
	private static function is_usable( $name ): bool {
		return is_string( $name )
			&& 'dataLayer' !== $name
			&& 1 === preg_match( '/^[A-Za-z_$][A-Za-z0-9_$]*$/', $name );
	}
}
