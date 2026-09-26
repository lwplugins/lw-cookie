<?php
/**
 * Paths and URLs of the hub's bundled files.
 *
 * Synced from lwplugins/admin-hub 1.0.0 by bin/sync.php. Do not edit
 * this copy: change the admin-hub repo and sync again.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin\Hub;

/**
 * The hub's built app, icons and asset manifest live in DIR inside the host
 * plugin (bin/sync.php puts them there). Outside build/: wp-scripts empties
 * build/ on every build of the host plugin's own admin.
 */
final class Assets {

	/**
	 * Hub directory, relative to the host plugin root.
	 */
	public const DIR = 'assets/hub';

	/**
	 * Absolute path of a hub file.
	 *
	 * @param string $file Path inside the hub directory.
	 * @return string
	 */
	public static function path( string $file ): string {
		return plugin_dir_path( Hub::plugin_file() ) . self::DIR . '/' . $file;
	}

	/**
	 * URL of a hub file.
	 *
	 * @param string $file Path inside the hub directory.
	 * @return string
	 */
	public static function url( string $file ): string {
		return plugins_url( self::DIR . '/' . $file, Hub::plugin_file() );
	}

	/**
	 * Whether the app build is present.
	 *
	 * @return bool
	 */
	public static function has_build(): bool {
		return file_exists( self::path( 'index.js' ) );
	}

	/**
	 * Script dependencies and version from index.asset.json.
	 *
	 * @return array{dependencies: array<int, string>, version: string}
	 */
	public static function manifest(): array {
		$file = self::path( 'index.asset.json' );
		$data = file_exists( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local bundled file.
		$data = is_array( $data ) ? $data : array();

		return array(
			'dependencies' => array_values( array_map( 'strval', (array) ( $data['dependencies'] ?? array() ) ) ),
			'version'      => isset( $data['version'] ) && is_scalar( $data['version'] ) ? (string) $data['version'] : Hub::VERSION,
		);
	}

	/**
	 * URL of the bundled icon of a plugin, or null when there is none (the
	 * app then draws a neutral fallback icon).
	 *
	 * @param string $slug Registry slug.
	 * @return string|null
	 */
	public static function icon_url( string $slug ): ?string {
		if ( ! preg_match( '/^[a-z0-9][a-z0-9-]*$/', $slug ) || ! file_exists( self::path( 'icons/' . $slug . '.svg' ) ) ) {
			return null;
		}

		return self::url( 'icons/' . $slug . '.svg' );
	}
}
