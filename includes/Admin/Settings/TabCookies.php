<?php
/**
 * Cookies Declaration Tab.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin\Settings;

use LightweightPlugins\Cookie\Options;

/**
 * Cookie declaration settings tab.
 */
final class TabCookies implements TabInterface {

	/**
	 * Get tab slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'cookies';
	}

	/**
	 * Get tab label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Cookies', 'lw-cookie' );
	}

	/**
	 * Get tab icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-list-view';
	}

	/**
	 * Render tab content.
	 *
	 * @return void
	 */
	public function render(): void {
		$cookies = Options::get( 'declared_cookies', [] );
		if ( ! is_array( $cookies ) ) {
			$cookies = [];
		}

		$categories = [
			'necessary'  => __( 'Necessary', 'lw-cookie' ),
			'functional' => Options::get( 'cat_functional_name', __( 'Functional', 'lw-cookie' ) ),
			'analytics'  => Options::get( 'cat_analytics_name', __( 'Analytics', 'lw-cookie' ) ),
			'marketing'  => Options::get( 'cat_marketing_name', __( 'Marketing', 'lw-cookie' ) ),
		];
		?>
		<div class="lw-cookie-declaration-manager">
			<table class="wp-list-table widefat fixed striped" id="lw-cookie-declaration-table">
				<thead>
					<tr>
						<th style="width: 15%;"><?php esc_html_e( 'Cookie Name', 'lw-cookie' ); ?></th>
						<th style="width: 15%;"><?php esc_html_e( 'Provider', 'lw-cookie' ); ?></th>
						<th style="width: 25%;"><?php esc_html_e( 'Purpose', 'lw-cookie' ); ?></th>
						<th style="width: 10%;"><?php esc_html_e( 'Duration', 'lw-cookie' ); ?></th>
						<th style="width: 12%;"><?php esc_html_e( 'Category', 'lw-cookie' ); ?></th>
						<th style="width: 10%;"><?php esc_html_e( 'Type', 'lw-cookie' ); ?></th>
						<th style="width: 13%;"><?php esc_html_e( 'Actions', 'lw-cookie' ); ?></th>
					</tr>
				</thead>
				<tbody id="lw-cookie-rows">
					<?php
					if ( ! empty( $cookies ) ) {
						foreach ( $cookies as $index => $cookie ) {
							$this->render_cookie_row( $index, $cookie, $categories );
						}
					}
					?>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="7">
							<button type="button" class="button button-secondary" id="lw-cookie-add-row">
								<?php esc_html_e( '+ Add Cookie', 'lw-cookie' ); ?>
							</button>
							<button type="button" class="button button-secondary" id="lw-cookie-add-common">
								<?php esc_html_e( '+ Add Common Cookies', 'lw-cookie' ); ?>
							</button>
						</td>
					</tr>
				</tfoot>
			</table>

			<template id="lw-cookie-row-template">
				<?php $this->render_cookie_row( '{{INDEX}}', [], $categories ); ?>
			</template>

			<p class="description" style="margin-top: 15px;">
				<?php esc_html_e( 'Use the shortcode [lw_cookie_declaration] to display this cookie list on any page.', 'lw-cookie' ); ?>
			</p>
		</div>

		<script>
		jQuery(document).ready(function($) {
			var rowIndex = <?php echo count( $cookies ); ?>;

			// Add new row.
			$('#lw-cookie-add-row').on('click', function() {
				var template = $('#lw-cookie-row-template').html();
				template = template.replace(/\{\{INDEX\}\}/g, rowIndex);
				$('#lw-cookie-rows').append(template);
				rowIndex++;
			});

			// Remove row.
			$(document).on('click', '.lw-cookie-remove-row', function() {
				$(this).closest('tr').remove();
			});

			// Add common cookies.
			$('#lw-cookie-add-common').on('click', function() {
				var commonCookies = [
					{
						name: 'lw_cookie_consent',
						provider: '<?php echo esc_js( get_bloginfo( 'name' ) ); ?>',
						purpose: '<?php echo esc_js( __( 'Stores cookie consent preferences', 'lw-cookie' ) ); ?>',
						duration: '<?php echo esc_js( __( '1 year', 'lw-cookie' ) ); ?>',
						category: 'necessary',
						type: 'persistent'
					},
					{
						name: 'wordpress_sec_*',
						provider: 'WordPress',
						purpose: '<?php echo esc_js( __( 'Authentication cookie for logged-in users', 'lw-cookie' ) ); ?>',
						duration: '<?php echo esc_js( __( 'Session', 'lw-cookie' ) ); ?>',
						category: 'necessary',
						type: 'session'
					},
					{
						name: 'wordpress_logged_in_*',
						provider: 'WordPress',
						purpose: '<?php echo esc_js( __( 'Indicates when user is logged in', 'lw-cookie' ) ); ?>',
						duration: '<?php echo esc_js( __( 'Session', 'lw-cookie' ) ); ?>',
						category: 'necessary',
						type: 'session'
					},
					{
						name: '_ga',
						provider: 'Google Analytics',
						purpose: '<?php echo esc_js( __( 'Distinguishes unique users', 'lw-cookie' ) ); ?>',
						duration: '<?php echo esc_js( __( '2 years', 'lw-cookie' ) ); ?>',
						category: 'analytics',
						type: 'persistent'
					},
					{
						name: '_ga_*',
						provider: 'Google Analytics',
						purpose: '<?php echo esc_js( __( 'Maintains session state', 'lw-cookie' ) ); ?>',
						duration: '<?php echo esc_js( __( '2 years', 'lw-cookie' ) ); ?>',
						category: 'analytics',
						type: 'persistent'
					},
					{
						name: '_fbp',
						provider: 'Facebook',
						purpose: '<?php echo esc_js( __( 'Tracks visits across websites for advertising', 'lw-cookie' ) ); ?>',
						duration: '<?php echo esc_js( __( '3 months', 'lw-cookie' ) ); ?>',
						category: 'marketing',
						type: 'persistent'
					}
				];

				commonCookies.forEach(function(cookie) {
					var template = $('#lw-cookie-row-template').html();
					template = template.replace(/\{\{INDEX\}\}/g, rowIndex);
					var $row = $(template);

					$row.find('[name*="[name]"]').val(cookie.name);
					$row.find('[name*="[provider]"]').val(cookie.provider);
					$row.find('[name*="[purpose]"]').val(cookie.purpose);
					$row.find('[name*="[duration]"]').val(cookie.duration);
					$row.find('[name*="[category]"]').val(cookie.category);
					$row.find('[name*="[type]"]').val(cookie.type);

					$('#lw-cookie-rows').append($row);
					rowIndex++;
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Render a single cookie row.
	 *
	 * @param int|string $index      Row index.
	 * @param array      $cookie     Cookie data.
	 * @param array      $categories Available categories.
	 * @return void
	 */
	private function render_cookie_row( $index, array $cookie, array $categories ): void {
		$name   = esc_attr( Options::OPTION_NAME );
		$cookie = wp_parse_args(
			$cookie,
			[
				'name'     => '',
				'provider' => '',
				'purpose'  => '',
				'duration' => '',
				'category' => 'necessary',
				'type'     => 'persistent',
			]
		);
		?>
		<tr>
			<td>
				<input type="text"
					name="<?php echo esc_attr( $name ); ?>[declared_cookies][<?php echo esc_attr( $index ); ?>][name]"
					value="<?php echo esc_attr( $cookie['name'] ); ?>"
					placeholder="<?php esc_attr_e( 'e.g. _ga', 'lw-cookie' ); ?>"
					class="widefat" />
			</td>
			<td>
				<input type="text"
					name="<?php echo esc_attr( $name ); ?>[declared_cookies][<?php echo esc_attr( $index ); ?>][provider]"
					value="<?php echo esc_attr( $cookie['provider'] ); ?>"
					placeholder="<?php esc_attr_e( 'e.g. Google', 'lw-cookie' ); ?>"
					class="widefat" />
			</td>
			<td>
				<input type="text"
					name="<?php echo esc_attr( $name ); ?>[declared_cookies][<?php echo esc_attr( $index ); ?>][purpose]"
					value="<?php echo esc_attr( $cookie['purpose'] ); ?>"
					placeholder="<?php esc_attr_e( 'Purpose description', 'lw-cookie' ); ?>"
					class="widefat" />
			</td>
			<td>
				<input type="text"
					name="<?php echo esc_attr( $name ); ?>[declared_cookies][<?php echo esc_attr( $index ); ?>][duration]"
					value="<?php echo esc_attr( $cookie['duration'] ); ?>"
					placeholder="<?php esc_attr_e( 'e.g. 1 year', 'lw-cookie' ); ?>"
					class="widefat" />
			</td>
			<td>
				<select name="<?php echo esc_attr( $name ); ?>[declared_cookies][<?php echo esc_attr( $index ); ?>][category]" class="widefat">
					<?php foreach ( $categories as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $cookie['category'], $key ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</td>
			<td>
				<select name="<?php echo esc_attr( $name ); ?>[declared_cookies][<?php echo esc_attr( $index ); ?>][type]" class="widefat">
					<option value="session" <?php selected( $cookie['type'], 'session' ); ?>>
						<?php esc_html_e( 'Session', 'lw-cookie' ); ?>
					</option>
					<option value="persistent" <?php selected( $cookie['type'], 'persistent' ); ?>>
						<?php esc_html_e( 'Persistent', 'lw-cookie' ); ?>
					</option>
				</select>
			</td>
			<td>
				<button type="button" class="button button-link-delete lw-cookie-remove-row">
					<?php esc_html_e( 'Remove', 'lw-cookie' ); ?>
				</button>
			</td>
		</tr>
		<?php
	}
}
