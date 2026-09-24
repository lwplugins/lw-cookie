/**
 * WordPress dependencies
 */
import { Icon, info } from '@wordpress/icons';

/**
 * Quiet inline explanation box (grey, info icon) — for context that is not a
 * warning. Use core <Notice> for warnings and errors.
 *
 * @param {Object}  props
 * @param {Element} props.children Content.
 * @param {string}  props.tone     neutral|warning.
 */
export default function Callout( { children, tone = 'neutral' } ) {
	return (
		<div className={ `lw-admin-callout is-${ tone }` }>
			<Icon icon={ info } size={ 18 } />
			<div>{ children }</div>
		</div>
	);
}
