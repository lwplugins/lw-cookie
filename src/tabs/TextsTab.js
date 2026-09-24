/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { AreaRow, TextRow } from '../components/Fields';
import MultilingualCallout from '../components/MultilingualCallout';
import Section from '../components/Section';

/**
 * Every user-facing string, rendered from meta.text_fields (the server's
 * TextFieldDefinitions, translated and in display order).
 *
 * @param {Object} props
 * @param {Object} props.store Settings store.
 */
export default function TextsTab( { store } ) {
	const { meta } = store.data;

	return (
		<>
			<MultilingualCallout plugin={ meta.multilingual } store={ store } />
			<p className="lw-admin-muted">
				{ __(
					'Customize all user-facing text. Leave a field empty to use the built-in default for the current site language.',
					'lw-cookie'
				) }
			</p>
			{ ( meta.text_fields || [] ).map( ( section ) => (
				<Section key={ section.section } title={ section.section }>
					{ section.fields.map( ( field ) => {
						const Row =
							field.type === 'textarea' ? AreaRow : TextRow;
						return (
							<Row
								key={ field.name }
								title={ field.label }
								help={ field.description || undefined }
								placeholder={ field.placeholder || undefined }
								store={ store }
								name={ field.name }
								disabled={ store.locked.includes( field.name ) }
							/>
						);
					} ) }
				</Section>
			) ) }
		</>
	);
}
