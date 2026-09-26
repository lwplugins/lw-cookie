<?php
/**
 * The "LW Plugins" top-level admin page.
 *
 * Synced from lwplugins/admin-hub 1.0.0 by bin/sync.php. Do not edit
 * this copy: change the admin-hub repo and sync again.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin\Hub;

/**
 * Registers the shared top-level menu, mounts the React app and loads its
 * assets. Only the winning hub copy calls register().
 */
final class Page {

	/**
	 * Top-level menu slug shared by every LW plugin.
	 */
	public const SLUG = 'lw-plugins';

	/**
	 * Capability to see the page.
	 */
	public const CAPABILITY = 'manage_options';

	/**
	 * Script and style handle.
	 */
	private const HANDLE = 'lw-plugins-hub';

	/**
	 * Body class of the hub screen.
	 */
	private const BODY_CLASS = 'lw-hub-screen';

	/**
	 * Hook suffix returned by add_menu_page().
	 *
	 * @var string
	 */
	private static string $hook_suffix = '';

	/**
	 * Add the menu page and the screen hooks (once).
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( '' !== self::$hook_suffix ) {
			return;
		}

		self::$hook_suffix = add_menu_page(
			__( 'LW Plugins', 'lw-cookie' ),
			__( 'LW Plugins', 'lw-cookie' ),
			self::CAPABILITY,
			self::SLUG,
			array( self::class, 'render' ),
			'dashicons-superhero-alt',
			80
		);

		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ) );
		add_filter( 'admin_body_class', array( self::class, 'body_class' ) );
	}

	/**
	 * Load the app on the hub screen.
	 *
	 * @param string $hook Current admin page hook suffix.
	 * @return void
	 */
	public static function enqueue( $hook ): void {
		if ( '' === self::$hook_suffix || self::$hook_suffix !== $hook || ! Assets::has_build() ) {
			return;
		}

		$manifest = Assets::manifest();

		wp_enqueue_script( self::HANDLE, Assets::url( 'index.js' ), $manifest['dependencies'], $manifest['version'], true );
		wp_set_script_translations( self::HANDLE, 'lw-cookie', plugin_dir_path( Hub::plugin_file() ) . 'languages' );
		wp_enqueue_style( self::HANDLE, Assets::url( 'index.css' ), array( 'wp-components' ), $manifest['version'] );
		wp_style_add_data( self::HANDLE, 'rtl', 'replace' );

		wp_add_inline_script(
			self::HANDLE,
			'window.lwPluginsHub = ' . wp_json_encode(
				array(
					'version'    => Hub::VERSION,
					'restPath'   => '/' . RestController::NAMESPACE . RestController::BASE,
					'pluginsUrl' => admin_url( 'plugins.php' ),
				)
			) . ';',
			'before'
		);
	}

	/**
	 * Mark the hub screen body for the app styles.
	 *
	 * @param mixed $classes Space-separated body classes.
	 * @return string
	 */
	public static function body_class( $classes ): string {
		$classes = (string) $classes;
		$screen  = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( '' === self::$hook_suffix || ! $screen || $screen->id !== self::$hook_suffix ) {
			return $classes;
		}

		return $classes . ' ' . self::BODY_CLASS;
	}

	/**
	 * Render the mount point, or a notice when the build is missing. The
	 * notice carries lw-notice so the LW notice isolation keeps it.
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		if ( ! Assets::has_build() ) {
			printf(
				'<div class="wrap"><h1>%s</h1><div class="notice notice-error lw-notice"><p>%s</p></div></div>',
				esc_html__( 'LW Plugins', 'lw-cookie' ),
				esc_html__( 'The LW Plugins page files are missing. Re-install the plugin from a release ZIP.', 'lw-cookie' )
			);
			return;
		}

		echo '<div id="lw-plugins-hub-root" class="lw-hub-root"></div>';
	}

	/**
	 * Forget the registration. Tests only.
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$hook_suffix = '';
	}
}
