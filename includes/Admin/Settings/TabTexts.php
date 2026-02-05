<?php
/**
 * Texts Settings Tab.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin\Settings;

/**
 * Handles the Texts settings tab.
 */
final class TabTexts implements TabInterface {

	use FieldRendererTrait;

	/**
	 * Get the tab slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'texts';
	}

	/**
	 * Get the tab label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Texts', 'lw-cookie' );
	}

	/**
	 * Get the tab icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-editor-textcolor';
	}

	/**
	 * Render the tab content.
	 *
	 * @return void
	 */
	public function render(): void {
		?>
		<h2><?php esc_html_e( 'Banner Texts', 'lw-cookie' ); ?></h2>

		<div class="lw-cookie-section-description">
			<p><?php esc_html_e( 'Customize the text displayed on the cookie consent banner.', 'lw-cookie' ); ?></p>
		</div>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="banner_title"><?php esc_html_e( 'Banner Title', 'lw-cookie' ); ?></label>
				</th>
				<td>
					<?php $this->render_text_field( [ 'name' => 'banner_title' ] ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="banner_message"><?php esc_html_e( 'Banner Message', 'lw-cookie' ); ?></label>
				</th>
				<td>
					<?php
					$this->render_textarea_field(
						[
							'name'        => 'banner_message',
							'description' => __( 'Main message explaining cookie usage.', 'lw-cookie' ),
						]
					);
					?>
				</td>
			</tr>
		</table>

		<h3><?php esc_html_e( 'Button Labels', 'lw-cookie' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="btn_accept_all"><?php esc_html_e( 'Accept All Button', 'lw-cookie' ); ?></label>
				</th>
				<td>
					<?php $this->render_text_field( [ 'name' => 'btn_accept_all' ] ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="btn_reject_all"><?php esc_html_e( 'Reject All Button', 'lw-cookie' ); ?></label>
				</th>
				<td>
					<?php $this->render_text_field( [ 'name' => 'btn_reject_all' ] ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="btn_customize"><?php esc_html_e( 'Customize Button', 'lw-cookie' ); ?></label>
				</th>
				<td>
					<?php $this->render_text_field( [ 'name' => 'btn_customize' ] ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="btn_save"><?php esc_html_e( 'Save Preferences Button', 'lw-cookie' ); ?></label>
				</th>
				<td>
					<?php $this->render_text_field( [ 'name' => 'btn_save' ] ); ?>
				</td>
			</tr>
		</table>
		<?php
	}
}
