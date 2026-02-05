<?php
/**
 * Appearance Settings Tab.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin\Settings;

/**
 * Handles the Appearance settings tab.
 */
final class TabAppearance implements TabInterface {

	use FieldRendererTrait;

	/**
	 * Get the tab slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'appearance';
	}

	/**
	 * Get the tab label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Appearance', 'lw-cookie' );
	}

	/**
	 * Get the tab icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-admin-appearance';
	}

	/**
	 * Render the tab content.
	 *
	 * @return void
	 */
	public function render(): void {
		?>
		<h2><?php esc_html_e( 'Appearance Settings', 'lw-cookie' ); ?></h2>

		<div class="lw-cookie-section-description">
			<p><?php esc_html_e( 'Customize the look and feel of your cookie banner.', 'lw-cookie' ); ?></p>
		</div>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="banner_position"><?php esc_html_e( 'Banner Position', 'lw-cookie' ); ?></label>
				</th>
				<td>
					<?php
					$this->render_select_field(
						[
							'name'    => 'banner_position',
							'options' => [
								'bottom' => __( 'Bottom', 'lw-cookie' ),
								'top'    => __( 'Top', 'lw-cookie' ),
								'modal'  => __( 'Modal (Center)', 'lw-cookie' ),
							],
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="banner_layout"><?php esc_html_e( 'Banner Layout', 'lw-cookie' ); ?></label>
				</th>
				<td>
					<?php
					$this->render_select_field(
						[
							'name'    => 'banner_layout',
							'options' => [
								'bar' => __( 'Full-width Bar', 'lw-cookie' ),
								'box' => __( 'Floating Box', 'lw-cookie' ),
							],
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="primary_color"><?php esc_html_e( 'Primary Color', 'lw-cookie' ); ?></label>
				</th>
				<td>
					<?php $this->render_color_field( [ 'name' => 'primary_color' ] ); ?>
					<p class="description"><?php esc_html_e( 'Button and accent color.', 'lw-cookie' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="text_color"><?php esc_html_e( 'Text Color', 'lw-cookie' ); ?></label>
				</th>
				<td>
					<?php $this->render_color_field( [ 'name' => 'text_color' ] ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="background_color"><?php esc_html_e( 'Background Color', 'lw-cookie' ); ?></label>
				</th>
				<td>
					<?php $this->render_color_field( [ 'name' => 'background_color' ] ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="border_radius"><?php esc_html_e( 'Border Radius', 'lw-cookie' ); ?></label>
				</th>
				<td>
					<?php
					$this->render_number_field(
						[
							'name'        => 'border_radius',
							'min'         => 0,
							'max'         => 50,
							'description' => __( 'Border radius in pixels.', 'lw-cookie' ),
						]
					);
					?>
				</td>
			</tr>
		</table>
		<?php
	}
}
