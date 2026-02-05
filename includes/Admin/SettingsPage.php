<?php
/**
 * Settings Page class.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin;

use LightweightPlugins\Cookie\Admin\Settings\TabInterface;
use LightweightPlugins\Cookie\Admin\Settings\TabGeneral;
use LightweightPlugins\Cookie\Admin\Settings\TabAppearance;
use LightweightPlugins\Cookie\Admin\Settings\TabCategories;
use LightweightPlugins\Cookie\Admin\Settings\TabTexts;
use LightweightPlugins\Cookie\Admin\Settings\TabCookies;
use LightweightPlugins\Cookie\Admin\Settings\TabAdvanced;
use LightweightPlugins\Cookie\Options;

/**
 * Handles the plugin settings page.
 */
final class SettingsPage {

	/**
	 * Settings page slug.
	 */
	public const SLUG = 'lw-cookie';

	/**
	 * Settings group.
	 */
	private const SETTINGS_GROUP = 'lw_cookie_settings';

	/**
	 * Registered tabs.
	 *
	 * @var array<TabInterface>
	 */
	private array $tabs = [];

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_tabs();

		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Register settings tabs.
	 *
	 * @return void
	 */
	private function register_tabs(): void {
		$this->tabs = [
			new TabGeneral(),
			new TabAppearance(),
			new TabCategories(),
			new TabTexts(),
			new TabCookies(),
			new TabAdvanced(),
		];
	}

	/**
	 * Add menu page.
	 *
	 * @return void
	 */
	public function add_menu_page(): void {
		ParentPage::maybe_register();

		add_submenu_page(
			ParentPage::SLUG,
			__( 'Cookie Consent', 'lw-cookie' ),
			__( 'Cookie', 'lw-cookie' ),
			'manage_options',
			self::SLUG,
			[ $this, 'render' ]
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		$valid_hooks = [
			'toplevel_page_' . ParentPage::SLUG,
			ParentPage::SLUG . '_page_' . self::SLUG,
		];

		if ( ! in_array( $hook, $valid_hooks, true ) ) {
			return;
		}

		wp_enqueue_style(
			'lw-cookie-admin',
			LW_COOKIE_URL . 'assets/css/admin.css',
			[],
			LW_COOKIE_VERSION
		);

		wp_enqueue_script(
			'lw-cookie-admin',
			LW_COOKIE_URL . 'assets/js/admin.js',
			[ 'jquery', 'wp-color-picker' ],
			LW_COOKIE_VERSION,
			true
		);

		wp_enqueue_style( 'wp-color-picker' );
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			self::SETTINGS_GROUP,
			Options::OPTION_NAME,
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize_settings' ],
				'default'           => Options::get_defaults(),
			]
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array<string, mixed> $input Input values.
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( array $input ): array {
		$defaults  = Options::get_defaults();
		$sanitized = [];

		foreach ( $defaults as $key => $default ) {
			if ( is_bool( $default ) ) {
				$sanitized[ $key ] = ! empty( $input[ $key ] );
			} elseif ( is_int( $default ) ) {
				$sanitized[ $key ] = isset( $input[ $key ] ) ? absint( $input[ $key ] ) : $default;
			} elseif ( str_contains( $key, 'color' ) ) {
				$sanitized[ $key ] = isset( $input[ $key ] ) ? sanitize_hex_color( $input[ $key ] ) : $default;
			} else {
				$sanitized[ $key ] = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : $default;
			}
		}

		// Handle declared cookies array separately.
		if ( isset( $input['declared_cookies'] ) && is_array( $input['declared_cookies'] ) ) {
			$sanitized['declared_cookies'] = $this->sanitize_cookies( $input['declared_cookies'] );
		}

		return $sanitized;
	}

	/**
	 * Sanitize declared cookies array.
	 *
	 * @param array $cookies Raw cookies data.
	 * @return array
	 */
	private function sanitize_cookies( array $cookies ): array {
		$sanitized        = [];
		$valid_categories = [ 'necessary', 'functional', 'analytics', 'marketing' ];
		$valid_types      = [ 'session', 'persistent' ];

		foreach ( $cookies as $cookie ) {
			if ( ! is_array( $cookie ) || empty( $cookie['name'] ) ) {
				continue;
			}

			$sanitized[] = [
				'name'     => sanitize_text_field( $cookie['name'] ?? '' ),
				'provider' => sanitize_text_field( $cookie['provider'] ?? '' ),
				'purpose'  => sanitize_text_field( $cookie['purpose'] ?? '' ),
				'duration' => sanitize_text_field( $cookie['duration'] ?? '' ),
				'category' => in_array( $cookie['category'] ?? '', $valid_categories, true )
					? $cookie['category']
					: 'necessary',
				'type'     => in_array( $cookie['type'] ?? '', $valid_types, true )
					? $cookie['type']
					: 'persistent',
			];
		}

		return $sanitized;
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form method="post" action="options.php">
				<?php settings_fields( self::SETTINGS_GROUP ); ?>

				<div class="lw-cookie-settings">
					<?php $this->render_tabs_nav(); ?>

					<div class="lw-cookie-tab-content">
						<?php $this->render_tabs_content(); ?>
						<?php submit_button(); ?>
					</div>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Render tabs navigation.
	 *
	 * @return void
	 */
	private function render_tabs_nav(): void {
		?>
		<ul class="lw-cookie-tabs">
			<?php foreach ( $this->tabs as $index => $tab ) : ?>
				<li>
					<a href="#<?php echo esc_attr( $tab->get_slug() ); ?>" <?php echo 0 === $index ? 'class="active"' : ''; ?>>
						<span class="dashicons <?php echo esc_attr( $tab->get_icon() ); ?>"></span>
						<?php echo esc_html( $tab->get_label() ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	/**
	 * Render tabs content.
	 *
	 * @return void
	 */
	private function render_tabs_content(): void {
		foreach ( $this->tabs as $index => $tab ) {
			$active_class = 0 === $index ? ' active' : '';
			printf(
				'<div id="tab-%s" class="lw-cookie-tab-panel%s">',
				esc_attr( $tab->get_slug() ),
				esc_attr( $active_class )
			);
			$tab->render();
			echo '</div>';
		}
	}
}
