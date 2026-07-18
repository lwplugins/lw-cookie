<?php
/**
 * PHPUnit bootstrap file.
 *
 * Unit tests run WITHOUT WordPress: only the Composer autoloader is loaded,
 * which also pulls in Brain Monkey. WordPress functions are stubbed per test
 * via Brain\Monkey — the setUp()/tearDown() lifecycle lives in
 * tests/Unit/MonkeyTestCase.php.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

// Plugin constants that runtime code relies on (defined in lw-cookie.php at
// runtime; declared here so classes referencing them load under unit tests).
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/var/www/wp/' );
}
if ( ! defined( 'LW_COOKIE_PATH' ) ) {
	define( 'LW_COOKIE_PATH', dirname( __DIR__ ) . '/' );
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
