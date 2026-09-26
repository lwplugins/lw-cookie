<?php
/**
 * LW Plugins hub bootstrap and version negotiation.
 *
 * Synced from lwplugins/admin-hub 1.0.0 by bin/sync.php. Do not edit
 * this copy: change the admin-hub repo and sync again.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin\Hub;

/**
 * Every LW plugin ships its own copy of the hub under its own namespace. Each
 * copy registers itself as a candidate on the shared
 * `lw_plugins_hub_candidates` filter; the copy with the highest VERSION
 * (ties: lowest class name) owns the "LW Plugins" page and its REST routes.
 * The others stay idle, so an old copy can never render over a newer one.
 */
final class Hub {

	/**
	 * Hub version. Bumped in the admin-hub repo only; bin/sync.php copies it.
	 */
	public const VERSION = '1.0.0';

	/**
	 * Shared candidate filter.
	 */
	public const CANDIDATES_FILTER = 'lw_plugins_hub_candidates';

	/**
	 * Main file of the plugin that carries this copy.
	 *
	 * @var string
	 */
	private static string $plugin_file = '';

	/**
	 * Cached negotiation result for this request.
	 *
	 * @var bool|null
	 */
	private static ?bool $winner = null;

	/**
	 * Register this copy. Call once, early (plugin bootstrap), on every
	 * request: REST requests need it too.
	 *
	 * @param string $plugin_file Main plugin file (__FILE__ of the plugin).
	 * @return void
	 */
	public static function init( string $plugin_file ): void {
		if ( '' !== self::$plugin_file ) {
			return;
		}

		self::$plugin_file = $plugin_file;

		add_filter( self::CANDIDATES_FILTER, array( self::class, 'add_candidate' ) );
		// Before every legacy ParentPage::maybe_register() (admin_menu, 10+):
		// those skip when the lw-plugins menu already exists.
		add_action( 'admin_menu', array( self::class, 'on_admin_menu' ), 5 );
		add_action( 'rest_api_init', array( self::class, 'on_rest_api_init' ) );
	}

	/**
	 * Main file of the plugin that carries this copy.
	 *
	 * @return string
	 */
	public static function plugin_file(): string {
		return self::$plugin_file;
	}

	/**
	 * Add this copy to the candidate list.
	 *
	 * @param mixed $candidates Candidates collected so far.
	 * @return array<int, mixed>
	 */
	public static function add_candidate( $candidates ): array {
		$candidates   = is_array( $candidates ) ? array_values( $candidates ) : array();
		$candidates[] = array(
			'version' => self::VERSION,
			'class'   => self::class,
			'file'    => self::$plugin_file,
		);

		return $candidates;
	}

	/**
	 * Whether this copy won the negotiation.
	 *
	 * @return bool
	 */
	public static function is_winner(): bool {
		if ( null === self::$winner ) {
			$winner       = Negotiator::winner( (array) apply_filters( 'lw_plugins_hub_candidates', array() ) );
			self::$winner = null !== $winner && self::class === $winner['class'];
		}

		return self::$winner;
	}

	/**
	 * Register the page when this copy is the winner.
	 *
	 * @return void
	 */
	public static function on_admin_menu(): void {
		if ( self::is_winner() ) {
			Page::register();
		}
	}

	/**
	 * Register the REST routes when this copy is the winner.
	 *
	 * @return void
	 */
	public static function on_rest_api_init(): void {
		if ( self::is_winner() ) {
			( new RestController() )->register_routes();
		}
	}

	/**
	 * Register the page from a legacy caller (ParentPage shim) when no copy
	 * has done it yet, e.g. when the shim runs outside the normal hook order.
	 *
	 * @return void
	 */
	public static function ensure_menu(): void {
		global $admin_page_hooks;

		if ( '' !== self::$plugin_file && empty( $admin_page_hooks[ Page::SLUG ] ) ) {
			Page::register();
		}
	}

	/**
	 * Reset the cached state. Tests only.
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$plugin_file = '';
		self::$winner      = null;
		Page::reset();
	}
}
