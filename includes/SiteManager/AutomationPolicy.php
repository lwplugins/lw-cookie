<?php
/**
 * Which settings automation may change.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\SiteManager;

use LightweightPlugins\Cookie\Admin\SettingsStore;

/**
 * The explicit list of option keys the Site Manager set-options ability may
 * write. It covers the whole settings model of the React admin; a test keeps
 * it in step with Options::get_defaults(), so a new setting has to be
 * classified here before it ships.
 */
final class AutomationPolicy {

	/**
	 * Option keys automation may change, grouped like Options::get_defaults().
	 */
	public const KEYS = [
		// General.
		'enabled',
		'privacy_policy_page',
		'policy_version',

		// Appearance.
		'banner_position',
		'banner_layout',
		'banner_box_alignment',
		'primary_color',
		'text_color',
		'background_color',
		'border_radius',

		// Categories.
		'cat_necessary_name',
		'cat_necessary_desc',
		'cat_functional_name',
		'cat_functional_desc',
		'cat_analytics_name',
		'cat_analytics_desc',
		'cat_marketing_name',
		'cat_marketing_desc',

		// Texts.
		'banner_title',
		'banner_message',
		'btn_accept_all',
		'btn_reject_all',
		'btn_customize',
		'btn_save',
		'link_privacy_policy',
		'modal_title',
		'label_required',
		'col_cookie',
		'col_provider',
		'col_purpose',
		'col_duration',
		'col_type',
		'btn_manage_preferences',
		'btn_delete_all',
		'blocked_embed_message',
		'blocked_embed_button',

		// Advanced.
		'consent_duration',
		'script_blocking',
		'content_blocking',
		'allow_youtube_nocookie',
		'gcm_enabled',
		'hide_for_logged_in',
		'show_floating_button',
		'floating_button_pos',

		// Cookie declaration.
		'declared_cookies',
	];

	/**
	 * Whether automation may write the key at all.
	 *
	 * @param string $key Option key.
	 * @return bool
	 */
	public static function allows( string $key ): bool {
		return in_array( $key, self::KEYS, true );
	}

	/**
	 * Keys automation may write right now: the policy minus the text keys a
	 * multilingual plugin owns while it is active (same lock as the admin).
	 *
	 * @return array<int, string>
	 */
	public static function writable_keys(): array {
		return array_values( array_diff( self::KEYS, SettingsStore::locked_keys() ) );
	}
}
