/**
 * When saving consent reloads the page (consent.js + guard.js).
 *
 * A reload is only worth it when a newly granted category has something
 * blocked on the page that cannot be restored in place: a script reload
 * replaces document.referrer with the page's own URL, so analytics loaded
 * after consent loses the visitor's real traffic source (#20).
 *
 * The page is served from a fake origin, the guard is inlined in <head> as
 * GuardScript.php does, and the test answers every other request itself.
 */
const fs = require( 'fs' );
const path = require( 'path' );
const { test, expect } = require( '@playwright/test' );

const ASSETS = path.join( __dirname, '../../assets/js' );
const GUARD = fs.readFileSync( path.join( ASSETS, 'guard.js' ), 'utf8' );
const CONSENT = fs.readFileSync( path.join( ASSETS, 'consent.js' ), 'utf8' );
// The inline click handler that stands in while consent.js is delayed by a cache plugin.
const FALLBACK = /<<<'JS'\n([\s\S]*?)\nJS;/.exec(
	fs.readFileSync( path.join( __dirname, '../../includes/Banner/InlineFallback.php' ), 'utf8' )
)[ 1 ];

const ORIGIN = 'https://shop.example.test';
const PAGE = ORIGIN + '/shop/';
const REFERRER = 'https://search.example.org/';
const POLICY = '1.0';

const DOMAINS = {
	'connect.facebook.net': 'marketing',
	'px.ads.example.test': 'marketing',
	'googletagmanager.com': 'analytics',
};

const FB_SCRIPT = 'https://connect.facebook.net/en_US/fbevents.js';
const GA_SCRIPT = 'https://www.googletagmanager.com/gtag/js?id=G-TEST123456';

const BANNER = '<div id="lw-cookie-notice">' +
	'<button type="button" data-lw-cookie-accept>Accept All</button>' +
	'<button type="button" data-lw-cookie-reject>Reject All</button></div>';

/**
 * Serve the page and count how often the document itself is requested.
 *
 * @param {import('@playwright/test').Page} page
 * @param {Object} options
 * @param {boolean} options.scriptBlocking Advanced → Script Blocking.
 * @param {string}  options.head           Markup after the guard in <head>.
 * @param {string}  options.body           Markup before the banner in <body>.
 * @param {Object}  options.consent        Stored consent categories, if any.
 * @param {Object}  options.trackers       Host → status for tracker requests (others are aborted).
 * @param {boolean} options.consentJs      Load consent.js (false: only the inline fallback).
 * @return {Promise<{documents: () => number, errors: string[]}>}
 */
async function load( page, options ) {
	const opts = Object.assign( { scriptBlocking: true, head: '', body: '', consent: null, trackers: {}, consentJs: true }, options );
	const errors = [];
	let documents = 0;

	page.on( 'pageerror', ( err ) => errors.push( err.message ) );

	const guardCfg = { cookieName: 'lw_cookie_consent', policyVersion: POLICY, domains: DOMAINS, cookies: {}, swUrl: '', scriptBlocking: opts.scriptBlocking, contentBlocking: true };
	const consentCfg = { cookieName: 'lw_cookie_consent', policyVersion: POLICY, restUrl: ORIGIN + '/wp-json/lw-cookie/v1/consent' };

	const html = '<!doctype html><html><head>' +
		'<script id="lw-cookie-guard">window.__lwGuardCfg=' + JSON.stringify( guardCfg ) + ';' + GUARD + '</script>' +
		opts.head +
		'</head><body><p>Shop</p>' + opts.body + BANNER +
		'<script>window.lwCookieConfig=' + JSON.stringify( consentCfg ) + ';</script>' +
		( opts.consentJs ? '<script src="/consent.js"></script>' : '<script id="lw-cookie-fallback">' + FALLBACK + '</script>' ) +
		'</body></html>';

	await page.route( '**/*', ( route ) => {
		const url = new URL( route.request().url() );
		if ( url.origin === ORIGIN ) {
			if ( url.pathname === '/consent.js' ) {
				return route.fulfill( { body: CONSENT, contentType: 'text/javascript' } );
			}
			if ( url.pathname.indexOf( '/wp-json/' ) === 0 ) {
				return route.fulfill( { body: '{"success":true}', contentType: 'application/json' } );
			}
			documents++;
			return route.fulfill( { body: html, contentType: 'text/html' } );
		}
		const host = url.hostname.replace( /^www\./, '' );
		if ( Object.prototype.hasOwnProperty.call( opts.trackers, host ) ) {
			return route.fulfill( { status: opts.trackers[ host ], body: '', contentType: 'text/javascript' } );
		}
		return route.abort();
	} );

	if ( opts.consent ) {
		const value = Buffer.from( JSON.stringify( { id: 'test', version: POLICY, timestamp: 1, categories: opts.consent } ) ).toString( 'base64' );
		await page.context().addCookies( [ { name: 'lw_cookie_consent', value, url: ORIGIN } ] );
	}

	await page.goto( PAGE, { referer: REFERRER } );
	await page.waitForFunction( ( withConsentJs ) => document.readyState === 'complete' && ( ! withConsentJs || !! window.LWCookie ), opts.consentJs );

	return { documents: () => documents, errors };
}

/**
 * Run an action and report whether the page reloaded afterwards.
 *
 * consent.js waits at least 100 ms (longer for a Service Worker) before it
 * reloads; one second leaves ample room for that.
 *
 * @param {import('@playwright/test').Page} page
 * @param {Function} action
 * @return {Promise<boolean>}
 */
async function reloadsAfter( page, action ) {
	await page.evaluate( () => {
		window.__lwTestMarker = true;
	} );
	await action();
	await page.waitForTimeout( 1000 );
	await page.waitForFunction( () => document.readyState === 'complete' );
	return page.evaluate( () => ! window.__lwTestMarker );
}

const ALL = { necessary: true, functional: true, analytics: true, marketing: true };

function acceptAll( page ) {
	return () => page.click( '[data-lw-cookie-accept]' );
}

test( 'Script Blocking off: Accept All does not reload, the referrer stays', async ( { page } ) => {
	const site = await load( page, {
		scriptBlocking: false,
		head: '<script async src="' + GA_SCRIPT + '"></script>',
		trackers: { 'googletagmanager.com': 200 },
	} );

	expect( await reloadsAfter( page, acceptAll( page ) ) ).toBe( false );
	expect( site.documents() ).toBe( 1 );
	expect( await page.evaluate( () => document.referrer ) ).toBe( REFERRER );
	expect( site.errors ).toEqual( [] );
} );

test( 'Script Blocking on, nothing of an optional category on the page: no reload', async ( { page } ) => {
	const site = await load( page, {} );

	expect( await reloadsAfter( page, acceptAll( page ) ) ).toBe( false );
	expect( site.documents() ).toBe( 1 );
} );

test( 'a tracker script blocked by the guard: Accept All reloads', async ( { page } ) => {
	const site = await load( page, { head: '<script src="' + FB_SCRIPT + '"></script>' } );

	expect( await page.evaluate( () => window.__lwGuard.hasBlockedScripts( 'marketing' ) ) ).toBe( true );
	expect( await reloadsAfter( page, acceptAll( page ) ) ).toBe( true );
	expect( site.documents() ).toBe( 2 );
} );

test( 'a server-rendered placeholder (ScriptTagBlocker) counts as blocked: Accept All reloads', async ( { page } ) => {
	const site = await load( page, {
		head: '<script type="text/plain" data-lw-blocked="1" data-lw-category="marketing" data-lw-original-src="' + FB_SCRIPT + '" id="fb-js"></script>' +
			'<script data-lw-hold="1" id="fb-js-after">window.__heldRan = true;</script>',
	} );

	expect( await page.evaluate( () => window.__lwGuard.hasBlockedScripts( 'marketing' ) ) ).toBe( true );
	expect( await reloadsAfter( page, acceptAll( page ) ) ).toBe( true );
	expect( site.documents() ).toBe( 2 );
} );

test( 'a request only the Service Worker blocked (no marker in the DOM): Accept All reloads', async ( { page } ) => {
	// new Image() never enters the DOM, so only the worker sees it; the 403
	// is what the worker answers in place of a blocked request.
	const site = await load( page, {
		body: '<script>new Image().src = "https://px.ads.example.test/p.gif?ev=PageView";</script>',
		trackers: { 'px.ads.example.test': 403 },
	} );
	await page.waitForFunction( () => performance.getEntriesByType( 'resource' ).some( ( e ) => e.name.indexOf( 'px.ads.example.test' ) !== -1 ) );

	expect( await page.evaluate( () => window.__lwGuard.hasBlockedScripts( 'marketing' ) ) ).toBe( true );
	expect( await reloadsAfter( page, acceptAll( page ) ) ).toBe( true );
	expect( site.documents() ).toBe( 2 );
} );

test( 'a blocked tracking pixel <img> counts as blocked', async ( { page } ) => {
	await load( page, { body: '<img alt="" src="https://px.ads.example.test/tr.gif">' } );

	expect( await page.evaluate( () => window.__lwGuard.hasBlockedScripts( 'marketing' ) ) ).toBe( true );
	expect( await page.evaluate( () => window.__lwGuard.hasBlockedScripts( 'analytics' ) ) ).toBe( false );
} );

test( 'granting only a category with nothing blocked does not reload', async ( { page } ) => {
	const site = await load( page, { head: '<script src="' + FB_SCRIPT + '"></script>' } );

	const reloaded = await reloadsAfter( page, () => page.evaluate( () => {
		window.LWCookie.saveConsent( { necessary: true, functional: false, analytics: true, marketing: false }, 'customize' );
	} ) );

	expect( reloaded ).toBe( false );
	expect( site.documents() ).toBe( 1 );
} );

test( 're-saving unchanged consent does not reload', async ( { page } ) => {
	const site = await load( page, {
		consent: ALL,
		head: '<script src="' + FB_SCRIPT + '"></script><script async src="' + GA_SCRIPT + '"></script>',
		trackers: { 'connect.facebook.net': 200, 'googletagmanager.com': 200 },
	} );

	expect( await reloadsAfter( page, () => page.evaluate( () => window.LWCookie.acceptAll() ) ) ).toBe( false );
	expect( site.documents() ).toBe( 1 );
} );

test( 'an older cached guard without hasBlockedScripts: every grant still reloads', async ( { page } ) => {
	const site = await load( page, { scriptBlocking: false } );
	await page.evaluate( () => {
		delete window.__lwGuard.hasBlockedScripts;
	} );

	expect( await reloadsAfter( page, acceptAll( page ) ) ).toBe( true );
	expect( site.documents() ).toBe( 2 );
} );

test( 'Reject All never reloads', async ( { page } ) => {
	const site = await load( page, { head: '<script src="' + FB_SCRIPT + '"></script>' } );

	expect( await reloadsAfter( page, () => page.click( '[data-lw-cookie-reject]' ) ) ).toBe( false );
	expect( site.documents() ).toBe( 1 );
} );

test.describe( 'inline fallback (consent.js delayed by a cache plugin)', () => {
	test( 'Script Blocking off: Accept All does not reload, the referrer stays', async ( { page } ) => {
		const site = await load( page, { consentJs: false, scriptBlocking: false } );

		expect( await reloadsAfter( page, acceptAll( page ) ) ).toBe( false );
		expect( site.documents() ).toBe( 1 );
		expect( await page.evaluate( () => document.referrer ) ).toBe( REFERRER );
	} );

	test( 'a blocked tracker script: Accept All reloads', async ( { page } ) => {
		const site = await load( page, { consentJs: false, head: '<script src="' + FB_SCRIPT + '"></script>' } );

		expect( await reloadsAfter( page, acceptAll( page ) ) ).toBe( true );
		expect( site.documents() ).toBe( 2 );
	} );

	test( 'an older cached guard without hasBlockedScripts: still reloads', async ( { page } ) => {
		const site = await load( page, { consentJs: false, scriptBlocking: false } );
		await page.evaluate( () => {
			delete window.__lwGuard.hasBlockedScripts;
		} );

		expect( await reloadsAfter( page, acceptAll( page ) ) ).toBe( true );
		expect( site.documents() ).toBe( 2 );
	} );
} );
