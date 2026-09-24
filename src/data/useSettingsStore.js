/**
 * WordPress dependencies
 */
import { useDispatch } from '@wordpress/data';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import { api, errorMessage } from './api';

const same = ( a, b ) => JSON.stringify( a ) === JSON.stringify( b );

/**
 * Keys the save must never send: owned by the active multilingual plugin.
 *
 * @param {Object} meta Settings meta.
 * @return {string[]} Locked keys.
 */
export const lockedKeys = ( meta ) =>
	meta?.multilingual ? meta.locked_keys || [] : [];

/**
 * The lw_cookie_options draft. Save sends only changed keys, so a key that is
 * not on screen can never be reset by a save; locked keys are never sent.
 *
 * @return {Object} Store: data { options, meta }, set, hasEdits, save, discard…
 */
export default function useSettingsStore() {
	const [ server, setServer ] = useState( null );
	const [ options, setOptions ] = useState( null );
	const [ error, setError ] = useState( null );
	const [ isSaving, setIsSaving ] = useState( false );
	// "Unlock to edit source text": the locked keys become editable and a
	// save that touches them sends unlock_source_text so the server writes them.
	const [ isUnlocked, setIsUnlocked ] = useState( false );
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( noticesStore );

	const apply = ( data ) => {
		setServer( data );
		setOptions( data.options );
	};

	const reload = useCallback( () => {
		setError( null );
		return api
			.settings()
			.then( apply, ( e ) => setError( errorMessage( e ) ) );
	}, [] );

	useEffect( () => {
		reload();
	}, [ reload ] );

	const lockable = lockedKeys( server?.meta );
	const locked = isUnlocked ? [] : lockable;
	const patch = options
		? Object.fromEntries(
				Object.keys( options )
					.filter(
						( key ) =>
							! locked.includes( key ) &&
							! same( options[ key ], server.options[ key ] )
					)
					.map( ( key ) => [ key, options[ key ] ] )
			)
		: {};
	const hasEdits = Object.keys( patch ).length > 0;

	return {
		data: options ? { options, meta: server.meta } : null,
		isLoading: ! options && ! error,
		error,
		reload,
		locked,
		canUnlock: lockable.length > 0 && ! isUnlocked,
		unlock: () => setIsUnlocked( true ),
		set: ( key, value ) =>
			setOptions( ( prev ) => ( { ...prev, [ key ]: value } ) ),
		setMany: ( values ) =>
			setOptions( ( prev ) => ( { ...prev, ...values } ) ),
		hasEdits,
		isSaving,
		discard: () => setOptions( server.options ),
		save: async () => {
			if ( ! hasEdits ) {
				return;
			}
			setIsSaving( true );
			try {
				const touchesLocked = lockable.some( ( key ) => key in patch );
				apply(
					await api.saveSettings(
						touchesLocked
							? { ...patch, unlock_source_text: true }
							: patch
					)
				);
				createSuccessNotice( __( 'Settings saved.', 'lw-cookie' ), {
					type: 'snackbar',
				} );
			} catch ( e ) {
				createErrorNotice( errorMessage( e ), { type: 'snackbar' } );
			}
			setIsSaving( false );
		},
	};
}
