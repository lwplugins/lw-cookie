<?php
/**
 * Advanced Settings Tab.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin\Settings;

/**
 * Handles the Advanced settings tab.
 */
final class TabAdvanced implements TabInterface {

	use FieldRendererTrait;

	/**
	 * Get the tab slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'advanced';
	}

	/**
	 * Get the tab label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Advanced', 'lw-cookie' );
	}

	/**
	 * Get the tab icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-admin-tools';
	}

	/**
	 * Render the tab content.
	 *
	 * @return void
	 */
	public function render(): void {
		?>
		<h2><?php esc_html_e( 'Advanced Settings', 'lw-cookie' ); ?></h2>

		<div class="lw-cookie-section-description">
			<p><?php esc_html_e( 'Advanced configuration options.', 'lw-cookie' ); ?></p>
		</div>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="consent_duration"><?php esc_html_e( 'Consent Duration', 'lw-cookie' ); ?></label>
				</th>
				<td>
					<?php
					$this->render_number_field(
						[
							'name'        => 'consent_duration',
							'min'         => 1,
							'max'         => 730,
							'description' => __( 'Days until consent expires and user is asked again.', 'lw-cookie' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="script_blocking"><?php esc_html_e( 'Script Blocking', 'lw-cookie' ); ?></label>
				</th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'script_blocking',
							'label' => __( 'Block scripts until consent is given', 'lw-cookie' ),
						]
					);
					?>
					<p class="description">
						<?php esc_html_e( 'Automatically blocks known tracking scripts (Google Analytics, Facebook Pixel, etc.) until user consents.', 'lw-cookie' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="gcm_enabled"><?php esc_html_e( 'Google Consent Mode v2', 'lw-cookie' ); ?></label>
				</th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'gcm_enabled',
							'label' => __( 'Enable Google Consent Mode v2', 'lw-cookie' ),
						]
					);
					?>
					<p class="description">
						<?php esc_html_e( 'Required for Google Ads and Analytics in the EU. Sends consent signals to Google services.', 'lw-cookie' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<h3><?php esc_html_e( 'Floating Button', 'lw-cookie' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="show_floating_button"><?php esc_html_e( 'Show Floating Button', 'lw-cookie' ); ?></label>
				</th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'show_floating_button',
							'label' => __( 'Show a floating button for users to change their consent', 'lw-cookie' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="floating_button_pos"><?php esc_html_e( 'Button Position', 'lw-cookie' ); ?></label>
				</th>
				<td>
					<?php
					$this->render_select_field(
						[
							'name'    => 'floating_button_pos',
							'options' => [
								'bottom-left'  => __( 'Bottom Left', 'lw-cookie' ),
								'bottom-right' => __( 'Bottom Right', 'lw-cookie' ),
							],
						]
					);
					?>
				</td>
			</tr>
		</table>
		<?php
	}
}
