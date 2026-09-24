/**
 * WordPress dependencies
 */
import { SnackbarList } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Snackbar notices from the core notices store (bottom-left toasts).
 */
export default function Notices() {
	const notices = useSelect(
		( select ) =>
			select( noticesStore )
				.getNotices()
				.filter( ( notice ) => notice.type === 'snackbar' ),
		[]
	);
	const { removeNotice } = useDispatch( noticesStore );

	return (
		<SnackbarList
			className="lw-admin-snackbars"
			notices={ notices }
			onRemove={ removeNotice }
		/>
	);
}
