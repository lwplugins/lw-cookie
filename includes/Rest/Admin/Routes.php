<?php
/**
 * Admin REST routes bootstrap.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Rest\Admin;

/**
 * Registers the lw-cookie/v1/admin/* routes used by the React admin.
 *
 * Every route requires manage_options; REST cookie auth supplies the nonce.
 */
final class Routes {

	/**
	 * REST namespace.
	 */
	public const NAMESPACE = 'lw-cookie/v1';

	/**
	 * Hook the route registration.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register every admin route.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		( new SettingsController() )->register_routes();
	}

	/**
	 * Permission callback shared by all admin routes.
	 *
	 * @return bool
	 */
	public static function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}
}
