/**
 * Quick search for LW admin screens: a sidebar field and a Cmd/Ctrl+K
 * dialog that finds sections, cards and settings by name and opens them.
 *
 * Self-contained so other LW plugins can take the folder as is. The index
 * is read from the plugin's own rendered tabs (card titles, setting titles
 * and help), so a new setting is searchable without listing it anywhere.
 */

/**
 * WordPress dependencies
 */
import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Icon, search } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import harvest, { locate } from './harvest';
import { rememberRecent } from './recents';
import SearchDialog from './SearchDialog';
import './quick-search.scss';

const IS_MAC = /Mac|iPhone|iPad/.test( window.navigator.platform || '' );

/**
 * Show an entry on the page: scroll it into view, flash it and focus its
 * control. Waits a few frames for a tab switch to render.
 *
 * @param {Object} entry           Entry.
 * @param {string} contentSelector Page content root.
 */
function reveal( entry, contentSelector ) {
	let tries = 0;
	const attempt = () => {
		const scope = document.querySelector( contentSelector );
		const target =
			scope && entry.kind !== 'tab' ? locate( scope, entry ) : null;
		if ( ! target && entry.kind !== 'tab' && tries++ < 20 ) {
			window.setTimeout( attempt, 50 );
			return;
		}
		if ( ! target ) {
			return;
		}
		target.scrollIntoView( { block: 'center', behavior: 'smooth' } );
		target.classList.add( 'lw-qs-flash' );
		window.setTimeout(
			() => target.classList.remove( 'lw-qs-flash' ),
			1800
		);
		target
			.querySelector( 'input:not([type=hidden]), textarea, select' )
			?.focus( { preventScroll: true } );
	};
	window.setTimeout( attempt, 0 );
}

/**
 * Sidebar search field and dialog.
 *
 * @param {Object}       props
 * @param {Object[]}     props.tabs            Tab registry ({ id, label, title, icon }).
 * @param {() => ?Array} props.pages           Returns [{ tab, element }] to index; null while not ready.
 * @param {string}       props.storageKey      localStorage key of the recents.
 * @param {string}       props.contentSelector Page content root.
 */
export default function QuickSearch( {
	tabs,
	pages,
	storageKey,
	contentSelector = '.lw-admin-content',
} ) {
	const [ isOpen, setIsOpen ] = useState( false );
	const [ index, setIndex ] = useState( [] );
	const harvested = useRef( false );
	const trigger = useRef();

	useEffect( () => {
		const onKeyDown = ( event ) => {
			if (
				( event.metaKey || event.ctrlKey ) &&
				! event.altKey &&
				event.key.toLowerCase() === 'k'
			) {
				event.preventDefault();
				setIsOpen( ( open ) => ! open );
			}
		};
		document.addEventListener( 'keydown', onKeyDown );
		return () => document.removeEventListener( 'keydown', onKeyDown );
	}, [] );

	// Build the index on first open (settings must be loaded by then).
	useEffect( () => {
		if ( ! isOpen || harvested.current ) {
			return;
		}
		const tabEntries = tabs.map( ( tab ) => ( {
			kind: 'tab',
			tab: tab.id,
			path: [],
			title: tab.label,
			help: tab.title,
		} ) );
		setIndex( tabEntries );

		const list = pages();
		if ( ! list ) {
			return;
		}
		harvested.current = true;
		// Out of this effect: React ignores flushSync while it is committing,
		// and the off-screen tabs would render empty.
		const timer = window.setTimeout(
			() => setIndex( [ ...tabEntries, ...harvest( list ) ] ),
			0
		);
		return () => {
			window.clearTimeout( timer );
			harvested.current = false;
		};
	}, [ isOpen, tabs, pages ] );

	const close = () => {
		setIsOpen( false );
		trigger.current?.focus();
	};

	const select = ( entry ) => {
		rememberRecent( storageKey, entry );
		setIsOpen( false );
		if ( window.location.hash !== `#${ entry.tab }` ) {
			window.location.hash = entry.tab;
		}
		reveal( entry, contentSelector );
	};

	return (
		<>
			<button
				ref={ trigger }
				type="button"
				className="lw-qs-trigger"
				aria-haspopup="dialog"
				aria-keyshortcuts={ IS_MAC ? 'Meta+K' : 'Control+K' }
				onClick={ () => setIsOpen( true ) }
			>
				<Icon icon={ search } size={ 20 } />
				<span className="lw-qs-trigger__label">
					{ __( 'Quick search…', 'lw-cookie' ) }
				</span>
				<kbd className="lw-qs-kbd">{ IS_MAC ? '⌘K' : 'Ctrl K' }</kbd>
			</button>
			{ isOpen && (
				<SearchDialog
					index={ index }
					icons={ Object.fromEntries(
						tabs.map( ( tab ) => [ tab.id, tab.icon ] )
					) }
					storageKey={ storageKey }
					onSelect={ select }
					onClose={ close }
				/>
			) }
		</>
	);
}
