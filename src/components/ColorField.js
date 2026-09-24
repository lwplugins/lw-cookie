/**
 * WordPress dependencies
 */
import {
	Button,
	ColorIndicator,
	ColorPicker,
	Dropdown,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SettingRow from './SettingRow';

/**
 * Colour option: swatch + hex button opening the core ColorPicker.
 *
 * @param {Object} props
 * @param {string} props.title Title.
 * @param {string} props.help  Help.
 * @param {Object} props.store Settings store.
 * @param {string} props.name  Option key.
 */
export default function ColorField( { title, help, store, name } ) {
	const value = store.data.options[ name ] || '';

	return (
		<SettingRow title={ title } help={ help }>
			<Dropdown
				className="lw-admin-color"
				popoverProps={ { placement: 'bottom-start' } }
				renderToggle={ ( { isOpen, onToggle } ) => (
					<Button
						__next40pxDefaultSize
						variant="secondary"
						className="lw-admin-color__toggle"
						aria-expanded={ isOpen }
						aria-label={ sprintf(
							/* translators: 1: setting name, 2: hex colour. */
							__( '%1$s: %2$s', 'lw-cookie' ),
							title,
							value || __( 'not set', 'lw-cookie' )
						) }
						onClick={ onToggle }
					>
						<ColorIndicator colorValue={ value } />
						<code>{ value }</code>
					</Button>
				) }
				renderContent={ () => (
					<ColorPicker
						color={ value }
						onChange={ ( hex ) => store.set( name, hex ) }
					/>
				) }
			/>
		</SettingRow>
	);
}
