/**
 * WordPress dependencies
 */
import {
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

/**
 * Soft segmented control (styled core ToggleGroupControl) from a value→label map.
 *
 * @param {Object}   props
 * @param {string}   props.label    Accessible label (hidden).
 * @param {Object}   props.options  { value: label }.
 * @param {string}   props.value    Current value.
 * @param {Function} props.onChange Receives the value.
 */
export default function Segmented( { label, options, value, onChange } ) {
	return (
		<ToggleGroupControl
			__next40pxDefaultSize
			isBlock
			hideLabelFromVision
			label={ label }
			value={ value }
			onChange={ onChange }
		>
			{ Object.entries( options ).map( ( [ key, text ] ) => (
				<ToggleGroupControlOption
					key={ key }
					value={ key }
					label={ text }
				/>
			) ) }
		</ToggleGroupControl>
	);
}
