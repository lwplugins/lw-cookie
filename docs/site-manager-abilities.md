# LW Cookie - Site Manager Abilities

LW Cookie registers abilities with [LW Site Manager](https://github.com/lwplugins/lw-site-manager) when that plugin is active. These abilities expose cookie consent management to AI agents and REST API consumers via the WordPress Abilities API.

## Category

All abilities are registered under the `cookie` category.

## Abilities

### `lw-cookie/get-options` (readonly)

Get all LW Cookie consent settings.

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
  }
}
```

**Permission:** `can_manage_options`

---

### `lw-cookie/set-options` (write)

Update one or more LW Cookie settings. Only the provided keys are changed.

**Input:**
```json
{
  "options": {
    "enabled": true,
    "banner_position": "top",
    "primary_color": "#2271b1",
    "consent_duration": 180
  }
}
```

**Writable keys:** `enabled`, `privacy_policy_page`, `policy_version`, `banner_position`, `banner_layout`, `primary_color`, `text_color`, `background_color`, `border_radius`, `cat_functional_name`, `cat_functional_desc`, `cat_analytics_name`, `cat_analytics_desc`, `cat_marketing_name`, `cat_marketing_desc`, `banner_title`, `banner_message`, `btn_accept_all`, `btn_reject_all`, `btn_customize`, `btn_save`, `consent_duration`, `script_blocking`, `content_blocking`, `gcm_enabled`, `show_floating_button`, `floating_button_pos`

**Output:**
```json
{
  "success": true,
  "message": "3 option(s) updated.",
  "updated": ["banner_position", "primary_color", "consent_duration"]
}
```

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

The integration is initialized in `Plugin::init_components()` via `SiteManagerIntegration::init()`. It registers WordPress action hooks that only fire if LW Site Manager is active, so there is no dependency.
