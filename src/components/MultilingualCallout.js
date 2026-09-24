/**
 * WordPress dependencies
 */
import { Button, ExternalLink } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Callout from './Callout';

/**
 * Shown on Categories + Texts while a multilingual plugin owns the strings.
 *
 * @param {Object}      props
 * @param {Object|null} props.plugin meta.multilingual ({ slug, name, url }).
 * @param {Object}      props.store  Settings store (canUnlock / unlock).
 */
export default function MultilingualCallout( { plugin, store } ) {
	if ( ! plugin ) {
		return null;
	}

	return (
		<Callout tone="warning">
			<strong>
				{ sprintf(
					/* translators: %s: multilingual plugin name */
					__(
						'%s detected — these fields are managed there.',
						'lw-cookie'
					),
					plugin.name
				) }
			</strong>{ ' ' }
			{ __(
				'These fields hold the source-language strings only. Their values will not take effect on the frontend — translations are delivered by your multilingual plugin. Editing the source text here can invalidate existing translations.',
				'lw-cookie'
			) }{ ' ' }
			{ plugin.url && (
				<ExternalLink href={ plugin.url }>
					{ sprintf(
						/* translators: %s: multilingual plugin name */
						__( 'Manage translations in %s', 'lw-cookie' ),
						plugin.name
					) }
				</ExternalLink>
			) }
			{ store.canUnlock && (
				<>
					{ ' ' }
					<Button variant="link" onClick={ store.unlock }>
						{ __( 'Unlock to edit source text', 'lw-cookie' ) }
					</Button>
				</>
			) }
		</Callout>
	);
}
