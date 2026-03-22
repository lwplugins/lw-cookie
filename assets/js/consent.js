/**
 * LW Cookie — Consent Manager (v2.0 — cache-proof)
 *
 * Reads consent state from the browser cookie (not from PHP config).
 * Saves consent via REST API (not admin-ajax).
 * Integrates with guard.js via window.__lwGuard.refresh().
 *
 * @package LightweightPlugins\Cookie
 */

(function () {
	'use strict';

	var config      = window.lwCookieConfig || {};
	var COOKIE_NAME = config.cookieName || 'lw_cookie_consent';

	/**
	 * Read consent data from the browser cookie.
	 *
	 * @return {Object|null} Parsed consent or null.
	 */
	function readConsentCookie() {
		var match = document.cookie.match( '(?:^|; )' + COOKIE_NAME + '=([^;]*)' );
		if ( ! match ) {
			return null;
		}
		try {
			return JSON.parse( atob( match[1] ) );
		} catch ( e ) {
			return null;
		}
	}

	/**
	 * Check if stored consent matches current policy version.
	 */
	function isConsentValid() {
		var data = readConsentCookie();
		return data && data.version === config.policyVersion && data.categories;
	}

	/**
	 * Get current consent categories from cookie.
	 *
	 * @return {Object} categories or empty fallback.
	 */
	function getConsentCategories() {
		var data = readConsentCookie();
		if ( data && data.version === config.policyVersion && data.categories ) {
			return data.categories;
		}
		return { necessary: true };
	}

	/**
	 * Initialize the consent manager.
	 */
	function init() {
		bindEvents();
	}

	/**
	 * Bind event listeners.
	 */
	function bindEvents() {
		document.querySelectorAll( '[data-lw-cookie-accept]' ).forEach(
			function ( btn ) {
				btn.addEventListener( 'click', acceptAll );
			}
		);

		document.querySelectorAll( '[data-lw-cookie-reject]' ).forEach(
			function ( btn ) {
				btn.addEventListener( 'click', rejectAll );
			}
		);

		document.querySelectorAll( '[data-lw-cookie-customize]' ).forEach(
			function ( btn ) {
				btn.addEventListener( 'click', openPreferences );
			}
		);

		document.querySelectorAll( '[data-lw-cookie-save]' ).forEach(
			function ( btn ) {
				btn.addEventListener( 'click', savePreferences );
			}
		);

		document.querySelectorAll( '[data-lw-cookie-close-modal]' ).forEach(
			function ( el ) {
				el.addEventListener( 'click', closePreferences );
			}
		);

		document.querySelectorAll( '[data-lw-cookie-open-preferences]' ).forEach(
			function ( btn ) {
				btn.addEventListener( 'click', openPreferences );
			}
		);

		document.addEventListener(
			'keydown',
			function ( e ) {
				if ( e.key === 'Escape' ) {
					closePreferences();
				}
			}
		);
	}

	/**
	 * Show the cookie banner.
	 */
	function showBanner() {
		var banner = document.getElementById( 'lw-cookie-notice' );
		if ( banner ) {
			banner.classList.remove( 'lw-cookie-hidden' );
			banner.style.display = '';
		}
	}

	/**
	 * Hide the cookie banner.
	 */
	function hideBanner() {
		var banner = document.getElementById( 'lw-cookie-notice' );
		if ( banner ) {
			banner.classList.add( 'lw-cookie-hidden' );
		}
	}

	/**
	 * Open preferences modal.
	 */
	function openPreferences() {
		hideBanner();
		var modal = document.getElementById( 'lw-cookie-preferences' );
		if ( modal ) {
			modal.style.display = 'flex';
			modal.classList.remove( 'lw-cookie-hidden' );

			setCheckboxStates();

			var firstCheckbox = modal.querySelector( 'input[type="checkbox"]:not(:disabled)' );
			if ( firstCheckbox ) {
				firstCheckbox.focus();
			}
		}
	}

	/**
	 * Close preferences modal.
	 */
	function closePreferences() {
		var modal = document.getElementById( 'lw-cookie-preferences' );
		if ( modal ) {
			modal.style.display = 'none';
			modal.classList.add( 'lw-cookie-hidden' );
		}

		// Show banner again if no valid consent.
		if ( ! isConsentValid() ) {
			showBanner();
		}
	}

	/**
	 * Set checkbox states from cookie (not from PHP config).
	 */
	function setCheckboxStates() {
		var categories = getConsentCategories();

		Object.keys( categories ).forEach(
			function ( key ) {
				var checkbox = document.querySelector( '[data-category="' + key + '"]' );
				if ( checkbox && ! checkbox.disabled ) {
					checkbox.checked = categories[key];
				}
			}
		);
	}

	/**
	 * Accept all cookies.
	 */
	function acceptAll() {
		var categories = {
			necessary: true,
			functional: true,
			analytics: true,
			marketing: true
		};

		saveConsent( categories, 'accept_all' );
	}

	/**
	 * Reject all optional cookies.
	 */
	function rejectAll() {
		var categories = {
			necessary: true,
			functional: false,
			analytics: false,
			marketing: false
		};

		saveConsent( categories, 'reject_all' );
	}

	/**
	 * Save custom preferences.
	 */
	function savePreferences() {
		var categories = {
			necessary: true,
			functional: isChecked( 'functional' ),
			analytics: isChecked( 'analytics' ),
			marketing: isChecked( 'marketing' )
		};

		saveConsent( categories, 'customize' );
		closePreferences();
	}

	/**
	 * Check if a category checkbox is checked.
	 *
	 * @param {string} category Category key.
	 * @return {boolean}
	 */
	function isChecked( category ) {
		var checkbox = document.querySelector( '[data-category="' + category + '"]' );
		return checkbox ? checkbox.checked : false;
	}

	/**
	 * Save consent to cookie and server.
	 *
	 * @param {Object}  categories  Category consent states.
	 * @param {string}  actionType  Action type.
	 * @param {boolean} skipReload  Skip page reload.
	 */
	function saveConsent( categories, actionType, skipReload ) {
		// Save to cookie immediately.
		saveCookie( categories );

		// Hide banner.
		hideBanner();

		// Notify guard.js to update SW, GCM, observer, etc.
		if ( window.__lwGuard && window.__lwGuard.refresh ) {
			window.__lwGuard.refresh( categories );
		}

		// Send to server via REST API (fire-and-forget).
		if ( config.restUrl ) {
			fetch(
				config.restUrl,
				{
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify(
						{
							categories: categories,
							action_type: actionType
						}
					),
				credentials: 'same-origin'
				}
			).catch(
				function ( error ) {
					console.error( 'LW Cookie: Failed to log consent', error );
				}
			);
		}

		// Dispatch events for other scripts.
		dispatchConsentEvent( categories, actionType );

		// Reload only if scripts were previously blocked and now need loading.
		// The SW can block but cannot retroactively load scripts.
		if ( ! skipReload && needsReload( categories ) ) {
			setTimeout(
				function () {
					window.location.reload();
				},
				100
			);
		}
	}

	/**
	 * Check if a page reload is needed.
	 *
	 * Reload if any previously blocked category is now allowed,
	 * since blocked scripts cannot be un-blocked without reload.
	 *
	 * @param {Object} categories New categories.
	 * @return {boolean}
	 */
	function needsReload( categories ) {
		return categories.analytics || categories.marketing || categories.functional;
	}

	/**
	 * Save consent to cookie.
	 *
	 * @param {Object} categories Category consent states.
	 */
	function saveCookie( categories ) {
		var consent = {
			id: generateUUID(),
			version: config.policyVersion,
			timestamp: Math.floor( Date.now() / 1000 ),
			categories: categories
		};

		var cookieValue = btoa( JSON.stringify( consent ) );
		var duration    = config.consentDuration || 365;
		var expires     = new Date();
		expires.setDate( expires.getDate() + duration );

		document.cookie = COOKIE_NAME + '=' + cookieValue +
			';expires=' + expires.toUTCString() +
			';path=/;SameSite=Lax';
	}

	/**
	 * Delete all cookies for the current domain.
	 */
	function deleteAllCookies() {
		var cookies       = document.cookie.split( ';' );
		var domain        = window.location.hostname;
		var paths         = ['/', window.location.pathname];
		var cookiesLength = cookies.length;
		var pathsLength   = paths.length;

		for ( var i = 0; i < cookiesLength; i++ ) {
			var cookie     = cookies[i];
			var eqPos      = cookie.indexOf( '=' );
			var cookieName = eqPos > -1 ? cookie.substr( 0, eqPos ).trim() : cookie.trim();

			if ( ! cookieName ) {
				continue;
			}

			for ( var j = 0; j < pathsLength; j++ ) {
				document.cookie = cookieName + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=' + paths[j];
				document.cookie = cookieName + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=' + paths[j] + ';domain=' + domain;

				if ( domain.indexOf( 'www.' ) !== 0 ) {
					document.cookie = cookieName + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=' + paths[j] + ';domain=.' + domain;
				}
			}
		}

		window.location.reload();
	}

	/**
	 * Generate a UUID v4.
	 *
	 * @return {string}
	 */
	function generateUUID() {
		return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(
			/[xy]/g,
			function ( c ) {
				var r = Math.random() * 16 | 0;
				var v = c === 'x' ? r : (r & 0x3 | 0x8);
				return v.toString( 16 );
			}
		);
	}

	/**
	 * Dispatch consent event for other scripts.
	 *
	 * @param {Object} categories Category consent states.
	 * @param {string} actionType Action type.
	 */
	function dispatchConsentEvent( categories, actionType ) {
		var event = new CustomEvent(
			'lwCookieConsent',
			{
				detail: {
					categories: categories,
					action: actionType
				}
			}
		);
		window.dispatchEvent( event );

		window.dataLayer = window.dataLayer || [];
		window.dataLayer.push(
			{
				'event': 'lw_cookie_consent_update',
				'lw_cookie_consent': {
					'necessary': true,
					'functional': categories.functional || false,
					'analytics': categories.analytics || false,
					'marketing': categories.marketing || false
				},
				'lw_cookie_action': actionType
			}
		);

		// GCM v2 update is handled by guard.js.refresh() above.

		// Meta Pixel consent API.
		if ( typeof fbq === 'function' ) {
			if ( categories.marketing ) {
				fbq( 'consent', 'grant' );
			} else {
				fbq( 'consent', 'revoke' );
			}
		}
	}

	// Initialize on DOM ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

	// Expose API globally.
	window.LWCookie = {
		acceptAll: acceptAll,
		rejectAll: rejectAll,
		openPreferences: openPreferences,
		deleteAllCookies: deleteAllCookies,
		saveConsent: saveConsent,
		getConsent: function () {
			return getConsentCategories();
		},
		isAllowed: function ( category ) {
			var cats = getConsentCategories();
			return cats[category] === true;
		}
	};

})();
