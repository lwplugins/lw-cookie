<?php
/**
 * Settings Tab Interface.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin\Settings;

/**
 * Interface for settings tabs.
 */
interface TabInterface {

	/**
	 * Get the tab slug.
	 *
	 * @return string
	 */
	public function get_slug(): string;

	/**
	 * Get the tab label.
	 *
	 * @return string
	 */
	public function get_label(): string;

	/**
	 * Get the tab icon (dashicon class).
	 *
	 * @return string
	 */
	public function get_icon(): string;

	/**
	 * Render the tab content.
	 *
	 * @return void
	 */
	public function render(): void;
}
