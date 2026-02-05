<?php
/**
 * Categories Settings Tab.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin\Settings;

/**
 * Handles the Categories settings tab.
 */
final class TabCategories implements TabInterface {

	use FieldRendererTrait;

	/**
	 * Get the tab slug.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'categories';
	}

	/**
	 * Get the tab label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Categories', 'lw-cookie' );
	}

	/**
	 * Get the tab icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-category';
	}

	/**
	 * Render the tab content.
	 *
	 * @return void
	 */
	public function render(): void {
		?>
		<h2><?php esc_html_e( 'Cookie Categories', 'lw-cookie' ); ?></h2>

		<div class="lw-cookie-section-description">
			<p><?php esc_html_e( 'Customize the names and descriptions of cookie categories shown to users.', 'lw-cookie' ); ?></p>
		</div>

		<?php $this->render_necessary_category(); ?>
		<?php $this->render_functional_category(); ?>
		<?php $this->render_analytics_category(); ?>
		<?php $this->render_marketing_category(); ?>
		<?php
	}

	/**
	 * Render necessary category (readonly).
	 *
	 * @return void
	 */
	private function render_necessary_category(): void {
		?>
		<h3><?php esc_html_e( 'Necessary Cookies', 'lw-cookie' ); ?></h3>
		<p class="description">
			<?php esc_html_e( 'This category is always enabled and cannot be customized.', 'lw-cookie' ); ?>
		</p>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Name', 'lw-cookie' ); ?></th>
				<td><code><?php esc_html_e( 'Necessary', 'lw-cookie' ); ?></code></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Description', 'lw-cookie' ); ?></th>
				<td>
					<em><?php esc_html_e( 'Essential cookies required for the website to function.', 'lw-cookie' ); ?></em>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render functional category.
	 *
	 * @return void
	 */
	private function render_functional_category(): void {
		?>
		<h3><?php esc_html_e( 'Functional Cookies', 'lw-cookie' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="cat_functional_name"><?php esc_html_e( 'Name', 'lw-cookie' ); ?></label>
				</th>
				<td><?php $this->render_text_field( [ 'name' => 'cat_functional_name' ] ); ?></td>
			</tr>
			<tr>
				<th scope="row">
					<label for="cat_functional_desc"><?php esc_html_e( 'Description', 'lw-cookie' ); ?></label>
				</th>
				<td><?php $this->render_textarea_field( [ 'name' => 'cat_functional_desc' ] ); ?></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render analytics category.
	 *
	 * @return void
	 */
	private function render_analytics_category(): void {
		?>
		<h3><?php esc_html_e( 'Analytics Cookies', 'lw-cookie' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="cat_analytics_name"><?php esc_html_e( 'Name', 'lw-cookie' ); ?></label>
				</th>
				<td><?php $this->render_text_field( [ 'name' => 'cat_analytics_name' ] ); ?></td>
			</tr>
			<tr>
				<th scope="row">
					<label for="cat_analytics_desc"><?php esc_html_e( 'Description', 'lw-cookie' ); ?></label>
				</th>
				<td><?php $this->render_textarea_field( [ 'name' => 'cat_analytics_desc' ] ); ?></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render marketing category.
	 *
	 * @return void
	 */
	private function render_marketing_category(): void {
		?>
		<h3><?php esc_html_e( 'Marketing Cookies', 'lw-cookie' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="cat_marketing_name"><?php esc_html_e( 'Name', 'lw-cookie' ); ?></label>
				</th>
				<td><?php $this->render_text_field( [ 'name' => 'cat_marketing_name' ] ); ?></td>
			</tr>
			<tr>
				<th scope="row">
					<label for="cat_marketing_desc"><?php esc_html_e( 'Description', 'lw-cookie' ); ?></label>
				</th>
				<td><?php $this->render_textarea_field( [ 'name' => 'cat_marketing_desc' ] ); ?></td>
			</tr>
		</table>
		<?php
	}
}
