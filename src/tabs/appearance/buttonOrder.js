/**
 * The first-layer banner buttons and their order (banner_button_order).
 * Mirrors includes/Banner/ButtonOrder.php: three keys, each exactly once.
 */
export const DEFAULT_ORDER = [ 'customize', 'reject', 'accept' ];

/**
 * Button key => preview style and text option.
 */
export const BUTTONS = {
	customize: { style: 'secondary', textKey: 'btn_customize' },
	reject: { style: 'outline', textKey: 'btn_reject_all' },
	accept: { style: 'primary', textKey: 'btn_accept_all' },
};

/**
 * The stored order, or the default one when it is not valid.
 *
 * @param {string} value Stored value.
 * @return {string[]} Button keys.
 */
export function resolveOrder( value ) {
	const order = String( value || '' )
		.split( ',' )
		.map( ( key ) => key.trim() );
	const valid =
		order.length === DEFAULT_ORDER.length &&
		DEFAULT_ORDER.every( ( key ) => order.includes( key ) );
	return valid ? order : DEFAULT_ORDER;
}

/**
 * Move one button to a new position.
 *
 * @param {string[]} order Current order.
 * @param {string}   key   Button key.
 * @param {number}   to    New index.
 * @return {string[]} New order.
 */
export function moveButton( order, key, to ) {
	const next = order.filter( ( item ) => item !== key );
	next.splice( Math.max( 0, Math.min( to, next.length ) ), 0, key );
	return next;
}
