<?php
/**
 * Migration REST controller.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Rest\Admin;

use LightweightPlugins\Cookie\Admin\SettingsStore;
use LightweightPlugins\Cookie\Migration\Importer;
use WP_Error;
use WP_REST_Response;
use WP_REST_Server;

/**
 * GET (preview) / POST (import) lw-cookie/v1/admin/migration/complianz.
 */
final class MigrationController {

	/**
	 * Register the routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			Routes::NAMESPACE,
			'/admin/migration/complianz',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'preview' ],
					'permission_callback' => [ Routes::class, 'can_manage' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'import' ],
					'permission_callback' => [ Routes::class, 'can_manage' ],
				],
			]
		);
	}

	/**
	 * What an import would change.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function preview(): WP_REST_Response|WP_Error {
		return $this->respond( Importer::run( true ) );
	}

	/**
	 * Run the import; the response carries the new settings for the screen.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function import(): WP_REST_Response|WP_Error {
		$report = Importer::run( false );

		return null === $report ? $this->respond( null ) : new WP_REST_Response(
			[
				'report'  => $report,
				'options' => SettingsStore::current(),
				'meta'    => SettingsMeta::build(),
			]
		);
	}

	/**
	 * Report response, or 404 when there is nothing to import.
	 *
	 * @param array<string, mixed>|null $report Importer report.
	 * @return WP_REST_Response|WP_Error
	 */
	private function respond( ?array $report ): WP_REST_Response|WP_Error {
		if ( null === $report ) {
			return new WP_Error( 'lw_cookie_no_complianz', __( 'No Complianz settings were found on this site.', 'lw-cookie' ), [ 'status' => 404 ] );
		}

		return new WP_REST_Response( [ 'report' => $report ] );
	}
}
