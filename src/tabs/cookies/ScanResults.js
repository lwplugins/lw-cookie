/**
 * WordPress dependencies
 */
import { CheckboxControl } from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Callout from '../../components/Callout';
import StatusBadge from '../../components/StatusBadge';

/**
 * Summary line, as in the classic modal.
 *
 * @param {Object} props
 * @param {Object} props.results  { cookies, domains, fonts }.
 * @param {number} props.newCount Cookies not declared yet.
 */
function Summary( { results, newCount } ) {
	const { cookies, domains, fonts } = results;
	if ( cookies.length + domains.length + fonts.length === 0 ) {
		return (
			<Callout>
				{ __(
					'No cookies detected. Try visiting your site first to set some cookies.',
					'lw-cookie'
				) }
			</Callout>
		);
	}
	if ( newCount === 0 && ! domains.length && ! fonts.length ) {
		return (
			<Callout>
				{ __(
					'All detected cookies are already in your list!',
					'lw-cookie'
				) }
			</Callout>
		);
	}
	const parts = [];
	if ( newCount ) {
		parts.push(
			sprintf(
				/* translators: %d: number of new cookies. */
				_n( '%d new cookie', '%d new cookies', newCount, 'lw-cookie' ),
				newCount
			)
		);
	}
	if ( domains.length ) {
		parts.push(
			sprintf(
				/* translators: %d: number of external domains. */
				_n(
					'%d external domain',
					'%d external domains',
					domains.length,
					'lw-cookie'
				),
				domains.length
			)
		);
	}
	if ( fonts.length ) {
		parts.push(
			sprintf(
				/* translators: %d: number of external fonts. */
				_n(
					'%d external font',
					'%d external fonts',
					fonts.length,
					'lw-cookie'
				),
				fonts.length
			)
		);
	}
	return (
		<Callout tone="warning">
			<strong>
				{ sprintf(
					/* translators: %s: comma-separated counts, e.g. "3 new cookies, 1 external domain". */
					__( '%s found.', 'lw-cookie' ),
					parts.join( ', ' )
				) }
			</strong>
		</Callout>
	);
}

/**
 * Scan results: cookies (checkbox each; declared ones marked, locked and
 * unchecked), external domains and fonts as information.
 *
 * @param {Object}   props
 * @param {Object}   props.results    { cookies, domains, fonts }.
 * @param {Function} props.isDeclared ( cookie ) => bool.
 * @param {Set}      props.selected   Selected cookie indexes.
 * @param {Function} props.onToggle   ( index, checked ).
 * @param {Object}   props.labels     Category labels.
 */
export default function ScanResults( {
	results,
	isDeclared,
	selected,
	onToggle,
	labels,
} ) {
	const { cookies, domains, fonts } = results;
	const newCount = cookies.filter( ( c ) => ! isDeclared( c ) ).length;

	return (
		<div className="lw-scan">
			<Summary results={ results } newCount={ newCount } />
			{ cookies.length > 0 && (
				<>
					<h3 className="lw-scan__heading">
						{ `${ __( 'Cookies', 'lw-cookie' ) } (${ cookies.length })` }
					</h3>
					<ul className="lw-scan__list">
						{ cookies.map( ( cookie, index ) => {
							const declared = isDeclared( cookie );
							return (
								<li
									key={ `${ cookie.original_name || cookie.name }-${ index }` }
									className={ `lw-scan__item ${ declared ? 'is-declared' : '' }` }
								>
									<CheckboxControl
										__nextHasNoMarginBottom
										label={
											<code className="lw-scan__name">
												{ cookie.original_name ||
													cookie.name }
											</code>
										}
										checked={
											! declared && selected.has( index )
										}
										disabled={ declared }
										onChange={ ( checked ) =>
											onToggle( index, checked )
										}
									/>
									<span className="lw-admin-stack lw-scan__body">
										<span className="lw-admin-inline">
											<span
												className={ `lw-scan__cat is-${ cookie.category || 'unknown' }` }
											>
												{ labels[ cookie.category ] ||
													cookie.category ||
													__(
														'Unknown',
														'lw-cookie'
													) }
											</span>
											{ cookie.source === 'api' && (
												<StatusBadge status="info">
													API
												</StatusBadge>
											) }
											{ declared && (
												<StatusBadge status="ok">
													{ __(
														'Already added',
														'lw-cookie'
													) }
												</StatusBadge>
											) }
										</span>
										<span className="lw-admin-hint">
											{ cookie.provider ||
												__(
													'Unknown provider',
													'lw-cookie'
												) }
											{ ' — ' }
											{ cookie.purpose ||
												__(
													'Purpose not specified',
													'lw-cookie'
												) }
										</span>
									</span>
								</li>
							);
						} ) }
					</ul>
				</>
			) }
			{ domains.length > 0 && (
				<>
					<h3 className="lw-scan__heading">
						{ `${ __( 'External Domains', 'lw-cookie' ) } (${ domains.length })` }
					</h3>
					<ul className="lw-scan__list">
						{ domains.map( ( domain ) => (
							<li
								key={ domain }
								className="lw-scan__item is-info"
							>
								<code className="lw-scan__name">
									{ domain }
								</code>
								<span className="lw-scan__cat is-functional">
									{ __( 'External', 'lw-cookie' ) }
								</span>
							</li>
						) ) }
					</ul>
				</>
			) }
			{ fonts.length > 0 && (
				<>
					<h3 className="lw-scan__heading">
						{ `${ __( 'External Fonts', 'lw-cookie' ) } (${ fonts.length })` }
					</h3>
					<ul className="lw-scan__list">
						{ fonts.map( ( font ) => {
							const [ family, host ] =
								String( font ).split( '|' );
							return (
								<li
									key={ font }
									className="lw-scan__item is-info"
								>
									<code className="lw-scan__name">
										{ family || font }
									</code>
									<span className="lw-scan__cat is-functional">
										{ __( 'Font', 'lw-cookie' ) }
									</span>
									{ host && (
										<span className="lw-admin-hint">
											{ host }
										</span>
									) }
								</li>
							);
						} ) }
					</ul>
				</>
			) }
		</div>
	);
}
