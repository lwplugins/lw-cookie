<?php
/**
 * Settings REST controller.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Rest\Admin;

use LightweightPlugins\Cookie\Admin\SettingsStore;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * GET/POST lw-cookie/v1/admin/settings.
 */
final class SettingsController {

	/**
	 * Register the routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			Routes::NAMESPACE,
			'/admin/settings',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_settings' ],
					'permission_callback' => [ Routes::class, 'can_manage' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'save_settings' ],
					'permission_callback' => [ Routes::class, 'can_manage' ],
				],
			]
		);
	}

	/**
	 * Current options plus the screen context.
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings(): WP_REST_Response {
		return $this->response( SettingsStore::current() );
	}

	/**
	 * Partial update: only the submitted keys change. Non-option params
	 * (e.g. `_locale`) are dropped by the merge.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function save_settings( WP_REST_Request $request ): WP_REST_Response {
		return $this->response( SettingsStore::save( $request->get_params() ) );
	}

	/**
	 * Response shape shared by GET and POST.
	 *
	 * @param array<string, mixed> $options Typed options.
	 * @return WP_REST_Response
	 */
	private function response( array $options ): WP_REST_Response {
		return new WP_REST_Response(
			[
				'options' => $options,
				'meta'    => SettingsMeta::build(),
			]
		);
	}
}
