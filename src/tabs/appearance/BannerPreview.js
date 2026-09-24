/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Banner text: the option value, else the field's placeholder (the default
 * the frontend falls back to).
 *
 * @param {Object} options Draft options.
 * @param {Object} meta    Settings meta.
 * @param {string} key     Text key.
 * @return {string} Text.
 */
const text = ( options, meta, key ) => {
	if ( options[ key ] ) {
		return options[ key ];
	}
	for ( const section of meta.text_fields || [] ) {
		const field = section.fields.find( ( f ) => f.name === key );
		if ( field ) {
			return field.placeholder || '';
		}
	}
	return '';
};

/**
 * Lightweight live mock of the consent banner (bar / box / modal) using the
 * draft colours, radius and texts. Purely visual, aria-hidden.
 *
 * @param {Object} props
 * @param {Object} props.options Draft options.
 * @param {Object} props.meta    Settings meta.
 */
export default function BannerPreview( { options, meta } ) {
	const isModal = options.banner_position === 'modal';
	const isBox = ! isModal && options.banner_layout === 'box';
	const place = [
		'lw-preview__banner',
		isModal ? 'is-modal' : `is-${ options.banner_position || 'bottom' }`,
		isBox ? `is-box is-${ options.banner_box_alignment || 'right' }` : '',
		! isModal && ! isBox ? 'is-bar' : '',
	].join( ' ' );
	const style = {
		'--lw-preview-primary': options.primary_color || '#2271b1',
		'--lw-preview-text': options.text_color || '#1d2327',
		'--lw-preview-bg': options.background_color || '#ffffff',
		'--lw-preview-radius': `${ Number( options.border_radius ) || 0 }px`,
	};
	return (
		<div className="lw-preview" style={ style } aria-hidden="true">
			<div className="lw-preview__page">
				<span />
				<span />
				<span />
			</div>
			{ isModal && <div className="lw-preview__overlay" /> }
			<div className={ place }>
				<div className="lw-preview__content">
					<strong>{ text( options, meta, 'banner_title' ) }</strong>
					<p>{ text( options, meta, 'banner_message' ) }</p>
				</div>
				<div className="lw-preview__actions">
					<span className="lw-preview__btn is-secondary">
						{ text( options, meta, 'btn_customize' ) ||
							__( 'Customize', 'lw-cookie' ) }
					</span>
					<span className="lw-preview__btn is-outline">
						{ text( options, meta, 'btn_reject_all' ) ||
							__( 'Reject All', 'lw-cookie' ) }
					</span>
					<span className="lw-preview__btn is-primary">
						{ text( options, meta, 'btn_accept_all' ) ||
							__( 'Accept All', 'lw-cookie' ) }
					</span>
				</div>
			</div>
		</div>
	);
}
