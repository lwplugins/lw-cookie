/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import LwPluginsWordmark from '../components/LwPluginsWordmark';

/**
 * Main column footer: LW Plugins wordmark linking to lwplugins.com.
 * Same height as the sidebar footer, so the two line up.
 */
export default function Footer() {
	return (
		<footer className="lw-admin-footer">
			<a
				href="https://lwplugins.com"
				target="_blank"
				rel="noopener noreferrer"
			>
				<LwPluginsWordmark />
				<span className="screen-reader-text">
					{ __( 'LW Plugins (opens in a new tab)', 'lw-cookie' ) }
				</span>
			</a>
		</footer>
	);
}
