/**
 * WordPress dependencies
 */
import {
	Button,
	SelectControl,
	TextControl,
	__experimentalHStack as HStack,
	__experimentalText as Text,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { closeSmall, plus } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import Section from '../components/Section';

const NAME = 'custom_blocking_rules';

/**
 * Client-side mirror of CustomRules::pattern(): the server drops a rule
 * whose pattern does not normalize, so say so before saving.
 *
 * @param {string} raw Pattern as typed.
 * @return {boolean} Whether it will be kept.
 */
const isValidPattern = ( raw ) => {
	const pattern = raw
		.trim()
		.replace( /^([a-z][a-z0-9+.-]*:)?\/\//i, '' )
		.replace( /[?#].*$/, '' );
	const [ host, ...path ] = pattern.split( '/' );

	return (
		/^[a-z0-9-]+(\.[a-z0-9-]+)+$/i.test( host ) &&
		! /[\s"'<>\\]/.test( path.join( '/' ) )
	);
};

/**
 * Advanced → Custom Blocking Rules: domain / URL pattern → category rows.
 *
 * @param {Object} props
 * @param {Object} props.store Settings store.
 */
export default function BlockingRulesSection( { store } ) {
	const rules = store.data.options[ NAME ] || [];
	const labels = store.data.meta.category_labels || {};

	const categories = Object.entries( labels ).map( ( [ value, label ] ) => ( {
		value,
		label:
			value === 'necessary'
				? sprintf(
						/* translators: %s: name of the necessary category */
						__( '%s (never blocked)', 'lw-cookie' ),
						label
					)
				: label,
	} ) );

	const update = ( index, patch ) =>
		store.set(
			NAME,
			rules.map( ( rule, i ) =>
				i === index ? { ...rule, ...patch } : rule
			)
		);

	return (
		<Section
			title={ __( 'Custom Blocking Rules', 'lw-cookie' ) }
			description={ __(
				'Block a script, pixel or embed the built-in list does not know until its category is accepted. A domain also matches its subdomains (code.tidio.co); a pattern with a path matches any URL containing it (example.com/pixel.js). Rules override the built-in list: "never blocked" lets e.g. googletagmanager.com load before consent. Inline code is not blocked, only what it loads from a URL.',
				'lw-cookie'
			) }
		>
			{ rules.map( ( rule, index ) => (
				<div key={ index }>
					<HStack
						alignment="flex-start"
						justify="flex-start"
						spacing={ 2 }
					>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Domain or URL pattern', 'lw-cookie' ) }
							hideLabelFromVision={ index > 0 }
							className="lw-admin-mono lw-admin-rule-pattern"
							placeholder="code.tidio.co"
							value={ rule.pattern }
							onChange={ ( pattern ) =>
								update( index, { pattern } )
							}
						/>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Category', 'lw-cookie' ) }
							hideLabelFromVision={ index > 0 }
							value={ rule.category }
							options={ categories }
							onChange={ ( category ) =>
								update( index, { category } )
							}
						/>
						<Button
							icon={ closeSmall }
							label={ __( 'Remove rule', 'lw-cookie' ) }
							className={
								index === 0
									? 'lw-admin-rule-remove is-first'
									: 'lw-admin-rule-remove'
							}
							onClick={ () =>
								store.set(
									NAME,
									rules.filter( ( r, i ) => i !== index )
								)
							}
						/>
					</HStack>
					{ rule.pattern !== '' &&
						! isValidPattern( rule.pattern ) && (
							<Text variant="muted">
								{ __(
									'Not a domain or URL pattern: this rule is dropped on save.',
									'lw-cookie'
								) }
							</Text>
						) }
				</div>
			) ) }
			<div>
				<Button
					variant="secondary"
					icon={ plus }
					onClick={ () =>
						store.set( NAME, [
							...rules,
							{ pattern: '', category: 'marketing' },
						] )
					}
				>
					{ __( 'Add rule', 'lw-cookie' ) }
				</Button>
			</div>
		</Section>
	);
}
