/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { NumberRow, SwitchRow } from '../components/Fields';
import Section from '../components/Section';

export default function AdvancedTab( { store } ) {
	return (
		<>
			<Section
				title={ __( 'Consent', 'lw-cookie' ) }
				description={ __(
					'Advanced configuration options.',
					'lw-cookie'
				) }
			>
				<NumberRow
					title={ __( 'Consent Duration', 'lw-cookie' ) }
					help={ __(
						'Days until consent expires and user is asked again.',
						'lw-cookie'
					) }
					store={ store }
					name="consent_duration"
					min={ 1 }
					max={ 730 }
					suffix={ __( 'days', 'lw-cookie' ) }
				/>
				<SwitchRow
					title={ __( 'Logged-in users', 'lw-cookie' ) }
					help={ __(
						'Do not show the consent banner to logged-in users — e.g. so it never pops up for administrators while they work. The banner is always hidden inside page-builder editors (Bricks, Elementor).',
						'lw-cookie'
					) }
					store={ store }
					name="hide_for_logged_in"
					onText={ __(
						'Hide the banner for logged-in users',
						'lw-cookie'
					) }
					offText={ __( 'Shown to everyone', 'lw-cookie' ) }
				/>
			</Section>
			<Section title={ __( 'Blocking', 'lw-cookie' ) }>
				<SwitchRow
					title={ __( 'Script Blocking', 'lw-cookie' ) }
					help={ __(
						'Automatically blocks known tracking scripts (Google Analytics, Facebook Pixel, etc.) until user consents.',
						'lw-cookie'
					) }
					store={ store }
					name="script_blocking"
					onText={ __(
						'Block scripts until consent is given',
						'lw-cookie'
					) }
					offText={ __( 'Off', 'lw-cookie' ) }
				/>
				<SwitchRow
					title={ __( 'Content Blocking', 'lw-cookie' ) }
					help={ __(
						'Blocks YouTube, Vimeo, Google Maps and other embedded content until user consents.',
						'lw-cookie'
					) }
					store={ store }
					name="content_blocking"
					onText={ __(
						'Block embedded content until consent is given',
						'lw-cookie'
					) }
					offText={ __( 'Off', 'lw-cookie' ) }
				/>
			</Section>
			<Section title={ __( 'Google Consent Mode v2', 'lw-cookie' ) }>
				<SwitchRow
					title={ __( 'Enable Google Consent Mode v2', 'lw-cookie' ) }
					help={ __(
						'Required for Google Ads and Analytics in the EU. Sends consent signals to Google services.',
						'lw-cookie'
					) }
					store={ store }
					name="gcm_enabled"
				/>
			</Section>
		</>
	);
}
