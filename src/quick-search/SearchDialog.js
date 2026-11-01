/**
 * WordPress dependencies
 */
import { useInstanceId } from '@wordpress/compose';
import {
	createPortal,
	useEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Icon, arrowRight, backup, cog, search } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { highlight, score, terms } from './match';
import { keyOf, readRecents } from './recents';

const MAX_JUMPS = 5;
const MAX_SETTINGS = 8;

/**
 * Highlighted text.
 *
 * @param {Object}   props
 * @param {string}   props.text  Text.
 * @param {string[]} props.words Terms.
 */
function Marked( { text, words } ) {
	return highlight( text, words ).map( ( part, i ) =>
		part.hit ? <mark key={ i }>{ part.text }</mark> : part.text
	);
}

/**
 * Result groups for a query: recents and tabs while empty, otherwise the
 * best tab/card jumps and the best settings.
 *
 * @param {Object[]} index      Entries.
 * @param {string[]} words      Terms.
 * @param {string}   storageKey Recents key.
 * @return {Array<{label: string, items: Object[]}>} Groups.
 */
function groupsFor( index, words, storageKey ) {
	if ( ! words.length ) {
		const byKey = new Map(
			index.map( ( entry ) => [ keyOf( entry ), entry ] )
		);
		const recents = readRecents( storageKey )
			.map( ( key ) => byKey.get( key ) )
			.filter( Boolean )
			.map( ( entry ) => ( { ...entry, recent: true } ) );
		return [
			{ label: __( 'Recent', 'lw-cookie' ), items: recents },
			{
				label: __( 'Sections', 'lw-cookie' ),
				items: index.filter( ( entry ) => entry.kind === 'tab' ),
			},
		];
	}

	const ranked = index
		.map( ( entry ) => [ entry, score( entry, words ) ] )
		.filter( ( [ , value ] ) => value >= 0 )
		.sort( ( a, b ) => b[ 1 ] - a[ 1 ] )
		.map( ( [ entry ] ) => entry );

	return [
		{
			label: __( 'Go to', 'lw-cookie' ),
			items: ranked
				.filter( ( entry ) => entry.kind !== 'setting' )
				.slice( 0, MAX_JUMPS ),
		},
		{
			label: __( 'Settings', 'lw-cookie' ),
			items: ranked
				.filter( ( entry ) => entry.kind === 'setting' )
				.slice( 0, MAX_SETTINGS ),
		},
	];
}

/**
 * The search dialog: a combobox over the index, keyboard first.
 *
 * @param {Object}                  props
 * @param {Object[]}                props.index      Entries.
 * @param {Object}                  props.icons      Tab id → icon.
 * @param {string}                  props.storageKey Recents key.
 * @param {(entry: Object) => void} props.onSelect   Called with the chosen entry.
 * @param {() => void}              props.onClose    Close without choosing.
 */
export default function SearchDialog( {
	index,
	icons,
	storageKey,
	onSelect,
	onClose,
} ) {
	const [ query, setQuery ] = useState( '' );
	const [ active, setActive ] = useState( 0 );
	const input = useRef();
	const list = useRef();
	const id = useInstanceId( SearchDialog, 'lw-qs' );

	const words = terms( query );
	const groups = useMemo(
		() =>
			groupsFor( index, terms( query ), storageKey ).filter(
				( group ) => group.items.length
			),
		[ index, query, storageKey ]
	);
	const flat = groups.flatMap( ( group ) => group.items );

	useEffect( () => setActive( 0 ), [ query ] );
	useEffect( () => input.current?.focus(), [] );
	useEffect( () => {
		list.current
			?.querySelector( `#${ id }-${ active }` )
			?.scrollIntoView( { block: 'nearest' } );
	}, [ active, id ] );

	const onKeyDown = ( event ) => {
		if ( event.key === 'ArrowDown' || event.key === 'ArrowUp' ) {
			event.preventDefault();
			const step = event.key === 'ArrowDown' ? 1 : -1;
			setActive( ( current ) =>
				Math.max( 0, Math.min( flat.length - 1, current + step ) )
			);
		} else if ( event.key === 'Enter' ) {
			event.preventDefault();
			if ( flat[ active ] ) {
				onSelect( flat[ active ] );
			}
		} else if ( event.key === 'Escape' ) {
			event.preventDefault();
			onClose();
		} else if ( event.key === 'Tab' ) {
			// The input is the only stop; the list is driven by the arrows.
			event.preventDefault();
		}
	};

	let position = -1;

	return createPortal(
		// eslint-disable-next-line jsx-a11y/no-static-element-interactions -- Esc closes from the input.
		<div
			className="lw-qs-scrim"
			onMouseDown={ ( event ) =>
				event.target === event.currentTarget && onClose()
			}
		>
			<div
				className="lw-qs-dialog"
				role="dialog"
				aria-modal="true"
				aria-label={ __( 'Quick search', 'lw-cookie' ) }
			>
				<div className="lw-qs-head">
					<Icon icon={ search } size={ 22 } />
					<input
						ref={ input }
						type="text"
						className="lw-qs-input"
						value={ query }
						placeholder={ __(
							'Search sections and settings',
							'lw-cookie'
						) }
						autoComplete="off"
						spellCheck={ false }
						role="combobox"
						aria-expanded="true"
						aria-controls={ `${ id }-list` }
						aria-activedescendant={
							flat.length ? `${ id }-${ active }` : undefined
						}
						onChange={ ( event ) => setQuery( event.target.value ) }
						onKeyDown={ onKeyDown }
					/>
					<kbd className="lw-qs-kbd">Esc</kbd>
				</div>
				<div
					className="lw-qs-list"
					id={ `${ id }-list` }
					role="listbox"
					ref={ list }
					aria-label={ __( 'Results', 'lw-cookie' ) }
				>
					{ groups.map( ( group ) => (
						<div
							key={ group.label }
							role="group"
							aria-label={ group.label }
						>
							<div className="lw-qs-group" aria-hidden="true">
								{ group.label }
							</div>
							{ group.items.map( ( entry ) => {
								position++;
								const at = position;
								let icon = cog;
								if ( entry.recent ) {
									icon = backup;
								} else if ( entry.kind !== 'setting' ) {
									icon = icons[ entry.tab ] || arrowRight;
								}
								return (
									// Options are driven from the input (arrows, Enter) via aria-activedescendant; the mouse only picks.
									// eslint-disable-next-line jsx-a11y/click-events-have-key-events, jsx-a11y/interactive-supports-focus
									<div
										key={ `${ group.label }-${ keyOf( entry ) }` }
										id={ `${ id }-${ at }` }
										role="option"
										aria-selected={ at === active }
										className="lw-qs-option"
										onMouseMove={ () =>
											index !== active &&
											setActive( index )
										}
										onMouseDown={ ( event ) =>
											event.preventDefault()
										}
										onClick={ () => onSelect( entry ) }
									>
										<Icon icon={ icon } size={ 20 } />
										<span className="lw-qs-path">
											{ entry.path.map( ( part ) => (
												<span
													key={ part }
													className="lw-qs-crumb"
												>
													<Marked
														text={ part }
														words={ words }
													/>
												</span>
											) ) }
											<span className="lw-qs-title">
												<Marked
													text={ entry.title }
													words={ words }
												/>
											</span>
										</span>
										<Icon
											icon={ arrowRight }
											size={ 18 }
											className="lw-qs-go"
										/>
									</div>
								);
							} ) }
						</div>
					) ) }
					{ ! flat.length && (
						<p className="lw-qs-empty">
							{ sprintf(
								/* translators: %s: the search text. */
								__( 'No results for “%s”.', 'lw-cookie' ),
								query.trim()
							) }
						</p>
					) }
				</div>
				<div className="lw-qs-foot" aria-hidden="true">
					<span>
						<kbd className="lw-qs-kbd">↑</kbd>
						<kbd className="lw-qs-kbd">↓</kbd>
						{ __( 'to navigate', 'lw-cookie' ) }
					</span>
					<span>
						<kbd className="lw-qs-kbd">↵</kbd>
						{ __( 'to open', 'lw-cookie' ) }
					</span>
				</div>
			</div>
		</div>,
		document.body
	);
}
