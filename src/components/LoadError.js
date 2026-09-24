/**
 * WordPress dependencies
 */
import { Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Load failure with a retry button.
 *
 * @param {Object}   props
 * @param {string}   props.message Error text.
 * @param {Function} props.onRetry Retry.
 */
export default function LoadError( { message, onRetry } ) {
	return (
		<Notice
			status="error"
			isDismissible={ false }
			actions={ [
				{ label: __( 'Try again', 'lw-cookie' ), onClick: onRetry },
			] }
		>
			{ message }
		</Notice>
	);
}
