<?php
/**
 * Content Blocker - blocks iframes until consent.
 *
 * @package LightweightPlugins\Cookie\Blocking
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Blocking;

use LightweightPlugins\Cookie\Consent\Manager as ConsentManager;
use LightweightPlugins\Cookie\Options;

/**
 * Blocks external iframes (YouTube, Vimeo, etc.) until consent is given.
 */
class ContentBlocker {

	/**
	 * Consent manager instance.
	 *
	 * @var ConsentManager
	 */
	private ConsentManager $consent_manager;

	/**
	 * Blocked hosts and their categories.
	 *
	 * @var array
	 */
	private const BLOCKED_HOSTS = [
		// Video platforms - Marketing.
		'youtube.com'          => 'marketing',
		'youtube-nocookie.com' => 'marketing',
		'youtu.be'             => 'marketing',
		'vimeo.com'            => 'marketing',
		'player.vimeo.com'     => 'marketing',
		'dailymotion.com'      => 'marketing',
		'twitch.tv'            => 'marketing',
		'tiktok.com'           => 'marketing',
		// Social embeds - Marketing.
		'facebook.com'         => 'marketing',
		'instagram.com'        => 'marketing',
		'twitter.com'          => 'marketing',
		'x.com'                => 'marketing',
		'linkedin.com'         => 'marketing',
		'pinterest.com'        => 'marketing',
		// Maps - Functional.
		'google.com/maps'      => 'functional',
		'maps.google.com'      => 'functional',
		'openstreetmap.org'    => 'functional',
		// Audio - Functional.
		'soundcloud.com'       => 'functional',
		'spotify.com'          => 'functional',
		// Other embeds - Functional.
		'codepen.io'           => 'functional',
		'jsfiddle.net'         => 'functional',
	];

	/**
	 * Constructor.
	 *
	 * @param ConsentManager $consent_manager Consent manager instance.
	 */
	public function __construct( ConsentManager $consent_manager ) {
		$this->consent_manager = $consent_manager;

		if ( ! Options::get( 'content_blocking' ) ) {
			return;
		}

		add_action( 'template_redirect', [ $this, 'start_buffer' ], 1 );
	}

	/**
	 * Start output buffering.
	 *
	 * @return void
	 */
	public function start_buffer(): void {
		if ( is_admin() ) {
			return;
		}

		// Skip AJAX and REST API requests.
		if ( wp_doing_ajax() || defined( 'REST_REQUEST' ) ) {
			return;
		}

		ob_start( [ $this, 'process_buffer' ] );
	}

	/**
	 * Process the output buffer and block iframes.
	 *
	 * @param string $html HTML content.
	 * @return string Modified HTML.
	 */
	public function process_buffer( $html ): string {
		// Handle non-string input.
		if ( ! is_string( $html ) || empty( $html ) ) {
			return (string) $html;
		}

		// Only process HTML documents.
		if ( strpos( $html, '</html>' ) === false ) {
			return $html;
		}

		// Find and replace iframes.
		$html = preg_replace_callback(
			'/<iframe\s+[^>]*src=["\']([^"\']+)["\'][^>]*><\/iframe>/is',
			[ $this, 'replace_iframe' ],
			$html
		);

		return $html;
	}

	/**
	 * Replace iframe with placeholder if blocked.
	 *
	 * @param array $matches Regex matches.
	 * @return string Replacement HTML.
	 */
	public function replace_iframe( array $matches ): string {
		$iframe_html = $matches[0];
		$src         = $matches[1];

		$category = $this->get_blocked_category( $src );

		if ( ! $category ) {
			return $iframe_html;
		}

		// Check if user has consent for this category.
		if ( $this->consent_manager->has_consent( $category ) ) {
			return $iframe_html;
		}

		// Extract iframe attributes.
		$width  = $this->extract_attribute( $iframe_html, 'width', '100%' );
		$height = $this->extract_attribute( $iframe_html, 'height', '400' );
		$host   = wp_parse_url( $src, PHP_URL_HOST );

		return $this->get_placeholder_html( $src, $host, $category, $width, $height );
	}

	/**
	 * Get blocked category for a URL.
	 *
	 * @param string $url URL to check.
	 * @return string|null Category name or null if not blocked.
	 */
	private function get_blocked_category( string $url ): ?string {
		$host = wp_parse_url( $url, PHP_URL_HOST );

		if ( ! $host ) {
			return null;
		}

		// Remove www. prefix.
		$host = preg_replace( '/^www\./', '', $host );

		foreach ( self::BLOCKED_HOSTS as $blocked_host => $category ) {
			if ( str_contains( $host, $blocked_host ) || str_contains( $url, $blocked_host ) ) {
				return $category;
			}
		}

		return null;
	}

	/**
	 * Extract attribute value from HTML.
	 *
	 * @param string $html      HTML string.
	 * @param string $attribute Attribute name.
	 * @param string $default   Default value.
	 * @return string Attribute value.
	 */
	private function extract_attribute( string $html, string $attribute, string $default ): string {
		if ( preg_match( '/' . $attribute . '=["\']([^"\']+)["\']/i', $html, $matches ) ) {
			return $matches[1];
		}
		return $default;
	}

	/**
	 * Get placeholder HTML.
	 *
	 * @param string $src      Original iframe src.
	 * @param string $host     Host name.
	 * @param string $category Cookie category.
	 * @param string $width    Iframe width.
	 * @param string $height   Iframe height.
	 * @return string Placeholder HTML.
	 */
	private function get_placeholder_html(
		string $src,
		string $host,
		string $category,
		string $width,
		string $height
	): string {
		$bg_color   = Options::get( 'primary_color', '#2271b1' );
		$text_color = '#ffffff';

		$message = sprintf(
			/* translators: %s: hostname (e.g. youtube.com) */
			__( 'Content from %s is blocked until you accept cookies.', 'lw-cookie' ),
			'<strong>' . esc_html( $host ) . '</strong>'
		);

		$button_text = __( 'Accept & Load Content', 'lw-cookie' );

		$style = sprintf(
			'width:%s;height:%s;',
			is_numeric( $width ) ? $width . 'px' : $width,
			is_numeric( $height ) ? $height . 'px' : $height
		);

		$html  = '<div class="lw-cookie-blocked-content" data-src="' . esc_attr( $src ) . '" ';
		$html .= 'data-category="' . esc_attr( $category ) . '" style="' . esc_attr( $style ) . '">';
		$html .= '<div class="lw-cookie-blocked-inner">';
		$html .= '<span class="dashicons dashicons-video-alt3"></span>';
		$html .= '<p>' . wp_kses_post( $message ) . '</p>';
		$html .= '<button type="button" class="lw-cookie-load-content">';
		$html .= esc_html( $button_text );
		$html .= '</button>';
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Get CSS for blocked content placeholders.
	 *
	 * @return string CSS string.
	 */
	public static function get_placeholder_css(): string {
		$primary_color = Options::get( 'primary_color', '#2271b1' );

		return '
		.lw-cookie-blocked-content {
			background: #1d2327;
			display: flex;
			align-items: center;
			justify-content: center;
			position: relative;
			min-height: 200px;
		}
		.lw-cookie-blocked-inner {
			text-align: center;
			padding: 30px;
			color: #fff;
		}
		.lw-cookie-blocked-inner .dashicons {
			font-size: 48px;
			width: 48px;
			height: 48px;
			opacity: 0.5;
			margin-bottom: 15px;
		}
		.lw-cookie-blocked-inner p {
			margin: 0 0 20px 0;
			font-size: 14px;
			opacity: 0.9;
		}
		.lw-cookie-load-content {
			background: ' . esc_attr( $primary_color ) . ';
			color: #fff;
			border: none;
			padding: 12px 24px;
			font-size: 14px;
			cursor: pointer;
			border-radius: 4px;
		}
		.lw-cookie-load-content:hover {
			opacity: 0.9;
		}
		';
	}

	/**
	 * Get JS for loading blocked content.
	 *
	 * @return string JavaScript string.
	 */
	public static function get_placeholder_js(): string {
		return "
		document.addEventListener('click', function(e) {
			if (e.target.classList.contains('lw-cookie-load-content')) {
				var container = e.target.closest('.lw-cookie-blocked-content');
				if (container) {
					var src = container.dataset.src;
					var category = container.dataset.category;

					// Trigger consent for this category.
					if (window.lwCookieConsent) {
						var categories = window.lwCookieConsent.getConsent();
						categories[category] = true;
						window.lwCookieConsent.saveConsent(categories, 'customize');
					}

					// Replace placeholder with iframe.
					var iframe = document.createElement('iframe');
					iframe.src = src;
					iframe.style.width = container.style.width || '100%';
					iframe.style.height = container.style.height || '400px';
					iframe.style.border = 'none';
					iframe.setAttribute('allowfullscreen', '');
					iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
					container.parentNode.replaceChild(iframe, container);
				}
			}
		});

		// Auto-load when consent is given.
		document.addEventListener('lw_cookie_consent_saved', function(e) {
			var consent = e.detail.categories;
			document.querySelectorAll('.lw-cookie-blocked-content').forEach(function(container) {
				var category = container.dataset.category;
				if (consent[category]) {
					var src = container.dataset.src;
					var iframe = document.createElement('iframe');
					iframe.src = src;
					iframe.style.width = container.style.width || '100%';
					iframe.style.height = container.style.height || '400px';
					iframe.style.border = 'none';
					iframe.setAttribute('allowfullscreen', '');
					container.parentNode.replaceChild(iframe, container);
				}
			});
		});
		";
	}
}
