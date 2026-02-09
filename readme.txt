=== LW Cookie ===
Contributors: lwplugins
Tags: cookie, gdpr, consent, privacy, compliance
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 1.4.8
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

GDPR-compliant cookie consent banner for WordPress - minimal footprint, full compliance.

== Description ==

LW Cookie provides a lightweight, GDPR-compliant cookie consent solution. No bloat, no upsells, no tracking - just clean and efficient cookie management.

= Features =

**GDPR Compliance**

* Opt-in by default - all optional categories are OFF until user consents
* Granular category selection (Necessary, Functional, Analytics, Marketing)
* Consent logging with timestamp for compliance proof
* Policy version tracking - automatic re-consent on policy changes
* Anonymized IP logging (GDPR-compliant)

**Cookie Banner**

* Customizable position (top, bottom, modal)
* Customizable layout (full-width bar, floating box)
* Customizable colors and border radius
* Accept All / Reject All / Customize buttons
* Floating button for easy access to change preferences

**Script Blocking**

* Automatic blocking of known tracking scripts until consent
* Supports Google Analytics, Facebook Pixel, Hotjar, and more
* Scripts unblock dynamically when consent is given

**Google Consent Mode v2**

* Built-in support for Google Consent Mode v2
* Required for Google Ads and Analytics in the EU
* Automatic consent signal updates
* Loads before any tracking scripts (-PHP_INT_MAX priority)
* EEA region-specific defaults

**Meta Pixel (Facebook) Support**

* Automatic `fbq('consent', 'revoke/grant')` API calls
* Works with existing Facebook Pixel implementations

**Third-Party Plugin Integration**

* dataLayer.push events for GTM triggers (`lw_cookie_consent_update`)
* WordPress filters for other plugins to query consent state
* Script blocking override filter for plugin compatibility

**Admin Features**

* Unified "LW Plugins" admin menu
* Modern tabbed settings interface
* Easy customization of all texts and labels
* Privacy policy page linking

**WP-CLI Support**

* Full command-line management of settings
* View and export consent statistics
* Bulk operations and automation support
* Perfect for deployment scripts and cron jobs

= Cookie Categories =

* **Necessary** - Always enabled, essential for website function
* **Functional** - Enhanced functionality and personalization
* **Analytics** - Visitor analytics and statistics
* **Marketing** - Advertising and remarketing

= GDPR Notes =

This plugin helps you comply with GDPR and other privacy regulations by:

1. **Prior Consent** - No cookies (except necessary) set before consent
2. **Granular Control** - Users choose which categories to accept
3. **Easy Withdrawal** - Floating button allows changing consent anytime
4. **Consent Proof** - All consents logged with timestamp and policy version
5. **Re-consent** - Changing policy version triggers new consent request

**Note:** This plugin is a tool to help with compliance. You are responsible for ensuring your complete website and cookie usage complies with applicable laws.

== Installation ==

1. Upload the `lw-cookie` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu
3. Go to **LW Plugins → Cookie** to configure

Or install via Composer:

`composer require lwplugins/lw-cookie`

== Frequently Asked Questions ==

= Is this plugin GDPR compliant? =

This plugin provides the technical framework for GDPR-compliant cookie consent. However, compliance also depends on how you configure it and your overall privacy practices. Ensure you have a proper privacy policy and only use cookies as described.

= How do I customize the banner appearance? =

Go to **LW Plugins → Cookie → Appearance** to customize colors, position, and layout.

= How do I change the text on the banner? =

Go to **LW Plugins → Cookie → Texts** to customize all labels and messages.

= What scripts are automatically blocked? =

The plugin automatically detects and blocks known tracking scripts including:
- Google Analytics / Google Tag Manager
- Facebook Pixel
- Hotjar
- LinkedIn Insight Tag
- Twitter Pixel
- TikTok Pixel
- Microsoft Clarity
- And more

= How does Google Consent Mode work? =

When enabled, the plugin sets Google Consent Mode v2 signals based on user consent. This is required for Google Ads and Analytics to function properly in the EU.

= Can users change their consent after accepting? =

Yes, if you enable the floating button in settings, users can click it anytime to open the preferences modal and change their choices.

= Is consent logged? =

Yes, all consent actions are logged to the database with:
- Unique consent ID
- Anonymized IP hash (GDPR-compliant)
- Categories selected
- Policy version
- Timestamp

= What happens when I update my privacy policy? =

Change the "Policy Version" setting in the General tab. Users will be asked for consent again on their next visit.

= Can I manage settings via WP-CLI? =

Yes! LW Cookie has full WP-CLI support:

`wp lw-cookie settings list` - View all settings
`wp lw-cookie settings set enabled true` - Change a setting
`wp lw-cookie stats` - View consent statistics
`wp lw-cookie export --format=csv` - Export consent logs
`wp lw-cookie clear-logs --older-than=365` - Clean up old logs

== Screenshots ==

1. Cookie consent banner (bottom position)
2. Cookie preferences modal with category selection
3. Settings page - General tab
4. Settings page - Appearance tab
5. Settings page - Categories tab

== Changelog ==

= 1.4.8 =
* Minor fix

= 1.4.7 =
* Minor fix

= 1.4.6 =
* Fix admin notice isolation for notices relocated by WordPress core JS

= 1.4.5 =
* Isolate third-party admin notices on LW plugin pages

= 1.4.4 =
* Add fresh POT file and Hungarian (hu_HU) translation

= 1.4.3 =
* New: Plugin registry fetched from central GitHub JSON (no more per-plugin registry updates)
* Fix: Release ZIP now includes Composer autoloader for non-Composer installs
* Fix: Settings page now stays on the active tab after saving

= 1.4.1 =
* Fix: "Accept & Load Content" button now properly saves consent and loads blocked iframes in-place
* Fix: Cookie banner now hides when accepting content via blocked content placeholder
* Fix: All blocked content of the same category loads when any placeholder is accepted

= 1.4.0 =
* New: Google Consent Mode v2 now loads at -PHP_INT_MAX priority (before any other script)
* New: Meta Pixel (Facebook) consent API support - automatic revoke/grant calls
* New: dataLayer.push event for GTM triggers (`lw_cookie_consent_update`)
* New: WordPress filters for third-party plugin integration
* New: Script blocking override filter (`lw_cookie_should_block_script`)

= 1.3.5 =
* Improved: Scanner auto-enables all cookie categories during scan for complete detection

= 1.3.4 =
* New: Deep scan using remote headless browser for better cookie detection
* Improved: Scanner now combines local and remote scan results automatically

= 1.3.3 =
* Fix: Preferences modal now always renders (floating button works after consent given)
* New: "Delete All Cookies" button in cookie declaration shortcode

= 1.3.2 =
* Fix: ContentBlocker output buffer compatibility with AJAX and REST requests
* Fix: ContentBlocker now only processes HTML documents

= 1.3.1 =
* New: Content Blocking - blocks YouTube, Vimeo, Google Maps, and other embeds until consent
* New: HTTP header cookie detection via wp_remote_head()
* Improved: Scanner now includes random blog posts (not just pages)
* Improved: External content scan limit removed (scans all posts)
* Improved: Native WordPress admin style for scanner UI

= 1.3.0 =
* New: Server-side Cookie Scanner - detects all cookies including HttpOnly
* New: LW Plugins Cookie Database API integration (2000+ cookies)
* New: Automatic cookie enrichment with provider, purpose, and duration
* New: REST API endpoints for scan results
* New: Multi-page scanning (home, WooCommerce cart/checkout, posts)
* Improved: Modern scanner UI with networkidle detection

= 1.2.0 =
* New: Hungarian (hu_HU) translation by @trueqap
* New: POT file for translations

= 1.1.0 =
* New: Cookie Declaration admin tab for managing cookie list
* New: [lw_cookie_declaration] shortcode to display cookies on any page
* New: "Add Common Cookies" quick-add feature
* New: GDPR consent search by consent ID or IP address (CLI)
* New: GDPR consent deletion/erasure support (CLI)
* Improved: Full GDPR compliance with cookie transparency

= 1.0.0 =
* Initial release
* GDPR-compliant cookie consent banner
* 4 cookie categories (Necessary, Functional, Analytics, Marketing)
* Customizable appearance (position, layout, colors)
* Script blocking for known tracking scripts
* Google Consent Mode v2 support
* Consent logging with anonymized IP
* Floating button for consent changes
* Modern tabbed admin interface
* Full WP-CLI support for settings, stats, and export

== Upgrade Notice ==

= 1.1.0 =
Cookie Declaration feature for GDPR transparency - declare and display all cookies used on your site.

= 1.0.0 =
Initial release.
