/**
 * LW Cookie - Admin JavaScript
 *
 * @package LightweightPlugins\Cookie
 */

(function ($) {
	'use strict';

	/**
	 * Initialize admin functionality.
	 */
	function init() {
		initTabs();
		initColorPickers();
		initFormHashPreserver();
	}

	/**
	 * Initialize tab navigation.
	 */
	function initTabs() {
		var $tabs   = $( '.lw-cookie-tabs a' );
		var $panels = $( '.lw-cookie-tab-panel' );

		$tabs.on(
			'click',
			function (e) {
				e.preventDefault();

				var target = $( this ).attr( 'href' ).replace( '#', '' );

				// Update active tab.
				$tabs.removeClass( 'active' );
				$( this ).addClass( 'active' );

				// Update active panel.
				$panels.removeClass( 'active' );
				$( '#tab-' + target ).addClass( 'active' );

				// Save to URL hash.
				if (history.pushState) {
					history.pushState( null, null, '#' + target );
				}
			}
		);

		// Check URL hash on load.
		var hash = window.location.hash.replace( '#', '' );
		if (hash) {
			var $targetTab = $tabs.filter( '[href="#' + hash + '"]' );
			if ($targetTab.length) {
				$targetTab.trigger( 'click' );
			}
		}
	}

	/**
	 * Preserve the active tab hash across form save.
	 */
	function initFormHashPreserver() {
		$( '.lw-cookie-settings' ).closest( 'form' ).on(
			'submit',
			function () {
				var hash     = window.location.hash;
				var $referer = $( this ).find( 'input[name="_wp_http_referer"]' );

				if (hash && $referer.length) {
					$referer.val( $referer.val().replace( /#.*$/, '' ) + hash );
				}
			}
		);
	}

	/**
	 * Initialize color pickers.
	 */
	function initColorPickers() {
		if ($.fn.wpColorPicker) {
			$( '.lw-cookie-color-picker' ).wpColorPicker();
		}
	}

	// Initialize on document ready.
	$( document ).ready( init );

})( jQuery );
