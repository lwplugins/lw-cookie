<?php
/**
 * LW Plugins Parent Page.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin;

/**
 * Handles the LW Plugins parent menu page.
 */
final class ParentPage {

	/**
	 * Parent menu slug.
	 */
	public const SLUG = 'lw-plugins';

	/**
	 * Remote registry URL (raw GitHub).
	 */
	private const REGISTRY_URL = 'https://raw.githubusercontent.com/lwplugins/registry/main/plugins.json';

	/**
	 * Transient cache key and TTL.
	 */
	private const CACHE_KEY = 'lw_plugins_registry';
	private const CACHE_TTL = 43200; // 12 hours in seconds.

	/**
	 * Get all LW plugins registry (remote with local fallback).
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function get_plugins_registry(): array {
		$cached = get_transient( self::CACHE_KEY );

		if ( is_array( $cached ) && ! empty( $cached ) ) {
			return $cached;
		}

		$remote = self::fetch_remote_registry();

		if ( $remote ) {
			set_transient( self::CACHE_KEY, $remote, self::CACHE_TTL );
			return $remote;
		}

		return self::get_local_fallback();
	}

	/**
	 * Fetch plugin registry from GitHub.
	 *
	 * @return array<string, array<string, string>>|null
	 */
	private static function fetch_remote_registry(): ?array {
		$response = wp_remote_get( self::REGISTRY_URL, [ 'timeout' => 5 ] );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		return is_array( $data ) && ! empty( $data ) ? $data : null;
	}

	/**
	 * Local fallback when remote is unavailable.
	 *
	 * @return array<string, array<string, string>>
	 */
	private static function get_local_fallback(): array {
		return [
			'lw-cookie' => [
				'name'          => 'LW Cookie',
				'description'   => 'GDPR-compliant cookie consent banner.',
				'icon'          => 'dashicons-shield',
				'icon_color'    => '#00a32a',
				'constant'      => 'LW_COOKIE_VERSION',
				'settings_page' => 'lw-cookie',
				'github'        => 'https://github.com/lwplugins/lw-cookie',
			],
		];
	}

	/**
	 * Register the parent menu if not exists.
	 *
	 * @return void
	 */
	public static function maybe_register(): void {
		NoticeManager::init();

		global $admin_page_hooks;

		if ( ! empty( $admin_page_hooks[ self::SLUG ] ) ) {
			return;
		}

		add_menu_page(
			__( 'LW Plugins', 'lw-cookie' ),
			__( 'LW Plugins', 'lw-cookie' ),
			'manage_options',
			self::SLUG,
			[ self::class, 'render' ],
			'dashicons-superhero-alt',
			80
		);
	}

	/**
	 * Render the parent page.
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		?>
		<div class="wrap lw-plugins-overview">
			<h1><?php esc_html_e( 'LW Plugins', 'lw-cookie' ); ?></h1>
			<p><?php esc_html_e( 'Lightweight plugins for WordPress - minimal footprint, maximum impact.', 'lw-cookie' ); ?></p>

			<div class="lw-plugins-cards" style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 20px;">
				<?php self::render_all_plugin_cards(); ?>

				<?php
				/**
				 * Add additional plugin cards to the LW Plugins overview page.
				 *
				 * @since 1.0.0
				 */
				do_action( 'lw_plugins_overview_cards' );
				?>
			</div>

			<div class="lw-plugins-footer" style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ccd0d4;">
				<p>
					<a href="https://github.com/lwplugins" target="_blank">GitHub</a> |
					<a href="https://lwplugins.com" target="_blank">Website</a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render all plugin cards from registry.
	 *
	 * @return void
	 */
	private static function render_all_plugin_cards(): void {
		$plugins = self::get_plugins_registry();

		foreach ( $plugins as $slug => $plugin ) {
			self::render_plugin_card( $slug, $plugin );
		}
	}

	/**
	 * Render a single plugin card.
	 *
	 * @param string                                                                                                                              $slug   Plugin slug.
	 * @param array{name: string, description: string, icon: string, icon_color: string, constant: string, settings_page: string, github: string} $plugin Plugin data.
	 * @return void
	 */
	private static function render_plugin_card( string $slug, array $plugin ): void {
		$is_active = defined( $plugin['constant'] );
		$svg_path  = WP_PLUGIN_DIR . '/' . $slug . '/assets/img/title-icon.svg';
		$svg_url   = $is_active && file_exists( $svg_path )
			? plugins_url( $slug . '/assets/img/title-icon.svg' )
			: ( $plugin['icon_svg'] ?? '' );
		?>
		<div class="lw-plugin-card" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; width: 300px;">
			<h2 style="margin-top: 0;">
				<?php if ( $svg_url ) : ?>
					<img src="<?php echo esc_url( $svg_url ); ?>" alt="" style="width: 20px; height: 20px; vertical-align: middle; margin-right: 4px;" />
				<?php else : ?>
					<span class="dashicons <?php echo esc_attr( $plugin['icon'] ); ?>" style="color: <?php echo esc_attr( $plugin['icon_color'] ); ?>;"></span>
				<?php endif; ?>
				<?php echo esc_html( $plugin['name'] ); ?>
				<?php if ( $is_active ) : ?>
					<span style="display: inline-block; background: #00a32a; color: #fff; font-size: 11px; padding: 2px 6px; border-radius: 3px; margin-left: 8px; vertical-align: middle;"><?php esc_html_e( 'Active', 'lw-cookie' ); ?></span>
				<?php endif; ?>
			</h2>
			<p><?php echo esc_html( $plugin['description'] ); ?></p>
			<p>
				<?php if ( $is_active ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $plugin['settings_page'] ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Settings', 'lw-cookie' ); ?>
					</a>
				<?php else : ?>
					<a href="<?php echo esc_url( $plugin['github'] ); ?>" class="button" target="_blank">
						<?php esc_html_e( 'Get Plugin', 'lw-cookie' ); ?>
					</a>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}
}
