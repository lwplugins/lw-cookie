<?php
/**
 * Translatable (user-facing) text settings.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin;

use LightweightPlugins\Cookie\Admin\Settings\TextFieldDefinitions;

/**
 * Knows which option keys hold user-facing text: the category names and
 * descriptions plus every text field of TextFieldDefinitions.
 *
 * These are the keys a multilingual plugin owns while it is active, and the
 * ones among them that are multi-line.
 */
final class TranslatableFields {

	/**
	 * Cookie category slugs, in display order.
	 */
	public const CATEGORIES = [ 'necessary', 'functional', 'analytics', 'marketing' ];

	/**
	 * Every translatable option key: category names/descriptions first, then
	 * the text fields in display order.
	 *
	 * @return array<int, string>
	 */
	public static function keys(): array {
		$keys = [];

		foreach ( self::CATEGORIES as $category ) {
			$keys[] = 'cat_' . $category . '_name';
			$keys[] = 'cat_' . $category . '_desc';
		}

		foreach ( TextFieldDefinitions::all() as $fields ) {
			foreach ( $fields as $field ) {
				$keys[] = (string) $field['name'];
			}
		}

		return $keys;
	}

	/**
	 * Keys holding multi-line text (line breaks must survive sanitizing).
	 *
	 * @return array<int, string>
	 */
	public static function textarea_keys(): array {
		$keys = [];

		foreach ( self::CATEGORIES as $category ) {
			$keys[] = 'cat_' . $category . '_desc';
		}

		foreach ( TextFieldDefinitions::all() as $fields ) {
			foreach ( $fields as $field ) {
				if ( ! empty( $field['textarea'] ) ) {
					$keys[] = (string) $field['name'];
				}
			}
		}

		return $keys;
	}

	/**
	 * Text field sections for the settings UI, in display order.
	 *
	 * @return array<int, array{section: string, fields: array<int, array{name: string, label: string, type: string, placeholder: string, description: string}>}>
	 */
	public static function sections(): array {
		$sections = [];

		foreach ( TextFieldDefinitions::all() as $title => $fields ) {
			$list = [];
			foreach ( $fields as $field ) {
				$list[] = [
					'name'        => (string) $field['name'],
					'label'       => (string) $field['label'],
					'type'        => empty( $field['textarea'] ) ? 'text' : 'textarea',
					'placeholder' => (string) ( $field['placeholder'] ?? '' ),
					'description' => (string) ( $field['description'] ?? '' ),
				];
			}

			$sections[] = [
				'section' => (string) $title,
				'fields'  => $list,
			];
		}

		return $sections;
	}
}
