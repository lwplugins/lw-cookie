<?php
/**
 * Main Plugin class (v2.0 — client-side blocking).
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie;

use LightweightPlugins\Cookie\Admin\SettingsPage;
use LightweightPlugins\Cookie\Banner\Renderer as BannerRenderer;
use LightweightPlugins\Cookie\Banner\Assets as BannerAssets;
use LightweightPlugins\Cookie\Banner\InlineFallback;
use LightweightPlugins\Cookie\Banner\FloatingButton;
use LightweightPlugins\Cookie\Consent\Manager as ConsentManager;
use LightweightPlugins\Cookie\Blocking\GuardScript;
use LightweightPlugins\Cookie\Blocking\ServiceWorkerManager;
use LightweightPlugins\Cookie\Integrations\GoogleConsentMode;
use LightweightPlugins\Cookie\Integrations\CacheCompat;
use LightweightPlugins\Cookie\Rest\ConsentEndpoint;
use LightweightPlugins\Cookie\CLI\Commands as CLICommands;
use LightweightPlugins\Cookie\Shortcodes\CookieDeclaration;
use LightweightPlugins\Cookie\Scanner\Scanner;
use LightweightPlugins\Cookie\SiteManager\Integration as SiteManagerIntegration;
use LightweightPlugins\Cookie\I18n\Strings;
use LightweightPlugins\Cookie\Hooks;

/**
 * Main plugin class.
 */
final class Plugin {

	/**
	 * Consent manager instance (kept for Hooks/Scanner compatibility).
	 *
	 * @var ConsentManager
	 */
	private ConsentManager $consent_manager;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->consent_manager = new ConsentManager();

		$this->init_hooks();
		$this->init_components();
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		add_action( 'init', [ $this, 'load_textdomain' ] );
	}

	/**
	 * Initialize plugin components.
	 *
	 * @return void
	 */
	private function init_components(): void {
		CLICommands::register();
		Scanner::init();
		SiteManagerIntegration::init();
		Strings::init();

		// WordPress hooks for third-party plugin integration.
		new Hooks( $this->consent_manager );

		new CookieDeclaration();

		// REST API endpoint for consent logging (replaces AJAX).
		new ConsentEndpoint();

		// Service Worker fallback route.
		ServiceWorkerManager::register_fallback();

		if ( is_admin() ) {
			new SettingsPage();
		}

		if ( ! Options::get( 'enabled' ) ) {
			return;
		}

		// Frontend — all cache-safe, no server-side consent checks.
		new CacheCompat();
		new BannerAssets();
		new BannerRenderer();
		new FloatingButton();
		new InlineFallback();
		new GuardScript();

		// Google Consent Mode v2.
		if ( Options::get( 'gcm_enabled' ) ) {
			new GoogleConsentMode();
		}
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'lw-cookie',
			false,
			dirname( plugin_basename( LW_COOKIE_FILE ) ) . '/languages'
		);
	}

	/**
	 * Get the consent manager instance.
	 *
	 * @return ConsentManager
	 */
	public function get_consent_manager(): ConsentManager {
		return $this->consent_manager;
	}
}
