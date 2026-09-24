<?php
/**
 * TranslatableFields unit tests.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use LightweightPlugins\Cookie\Admin\TranslatableFields;
use LightweightPlugins\Cookie\Options;
use LightweightPlugins\Cookie\Tests\Unit\MonkeyTestCase;

/**
 * @covers \LightweightPlugins\Cookie\Admin\TranslatableFields
 */
final class TranslatableFieldsTest extends MonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\stubTranslationFunctions();
	}

	public function test_keys_start_with_the_category_names_and_descriptions(): void {
		$this->assertSame(
			[
				'cat_necessary_name',
				'cat_necessary_desc',
				'cat_functional_name',
				'cat_functional_desc',
				'cat_analytics_name',
				'cat_analytics_desc',
				'cat_marketing_name',
				'cat_marketing_desc',
				'banner_title',
			],
			array_slice( TranslatableFields::keys(), 0, 9 )
		);
	}

	public function test_keys_are_all_string_options(): void {
		$defaults = Options::get_defaults();

		foreach ( TranslatableFields::keys() as $key ) {
			$this->assertIsString( $defaults[ $key ] ?? null, $key );
		}
	}

	public function test_keys_cover_every_text_option(): void {
		$text_keys = [ 'banner_title', 'banner_message', 'btn_accept_all', 'btn_reject_all', 'btn_customize', 'btn_save', 'link_privacy_policy', 'modal_title', 'label_required', 'col_cookie', 'col_provider', 'col_purpose', 'col_duration', 'col_type', 'btn_manage_preferences', 'btn_delete_all', 'blocked_embed_message', 'blocked_embed_button' ];

		$this->assertSame( [], array_diff( $text_keys, TranslatableFields::keys() ) );
	}

	public function test_textarea_keys_are_the_descriptions_and_multi_line_texts(): void {
		$this->assertSame(
			[ 'cat_necessary_desc', 'cat_functional_desc', 'cat_analytics_desc', 'cat_marketing_desc', 'banner_message', 'blocked_embed_message' ],
			TranslatableFields::textarea_keys()
		);
	}

	public function test_sections_normalise_every_field(): void {
		$sections = TranslatableFields::sections();

		$this->assertSame(
			[
				'name'        => 'banner_message',
				'label'       => 'Banner Message',
				'type'        => 'textarea',
				'placeholder' => '',
				'description' => 'Main message explaining cookie usage.',
			],
			$sections[0]['fields'][1]
		);
	}

	public function test_sections_keep_display_order(): void {
		$this->assertSame(
			[ 'Banner', 'Buttons', 'Preferences Modal', 'Cookie Declaration Page', 'Blocked Content' ],
			array_column( TranslatableFields::sections(), 'section' )
		);
	}
}
