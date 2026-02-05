<?php
/**
 * Cookie Declaration Shortcode.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Shortcodes;

use LightweightPlugins\Cookie\Options;

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

		$cookies = Options::get( 'declared_cookies', [] );
		if ( ! is_array( $cookies ) || empty( $cookies ) ) {
			return '<p>' . esc_html__( 'No cookies have been declared.', 'lw-cookie' ) . '</p>';
		}

		// Filter out empty rows.
		$cookies = array_filter(
			$cookies,
			function ( $cookie ) {
				return ! empty( $cookie['name'] );
			}
		);

		if ( empty( $cookies ) ) {
			return '<p>' . esc_html__( 'No cookies have been declared.', 'lw-cookie' ) . '</p>';
		}

		$categories = $this->get_categories();

		// Group by category.
		$grouped = [];
		foreach ( $cookies as $cookie ) {
			$cat = $cookie['category'] ?? 'necessary';
			if ( ! isset( $grouped[ $cat ] ) ) {
				$grouped[ $cat ] = [];
			}
			$grouped[ $cat ][] = $cookie;
		}

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
								<th><?php esc_html_e( 'Cookie', 'lw-cookie' ); ?></th>
								<th><?php esc_html_e( 'Provider', 'lw-cookie' ); ?></th>
								<th><?php esc_html_e( 'Purpose', 'lw-cookie' ); ?></th>
								<th><?php esc_html_e( 'Duration', 'lw-cookie' ); ?></th>
								<th><?php esc_html_e( 'Type', 'lw-cookie' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $category_cookies as $cookie ) : ?>
								<tr>
									<td data-label="<?php esc_attr_e( 'Cookie', 'lw-cookie' ); ?>">
										<code><?php echo esc_html( $cookie['name'] ); ?></code>
									</td>
									<td data-label="<?php esc_attr_e( 'Provider', 'lw-cookie' ); ?>">
										<?php echo esc_html( $cookie['provider'] ); ?>
									</td>
									<td data-label="<?php esc_attr_e( 'Purpose', 'lw-cookie' ); ?>">
										<?php echo esc_html( $cookie['purpose'] ); ?>
									</td>
									<td data-label="<?php esc_attr_e( 'Duration', 'lw-cookie' ); ?>">
										<?php echo esc_html( $cookie['duration'] ); ?>
									</td>
									<td data-label="<?php esc_attr_e( 'Type', 'lw-cookie' ); ?>">
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
					<?php esc_html_e( 'Manage Cookie Preferences', 'lw-cookie' ); ?>
				</button>
				<button type="button" onclick="if(window.LWCookie){window.LWCookie.deleteAllCookies();}" class="lw-cookie-delete-btn">
					<?php esc_html_e( 'Delete All Cookies', 'lw-cookie' ); ?>
				</button>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get cookie categories with names and descriptions.
	 *
	 * @return array
	 */
	private function get_categories(): array {
		return [
			'necessary'  => [
				'name'        => __( 'Necessary', 'lw-cookie' ),
				'description' => __( 'Essential cookies required for the website to function properly.', 'lw-cookie' ),
			],
			'functional' => [
				'name'        => Options::get( 'cat_functional_name', __( 'Functional', 'lw-cookie' ) ),
				'description' => Options::get( 'cat_functional_desc', '' ),
			],
			'analytics'  => [
				'name'        => Options::get( 'cat_analytics_name', __( 'Analytics', 'lw-cookie' ) ),
				'description' => Options::get( 'cat_analytics_desc', '' ),
			],
			'marketing'  => [
				'name'        => Options::get( 'cat_marketing_name', __( 'Marketing', 'lw-cookie' ) ),
				'description' => Options::get( 'cat_marketing_desc', '' ),
			],
		];
	}
}
