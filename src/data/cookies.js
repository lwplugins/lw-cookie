/**
 * Declared-cookie list helpers (the `declared_cookies` option).
 */

export const BLANK_COOKIE = {
	name: '',
	provider: '',
	purpose: '',
	duration: '',
	category: 'necessary',
	type: 'persistent',
};

/**
 * Normalise any cookie-like object (scan result, common cookie) to the six
 * stored fields, like the classic "add row" did.
 *
 * @param {Object} cookie Source.
 * @return {Object} Declared cookie.
 */
export const toDeclared = ( cookie ) => ( {
	name: cookie.name || cookie.original_name || '',
	provider: cookie.provider || '',
	purpose: cookie.purpose || '',
	duration: cookie.duration || '',
	category: cookie.category || 'necessary',
	type: cookie.type === 'session' ? 'session' : 'persistent',
} );

/**
 * Append cookies whose name is not in the list yet.
 *
 * @param {Array} list  Current list.
 * @param {Array} extra Cookies to add.
 * @return {Array} New list.
 */
export function mergeCookies( list, extra ) {
	const names = new Set( list.map( ( c ) => c.name ) );
	const added = [];
	extra.map( toDeclared ).forEach( ( cookie ) => {
		if ( cookie.name && ! names.has( cookie.name ) ) {
			names.add( cookie.name );
			added.push( cookie );
		}
	} );
	return [ ...list, ...added ];
}
