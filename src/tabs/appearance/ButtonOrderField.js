/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { arrowDown, arrowUp, dragHandle } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import SettingRow from '../../components/SettingRow';
import { fallbackLabel, text } from './BannerPreview';
import {
	BUTTONS,
	DEFAULT_ORDER,
	moveButton,
	resolveOrder,
} from './buttonOrder';

/**
 * Sortable list of the three first-layer banner buttons. Rows are dragged
 * with the mouse (native HTML5 drag and drop, no library); the arrow buttons
 * do the same from the keyboard and on touch screens, where native drag
 * does not work.
 *
 * @param {Object} props
 * @param {Object} props.store Settings store.
 */
export default function ButtonOrderField( { store } ) {
	const { options, meta } = store.data;
	const order = resolveOrder( options.banner_button_order );
	const [ dragging, setDragging ] = useState( null );
	const [ status, setStatus ] = useState( '' );
	const arrows = useRef( {} );

	const label = ( key ) =>
		text( options, meta, BUTTONS[ key ].textKey ) || fallbackLabel( key );

	const save = ( next ) =>
		store.set( 'banner_button_order', next.join( ',' ) );

	const move = ( key, to, direction ) => {
		const next = moveButton( order, key, to );
		save( next );
		setStatus(
			sprintf(
				/* translators: 1: button label, 2: position number */
				__( '%1$s moved to position %2$d.', 'lw-cookie' ),
				label( key ),
				to + 1
			)
		);
		// Keep focus on the moved row; at the end of the list the pressed
		// arrow gets disabled, so the other one takes the focus.
		window.requestAnimationFrame( () => {
			const opposite = 'up' === direction ? 'down' : 'up';
			const atEdge =
				'up' === direction ? 0 === to : next.length - 1 === to;
			const target = atEdge ? opposite : direction;
			arrows.current[ `${ key }-${ target }` ]?.focus();
		} );
	};

	return (
		<SettingRow
			title={ __( 'Button Order', 'lw-cookie' ) }
			help={ __(
				'Drag the buttons into order, or use the arrows. The top of the list is the first button on the banner: leftmost, and on top on phones. The keyboard (Tab) and screen-reader order follow it too.',
				'lw-cookie'
			) }
		>
			<div
				className="lw-cookie-order"
				style={ {
					'--lw-preview-primary': options.primary_color || '#2271b1',
					'--lw-preview-text': options.text_color || '#1d2327',
				} }
			>
				<ol
					className="lw-cookie-order__list"
					aria-label={ __( 'Button Order', 'lw-cookie' ) }
				>
					{ order.map( ( key, index ) => (
						<li
							key={ key }
							className={ `lw-cookie-order__row${
								dragging === key ? ' is-dragging' : ''
							}` }
							draggable
							onDragStart={ ( event ) => {
								event.dataTransfer.effectAllowed = 'move';
								event.dataTransfer.setData( 'text/plain', key );
								setDragging( key );
							} }
							onDragOver={ ( event ) => {
								event.preventDefault();
								if ( dragging && dragging !== key ) {
									save(
										moveButton( order, dragging, index )
									);
								}
							} }
							onDrop={ ( event ) => event.preventDefault() }
							onDragEnd={ () => setDragging( null ) }
						>
							<span
								className="lw-cookie-order__handle"
								aria-hidden="true"
							>
								{ dragHandle }
							</span>
							<span className="lw-cookie-order__pos">
								{ index + 1 }.
							</span>
							<span
								className={ `lw-cookie-order__chip is-${ BUTTONS[ key ].style }` }
							>
								{ label( key ) }
							</span>
							<Button
								ref={ ( el ) =>
									( arrows.current[ `${ key }-up` ] = el )
								}
								icon={ arrowUp }
								size="compact"
								label={ sprintf(
									/* translators: %s: button label */
									__( 'Move %s up', 'lw-cookie' ),
									label( key )
								) }
								disabled={ 0 === index }
								accessibleWhenDisabled={ false }
								onClick={ () => move( key, index - 1, 'up' ) }
							/>
							<Button
								ref={ ( el ) =>
									( arrows.current[ `${ key }-down` ] = el )
								}
								icon={ arrowDown }
								size="compact"
								label={ sprintf(
									/* translators: %s: button label */
									__( 'Move %s down', 'lw-cookie' ),
									label( key )
								) }
								disabled={ order.length - 1 === index }
								accessibleWhenDisabled={ false }
								onClick={ () => move( key, index + 1, 'down' ) }
							/>
						</li>
					) ) }
				</ol>
				<Button
					variant="link"
					onClick={ () => {
						save( DEFAULT_ORDER );
						setStatus(
							__( 'Default order restored.', 'lw-cookie' )
						);
					} }
				>
					{ __( 'Restore default order', 'lw-cookie' ) }
				</Button>
				<p className="screen-reader-text" aria-live="polite">
					{ status }
				</p>
			</div>
		</SettingRow>
	);
}
