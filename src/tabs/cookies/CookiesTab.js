/**
 * External dependencies
 */
import { DataTable, useTableState } from '@lwplugins/data-table';
import '@lwplugins/data-table/style.css';

/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import { plus, search } from '@wordpress/icons';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import CopyField from '../../components/CopyField';
import Section from '../../components/Section';
import StatusBadge from '../../components/StatusBadge';
import { mergeCookies } from '../../data/cookies';
import CookieForm from './CookieForm';
import { cookieColumns } from './columns';
import ScanModal from './ScanModal';
import { tableLabels } from './tableLabels';
import useScanner from './useScanner';

/**
 * Category labels for the table: meta.category_labels, with the draft names
 * of the optional categories so a rename shows up at once.
 *
 * @param {Object} options Draft options.
 * @param {Object} meta    Settings meta.
 * @return {Object} { slug: label }.
 */
const categoryLabels = ( options, meta ) => {
	const labels = { ...( meta.category_labels || {} ) };
	[ 'functional', 'analytics', 'marketing' ].forEach( ( slug ) => {
		if ( options[ `cat_${ slug }_name` ] ) {
			labels[ slug ] = options[ `cat_${ slug }_name` ];
		}
	} );
	return labels;
};

/**
 * Cookie declaration: scanner, the declared_cookies list (draft; saved with
 * the page Save as one array), common cookies, shortcode.
 *
 * @param {Object} props
 * @param {Object} props.store Settings store.
 */
export default function CookiesTab( { store } ) {
	const { options, meta } = store.data;
	const list = options.declared_cookies || [];
	const labels = categoryLabels( options, meta );
	const [ editing, setEditing ] = useState( null ); // null | 'new' | row
	const scanner = useScanner( meta.scan_urls || [] );
	const { createSuccessNotice } = useDispatch( noticesStore );

	const names = new Set( list.map( ( c ) => c.name ) );
	const isDeclared = ( c ) =>
		!! c.is_declared || names.has( c.name ) || names.has( c.original_name );
	const setList = ( next ) => store.set( 'declared_cookies', next );
	const addMany = ( extra ) => {
		const next = mergeCookies( list, extra );
		const added = next.length - list.length;
		setList( next );
		createSuccessNotice(
			added
				? sprintf(
						/* translators: %d: number of cookies added to the list. */
						_n(
							'%d cookie added. Save changes to keep it.',
							'%d cookies added. Save changes to keep them.',
							added,
							'lw-cookie'
						),
						added
					)
				: __( 'These cookies are already in the list.', 'lw-cookie' ),
			{ type: 'snackbar' }
		);
	};

	const rows = list.map( ( c, index ) => ( { ...c, index } ) );
	const columns = cookieColumns( {
		labels,
		onEdit: setEditing,
		onDelete: ( row ) =>
			setList( list.filter( ( c, i ) => i !== row.index ) ),
	} );
	const table = useTableState( rows, {
		searchFields: [ 'name', 'provider', 'purpose' ],
		columns,
		perPage: 25,
	} );

	const saveCookie = ( cookie ) => {
		const { index, ...clean } = cookie;
		setList(
			editing === 'new'
				? [ ...list, clean ]
				: list.map( ( c, i ) => ( i === editing.index ? clean : c ) )
		);
		setEditing( null );
	};

	return (
		<>
			<Section
				title={ __( 'Cookie Scanner', 'lw-cookie' ) }
				description={ __(
					'Scan your website to detect cookies in use.',
					'lw-cookie'
				) }
				actions={
					<Button
						__next40pxDefaultSize
						variant="primary"
						icon={ search }
						isBusy={
							!! scanner.state && scanner.state.phase !== 'done'
						}
						onClick={ scanner.start }
					>
						{ __( 'Scan Website', 'lw-cookie' ) }
					</Button>
				}
			/>

			<Section
				title={ __( 'Declared Cookies', 'lw-cookie' ) }
				badge={
					<StatusBadge status="idle">
						{ String( list.length ) }
					</StatusBadge>
				}
				actions={
					<div className="lw-admin-inline">
						<Button
							variant="secondary"
							onClick={ () =>
								addMany( meta.common_cookies || [] )
							}
						>
							{ __( 'Add Common Cookies', 'lw-cookie' ) }
						</Button>
						<Button
							variant="primary"
							icon={ plus }
							onClick={ () => setEditing( 'new' ) }
						>
							{ __( 'Add Cookie', 'lw-cookie' ) }
						</Button>
					</div>
				}
			>
				<DataTable
					columns={ columns }
					table={ table }
					caption={ __( 'Declared cookies', 'lw-cookie' ) }
					filters={ [
						{
							field: 'category',
							label: __( 'Category', 'lw-cookie' ),
							options: Object.entries( labels ).map(
								( [ value, label ] ) => ( {
									value,
									label,
								} )
							),
						},
					] }
					labels={ {
						...tableLabels(),
						search: __(
							'Search name, provider or purpose',
							'lw-cookie'
						),
					} }
					getRowId={ ( r ) => r.index }
				/>
			</Section>

			<Section
				title={ __( 'Shortcode', 'lw-cookie' ) }
				description={ __(
					'Use this shortcode to display the cookie list on any page.',
					'lw-cookie'
				) }
			>
				<CopyField
					text={ meta.shortcode || '[lw_cookie_declaration]' }
				/>
			</Section>

			{ editing && (
				<CookieForm
					initial={ editing === 'new' ? null : editing }
					labels={ labels }
					isTaken={ ( name ) => names.has( name ) }
					onClose={ () => setEditing( null ) }
					onSave={ saveCookie }
				/>
			) }
			{ scanner.state && (
				<ScanModal
					scanner={ scanner }
					isDeclared={ isDeclared }
					labels={ labels }
					onAdd={ addMany }
				/>
			) }
		</>
	);
}
