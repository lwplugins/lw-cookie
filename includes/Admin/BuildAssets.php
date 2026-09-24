<?php
/**
 * Enqueues the React build entries.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin;

/**
 * Loads a wp-scripts build entry (build/{entry}.js, .css, .asset.php).
 */
final class BuildAssets {

	/**
	 * Whether the entry's script was built.
	 *
	 * @param string $entry Entry name.
	 * @return bool
	 */
	public static function exists( string $entry ): bool {
		return file_exists( self::path( $entry, 'js' ) );
	}

	/**
	 * Enqueue the entry's script (footer, with translations) and its
	 * stylesheet when present.
	 *
	 * @param string $entry  Entry name.
	 * @param string $handle Script and style handle.
	 * @return bool False when the build is missing.
	 */
	public static function enqueue( string $entry, string $handle ): bool {
		if ( ! self::exists( $entry ) ) {
			return false;
		}

		$asset = self::asset( $entry );

		wp_enqueue_script( $handle, self::url( $entry, 'js' ), $asset['dependencies'], $asset['version'], true );
		wp_set_script_translations( $handle, 'lw-cookie', LW_COOKIE_PATH . 'languages' );

		$css = self::path( $entry, 'css' );
		if ( file_exists( $css ) ) {
			wp_enqueue_style( $handle, self::url( $entry, 'css' ), [ 'wp-components' ], (string) filemtime( $css ) );
		}

		return true;
	}

	/**
	 * Dependencies and version from the generated asset file.
	 *
	 * @param string $entry Entry name.
	 * @return array{dependencies: array<int, string>, version: string}
	 */
	private static function asset( string $entry ): array {
		$file  = LW_COOKIE_PATH . 'build/' . $entry . '.asset.php';
		$asset = file_exists( $file ) ? include $file : [];
		$asset = is_array( $asset ) ? $asset : [];

		return [
			'dependencies' => array_values( array_map( 'strval', (array) ( $asset['dependencies'] ?? [] ) ) ),
			'version'      => (string) ( $asset['version'] ?? filemtime( self::path( $entry, 'js' ) ) ),
		];
	}

	/**
	 * Absolute path of a build file.
	 *
	 * @param string $entry Entry name.
	 * @param string $ext   Extension.
	 * @return string
	 */
	private static function path( string $entry, string $ext ): string {
		return LW_COOKIE_PATH . 'build/' . $entry . '.' . $ext;
	}

	/**
	 * URL of a build file.
	 *
	 * @param string $entry Entry name.
	 * @param string $ext   Extension.
	 * @return string
	 */
	private static function url( string $entry, string $ext ): string {
		return LW_COOKIE_URL . 'build/' . $entry . '.' . $ext;
	}
}
