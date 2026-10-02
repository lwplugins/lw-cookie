/**
 * WordPress dependencies
 */
import {
	Button,
	__experimentalText as Text,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import { api, errorMessage } from '../data/api';
import Callout from '../components/Callout';
import Section from '../components/Section';

const list = ( items ) => ( items.length ? items.join( ', ' ) : '—' );

/**
 * "Import from Complianz": preview what would change, then import. Shown
 * only while Complianz data exists in the database.
 *
 * @param {Object} props
 * @param {Object} props.store Settings store.
 */
export default function MigrationSection( { store } ) {
	const [ report, setReport ] = useState( null );
	const [ busy, setBusy ] = useState( false );
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( noticesStore );

	if ( ! store.data.meta.complianz_data ) {
		return null;
	}

	const call = async ( request, onDone ) => {
		setBusy( true );
		try {
			onDone( await request() );
		} catch ( e ) {
			createErrorNotice( errorMessage( e ), { type: 'snackbar' } );
		}
		setBusy( false );
	};

	const preview = () =>
		call( api.complianzPreview, ( data ) => setReport( data.report ) );

	const runImport = () =>
		call( api.complianzImport, async () => {
			setReport( null );
			await store.reload();
			createSuccessNotice(
				__(
					'Complianz settings imported. Deactivate Complianz so only one banner is shown.',
					'lw-cookie'
				),
				{ type: 'snackbar' }
			);
		} );

	const settings = report ? Object.keys( report.settings ) : [];
	const changes = settings.length + ( report?.cookies_added.length || 0 );

	return (
		<Section
			title={ __( 'Import from Complianz', 'lw-cookie' ) }
			description={ __(
				'Complianz settings were found in the database. Import the banner texts, colours, position, consent duration, Google Consent Mode and declared cookies. Cookies you already declared are kept.',
				'lw-cookie'
			) }
		>
			{ report && (
				<VStack spacing={ 2 }>
					<Text>
						{ sprintf(
							/* translators: %d: number of changes */
							_n(
								'%d change will be imported.',
								'%d changes will be imported.',
								changes,
								'lw-cookie'
							),
							changes
						) }
					</Text>
					<Text variant="muted">
						{ __( 'Settings:', 'lw-cookie' ) } { list( settings ) }
					</Text>
					<Text variant="muted">
						{ __( 'New cookies:', 'lw-cookie' ) }{ ' ' }
						{ list( report.cookies_added ) }
					</Text>
					<Text variant="muted">
						{ __( 'Already declared (kept):', 'lw-cookie' ) }{ ' ' }
						{ list( report.cookies_existing ) }
					</Text>
					{ report.cookies_skipped > 0 && (
						<Text variant="muted">
							{ sprintf(
								/* translators: %d: number of cookies */
								_n(
									'%d cookie without a category in Complianz is skipped.',
									'%d cookies without a category in Complianz are skipped.',
									report.cookies_skipped,
									'lw-cookie'
								),
								report.cookies_skipped
							) }
						</Text>
					) }
					{ report.locked.length > 0 && (
						<Callout tone="warning">
							{ __(
								'Texts managed by your multilingual plugin are not imported:',
								'lw-cookie'
							) }{ ' ' }
							{ list( report.locked ) }
						</Callout>
					) }
				</VStack>
			) }
			{ store.hasEdits && (
				<Callout tone="warning">
					{ __(
						'Save or discard your unsaved changes before importing.',
						'lw-cookie'
					) }
				</Callout>
			) }
			<div>
				{ report ? (
					<Button
						variant="primary"
						onClick={ runImport }
						isBusy={ busy }
						disabled={ busy || store.hasEdits || changes === 0 }
					>
						{ __( 'Import now', 'lw-cookie' ) }
					</Button>
				) : (
					<Button
						variant="secondary"
						onClick={ preview }
						isBusy={ busy }
						disabled={ busy }
					>
						{ __( 'Check what will be imported', 'lw-cookie' ) }
					</Button>
				) }
			</div>
		</Section>
	);
}
