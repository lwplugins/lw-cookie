/**
 * When the lwCookieConsent event fires (consent.js + guard.js).
 *
 * A theme or plugin that loads a tracker from a lwCookieConsent listener
 * (e.g. gtm.js after consent) must not reach a Service Worker that still
 * holds the old consent state: the worker would answer 403 and the first
 * page view of every new visitor would be lost (#28).
 *
 * The Service Worker is a stand-in with the real worker's contract: it takes
 * the 'consent-update' message, applies it after a delay, then acknowledges
 * on the given port. Until then the test answers the tracker host with the
 * worker's 403, just as lw-cookie-sw.js would. Offline: the page is served
 * from a fake origin and every other request is aborted.
 */
const fs = require( 'fs' );
const path = require( 'path' );
const { test, expect } = require( '@playwright/test' );

const ASSETS = path.join( __dirname, '../../assets/js' );
const GUARD = fs.readFileSync( path.join( ASSETS, 'guard.js' ), 'utf8' );
const CONSENT = fs.readFileSync( path.join( ASSETS, 'consent.js' ), 'utf8' );

const ORIGIN = 'https://shop.example.test';
const PAGE = ORIGIN + '/shop/';
const POLICY = '1.0';
const DOMAINS = { 'googletagmanager.com': 'analytics' };
const GTM = 'https://www.googletagmanager.com/gtm.js?id=GTM-TEST123';

const BANNER = '<div id="lw-cookie-notice">' +
	'<button type="button" data-lw-cookie-accept>Accept All</button>' +
	'<button type="button" data-lw-cookie-reject>Reject All</button></div>';

// The pattern from #28: load GTM only once analytics is granted.
const LISTENER = '<script>window.addEventListener("lwCookieConsent",function(e){' +
	'window.__events=(window.__events||[]).concat([e.detail]);' +
	'if(e.detail&&e.detail.categories.analytics){var s=document.createElement("script");s.async=true;s.src=' + JSON.stringify( GTM ) + ';document.head.appendChild(s);}' +
	'});</script>';

/**
 * Serve the page with an optional stand-in Service Worker.
 *
 * @param {import('@playwright/test').Page} page
 * @param {Object}  options
 * @param {boolean} options.sw        Register the stand-in worker (guard swUrl set).
 * @param {number}  options.swDelay   Milliseconds before the worker applies and acks; -1 never acks.
 * @return {Promise<{worker: Object, gtm: number[], errors: string[]}>}
 */
async function load( page, options ) {
	const opts = Object.assign( { sw: true, swDelay: 300 }, options );
	const worker = { consent: { necessary: true }, messages: 0 };
	const gtm = [];
	const errors = [];

	page.on( 'pageerror', ( err ) => errors.push( err.message ) );

	await page.exposeFunction( '__swApply', ( consent ) => {
		worker.consent = consent;
		worker.messages++;
	} );

	if ( opts.sw ) {
		await page.addInitScript( ( delay ) => {
			const active = {
				scriptURL: 'https://shop.example.test/lw-cookie-sw.js',
				postMessage( message, ports ) {
					if ( ! message || message.type !== 'consent-update' || delay < 0 ) {
						return;
					}
					setTimeout( () => {
						window.__swApply( message.consent ).then( () => {
							if ( ports && ports[ 0 ] ) {
								ports[ 0 ].postMessage( { type: 'consent-updated' } );
							}
						} );
					}, delay );
				},
			};
			const reg = { active, unregister: () => Promise.resolve( true ) };
			const container = {
				controller: null,
				ready: Promise.resolve( reg ),
				register: () => Promise.resolve( reg ),
				getRegistration: () => Promise.resolve( reg ),
			};
			Object.defineProperty( Navigator.prototype, 'serviceWorker', { get: () => container, configurable: true } );
		}, opts.swDelay );
	}

	const guardCfg = { cookieName: 'lw_cookie_consent', policyVersion: POLICY, domains: DOMAINS, cookies: {}, swUrl: opts.sw ? ORIGIN + '/lw-cookie-sw.js' : '', scriptBlocking: true, contentBlocking: true };
	const consentCfg = { cookieName: 'lw_cookie_consent', policyVersion: POLICY, restUrl: ORIGIN + '/wp-json/lw-cookie/v1/consent' };

	const html = '<!doctype html><html><head>' +
		'<script id="lw-cookie-guard">window.__lwGuardCfg=' + JSON.stringify( guardCfg ) + ';' + GUARD + '</script>' +
		LISTENER +
		'</head><body><p>Shop</p>' + BANNER +
		'<script>window.lwCookieConfig=' + JSON.stringify( consentCfg ) + ';</script>' +
		'<script src="/consent.js"></script>' +
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
			return route.fulfill( { body: html, contentType: 'text/html' } );
		}
		if ( url.hostname === 'www.googletagmanager.com' ) {
			// What lw-cookie-sw.js answers while it holds a state without analytics.
			const status = ! opts.sw || worker.consent.analytics ? 200 : 403;
			gtm.push( status );
			return route.fulfill( { status, body: status === 200 ? 'window.__gtmLoaded = true;' : '', contentType: 'text/javascript' } );
		}
		return route.abort();
	} );

	await page.goto( PAGE );
	await page.waitForFunction( () => document.readyState === 'complete' && !! window.LWCookie );
	if ( opts.sw && opts.swDelay >= 0 ) {
		// The guard's own hand-off on load (navigator.serviceWorker.ready).
		await expect.poll( () => worker.messages ).toBe( 1 );
	}

	return { worker, gtm, errors };
}

test( 'a script loaded from a lwCookieConsent listener is not blocked by the Service Worker (#28)', async ( { page } ) => {
	const site = await load( page, {} );

	await page.click( '[data-lw-cookie-accept]' );
	await expect.poll( () => site.gtm.length, { timeout: 5000 } ).toBe( 1 );

	expect( site.gtm ).toEqual( [ 200 ] );
	await page.waitForFunction( () => window.__gtmLoaded === true, null, { timeout: 5000 } );
	expect( site.errors ).toEqual( [] );
} );

test( 'detail.ready resolves once the Service Worker holds the new state', async ( { page } ) => {
	const site = await load( page, { swDelay: 300 } );

	const result = await page.evaluate( () => new Promise( ( resolve ) => {
		window.addEventListener( 'lwCookieConsent', ( e ) => {
			const ready = e.detail.ready;
			if ( ! ready || typeof ready.then !== 'function' ) {
				resolve( { hasReady: false } );
				return;
			}
			ready.then( () => resolve( { hasReady: true } ) );
		}, { once: true } );
		window.LWCookie.acceptAll();
	} ) );

	expect( result.hasReady ).toBe( true );
	expect( site.worker.consent.analytics ).toBe( true );
} );

test( 'a Service Worker that never answers does not hold the event back for good', async ( { page } ) => {
	const site = await load( page, { swDelay: -1 } );

	await page.click( '[data-lw-cookie-accept]' );
	// The guard caps the hand-off at SW_SYNC_TIMEOUT (at most 2 s).
	await page.waitForFunction( () => window.__events && window.__events.length === 1, null, { timeout: 5000 } );

	expect( await page.evaluate( () => window.__events[ 0 ].action ) ).toBe( 'accept_all' );
	expect( site.errors ).toEqual( [] );
} );

test( 'without a Service Worker the event fires at once, with a resolved detail.ready', async ( { page } ) => {
	const site = await load( page, { sw: false } );

	const result = await page.evaluate( () => {
		let detail = null;
		window.addEventListener( 'lwCookieConsent', ( e ) => {
			detail = e.detail;
		}, { once: true } );
		window.LWCookie.acceptAll();
		// Synchronous: no worker to wait for.
		if ( ! detail ) {
			return { fired: false };
		}
		return Promise.resolve( detail.ready ).then( () => ( { fired: true, hasReady: !! detail.ready && typeof detail.ready.then === 'function' } ) );
	} );

	expect( result ).toEqual( { fired: true, hasReady: true } );
	await page.waitForFunction( () => window.__gtmLoaded === true );
	expect( site.gtm ).toEqual( [ 200 ] );
	expect( site.errors ).toEqual( [] );
} );

test( 'Reject All also waits for the worker and carries detail.ready', async ( { page } ) => {
	const site = await load( page, {} );

	await page.click( '[data-lw-cookie-reject]' );
	await page.waitForFunction( () => window.__events && window.__events.length === 1, null, { timeout: 5000 } );

	const ready = await page.evaluate( () => {
		const d = window.__events[ 0 ];
		return d.action === 'reject_all' && !! d.ready && typeof d.ready.then === 'function';
	} );
	expect( ready ).toBe( true );
	// By the time listeners hear about it, the worker already has the new state.
	expect( site.worker.messages ).toBe( 2 );
	expect( site.gtm ).toEqual( [] );
} );
