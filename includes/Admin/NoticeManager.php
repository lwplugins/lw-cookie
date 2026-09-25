<?php
/**
 * Keeps other plugins' and themes' admin notices off LW plugin screens.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin;

/**
 * On every screen under the shared "LW Plugins" menu, only notices from LW
 * plugins are shown. Every LW plugin carries its own copy of this class, so
 * the isolation works whichever LW plugins are active.
 *
 * Two layers:
 * 1. Just before WordPress prints the notice hooks, every callback that is
 *    not in the `LightweightPlugins\` namespace is removed from them. This
 *    catches notices whatever their markup.
 * 2. A stylesheet hides notice markup printed any other way (echoed from
 *    another hook, injected by script) unless it carries `lw-notice`.
 */
final class NoticeManager {

	/** Parent menu slug of every LW plugin screen. */
	private const PARENT = 'lw-plugins';

	/** Namespace prefix of LW plugin code. */
	private const OWN_NAMESPACE = 'LightweightPlugins\\';

	/** The hooks WordPress prints admin notices from. */
	private const HOOKS = [ 'admin_notices', 'all_admin_notices', 'network_admin_notices', 'user_admin_notices' ];

	/** Body class that scopes the fallback stylesheet. */
	private const BODY_CLASS = 'lw-plugins-admin-page';

	/**
	 * Whether init() has registered the hooks.
	 *
	 * @var bool
	 */
	private static bool $initialized = false;

	/**
	 * Called from `ParentPage::maybe_register()` while the admin menu is
	 * built, so on admin requests only. Every LW plugin screen triggers that
	 * call, hence the flag: the hooks are registered once.
	 */
	public static function init(): void {
		if ( self::$initialized ) {
			return;
		}

		self::$initialized = true;
		self::register();
	}

	/**
	 * Register the isolation hooks. Use init(), which runs this once.
	 */
	public static function register(): void {
		// in_admin_header runs in admin-header.php right before the notice hooks.
		add_action( 'in_admin_header', [ self::class, 'isolate' ], PHP_INT_MAX );
		add_action( 'admin_head', [ self::class, 'print_styles' ] );
		add_filter( 'admin_body_class', [ self::class, 'body_class' ] );
	}

	/**
	 * Whether the current admin page sits under the LW Plugins menu. Uses the
	 * menu slug, not the screen id: the screen id is derived from the parent
	 * menu's (translatable) title.
	 */
	public static function is_lw_page(): bool {
		$page = isset( $GLOBALS['plugin_page'] ) && is_string( $GLOBALS['plugin_page'] ) ? $GLOBALS['plugin_page'] : '';

		if ( '' === $page ) {
			return false;
		}

		return self::PARENT === $page || ( function_exists( 'get_admin_page_parent' ) && self::PARENT === get_admin_page_parent() );
	}

	/**
	 * Remove every non-LW callback from the notice hooks on LW pages.
	 */
	public static function isolate(): void {
		global $wp_filter;

		if ( ! self::is_lw_page() || ! is_array( $wp_filter ) ) {
			return;
		}

		foreach ( self::HOOKS as $hook ) {
			if ( ! isset( $wp_filter[ $hook ] ) || ! is_object( $wp_filter[ $hook ] ) || ! isset( $wp_filter[ $hook ]->callbacks ) ) {
				continue;
			}

			foreach ( (array) $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
				foreach ( (array) $callbacks as $callback ) {
					if ( isset( $callback['function'] ) && ! self::is_own( $callback['function'] ) ) {
						remove_action( $hook, $callback['function'], (int) $priority );
					}
				}
			}
		}
	}

	/**
	 * Whether a hook callback belongs to an LW plugin.
	 *
	 * @param mixed $callback Hook callback.
	 */
	public static function is_own( $callback ): bool {
		return str_starts_with( ltrim( self::callback_owner( $callback ), '\\' ), self::OWN_NAMESPACE );
	}

	/**
	 * Hide notice markup that did not come through the notice hooks.
	 */
	public static function print_styles(): void {
		if ( ! self::is_lw_page() ) {
			return;
		}

		$scope     = 'body.' . self::BODY_CLASS . ' #wpbody-content > ';
		$selectors = [];

		foreach ( [ '.notice', '.error', '.updated', '.update-nag', '#message' ] as $notice ) {
			$selectors[] = $scope . $notice . ':not(.lw-notice)';
			$selectors[] = $scope . '.wrap > ' . $notice . ':not(.lw-notice)';
		}

		echo '<style>' . implode( ',', $selectors ) . '{display:none!important}</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed selectors built above.
	}

	/**
	 * Add the class that scopes the fallback stylesheet on LW pages.
	 *
	 * @param string $classes Space-separated admin body classes.
	 */
	public static function body_class( $classes ): string {
		$classes = (string) $classes;

		if ( ! self::is_lw_page() || str_contains( ' ' . $classes . ' ', ' ' . self::BODY_CLASS . ' ' ) ) {
			return $classes;
		}

		return trim( $classes . ' ' . self::BODY_CLASS );
	}

	/**
	 * Fully qualified name of the function or class behind a callback, or ''
	 * when it cannot be told.
	 *
	 * @param mixed $callback Hook callback.
	 */
	private static function callback_owner( $callback ): string {
		if ( is_string( $callback ) ) {
			return explode( '::', $callback )[0];
		}

		if ( is_array( $callback ) && isset( $callback[0] ) ) {
			return is_object( $callback[0] ) ? get_class( $callback[0] ) : (string) $callback[0];
		}

		if ( $callback instanceof \Closure ) {
			return self::closure_owner( new \ReflectionFunction( $callback ) );
		}

		return is_object( $callback ) ? get_class( $callback ) : '';
	}

	/**
	 * Where a closure was written. Its bound scope can be rebound, where it
	 * was declared cannot. PHP < 8.4 reports the namespace; PHP 8.4+ reports
	 * none but names the closure `{closure:Owner\Class::method():line}`.
	 *
	 * @param \ReflectionFunction $closure Closure reflection.
	 */
	private static function closure_owner( \ReflectionFunction $closure ): string {
		if ( $closure->inNamespace() ) {
			return $closure->getNamespaceName() . '\\';
		}

		$name = $closure->getName();

		return str_starts_with( $name, '{closure:' ) ? substr( $name, 9 ) : '';
	}
}
