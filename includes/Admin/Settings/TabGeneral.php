<?php
/**
 * General Settings Tab.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin\Settings;

/**
 * Handles the General settings tab.
 */
final class TabGeneral implements TabInterface {

	use FieldRendererTrait;

	/**
	 * Get the tab slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'general';
	}

	/**
	 * Get the tab label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'General', 'lw-cookie' );
	}

	/**
	 * Get the tab icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-admin-settings';
	}

	/**
	 * Render the tab content.
	 *
	 * @return void
	 */
	public function render(): void {
		?>
		<h2><?php esc_html_e( 'General Settings', 'lw-cookie' ); ?></h2>

		<div class="lw-cookie-section-description">
			<p><?php esc_html_e( 'Basic configuration for your cookie consent banner.', 'lw-cookie' ); ?></p>
		</div>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="enabled"><?php esc_html_e( 'Enable Banner', 'lw-cookie' ); ?></label>
				</th>
				<td>
					<?php
					$this->render_checkbox_field(
						[
							'name'  => 'enabled',
							'label' => __( 'Show cookie consent banner on the frontend', 'lw-cookie' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="privacy_policy_page"><?php esc_html_e( 'Privacy Policy Page', 'lw-cookie' ); ?></label>
				</th>
				<td>
					<?php $this->render_page_select_field( [ 'name' => 'privacy_policy_page' ] ); ?>
					<p class="description">
						<?php esc_html_e( 'Select your privacy policy page to link from the banner.', 'lw-cookie' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="policy_version"><?php esc_html_e( 'Policy Version', 'lw-cookie' ); ?></label>
				</th>
				<td>
					<?php
					$this->render_text_field(
						[
							'name'        => 'policy_version',
							'description' => __( 'Change this when you update your privacy policy to request new consent.', 'lw-cookie' ),
						]
					);
					?>
				</td>
			</tr>
		</table>

		<h3><?php esc_html_e( 'GDPR Compliance Notes', 'lw-cookie' ); ?></h3>
		<div class="lw-cookie-info-box">
			<ul>
				<li><?php esc_html_e( 'All optional cookie categories are OFF by default (opt-in required).', 'lw-cookie' ); ?></li>
				<li><?php esc_html_e( 'Users can granularly choose which categories to accept.', 'lw-cookie' ); ?></li>
				<li><?php esc_html_e( 'Consent is logged with timestamp for compliance proof.', 'lw-cookie' ); ?></li>
				<li><?php esc_html_e( 'Policy version changes trigger re-consent requests.', 'lw-cookie' ); ?></li>
			</ul>
		</div>
		<?php
	}
}
