<?php
/**
 * Plugin Activator class.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie;

use LightweightPlugins\Cookie\Database\Schema;

/**
 * Handles plugin activation.
 */
final class Activator {

	/**
	 * Activate the plugin.
	 *
	 * @return void
	 */
	public static function activate(): void {
		self::create_tables();
		self::set_defaults();
	}

	/**
	 * Create database tables.
	 *
	 * @return void
	 */
	private static function create_tables(): void {
		Schema::create_tables();
	}

	/**
	 * Set default options if not already set.
	 *
	 * @return void
	 */
	private static function set_defaults(): void {
		if ( false === get_option( Options::OPTION_NAME ) ) {
			add_option( Options::OPTION_NAME, Options::get_defaults() );
		}
	}
}
