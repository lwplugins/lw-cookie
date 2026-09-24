/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import StatusBadge from '../../components/StatusBadge';

const muted = ( text ) =>
	text || (
		<span className="lw-admin-muted">{ __( 'N/A', 'lw-cookie' ) }</span>
	);

/**
 * Declared-cookie table columns.
 *
 * @param {Object}   args
 * @param {Object}   args.labels   Category labels.
 * @param {Function} args.onEdit   ( row ).
 * @param {Function} args.onDelete ( row ).
 * @return {Array} Columns.
 */
export function cookieColumns( { labels, onEdit, onDelete } ) {
	return [
		{
			id: 'name',
			label: __( 'Cookie Name', 'lw-cookie' ),
			sortable: true,
			defaultSortDirection: 'asc',
			render: ( r ) => <code className="lw-admin-code">{ r.name }</code>,
		},
		{
			id: 'provider',
			label: __( 'Provider', 'lw-cookie' ),
			sortable: true,
			render: ( r ) => muted( r.provider ),
		},
		{
			id: 'purpose',
			label: __( 'Purpose', 'lw-cookie' ),
			render: ( r ) => muted( r.purpose ),
		},
		{
			id: 'duration',
			label: __( 'Duration', 'lw-cookie' ),
			render: ( r ) => muted( r.duration ),
		},
		{
			id: 'category',
			label: __( 'Category', 'lw-cookie' ),
			sortable: true,
			sortValue: ( r ) => labels[ r.category ] || r.category,
			render: ( r ) => (
				<StatusBadge
					status={ r.category === 'necessary' ? 'info' : 'idle' }
				>
					{ labels[ r.category ] || r.category }
				</StatusBadge>
			),
		},
		{
			id: 'type',
			label: __( 'Type', 'lw-cookie' ),
			render: ( r ) =>
				r.type === 'session'
					? __( 'Session', 'lw-cookie' )
					: __( 'Persistent', 'lw-cookie' ),
		},
		{
			id: 'actions',
			label: __( 'Actions', 'lw-cookie' ),
			render: ( r ) => (
				<span className="lw-admin-inline lw-admin-actions">
					<Button
						size="compact"
						variant="secondary"
						onClick={ () => onEdit( r ) }
						label={ sprintf(
							/* translators: %s: cookie name. */
							__( 'Edit %s', 'lw-cookie' ),
							r.name
						) }
						showTooltip={ false }
					>
						{ __( 'Edit', 'lw-cookie' ) }
					</Button>
					<Button
						size="compact"
						variant="tertiary"
						isDestructive
						onClick={ () => onDelete( r ) }
						label={ sprintf(
							/* translators: %s: cookie name. */
							__( 'Remove %s', 'lw-cookie' ),
							r.name
						) }
						showTooltip={ false }
					>
						{ __( 'Remove', 'lw-cookie' ) }
					</Button>
				</span>
			),
		},
	];
}
