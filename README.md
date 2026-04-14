# JPKCom Rank Math Options

**Plugin Name:** JPKCom Rank Math Options  
**Plugin URI:** https://github.com/JPKCom/jpkcom-rank-math-options  
**Description:** Opinionated tweaks and options for the Rank Math SEO plugin.  
**Version:** 1.0.2  
**Author:** Jean Pierre Kolb <jpk@jpkc.com>  
**Author URI:** https://www.jpkc.com/  
**Contributors:** JPKCom  
**Tags:** SEO, settings, rank math, robots.txt, htaccess  
**Requires Plugins:** seo-by-rank-math  
**Requires at least:** 6.9  
**Tested up to:** 7.0  
**Requires PHP:** 8.3  
**Network:** true  
**Stable tag:** 1.0.2  
**License:** GPL-2.0-or-later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  
**Text Domain:** jpkcom-rank-math-options

A small companion plugin that enables Rank Math SEO features that are disabled by default on certain WordPress installations.

---

## Description

**JPKCom Rank Math Options** is a lightweight helper plugin that applies opinionated defaults to [Rank Math SEO](https://wordpress.org/plugins/seo-by-rank-math/).

By default, Rank Math hides the UI for editing `robots.txt` and `.htaccess` on multisite installations (and on setups where the constant `RANK_MATH_ADVANCED_MODE` is not set). This plugin re-enables those editors via the official `rank_math/can_edit_file` filter. It also trims a handful of Rank Math defaults that most site operators end up disabling manually anyway.

### Key Features

- **Unlocks robots.txt / .htaccess editing** in Rank Math SEO — including on multisite
- **Removes the "Powered by Rank Math" HTML comment** from the frontend source
- **Removes the "Generator" credit line** from Rank Math's XML sitemap
- **Forces Rank Math's anonymous usage tracking / telemetry to off** at the option layer
- **Cleans up the generated `llms.txt`** by stripping Rank Math's intro paragraph so the file starts with the site's H1 as the spec expects
- **Hides the Rank Math admin bar menu** on the frontend and in the backend
- **Network-ready** — can be network-activated and takes effect on every site
- **Zero configuration** — no admin page, no settings to adjust
- **Secure self-hosted updates** — GitHub-based updater with SHA256 checksum verification
- **No dependencies** beyond Rank Math SEO itself

---

## Installation

1. Upload the `jpkcom-rank-math-options` directory to `/wp-content/plugins/`, or install the ZIP via **Plugins → Add New → Upload Plugin**.
2. Activate the plugin (network-wide on multisite) via **Plugins → Installed Plugins**.
3. Ensure **Rank Math SEO** is installed and active.
4. Visit **Rank Math → General Settings → Edit robots.txt** / **Edit .htaccess** — the editors should now be available.

### Configuration

There is nothing to configure. Activating the plugin is enough.

If you want to disable the behaviour temporarily without deactivating the plugin, you can unhook the filter from another plugin or your `functions.php`:

```php
remove_filter( 'rank_math/can_edit_file', '__return_true' );
```

---

## FAQ

### Why can't I edit robots.txt / .htaccess in Rank Math on multisite?
Rank Math disables these editors on multisite by default for safety. This plugin opts your installation back in by returning `true` on the `rank_math/can_edit_file` filter.

### Does this plugin require Rank Math SEO?
Yes. The plugin header declares `Requires Plugins: seo-by-rank-math`, so WordPress will prevent activation until Rank Math SEO is installed and active.

### Does this plugin store any data or add admin pages?
No. It only hooks into a single Rank Math filter and has no UI, no options, and no database writes.

### Does this plugin auto-update?
Yes. It uses a secure, self-hosted GitHub updater with SHA256 checksum verification. Updates appear in **Plugins → Installed Plugins** just like any plugin from wordpress.org.

---

## Changelog

### 1.0.2
- **Fix sitemap credit filter name** — v1.0.1 targeted the non-existent `rank_math/sitemap/remove_credits`; Rank Math actually fires the singular `rank_math/sitemap/remove_credit`
- **Rework telemetry disable** — v1.0.1's `rank_math/usage_tracking` filter does not exist in Rank Math. Replaced with an `option_rank-math-options-general` filter that rewrites the `usage_tracking` key to `off` on read, which is the only reliable hook point since Rank Math's tracker class reads a plain option
- **Clean up `llms.txt`** — strips Rank Math's intro paragraph preceding the first Markdown H1 so the file conforms to the llms.txt spec (the line is hardcoded in Rank Math and has no filter; we intercept the response body on `template_redirect`)

### 1.0.1
- **Remove Rank Math frontend credit** — applies `rank_math/frontend/remove_credit_notice` to strip the "Powered by Rank Math" HTML comment
- **Remove sitemap credit** — applies `rank_math/sitemap/remove_credits` to remove the "Generator" line from the XML sitemap (see v1.0.2 — filter name was wrong)
- **Disable telemetry** — applies `rank_math/usage_tracking` returning `false` (see v1.0.2 — filter does not exist)
- **Hide admin bar menu** — removes the Rank Math node from the WordPress admin bar via `remove_node( 'rank-math' )` at priority 999

### 1.0.0
- Initial release
- `rank_math/can_edit_file` filter returning `true` to unlock robots.txt / .htaccess editing
- GitHub-based self-hosted plugin updater with SHA256 checksum verification
- Network-capable plugin header

---

## Developer Reference

See `CLAUDE.md` in the plugin root for the full developer reference.

### Constants

| Constant | Default | Purpose |
|----------|---------|---------|
| `JPKCOM_RANK_MATH_OPTIONS_VERSION` | `'1.0.0'` | Plugin version |
| `JPKCOM_RANK_MATH_OPTIONS_BASENAME` | `plugin_basename(__FILE__)` | Plugin basename |
| `JPKCOM_RANK_MATH_OPTIONS_PLUGIN_PATH` | `plugin_dir_path(__FILE__)` | Absolute path |
| `JPKCOM_RANK_MATH_OPTIONS_PLUGIN_URL` | `plugin_dir_url(__FILE__)` | URL |

### Filters applied

```php
add_filter( 'rank_math/can_edit_file', '__return_true' );
```
