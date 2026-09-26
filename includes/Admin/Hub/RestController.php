<?php
/**
 * REST routes of the hub.
 *
 * Synced from lwplugins/admin-hub 1.0.0 by bin/sync.php. Do not edit
 * this copy: change the admin-hub repo and sync again.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin\Hub;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * GET  /lw-plugins/v1/hub/plugins                 → { plugins: Row[] }
 * POST /lw-plugins/v1/hub/plugins/{slug}/activate → { plugin: Row }
 *
 * Registered by the winning hub copy only. Cookie-authenticated requests
 * need the wp_rest nonce (X-WP-Nonce), which apiFetch sends.
 */
final class RestController {

	/**
	 * Route namespace.
	 */
	public const NAMESPACE = 'lw-plugins/v1';

	/**
	 * Route base.
	 */
	public const BASE = '/hub/plugins';

	/**
	 * Register the routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			self::BASE,
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'list_plugins' ),
				'permission_callback' => array( $this, 'can_view' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			self::BASE . '/(?P<slug>[a-z0-9-]+)/activate',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'activate' ),
				'permission_callback' => array( $this, 'can_activate' ),
				'args'                => array(
					'slug' => array(
						'type'     => 'string',
						'pattern'  => '^[a-z0-9-]+$',
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * Same capability as the page.
	 *
	 * @return bool
	 */
	public function can_view(): bool {
		return current_user_can( Page::CAPABILITY );
	}

	/**
	 * Activation needs activate_plugins (the per-plugin meta capability is
	 * checked again by the Activator).
	 *
	 * @return bool
	 */
	public function can_activate(): bool {
		return current_user_can( 'activate_plugins' );
	}

	/**
	 * Every registry plugin with its state.
	 *
	 * @return WP_REST_Response
	 */
	public function list_plugins(): WP_REST_Response {
		return new WP_REST_Response( array( 'plugins' => Catalog::from_wordpress()->rows() ) );
	}

	/**
	 * Activate one plugin, then return its fresh row.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function activate( WP_REST_Request $request ) {
		$slug   = sanitize_key( (string) $request->get_param( 'slug' ) );
		$result = ( new Activator( Catalog::from_wordpress() ) )->activate( $slug );

		if ( null !== $result ) {
			return $result;
		}

		// A new catalog: activation changed the active plugin list.
		return new WP_REST_Response( array( 'plugin' => Catalog::from_wordpress()->row( $slug ) ) );
	}
}
