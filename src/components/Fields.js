/**
 * Small field shorthands so the tabs stay declarative.
 */
/**
 * WordPress dependencies
 */
import {
	SelectControl,
	TextareaControl,
	TextControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import SettingRow from './SettingRow';
import ToggleRow from './ToggleRow';

export function TextRow( {
	title,
	help,
	store,
	name,
	type = 'text',
	placeholder,
	mono,
	disabled = false,
} ) {
	return (
		<SettingRow title={ title } help={ help }>
			<TextControl
				disabled={ disabled }
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ title }
				hideLabelFromVision
				type={ type }
				className={ mono ? 'lw-admin-mono' : undefined }
				placeholder={ placeholder }
				value={ store.data.options[ name ] ?? '' }
				onChange={ ( value ) => store.set( name, value ) }
			/>
		</SettingRow>
	);
}

export function AreaRow( {
	title,
	help,
	store,
	name,
	rows = 3,
	placeholder,
	mono,
	disabled = false,
} ) {
	return (
		<SettingRow title={ title } help={ help } stacked={ rows > 4 }>
			<TextareaControl
				disabled={ disabled }
				__nextHasNoMarginBottom
				label={ title }
				hideLabelFromVision
				rows={ rows }
				className={ mono ? 'lw-admin-mono' : undefined }
				placeholder={ placeholder }
				value={ store.data.options[ name ] ?? '' }
				onChange={ ( value ) => store.set( name, value ) }
			/>
		</SettingRow>
	);
}

export function SelectRow( { title, help, store, name, options, onChange } ) {
	return (
		<SettingRow title={ title } help={ help }>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ title }
				hideLabelFromVision
				value={ String( store.data.options[ name ] ?? '' ) }
				options={ options }
				onChange={
					onChange || ( ( value ) => store.set( name, value ) )
				}
			/>
		</SettingRow>
	);
}

export function SwitchRow( { title, help, store, name, onText, offText } ) {
	return (
		<ToggleRow
			title={ title }
			help={ help }
			checked={ !! store.data.options[ name ] }
			onChange={ ( value ) => store.set( name, value ) }
			onText={ onText }
			offText={ offText }
		/>
	);
}

/**
 * Number input. The value keeps the JS type the server sent (int options
 * stay numbers, numeric-string options like border_radius stay strings).
 *
 * @param {Object} props
 * @param {string} props.title  Title.
 * @param {string} props.help   Help.
 * @param {Object} props.store  Settings store.
 * @param {string} props.name   Option key.
 * @param {number} props.min    Minimum.
 * @param {number} props.max    Maximum.
 * @param {string} props.suffix Unit after the field.
 */
export function NumberRow( { title, help, store, name, min, max, suffix } ) {
	const current = store.data.options[ name ];
	const onChange = ( value ) =>
		store.set(
			name,
			typeof current === 'number' ? Number( value ) || 0 : value
		);

	return (
		<SettingRow title={ title } help={ help }>
			<div className="lw-admin-inline lw-admin-number">
				<TextControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ title }
					hideLabelFromVision
					type="number"
					min={ min }
					max={ max }
					value={ String( current ?? '' ) }
					onChange={ onChange }
				/>
				{ suffix && <span className="lw-admin-muted">{ suffix }</span> }
			</div>
		</SettingRow>
	);
}
