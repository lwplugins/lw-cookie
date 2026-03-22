<?php
/**
 * LW Site Manager Integration.
 *
 * Registers Cookie abilities when LW Site Manager is active.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\SiteManager;

/**
 * Hooks into LW Site Manager to register Cookie abilities.
 */
final class Integration {

	/**
	 * Initialize hooks. Safe to call even if Site Manager is not active.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'lw_site_manager_register_categories', [ self::class, 'register_category' ] );
		add_action( 'lw_site_manager_register_abilities', [ self::class, 'register_abilities' ] );
	}

	/**
	 * Register the Cookie ability category.
	 *
	 * @return void
	 */
	public static function register_category(): void {
		wp_register_ability_category(
			'cookie',
			[
				'label'       => __( 'Cookie Consent', 'lw-cookie' ),
				'description' => __( 'Cookie consent management abilities', 'lw-cookie' ),
			]
		);
	}

	/**
	 * Register Cookie abilities.
	 *
	 * @param object $permissions Permission manager from Site Manager.
	 * @return void
	 */
	public static function register_abilities( object $permissions ): void {
		CookieAbilities::register( $permissions );
	}
}
