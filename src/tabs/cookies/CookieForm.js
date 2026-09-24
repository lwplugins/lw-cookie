/**
 * WordPress dependencies
 */
import {
	Button,
	Modal,
	SelectControl,
	TextControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { BLANK_COOKIE } from '../../data/cookies';

/**
 * Add / edit one declared cookie in a modal. Changes only the draft list;
 * the page Save persists it.
 *
 * @param {Object}   props
 * @param {Object}   props.initial Cookie being edited, or null for a new one.
 * @param {Object}   props.labels  Category labels.
 * @param {Function} props.isTaken ( name ) => bool, duplicate check.
 * @param {Function} props.onSave  Receives the cookie.
 * @param {Function} props.onClose Close.
 */
export default function CookieForm( {
	initial,
	labels,
	isTaken,
	onSave,
	onClose,
} ) {
	const [ form, setForm ] = useState( initial || BLANK_COOKIE );
	const set = ( values ) => setForm( ( prev ) => ( { ...prev, ...values } ) );
	const name = form.name.trim();
	const duplicate = name && name !== initial?.name && isTaken( name );
	const field = ( key, label, placeholder, extra = {} ) => (
		<TextControl
			__next40pxDefaultSize
			__nextHasNoMarginBottom
			label={ label }
			placeholder={ placeholder }
			value={ form[ key ] }
			onChange={ ( value ) => set( { [ key ]: value } ) }
			{ ...extra }
		/>
	);

	const submit = ( event ) => {
		event.preventDefault();
		if ( name && ! duplicate ) {
			onSave( { ...form, name } );
		}
	};

	return (
		<Modal
			title={
				initial
					? __( 'Edit cookie', 'lw-cookie' )
					: __( 'Add cookie', 'lw-cookie' )
			}
			onRequestClose={ onClose }
			size="medium"
		>
			<form onSubmit={ submit } className="lw-admin-form">
				{ field(
					'name',
					__( 'Cookie Name', 'lw-cookie' ),
					__( 'e.g. _ga', 'lw-cookie' ),
					{
						className: 'lw-admin-mono',
						required: true,
						help: duplicate
							? __(
									'A cookie with this name is already in the list.',
									'lw-cookie'
								)
							: undefined,
					}
				) }
				{ field(
					'provider',
					__( 'Provider', 'lw-cookie' ),
					__( 'e.g. Google', 'lw-cookie' )
				) }
				{ field(
					'purpose',
					__( 'Purpose', 'lw-cookie' ),
					__( 'Purpose description', 'lw-cookie' )
				) }
				{ field(
					'duration',
					__( 'Duration', 'lw-cookie' ),
					__( 'e.g. 1 year', 'lw-cookie' )
				) }
				<div className="lw-admin-form__pair">
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Category', 'lw-cookie' ) }
						value={ form.category }
						options={ Object.entries( labels ).map(
							( [ value, label ] ) => ( {
								value,
								label,
							} )
						) }
						onChange={ ( category ) => set( { category } ) }
					/>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Type', 'lw-cookie' ) }
						value={ form.type }
						options={ [
							{
								value: 'session',
								label: __( 'Session', 'lw-cookie' ),
							},
							{
								value: 'persistent',
								label: __( 'Persistent', 'lw-cookie' ),
							},
						] }
						onChange={ ( type ) => set( { type } ) }
					/>
				</div>
				<div className="lw-admin-inline lw-admin-form__actions">
					<Button variant="tertiary" onClick={ onClose }>
						{ __( 'Cancel', 'lw-cookie' ) }
					</Button>
					<Button
						__next40pxDefaultSize
						variant="primary"
						type="submit"
						disabled={ ! name || duplicate }
						accessibleWhenDisabled
					>
						{ initial
							? __( 'Update Cookie', 'lw-cookie' )
							: __( 'Add Cookie', 'lw-cookie' ) }
					</Button>
				</div>
			</form>
		</Modal>
	);
}
