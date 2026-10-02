<?php
/**
 * Complianz → LW Cookie settings mapper.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Migration\Complianz;

/**
 * Turns a Complianz banner row and cmplz_options into an lw_cookie_options
 * patch. Only keys with a usable source value are returned; the patch goes
 * through the regular settings sanitizer before it is stored.
 *
 * Complianz "Functional" is the always-on category, so it maps to
 * Necessary; its "Preferences" maps to Functional.
 */
final class SettingsMapper {

	/**
	 * LW Cookie text key => Complianz banner column.
	 */
	private const TEXTS = [
		'banner_title'           => 'header',
		'banner_message'         => 'message_optin',
		'btn_accept_all'         => 'accept',
		'btn_reject_all'         => 'dismiss',
		'btn_customize'          => 'view_preferences',
		'btn_save'               => 'save_preferences',
		'btn_manage_preferences' => 'revoke',
		'cat_necessary_name'     => 'category_functional',
		'cat_necessary_desc'     => 'functional_text',
		'cat_functional_name'    => 'category_prefs',
		'cat_functional_desc'    => 'preferences_text',
		'cat_analytics_name'     => 'category_stats',
		'cat_analytics_desc'     => 'statistics_text',
		'cat_marketing_name'     => 'category_all',
		'cat_marketing_desc'     => 'marketing_text',
	];

	/**
	 * LW Cookie colour key => [ Complianz column, palette entry ].
	 */
	private const COLORS = [
		'background_color' => [ 'colorpalette_background', 'color' ],
		'text_color'       => [ 'colorpalette_text', 'color' ],
		'primary_color'    => [ 'colorpalette_button_accept', 'background' ],
	];

	/**
	 * Complianz position => LW Cookie position/layout/alignment.
	 */
	private const POSITIONS = [
		'center'       => [ 'banner_position' => 'modal' ],
		'bottom'       => [
			'banner_position' => 'bottom',
			'banner_layout'   => 'bar',
		],
		'bottom-left'  => [
			'banner_position'      => 'bottom',
			'banner_layout'        => 'box',
			'banner_box_alignment' => 'left',
		],
		'bottom-right' => [
			'banner_position'      => 'bottom',
			'banner_layout'        => 'box',
			'banner_box_alignment' => 'right',
		],
	];

	/**
	 * Build the options patch.
	 *
	 * @param array<string, mixed> $banner       Decoded banner row.
	 * @param array<string, mixed> $options      cmplz_options.
	 * @param int                  $privacy_page Custom privacy statement page ID.
	 * @return array<string, mixed>
	 */
	public static function map( array $banner, array $options, int $privacy_page ): array {
		$patch = self::POSITIONS[ $banner['position'] ?? '' ] ?? [];

		foreach ( self::TEXTS as $key => $column ) {
			$text = self::text( $banner[ $column ] ?? '' );
			if ( '' !== $text ) {
				$patch[ $key ] = $text;
			}
		}

		foreach ( self::COLORS as $key => [ $column, $entry ] ) {
			$color = $banner[ $column ][ $entry ] ?? '';
			if ( is_string( $color ) && preg_match( '/^#([0-9a-f]{3}){1,2}$/i', $color ) ) {
				$patch[ $key ] = $color;
			}
		}

		$radius = $banner['colorpalette_border_radius'] ?? [];
		if ( is_array( $radius ) && is_numeric( $radius['top'] ?? null ) && 'px' === ( $radius['type'] ?? 'px' ) ) {
			$patch['border_radius'] = (string) (int) $radius['top'];
		}

		return array_merge( $patch, self::general( $options, $privacy_page ) );
	}

	/**
	 * Keys taken from cmplz_options.
	 *
	 * @param array<string, mixed> $options      cmplz_options.
	 * @param int                  $privacy_page Custom privacy statement page ID.
	 * @return array<string, mixed>
	 */
	private static function general( array $options, int $privacy_page ): array {
		$patch = [];

		if ( is_numeric( $options['cookie_expiry'] ?? null ) && (int) $options['cookie_expiry'] > 0 ) {
			$patch['consent_duration'] = min( 730, (int) $options['cookie_expiry'] );
		}

		if ( isset( $options['consent-mode'] ) ) {
			$patch['gcm_enabled'] = 'yes' === $options['consent-mode'];
		}

		// A Complianz-generated statement is a shortcode page that breaks once
		// Complianz is gone: only an existing page is carried over.
		if ( 'custom' === ( $options['privacy-statement'] ?? '' ) && $privacy_page > 0 ) {
			$patch['privacy_policy_page'] = $privacy_page;
		}

		return $patch;
	}

	/**
	 * Plain text of a text or text_checkbox value (`['text' => …, 'show' => …]`).
	 *
	 * @param mixed $value Column value.
	 * @return string
	 */
	private static function text( mixed $value ): string {
		if ( is_array( $value ) ) {
			$value = $value['text'] ?? '';
		}

		if ( ! is_string( $value ) ) {
			return '';
		}

		return trim( html_entity_decode( wp_strip_all_tags( $value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	}
}
