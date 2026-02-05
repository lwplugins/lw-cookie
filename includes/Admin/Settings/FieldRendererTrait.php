<?php
/**
 * Field Renderer Trait.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin\Settings;

use LightweightPlugins\Cookie\Options;

/**
 * Trait for rendering form fields.
 */
trait FieldRendererTrait {

	/**
	 * Render a text input field.
	 *
	 * @param array{name: string, description?: string} $args Field arguments.
	 * @return void
	 */
	protected function render_text_field( array $args ): void {
		$name  = $args['name'];
		$value = Options::get( $name );
		$desc  = $args['description'] ?? '';

		printf(
			'<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" class="regular-text" />',
			esc_attr( $name ),
			esc_attr( Options::OPTION_NAME ),
			esc_attr( (string) $value )
		);

		if ( $desc ) {
			printf( '<p class="description">%s</p>', esc_html( $desc ) );
		}
	}

	/**
	 * Render a textarea field.
	 *
	 * @param array{name: string, description?: string} $args Field arguments.
	 * @return void
	 */
	protected function render_textarea_field( array $args ): void {
		$name  = $args['name'];
		$value = Options::get( $name );
		$desc  = $args['description'] ?? '';

		printf(
			'<textarea id="%1$s" name="%2$s[%1$s]" rows="3" class="large-text">%3$s</textarea>',
			esc_attr( $name ),
			esc_attr( Options::OPTION_NAME ),
			esc_textarea( (string) $value )
		);

		if ( $desc ) {
			printf( '<p class="description">%s</p>', esc_html( $desc ) );
		}
	}

	/**
	 * Render a checkbox field.
	 *
	 * @param array{name: string, label: string} $args Field arguments.
	 * @return void
	 */
	protected function render_checkbox_field( array $args ): void {
		$name  = $args['name'];
		$label = $args['label'] ?? '';
		$value = Options::get( $name );

		printf(
			'<label><input type="checkbox" id="%1$s" name="%2$s[%1$s]" value="1" %3$s /> %4$s</label>',
			esc_attr( $name ),
			esc_attr( Options::OPTION_NAME ),
			checked( $value, true, false ),
			esc_html( $label )
		);
	}

	/**
	 * Render a select field.
	 *
	 * @param array{name: string, options: array<string, string>} $args Field arguments.
	 * @return void
	 */
	protected function render_select_field( array $args ): void {
		$name    = $args['name'];
		$options = $args['options'] ?? [];
		$value   = Options::get( $name );

		printf(
			'<select id="%1$s" name="%2$s[%1$s]">',
			esc_attr( $name ),
			esc_attr( Options::OPTION_NAME )
		);

		foreach ( $options as $key => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $key ),
				selected( $value, $key, false ),
				esc_html( $label )
			);
		}

		echo '</select>';
	}

	/**
	 * Render a number input field.
	 *
	 * @param array{name: string, min?: int, max?: int, description?: string} $args Field arguments.
	 * @return void
	 */
	protected function render_number_field( array $args ): void {
		$name  = $args['name'];
		$value = Options::get( $name );
		$min   = $args['min'] ?? 0;
		$max   = $args['max'] ?? 9999;
		$desc  = $args['description'] ?? '';

		printf(
			'<input type="number" id="%1$s" name="%2$s[%1$s]" value="%3$s" min="%4$d" max="%5$d" class="small-text" />',
			esc_attr( $name ),
			esc_attr( Options::OPTION_NAME ),
			esc_attr( (string) $value ),
			intval( $min ),
			intval( $max )
		);

		if ( $desc ) {
			printf( '<p class="description">%s</p>', esc_html( $desc ) );
		}
	}

	/**
	 * Render a color picker field.
	 *
	 * @param array{name: string} $args Field arguments.
	 * @return void
	 */
	protected function render_color_field( array $args ): void {
		$name  = $args['name'];
		$value = Options::get( $name );

		printf(
			'<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" class="lw-cookie-color-picker" />',
			esc_attr( $name ),
			esc_attr( Options::OPTION_NAME ),
			esc_attr( (string) $value )
		);
	}

	/**
	 * Render a page select field.
	 *
	 * @param array{name: string} $args Field arguments.
	 * @return void
	 */
	protected function render_page_select_field( array $args ): void {
		$name  = $args['name'];
		$value = Options::get( $name );

		wp_dropdown_pages(
			[
				'name'             => esc_attr( Options::OPTION_NAME . '[' . $name . ']' ),
				'id'               => esc_attr( $name ),
				'selected'         => intval( $value ),
				'show_option_none' => esc_html__( '— Select Page —', 'lw-cookie' ),
			]
		);
	}
}
