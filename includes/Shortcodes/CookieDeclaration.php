<?php
/**
 * Cookie Declaration Shortcode.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Shortcodes;

use LightweightPlugins\Cookie\I18n\Strings;

/**
 * Renders the cookie declaration table.
 */
final class CookieDeclaration {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'lw_cookie_declaration', [ $this, 'render' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue_styles' ] );
	}

	/**
	 * Enqueue styles if shortcode is present.
	 *
	 * @return void
	 */
	public function maybe_enqueue_styles(): void {
		global $post;

		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'lw_cookie_declaration' ) ) {
			wp_enqueue_style(
				'lw-cookie-declaration',
				LW_COOKIE_URL . 'assets/css/declaration.css',
				[],
				LW_COOKIE_VERSION
			);
		}
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ): string {
		$atts = shortcode_atts(
			[
				'group_by' => 'category',
				'class'    => '',
			],
			$atts,
			'lw_cookie_declaration'
		);

		$grouped = Strings::get_cookies_by_category();
		if ( empty( $grouped ) ) {
			return '<p>' . esc_html__( 'No cookies have been declared.', 'lw-cookie' ) . '</p>';
		}

		$categories = $this->get_categories();

		// Sort by category order.
		$category_order = [ 'necessary', 'functional', 'analytics', 'marketing' ];
		$sorted         = [];
		foreach ( $category_order as $cat ) {
			if ( isset( $grouped[ $cat ] ) ) {
				$sorted[ $cat ] = $grouped[ $cat ];
			}
		}

		$class = 'lw-cookie-declaration';
		if ( ! empty( $atts['class'] ) ) {
			$class .= ' ' . sanitize_html_class( $atts['class'] );
		}

		$col_cookie   = Strings::get_or_default( 'col_cookie', __( 'Cookie', 'lw-cookie' ) );
		$col_provider = Strings::get_or_default( 'col_provider', __( 'Provider', 'lw-cookie' ) );
		$col_purpose  = Strings::get_or_default( 'col_purpose', __( 'Purpose', 'lw-cookie' ) );
		$col_duration = Strings::get_or_default( 'col_duration', __( 'Duration', 'lw-cookie' ) );
		$col_type     = Strings::get_or_default( 'col_type', __( 'Type', 'lw-cookie' ) );
		$btn_manage   = Strings::get_or_default( 'btn_manage_preferences', __( 'Manage Cookie Preferences', 'lw-cookie' ) );
		$btn_delete   = Strings::get_or_default( 'btn_delete_all', __( 'Delete All Cookies', 'lw-cookie' ) );

		ob_start();
		?>
		<div class="<?php echo esc_attr( $class ); ?>">
			<?php foreach ( $sorted as $category => $category_cookies ) : ?>
				<div class="lw-cookie-declaration-category">
					<h3 class="lw-cookie-declaration-category-title">
						<?php echo esc_html( $categories[ $category ]['name'] ?? ucfirst( $category ) ); ?>
						<?php if ( 'necessary' === $category ) : ?>
							<span class="lw-cookie-required-badge"><?php esc_html_e( 'Always Active', 'lw-cookie' ); ?></span>
						<?php endif; ?>
					</h3>
					<?php if ( ! empty( $categories[ $category ]['description'] ) ) : ?>
						<p class="lw-cookie-declaration-category-desc">
							<?php echo esc_html( $categories[ $category ]['description'] ); ?>
						</p>
					<?php endif; ?>

					<table class="lw-cookie-declaration-table">
						<thead>
							<tr>
								<th><?php echo esc_html( $col_cookie ); ?></th>
								<th><?php echo esc_html( $col_provider ); ?></th>
								<th><?php echo esc_html( $col_purpose ); ?></th>
								<th><?php echo esc_html( $col_duration ); ?></th>
								<th><?php echo esc_html( $col_type ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $category_cookies as $cookie ) : ?>
								<tr>
									<td data-label="<?php echo esc_attr( $col_cookie ); ?>">
										<code><?php echo esc_html( $cookie['name'] ); ?></code>
									</td>
									<td data-label="<?php echo esc_attr( $col_provider ); ?>">
										<?php echo esc_html( $cookie['provider'] ); ?>
									</td>
									<td data-label="<?php echo esc_attr( $col_purpose ); ?>">
										<?php echo esc_html( $cookie['purpose'] ); ?>
									</td>
									<td data-label="<?php echo esc_attr( $col_duration ); ?>">
										<?php echo esc_html( $cookie['duration'] ); ?>
									</td>
									<td data-label="<?php echo esc_attr( $col_type ); ?>">
										<?php
										$type_labels = [
											'session'    => __( 'Session', 'lw-cookie' ),
											'persistent' => __( 'Persistent', 'lw-cookie' ),
										];
										echo esc_html( $type_labels[ $cookie['type'] ] ?? $cookie['type'] );
										?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endforeach; ?>

			<p class="lw-cookie-declaration-footer">
				<button type="button" onclick="if(window.LWCookie){window.LWCookie.openPreferences();}" class="lw-cookie-manage-btn">
					<?php echo esc_html( $btn_manage ); ?>
				</button>
				<button type="button" onclick="if(window.LWCookie){window.LWCookie.deleteAllCookies();}" class="lw-cookie-delete-btn">
					<?php echo esc_html( $btn_delete ); ?>
				</button>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get translated cookie category metadata.
	 *
	 * @return array<string, array{name: string, description: string, required: bool}>
	 */
	private function get_categories(): array {
		return Strings::get_categories();
	}
}
