/**
 * WordPress dependencies
 */
import { Button, Modal, ProgressBar } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { plus } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import ScanResults from './ScanResults';

/**
 * Progress line + percentage for the running phases.
 *
 * @param {Object} state Scanner state.
 * @return {Array} [ text, percent|undefined ].
 */
const progress = ( state ) => {
	if ( state.phase === 'pages' ) {
		return [
			sprintf(
				/* translators: 1: current page number, 2: total pages. */
				__(
					'Scanning your website for cookies… page %1$d of %2$d',
					'lw-cookie'
				),
				state.page,
				state.total
			),
			Math.round( ( ( state.page - 1 ) / state.total ) * 100 ),
		];
	}
	if ( state.phase === 'deep' ) {
		return [
			__( 'Deep scanning for additional cookies…', 'lw-cookie' ),
			undefined,
		];
	}
	return [
		__( 'Scanning your website for cookies…', 'lw-cookie' ),
		undefined,
	];
};

/**
 * Scanner modal: hidden scan iframe, progress, then results + "Add selected".
 *
 * @param {Object}   props
 * @param {Object}   props.scanner    useScanner() result.
 * @param {Function} props.isDeclared ( cookie ) => bool.
 * @param {Object}   props.labels     Category labels.
 * @param {Function} props.onAdd      Receives the selected scan cookies.
 */
export default function ScanModal( { scanner, isDeclared, labels, onAdd } ) {
	const { state, cancel, frameRef } = scanner;
	const [ selected, setSelected ] = useState( new Set() );
	const results = state.phase === 'done' ? state.results : null;

	useEffect( () => {
		if ( results ) {
			setSelected(
				new Set(
					results.cookies
						.map( ( c, i ) => ( isDeclared( c ) ? -1 : i ) )
						.filter( ( i ) => i >= 0 )
				)
			);
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ results ] );

	const toggle = ( index, checked ) =>
		setSelected( ( prev ) => {
			const next = new Set( prev );
			if ( checked ) {
				next.add( index );
			} else {
				next.delete( index );
			}
			return next;
		} );

	const chosen = results
		? results.cookies.filter(
				( c, i ) => selected.has( i ) && ! isDeclared( c )
			)
		: [];
	const [ text, percent ] = results ? [] : progress( state );

	return (
		<Modal
			title={ __( 'Scan Results', 'lw-cookie' ) }
			onRequestClose={ cancel }
			size="medium"
			className="lw-scan-modal"
		>
			<iframe
				ref={ frameRef }
				title={ __( 'Cookie scanner', 'lw-cookie' ) }
				className="lw-scan__frame"
				tabIndex={ -1 }
				aria-hidden="true"
			/>
			{ results ? (
				<>
					{ state.error && (
						<p
							className="lw-admin-testresult is-error"
							role="alert"
						>
							{ state.error }
						</p>
					) }
					<ScanResults
						results={ results }
						isDeclared={ isDeclared }
						selected={ selected }
						onToggle={ toggle }
						labels={ labels }
					/>
				</>
			) : (
				<div className="lw-scan__progress" role="status">
					<p>{ text }</p>
					<ProgressBar value={ percent } />
				</div>
			) }
			<div className="lw-admin-inline lw-admin-form__actions lw-scan__actions">
				<Button
					__next40pxDefaultSize
					variant="tertiary"
					onClick={ cancel }
				>
					{ results
						? __( 'Close', 'lw-cookie' )
						: __( 'Cancel', 'lw-cookie' ) }
				</Button>
				{ chosen.length > 0 && (
					<Button
						__next40pxDefaultSize
						variant="primary"
						icon={ plus }
						onClick={ () => {
							onAdd( chosen );
							cancel();
						} }
					>
						{ __( 'Add Selected', 'lw-cookie' ) }
					</Button>
				) }
			</div>
		</Modal>
	);
}
