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
	 */
	public function get_slug(): string {
		return 'texts';
	}

	/**
	 * Get the tab label.
	 */
	public function get_label(): string {
		return __( 'Texts', 'lw-cookie' );
	}

	/**
	 * Get the tab icon.
	 */
	public function get_icon(): string {
		return 'dashicons-editor-textcolor';
	}

	/**
	 * Render the tab content.
	 */
	public function render(): void {
		?>
		<h2><?php esc_html_e( 'Banner & Modal Texts', 'lw-cookie' ); ?></h2>

		<?php MultilingualNotice::render(); ?>

		<div class="lw-cookie-section-description">
			<p><?php esc_html_e( 'Customize all user-facing text. Leave a field empty to use the built-in default for the current site language.', 'lw-cookie' ); ?></p>
		</div>

		<?php MultilingualNotice::open_lock(); ?>
		<?php foreach ( TextFieldDefinitions::all() as $section_title => $fields ) : ?>
			<?php $this->render_section( $section_title, $fields ); ?>
		<?php endforeach; ?>
		<?php MultilingualNotice::close_lock(); ?>
		<?php
	}

	/**
	 * Render a section heading and its form fields.
	 *
	 * @param string                                                                                                      $title  Section heading.
	 * @param array<int, array{name: string, label: string, textarea?: bool, placeholder?: string, description?: string}> $fields Field configs.
	 */
	private function render_section( string $title, array $fields ): void {
		printf( '<h3>%s</h3>', esc_html( $title ) );
		echo '<table class="form-table">';
		foreach ( $fields as $field ) {
			$this->render_field_row( $field );
		}
		echo '</table>';
	}

	/**
	 * Render a single field row.
	 *
	 * @param array{name: string, label: string, textarea?: bool, placeholder?: string, description?: string} $field Field config.
	 */
	private function render_field_row( array $field ): void {
		$name = $field['name'];
		$args = [
			'name'        => $name,
			'description' => $field['description'] ?? '',
			'placeholder' => $field['placeholder'] ?? '',
		];
		?>
		<tr>
			<th scope="row">
				<label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
			</th>
			<td>
				<?php
				if ( ! empty( $field['textarea'] ) ) {
					$this->render_textarea_field( $args );
				} else {
					$this->render_text_field( $args );
				}
				?>
			</td>
		</tr>
		<?php
	}
}
