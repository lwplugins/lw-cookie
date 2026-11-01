/**
 * WordPress dependencies
 */
import { createRoot, flushSync } from '@wordpress/element';

/**
 * Where the settings live in the rendered tabs. A card is a
 * `@wordpress/components` Card with an <h3> title; a setting is a row
 * title, a switch label or a control label inside its body.
 */
const CARD = '.components-card';
const CARD_TITLE = '.components-card__header h3';
// Each LW admin prefixes its own switch list (lw-px-switch, lw-lms-switch…);
// core ToggleControl rows carry their label in a BaseControl.
const ROW =
	'.lw-admin-row, [class*="-switch"]:not([class*="-switch__"]), .components-base-control';
const ROW_TITLE =
	'.lw-admin-row__title, [class*="-switch__title"], .components-base-control__label, .components-toggle-control__label';
const ROW_HELP =
	'.lw-admin-row__help, [class*="-switch__help"], .components-base-control__help';
// A row's own control (a toggle's On/Off, a hidden input label) repeats or
// contradicts the row title; the title is what people search for.
const SKIP =
	'.components-card__header, .components-visually-hidden, .lw-admin-row__control';

const text = ( node ) =>
	( node?.textContent || '' ).replace( /\s+/g, ' ' ).trim();

/**
 * Cards and settings of one rendered tab.
 *
 * @param {Element} root Rendered tab.
 * @param {Object}  tab  Tab ({ id, label }).
 * @return {Object[]} Entries.
 */
function collect( root, tab ) {
	const entries = [];
	const seen = new Set();
	const add = ( entry ) => {
		const key = entry.path.concat( entry.title ).join( '\u0000' );
		if ( entry.title && ! seen.has( key ) ) {
			seen.add( key );
			entries.push( entry );
		}
	};

	root.querySelectorAll( CARD ).forEach( ( card ) => {
		const cardTitle = text( card.querySelector( CARD_TITLE ) );
		if ( cardTitle ) {
			add( {
				kind: 'card',
				tab: tab.id,
				path: [ tab.label ],
				title: cardTitle,
			} );
		}

		card.querySelectorAll( ROW_TITLE ).forEach( ( label ) => {
			if ( label.closest( SKIP ) ) {
				return;
			}
			const row = label.closest( ROW );
			add( {
				kind: 'setting',
				tab: tab.id,
				card: cardTitle,
				path: cardTitle ? [ tab.label, cardTitle ] : [ tab.label ],
				title: text( label ),
				help: text( row?.querySelector( ROW_HELP ) ),
			} );
		} );
	} );

	return entries;
}

/**
 * Render each tab once, off screen, and read its cards and settings. Tabs
 * render from the settings already in memory and start no requests, so this
 * is a synchronous pass with nothing left mounted.
 *
 * @param {Array<{tab: Object, element: Element}>} pages Tabs to index.
 * @return {Object[]} Entries.
 */
export default function harvest( pages ) {
	const entries = [];

	pages.forEach( ( { tab, element } ) => {
		const node = document.createElement( 'div' );
		const root = createRoot( node );
		try {
			flushSync( () => root.render( element ) );
			entries.push( ...collect( node, tab ) );
		} catch {
			// A tab that fails to render is only left out of the search.
		} finally {
			root.unmount();
		}
	} );

	return entries;
}

/**
 * Find a harvested entry on the page: the card, or the setting inside it.
 *
 * @param {Element} scope Page content.
 * @param {Object}  entry Entry.
 * @return {Element|null} Element to show.
 */
export function locate( scope, entry ) {
	const cards = Array.from( scope.querySelectorAll( CARD ) );
	const cardTitle = entry.kind === 'card' ? entry.title : entry.card;
	const card = cardTitle
		? cards.find(
				( c ) => text( c.querySelector( CARD_TITLE ) ) === cardTitle
			)
		: null;

	if ( entry.kind === 'card' ) {
		return card || null;
	}

	const label = Array.from(
		( card || scope ).querySelectorAll( ROW_TITLE )
	).find(
		( node ) => ! node.closest( SKIP ) && text( node ) === entry.title
	);

	return label ? label.closest( ROW ) || label : card;
}
