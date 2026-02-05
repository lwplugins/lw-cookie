<?php
/**
 * Main Plugin class.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie;

use LightweightPlugins\Cookie\Admin\SettingsPage;
use LightweightPlugins\Cookie\Banner\Renderer as BannerRenderer;
use LightweightPlugins\Cookie\Banner\Assets as BannerAssets;
use LightweightPlugins\Cookie\Consent\Manager as ConsentManager;
use LightweightPlugins\Cookie\Blocking\ScriptBlocker;
use LightweightPlugins\Cookie\Integrations\GoogleConsentMode;
use LightweightPlugins\Cookie\CLI\Commands as CLICommands;

/**
 * Main plugin class.
 */
final class Plugin {

	/**
	 * Consent manager instance.
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
		add_action( 'wp_ajax_lw_cookie_save_consent', [ $this, 'ajax_save_consent' ] );
		add_action( 'wp_ajax_nopriv_lw_cookie_save_consent', [ $this, 'ajax_save_consent' ] );
	}

	/**
	 * Initialize plugin components.
	 *
	 * @return void
	 */
	private function init_components(): void {
		// CLI commands.
		CLICommands::register();

		// Admin components.
		if ( is_admin() ) {
			new SettingsPage();
		}

		// Skip frontend if disabled.
		if ( ! Options::get( 'enabled' ) ) {
			return;
		}

		// Frontend components.
		new BannerAssets( $this->consent_manager );
		new BannerRenderer( $this->consent_manager );

		// Script blocking.
		if ( Options::get( 'script_blocking' ) ) {
			new ScriptBlocker( $this->consent_manager );
		}

		// Google Consent Mode v2.
		if ( Options::get( 'gcm_enabled' ) ) {
			new GoogleConsentMode( $this->consent_manager );
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
	 * AJAX handler for saving consent.
	 *
	 * @return void
	 */
	public function ajax_save_consent(): void {
		check_ajax_referer( 'lw_cookie_consent', 'nonce' );

		$categories = isset( $_POST['categories'] ) ? json_decode(
			sanitize_text_field( wp_unslash( $_POST['categories'] ) ),
			true
		) : [];

		$action_type = isset( $_POST['action_type'] )
			? sanitize_text_field( wp_unslash( $_POST['action_type'] ) )
			: 'customize';

		if ( ! is_array( $categories ) ) {
			wp_send_json_error( [ 'message' => 'Invalid categories' ] );
		}

		$this->consent_manager->save_consent( $categories, $action_type );

		wp_send_json_success( [ 'message' => 'Consent saved' ] );
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
