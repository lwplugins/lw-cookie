<?php
/**
 * Activates an installed LW plugin from the hub.
 *
 * Synced from lwplugins/admin-hub 1.0.0 by bin/sync.php. Do not edit
 * this copy: change the admin-hub repo and sync again.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin\Hub;

use WP_Error;

/**
 * Only plugins that are in the registry AND installed can be activated. The
 * request supplies a slug; the file passed to activate_plugin() is always the
 * matching get_plugins() key, never a path built from request data.
 */
final class Activator {

	/**
	 * Registry rows and detection.
	 *
	 * @var Catalog
	 */
	private Catalog $catalog;

	/**
	 * Constructor.
	 *
	 * @param Catalog $catalog Registry rows and detection.
	 */
	public function __construct( Catalog $catalog ) {
		$this->catalog = $catalog;
	}

	/**
	 * Activate a registry plugin. Already active counts as success.
	 *
	 * @param string $slug Registry slug.
	 * @return WP_Error|null Null on success.
	 */
	public function activate( string $slug ): ?WP_Error {
		if ( null === $this->catalog->entry( $slug ) ) {
			return new WP_Error( 'lw_hub_unknown_plugin', __( 'This is not an LW plugin.', 'lw-cookie' ), array( 'status' => 404 ) );
		}

		$file = $this->catalog->detector()->find_file( $slug );

		if ( null === $file ) {
			return new WP_Error( 'lw_hub_not_installed', __( 'This plugin is not installed.', 'lw-cookie' ), array( 'status' => 409 ) );
		}

		if ( is_plugin_active( $file ) ) {
			return null;
		}

		if ( ! current_user_can( 'activate_plugin', $file ) ) {
			return new WP_Error( 'lw_hub_forbidden', __( 'You are not allowed to activate this plugin.', 'lw-cookie' ), array( 'status' => 403 ) );
		}

		$result = activate_plugin( $file );

		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'lw_hub_activation_failed', $result->get_error_message(), array( 'status' => 500 ) );
		}

		return null;
	}
}
