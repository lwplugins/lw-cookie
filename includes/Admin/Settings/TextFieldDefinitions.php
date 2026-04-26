<?php
/**
 * Field definitions for the Texts settings tab.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin\Settings;

/**
 * Centralizes the form-field definitions for the Texts tab so that
 * TabTexts stays a thin renderer.
 *
 * Each section returns an array of fields, each shaped:
 *   [ 'name' => string, 'label' => string, 'textarea'? => bool,
 *     'placeholder'? => string, 'description'? => string ]
 *
 * Placeholder values mirror the textdomain default that the frontend
 * falls back to when the field is left empty.
 */
final class TextFieldDefinitions {

	/**
	 * Get all field sections, keyed by section heading.
	 *
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public static function all(): array {
		return [
			__( 'Banner', 'lw-cookie' )                  => self::banner(),
			__( 'Buttons', 'lw-cookie' )                 => self::buttons(),
			__( 'Preferences Modal', 'lw-cookie' )       => self::modal(),
			__( 'Cookie Declaration Page', 'lw-cookie' ) => self::declaration(),
		];
	}

	/**
	 * Banner section: title, message, privacy policy link.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function banner(): array {
		return [
			[
				'name'  => 'banner_title',
				'label' => __( 'Banner Title', 'lw-cookie' ),
			],
			[
				'name'        => 'banner_message',
				'label'       => __( 'Banner Message', 'lw-cookie' ),
				'textarea'    => true,
				'description' => __( 'Main message explaining cookie usage.', 'lw-cookie' ),
			],
			[
				'name'        => 'link_privacy_policy',
				'label'       => __( 'Privacy Policy Link', 'lw-cookie' ),
				'placeholder' => __( 'Privacy Policy', 'lw-cookie' ),
			],
		];
	}

	/**
	 * Buttons section: accept/reject/customize/save labels.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function buttons(): array {
		return [
			[
				'name'  => 'btn_accept_all',
				'label' => __( 'Accept All Button', 'lw-cookie' ),
			],
			[
				'name'  => 'btn_reject_all',
				'label' => __( 'Reject All Button', 'lw-cookie' ),
			],
			[
				'name'  => 'btn_customize',
				'label' => __( 'Customize Button', 'lw-cookie' ),
			],
			[
				'name'  => 'btn_save',
				'label' => __( 'Save Preferences Button', 'lw-cookie' ),
			],
		];
	}

	/**
	 * Preferences modal: title, "(Required)", and column headers.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function modal(): array {
		return [
			[
				'name'        => 'modal_title',
				'label'       => __( 'Modal Title', 'lw-cookie' ),
				'placeholder' => __( 'Cookie Preferences', 'lw-cookie' ),
			],
			[
				'name'        => 'label_required',
				'label'       => __( 'Required Label', 'lw-cookie' ),
				'placeholder' => __( '(Required)', 'lw-cookie' ),
			],
			[
				'name'        => 'col_cookie',
				'label'       => __( 'Cookie Column Header', 'lw-cookie' ),
				'placeholder' => __( 'Cookie', 'lw-cookie' ),
			],
			[
				'name'        => 'col_provider',
				'label'       => __( 'Provider Column Header', 'lw-cookie' ),
				'placeholder' => __( 'Provider', 'lw-cookie' ),
			],
			[
				'name'        => 'col_purpose',
				'label'       => __( 'Purpose Column Header', 'lw-cookie' ),
				'placeholder' => __( 'Purpose', 'lw-cookie' ),
			],
			[
				'name'        => 'col_duration',
				'label'       => __( 'Duration Column Header', 'lw-cookie' ),
				'placeholder' => __( 'Duration', 'lw-cookie' ),
			],
		];
	}

	/**
	 * Cookie declaration shortcode page: type column + manage/delete buttons.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function declaration(): array {
		return [
			[
				'name'        => 'col_type',
				'label'       => __( 'Type Column Header', 'lw-cookie' ),
				'placeholder' => __( 'Type', 'lw-cookie' ),
			],
			[
				'name'        => 'btn_manage_preferences',
				'label'       => __( 'Manage Preferences Button', 'lw-cookie' ),
				'placeholder' => __( 'Manage Cookie Preferences', 'lw-cookie' ),
			],
			[
				'name'        => 'btn_delete_all',
				'label'       => __( 'Delete All Button', 'lw-cookie' ),
				'placeholder' => __( 'Delete All Cookies', 'lw-cookie' ),
			],
		];
	}
}
