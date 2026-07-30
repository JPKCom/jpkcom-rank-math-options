# JPKCom Rank Math Options

**Plugin Name:** JPKCom Rank Math Options  
**Plugin URI:** https://github.com/JPKCom/jpkcom-rank-math-options  
**Description:** Opinionated tweaks and options for the Rank Math SEO plugin.  
**Version:** 1.0.10  
**Author:** Jean Pierre Kolb <jpk@jpkc.com>  
**Author URI:** https://www.jpkc.com/  
**Contributors:** JPKCom  
**Tags:** SEO, settings, rank math, robots.txt, htaccess  
**Requires Plugins:** seo-by-rank-math  
**Requires at least:** 6.9  
**Tested up to:** 7.1  
**Requires PHP:** 8.3  
**Network:** true  
**Stable tag:** 1.0.10  
**License:** GPL-2.0-or-later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  
**Text Domain:** jpkcom-rank-math-options

A small companion plugin that enables Rank Math SEO features that are disabled by default on certain WordPress installations.

---

## Description

**JPKCom Rank Math Options** is a lightweight helper plugin that applies opinionated defaults to [Rank Math SEO](https://wordpress.org/plugins/seo-by-rank-math/).

Rank Math disables its `robots.txt` and `.htaccess` editors whenever the WordPress constant `DISALLOW_FILE_EDIT` or `DISALLOW_FILE_MODS` is set. This plugin re-enables them through the official `rank_math/can_edit_file` filter, and trims a handful of Rank Math defaults that most site operators end up switching off by hand anyway.

**Please note:** because that is the only condition Rank Math checks, re-enabling the editors means overriding those constants where they are set. On Apache, `.htaccess` is effectively server configuration — activate this plugin only where that is intended.

### Key Features

- **Unlocks robots.txt / .htaccess editing** in Rank Math SEO, including where `DISALLOW_FILE_EDIT` is set
- **Removes the "Search Engine Optimization by Rank Math" HTML comment** from the frontend source
- **Removes the generator credit** from Rank Math's XML sitemap and its stylesheet
- **Forces Rank Math's anonymous usage tracking / telemetry to off**, and prevents an opt-in from being stored, so removing this plugin cannot leave tracking switched on behind it
- **Cleans up the generated `llms.txt`** so the file opens with the site's H1 as the format expects
- **Hides the Rank Math admin bar menu** on the frontend and in the backend
- **Network-ready** — can be network-activated and takes effect on every site
- **Zero configuration** — no admin page, no settings to adjust
- **Secure self-hosted updates** — GitHub-based updater with SHA256 checksum verification
- **No dependencies** beyond Rank Math SEO itself

Verified against **Rank Math 1.0.275**.

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

### Why can't I edit robots.txt / .htaccess in Rank Math?
Rank Math checks a single condition: whether `DISALLOW_FILE_EDIT` or `DISALLOW_FILE_MODS` is defined and true in your `wp-config.php`. If neither is set, the editors are already available and this plugin changes nothing there. If one of them is set, this plugin overrides it by returning `true` on the `rank_math/can_edit_file` filter. Multisite makes no difference of its own.

### Does the plugin turn telemetry off for good?
Yes. Rank Math's switch is the `rank_math_mixpanel_optin` option, and this plugin filters every read of it to `false` — so `Optin::can_track()` reports off no matter what is stored. It also blocks writes to that option, which means an opt-in never reaches the database. Deactivating this plugin therefore leaves tracking off, rather than switching it on.

### Does this plugin require Rank Math SEO?
Yes. The plugin header declares `Requires Plugins: seo-by-rank-math`, so WordPress will prevent activation until Rank Math SEO is installed and active.

### Does this plugin store any data or add admin pages?
No. It registers a handful of filters and one action, and has no UI, no options and no database writes of its own.

### Does this plugin auto-update?
Yes. It uses a secure, self-hosted GitHub updater with SHA256 checksum verification. Updates appear in **Plugins → Installed Plugins** just like any plugin from wordpress.org.

---

## Changelog

### 1.0.10
* Fixed: Rank Math's usage tracking was not actually being switched off. The plugin rewrote a `usage_tracking` key in `rank-math-options-general`, but that settings field is declared `'save_field' => false`, is never stored in that option, and nothing reads the tracking state from it. The real switch is the standalone `rank_math_mixpanel_optin` option, which `Optin::can_track()` and `Optin::is_enabled()` consult. Measured against Rank Math 1.0.275: with tracking opted in, `can_track()` returned **true** despite this plugin being active; it now returns false
* Added: the stored opt-in value can no longer become true either (`pre_update_option_rank_math_mixpanel_optin`). Without that, an opt-in would leave `true` in the database and deactivating or removing this plugin later would silently switch tracking on
* Fixed: the llms.txt intro paragraph is now removed through `rank_math/llms_txt/remove_credit`, the filter Rank Math has since added around exactly those two output lines. The previous implementation buffered the response on `template_redirect` and compared the request path against the literal `/llms.txt`, so it never ran on an installation in a subdirectory, where the path is `/subdir/llms.txt` — precisely the case it was written for. Output is byte-identical; roughly 20 lines of output buffering and regex are gone
* Removed: the `option_rank-math-options-general` filter, which no longer served a purpose once telemetry moved to the option Rank Math actually consults
* Added: `tests/test-hooks.php` covers the hook surface and every callback; CI runs it on every pull request and push to `main`
* Docs: `CLAUDE.md` described one filter where there are six, explained `rank_math/can_edit_file` with multisite instead of the `DISALLOW_FILE_EDIT`/`DISALLOW_FILE_MODS` constants it actually overrides, claimed releases are triggered by `release: published` rather than a tag push, and said the version lives in three places while listing five
* Note: no behaviour change to `rank_math/can_edit_file`. Its only effect is to override an explicit `DISALLOW_FILE_EDIT` or `DISALLOW_FILE_MODS`; with neither constant defined Rank Math already allows the editors. See `CLAUDE.md` for the trade-off

### 1.0.9
* Changed: the update manifest generator now defaults a missing `Network:` header to false instead of true, matching WordPress' own default. No change for this plugin, which declares `Network: true` explicitly
* CI: the lint and guard workflow now also runs on pushes to `main`. It only covered pull requests, so a direct push with bypass rights skipped every check
* Changed: comments, workflow step names and CI output across the repository are now English throughout, and the developer notes in `CLAUDE.md` were translated and trimmed. No effect on the shipped plugin

### 1.0.8
* Changed: `Tested up to` raised to WordPress 7.1
* Changed: the bundled updater's runtime floor now matches the plugin's own minimum. It bailed out below WordPress 6.8 while the plugin header has required 6.9 for several releases, so the check could never fire on a supported installation
* CI: the release manifest's fallback values for `requires` and `tested` now say 6.9 and 7.1. They only apply when the README metadata cannot be read, but a stale fallback would have published a minimum the plugin no longer supports

### 1.0.7
* Added: plugin banners (`assets/banner-1544x500.avif`, `assets/banner-772x250.avif`) — a plain `#3c4955` surface with no lettering. The update manifest already advertised these two URLs, but nothing was published under them, so the plugin card in wp-admin had a broken banner

### 1.0.6
* CI: the release step no longer copies the staging directory into itself, so the ZIP has no empty `jpkcom-rank-math-options/jpkcom-rank-math-options/` folder
* CI: bumped the pinned GitHub Actions (checkout v7.0.1, setup-python v7.0.0, action-gh-release v3.0.2, fetch-metadata v3.1.0), still pinned to full commit SHAs
* CI: the release ZIP now excludes the development-only `tests/` and `tools/` directories
* CI: security and regression tests now run on every pull request, where a plugin has them

### 1.0.5
* Security: update packages are now verified *before* installation — the verified file is handed to WordPress instead of being downloaded a second time, so the bytes that were checked are the bytes that get installed
* Security: a missing or unfetchable SHA-256 checksum now aborts the update instead of installing unverified code (previously it silently skipped verification)
* Security: pinned every GitHub Action to a full commit SHA and added Dependabot with a 7-day cooldown, so a moved tag can no longer change the release build
* Security: tightened which download the updater claims, so sibling plugins cannot match each other's package
* Fixed: `sprintf()` calls in the updater bound named arguments to a variadic parameter, which raises `ArgumentCountError` on PHP 8.3
* Fixed: the "View Details" modal could fail with a `TypeError` when the manifest omitted `requires_plugins`
* Performance: a failed manifest fetch is now cached for an hour instead of being retried on every admin request
* Added: CI workflow on every pull request (PHP lint, named-argument check, YAML validation, action-pinning guard)

### 1.0.4
* Normalized license fallback defaults (updater and release workflow) to `GPL-2.0-or-later` with the HTTPS license URI

### 1.0.3
* Security: updater prefers an exact match against the manifest `download_url` over the slug heuristic, so a tampered manifest can no longer bypass the checksum gate
* Security: timing-safe checksum comparison (`hash_equals()`) with an `is_string()` guard against `hash_file()` failures
* Security: manifest fetch via `wp_safe_remote_get()` (SSRF defense-in-depth)
* Fixed PHP warning and missing contributor names in the plugin detail popup (`display_name` now provided)
* Fixed PHP warning/deprecation on `wp plugin list` by completing the `no_update` transient entry (`new_version`, `package`, `tested`, `requires_php`)

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
| `JPKCOM_RANK_MATH_OPTIONS_VERSION` | matches the header `Version:` | Plugin version |
| `JPKCOM_RANK_MATH_OPTIONS_BASENAME` | `plugin_basename(__FILE__)` | Plugin basename |
| `JPKCOM_RANK_MATH_OPTIONS_PLUGIN_PATH` | `plugin_dir_path(__FILE__)` | Absolute path |
| `JPKCOM_RANK_MATH_OPTIONS_PLUGIN_URL` | `plugin_dir_url(__FILE__)` | URL |

### Hooks applied

```php
add_filter( 'rank_math/can_edit_file', '__return_true' );
add_filter( 'rank_math/frontend/remove_credit_notice', '__return_true' );
add_filter( 'rank_math/sitemap/remove_credit', '__return_true' );
add_filter( 'rank_math/llms_txt/remove_credit', '__return_true' );

add_filter( 'option_rank_math_mixpanel_optin', '__return_false', PHP_INT_MAX );
add_filter( 'default_option_rank_math_mixpanel_optin', '__return_false', PHP_INT_MAX );
add_filter( 'pre_update_option_rank_math_mixpanel_optin', '__return_false', PHP_INT_MAX );

add_action( 'admin_bar_menu', /* remove_node( 'rank-math' ) */, 999 );
```
