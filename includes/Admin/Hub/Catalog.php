<?php
/**
 * The rows the hub table shows.
 *
 * Synced from lwplugins/admin-hub 1.0.0 by bin/sync.php. Do not edit
 * this copy: change the admin-hub repo and sync again.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin\Hub;

/**
 * Joins the registry with the detected state into the REST row shape.
 */
final class Catalog {

	/**
	 * Normalized registry.
	 *
	 * @var array<string, array<string, string>>
	 */
	private array $registry;

	/**
	 * Installed / active detection.
	 *
	 * @var Detector
	 */
	private Detector $detector;

	/**
	 * Constructor.
	 *
	 * @param array<string, array<string, string>> $registry Normalized registry.
	 * @param Detector                             $detector Detection.
	 */
	public function __construct( array $registry, Detector $detector ) {
		$this->registry = $registry;
		$this->detector = $detector;
	}

	/**
	 * Catalog over the live registry and plugin list.
	 *
	 * @return self
	 */
	public static function from_wordpress(): self {
		return new self( Registry::get(), Detector::from_wordpress() );
	}

	/**
	 * Normalized registry entry of a slug, or null.
	 *
	 * @param string $slug Registry slug.
	 * @return array<string, string>|null
	 */
	public function entry( string $slug ): ?array {
		return $this->registry[ $slug ] ?? null;
	}

	/**
	 * Detection used by this catalog.
	 *
	 * @return Detector
	 */
	public function detector(): Detector {
		return $this->detector;
	}

	/**
	 * Every row, in registry order.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function rows(): array {
		$rows = array();

		foreach ( array_keys( $this->registry ) as $slug ) {
			$rows[] = $this->row( (string) $slug );
		}

		return $rows;
	}

	/**
	 * One row, or an empty array for an unknown slug.
	 *
	 * @param string $slug Registry slug.
	 * @return array<string, mixed>
	 */
	public function row( string $slug ): array {
		$entry = $this->entry( $slug );

		if ( null === $entry ) {
			return array();
		}

		$state  = $this->detector->detect( $slug, $entry );
		$active = 'active' === $state['status'];
		$page   = preg_match( '/^[a-z0-9_-]+$/', $entry['settings_page'] ) ? $entry['settings_page'] : '';
		$github = str_starts_with( $entry['github'], 'https://github.com/' ) ? $entry['github'] : '';

		return array(
			'slug'        => $slug,
			'name'        => $entry['name'],
			// The remote registry is English; the hub .po carries its texts.
			'description' => translate( $entry['description'], 'lw-cookie' ), // phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction,WordPress.WP.I18n.NonSingularStringLiteralText -- registry text; the literals are in RegistryFallback.
			'status'      => $state['status'],
			'version'     => $state['version'],
			'beta'        => 'beta' === $entry['status'],
			'color'       => preg_match( '/^#[0-9a-f]{3,8}$/i', $entry['icon_color'] ) ? $entry['icon_color'] : '',
			'iconUrl'     => Assets::icon_url( $slug ),
			'settingsUrl' => $active && '' !== $page ? admin_url( 'admin.php?page=' . $page ) : null,
			'githubUrl'   => '' !== $github ? $github : null,
			'canActivate' => 'inactive' === $state['status'] && null !== $state['file'] && current_user_can( 'activate_plugin', $state['file'] ),
		);
	}
}
