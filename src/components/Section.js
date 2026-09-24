/**
 * WordPress dependencies
 */
import {
	Card,
	CardBody,
	CardHeader,
	__experimentalHeading as Heading,
	__experimentalHStack as HStack,
	__experimentalText as Text,
	__experimentalVStack as VStack,
} from '@wordpress/components';

/**
 * A titled card: the building block of every tab.
 *
 * @param {Object}  props
 * @param {string}  props.title       Heading.
 * @param {Element} props.badge       Optional badge after the heading.
 * @param {string}  props.description Optional lead text.
 * @param {Element} props.actions     Optional header actions.
 * @param {string}  props.className   Extra class.
 * @param {Element} props.children    Body (optional: header-only card).
 */
export default function Section( {
	title,
	badge,
	description,
	actions,
	className,
	children,
} ) {
	return (
		<Card className={ `lw-admin-section ${ className || '' }` }>
			{ title && (
				<CardHeader>
					<VStack spacing={ 1 }>
						<HStack justify="flex-start" spacing={ 2 }>
							<Heading level={ 3 } size={ 15 }>
								{ title }
							</Heading>
							{ badge }
						</HStack>
						{ description && (
							<Text variant="muted">{ description }</Text>
						) }
					</VStack>
					{ actions }
				</CardHeader>
			) }
			{ children && (
				<CardBody>
					<VStack spacing={ 5 }>{ children }</VStack>
				</CardBody>
			) }
		</Card>
	);
}
