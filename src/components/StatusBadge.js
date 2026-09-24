/**
 * WordPress dependencies
 */
import { Badge } from '@wordpress/ui';

const INTENTS = {
	ok: 'stable',
	warning: 'medium',
	critical: 'high',
	info: 'informational',
	idle: 'draft',
};

/**
 * Status pill built on the WPDS Badge (bundled `@wordpress/ui`).
 *
 * @param {Object} props
 * @param {string} props.status   ok|warning|critical|info|idle.
 * @param {string} props.children Label.
 */
export default function StatusBadge( { status, children } ) {
	return <Badge intent={ INTENTS[ status ] || 'none' }>{ children }</Badge>;
}
