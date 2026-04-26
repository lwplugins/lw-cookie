<?php
/**
 * Banner Renderer class.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Banner;

use LightweightPlugins\Cookie\I18n\Strings;
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

		if ( 'box' === $layout ) {
			$alignment = 'left' === Options::get( 'banner_box_alignment' ) ? 'left' : 'right';
			$classes  .= ' lw-cookie-box-align-' . $alignment;
		}

		$privacy_page_id = (int) Options::get( 'privacy_policy_page' );
		$privacy_link    = $privacy_page_id ? get_permalink( $privacy_page_id ) : '';
		?>
		<div id="lw-cookie-notice" class="<?php echo esc_attr( $classes ); ?>" role="dialog" aria-modal="true" aria-labelledby="lw-cookie-title">
			<div class="lw-cookie-notice-inner">
				<div class="lw-cookie-content">
					<h2 id="lw-cookie-title" class="lw-cookie-title">
						<?php echo esc_html( Strings::get( 'banner_title' ) ); ?>
					</h2>
					<p class="lw-cookie-message">
						<?php echo esc_html( Strings::get( 'banner_message' ) ); ?>
						<?php if ( $privacy_link ) : ?>
							<a href="<?php echo esc_url( $privacy_link ); ?>" target="_blank" rel="noopener">
								<?php echo esc_html( Strings::get_or_default( 'link_privacy_policy', __( 'Privacy Policy', 'lw-cookie' ) ) ); ?>
							</a>
						<?php endif; ?>
					</p>
				</div>
				<div class="lw-cookie-actions">
					<button type="button" class="lw-cookie-btn lw-cookie-btn-secondary" data-lw-cookie-customize>
						<?php echo esc_html( Strings::get( 'btn_customize' ) ); ?>
					</button>
					<button type="button" class="lw-cookie-btn lw-cookie-btn-outline" data-lw-cookie-reject>
						<?php echo esc_html( Strings::get( 'btn_reject_all' ) ); ?>
					</button>
					<button type="button" class="lw-cookie-btn lw-cookie-btn-primary" data-lw-cookie-accept>
						<?php echo esc_html( Strings::get( 'btn_accept_all' ) ); ?>
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
		$categories     = Strings::get_categories();
		$cookies_by_cat = Strings::get_cookies_by_category();
		?>
		<div id="lw-cookie-preferences" class="lw-cookie-modal" role="dialog" aria-modal="true" aria-labelledby="lw-cookie-prefs-title" style="display:none;">
			<div class="lw-cookie-modal-overlay" data-lw-cookie-close-modal></div>
			<div class="lw-cookie-modal-content">
				<button type="button" class="lw-cookie-modal-close" data-lw-cookie-close-modal aria-label="<?php esc_attr_e( 'Close', 'lw-cookie' ); ?>">
					<span aria-hidden="true">&times;</span>
				</button>

				<h2 id="lw-cookie-prefs-title" class="lw-cookie-modal-title">
					<?php echo esc_html( Strings::get_or_default( 'modal_title', __( 'Cookie Preferences', 'lw-cookie' ) ) ); ?>
				</h2>

				<div class="lw-cookie-categories">
					<?php foreach ( $categories as $key => $category ) : ?>
						<?php $this->output_category_block( $key, $category, $cookies_by_cat[ $key ] ?? [] ); ?>
					<?php endforeach; ?>
				</div>

				<div class="lw-cookie-modal-actions">
					<button type="button" class="lw-cookie-btn lw-cookie-btn-primary" data-lw-cookie-save>
						<?php echo esc_html( Strings::get( 'btn_save' ) ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Output a single category block inside the preferences modal.
	 *
	 * @param string                                                   $key      Category key.
	 * @param array{name: string, description: string, required: bool} $category Category data.
	 * @param array<int, array<string, string>>                        $cookies  Cookies declared under this category.
	 * @return void
	 */
	private function output_category_block( string $key, array $category, array $cookies ): void {
		?>
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
						<span class="lw-cookie-required"><?php echo esc_html( Strings::get_or_default( 'label_required', __( '(Required)', 'lw-cookie' ) ) ); ?></span>
					<?php endif; ?>
				</label>
			</div>
			<p class="lw-cookie-category-desc">
				<?php echo esc_html( $category['description'] ); ?>
			</p>
			<?php if ( ! empty( $cookies ) ) : ?>
				<details class="lw-cookie-category-details">
					<summary>
						<?php
						printf(
							/* translators: %d: number of cookies */
							esc_html( _n( 'Show %d cookie', 'Show %d cookies', count( $cookies ), 'lw-cookie' ) ),
							(int) count( $cookies )
						);
						?>
					</summary>
					<?php
					$col_cookie   = Strings::get_or_default( 'col_cookie', __( 'Cookie', 'lw-cookie' ) );
					$col_provider = Strings::get_or_default( 'col_provider', __( 'Provider', 'lw-cookie' ) );
					$col_purpose  = Strings::get_or_default( 'col_purpose', __( 'Purpose', 'lw-cookie' ) );
					$col_duration = Strings::get_or_default( 'col_duration', __( 'Duration', 'lw-cookie' ) );
					?>
					<table class="lw-cookie-category-cookies">
						<thead>
							<tr>
								<th scope="col"><?php echo esc_html( $col_cookie ); ?></th>
								<th scope="col"><?php echo esc_html( $col_provider ); ?></th>
								<th scope="col"><?php echo esc_html( $col_purpose ); ?></th>
								<th scope="col"><?php echo esc_html( $col_duration ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $cookies as $cookie ) : ?>
								<tr>
									<td data-label="<?php echo esc_attr( $col_cookie ); ?>"><code><?php echo esc_html( $cookie['name'] ); ?></code></td>
									<td data-label="<?php echo esc_attr( $col_provider ); ?>"><?php echo esc_html( $cookie['provider'] ); ?></td>
									<td data-label="<?php echo esc_attr( $col_purpose ); ?>"><?php echo esc_html( $cookie['purpose'] ); ?></td>
									<td data-label="<?php echo esc_attr( $col_duration ); ?>"><?php echo esc_html( $cookie['duration'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</details>
			<?php endif; ?>
		</div>
		<?php
	}
}
