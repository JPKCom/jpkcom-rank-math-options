# JPKCom Rank Math Options – Developer Reference

## Plugin Overview

Small companion plugin that applies opinionated tweaks to [Rank Math SEO](https://wordpress.org/plugins/seo-by-rank-math/): it re-enables the robots.txt / .htaccess editors, strips Rank Math's credit lines from the front end, the sitemap and `llms.txt`, forces the anonymous usage tracking off, and removes the SEO node from the admin bar.

- **Text Domain:** `jpkcom-rank-math-options` (declared and loaded, but the plugin currently has **no** translatable strings)
- **Min PHP:** 8.3 | **Min WP:** 6.9
- **Required Plugin:** `seo-by-rank-math`
- **Network:** `true` (can be network-activated)

> **Verified against Rank Math 1.0.275** (released 2026-07-28, the current release at the time of writing). Every hook below carries the Rank Math file and line that consumes it — check those first when a Rank Math update lands, because two of them had silently drifted before 1.0.10.
>
> Header mismatch worth knowing: this plugin declares `Tested up to: 7.1`, in line with the rest of the JPKCom fleet, where that value is a statement of intent rather than a measurement. Rank Math itself — a hard dependency via `Requires Plugins` — declares `tested up to 7.0.2`.

---

## Architecture

Intentionally minimal: a flat list of filter and action registrations, plus the shared JPKCom GitHub updater class (downstream copy of upstream `jpkcom-post-filter`; do not edit per-plugin). SHA256 checksum verification is **mandatory** — a missing or unfetchable `checksum_sha256` aborts the update — and the verified temp file is returned from `upgrader_pre_download`, so WordPress installs exactly the bytes that were hashed.

```
Main file (jpkcom-rank-math-options.php)
├── Plugin header (incl. Requires Plugins: seo-by-rank-math, Network: true)
├── Constants (JPKCOM_RANK_MATH_OPTIONS_*)
├── plugins_loaded  → load_plugin_textdomain()
├── rank_math/can_edit_file                → __return_true
├── rank_math/frontend/remove_credit_notice → __return_true
├── rank_math/sitemap/remove_credit         → __return_true
├── rank_math/llms_txt/remove_credit        → __return_true
├── option_rank_math_mixpanel_optin         → __return_false  (PHP_INT_MAX)
├── default_option_rank_math_mixpanel_optin → __return_false  (PHP_INT_MAX)
├── pre_update_option_rank_math_mixpanel_optin → __return_false (PHP_INT_MAX)
├── admin_bar_menu  → remove_node( 'rank-math' ) (prio 999)
└── init @ priority 5: boot JPKComGitPluginUpdater
```

Rank Math writes its own hooks through `do_filter( 'frontend/remove_credit_notice' )` and friends; `Hooker::do_filter()` (`includes/traits/class-hooker.php:103`) prefixes `rank_math/`. Grepping the Rank Math source for the full filter name therefore finds nothing — search for the suffix.

---

## Constants

| Constant | Default | Purpose |
|----------|---------|---------|
| `JPKCOM_RANK_MATH_OPTIONS_VERSION` | matches the header `Version:` | Plugin version |
| `JPKCOM_RANK_MATH_OPTIONS_BASENAME` | `plugin_basename(__FILE__)` | Plugin basename |
| `JPKCOM_RANK_MATH_OPTIONS_PLUGIN_PATH` | `plugin_dir_path(__FILE__)` | Absolute path |
| `JPKCOM_RANK_MATH_OPTIONS_PLUGIN_URL` | `plugin_dir_url(__FILE__)` | URL |

---

## File Structure

```
jpkcom-rank-math-options/
├── jpkcom-rank-math-options.php    ← Main: header, constants, filters, updater bootstrap
├── includes/
│   └── class-plugin-updater.php    ← GitHub auto-updater (namespace: JPKComRankMathOptionsGitUpdate)
├── .github/
│   └── workflows/
│       └── release.yml             ← Build ZIP, generate manifest, deploy to gh-pages
├── phpdoc.xml                      ← phpDocumentor config
├── README.md                       ← Public-facing readme (also source for WP plugin modal)
├── CLAUDE.md                       ← This file
├── LICENSE                         ← GPL-2.0-or-later
└── .gitignore
```

---

## Plugin Updater

### Namespace
`JPKComRankMathOptionsGitUpdate\JPKComGitPluginUpdater`

### Manifest URL
`https://jpkcom.github.io/jpkcom-rank-math-options/plugin_jpkcom-rank-math-options.json`

### Features
- SHA256 checksum verification of downloaded ZIP (via `upgrader_pre_download`)
- `wp_http_validate_url()` on every remote URL before use
- Race-condition lock on manifest fetch (`*_lock` transient, 30 s)
- 24-hour transient cache of decoded manifest
- Comprehensive error logging when `WP_DEBUG` is on
- **Fail closed:** a missing `checksum_sha256`, or a manifest that cannot be fetched, aborts the update with a `WP_Error`. There is deliberately no "skip verification" fallback — that would let anyone able to alter the manifest disable the integrity check by dropping one field
- The verified temp file is returned from `upgrader_pre_download`, so WordPress installs exactly the bytes that were hashed (no second download)
- Failed manifest fetches are negatively cached for 1 h

### Hooks registered
| Hook | Purpose |
|------|---------|
| `plugins_api` | Supplies the "View Details" modal with remote plugin info |
| `site_transient_update_plugins` | Injects available update into WP's update transient |
| `upgrader_process_complete` | Clears manifest transient after a successful update |
| `upgrader_pre_download` | SHA256 checksum verification before installation |

---

## Release Workflow

**Actions are pinned to commit SHAs.** Every `uses:` line in `.github/workflows/` references a 40-character commit SHA instead of a tag (`@v4`), with the version as a trailing comment. A tag is a movable pointer and can be repointed; a SHA cannot. Since the release workflow builds the plugin ZIP **and** the SHA256 checksum the auto-updater trusts, a compromised action would ship a tampered ZIP together with a matching checksum — the checksum secures the transport, the pinning secures the build. `.github/dependabot.yml` keeps the pins current weekly in one combined PR; when updating, always change the SHA *and* the version comment together.

**CI** (`.github/workflows/ci.yml`) runs on every pull request *and* on every push to `main` — a required status check only covers pull requests, so a direct push with bypass rights would otherwise skip the checks entirely. It runs `php -l` over all PHP files; flags invalid named arguments to internal PHP functions (catches `sprintf(format:, values:)` → `ArgumentCountError`, which `php -l` does not see); validates the YAML of every `.github` file; asserts every action is pinned to a 40-character commit SHA; and executes `tests/test-*.php` where present.

**Dependabot auto-merge** (`.github/workflows/dependabot-auto-merge.yml`) merges only `semver-patch` and `semver-minor`, and only PRs from `dependabot[bot]` in this repo — never from forks. Major updates get a comment and stay manual. Two repo settings are prerequisites, otherwise this is useless or outright dangerous: "Allow auto-merge" must be enabled, and branch protection must list `CI / Lint & Guards` as a **required status check** — without it `gh pr merge --auto` merges *immediately*, since there is nothing left to wait for. Together with `cooldown: default-days: 7` no action release is adopted during its first week.

Triggered by **pushing a `v*` tag** — that tag push is the only trigger, and the workflow creates the GitHub release itself. Do **not** create the release by hand first. Pipeline:

1. Checkout + setup PHP 8.3, Python 3, Pandoc, jq, GraphViz
2. Extract metadata/sections from `README.md` into `gh-pages-json/`
3. Build plugin ZIP via `rsync` into a staging dir named after the repo slug (so WordPress recognises the update directory), then `zip -r`
4. Generate SHA256 of ZIP → inject into manifest + upload `<zip>.sha256` alongside the ZIP on the release
5. Upload ZIP + checksum to the GitHub release
6. Generate `plugin_<slug>.json` manifest (Python) with `download_url`, `checksum_sha256`, sections, contributors, banners, icons
7. Generate PHPDoc via `phpDocumentor.phar` using `phpdoc.xml`
8. Publish `gh-pages-deploy/` (manifest + html + docs + assets) to the `gh-pages` branch

### Manifest fields consumed by the updater
`name`, `display_name`, `slug`, `version`, `download_url`, `checksum_sha256`, `requires`, `tested`, `requires_php`, `author`, `author_profile`, `contributors`, `tags`, `license`, `license_uri`, `text_domain`, `domain_path`, `network`, `requires_plugins`, `homepage`, `last_updated`, `sections.{description,installation,changelog,faq}`, `readme_html`, `banners.{low,high}`, `icons.default`.

---

## Filters & actions applied to Rank Math

| Hook | Value | Consumed by (Rank Math 1.0.275) | Effect |
|------|-------|--------------------------------|--------|
| `rank_math/can_edit_file` | `__return_true` | `Helper::is_edit_allowed()`, `includes/helpers/class-conditional.php:160` | Keeps the robots.txt / .htaccess editors usable — see the caveat below |
| `rank_math/frontend/remove_credit_notice` | `__return_true` | `Head::credits()`, `includes/frontend/class-head.php:415` | Removes the "Search Engine Optimization by Rank Math" HTML comment |
| `rank_math/sitemap/remove_credit` | `__return_true` | `class-sitemap-xml.php:113`, `sitemap-xsl.php:126` and `:214` | Removes the generator credit from the sitemap XML and its stylesheet |
| `rank_math/llms_txt/remove_credit` | `__return_true` | `LLMS_Txt::add_header_content()`, `includes/modules/llms/class-llms-txt.php:160` | Drops the intro line + blank line so `llms.txt` opens with the site H1, as the format expects |
| `option_rank_math_mixpanel_optin` `default_option_…` `pre_update_option_…` | `__return_false` (`PHP_INT_MAX`) | `Optin::can_track()` / `::is_enabled()`, `vendor/wp-media/wp-mixpanel/src/Optin.php:56` and `:37` | Forces the anonymous usage tracking off, and keeps the stored value from ever becoming true |
| `admin_bar_menu` (prio 999) | `remove_node( 'rank-math' )` | `Admin_Bar_Menu::MENU_IDENTIFIER`, registered at prio 100 | Removes Rank Math's top-level admin bar node |

### `can_edit_file` overrides a hardening constant — read this before changing it

Rank Math's default is not "hidden on multisite", as this file claimed until 1.0.10. It is:

```php
( ! defined( 'DISALLOW_FILE_EDIT' ) || ! DISALLOW_FILE_EDIT ) &&
( ! defined( 'DISALLOW_FILE_MODS' ) || ! DISALLOW_FILE_MODS )
```

With neither constant defined that is already `true`, so **the filter's only possible effect is to override an explicit hardening setting.** On a site that sets `DISALLOW_FILE_EDIT`, this plugin re-opens Rank Math's `.htaccess` editor for anyone holding Rank Math's own capability — and on Apache, `.htaccess` is effectively server configuration. That is the deliberate purpose of the plugin, but it is a trade, not a convenience. If it should ever be narrowed, the obvious line is to keep overriding `DISALLOW_FILE_EDIT` while respecting the stronger `DISALLOW_FILE_MODS`.

### Two hooks that can look broken but are not

Neither could be demonstrated through output on the `posts.ddev.site` test instance, for reasons outside this plugin. Verify them at the filter level before concluding they are dead:

- **Frontend credit:** `speed-booster-pack` runs an HTML minifier (`includes/classes/class-sbp-html-minifier.php`) that strips HTML comments, so the credit is absent with or without this plugin.
- **Admin bar node:** Rank Math's own setting `general.admin_bar_menu` was already `false` there, so `Admin_Bar_Menu::add_menu` registers nothing and there is no node left to remove.

### Telemetry: why the option name matters

Up to 1.0.9 this plugin rewrote a `usage_tracking` key inside `rank-math-options-general`. That was inert. The settings field is declared `'save_field' => false` (`includes/settings/general/others.php:99`), is never written to that option — the raw database value has no such key — and its displayed state comes from `escape_cb` reading `get_option( 'rank_math_mixpanel_optin' )`. Nothing consults `rank-math-options-general` for the tracking decision.

Measured against 1.0.275 before the fix: with tracking opted in, `Optin::can_track()` returned `true` while this plugin was active. Filtering the read of `rank_math_mixpanel_optin` covers both `can_track()` and `is_enabled()`; `pre_update_option_*` additionally means an opt-in never lands in the database, so removing this plugin cannot leave tracking switched on behind it.

---

## Security Checklist

- `declare(strict_types=1)` in every PHP file
- Typed function signatures throughout
- All remote URLs validated via `wp_http_validate_url()` before use
- Remote manifest values sanitized (`sanitize_text_field`, `esc_url_raw`, `wp_kses_post`, `sanitize_key`, `sanitize_title`)
- SHA256 checksum verification on plugin update packages
- Plugin header declares `Requires Plugins: seo-by-rank-math` so WP enforces the dependency

---

## Tests

`tests/test-hooks.php` runs standalone — no WordPress and no Rank Math. It stubs the WordPress functions the main file touches at load time, requires the plugin, then asserts hook names and priorities and invokes every callback: the three telemetry filters (including that a stored `true` still reads as `false`), the four Rank Math filters, the admin bar node removal, the updater bootstrap priority, the text domain path, and that the version constant matches the header. It also asserts the *absence* of the two constructs 1.0.9 relied on — `template_redirect` buffering and the `option_rank-math-options-general` filter. 17 cases; 6 of them fail against 1.0.9. CI runs it on every pull request and push to `main`.

```bash
php tests/test-hooks.php   # exit 0 = green
```

What the suite cannot cover is whether Rank Math still fires these hooks. That is the part that drifts, and it needs a real instance — see the file/line references in the hook table above.

---

## Release checklist

1. Bump the version in five places:
   - Plugin header `Version:`
   - Plugin header `Stable tag:`
   - Constant `JPKCOM_RANK_MATH_OPTIONS_VERSION`
   - `phpdoc.xml` `<version number="…">`
   - `README.md` — `**Version:**` and `**Stable tag:**`
2. Add a `### x.y.z` section to `## Changelog` in `README.md`
3. Run `php tests/test-hooks.php`
4. Commit, then push the tag `vx.y.z` — the workflow builds the ZIP, manifest and docs, creates the release and deploys to `gh-pages`
