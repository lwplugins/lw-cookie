/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { AreaRow, TextRow } from '../components/Fields';
import MultilingualCallout from '../components/MultilingualCallout';
import Section from '../components/Section';
import StatusBadge from '../components/StatusBadge';

const CATEGORIES = () => [
	{
		id: 'necessary',
		title: __( 'Necessary Cookies', 'lw-cookie' ),
		description: __(
			'This category is always enabled, but you can customize the displayed name and description.',
			'lw-cookie'
		),
		required: true,
	},
	{ id: 'functional', title: __( 'Functional Cookies', 'lw-cookie' ) },
	{ id: 'analytics', title: __( 'Analytics Cookies', 'lw-cookie' ) },
	{ id: 'marketing', title: __( 'Marketing Cookies', 'lw-cookie' ) },
];

export default function CategoriesTab( { store } ) {
	const { meta } = store.data;
	const isLocked = ( key ) => store.locked.includes( key );

	return (
		<>
			<MultilingualCallout plugin={ meta.multilingual } store={ store } />
			<p className="lw-admin-muted">
				{ __(
					'Customize the names and descriptions of cookie categories shown to users.',
					'lw-cookie'
				) }
			</p>
			{ CATEGORIES().map( ( cat ) => (
				<Section
					key={ cat.id }
					title={ cat.title }
					description={ cat.description }
					badge={
						cat.required ? (
							<StatusBadge status="info">
								{ __( 'Always on', 'lw-cookie' ) }
							</StatusBadge>
						) : null
					}
				>
					<TextRow
						title={ __( 'Name', 'lw-cookie' ) }
						store={ store }
						name={ `cat_${ cat.id }_name` }
						disabled={ isLocked( `cat_${ cat.id }_name` ) }
					/>
					<AreaRow
						title={ __( 'Description', 'lw-cookie' ) }
						store={ store }
						name={ `cat_${ cat.id }_desc` }
						disabled={ isLocked( `cat_${ cat.id }_desc` ) }
					/>
				</Section>
			) ) }
		</>
	);
}
