<?php
/**
 * Installed / active detection for registry plugins.
 *
 * Synced from lwplugins/admin-hub 1.0.0 by bin/sync.php. Do not edit
 * this copy: change the admin-hub repo and sync again.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin\Hub;

/**
 * Matches a registry slug to a get_plugins() entry by its directory and
 * tells whether it is active (is_plugin_active(), or the registry constant
 * for installs WordPress does not list, such as Composer or MU loaders).
 */
final class Detector {

	/**
	 * Result of get_plugins(): plugin file => header data.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $installed;

	/**
	 * Constructor.
	 *
	 * @param array<string, array<string, mixed>> $installed Result of get_plugins().
	 */
	public function __construct( array $installed ) {
		$this->installed = $installed;
	}

	/**
	 * Detector over the plugins WordPress knows about.
	 *
	 * @return self
	 */
	public static function from_wordpress(): self {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return new self( get_plugins() );
	}

	/**
	 * The get_plugins() key of a slug ("slug/slug.php" preferred), or null.
	 *
	 * @param string $slug Registry slug.
	 * @return string|null
	 */
	public function find_file( string $slug ): ?string {
		if ( ! preg_match( '/^[a-z0-9][a-z0-9-]*$/', $slug ) ) {
			return null;
		}

		$preferred = $slug . '/' . $slug . '.php';

		if ( isset( $this->installed[ $preferred ] ) ) {
			return $preferred;
		}

		foreach ( array_keys( $this->installed ) as $file ) {
			if ( dirname( (string) $file ) === $slug ) {
				return (string) $file;
			}
		}

		return null;
	}

	/**
	 * State of one registry plugin.
	 *
	 * @param string                $slug  Registry slug.
	 * @param array<string, string> $entry Normalized registry entry.
	 * @return array{status: string, version: string|null, file: string|null}
	 */
	public function detect( string $slug, array $entry ): array {
		$file     = $this->find_file( $slug );
		$constant = self::constant_value( $entry['constant'] ?? '' );
		$active   = ( null !== $file && is_plugin_active( $file ) ) || null !== $constant;

		$version = null;
		if ( null !== $file && isset( $this->installed[ $file ]['Version'] ) && is_scalar( $this->installed[ $file ]['Version'] ) ) {
			$version = (string) $this->installed[ $file ]['Version'];
		} elseif ( null !== $constant && '' !== $constant ) {
			$version = $constant;
		}

		if ( $active ) {
			$status = 'active';
		} else {
			$status = null !== $file ? 'inactive' : 'missing';
		}

		return array(
			'status'  => $status,
			'version' => '' === $version ? null : $version,
			'file'    => $file,
		);
	}

	/**
	 * Value of a registry constant when it is defined, else null.
	 *
	 * @param string $name Constant name.
	 * @return string|null
	 */
	private static function constant_value( string $name ): ?string {
		if ( ! preg_match( '/^[A-Z][A-Z0-9_]*$/', $name ) || ! defined( $name ) ) {
			return null;
		}

		$value = constant( $name );

		return is_scalar( $value ) ? (string) $value : '';
	}
}
