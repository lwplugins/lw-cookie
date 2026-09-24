/**
 * WordPress dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import App from './App';
import './admin.scss';

const root = document.getElementById( 'lw-cookie-root' );

if ( root ) {
	createRoot( root ).render( <App /> );
}
