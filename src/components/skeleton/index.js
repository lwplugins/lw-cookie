/**
 * Loading placeholders — the reusable pattern for LW React admins.
 *
 * Every shape is the WPDS <Skeleton> (bundled `@wordpress/ui`: pulse,
 * reduced-motion aware, aria-hidden). A screen or panel that is loading wraps
 * its shapes in <SkeletonRegion>, which carries the accessible "Loading" state.
 * Skeletons mirror the real layout (same cards, tiles, rows) so nothing jumps
 * when data arrives.
 */
/**
 * WordPress dependencies
 */
import { Card, CardBody, CardHeader } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Skeleton } from '@wordpress/ui';

const repeat = ( count, render ) =>
	Array.from( { length: count }, ( _, index ) => render( index ) );

/**
 * Accessible wrapper: role=status + aria-busy, one screen-reader label.
 *
 * @param {Object}  props
 * @param {string}  props.label     Announced text.
 * @param {string}  props.className Extra class.
 * @param {Element} props.children  Skeleton shapes.
 */
export function SkeletonRegion( { label, className = '', children } ) {
	return (
		<div
			className={ `lw-skel-region ${ className }` }
			role="status"
			aria-busy="true"
		>
			<span className="screen-reader-text">
				{ label || __( 'Loading…', 'lw-cookie' ) }
			</span>
			{ children }
		</div>
	);
}

/**
 * One text line (or several, the last one shorter).
 *
 * @param {Object}        props
 * @param {string|number} props.width CSS width of a single line.
 * @param {number}        props.lines Line count.
 * @param {string}        props.size  sm|md|lg|xl (line height).
 */
export function SkeletonText( { width = '100%', lines = 1, size = 'md' } ) {
	return (
		<span className="lw-skel-lines">
			{ repeat( lines, ( index ) => (
				<Skeleton
					key={ index }
					className={ `lw-skel lw-skel--text is-${ size }` }
					style={ {
						width: lines > 1 && index === lines - 1 ? '60%' : width,
					} }
				/>
			) ) }
		</span>
	);
}

/**
 * Any block shape (bars, charts, inputs).
 *
 * @param {Object}        props
 * @param {string|number} props.width  CSS width.
 * @param {string|number} props.height CSS height.
 * @param {boolean}       props.round  Pill / circle radius.
 */
export function SkeletonBlock( {
	width = '100%',
	height = 40,
	round = false,
} ) {
	return (
		<Skeleton
			className={ `lw-skel ${ round ? 'is-round' : '' }` }
			style={ { width, height } }
		/>
	);
}

/**
 * A Section card placeholder: header line + body.
 *
 * @param {Object}  props
 * @param {boolean} props.description Show a description line.
 * @param {Element} props.children    Body shapes (defaults to 3 text lines).
 */
export function SkeletonSection( { description = true, children } ) {
	return (
		<Card className="lw-admin-section">
			<CardHeader>
				<span className="lw-skel-stack">
					<SkeletonText width="30%" size="lg" />
					{ description && <SkeletonText width="55%" size="sm" /> }
				</span>
			</CardHeader>
			<CardBody>
				<span className="lw-skel-stack is-loose">
					{ children || <SkeletonText lines={ 3 } /> }
				</span>
			</CardBody>
		</Card>
	);
}

/**
 * List/table rows: label | detail | badge.
 *
 * @param {Object} props
 * @param {number} props.count Row count.
 */
export function SkeletonRows( { count = 5 } ) {
	return (
		<span className="lw-skel-rows">
			{ repeat( count, ( index ) => (
				<span key={ index } className="lw-skel-row">
					<SkeletonText
						width={ `${ 20 + ( ( index * 7 ) % 15 ) }%` }
					/>
					<SkeletonText
						width={ `${ 35 + ( ( index * 11 ) % 25 ) }%` }
					/>
					<SkeletonBlock width={ 56 } height={ 20 } round />
				</span>
			) ) }
		</span>
	);
}
