<?php
/**
 * LW Plugins parent page (compatibility shim).
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Admin;

use LightweightPlugins\Cookie\Admin\Hub\Hub;
use LightweightPlugins\Cookie\Admin\Hub\Page;
use LightweightPlugins\Cookie\Admin\Hub\Registry;

/**
 * The "LW Plugins" page itself is the shared hub (Admin\Hub, synced from
 * lwplugins/admin-hub). This class keeps the old entry points working:
 * SLUG for add_submenu_page(), maybe_register() from the menu callbacks and
 * get_plugins_registry().
 */
final class ParentPage {

	/**
	 * Parent menu slug.
	 */
	public const SLUG = Page::SLUG;

	/**
	 * The normalized LW plugin registry.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function get_plugins_registry(): array {
		return Registry::get();
	}

	/**
	 * Called from the plugin's admin_menu callback. The winning hub copy has
	 * already added the page at priority 5; this only covers a copy that runs
	 * outside that order.
	 *
	 * @return void
	 */
	public static function maybe_register(): void {
		NoticeManager::init();
		Hub::ensure_menu();
	}
}
