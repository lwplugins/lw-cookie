/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { external } from '@wordpress/icons';
import { addQueryArgs, removeQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { SelectRow, SwitchRow, TextRow } from '../components/Fields';
import Section from '../components/Section';
import SettingRow from '../components/SettingRow';

/**
 * Front-page preview link (Banner\Preview::url()). The server may send it as
 * meta.preview_url; otherwise it is built from the first scan URL (the home
 * page) without the scanner flag.
 *
 * @param {Object} meta Settings meta.
 * @return {string} URL or ''.
 */
const previewUrl = ( meta ) => {
	if ( meta.preview_url ) {
		return meta.preview_url;
	}
	const home = meta.scan_urls?.[ 0 ];
	return home
		? addQueryArgs( removeQueryArgs( home, 'lw_cookie_scan' ), {
				'lw-cookie-preview': 1,
			} )
		: '';
};

export default function GeneralTab( { store } ) {
	const { meta } = store.data;
	const preview = previewUrl( meta );

	return (
		<>
			<Section
				title={ __( 'General Settings', 'lw-cookie' ) }
				description={ __(
					'Basic configuration for your cookie consent banner.',
					'lw-cookie'
				) }
			>
				<SwitchRow
					title={ __( 'Enable Banner', 'lw-cookie' ) }
					help={ __(
						'Show cookie consent banner on the frontend',
						'lw-cookie'
					) }
					store={ store }
					name="enabled"
				/>
				{ preview && (
					<SettingRow
						title={ __( 'Preview', 'lw-cookie' ) }
						help={ __(
							'Opens the front page with the consent banner forced visible (administrators only), so you can review and tune it — even before enabling it or after you have already consented.',
							'lw-cookie'
						) }
					>
						<div className="lw-admin-inline">
							<Button
								__next40pxDefaultSize
								variant="secondary"
								icon={ external }
								iconPosition="right"
								href={ preview }
								target="_blank"
								rel="noopener noreferrer"
							>
								{ __(
									'Preview banner on the site',
									'lw-cookie'
								) }
							</Button>
						</div>
					</SettingRow>
				) }
				<SelectRow
					title={ __( 'Privacy Policy Page', 'lw-cookie' ) }
					help={ __(
						'Select your privacy policy page to link from the banner.',
						'lw-cookie'
					) }
					store={ store }
					name="privacy_policy_page"
					onChange={ ( value ) =>
						store.set( 'privacy_policy_page', Number( value ) || 0 )
					}
					options={ [
						{ value: '0', label: __( '— None —', 'lw-cookie' ) },
						...( meta.pages || [] ).map( ( page ) => ( {
							value: String( page.id ),
							label: page.title,
						} ) ),
					] }
				/>
				<TextRow
					title={ __( 'Policy Version', 'lw-cookie' ) }
					help={ __(
						'Change this when you update your privacy policy to request new consent.',
						'lw-cookie'
					) }
					store={ store }
					name="policy_version"
				/>
			</Section>
			<Section title={ __( 'GDPR Compliance Notes', 'lw-cookie' ) }>
				<ul className="lw-admin-list">
					<li>
						{ __(
							'All optional cookie categories are OFF by default (opt-in required).',
							'lw-cookie'
						) }
					</li>
					<li>
						{ __(
							'Users can granularly choose which categories to accept.',
							'lw-cookie'
						) }
					</li>
					<li>
						{ __(
							'Consent is logged with timestamp for compliance proof.',
							'lw-cookie'
						) }
					</li>
					<li>
						{ __(
							'Policy version changes trigger re-consent requests.',
							'lw-cookie'
						) }
					</li>
				</ul>
			</Section>
		</>
	);
}
