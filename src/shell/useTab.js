/**
 * WordPress dependencies
 */
import { useEffect, useState } from '@wordpress/element';

const read = ( ids, fallback ) => {
	const hash = window.location.hash.replace( '#', '' );
	if ( ids.includes( hash ) ) {
		return hash;
	}
	return ids.includes( fallback ) ? fallback : ids[ 0 ];
};

/**
 * Current tab from location.hash; the first load also honours the classic
 * `?tab=` URL (links in notification e-mails point there).
 *
 * @param {string[]} ids     Tab ids.
 * @param {string}   initial Tab from the server (?tab=).
 * @return {string} Current tab id.
 */
export default function useTab( ids, initial ) {
	const [ tab, setTab ] = useState( () => read( ids, initial ) );

	useEffect( () => {
		const onChange = () => {
			setTab( read( ids, ids[ 0 ] ) );
			window.scrollTo( { top: 0 } );
		};
		window.addEventListener( 'hashchange', onChange );
		return () => window.removeEventListener( 'hashchange', onChange );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	return tab;
}
