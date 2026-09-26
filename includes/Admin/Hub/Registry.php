<?php
/**
 * LW plugin registry loader.
 *
 * Synced from lwplugins/admin-hub 1.0.0 by bin/sync.php. Do not edit
 * this copy: change the admin-hub repo and sync again.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin\Hub;

/**
 * Loads the registry (github.com/lwplugins/registry plugins.json), caches the
 * raw JSON for 12 hours in the transient every legacy ParentPage copy also
 * uses, and falls back to the bundled list when the remote is unreachable.
 * Entries are normalized so a malformed remote record cannot break the page.
 */
final class Registry {

	/**
	 * Remote registry URL (raw GitHub).
	 */
	public const REMOTE_URL = 'https://raw.githubusercontent.com/lwplugins/registry/main/plugins.json';

	/**
	 * Transient key, shared with the legacy ParentPage copies.
	 */
	public const CACHE_KEY = 'lw_plugins_registry';

	/**
	 * Cache lifetime: 12 hours.
	 */
	public const CACHE_TTL = 43200;

	/**
	 * Registry filter for site-specific changes.
	 */
	public const FILTER = 'lw_plugins_hub_registry';

	/**
	 * Keys every normalized entry carries.
	 */
	private const FIELDS = array( 'name', 'description', 'icon_color', 'constant', 'settings_page', 'github', 'status' );

	/**
	 * The normalized registry, keyed by plugin slug.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function get(): array {
		$raw = get_transient( self::CACHE_KEY );

		if ( ! is_array( $raw ) || empty( $raw ) ) {
			$raw = self::fetch_remote();

			if ( null !== $raw ) {
				set_transient( self::CACHE_KEY, $raw, self::CACHE_TTL );
			}
		}

		$fallback = RegistryFallback::get();
		$list     = self::merge( is_array( $raw ) ? $raw : array(), $fallback );

		return (array) apply_filters( 'lw_plugins_hub_registry', $list );
	}

	/**
	 * Normalize the remote list, or the fallback when the remote gave nothing
	 * usable. A remote entry takes missing fields from the bundled entry of
	 * the same slug. Entries with status "hidden" are left out.
	 *
	 * @param array<mixed>                         $remote   Decoded remote JSON.
	 * @param array<string, array<string, string>> $fallback Bundled list.
	 * @return array<string, array<string, string>>
	 */
	public static function merge( array $remote, array $fallback ): array {
		$list = self::normalize_all( $remote, $fallback );

		return empty( $list ) ? self::normalize_all( $fallback, array() ) : $list;
	}

	/**
	 * Normalize every entry of a list.
	 *
	 * @param array<mixed>                         $entries  Raw entries.
	 * @param array<string, array<string, string>> $defaults Per-slug defaults.
	 * @return array<string, array<string, string>>
	 */
	private static function normalize_all( array $entries, array $defaults ): array {
		$list = array();

		foreach ( $entries as $slug => $entry ) {
			if ( ! is_string( $slug ) || ! preg_match( '/^[a-z0-9][a-z0-9-]*$/', $slug ) || ! is_array( $entry ) ) {
				continue;
			}

			$normalized = self::normalize( $slug, $entry, $defaults[ $slug ] ?? array() );

			if ( null !== $normalized ) {
				$list[ $slug ] = $normalized;
			}
		}

		return $list;
	}

	/**
	 * One entry with every field present and typed, or null when hidden.
	 *
	 * @param string               $slug     Plugin slug.
	 * @param array<mixed>         $entry    Raw entry.
	 * @param array<string, mixed> $defaults Bundled entry of the same slug.
	 * @return array<string, string>|null
	 */
	private static function normalize( string $slug, array $entry, array $defaults ): ?array {
		$out = array();

		foreach ( self::FIELDS as $field ) {
			$value         = $entry[ $field ] ?? null;
			$value         = is_scalar( $value ) && '' !== trim( (string) $value ) ? $value : ( $defaults[ $field ] ?? '' );
			$out[ $field ] = is_scalar( $value ) ? trim( (string) $value ) : '';
		}

		if ( 'hidden' === $out['status'] ) {
			return null;
		}

		$out['name'] = '' === $out['name'] ? $slug : $out['name'];

		return $out;
	}

	/**
	 * Fetch and decode the remote registry.
	 *
	 * @return array<mixed>|null
	 */
	private static function fetch_remote(): ?array {
		$response = wp_remote_get( self::REMOTE_URL, array( 'timeout' => 5 ) );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		return is_array( $data ) && ! empty( $data ) ? $data : null;
	}
}
