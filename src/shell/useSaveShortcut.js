/**
 * WordPress dependencies
 */
import { useEffect } from '@wordpress/element';
import { isKeyboardEvent } from '@wordpress/keycodes';

/**
 * Cmd/Ctrl+S saves (and never opens the browser's "Save page" dialog).
 *
 * @param {Function} onSave  Save callback.
 * @param {boolean}  enabled Whether saving is possible right now.
 */
export default function useSaveShortcut( onSave, enabled ) {
	useEffect( () => {
		const onKeyDown = ( event ) => {
			if ( ! isKeyboardEvent.primary( event, 's' ) ) {
				return;
			}
			event.preventDefault();
			if ( enabled ) {
				onSave();
			}
		};
		document.addEventListener( 'keydown', onKeyDown );
		return () => document.removeEventListener( 'keydown', onKeyDown );
	}, [ onSave, enabled ] );
}
