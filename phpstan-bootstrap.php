<?php
/**
 * PHPStan-only bootstrap.
 *
 * The main plugin file defines these constants at runtime with dynamic
 * values (`plugin_dir_path()` / `plugin_dir_url()`), which static analysis
 * cannot resolve. Declaring them here — with representative string values —
 * lets PHPStan type them as strings wherever they are used, without running
 * any plugin code. Loaded via `bootstrapFiles`.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

define( 'LW_COOKIE_VERSION', '1.7.0' );
define( 'LW_COOKIE_FILE', __DIR__ . '/lw-cookie.php' );
define( 'LW_COOKIE_PATH', __DIR__ . '/' );
define( 'LW_COOKIE_URL', 'https://example.test/wp-content/plugins/lw-cookie/' );

// WordPress core cookie constant, not provided by the stubs.
define( 'COOKIEPATH', '/' );
