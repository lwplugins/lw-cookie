/**
 * The last opened results, per browser (localStorage). Storage may be
 * unavailable (private mode, blocked site data); then there are no recents.
 */

const LIMIT = 5;

/**
 * Stable key of an entry.
 *
 * @param {Object} entry Entry.
 * @return {string} Key.
 */
export const keyOf = ( entry ) =>
	[ entry.kind, entry.tab, entry.card || '', entry.title ].join( '|' );

/**
 * Stored keys.
 *
 * @param {string} storageKey localStorage key.
 * @return {string[]} Keys, newest first.
 */
export function readRecents( storageKey ) {
	try {
		const stored = JSON.parse( window.localStorage.getItem( storageKey ) );
		return Array.isArray( stored ) ? stored : [];
	} catch {
		return [];
	}
}

/**
 * Put an entry first.
 *
 * @param {string} storageKey localStorage key.
 * @param {Object} entry      Opened entry.
 */
export function rememberRecent( storageKey, entry ) {
	const key = keyOf( entry );
	const next = [
		key,
		...readRecents( storageKey ).filter( ( k ) => k !== key ),
	];
	try {
		window.localStorage.setItem(
			storageKey,
			JSON.stringify( next.slice( 0, LIMIT ) )
		);
	} catch {
		// No storage: nothing to remember.
	}
}
