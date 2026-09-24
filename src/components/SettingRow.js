/**
 * Two-column settings row: title + help on the left, the control on the right.
 * Controls inside should hide their own label from sight (hideLabelFromVision)
 * or be tied to the title through `htmlFor`, so each field has one accessible
 * name and one visible title.
 *
 * @param {Object}  props
 * @param {string}  props.title    Visible title.
 * @param {Element} props.help     Description under the title.
 * @param {string}  props.htmlFor  Id of the control the title labels.
 * @param {boolean} props.stacked  Control goes under the text (wide controls).
 * @param {Element} props.children The control(s).
 */
export default function SettingRow( {
	title,
	help,
	htmlFor,
	stacked = false,
	children,
} ) {
	const Title = htmlFor ? 'label' : 'span';

	return (
		<div className={ `lw-admin-row ${ stacked ? 'is-stacked' : '' }` }>
			<div className="lw-admin-row__text">
				<Title className="lw-admin-row__title" htmlFor={ htmlFor }>
					{ title }
				</Title>
				{ help && <p className="lw-admin-row__help">{ help }</p> }
			</div>
			<div className="lw-admin-row__control">{ children }</div>
		</div>
	);
}
