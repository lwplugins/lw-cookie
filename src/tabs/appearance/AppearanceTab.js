/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ColorField from '../../components/ColorField';
import { NumberRow, SwitchRow } from '../../components/Fields';
import Section from '../../components/Section';
import Segmented from '../../components/Segmented';
import SettingRow from '../../components/SettingRow';
import BannerPreview from './BannerPreview';

/**
 * SettingRow + soft segmented control bound to one option.
 *
 * @param {Object} props
 * @param {string} props.title   Title.
 * @param {string} props.help    Help.
 * @param {Object} props.store   Settings store.
 * @param {string} props.name    Option key.
 * @param {Object} props.options { value: label }.
 */
function SegmentedRow( { title, help, store, name, options } ) {
	return (
		<SettingRow title={ title } help={ help }>
			<Segmented
				label={ title }
				options={ options }
				value={ store.data.options[ name ] }
				onChange={ ( value ) => store.set( name, value ) }
			/>
		</SettingRow>
	);
}

export default function AppearanceTab( { store } ) {
	const { options, meta } = store.data;

	return (
		<>
			<Section
				title={ __( 'Preview', 'lw-cookie' ) }
				description={ __(
					'A simplified live preview of the banner with your current, unsaved settings.',
					'lw-cookie'
				) }
			>
				<BannerPreview options={ options } meta={ meta } />
			</Section>
			<Section
				title={ __( 'Appearance Settings', 'lw-cookie' ) }
				description={ __(
					'Customize the look and feel of your cookie banner.',
					'lw-cookie'
				) }
			>
				<SegmentedRow
					title={ __( 'Banner Position', 'lw-cookie' ) }
					store={ store }
					name="banner_position"
					options={ {
						bottom: __( 'Bottom', 'lw-cookie' ),
						top: __( 'Top', 'lw-cookie' ),
						modal: __( 'Modal (Center)', 'lw-cookie' ),
					} }
				/>
				<SegmentedRow
					title={ __( 'Banner Layout', 'lw-cookie' ) }
					store={ store }
					name="banner_layout"
					options={ {
						bar: __( 'Full-width Bar', 'lw-cookie' ),
						box: __( 'Floating Box', 'lw-cookie' ),
					} }
				/>
				<SegmentedRow
					title={ __( 'Floating Box Alignment', 'lw-cookie' ) }
					help={ __(
						'Only applies when the layout is set to Floating Box.',
						'lw-cookie'
					) }
					store={ store }
					name="banner_box_alignment"
					options={ {
						left: __( 'Left', 'lw-cookie' ),
						right: __( 'Right', 'lw-cookie' ),
					} }
				/>
				<ColorField
					title={ __( 'Primary Color', 'lw-cookie' ) }
					help={ __( 'Button and accent color.', 'lw-cookie' ) }
					store={ store }
					name="primary_color"
				/>
				<ColorField
					title={ __( 'Text Color', 'lw-cookie' ) }
					store={ store }
					name="text_color"
				/>
				<ColorField
					title={ __( 'Background Color', 'lw-cookie' ) }
					store={ store }
					name="background_color"
				/>
				<NumberRow
					title={ __( 'Border Radius', 'lw-cookie' ) }
					help={ __( 'Border radius in pixels.', 'lw-cookie' ) }
					store={ store }
					name="border_radius"
					min={ 0 }
					max={ 50 }
					suffix="px"
				/>
			</Section>
			<Section title={ __( 'Floating Button', 'lw-cookie' ) }>
				<SwitchRow
					title={ __( 'Show Floating Button', 'lw-cookie' ) }
					help={ __(
						'Show a floating button for users to change their consent',
						'lw-cookie'
					) }
					store={ store }
					name="show_floating_button"
				/>
				<SegmentedRow
					title={ __( 'Button Position', 'lw-cookie' ) }
					help={ __(
						'Applies only to the Full-width Bar layout. With the Floating Box layout the button mirrors the banner alignment.',
						'lw-cookie'
					) }
					store={ store }
					name="floating_button_pos"
					options={ {
						'bottom-left': __( 'Bottom Left', 'lw-cookie' ),
						'bottom-right': __( 'Bottom Right', 'lw-cookie' ),
					} }
				/>
			</Section>
		</>
	);
}
