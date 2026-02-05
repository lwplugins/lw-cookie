<?php
/**
 * Known Scripts class.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Blocking;

/**
 * Defines known tracking scripts and their categories.
 */
final class KnownScripts {

	/**
	 * Get known scripts that should be blocked.
	 *
	 * @return array<string, array{category: string, patterns: array<string>}>
	 */
	public static function get_scripts(): array {
		return [
			// Analytics.
			'google_analytics'  => [
				'category' => 'analytics',
				'patterns' => [
					'google-analytics.com/analytics.js',
					'google-analytics.com/ga.js',
					'googletagmanager.com/gtag/js',
					'googletagmanager.com/gtm.js',
				],
			],
			'facebook_pixel'    => [
				'category' => 'marketing',
				'patterns' => [
					'connect.facebook.net',
					'facebook.com/tr',
				],
			],
			'hotjar'            => [
				'category' => 'analytics',
				'patterns' => [
					'static.hotjar.com',
					'script.hotjar.com',
				],
			],
			'linkedin_insight'  => [
				'category' => 'marketing',
				'patterns' => [
					'snap.licdn.com',
					'platform.linkedin.com',
				],
			],
			'twitter_pixel'     => [
				'category' => 'marketing',
				'patterns' => [
					'static.ads-twitter.com',
					'analytics.twitter.com',
				],
			],
			'tiktok_pixel'      => [
				'category' => 'marketing',
				'patterns' => [
					'analytics.tiktok.com',
				],
			],
			'pinterest_tag'     => [
				'category' => 'marketing',
				'patterns' => [
					'pintrk',
					's.pinimg.com/ct',
				],
			],
			'microsoft_clarity' => [
				'category' => 'analytics',
				'patterns' => [
					'clarity.ms',
				],
			],
			'hubspot'           => [
				'category' => 'marketing',
				'patterns' => [
					'js.hs-scripts.com',
					'js.hsforms.net',
				],
			],
			'intercom'          => [
				'category' => 'functional',
				'patterns' => [
					'widget.intercom.io',
				],
			],
			'crisp'             => [
				'category' => 'functional',
				'patterns' => [
					'client.crisp.chat',
				],
			],
			'youtube_embed'     => [
				'category' => 'marketing',
				'patterns' => [
					'youtube.com/embed',
					'youtube-nocookie.com/embed',
				],
			],
			'vimeo_embed'       => [
				'category' => 'marketing',
				'patterns' => [
					'player.vimeo.com',
				],
			],
		];
	}

	/**
	 * Get patterns for a specific category.
	 *
	 * @param string $category Category key.
	 * @return array<string>
	 */
	public static function get_patterns_for_category( string $category ): array {
		$patterns = [];
		$scripts  = self::get_scripts();

		foreach ( $scripts as $script ) {
			if ( $script['category'] === $category ) {
				$patterns = array_merge( $patterns, $script['patterns'] );
			}
		}

		return $patterns;
	}

	/**
	 * Get category for a URL.
	 *
	 * @param string $url Script URL.
	 * @return string|null Category key or null if not found.
	 */
	public static function get_category_for_url( string $url ): ?string {
		$scripts = self::get_scripts();

		foreach ( $scripts as $script ) {
			foreach ( $script['patterns'] as $pattern ) {
				if ( str_contains( $url, $pattern ) ) {
					return $script['category'];
				}
			}
		}

		return null;
	}
}
