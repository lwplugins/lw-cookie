/**
 * WordPress dependencies
 */
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import FormSkeleton from './components/FormSkeleton';
import LoadError from './components/LoadError';
import Notices from './components/Notices';
import useSettingsStore from './data/useSettingsStore';
import Footer from './shell/Footer';
import SideNav from './shell/SideNav';
import TopBar from './shell/TopBar';
import { TABS } from './shell/tabs';
import useSaveShortcut from './shell/useSaveShortcut';
import useTab from './shell/useTab';
import useUnsavedWarning from './shell/useUnsavedWarning';
import QuickSearch from './quick-search';
import AdvancedTab from './tabs/AdvancedTab';
import AppearanceTab from './tabs/appearance/AppearanceTab';
import CategoriesTab from './tabs/CategoriesTab';
import CookiesTab from './tabs/cookies/CookiesTab';
import GeneralTab from './tabs/GeneralTab';
import TextsTab from './tabs/TextsTab';

const VIEWS = {
	general: GeneralTab,
	appearance: AppearanceTab,
	categories: CategoriesTab,
	texts: TextsTab,
	cookies: CookiesTab,
	advanced: AdvancedTab,
};

// Classic links (?page=lw-cookie&tab=cookies) open the matching section.
const INITIAL_TAB =
	new URLSearchParams( window.location.search ).get( 'tab' ) || 'general';

/**
 * Shell + one options store shared by every tab (partial saves).
 */
export default function App() {
	const store = useSettingsStore();
	const tab = useTab(
		TABS.map( ( t ) => t.id ),
		INITIAL_TAB
	);
	const current = TABS.find( ( t ) => t.id === tab ) || TABS[ 0 ];
	const View = VIEWS[ current.id ];

	useUnsavedWarning( store.hasEdits );
	useSaveShortcut( store.save, store.hasEdits && ! store.isSaving );

	// The quick search indexes the settings tabs (read-only renders of the
	// loaded options). Cookies runs the scanner and a table, so it is found
	// by name only.
	const searchPages = useCallback(
		() =>
			store.data
				? TABS.filter( ( t ) => t.id !== 'cookies' ).map( ( t ) => {
						const Tab = VIEWS[ t.id ];
						return {
							tab: t,
							element: <Tab store={ store } />,
						};
					} )
				: null,
		[ store ]
	);

	let content;
	if ( store.error ) {
		content = (
			<LoadError message={ store.error } onRetry={ store.reload } />
		);
	} else if ( store.isLoading ) {
		content = <FormSkeleton />;
	} else {
		content = <View store={ store } />;
	}

	return (
		<>
			<div className="lw-admin-shell">
				<SideNav
					tabs={ TABS }
					current={ current.id }
					search={
						<QuickSearch
							tabs={ TABS }
							pages={ searchPages }
							storageKey="lw-cookie-quick-search"
						/>
					}
				/>
				<div className="lw-admin-main">
					<TopBar
						title={ current.title }
						store={ store.data ? store : null }
					/>
					<main className="lw-admin-scroll">
						<div className="lw-admin-content">{ content }</div>
					</main>
					<Footer />
				</div>
			</div>
			<Notices />
		</>
	);
}
