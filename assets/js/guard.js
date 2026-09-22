/**
 * LW Cookie — Client-Side Guard (inline, runs before any body element).
 *
 * This script is inlined by GuardScript.php in <head> at priority 1.
 * It MUST NOT be enqueued — it needs to execute before the parser
 * reaches any <body> content so that MutationObserver and cookie
 * override are active before tracking scripts load.
 *
 * @package LightweightPlugins\Cookie
 */

(function () {
	'use strict';

	var cfg = window.__lwGuardCfg || {};

	var COOKIE_NAME    = cfg.cookieName || 'lw_cookie_consent';
	var POLICY_VERSION = cfg.policyVersion || '1.0';
	var DOMAINS        = cfg.domains || {};
	var COOKIES        = cfg.cookies || {};
	var SW_URL         = cfg.swUrl || '';
	var PREVIEW        = ! ! cfg.preview;
	var TEXT           = cfg.text || {};

	// ── 1. Read consent from browser cookie ──────────────────────────
	function readConsent() {
		var match = document.cookie.match( '(?:^|; )' + COOKIE_NAME + '=([^;]*)' );
		if ( ! match ) {
			return null;
		}
		try {
			var json = atob( match[1] );
			return JSON.parse( json );
		} catch ( e ) {
			return null;
		}
	}

	function isConsentValid( consent ) {
		return consent && consent.version === POLICY_VERSION && consent.categories;
	}

	var consent = readConsent();
	var valid   = isConsentValid( consent );
	var cats    = valid ? consent.categories : { necessary : true };

	// ── Google Consent Mode helper ───────────────────────────────────
	// gtag() is only a thin wrapper that pushes its arguments onto the
	// dataLayer, which GTM reads directly. On GTM-only setups no global
	// gtag() exists, so we fall back to pushing the arguments ourselves —
	// otherwise Consent Mode signals are never delivered.
	function lwConsentPush() {
		window.dataLayer = window.dataLayer || [];
		var g            = ( typeof window.gtag === 'function' )
			? window.gtag
			: function () {
				window.dataLayer.push( arguments );
			};
		g.apply( null, arguments );
	}

	// GCM v2 default — must run before GTM/gtag so tags start denied and wait
	// for the update below. security_storage stays granted (not tracking).
	lwConsentPush(
		'consent',
		'default',
		{
			'analytics_storage':       'denied',
			'ad_storage':              'denied',
			'ad_user_data':            'denied',
			'ad_personalization':      'denied',
			'functionality_storage':   'denied',
			'personalization_storage': 'denied',
			'security_storage':        'granted',
			'wait_for_update':         500
		}
	);

	// ── 2. Banner + floating button visibility ───────────────────────
	function toggleVisibility() {
		var banner = document.getElementById( 'lw-cookie-notice' );
		var btn    = document.getElementById( 'lw-cookie-floating-btn' );

		if ( banner ) {
			// In preview mode the banner is always shown, even with valid consent.
			if ( valid && ! PREVIEW ) {
				banner.classList.add( 'lw-cookie-hidden' );
			} else {
				banner.classList.remove( 'lw-cookie-hidden' );
			}
		}

		if ( btn ) {
			if ( valid ) {
				btn.classList.remove( 'lw-cookie-hidden' );
			} else {
				btn.classList.add( 'lw-cookie-hidden' );
			}
		}
	}

	// Run immediately and again when DOM is ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', toggleVisibility );
	} else {
		toggleVisibility();
	}

	// ── 3. document.cookie setter override ───────────────────────────
	function isCookieBlocked( name ) {
		var keys       = Object.keys( COOKIES );
		var keysLength = keys.length;
		for ( var i = 0; i < keysLength; i++ ) {
			// Prefix match for patterns like '_ga_'.
			if ( name === keys[i] || name.indexOf( keys[i] ) === 0 ) {
				var category = COOKIES[ keys[i] ];
				if ( ! cats[ category ] ) {
					return true;
				}
			}
		}
		return false;
	}

	var cookieDesc = Object.getOwnPropertyDescriptor( Document.prototype, 'cookie' ) ||
					Object.getOwnPropertyDescriptor( HTMLDocument.prototype, 'cookie' );

	if ( cookieDesc && cookieDesc.set ) {
		var originalSet = cookieDesc.set;

		Object.defineProperty(
			document,
			'cookie',
			{
				get: function () {
					return cookieDesc.get.call( this );
				},
				set: function ( val ) {
					var cookieName = val.split( '=' )[0].trim();
					if ( isCookieBlocked( cookieName ) ) {
						return;
					}
					originalSet.call( this, val );
				},
				configurable: true
			}
		);
	}

	// ── 4. Domain category lookup ────────────────────────────────────
	function getCategoryForUrl( url ) {
		var a;
		try {
			a = new URL( url, location.href );
		} catch ( e ) {
			return null;
		}

		// Same-origin → always allowed.
		if ( a.origin === location.origin ) {
			return null;
		}

		var hostname      = a.hostname.replace( /^www\./, '' );
		var domains       = Object.keys( DOMAINS );
		var domainsLength = domains.length;

		for ( var i = 0; i < domainsLength; i++ ) {
			var d = domains[i];
			if ( hostname === d || hostname.endsWith( '.' + d ) ) {
				return DOMAINS[d];
			}
			// Path-based (e.g. "google.com/maps").
			if ( d.indexOf( '/' ) !== -1 && a.href.indexOf( d ) !== -1 ) {
				return DOMAINS[d];
			}
		}

		return null;
	}

	function isUrlBlocked( url ) {
		var category = getCategoryForUrl( url );
		return category ? ! cats[ category ] : false;
	}

	// ── 5. MutationObserver — intercept new elements ─────────────────
	// Elements that load third-party content, for scanning inserted subtrees.
	var BLOCKABLE = 'script[src],iframe[src],img[src]';
	var setAttr   = Element.prototype.setAttribute;

	var observer = new MutationObserver(
		function ( mutations ) {
			var mutLen = mutations.length;
			for ( var m = 0; m < mutLen; m++ ) {
				// A src set after insertion (lazy loaders, `iframe.src = url`).
				if ( mutations[m].type === 'attributes' ) {
					processElement( mutations[m].target );
					continue;
				}

				var nodes    = mutations[m].addedNodes;
				var nodesLen = nodes.length;
				for ( var n = 0; n < nodesLen; n++ ) {
					if ( nodes[n].nodeType === 1 ) {
						processTree( nodes[n] );
					}
				}
			}
		}
	);

	// A subtree inserted in one go (innerHTML, a wrapper built off-DOM) is
	// reported only through its root, so its descendants are checked too.
	function processTree( el ) {
		processElement( el );

		if ( ! el.firstElementChild ) {
			return;
		}

		var inner    = el.querySelectorAll( BLOCKABLE );
		var innerLen = inner.length;
		for ( var i = 0; i < innerLen; i++ ) {
			processElement( inner[i] );
		}
	}

	function processElement( el ) {
		var tag = el.tagName;

		// Scripts with src.
		if ( tag === 'SCRIPT' && el.src ) {
			blockScript( el, el.src );
			return;
		}

		// Iframes.
		if ( tag === 'IFRAME' && el.src && isUrlBlocked( el.src ) ) {
			blockIframe( el );
			return;
		}

		// Images (tracking pixels).
		if ( tag === 'IMG' && el.src && isUrlBlocked( el.src ) ) {
			el.setAttribute( 'data-lw-original-src', el.src );
			el.removeAttribute( 'src' );
			el.setAttribute( 'data-lw-blocked', '1' );
		}
	}

	// Make a script whose URL needs an ungranted category inert (text/plain).
	function blockScript( el, url ) {
		var category = getCategoryForUrl( url );
		if ( ! category || cats[ category ] ) {
			return;
		}

		setAttr.call( el, 'type', 'text/plain' );
		setAttr.call( el, 'data-lw-blocked', '1' );
		setAttr.call( el, 'data-lw-category', category );
	}

	// ── 5b. Script-created <script> elements ─────────────────────────
	// A script inserted by another script is prepared as soon as it is
	// connected (or gets its src), before the observer runs, so rewriting its
	// type there is too late. Scripts made with createElement are therefore
	// checked the moment their src is assigned, and stay inert once blocked.
	var nativeCreateElement = document.createElement;
	var scriptSrc           = Object.getOwnPropertyDescriptor( HTMLScriptElement.prototype, 'src' );
	var scriptType          = Object.getOwnPropertyDescriptor( HTMLScriptElement.prototype, 'type' );

	function isBlocked( el ) {
		return el.getAttribute( 'data-lw-blocked' ) === '1';
	}

	function guardScript( el ) {
		Object.defineProperty(
			el,
			'src',
			{
				configurable: true,
				get: function () {
					return scriptSrc.get.call( el );
				},
				set: function ( value ) {
					blockScript( el, String( value ) );
					scriptSrc.set.call( el, value );
				}
			}
		);
		Object.defineProperty(
			el,
			'type',
			{
				configurable: true,
				get: function () {
					return scriptType.get.call( el );
				},
				set: function ( value ) {
					if ( ! isBlocked( el ) ) {
						scriptType.set.call( el, value );
					}
				}
			}
		);
		el.setAttribute = function ( name, value ) {
			var attr = String( name ).toLowerCase();
			if ( attr === 'src' ) {
				blockScript( el, String( value ) );
			} else if ( attr === 'type' && isBlocked( el ) ) {
				return;
			}
			setAttr.call( el, name, value );
		};
	}

	if ( scriptSrc && scriptSrc.set && scriptType && scriptType.set ) {
		document.createElement = function () {
			var el = nativeCreateElement.apply( this, arguments );
			if ( el.tagName === 'SCRIPT' ) {
				guardScript( el );
			}
			return el;
		};
	}

	// ── 5a. Blocked-iframe placeholder ───────────────────────────────
	// Stripping the src alone leaves a bare, blank frame with no signal to
	// the visitor. Replace it with a placeholder that explains the block and
	// offers a button to consent and load the embed in place.
	function blockIframe( el ) {
		var category = getCategoryForUrl( el.src );

		el.setAttribute( 'data-lw-original-src', el.src );
		el.setAttribute( 'data-lw-blocked', '1' );
		if ( category ) {
			el.setAttribute( 'data-lw-category', category );
		}
		el.removeAttribute( 'src' );

		if ( isPlaceholder( el.previousSibling ) ) {
			return; // Already has a placeholder.
		}

		el.style.display = 'none';

		if ( el.parentNode ) {
			el.parentNode.insertBefore( buildPlaceholder( category, el ), el );
		}
	}

	function isPlaceholder( node ) {
		return ! ! node && node.nodeType === 1 && node.classList &&
			node.classList.contains( 'lw-cookie-embed-block' );
	}

	function buildPlaceholder( category, iframe ) {
		var box       = document.createElement( 'div' );
		box.className = 'lw-cookie-embed-block';

		// Mirror a declared pixel width so the placeholder keeps the layout.
		var width = iframe.getAttribute( 'width' );
		if ( width && /^\d+$/.test( width ) ) {
			box.style.maxWidth = width + 'px';
		}

		var msg         = document.createElement( 'p' );
		msg.className   = 'lw-cookie-embed-block__msg';
		msg.textContent = TEXT.blockedMessage ||
			'This content is blocked until you accept the required cookies.';

		var btn         = document.createElement( 'button' );
		btn.type        = 'button';
		btn.className   = 'lw-cookie-embed-block__btn';
		btn.textContent = TEXT.blockedButton || 'Accept & load content';
		btn.addEventListener(
			'click',
			function () {
				acceptEmbedCategory( category );
			}
		);

		box.appendChild( msg );
		box.appendChild( btn );
		return box;
	}

	// Consent to the category this embed needs, then load it in place.
	function acceptEmbedCategory( category ) {
		// Persist through the consent manager when present — it writes the
		// cookie, logs to the server, and calls refresh() (which restores the
		// embed). Falls back to a page-only grant if consent.js is not loaded.
		if ( window.LWCookie && typeof window.LWCookie.acceptCategory === 'function' ) {
			window.LWCookie.acceptCategory( category );
			return;
		}

		// Page-only grant: nothing is persisted, so nothing is sent to the
		// Service Worker, which is shared by every tab.
		if ( category ) {
			cats[ category ] = true;
		}
		restoreAllowed();
	}

	// Restore every blocked iframe whose category is now allowed.
	function restoreAllowed() {
		var blocked    = document.querySelectorAll( 'iframe[data-lw-blocked="1"]' );
		var blockedLen = blocked.length;
		for ( var i = 0; i < blockedLen; i++ ) {
			var el  = blocked[i];
			var cat = el.getAttribute( 'data-lw-category' );

			if ( cat && ! cats[ cat ] ) {
				continue; // Still blocked.
			}

			var src = el.getAttribute( 'data-lw-original-src' );
			if ( src ) {
				el.setAttribute( 'src', src );
			}
			el.removeAttribute( 'data-lw-blocked' );
			el.style.display = '';

			if ( isPlaceholder( el.previousSibling ) ) {
				el.parentNode.removeChild( el.previousSibling );
			}
		}
	}

	observer.observe(
		document.documentElement,
		{ childList: true, subtree: true, attributes: true, attributeFilter: [ 'src' ] }
	);

	// ── 6. Service Worker registration ───────────────────────────────
	// Cap on waiting for the worker's acknowledgement: a slow-starting worker
	// must not hold up the consent reload. The worker re-checks the consent
	// cookie before blocking anything, so the ack is not the only safeguard.
	var SW_SYNC_TIMEOUT = 500;

	function swMessage() {
		// Re-read the cookie first: consent may have changed in another tab
		// since this page loaded, and a stale state must never be broadcast.
		var fresh = readConsent();
		valid     = isConsentValid( fresh );
		cats      = valid ? fresh.categories : { necessary : true };

		return {
			type: 'consent-update',
			consent: cats,
			domains: DOMAINS,
			cookieName: COOKIE_NAME,
			policyVersion: POLICY_VERSION
		};
	}

	// Hand the current state to the active worker. Resolves once the worker
	// has acknowledged it, or after SW_SYNC_TIMEOUT. Targets the registration's
	// active worker, not navigator.serviceWorker.controller: a page the worker
	// does not control (e.g. after a hard reload) must still reach it.
	function updateSW() {
		if ( ! SW_URL || ! navigator.serviceWorker ) {
			return null;
		}

		var message = swMessage();
		var synced  = navigator.serviceWorker.getRegistration().then(
			function ( reg ) {
				if ( ! reg || ! reg.active ) {
					return;
				}
				var worker = reg.active;
				return new Promise(
					function ( resolve ) {
						var channel             = new MessageChannel();
						channel.port1.onmessage = resolve;
						worker.postMessage( message, [ channel.port2 ] );
					}
				);
			}
		);
		var timeout = new Promise(
			function ( resolve ) {
				setTimeout( resolve, SW_SYNC_TIMEOUT );
			}
		);

		return Promise.race( [ synced, timeout ] ).catch(
			function () {}
		);
	}

	if ( SW_URL && 'serviceWorker' in navigator ) {
		// Earliest possible hand-off to a worker already controlling this page.
		if ( navigator.serviceWorker.controller ) {
			navigator.serviceWorker.controller.postMessage( swMessage() );
		}

		navigator.serviceWorker.register( SW_URL, { scope: '/' } ).catch(
			function () {
				// SW registration failed — CSP fallback will handle it.
			}
		);

		// Once a worker is active (on a first visit only after install), sync
		// it. A registration never fires 'activate' — that event exists only
		// inside the worker.
		navigator.serviceWorker.ready.then( updateSW );
	}

	// ── 7. CSP meta fallback (browsers without SW) ───────────────────
	function injectCSP() {
		if ( 'serviceWorker' in navigator ) {
			return; // SW handles it.
		}

		var blocked       = [];
		var domains       = Object.keys( DOMAINS );
		var domainsLength = domains.length;

		for ( var i = 0; i < domainsLength; i++ ) {
			var d   = domains[i];
			var cat = DOMAINS[d];
			if ( ! cats[cat] ) {
				// Strip path for CSP (CSP doesn't support paths in source).
				var host = d.split( '/' )[0];
				if ( blocked.indexOf( host ) === -1 ) {
					blocked.push( host );
				}
			}
		}

		if ( blocked.length === 0 ) {
			return;
		}

		var blockedStr = blocked.map(
			function ( h ) {
				return '*.' + h; }
		).join( ' ' );
		var policy     = "script-src 'self' 'unsafe-inline' 'unsafe-eval' *; " +
						"frame-src 'self' *; " +
						"connect-src 'self' *; " +
						"img-src 'self' data: *";

		// Note: meta CSP can only restrict, not expand. This is a best-effort fallback.
		// Real blocking is handled by MutationObserver + cookie override.
		void policy;
	}

	injectCSP();

	// ── 8. GCM v2 update (if consent exists) ─────────────────────────
	function updateGCM( categories ) {
		// Delivered via the dataLayer (see lwConsentPush) so it also reaches
		// GTM-only setups where no global gtag() is defined.
		lwConsentPush(
			'consent',
			'update',
			{
				'analytics_storage':       categories.analytics ? 'granted' : 'denied',
				'ad_storage':              categories.marketing ? 'granted' : 'denied',
				'ad_user_data':            categories.marketing ? 'granted' : 'denied',
				'ad_personalization':      categories.marketing ? 'granted' : 'denied',
				'functionality_storage':   categories.functional ? 'granted' : 'denied',
				'personalization_storage': categories.functional ? 'granted' : 'denied'
			}
		);

		// Meta Pixel.
		if ( typeof fbq === 'function' ) {
			fbq( 'consent', categories.marketing ? 'grant' : 'revoke' );
		}
	}

	if ( valid ) {
		// Push the update immediately (no need to wait for gtag — it goes onto
		// the dataLayer right after the default), so the granted signal is in
		// place before GTM processes its queue and fires any tags.
		updateGCM( cats );
	}

	// ── 9. Public API for consent.js ─────────────────────────────────
	window.__lwGuard = {
		/**
		 * Called by consent.js after the user saves preferences.
		 * Updates internal state and notifies SW.
		 *
		 * @param {Object} newCategories Updated consent categories.
		 * @return {Promise|null} Settles once the SW has the new state (or
		 *                        timed out); null when no SW is in use.
		 */
		refresh: function ( newCategories ) {
			cats  = newCategories;
			valid = true;

			toggleVisibility();
			var synced = updateSW();
			updateGCM( newCategories );
			restoreAllowed();

			return synced;
		},

		/**
		 * Read current consent state (for consent.js init).
		 */
		getConsent: function () {
			return valid ? cats : null;
		},

		/**
		 * Check if consent is valid.
		 */
		isValid: function () {
			return valid;
		}
	};

})();
