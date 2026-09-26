# LW Cookie - Site Manager Abilities

LW Cookie registers abilities with [LW Site Manager](https://github.com/lwplugins/lw-site-manager) when that plugin is active. These abilities expose cookie consent management to AI agents and REST API consumers via the WordPress Abilities API.

## Category

All abilities are registered under the `cookie` category.

## Abilities

### `lw-cookie/get-options` (readonly)

Get all LW Cookie consent settings, typed like the settings screen's API returns them (every key of the settings model), plus the keys `set-options` may currently write.

**Input:** none

**Output:**
```json
{
  "success": true,
  "options": {
    "enabled": true,
    "banner_position": "bottom",
    "banner_layout": "bar",
    "primary_color": "#d4a017",
    "consent_duration": 365,
    "script_blocking": true,
    "gcm_enabled": false,
    ...
  },
  "writable_keys": ["enabled", "privacy_policy_page", "..."]
}
```

`writable_keys` leaves out the text settings while a multilingual plugin (Polylang, WPML, TranslatePress) owns them.

**Permission:** `can_manage_options`

---

### `lw-cookie/set-options` (write)

Update one or more LW Cookie settings. Only the provided keys are changed. The values go through the same sanitized partial update as the settings screen (`SettingsStore::save()`): choices are checked, colours must be hex, booleans and integers are normalized, text is sanitized, declared-cookie rows are cleaned.

**Input:**
```json
{
  "options": {
    "enabled": true,
    "banner_position": "top",
    "primary_color": "not-a-colour",
    "consent_duration": 180
  }
}
```

**Writable keys:** every key of the settings model, listed explicitly in `includes/SiteManager/AutomationPolicy.php` (a unit test keeps it equal to `Options::get_defaults()`). While a multilingual plugin is active, the text keys it translates are locked, as on the settings screen.

**Output:** valid keys are saved; a key that is unknown, locked or has an invalid value is not stored (the current value stays, like on the settings screen) and is listed in `rejected` with the reason.
```json
{
  "success": true,
  "message": "3 option(s) updated, 1 rejected.",
  "updated": ["enabled", "banner_position", "consent_duration"],
  "rejected": { "primary_color": "Must be a hex colour such as #2271b1." },
  "options": { "...": "all settings after the update" }
}
```

If no key could be saved, the ability returns a `no_valid_options` error (HTTP 400); its message and its `rejected` data list every key with its reason.

**Permission:** `can_manage_options`

---

### `lw-cookie/get-consent-stats` (readonly)

Get consent logging statistics from the database, grouped by action type. Requires the consent logging table to exist (created on plugin activation).

**Input:**
```json
{
  "days": 30
}
```

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `days` | integer | `30` | Number of past days to include |

**Output:**
```json
{
  "success": true,
  "stats": {
    "accept_all": 142,
    "reject_all": 38,
    "customize": 21
  },
  "total": 201,
  "period_days": 30
}
```

**Permission:** `can_manage_options`

---

### `lw-cookie/scan-cookies` (write)

Trigger an HTTP header pre-scan across site URLs. Sends HEAD requests to home, pages, posts, and WooCommerce URLs to detect cookies set via `Set-Cookie` headers. Results are merged into the persistent scanner storage.

**Input:** none

**Output:**
```json
{
  "success": true,
  "cookies": ["_ga", "wc_cart_hash", "wordpress_sec"],
  "domains": [],
  "urls_count": 12
}
```

**Note:** This is a write ability because it performs HTTP requests and modifies stored scanner data. For a full browser-based scan (JS cookies, external domains, fonts), use the admin scanner UI.

**Permission:** `can_manage_options`

---

## Implementation

| File | Purpose |
|------|---------|
| `includes/SiteManager/Integration.php` | Registers hooks and category |
| `includes/SiteManager/CookieAbilities.php` | Ability definitions and schemas |
| `includes/SiteManager/CookieService.php` | Execution callbacks |
| `includes/SiteManager/AutomationPolicy.php` | Keys `set-options` may write |
| `includes/SiteManager/OptionsWriter.php` | Validates and saves `set-options` input via `SettingsStore::save()` |

The integration is initialized in `Plugin::init_components()` via `SiteManagerIntegration::init()`. It registers WordPress action hooks that only fire if LW Site Manager is active, so there is no dependency.
