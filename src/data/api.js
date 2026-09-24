/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { NAMESPACE } from './boot';

const admin = ( route ) => `/${ NAMESPACE }/admin${ route }`;
const scan = ( route ) => `/${ NAMESPACE }/${ route }`;

export const api = {
	settings: () => apiFetch( { path: admin( '/settings' ) } ),
	saveSettings: ( patch ) =>
		apiFetch( { path: admin( '/settings' ), method: 'POST', data: patch } ),

	// Scanner routes (Scanner::register_rest_routes), unchanged from the
	// classic screen.
	clearScan: () => apiFetch( { path: scan( 'clear-scan' ), method: 'POST' } ),
	prescanHeaders: () =>
		apiFetch( { path: scan( 'prescan-headers' ), method: 'POST' } ),
	remoteScan: ( signal ) =>
		apiFetch( { path: scan( 'remote-scan' ), method: 'POST', signal } ),
	scanResults: () => apiFetch( { path: scan( 'scan-results' ) } ),
};

export const errorMessage = ( error ) =>
	error?.message ||
	'That did not work. Please reload the page and try again.';
