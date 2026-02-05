<?php
/**
 * Options management class.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie;

/**
 * Handles plugin options and settings.
 */
final class Options {

	/**
	 * Option name in database.
	 */
	public const OPTION_NAME = 'lw_cookie_options';

	/**
	 * Cached options.
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $options = null;

	/**
	 * Get default options.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_defaults(): array {
		return [
			// General.
			'enabled'              => true,
			'privacy_policy_page'  => 0,
			'policy_version'       => '1.0',

			// Appearance.
			'banner_position'      => 'bottom',
			'banner_layout'        => 'bar',
			'primary_color'        => '#2271b1',
			'text_color'           => '#1d2327',
			'background_color'     => '#ffffff',
			'border_radius'        => '4',

			// Categories - all OFF by default (GDPR compliance).
			'cat_functional_name'  => 'Functional',
			'cat_functional_desc'  => 'These cookies enable enhanced functionality and personalization.',
			'cat_analytics_name'   => 'Analytics',
			'cat_analytics_desc'   => 'These cookies help us understand how visitors interact with our website.',
			'cat_marketing_name'   => 'Marketing',
			'cat_marketing_desc'   => 'These cookies are used to deliver relevant advertisements.',

			// Texts.
			'banner_title'         => 'We value your privacy',
			'banner_message'       => 'We use cookies to enhance your browsing experience and analyze our traffic.',
			'btn_accept_all'       => 'Accept All',
			'btn_reject_all'       => 'Reject All',
			'btn_customize'        => 'Customize',
			'btn_save'             => 'Save Preferences',

			// Advanced.
			'consent_duration'     => 365,
			'script_blocking'      => true,
			'gcm_enabled'          => false,
			'show_floating_button' => true,
			'floating_button_pos'  => 'bottom-left',
		];
	}

	/**
	 * Get all options.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_all(): array {
		if ( null === self::$options ) {
			$saved         = get_option( self::OPTION_NAME, [] );
			self::$options = wp_parse_args( $saved, self::get_defaults() );
		}

		return self::$options;
	}

	/**
	 * Get a single option.
	 *
	 * @param string $key     Option key.
	 * @param mixed  $default Default value if not set.
	 * @return mixed
	 */
	public static function get( string $key, mixed $default = null ): mixed {
		$options = self::get_all();

		if ( array_key_exists( $key, $options ) ) {
			return $options[ $key ];
		}

		return $default ?? ( self::get_defaults()[ $key ] ?? null );
	}

	/**
	 * Set a single option.
	 *
	 * @param string $key   Option key.
	 * @param mixed  $value Option value.
	 * @return bool
	 */
	public static function set( string $key, mixed $value ): bool {
		$options         = self::get_all();
		$options[ $key ] = $value;

		return self::save( $options );
	}

	/**
	 * Save all options.
	 *
	 * @param array<string, mixed> $options Options to save.
	 * @return bool
	 */
	public static function save( array $options ): bool {
		self::$options = $options;
		return update_option( self::OPTION_NAME, $options );
	}

	/**
	 * Reset options to defaults.
	 *
	 * @return bool
	 */
	public static function reset(): bool {
		self::$options = null;
		return delete_option( self::OPTION_NAME );
	}

	/**
	 * Clear options cache.
	 *
	 * @return void
	 */
	public static function clear_cache(): void {
		self::$options = null;
	}

	/**
	 * Get cookie categories configuration.
	 *
	 * @return array<string, array{name: string, description: string, required: bool}>
	 */
	public static function get_categories(): array {
		return [
			'necessary'  => [
				'name'        => __( 'Necessary', 'lw-cookie' ),
				'description' => __( 'Essential cookies required for the website to function.', 'lw-cookie' ),
				'required'    => true,
			],
			'functional' => [
				'name'        => self::get( 'cat_functional_name' ),
				'description' => self::get( 'cat_functional_desc' ),
				'required'    => false,
			],
			'analytics'  => [
				'name'        => self::get( 'cat_analytics_name' ),
				'description' => self::get( 'cat_analytics_desc' ),
				'required'    => false,
			],
			'marketing'  => [
				'name'        => self::get( 'cat_marketing_name' ),
				'description' => self::get( 'cat_marketing_desc' ),
				'required'    => false,
			],
		];
	}
}
