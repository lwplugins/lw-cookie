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

	// The guard's own nonce (read now: currentScript is set only while this
	// runs). Server-rendered placeholders must carry it (see 5c).
	var GUARD_NONCE = '';
	try {
		var current = document.currentScript;
		GUARD_NONCE = current ? ( current.nonce || current.getAttribute( 'nonce' ) || '' ) : '';
	} catch ( e ) {
		GUARD_NONCE = '';
	}

	var COOKIE_NAME    = cfg.cookieName || 'lw_cookie_consent';
	var POLICY_VERSION = cfg.policyVersion || '1.0';
	var DOMAINS        = cfg.domains || {};
	var COOKIES        = cfg.cookies || {};
	var SW_URL         = cfg.swUrl || '';
	var PREVIEW        = ! ! cfg.preview;
	var TEXT           = cfg.text || {};

	// Advanced → Content Blocking. Governs embedded iframes (and their
	// placeholders) only. Missing from a config cached before 1.7.6 → on.
	var CONTENT_BLOCKING = cfg.contentBlocking !== false;

	// Advanced → Script Blocking. Governs tracker scripts, tracking pixels and
	// the Service Worker. Missing from a config cached before 1.7.7 → on.
	var SCRIPT_BLOCKING = cfg.scriptBlocking !== false;

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

	// Renamed Google data layers that also receive the Consent Mode commands
	// (set by the server when another plugin moves the Google tag onto one).
	var EXTRA_LAYERS = Array.isArray( cfg.extraLayers ) ? cfg.extraLayers : [];

	// The standard layer's name is assembled at runtime. This script is inlined
	// into the page, where output rewriters search the HTML for the literal name
	// (and for calls to the global Google tag function) to rename it; keeping
	// both out of the source (calls go through apply) keeps it unchanged.
	// phpcs:ignore Generic.Strings.UnnecessaryStringConcat.Found -- Deliberate: the split is the point.
	var STD_LAYER = 'data' + 'Layer';

	// ── Google Consent Mode helper ───────────────────────────────────
	// The global Google tag function is only a thin wrapper that pushes its
	// arguments onto a data layer, which GTM reads directly. On GTM-only setups
	// it does not exist, so we also push the arguments ourselves, onto the
	// standard layer and onto any renamed one — otherwise Consent Mode signals
	// are never delivered.
	function lwConsentPush() {
		var layers = [ STD_LAYER ].concat( EXTRA_LAYERS );
		var args   = arguments;

		if ( typeof window.gtag === 'function' ) {
			window.gtag.apply( null, args );
		}

		var count = layers.length;

		for ( var i = 0; i < count; i++ ) {
			window[ layers[i] ] = window[ layers[i] ] || [];
			// Skip a layer the global function above has just written to (it pushes
			// the same arguments), so the usual single-layer setup is unchanged.
			var layer = window[ layers[i] ];
			var last  = layer[ layer.length - 1 ];
			if ( last && last.length === args.length && last[0] === args[0] && last[1] === args[1] && last[2] === args[2] ) {
				continue;
			}
			layer.push( args );
		}
	}

	// Consent Mode must never take the guard down with it: if a push throws,
	// the banner and the blocking below still have to start.
	function lwConsentSafe() {
		try {
			lwConsentPush.apply( null, arguments );
		} catch ( e ) {
			// Fail open.
		}
	}

	// GCM v2 default — must run before GTM/the Google tag so tags start denied
	// and wait for the update below. security_storage stays granted (not tracking).
	lwConsentSafe(
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
	// Independent of Script Blocking: with it off, tracker scripts may load
	// (e.g. for Consent Mode's cookieless pings), but writing their known
	// cookies is what needs consent, so that stays blocked until it is given.
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

	// Removing a cookie stores nothing, so it is always let through: a script
	// clearing its cookies after consent is revoked must be able to.
	function isCookieRemoval( val ) {
		// Max-Age wins over Expires when both are set.
		var maxAge = /;\s*max-age\s*=\s*(-?\d+)/i.exec( val );
		if ( maxAge ) {
			return parseInt( maxAge[1], 10 ) <= 0;
		}
		var expires = /;\s*expires\s*=\s*([^;]+)/i.exec( val );
		return ! ! expires && new Date( expires[1] ).getTime() <= Date.now();
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
					if ( isCookieBlocked( cookieName ) && ! isCookieRemoval( val ) ) {
						return;
					}
					originalSet.call( this, val );
				},
				configurable: true
			}
		);
	}

	// ── 4. Domain category lookup ────────────────────────────────────
	// ES5 suffix check: this runs inside third-party src assignments (see 5b),
	// so it must never throw.
	function hasSuffix( str, suffix ) {
		var at = str.length - suffix.length;
		return at >= 0 && str.indexOf( suffix, at ) === at;
	}

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
			if ( hostname === d || hasSuffix( hostname, '.' + d ) ) {
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

		// Server-rendered placeholders and held inline scripts (see 5c).
		if ( tag === 'SCRIPT' && handlePlaceholder( el ) ) {
			return;
		}

		// Scripts with src (Script Blocking).
		if ( tag === 'SCRIPT' && SCRIPT_BLOCKING && el.src ) {
			blockScript( el, el.src );
			return;
		}

		// Iframes (embedded content).
		if ( tag === 'IFRAME' && CONTENT_BLOCKING && el.src && isUrlBlocked( el.src ) ) {
			blockIframe( el );
			return;
		}

		// Images (tracking pixels — Script Blocking).
		if ( tag === 'IMG' && SCRIPT_BLOCKING && el.src && isUrlBlocked( el.src ) ) {
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

	if ( SCRIPT_BLOCKING && scriptSrc && scriptSrc.set && scriptType && scriptType.set ) {
		document.createElement = function () {
			var el = nativeCreateElement.apply( this, arguments );
			if ( el.tagName === 'SCRIPT' ) {
				guardScript( el );
			}
			return el;
		};
	}

	// ── 5c. Server-rendered placeholders ─────────────────────────────
	// The server prints an enqueued tracker script (and every script that
	// depends on it) as an inert placeholder: type text/plain, URL in
	// data-lw-original-src, so the preload scanner never fetches it. Once its
	// category is allowed, the placeholders are loaded one after another, in
	// the printed order. Inline scripts of those handles (data-lw-hold) that
	// come while the chain is still loading wait their turn in it; with an
	// idle chain they run where they are, as without the rewrite. A defer
	// placeholder (and the held after-script of its handle) waits until the
	// document has been parsed, as a native defer script does.
	var CATEGORY_KEYS = { necessary: true, functional: true, analytics: true, marketing: true };
	var CLASSIC_JS    = /^(text|application)\/(x-)?(java|ecma)script$/;
	var chain         = [];
	var chainBusy     = false;
	var deferred      = [];
	var deferIds      = {};
	var parsed        = document.readyState !== 'loading';
	var domReady      = parsed;

	// Data blocks and modules keep their native timing; only classic
	// JavaScript is held.
	function isClassicInline( el ) {
		var type = ( el.getAttribute( 'type' ) || '' ).replace( /^\s+|\s+$/g, '' ).toLowerCase();
		return type === '' || CLASSIC_JS.test( type );
	}

	function isDeferred( el ) {
		return ! parsed && el.hasAttribute( 'defer' ) && ! el.hasAttribute( 'async' );
	}

	function hold( el, queue ) {
		setAttr.call( el, 'type', 'text/plain' );
		queue.push( { kind: 'inline', el: el } );
	}

	function nonceOf( el ) {
		return el.nonce || el.getAttribute( 'nonce' ) || '';
	}

	// Only the server's own markup is run: with a nonce on the guard, the
	// element must carry the same one (an injected tag cannot know it).
	function isOwnElement( el ) {
		return ! GUARD_NONCE || nonceOf( el ) === GUARD_NONCE;
	}

	function isAllowedPlaceholder( el ) {
		var cat = el.getAttribute( 'data-lw-category' );
		var src = el.getAttribute( 'data-lw-original-src' );

		// The host is not checked: a dependent of a tracker may load from any
		// host and inherits the tracker's category. The nonce is what a CSP
		// relies on; without a CSP an injected script would run natively anyway.
		if ( ! src || ! cat || ! Object.prototype.hasOwnProperty.call( CATEGORY_KEYS, cat ) || ! isOwnElement( el ) ) {
			return false;
		}
		return ! SCRIPT_BLOCKING || ! ! cats[ cat ];
	}

	function isPlaceholderAttr( name ) {
		return name === 'type' || name.indexOf( 'data-lw-' ) === 0;
	}

	// Copy the original attributes (all but type and data-lw-*).
	function copyAttributes( from, to ) {
		var attrs = from.attributes;
		var count = attrs.length;
		for ( var i = 0; i < count; i++ ) {
			var name = attrs[i].name.toLowerCase();
			if ( isPlaceholderAttr( name ) ) {
				continue;
			}
			// The browser hides a nonce from getAttribute once it is parsed.
			var value = name === 'nonce' ? nonceOf( from ) : attrs[i].value;
			setAttr.call( to, attrs[i].name, value );
		}
	}

	function handlePlaceholder( el ) {
		if ( el.hasAttribute( 'data-lw-original-src' ) ) {
			if ( ! isAllowedPlaceholder( el ) ) {
				return true;
			}
			if ( isDeferred( el ) ) {
				deferred.push( { kind: 'src', el: el } );
				if ( el.id ) {
					deferIds[ el.id ] = true;
				}
				return true;
			}
			chain.push( { kind: 'src', el: el } );
			pumpChain();
			return true;
		}

		if ( el.hasAttribute( 'data-lw-hold' ) && ! el.hasAttribute( 'src' ) ) {
			if ( ! isClassicInline( el ) || ! isOwnElement( el ) ) {
				return true;
			}
			// The after-script of a deferred handle follows that handle.
			var handle = /-after$/.test( el.id ) ? el.id.slice( 0, -6 ) : '';
			if ( handle && deferIds[ handle ] && ! parsed ) {
				hold( el, deferred );
			} else if ( chainBusy || chain.length ) {
				hold( el, chain );
			}
			return true;
		}

		return false;
	}

	// Parsing is done: deferred items join the chain, in their order.
	function flushDeferred() {
		if ( parsed || document.readyState === 'loading' ) {
			return;
		}
		parsed   = true;
		chain    = chain.concat( deferred );
		deferred = [];
		pumpChain();
	}

	document.addEventListener( 'readystatechange', flushDeferred );
	document.addEventListener(
		'DOMContentLoaded',
		function () {
			domReady = true;
			flushDeferred();
		}
	);

	// A script the chain runs after DOMContentLoaded would wait forever for a
	// listener it adds for that event, so such a listener is called (async)
	// instead. Everything else goes to the native method unchanged.
	function replayDomReady( target ) {
		var native = target.addEventListener;
		if ( typeof native !== 'function' ) {
			return;
		}

		target.addEventListener = function ( type, listener ) {
			var self = this;
			var replay;
			try {
				var current = document.currentScript;
				replay      = type === 'DOMContentLoaded' && domReady && listener && current && current.__lwChain;
			} catch ( e ) {
				replay = false;
			}
			if ( ! replay ) {
				return native.apply( this, arguments );
			}
			setTimeout(
				function () {
					var event;
					try {
						event = document.createEvent( 'Event' );
						event.initEvent( 'DOMContentLoaded', true, true );
					} catch ( e ) {
						return;
					}
					// Outside the try: a listener's own error is reported as usual.
					if ( typeof listener === 'function' ) {
						listener.call( self, event );
					} else if ( typeof listener.handleEvent === 'function' ) {
						listener.handleEvent( event );
					}
				},
				0
			);
		};
	}

	replayDomReady( document );
	replayDomReady( window );

	function pumpChain() {
		var item = chainBusy ? null : chain.shift();
		while ( item ) {
			if ( item.kind === 'src' ) {
				chainBusy = startScript( item.el );
			} else {
				runHeld( item.el );
			}
			item = chainBusy ? null : chain.shift();
		}
	}

	// A script the browser will not run fires neither load nor error, so the
	// chain must not wait for it.
	function willRun( script ) {
		var type = ( script.getAttribute( 'type' ) || '' ).toLowerCase();
		if ( script.hasAttribute( 'nomodule' ) && 'noModule' in script ) {
			return false;
		}
		return type === '' || type === 'module' || /(java|ecma)script$/.test( type );
	}

	// Swap a placeholder for the real script. Returns whether the chain has
	// to wait for it to load.
	function startScript( el ) {
		var parent = el.parentNode;
		if ( ! parent ) {
			return false;
		}

		try {
			var script = nativeCreateElement.call( document, 'script' );
			var type   = el.getAttribute( 'data-lw-original-type' );
			var done   = false;

			copyAttributes( el, script );
			if ( type ) {
				setAttr.call( script, 'type', type );
			}
			script.async     = el.hasAttribute( 'async' );
			script.__lwChain = true;

			// An async script keeps native timing: nothing waits for it.
			var wait   = willRun( script ) && ! el.hasAttribute( 'async' );
			var finish = function () {
				if ( done ) {
					return;
				}
				done      = true;
				chainBusy = false;
				pumpChain();
			};
			if ( wait ) {
				script.addEventListener( 'load', finish );
				script.addEventListener( 'error', finish );
			}

			setAttr.call( script, 'src', el.getAttribute( 'data-lw-original-src' ) );
			parent.insertBefore( script, el );
			parent.removeChild( el );
			return wait;
		} catch ( e ) {
			// Leave the placeholder inert; hasBlockedScripts() still counts it.
			return false;
		}
	}

	function runHeld( el ) {
		var parent = el.parentNode;
		if ( ! parent ) {
			return;
		}

		try {
			var script = nativeCreateElement.call( document, 'script' );
			copyAttributes( el, script );
			script.text      = el.textContent;
			script.__lwChain = true;
			parent.insertBefore( script, el );
			parent.removeChild( el );
		} catch ( e ) {
			// Never let a held script take the guard down.
		}
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
			var box = buildPlaceholder( category, el );
			el.parentNode.insertBefore( box, el );
			fitPlaceholder( box, el );
		}
	}

	// An absolutely positioned iframe (a padding-ratio box: height 0,
	// padding-top, overflow hidden) fills its box instead of taking space in
	// the flow. A placeholder in the flow would sit below that box and be
	// clipped, so it covers the box instead.
	function fitPlaceholder( box, iframe ) {
		if ( window.getComputedStyle( iframe ).position === 'absolute' ) {
			box.classList.add( 'lw-cookie-embed-block--cover' );
			box.style.maxWidth = '';
		}
	}

	// Stylesheets may still be loading when an early iframe is blocked, so
	// fit the placeholders again once the page has loaded.
	window.addEventListener(
		'load',
		function () {
			var blocked    = document.querySelectorAll( 'iframe[data-lw-blocked="1"]' );
			var blockedLen = blocked.length;
			for ( var i = 0; i < blockedLen; i++ ) {
				if ( isPlaceholder( blocked[i].previousSibling ) ) {
					fitPlaceholder( blocked[i].previousSibling, blocked[i] );
				}
			}
		}
	);

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
	// Cap on waiting for the worker's acknowledgement, so a slow-starting
	// worker cannot hold up the consent reload for long. Where the worker can
	// read the consent cookie itself (Cookie Store API — shipped together in
	// windows and workers) the ack is only a fast path; elsewhere it is the
	// only guarantee the worker has the new state, so wait longer for it.
	var SW_SYNC_TIMEOUT = window.cookieStore ? 500 : 2000;

	function swMessage() {
		// Re-read the cookie first: consent may have changed in another tab
		// since this page loaded, and a stale state must never be broadcast.
		var fresh = readConsent();
		valid     = isConsentValid( fresh );
		cats      = valid ? fresh.categories : { necessary : true };

		return {
			type: 'consent-update',
			consent: cats,
			// No domains with Script Blocking off: a worker holding none blocks
			// nothing (every worker version honours this).
			domains: SCRIPT_BLOCKING ? DOMAINS : {},
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

	// Script Blocking is off, but a worker registered while it was on stays in
	// the visitor's browser and would keep blocking tracker hosts — it reads
	// the consent cookie itself. The empty domain list sent by updateSW()
	// disarms it at once, for every open tab; unregistering it then keeps
	// later page loads out of it. Only LW Cookie's own worker is touched.
	function retireSW() {
		updateSW().then(
			function () {
				return navigator.serviceWorker.getRegistration();
			}
		).then(
			function ( reg ) {
				var worker = reg && ( reg.active || reg.waiting || reg.installing );
				if ( worker && worker.scriptURL.split( '?' )[0] === SW_URL.split( '?' )[0] ) {
					return reg.unregister();
				}
			}
		).catch(
			function () {}
		);
	}

	if ( SW_URL && 'serviceWorker' in navigator ) {
		// Earliest possible hand-off to a worker already controlling this page.
		if ( navigator.serviceWorker.controller ) {
			navigator.serviceWorker.controller.postMessage( swMessage() );
		}

		if ( SCRIPT_BLOCKING ) {
			navigator.serviceWorker.register( SW_URL, { scope: '/' } ).catch(
				function () {
					// Registration failed: the guard above still blocks in the page.
				}
			);

			// Once a worker is active (on a first visit only after install),
			// sync it. A registration never fires 'activate' — that event exists
			// only inside the worker.
			navigator.serviceWorker.ready.then( updateSW );
		} else {
			retireSW();
		}
	}

	// ── 7. GCM v2 update (if consent exists) ─────────────────────────
	function updateGCM( categories ) {
		// Delivered via the data layer (see lwConsentPush) so it also reaches
		// GTM-only setups where no global Google tag function is defined.
		lwConsentSafe(
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
		// Push the update immediately (no need to wait for the Google tag — it
		// goes onto the data layer right after the default), so the granted signal is in
		// place before GTM processes its queue and fires any tags.
		updateGCM( cats );
	}

	// ── 8. Public API for consent.js ─────────────────────────────────
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
		 * Whether a script of this category is blocked on the page. Such a
		 * script cannot be restored in place (code relying on it has already
		 * run without it), so consent.js reloads instead.
		 *
		 * @param {string} category Category key.
		 * @return {boolean}
		 */
		hasBlockedScripts: function ( category ) {
			var blocked    = document.querySelectorAll( 'script[data-lw-blocked="1"]' );
			var blockedLen = blocked.length;
			for ( var i = 0; i < blockedLen; i++ ) {
				if ( blocked[i].getAttribute( 'data-lw-category' ) === category ) {
					return true;
				}
			}
			return false;
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
