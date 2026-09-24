/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { useCopyToClipboard } from '@wordpress/compose';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { copy } from '@wordpress/icons';

/**
 * Copy-to-clipboard button with a short "Copied" confirmation.
 *
 * @param {Object} props
 * @param {string} props.text  Text to copy.
 * @param {string} props.label Button label.
 */
export function CopyButton( { text, label = __( 'Copy', 'lw-cookie' ) } ) {
	const [ copied, setCopied ] = useState( false );
	const ref = useCopyToClipboard( text, () => {
		setCopied( true );
		setTimeout( () => setCopied( false ), 1500 );
	} );

	return (
		<Button ref={ ref } size="compact" variant="secondary" icon={ copy }>
			{ copied ? __( 'Copied', 'lw-cookie' ) : label }
		</Button>
	);
}

/**
 * Read-only monospace line + copy button (cron lines, status URL).
 *
 * @param {Object} props
 * @param {string} props.text Text.
 */
export default function CopyField( { text } ) {
	return (
		<div className="lw-admin-copy">
			<code>{ text }</code>
			<CopyButton text={ text } />
		</div>
	);
}
