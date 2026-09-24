/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	brush,
	category,
	cog,
	listView,
	tool,
	typography,
} from '@wordpress/icons';

/**
 * Tab registry (hash slugs match the classic ?tab= values). Every tab edits
 * lw_cookie_options, so the top bar Save is always shown.
 */
export const TABS = [
	{
		id: 'general',
		label: __( 'General', 'lw-cookie' ),
		title: __( 'General Settings', 'lw-cookie' ),
		icon: cog,
	},
	{
		id: 'appearance',
		label: __( 'Appearance', 'lw-cookie' ),
		title: __( 'Appearance Settings', 'lw-cookie' ),
		icon: brush,
	},
	{
		id: 'categories',
		label: __( 'Categories', 'lw-cookie' ),
		title: __( 'Cookie Categories', 'lw-cookie' ),
		icon: category,
	},
	{
		id: 'texts',
		label: __( 'Texts', 'lw-cookie' ),
		title: __( 'Banner & Modal Texts', 'lw-cookie' ),
		icon: typography,
	},
	{
		id: 'cookies',
		label: __( 'Cookies', 'lw-cookie' ),
		title: __( 'Cookie Declaration', 'lw-cookie' ),
		icon: listView,
	},
	{
		id: 'advanced',
		label: __( 'Advanced', 'lw-cookie' ),
		title: __( 'Advanced Settings', 'lw-cookie' ),
		icon: tool,
	},
];
