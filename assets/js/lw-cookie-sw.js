/**
 * LW Cookie — Service Worker for network-level blocking.
 *
 * Intercepts fetch requests and blocks domains that require
 * consent categories the user has not yet granted.
 *
 * @package LightweightPlugins\Cookie
 */

/* eslint-disable no-restricted-globals */

'use strict';

// Blocked domain → category mapping.
var blockedDomains = {};

// Consent category → allowed state.
var consentState = {};

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

		if ( data.type === 'consent-update' ) {
			consentState   = data.consent || {};
			blockedDomains = data.domains || {};
		}
	}
);

/**
 * Intercept fetch requests and block disallowed domains.
 */
self.addEventListener(
	'fetch',
	function ( event ) {
		var url = new URL( event.request.url );

		// Only check cross-origin requests.
		if ( url.origin === self.location.origin ) {
			return;
		}

		var hostname = url.hostname.replace( /^www\./, '' );
		var category = matchDomain( hostname, url.href );

		if ( ! category ) {
			return;
		}

		// Block if category is not consented.
		if ( ! consentState[category] ) {
			event.respondWith(
				new Response( '', { status: 403, statusText: 'Blocked by LW Cookie' } )
			);
		}
	}
);

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
