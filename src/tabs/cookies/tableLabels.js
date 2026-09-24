/**
 * WordPress dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Translated UI strings for `@lwplugins/data-table` (it has no text domain).
 *
 * @return {Object} Labels.
 */
export function tableLabels() {
	return {
		search: __( 'Search', 'lw-cookie' ),
		filter: __( 'Filter', 'lw-cookie' ),
		clear: __( 'Clear', 'lw-cookie' ),
		clearAll: __( 'Clear all filters', 'lw-cookie' ),
		all: __( 'All', 'lw-cookie' ),
		empty: __( 'No cookies match these filters.', 'lw-cookie' ),
		emptyAll: __(
			'No cookies declared yet. Scan your site or add them manually.',
			'lw-cookie'
		),
		loading: __( 'Loading…', 'lw-cookie' ),
		previous: __( 'Previous page', 'lw-cookie' ),
		next: __( 'Next page', 'lw-cookie' ),
		perPage: __( 'Rows per page', 'lw-cookie' ),
		selectAll: __( 'Select all cookies on this page', 'lw-cookie' ),
		clearSelection: __( 'Clear selection', 'lw-cookie' ),
		bulkActions: __( 'Bulk actions', 'lw-cookie' ),
		entries: ( n ) =>
			sprintf(
				/* translators: %d: number of rows. */ _n(
					'%d entry',
					'%d entries',
					n,
					'lw-cookie'
				),
				n
			),
		results: ( n ) =>
			sprintf(
				/* translators: %d: number of results. */ _n(
					'%d result',
					'%d results',
					n,
					'lw-cookie'
				),
				n
			),
		page: ( p, t ) =>
			sprintf(
				/* translators: 1: current page, 2: total pages. */ __(
					'Page %1$d of %2$d',
					'lw-cookie'
				),
				p,
				t
			),
		selectRow: ( label ) =>
			sprintf(
				/* translators: %s: cookie name. */ __(
					'Select: %s',
					'lw-cookie'
				),
				label
			),
		selected: ( n, onPage ) =>
			n === onPage
				? sprintf(
						/* translators: %d: number of selected cookies. */
						_n( '%d selected', '%d selected', n, 'lw-cookie' ),
						n
					)
				: sprintf(
						/* translators: 1: selected cookies, 2: of those, on this page. */
						__( '%1$d selected, %2$d on this page', 'lw-cookie' ),
						n,
						onPage
					),
		eligible: ( e, n ) =>
			sprintf(
				/* translators: 1: cookies the action applies to, 2: selected cookies on this page. */ __(
					'applies to %1$d of %2$d',
					'lw-cookie'
				),
				e,
				n
			),
	};
}
