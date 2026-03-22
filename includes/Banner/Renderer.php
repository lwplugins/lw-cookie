<?php
/**
 * Banner Renderer class.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Banner;

use LightweightPlugins\Cookie\Options;

/**
 * Renders the cookie consent banner.
 *
 * In v2.0 the banner is ALWAYS rendered with a `lw-cookie-hidden`
 * class. The client-side guard.js toggles visibility based on the
 * consent cookie — this makes the output fully cacheable.
 */
final class Renderer {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_footer', [ $this, 'render_banner' ], 100 );
		add_action( 'wp_footer', [ $this, 'render_floating_button' ], 100 );
	}

	/**
	 * Render the cookie banner and preferences modal.
	 *
	 * @return void
	 */
	public function render_banner(): void {
		$this->output_preferences_modal();
		$this->output_banner_html();
	}

	/**
	 * Render the floating button (always hidden by default, guard.js shows it).
	 *
	 * @return void
	 */
	public function render_floating_button(): void {
		if ( ! Options::get( 'show_floating_button' ) ) {
			return;
		}

		$position = Options::get( 'floating_button_pos' );
		?>
		<button type="button"
			id="lw-cookie-floating-btn"
			class="lw-cookie-floating-btn lw-cookie-floating-<?php echo esc_attr( $position ); ?> lw-cookie-hidden"
			aria-label="<?php esc_attr_e( 'Cookie Settings', 'lw-cookie' ); ?>"
			data-lw-cookie-open-preferences>
			<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 2s3 2 7 2c0 6-3 11-7 14C6 15 3 10 3 4c4 0 7-2 7-2z"/></svg>
		</button>
		<?php
	}

	/**
	 * Output the banner HTML — always rendered, hidden by default.
	 *
	 * @return void
	 */
	private function output_banner_html(): void {
		$position = Options::get( 'banner_position' );
		$layout   = Options::get( 'banner_layout' );
		$classes  = sprintf(
			'lw-cookie-notice lw-cookie-pos-%s lw-cookie-layout-%s lw-cookie-hidden',
			$position,
			$layout
		);

		$privacy_page_id = (int) Options::get( 'privacy_policy_page' );
		$privacy_link    = $privacy_page_id ? get_permalink( $privacy_page_id ) : '';
		?>
		<div id="lw-cookie-notice" class="<?php echo esc_attr( $classes ); ?>" role="dialog" aria-modal="true" aria-labelledby="lw-cookie-title">
			<div class="lw-cookie-notice-inner">
				<div class="lw-cookie-content">
					<h2 id="lw-cookie-title" class="lw-cookie-title">
						<?php echo esc_html( Options::get( 'banner_title' ) ); ?>
					</h2>
					<p class="lw-cookie-message">
						<?php echo esc_html( Options::get( 'banner_message' ) ); ?>
						<?php if ( $privacy_link ) : ?>
							<a href="<?php echo esc_url( $privacy_link ); ?>" target="_blank" rel="noopener">
								<?php esc_html_e( 'Privacy Policy', 'lw-cookie' ); ?>
							</a>
						<?php endif; ?>
					</p>
				</div>
				<div class="lw-cookie-actions">
					<button type="button" class="lw-cookie-btn lw-cookie-btn-secondary" data-lw-cookie-customize>
						<?php echo esc_html( Options::get( 'btn_customize' ) ); ?>
					</button>
					<button type="button" class="lw-cookie-btn lw-cookie-btn-outline" data-lw-cookie-reject>
						<?php echo esc_html( Options::get( 'btn_reject_all' ) ); ?>
					</button>
					<button type="button" class="lw-cookie-btn lw-cookie-btn-primary" data-lw-cookie-accept>
						<?php echo esc_html( Options::get( 'btn_accept_all' ) ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Output the preferences modal.
	 *
	 * @return void
	 */
	private function output_preferences_modal(): void {
		$categories = Options::get_categories();
		?>
		<div id="lw-cookie-preferences" class="lw-cookie-modal" role="dialog" aria-modal="true" aria-labelledby="lw-cookie-prefs-title" style="display:none;">
			<div class="lw-cookie-modal-overlay" data-lw-cookie-close-modal></div>
			<div class="lw-cookie-modal-content">
				<button type="button" class="lw-cookie-modal-close" data-lw-cookie-close-modal aria-label="<?php esc_attr_e( 'Close', 'lw-cookie' ); ?>">
					<span aria-hidden="true">&times;</span>
				</button>

				<h2 id="lw-cookie-prefs-title" class="lw-cookie-modal-title">
					<?php esc_html_e( 'Cookie Preferences', 'lw-cookie' ); ?>
				</h2>

				<div class="lw-cookie-categories">
					<?php foreach ( $categories as $key => $category ) : ?>
						<div class="lw-cookie-category">
							<div class="lw-cookie-category-header">
								<label class="lw-cookie-category-label">
									<input type="checkbox"
										name="lw_cookie_cat_<?php echo esc_attr( $key ); ?>"
										data-category="<?php echo esc_attr( $key ); ?>"
										<?php checked( $category['required'] ); ?>
										<?php disabled( $category['required'] ); ?>>
									<span class="lw-cookie-category-name"><?php echo esc_html( $category['name'] ); ?></span>
									<?php if ( $category['required'] ) : ?>
										<span class="lw-cookie-required"><?php esc_html_e( '(Required)', 'lw-cookie' ); ?></span>
									<?php endif; ?>
								</label>
							</div>
							<p class="lw-cookie-category-desc">
								<?php echo esc_html( $category['description'] ); ?>
							</p>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="lw-cookie-modal-actions">
					<button type="button" class="lw-cookie-btn lw-cookie-btn-primary" data-lw-cookie-save>
						<?php echo esc_html( Options::get( 'btn_save' ) ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}
}
