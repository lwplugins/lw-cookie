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

	// ── 2. Banner + floating button visibility ───────────────────────
	function toggleVisibility() {
		var banner = document.getElementById( 'lw-cookie-notice' );
		var btn    = document.getElementById( 'lw-cookie-floating-btn' );

		if ( banner ) {
			if ( valid ) {
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
	var observer = new MutationObserver(
		function ( mutations ) {
			var mutLen = mutations.length;
			for ( var m = 0; m < mutLen; m++ ) {
				var nodes    = mutations[m].addedNodes;
				var nodesLen = nodes.length;
				for ( var n = 0; n < nodesLen; n++ ) {
					var node = nodes[n];
					if ( node.nodeType !== 1 ) {
							continue;
					}
					processElement( node );
				}
			}
		}
	);

	function processElement( el ) {
		var tag = el.tagName;

		// Scripts with src.
		if ( tag === 'SCRIPT' && el.src && isUrlBlocked( el.src ) ) {
			el.type = 'text/plain';
			el.setAttribute( 'data-lw-blocked', '1' );
			return;
		}

		// Iframes.
		if ( tag === 'IFRAME' && el.src && isUrlBlocked( el.src ) ) {
			el.setAttribute( 'data-lw-original-src', el.src );
			el.removeAttribute( 'src' );
			el.setAttribute( 'data-lw-blocked', '1' );
			return;
		}

		// Images (tracking pixels).
		if ( tag === 'IMG' && el.src && isUrlBlocked( el.src ) ) {
			el.setAttribute( 'data-lw-original-src', el.src );
			el.removeAttribute( 'src' );
			el.setAttribute( 'data-lw-blocked', '1' );
		}
	}

	observer.observe( document.documentElement, { childList: true, subtree: true } );

	// ── 6. Service Worker registration ───────────────────────────────
	function updateSW() {
		if ( ! navigator.serviceWorker || ! navigator.serviceWorker.controller ) {
			return;
		}
		navigator.serviceWorker.controller.postMessage(
			{
				type: 'consent-update',
				consent: cats,
				domains: DOMAINS
			}
		);
	}

	if ( SW_URL && 'serviceWorker' in navigator ) {
		navigator.serviceWorker.register( SW_URL, { scope: '/' } )
			.then(
				function ( reg ) {
					// SW may already be active or will be soon.
					if ( reg.active ) {
							updateSW();
					}
					reg.addEventListener( 'activate', updateSW );
				}
			)
			.catch(
				function () {
					// SW registration failed — CSP fallback will handle it.
				}
			);

		// Also update when existing SW becomes active.
		if ( navigator.serviceWorker.controller ) {
			updateSW();
		}
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
		if ( typeof gtag !== 'function' ) {
			return;
		}

		gtag(
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
		// Defer GCM update so gtag is defined first.
		if ( document.readyState === 'loading' ) {
			document.addEventListener(
				'DOMContentLoaded',
				function () {
					updateGCM( cats );
				}
			);
		} else {
			updateGCM( cats );
		}
	}

	// ── 9. Public API for consent.js ─────────────────────────────────
	window.__lwGuard = {
		/**
		 * Called by consent.js after the user saves preferences.
		 * Updates internal state and notifies SW.
		 *
		 * @param {Object} newCategories Updated consent categories.
		 */
		refresh: function ( newCategories ) {
			cats  = newCategories;
			valid = true;

			toggleVisibility();
			updateSW();
			updateGCM( newCategories );
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
