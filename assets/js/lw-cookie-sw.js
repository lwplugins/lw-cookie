/**
 * LW Cookie — Service Worker for network-level blocking.
 *
 * Intercepts fetch requests and blocks domains that require
 * consent categories the user has not yet granted.
 *
 * The consent cookie is the source of truth. The state pages post here is
 * only a fast path: it can be stale (another tab posted last, or the page
 * that changed consent could not reach this worker), so before blocking a
 * request the worker re-reads the cookie and never blocks a category the
 * visitor has granted there.
 *
 * @package LightweightPlugins\Cookie
 */

/* eslint-disable no-restricted-globals */

'use strict';

// Blocked domain → category mapping.
var blockedDomains = {};

// Consent category → allowed state (as last posted by a page).
var consentState = {};

// Consent cookie name and policy version, for verifying against the cookie.
var consentCookie = '';
var policyVersion = '';

self.addEventListener(
	'install',
	function () {
		self.skipWaiting();
	}
);

self.addEventListener(
	'activate',
	function ( event ) {
		event.waitUntil( self.clients.claim() );
	}
);

/**
 * Receive consent state and blocked domains from the main page.
 */
self.addEventListener(
	'message',
	function ( event ) {
		var data = event.data || {};

		if ( data.type !== 'consent-update' ) {
			return;
		}

		consentState   = data.consent || {};
		blockedDomains = data.domains || {};

		// Pages served from a cache made before 1.7.6 post neither; keep what
		// a current page told us rather than losing the cookie check.
		consentCookie = data.cookieName || consentCookie;
		policyVersion = data.policyVersion || policyVersion;

		// Acknowledge, so the page can reload knowing the new state is in place.
		if ( event.ports && event.ports[0] ) {
			event.ports[0].postMessage( { type: 'consent-updated' } );
		}
	}
);

/**
 * Intercept fetch requests and block disallowed domains.
 */
self.addEventListener(
	'fetch',
	function ( event ) {
		var request = event.request;
		var url     = new URL( request.url );

		// Only check cross-origin requests.
		if ( url.origin === self.location.origin ) {
			return;
		}

		var hostname = url.hostname.replace( /^www\./, '' );
		var category = matchDomain( hostname, url.href );

		if ( ! category || consentState[category] ) {
			return;
		}

		// Would block: confirm against the live consent cookie first.
		event.respondWith(
			readCookieConsent().then(
				function ( categories ) {
					if ( categories && categories[category] ) {
						consentState = categories;
						return fetch( request );
					}

					return new Response( '', { status: 403, statusText: 'Blocked by LW Cookie' } );
				}
			)
		);
	}
);

/**
 * Read the consent categories from the consent cookie.
 *
 * Mirrors guard.js: base64-encoded JSON, valid only for the current policy
 * version. Resolves null when the cookie is missing, invalid or unreadable
 * (no Cookie Store API in this browser), which keeps the request blocked.
 *
 * @return {Promise<Object|null>} Consent categories or null.
 */
function readCookieConsent() {
	if ( ! consentCookie || ! policyVersion || ! self.cookieStore ) {
		return Promise.resolve( null );
	}

	try {
		return self.cookieStore.get( consentCookie ).then(
			function ( cookie ) {
				var data = JSON.parse( atob( cookie.value ) );

				return data.version === policyVersion && data.categories ? data.categories : null;
			}
		).catch(
			function () {
				return null;
			}
		);
	} catch ( e ) {
		return Promise.resolve( null );
	}
}

/**
 * Match a hostname against blocked domains.
 *
 * @param {string} hostname Request hostname (without www).
 * @param {string} fullUrl  Full request URL.
 * @return {string|null} Category or null.
 */
function matchDomain( hostname, fullUrl ) {
	var domains       = Object.keys( blockedDomains );
	var domainsLength = domains.length;

	for ( var i = 0; i < domainsLength; i++ ) {
		var domain = domains[i];

		if ( hostname === domain || hostname.endsWith( '.' + domain ) ) {
			return blockedDomains[domain];
		}

		// Path-based match (e.g. "google.com/maps").
		if ( domain.indexOf( '/' ) !== -1 && fullUrl.indexOf( domain ) !== -1 ) {
			return blockedDomains[domain];
		}
	}

	return null;
}
