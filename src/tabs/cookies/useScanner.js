/**
 * WordPress dependencies
 */
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { api, errorMessage } from '../../data/api';

const PAGE_FALLBACK_MS = 10000;
const REMOTE_TIMEOUT_MS = 120000;
const EMPTY = { cookies: [], domains: [], fonts: [] };

/**
 * The classic scanner flow (TabCookies::render_scanner_script), step by step:
 * POST clear-scan → POST prescan-headers → each scan URL in the hidden
 * iframe (next on the 'networkidle0' message or after 10 s) → POST
 * remote-scan (up to 120 s) → GET scan-results.
 *
 * @param {string[]} urls meta.scan_urls.
 * @return {Object} { state, start, cancel, frameRef }.
 */
export default function useScanner( urls ) {
	// state: null | { phase: start|pages|deep|done, page, total, results, error }
	const [ state, setState ] = useState( null );
	const frameRef = useRef( null );
	const runRef = useRef( 0 );
	const stopRef = useRef( () => {} );

	const loadPage = ( url ) =>
		new Promise( ( resolve ) => {
			const pending = {};
			const done = () => {
				window.removeEventListener( 'message', onMessage );
				clearTimeout( pending.timer );
				stopRef.current = () => {};
				resolve();
			};
			const onMessage = ( event ) => {
				if (
					event.data === 'networkidle0' &&
					event.source === frameRef.current?.contentWindow
				) {
					done();
				}
			};
			window.addEventListener( 'message', onMessage );
			pending.timer = setTimeout( done, PAGE_FALLBACK_MS );
			stopRef.current = done;
			if ( frameRef.current ) {
				frameRef.current.src = url;
			}
		} );

	const remoteScan = async () => {
		const controller = new AbortController();
		const timer = setTimeout( () => controller.abort(), REMOTE_TIMEOUT_MS );
		stopRef.current = () => controller.abort();
		try {
			await api.remoteScan( controller.signal );
		} catch {
			// Like the classic flow: the remote pass is best-effort.
		}
		clearTimeout( timer );
		stopRef.current = () => {};
	};

	const start = async () => {
		const run = ++runRef.current;
		const alive = () => runRef.current === run;
		setState( { phase: 'start' } );

		// clear-scan failing skips prescan and goes straight to the pages.
		let cleared = true;
		try {
			await api.clearScan();
		} catch {
			cleared = false;
		}
		if ( cleared ) {
			try {
				await api.prescanHeaders();
			} catch {
				// Best-effort, like the classic flow.
			}
		}

		for ( let i = 0; i < urls.length; i++ ) {
			if ( ! alive() ) {
				return;
			}
			setState( { phase: 'pages', page: i + 1, total: urls.length } );
			await loadPage( urls[ i ] );
		}
		if ( ! alive() ) {
			return;
		}
		if ( frameRef.current ) {
			frameRef.current.src = 'about:blank';
		}

		setState( { phase: 'deep' } );
		await remoteScan();
		if ( ! alive() ) {
			return;
		}

		let results = EMPTY;
		let error = '';
		try {
			const r = await api.scanResults();
			if ( r?.success ) {
				results = {
					cookies: r.cookies || [],
					domains: r.domains || [],
					fonts: r.fonts || [],
				};
			}
		} catch ( e ) {
			error = errorMessage( e );
		}
		if ( alive() ) {
			setState( { phase: 'done', results, error } );
		}
	};

	const cancel = useCallback( () => {
		runRef.current++;
		stopRef.current();
		if ( frameRef.current ) {
			frameRef.current.src = 'about:blank';
		}
		setState( null );
	}, [] );

	useEffect( () => () => cancel(), [ cancel ] );

	return { state, start, cancel, frameRef };
}
