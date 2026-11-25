/**
 * The preferences modal follows the configured text color (banner.css).
 *
 * With a dark background and a light text color set under Appearance, every
 * text element of the modal must use the configured color, even when the
 * theme styles label, table cells and code itself (Zammad #7841835).
 *
 * Offline: the markup mirrors Renderer::output_preferences_modal(), the
 * stylesheet is read from disk, nothing is fetched.
 */
const fs = require( 'fs' );
const path = require( 'path' );
const { test, expect } = require( '@playwright/test' );

const CSS = fs.readFileSync( path.join( __dirname, '../../assets/css/banner.css' ), 'utf8' );

const TEXT = 'rgb(245, 245, 245)';

// Assets::output_custom_css() prints these after banner.css.
const CUSTOM = ':root{--lw-cookie-primary:#4f8cff;--lw-cookie-text:#f5f5f5;--lw-cookie-bg:#1e1e1e;--lw-cookie-radius:8px;}';

// Typical theme rules with class-level specificity.
const THEME = 'body{color:#222}' +
	'.site label, label{color:#333}' +
	'.site th, .site td, th, td{color:#444}' +
	'.site code, code{color:#c7254e;background:#f9f2f4}' +
	'.site p, p{color:#555}' +
	'.site h2, h2{color:#111}';

const MODAL = '<div id="lw-cookie-preferences" class="lw-cookie-modal" role="dialog">' +
	'<div class="lw-cookie-modal-overlay"></div>' +
	'<div class="lw-cookie-modal-content">' +
	'<h2 class="lw-cookie-modal-title">Cookie Preferences</h2>' +
	'<div class="lw-cookie-categories"><div class="lw-cookie-category">' +
	'<div class="lw-cookie-category-header"><label class="lw-cookie-category-label">' +
	'<input type="checkbox" checked disabled>' +
	'<span class="lw-cookie-category-name">Necessary</span>' +
	'<span class="lw-cookie-required">(Required)</span></label></div>' +
	'<p class="lw-cookie-category-desc">Needed for the site to work.</p>' +
	'<details class="lw-cookie-category-details" open><summary>Show 1 cookie</summary>' +
	'<table class="lw-cookie-category-cookies"><thead><tr><th scope="col">Cookie</th></tr></thead>' +
	'<tbody><tr><td data-label="Cookie"><code>lw_cookie_consent</code></td></tr></tbody></table>' +
	'</details></div></div></div></div>';

const NOTICE = '<div id="lw-cookie-notice" class="lw-cookie-notice lw-cookie-pos-bottom lw-cookie-layout-bar">' +
	'<div class="lw-cookie-notice-inner"><div class="lw-cookie-content">' +
	'<h2 class="lw-cookie-title">We use cookies</h2>' +
	'<p class="lw-cookie-message">Choose what you allow.</p></div></div></div>';

test( 'the banner title and message use the configured text color', async ( { page } ) => {
	await page.setContent( '<!doctype html><html><head><style>' + THEME + '</style><style>' + CSS + '</style><style>' + CUSTOM + '</style></head>' +
		'<body><div class="site">' + NOTICE + '</div></body></html>' );

	for ( const selector of [ '.lw-cookie-title', '.lw-cookie-message' ] ) {
		const color = await page.locator( selector ).evaluate( ( el ) => getComputedStyle( el ).color );
		expect( color, selector ).toBe( TEXT );
	}
} );

test( 'every text element of the preferences modal uses the configured text color', async ( { page } ) => {
	await page.setContent( '<!doctype html><html><head><style>' + THEME + '</style><style>' + CSS + '</style><style>' + CUSTOM + '</style></head>' +
		'<body><div class="site">' + MODAL + '</div></body></html>' );

	const selectors = [
		'.lw-cookie-modal-title',
		'.lw-cookie-category-name',
		'.lw-cookie-required',
		'.lw-cookie-category-desc',
		'.lw-cookie-category-cookies th',
		'.lw-cookie-category-cookies td',
		'.lw-cookie-category-cookies td code',
	];

	for ( const selector of selectors ) {
		const color = await page.locator( selector ).first().evaluate( ( el ) => getComputedStyle( el ).color );
		expect( color, selector ).toBe( TEXT );
	}

	// The theme's light code background must not sit behind light text.
	const codeBg = await page.locator( '.lw-cookie-category-cookies td code' ).evaluate( ( el ) => getComputedStyle( el ).backgroundColor );
	expect( codeBg ).not.toBe( 'rgb(249, 242, 244)' );
} );
