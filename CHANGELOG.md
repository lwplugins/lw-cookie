# Changelog

## [1.6.5] - 2026-03-22

### Fixed
- Rename frontend CSS classes from `lw-cookie-banner` to `lw-cookie-notice` to prevent adblocker false positives

## [1.6.4] - 2026-03-22

### Added
- LW Site Manager integration - cookie consent abilities for AI agents
- `lw-cookie/get-options` ability - get cookie consent settings
- `lw-cookie/set-options` ability - update cookie consent settings
- `lw-cookie/get-consent-stats` ability - get consent statistics
- `lw-cookie/scan-cookies` ability - trigger cookie scan

## [1.6.3]

### Fixed
- Smarter autoloader fallback - supports root Composer dependency installs

## [1.6.2]

### Fixed
- Graceful error when autoloader is missing (shows admin notice instead of fatal error)

## [1.6.1]

### Added
- Inline fallback click handlers for cache-proof banner interaction

### Fixed
- Cookie banner buttons now work on LiteSpeed Cache servers (JS delay/defer bypassed inline config)

### Changed
- LiteSpeed exclusion patterns now cover lwCookieConfig inline script and lw-cookie prefix
- Added `data-no-lazy` attribute to prevent lazy loading of consent script

## [1.6.0]

### Added
- Service Worker for network-level request blocking
- MutationObserver for DOM element interception (script/iframe/img)
- `document.cookie` override to prevent tracking cookie writes
- Inline guard.js runs before any body element is parsed
- REST API endpoint for consent logging (replaces admin-ajax)
- Works with ANY full-page cache plugin (WP Rocket, LiteSpeed, Cloudflare, etc.)

### Changed
- Major: Complete rewrite to client-side blocking architecture (cache-proof)
- Google Consent Mode v2 always defaults to `denied` (cache-safe)
- Banner always rendered in HTML with hidden class (guard.js toggles visibility)

### Removed
- Server-side ScriptBlocker (replaced by client-side guard)
- Server-side ContentBlocker output buffering (replaced by MutationObserver and Service Worker)
- AJAX nonce for consent saving (nonces get cached - now uses REST with rate limiting)

## [1.5.2]

### Added
- Cache plugin compatibility layer for LiteSpeed Cache, WP Rocket, Cloudflare Rocket Loader, and PageSpeed

### Fixed
- Cookie banner buttons now work correctly with LiteSpeed Cache and WP Rocket (JS delay/defer compatibility)

## [1.5.1]

### Fixed
- Minor fix

## [1.5.0]

### Added
- Hash-based tab navigation on settings page
- New cookie-bite icon
- Updated ParentPage with SVG icon support from registry

## [1.4.9]

### Fixed
- Replace dashicons with inline SVG on floating button (dashicons not available on frontend)

## [1.4.8]

### Fixed
- Minor fix

## [1.4.7]

### Fixed
- Minor fix

## [1.4.6]

### Fixed
- Admin notice isolation for notices relocated by WordPress core JS

## [1.4.5]

### Changed
- Isolate third-party admin notices on LW plugin pages

## [1.4.4]

### Added
- Fresh POT file and Hungarian (hu_HU) translation

## [1.4.3]

### Added
- Plugin registry fetched from central GitHub JSON (no more per-plugin registry updates)

### Fixed
- Release ZIP now includes Composer autoloader for non-Composer installs
- Settings page now stays on the active tab after saving

## [1.4.1]

### Fixed
- "Accept & Load Content" button now properly saves consent and loads blocked iframes in-place
- Cookie banner now hides when accepting content via blocked content placeholder
- All blocked content of the same category loads when any placeholder is accepted

## [1.4.0]

### Added
- Google Consent Mode v2 now loads at `-PHP_INT_MAX` priority (before any other script)
- Meta Pixel (Facebook) consent API support - automatic revoke/grant calls
- `dataLayer.push` event for GTM triggers (`lw_cookie_consent_update`)
- WordPress filters for third-party plugin integration
- Script blocking override filter (`lw_cookie_should_block_script`)

## [1.3.5]

### Changed
- Scanner auto-enables all cookie categories during scan for complete detection

## [1.3.4]

### Added
- Deep scan using remote headless browser for better cookie detection

### Changed
- Scanner now combines local and remote scan results automatically

## [1.3.3]

### Added
- "Delete All Cookies" button in cookie declaration shortcode

### Fixed
- Preferences modal now always renders (floating button works after consent given)

## [1.3.2]

### Fixed
- ContentBlocker output buffer compatibility with AJAX and REST requests
- ContentBlocker now only processes HTML documents

## [1.3.1]

### Added
- Content Blocking - blocks YouTube, Vimeo, Google Maps, and other embeds until consent
- HTTP header cookie detection via `wp_remote_head()`

### Changed
- Scanner now includes random blog posts (not just pages)
- External content scan limit removed (scans all posts)
- Native WordPress admin style for scanner UI

## [1.3.0]

### Added
- Server-side Cookie Scanner - detects all cookies including HttpOnly
- LW Plugins Cookie Database API integration (2000+ cookies)
- Automatic cookie enrichment with provider, purpose, and duration
- REST API endpoints for scan results
- Multi-page scanning (home, WooCommerce cart/checkout, posts)

### Changed
- Modern scanner UI with networkidle detection

## [1.2.0]

### Added
- Hungarian (hu_HU) translation
- POT file for translations

## [1.1.0]

### Added
- Cookie Declaration admin tab for managing cookie list
- `[lw_cookie_declaration]` shortcode to display cookies on any page
- "Add Common Cookies" quick-add feature
- GDPR consent search by consent ID or IP address (CLI)
- GDPR consent deletion/erasure support (CLI)

### Changed
- Full GDPR compliance with cookie transparency

## [1.0.0]

### Added
- Initial release
- GDPR-compliant cookie consent banner
- 4 cookie categories (Necessary, Functional, Analytics, Marketing)
- Customizable appearance (position, layout, colors)
- Script blocking for known tracking scripts
- Google Consent Mode v2 support
- Consent logging with anonymized IP
- Floating button for consent changes
- Modern tabbed admin interface
- Full WP-CLI support for settings, stats, and export
