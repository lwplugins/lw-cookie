# LW Cookie - Settings Documentation

## Table of Contents

1. [Installation](#installation)
2. [General Settings](#general-settings)
3. [Appearance Settings](#appearance-settings)
4. [Categories Settings](#categories-settings)
5. [Texts Settings](#texts-settings)
6. [Advanced Settings](#advanced-settings)
7. [WP-CLI Commands](#wp-cli-commands)
8. [JavaScript API](#javascript-api)
9. [Google Consent Mode](#google-consent-mode)
10. [Script Blocking](#script-blocking)
11. [Database & GDPR Compliance](#database--gdpr-compliance)

---

## Installation

1. Upload the `lw-cookie` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Navigate to **LW Plugins → LW Cookie** to configure settings

---

## General Settings

### Enable Cookie Banner

**Option:** `enabled`
**Default:** `true`

Enables or disables the cookie consent banner on the frontend. When disabled, no banner will be shown and no scripts will be blocked.

### Privacy Policy Page

**Option:** `privacy_policy_page`
**Default:** `0` (none)

Select the page containing your privacy policy. This page will be linked in the cookie banner, allowing users to review your privacy practices before making a consent decision.

### Policy Version

**Option:** `policy_version`
**Default:** `1.0`

The current version of your cookie/privacy policy. When you update this version number, all existing consents become invalid and users will be prompted to consent again. This ensures compliance when your policy changes.

**Best Practice:** Increment this version whenever you:
- Add new cookie categories
- Change how cookies are used
- Update your privacy policy significantly

---

## Appearance Settings

### Banner Position

**Option:** `banner_position`
**Default:** `bottom`

| Value | Description |
|-------|-------------|
| `bottom` | Fixed bar at the bottom of the viewport |
| `top` | Fixed bar at the top of the viewport |
| `modal` | Centered modal overlay |

### Banner Layout

**Option:** `banner_layout`
**Default:** `bar`

| Value | Description |
|-------|-------------|
| `bar` | Horizontal bar spanning the viewport width |
| `box` | Compact box in the corner |

### Primary Color

**Option:** `primary_color`
**Default:** `#2271b1`

The primary color used for buttons (Accept All, Save Preferences). Accepts any valid CSS color value.

### Text Color

**Option:** `text_color`
**Default:** `#1d2327`

The color used for banner text content.

### Background Color

**Option:** `background_color`
**Default:** `#ffffff`

The background color of the cookie banner and preferences modal.

### Border Radius

**Option:** `border_radius`
**Default:** `4`

Border radius in pixels for buttons and the banner container. Set to `0` for sharp corners.

---

## Categories Settings

LW Cookie uses four cookie categories as recommended by GDPR guidelines:

### Necessary (Required)

Always enabled and cannot be disabled by users. These cookies are essential for basic website functionality.

**Examples:**
- Session cookies
- Shopping cart cookies
- Security cookies (CSRF tokens)
- Cookie consent preferences

### Functional

**Options:**
- `cat_functional_name` - Display name (default: "Functional")
- `cat_functional_desc` - Description shown to users

Cookies that enhance website functionality and personalization but are not strictly necessary.

**Examples:**
- Language preferences
- Region/timezone settings
- User interface customization
- Recently viewed items

### Analytics

**Options:**
- `cat_analytics_name` - Display name (default: "Analytics")
- `cat_analytics_desc` - Description shown to users

Cookies used to collect information about how visitors use the website.

**Examples:**
- Google Analytics
- Matomo/Piwik
- Hotjar
- Microsoft Clarity

### Marketing

**Options:**
- `cat_marketing_name` - Display name (default: "Marketing")
- `cat_marketing_desc` - Description shown to users

Cookies used for advertising and tracking across websites.

**Examples:**
- Google Ads
- Facebook Pixel
- LinkedIn Insight Tag
- Retargeting cookies

---

## Texts Settings

All text displayed in the cookie banner can be customized:

### Banner Title

**Option:** `banner_title`
**Default:** `We value your privacy`

The heading displayed at the top of the cookie banner.

### Banner Message

**Option:** `banner_message`
**Default:** `We use cookies to enhance your browsing experience and analyze our traffic.`

The main message explaining why cookies are used.

### Button Labels

| Option | Default | Description |
|--------|---------|-------------|
| `btn_accept_all` | Accept All | Accepts all cookie categories |
| `btn_reject_all` | Reject All | Rejects all optional categories |
| `btn_customize` | Customize | Opens the preferences modal |
| `btn_save` | Save Preferences | Saves custom category selections |

---

## Advanced Settings

### Consent Duration

**Option:** `consent_duration`
**Default:** `365` (days)

How long the consent cookie is stored. After this period, users will be prompted to consent again.

**GDPR Recommendation:** Maximum 12 months (365 days).

### Script Blocking

**Option:** `script_blocking`
**Default:** `true`

When enabled, known tracking scripts are automatically blocked until the user provides consent for the relevant category.

**Blocked Scripts Include:**
- Google Analytics (analytics category)
- Google Tag Manager (analytics category)
- Facebook Pixel (marketing category)
- Hotjar (analytics category)
- LinkedIn Insight (marketing category)
- Twitter/X Pixel (marketing category)
- TikTok Pixel (marketing category)
- Pinterest Tag (marketing category)
- Snapchat Pixel (marketing category)

### Google Consent Mode

**Option:** `gcm_enabled`
**Default:** `false`

Enables Google Consent Mode v2 integration. When enabled, consent signals are automatically sent to Google services.

### Show Floating Button

**Option:** `show_floating_button`
**Default:** `true`

Shows a small floating button allowing users to re-open the cookie preferences modal after they've made their initial choice.

### Floating Button Position

**Option:** `floating_button_pos`
**Default:** `bottom-left`

| Value | Description |
|-------|-------------|
| `bottom-left` | Bottom left corner |
| `bottom-right` | Bottom right corner |

---

## WP-CLI Commands

LW Cookie provides comprehensive WP-CLI support for managing settings and consent data from the command line.

### Available Commands

| Command | Description |
|---------|-------------|
| `wp lw-cookie settings list` | List all settings with current values |
| `wp lw-cookie settings get <key>` | Get a specific setting value |
| `wp lw-cookie settings set <key> <value>` | Set a specific setting value |
| `wp lw-cookie settings reset` | Reset all settings to defaults |
| `wp lw-cookie keys` | Show all available setting keys |
| `wp lw-cookie stats` | Display consent statistics |
| `wp lw-cookie export` | Export consent logs |
| `wp lw-cookie clear-logs` | Clear consent logs |

### Settings Management

#### List All Settings

```bash
# Table format (default)
wp lw-cookie settings list

# JSON format
wp lw-cookie settings list --format=json

# YAML format
wp lw-cookie settings list --format=yaml
```

#### Get a Setting

```bash
wp lw-cookie settings get enabled
# Output: true

wp lw-cookie settings get primary_color
# Output: #2271b1
```

#### Set a Setting

```bash
# Enable/disable banner
wp lw-cookie settings set enabled true
wp lw-cookie settings set enabled false

# Change colors
wp lw-cookie settings set primary_color "#ff6600"
wp lw-cookie settings set background_color "#f5f5f5"

# Change texts
wp lw-cookie settings set banner_title "Cookie Settings"
wp lw-cookie settings set btn_accept_all "Accept Cookies"

# Change position
wp lw-cookie settings set banner_position top
wp lw-cookie settings set banner_position modal

# Enable Google Consent Mode
wp lw-cookie settings set gcm_enabled true

# Set consent duration (days)
wp lw-cookie settings set consent_duration 180
```

#### Reset Settings

```bash
wp lw-cookie settings reset
# Resets all settings to default values
```

### View Available Keys

```bash
wp lw-cookie keys

# Output:
# +------------------------+-------------------------------------+---------+
# | key                    | description                         | default |
# +------------------------+-------------------------------------+---------+
# | enabled                | Enable/disable cookie banner        | true    |
# | privacy_policy_page    | Privacy policy page ID              | 0       |
# | policy_version         | Current policy version              | 1.0     |
# | banner_position        | Banner position (bottom, top, modal)| bottom  |
# | ...                    | ...                                 | ...     |
# +------------------------+-------------------------------------+---------+
```

### Consent Statistics

```bash
# Default: last 30 days
wp lw-cookie stats

# Custom time period
wp lw-cookie stats --days=7
wp lw-cookie stats --days=90

# JSON output
wp lw-cookie stats --format=json
```

**Example output:**

```
+--------------------------------+-------+
| metric                         | value |
+--------------------------------+-------+
| Total consents (all time)      | 1543  |
| Consents (last 30 days)        | 234   |
| Accept All (last 30 days)      | 156   |
| Reject All (last 30 days)      | 45    |
| Customize (last 30 days)       | 33    |
| Accept All rate                | 66.7% |
| Reject All rate                | 19.2% |
+--------------------------------+-------+
```

### Export Consent Logs

```bash
# Export to table (default)
wp lw-cookie export

# Export to CSV file
wp lw-cookie export --format=csv > consents.csv

# Export to JSON
wp lw-cookie export --format=json --limit=1000

# Export last 30 days only
wp lw-cookie export --days=30 --format=csv
```

### Clear Consent Logs

```bash
# Delete all logs (requires confirmation)
wp lw-cookie clear-logs

# Skip confirmation
wp lw-cookie clear-logs --yes

# Delete only old logs
wp lw-cookie clear-logs --older-than=365 --yes
```

### Automation Examples

#### Backup Settings Before Update

```bash
# Export current settings
wp lw-cookie settings list --format=json > lw-cookie-backup.json
```

#### Deploy Settings Across Environments

```bash
# On staging/production
wp lw-cookie settings set enabled true
wp lw-cookie settings set gcm_enabled true
wp lw-cookie settings set policy_version "2.0"
wp lw-cookie settings set primary_color "#0066cc"
```

#### Scheduled Log Cleanup (Cron)

```bash
# Add to crontab: delete logs older than 1 year
0 0 1 * * cd /var/www/html && wp lw-cookie clear-logs --older-than=365 --yes
```

#### Monthly Statistics Report

```bash
#!/bin/bash
echo "=== LW Cookie Monthly Report ==="
echo "Date: $(date)"
wp lw-cookie stats --days=30 --format=table
```

---

## JavaScript API

LW Cookie exposes a global `LWCookie` object for programmatic access:

### Methods

```javascript
// Accept all cookies
LWCookie.acceptAll();

// Reject all optional cookies
LWCookie.rejectAll();

// Open the preferences modal
LWCookie.openPreferences();

// Get current consent state
const consent = LWCookie.getConsent();
// Returns: { necessary: true, functional: false, analytics: false, marketing: false }

// Check if a specific category is allowed
if (LWCookie.isAllowed('analytics')) {
    // Load analytics scripts
}
```

### Events

Listen for consent changes:

```javascript
window.addEventListener('lwCookieConsent', function(e) {
    console.log('Consent updated:', e.detail.categories);
    console.log('Action:', e.detail.action); // 'accept_all', 'reject_all', or 'customize'

    if (e.detail.categories.analytics) {
        // User accepted analytics cookies
    }
});
```

### Conditional Script Loading

You can use the API to conditionally load scripts:

```javascript
document.addEventListener('DOMContentLoaded', function() {
    if (LWCookie.isAllowed('analytics')) {
        // Load Google Analytics
        var script = document.createElement('script');
        script.src = 'https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID';
        document.head.appendChild(script);
    }
});
```

---

## Google Consent Mode

When Google Consent Mode is enabled, LW Cookie automatically manages the following consent signals:

| Signal | Cookie Category |
|--------|-----------------|
| `analytics_storage` | Analytics |
| `ad_storage` | Marketing |
| `ad_user_data` | Marketing |
| `ad_personalization` | Marketing |

### Default State

Before user consent, all signals default to `denied`:

```javascript
gtag('consent', 'default', {
    'analytics_storage': 'denied',
    'ad_storage': 'denied',
    'ad_user_data': 'denied',
    'ad_personalization': 'denied'
});
```

### After Consent

When users provide consent, signals are updated accordingly:

```javascript
gtag('consent', 'update', {
    'analytics_storage': 'granted', // if analytics accepted
    'ad_storage': 'granted',        // if marketing accepted
    'ad_user_data': 'granted',      // if marketing accepted
    'ad_personalization': 'granted' // if marketing accepted
});
```

---

## Script Blocking

### How It Works

When script blocking is enabled, the plugin:

1. Scans all `<script>` tags during page load
2. Identifies known tracking scripts by their URL patterns
3. Blocks scripts that require consent for categories the user hasn't accepted
4. Automatically unblocks and loads scripts when consent is granted

### Blocked Script Patterns

| Pattern | Category |
|---------|----------|
| `google-analytics.com` | Analytics |
| `googletagmanager.com` | Analytics |
| `facebook.net` | Marketing |
| `connect.facebook.net` | Marketing |
| `hotjar.com` | Analytics |
| `clarity.ms` | Analytics |
| `linkedin.com/insight` | Marketing |
| `ads.twitter.com` | Marketing |
| `tiktok.com` | Marketing |
| `pintrk` | Marketing |
| `snap.licdn.com` | Marketing |

### Custom Script Blocking

For scripts not automatically detected, use the `type` attribute:

```html
<!-- Block until analytics consent -->
<script type="text/plain" data-consent-category="analytics" data-src="https://example.com/analytics.js"></script>

<!-- Block until marketing consent -->
<script type="text/plain" data-consent-category="marketing" data-src="https://example.com/pixel.js"></script>
```

---

## Database & GDPR Compliance

### Consent Logging

All consent actions are logged to the `{prefix}lw_cookie_consents` table:

| Column | Description |
|--------|-------------|
| `id` | Auto-increment ID |
| `consent_id` | UUID v4 identifier |
| `ip_hash` | SHA-256 hashed IP (anonymized) |
| `categories` | JSON object of consent choices |
| `policy_version` | Policy version at time of consent |
| `action_type` | `accept_all`, `reject_all`, or `customize` |
| `user_agent` | Browser user agent string |
| `created_at` | Timestamp of consent |

### Cookie Structure

Consent is stored in a cookie named `lw_cookie_consent` containing base64-encoded JSON:

```json
{
    "id": "uuid-v4",
    "version": "1.0",
    "timestamp": 1706745600,
    "categories": {
        "necessary": true,
        "functional": false,
        "analytics": false,
        "marketing": false
    }
}
```

### GDPR Compliance Features

- **Opt-in by default:** All optional categories are OFF by default
- **Granular control:** Users can select individual categories
- **Easy withdrawal:** Floating button allows changing preferences anytime
- **Consent proof:** All consents are logged with timestamps
- **Re-consent:** Policy version changes trigger new consent requests
- **IP anonymization:** IP addresses are hashed, not stored in plain text
- **Data retention:** Consent logs can be purged based on your retention policy

### Data Export

To export consent data for a specific user (GDPR data request):

```sql
SELECT * FROM wp_lw_cookie_consents
WHERE consent_id = 'user-consent-uuid';
```

### Data Deletion

To delete consent data (GDPR erasure request):

```sql
DELETE FROM wp_lw_cookie_consents
WHERE consent_id = 'user-consent-uuid';
```

---

## Hooks & Filters

### Actions

```php
// Fired when consent is saved
do_action('lw_cookie_consent_saved', $categories, $action_type, $consent_id);

// Fired when banner is rendered
do_action('lw_cookie_before_banner');
do_action('lw_cookie_after_banner');
```

### Filters

```php
// Modify known scripts list
add_filter('lw_cookie_known_scripts', function($scripts) {
    $scripts['custom_analytics'] = [
        'pattern' => 'custom-analytics.com',
        'category' => 'analytics',
    ];
    return $scripts;
});

// Modify banner output
add_filter('lw_cookie_banner_html', function($html) {
    return $html;
});

// Modify cookie duration
add_filter('lw_cookie_duration', function($days) {
    return 180; // 6 months
});
```

---

## Troubleshooting

### Banner Not Showing

1. Check if the plugin is enabled in settings
2. Clear any caching plugins
3. Check browser console for JavaScript errors
4. Verify the consent cookie doesn't already exist

### Scripts Still Loading

1. Ensure Script Blocking is enabled
2. Check if the script URL matches known patterns
3. For custom scripts, add the appropriate data attributes
4. Clear server-side cache after changing settings

### Google Consent Mode Not Working

1. Enable GCM in Advanced settings
2. Ensure `gtag` is loaded before LW Cookie
3. Verify consent signals in browser console:
   ```javascript
   console.log(window.dataLayer);
   ```

---

## Cookie Declaration

### Admin Management

Navigate to **LW Plugins → Cookie → Cookies** to declare all cookies used on your website.

For each cookie, you can specify:
- **Cookie Name** - The actual cookie name (e.g., `_ga`, `_fbp`)
- **Provider** - Who sets this cookie (e.g., Google Analytics, Facebook)
- **Purpose** - What the cookie is used for
- **Duration** - How long the cookie is stored (e.g., 1 year, Session)
- **Category** - Which consent category it belongs to
- **Type** - Session (deleted when browser closes) or Persistent

Use the "Add Common Cookies" button to quickly add commonly used cookies (WordPress, Google Analytics, Facebook Pixel).

### Shortcode

Display your cookie declaration on any page using the shortcode:

```
[lw_cookie_declaration]
```

**Attributes:**

| Attribute | Default | Description |
|-----------|---------|-------------|
| `class` | (empty) | Additional CSS class for styling |

**Example:**

```
[lw_cookie_declaration class="my-custom-table"]
```

The shortcode displays:
- Cookies grouped by category
- Category names and descriptions
- Full cookie details (name, provider, purpose, duration, type)
- "Manage Cookie Preferences" button
- Responsive table design (mobile-friendly)

---

## GDPR Data Requests (CLI)

### Find Consent Records

Search for consent records by consent ID or IP address:

```bash
# Search by consent ID
wp lw-cookie consent --consent-id=abc123-def456-xyz789

# Search by IP address (will be hashed internally)
wp lw-cookie consent --ip=192.168.1.100

# Export to JSON
wp lw-cookie consent --ip=192.168.1.100 --format=json
```

### Delete Consent Records (Right to Erasure)

```bash
# Find and delete records
wp lw-cookie consent --consent-id=abc123-def456 --delete

# With confirmation skip
wp lw-cookie consent --ip=192.168.1.100 --delete --yes
```

---

## Support

- **GitHub Issues:** [github.com/lwplugins/lw-cookie/issues](https://github.com/lwplugins/lw-cookie/issues)
- **Documentation:** [github.com/lwplugins/lw-cookie/docs](https://github.com/lwplugins/lw-cookie/docs)
