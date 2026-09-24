/**
 * WordPress dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Native "leave site?" prompt while there are unsaved edits.
 *
 * @param {boolean} hasEdits Whether to warn.
 */
export default function useUnsavedWarning( hasEdits ) {
	useEffect( () => {
		if ( ! hasEdits ) {
			return;
		}
		const warn = ( event ) => {
			event.preventDefault();
			event.returnValue = '';
		};
		window.addEventListener( 'beforeunload', warn );
		return () => window.removeEventListener( 'beforeunload', warn );
	}, [ hasEdits ] );
}
