<?php
/**
 * Bundled copy of the LW plugin registry.
 *
 * Synced from lwplugins/admin-hub 1.0.0 by bin/sync.php. Do not edit
 * this copy: change the admin-hub repo and sync again.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin\Hub;

/**
 * Used when the remote registry cannot be fetched, and to fill fields a
 * remote entry lacks. Mirrors lwplugins/registry plugins.json; plugins that
 * are not listed yet (LW Memberships, LW Slider) are left out on purpose.
 * Pure data: no logic belongs here.
 */
final class RegistryFallback {

	/**
	 * The bundled registry, keyed by plugin slug.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function get(): array {
		return array(
			'lw-cookie'       => array(
				'name'          => 'LW Cookie',
				'description'   => __( 'GDPR-compliant cookie consent banner.', 'lw-cookie' ),
				'icon_color'    => '#c05621',
				'constant'      => 'LW_COOKIE_VERSION',
				'settings_page' => 'lw-cookie',
				'github'        => 'https://github.com/lwplugins/lw-cookie',
				'status'        => 'stable',
			),
			'lw-disable'      => array(
				'name'          => 'LW Disable',
				'description'   => __( 'Disable WordPress features like comments.', 'lw-cookie' ),
				'icon_color'    => '#d63638',
				'constant'      => 'LW_DISABLE_VERSION',
				'settings_page' => 'lw-disable',
				'github'        => 'https://github.com/lwplugins/lw-disable',
				'status'        => 'stable',
			),
			'lw-enable'       => array(
				'name'          => 'LW Enable',
				'description'   => __( 'Enable WordPress features: SVG uploads and more.', 'lw-cookie' ),
				'icon_color'    => '#2e7d32',
				'constant'      => 'LW_ENABLE_VERSION',
				'settings_page' => 'lw-enable',
				'github'        => 'https://github.com/lwplugins/lw-enable',
				'status'        => 'stable',
			),
			'lw-firewall'     => array(
				'name'          => 'LW Firewall',
				'description'   => __( 'Rate limiting, bot blocking, IP bans and security headers.', 'lw-cookie' ),
				'icon_color'    => '#6d28d9',
				'constant'      => 'LW_FIREWALL_VERSION',
				'settings_page' => 'lw-firewall',
				'github'        => 'https://github.com/lwplugins/lw-firewall',
				'status'        => 'stable',
			),
			'lw-img'          => array(
				'name'          => 'LW Img',
				'description'   => __( 'Lightweight image optimization — auto-convert uploads to WebP via HelloImg.', 'lw-cookie' ),
				'icon_color'    => '#00875f',
				'constant'      => 'LW_IMG_VERSION',
				'settings_page' => 'lw-img',
				'github'        => 'https://github.com/lwplugins/lw-img',
				'status'        => 'beta',
			),
			'lw-lms'          => array(
				'name'          => 'LW LMS',
				'description'   => __( 'Headless LMS backend: courses, lessons, access, progress and quizzes over a REST API.', 'lw-cookie' ),
				'icon_color'    => '#4a9c5d',
				'constant'      => 'LW_LMS_VERSION',
				'settings_page' => 'lw-lms',
				'github'        => 'https://github.com/lwplugins/lw-lms',
				'status'        => 'beta',
			),
			'lw-pixel'        => array(
				'name'          => 'LW Pixel',
				'description'   => __( 'Tracking pixels: Meta, GA4, Ads, GTM, TikTok, Pinterest, Bing, Reddit, Snapchat, X.', 'lw-cookie' ),
				'icon_color'    => '#2b65f6',
				'constant'      => 'LW_PIXEL_VERSION',
				'settings_page' => 'lw-pixel',
				'github'        => 'https://github.com/lwplugins/lw-pixel',
				'status'        => 'stable',
			),
			'lw-scan'         => array(
				'name'          => 'LW Scan',
				'description'   => __( 'Malware scanner for files, the database and vulnerable software.', 'lw-cookie' ),
				'icon_color'    => '#d63638',
				'constant'      => 'LW_SCAN_VERSION',
				'settings_page' => 'lw-scan',
				'github'        => 'https://github.com/lwplugins/lw-scan',
				'status'        => 'stable',
			),
			'lw-seo'          => array(
				'name'          => 'LW SEO',
				'description'   => __( 'Essential SEO features without the bloat.', 'lw-cookie' ),
				'icon_color'    => '#2271b1',
				'constant'      => 'LW_SEO_VERSION',
				'settings_page' => 'lw-seo',
				'github'        => 'https://github.com/lwplugins/lw-seo',
				'status'        => 'stable',
			),
			'lw-site-manager' => array(
				'name'          => 'LW Site Manager',
				'description'   => __( 'Site maintenance via AI/REST using Abilities API.', 'lw-cookie' ),
				'icon_color'    => '#135e96',
				'constant'      => 'LW_SITE_MANAGER_VERSION',
				'settings_page' => 'lw-site-manager-mcp',
				'github'        => 'https://github.com/lwplugins/lw-site-manager',
				'status'        => 'stable',
			),
			'lw-translate'    => array(
				'name'          => 'LW Translate',
				'description'   => __( 'Manage WordPress translations from community repositories.', 'lw-cookie' ),
				'icon_color'    => '#0073aa',
				'constant'      => 'LW_TRANSLATE_VERSION',
				'settings_page' => 'lw-translate',
				'github'        => 'https://github.com/lwplugins/lw-translate',
				'status'        => 'beta',
			),
			'lw-zenadmin'     => array(
				'name'          => 'LW ZenAdmin',
				'description'   => __( 'Clean up your admin — notices sidebar & widget manager.', 'lw-cookie' ),
				'icon_color'    => '#8e44ad',
				'constant'      => 'LW_ZENADMIN_VERSION',
				'settings_page' => 'lw-zenadmin',
				'github'        => 'https://github.com/lwplugins/lw-zenadmin',
				'status'        => 'stable',
			),
		);
	}
}
