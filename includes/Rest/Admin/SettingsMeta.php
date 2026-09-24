<?php
/**
 * Read-only context for the settings screen.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Rest\Admin;

use LightweightPlugins\Cookie\Admin\CommonCookies;
use LightweightPlugins\Cookie\Banner\Preview;
use LightweightPlugins\Cookie\Admin\SettingsStore;
use LightweightPlugins\Cookie\Admin\TranslatableFields;
use LightweightPlugins\Cookie\I18n\MultilingualDetector;
use LightweightPlugins\Cookie\Options;
use LightweightPlugins\Cookie\Scanner\Scanner;

/**
 * Builds the `meta` block of the settings response: choices, labels and
 * environment facts the settings UI needs but never writes.
 */
final class SettingsMeta {

	/**
	 * Build the meta block. Computed on every request (the category labels
	 * depend on the just-saved options, the scan URLs are randomised).
	 *
	 * @return array<string, mixed>
	 */
	public static function build(): array {
		return [
			'pages'           => self::pages(),
			'multilingual'    => MultilingualDetector::active(),
			'locked_keys'     => SettingsStore::locked_keys(),
			'text_fields'     => TranslatableFields::sections(),
			'category_labels' => self::category_labels(),
			'common_cookies'  => CommonCookies::all( (string) get_bloginfo( 'name' ) ),
			'scan_urls'       => array_values( array_map( 'strval', array_filter( Scanner::get_scan_urls() ) ) ),
			'shortcode'       => '[lw_cookie_declaration]',
			'preview_url'     => Preview::url(),
		];
	}

	/**
	 * Published pages for the privacy policy page select.
	 *
	 * @return array<int, array{id: int, title: string}>
	 */
	private static function pages(): array {
		$pages = get_pages(
			[
				'post_status' => 'publish',
				'sort_column' => 'menu_order,post_title',
			]
		);

		$list = [];
		foreach ( is_array( $pages ) ? $pages : [] as $page ) {
			$title = (string) $page->post_title;
			if ( '' === $title ) {
				/* translators: %d: page ID */
				$title = sprintf( __( '(no title) #%d', 'lw-cookie' ), $page->ID );
			}

			$list[] = [
				'id'    => (int) $page->ID,
				'title' => $title,
			];
		}

		return $list;
	}

	/**
	 * Current category names for the cookie table, falling back to the
	 * translated default when a name is empty.
	 *
	 * @return array<string, string>
	 */
	private static function category_labels(): array {
		$fallbacks = [
			'necessary'  => __( 'Necessary', 'lw-cookie' ),
			'functional' => __( 'Functional', 'lw-cookie' ),
			'analytics'  => __( 'Analytics', 'lw-cookie' ),
			'marketing'  => __( 'Marketing', 'lw-cookie' ),
		];

		$labels = [];
		foreach ( Options::get_categories() as $slug => $category ) {
			$name            = trim( $category['name'] );
			$labels[ $slug ] = '' !== $name ? $name : $fallbacks[ $slug ];
		}

		return $labels;
	}
}
