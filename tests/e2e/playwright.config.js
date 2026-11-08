/**
 * Browser tests for assets/js/guard.js and assets/js/consent.js.
 *
 * Offline: the page is served from a fake origin and every other request is
 * blocked or answered by the test, so nothing leaves the machine.
 */
const { defineConfig } = require( '@playwright/test' );

module.exports = defineConfig( {
	testDir: __dirname,
	outputDir: __dirname + '/.results',
	reporter: process.env.CI ? 'github' : 'list',
	use: { browserName: 'chromium', headless: true },
} );
