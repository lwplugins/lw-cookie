/**
 * LW Cookie - Consent Manager
 *
 * @package LightweightPlugins\Cookie
 */

(function () {
	'use strict';

	const config      = window.lwCookieConfig || {};
	const COOKIE_NAME = 'lw_cookie_consent';

	/**
	 * Initialize the consent manager.
	 */
	function init() {
		bindEvents();

		// If we need to show banner (no valid consent), make sure it's visible.
		if ( ! config.isValid) {
			showBanner();
		}
	}

	/**
	 * Bind event listeners.
	 */
	function bindEvents() {
		// Accept all button.
		document.querySelectorAll( '[data-lw-cookie-accept]' ).forEach(
			function (btn) {
				btn.addEventListener( 'click', acceptAll );
			}
		);

		// Reject all button.
		document.querySelectorAll( '[data-lw-cookie-reject]' ).forEach(
			function (btn) {
				btn.addEventListener( 'click', rejectAll );
			}
		);

		// Customize button.
		document.querySelectorAll( '[data-lw-cookie-customize]' ).forEach(
			function (btn) {
				btn.addEventListener( 'click', openPreferences );
			}
		);

		// Save preferences button.
		document.querySelectorAll( '[data-lw-cookie-save]' ).forEach(
			function (btn) {
				btn.addEventListener( 'click', savePreferences );
			}
		);

		// Close modal.
		document.querySelectorAll( '[data-lw-cookie-close-modal]' ).forEach(
			function (el) {
				el.addEventListener( 'click', closePreferences );
			}
		);

		// Open preferences (floating button or link).
		document.querySelectorAll( '[data-lw-cookie-open-preferences]' ).forEach(
			function (btn) {
				btn.addEventListener( 'click', openPreferences );
			}
		);

		// ESC key closes modal.
		document.addEventListener(
			'keydown',
			function (e) {
				if (e.key === 'Escape') {
					closePreferences();
				}
			}
		);
	}

	/**
	 * Show the cookie banner.
	 */
	function showBanner() {
		const banner = document.getElementById( 'lw-cookie-banner' );
		if (banner) {
			banner.classList.remove( 'lw-cookie-hidden' );
			banner.style.display = '';
		}
	}

	/**
	 * Hide the cookie banner.
	 */
	function hideBanner() {
		const banner = document.getElementById( 'lw-cookie-banner' );
		if (banner) {
			banner.classList.add( 'lw-cookie-hidden' );
		}
	}

	/**
	 * Open preferences modal.
	 */
	function openPreferences() {
		hideBanner();
		const modal = document.getElementById( 'lw-cookie-preferences' );
		if (modal) {
			modal.style.display = 'flex';
			modal.classList.remove( 'lw-cookie-hidden' );

			// Set checkbox states based on current consent.
			setCheckboxStates();

			// Focus first interactive element.
			const firstCheckbox = modal.querySelector( 'input[type="checkbox"]:not(:disabled)' );
			if (firstCheckbox) {
				firstCheckbox.focus();
			}
		}
	}

	/**
	 * Close preferences modal.
	 */
	function closePreferences() {
		const modal = document.getElementById( 'lw-cookie-preferences' );
		if (modal) {
			modal.style.display = 'none';
			modal.classList.add( 'lw-cookie-hidden' );
		}

		// Show banner again if no consent.
		if ( ! config.isValid && ! hasConsent()) {
			showBanner();
		}
	}

	/**
	 * Set checkbox states based on current consent.
	 */
	function setCheckboxStates() {
		const categories = config.categories || {};

		Object.keys( categories ).forEach(
			function (key) {
				const checkbox = document.querySelector( '[data-category="' + key + '"]' );
				if (checkbox && ! checkbox.disabled) {
					checkbox.checked = categories[key];
				}
			}
		);
	}

	/**
	 * Accept all cookies.
	 */
	function acceptAll() {
		const categories = {
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
		const categories = {
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
		const categories = {
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
	function isChecked(category) {
		const checkbox = document.querySelector( '[data-category="' + category + '"]' );
		return checkbox ? checkbox.checked : false;
	}

	/**
	 * Save consent to server and cookie.
	 *
	 * @param {Object} categories Category consent states.
	 * @param {string} actionType Action type.
	 */
	function saveConsent(categories, actionType) {
		// Save to cookie immediately (for instant effect).
		saveCookie( categories );

		// Update config.
		config.categories = categories;
		config.isValid    = true;

		// Hide banner.
		hideBanner();

		// Send to server for logging.
		const formData = new FormData();
		formData.append( 'action', 'lw_cookie_save_consent' );
		formData.append( 'nonce', config.nonce );
		formData.append( 'categories', JSON.stringify( categories ) );
		formData.append( 'action_type', actionType );

		fetch(
			config.ajaxUrl,
			{
				method: 'POST',
				body: formData,
				credentials: 'same-origin'
			}
		).catch(
			function (error) {
				console.error( 'LW Cookie: Failed to log consent', error );
			}
		);

		// Dispatch event for other scripts.
		dispatchConsentEvent( categories, actionType );

		// Reload page if scripts need to load.
		if (actionType === 'accept_all' || (actionType === 'customize' && hasAnalyticsOrMarketing( categories ))) {
			// Give a moment for cookie to be set, then reload.
			setTimeout(
				function () {
					window.location.reload();
				},
				100
			);
		}
	}

	/**
	 * Check if analytics or marketing is enabled.
	 *
	 * @param {Object} categories Category states.
	 * @return {boolean}
	 */
	function hasAnalyticsOrMarketing(categories) {
		return categories.analytics || categories.marketing;
	}

	/**
	 * Save consent to cookie.
	 *
	 * @param {Object} categories Category consent states.
	 */
	function saveCookie(categories) {
		const consent = {
			id: generateUUID(),
			version: config.policyVersion,
			timestamp: Math.floor( Date.now() / 1000 ),
			categories: categories
		};

		const cookieValue = btoa( JSON.stringify( consent ) );
		const expires     = new Date();
		expires.setDate( expires.getDate() + 365 );

		document.cookie = COOKIE_NAME + '=' + cookieValue +
			';expires=' + expires.toUTCString() +
			';path=/;SameSite=Lax';
	}

	/**
	 * Check if consent cookie exists.
	 *
	 * @return {boolean}
	 */
	function hasConsent() {
		return document.cookie.indexOf( COOKIE_NAME + '=' ) !== -1;
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

		for (var i = 0; i < cookiesLength; i++) {
			var cookie     = cookies[i];
			var eqPos      = cookie.indexOf( '=' );
			var cookieName = eqPos > -1 ? cookie.substr( 0, eqPos ).trim() : cookie.trim();

			if ( ! cookieName) {
				continue;
			}

			// Delete for each path.
			for (var j = 0; j < pathsLength; j++) {
				// Delete without domain.
				document.cookie = cookieName + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=' + paths[j];

				// Delete with current domain.
				document.cookie = cookieName + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=' + paths[j] + ';domain=' + domain;

				// Delete with dot prefix domain (for subdomains).
				if (domain.indexOf( 'www.' ) !== 0) {
					document.cookie = cookieName + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=' + paths[j] + ';domain=.' + domain;
				}
			}
		}

		// Reset config state.
		config.categories = {};
		config.isValid    = false;

		// Reload page to show banner again.
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
			function (c) {
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
	function dispatchConsentEvent(categories, actionType) {
		// 1. Custom Event for JavaScript listeners.
		const event = new CustomEvent(
			'lwCookieConsent',
			{
				detail: {
					categories: categories,
					action: actionType
				}
			}
		);
		window.dispatchEvent( event );

		// 2. dataLayer.push for GTM triggers.
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

		// 3. Google Consent Mode v2 update.
		if (typeof gtag === 'function') {
			gtag(
				'consent',
				'update',
				{
					'analytics_storage': categories.analytics ? 'granted' : 'denied',
					'ad_storage': categories.marketing ? 'granted' : 'denied',
					'ad_user_data': categories.marketing ? 'granted' : 'denied',
					'ad_personalization': categories.marketing ? 'granted' : 'denied'
				}
			);
		}

		// 4. Meta Pixel (Facebook) consent API.
		if (typeof fbq === 'function') {
			if (categories.marketing) {
				fbq( 'consent', 'grant' );
			} else {
				fbq( 'consent', 'revoke' );
			}
		}
	}

	// Initialize on DOM ready.
	if (document.readyState === 'loading') {
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
		getConsent: function () {
			return config.categories;
		},
		isAllowed: function (category) {
			return config.categories && config.categories[category] === true;
		}
	};

})();
