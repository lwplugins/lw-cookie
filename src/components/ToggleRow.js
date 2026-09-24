/**
 * WordPress dependencies
 */
import { FormToggle } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import SettingRow from './SettingRow';

/**
 * SettingRow with a switch on the right and a live state line next to it.
 *
 * @param {Object}   props
 * @param {string}   props.title    Setting name (labels the switch).
 * @param {Element}  props.help     Description.
 * @param {boolean}  props.checked  Value.
 * @param {Function} props.onChange Receives the new boolean.
 * @param {string}   props.onText   State text when on.
 * @param {string}   props.offText  State text when off.
 * @param {boolean}  props.disabled Disabled.
 */
export default function ToggleRow( {
	title,
	help,
	checked,
	onChange,
	onText,
	offText,
	disabled = false,
} ) {
	const id = useInstanceId( ToggleRow, 'lw-admin-toggle' );

	return (
		<SettingRow title={ title } help={ help } htmlFor={ id }>
			<span className="lw-admin-switch">
				<FormToggle
					id={ id }
					checked={ checked }
					disabled={ disabled }
					onChange={ ( event ) => onChange( event.target.checked ) }
				/>
				{ ( onText || offText ) && (
					<span className="lw-admin-switch__state">
						{ checked ? onText : offText }
					</span>
				) }
			</span>
		</SettingRow>
	);
}
