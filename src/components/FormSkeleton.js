/**
 * Internal dependencies
 */
import { SkeletonRegion, SkeletonRows, SkeletonSection } from './skeleton';

/**
 * Placeholder for a settings tab: two cards of rows.
 */
export default function FormSkeleton() {
	return (
		<SkeletonRegion className="lw-skel-tab">
			<SkeletonSection description={ false }>
				<SkeletonRows count={ 3 } />
			</SkeletonSection>
			<SkeletonSection description={ false }>
				<SkeletonRows count={ 3 } />
			</SkeletonSection>
		</SkeletonRegion>
	);
}
