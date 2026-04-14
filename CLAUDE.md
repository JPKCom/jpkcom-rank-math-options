# JPKCom Rank Math Options – Developer Reference

## Plugin Overview

Small companion plugin that applies opinionated tweaks to [Rank Math SEO](https://wordpress.org/plugins/seo-by-rank-math/).

Currently applies a single filter: `rank_math/can_edit_file` → `__return_true`, which re-enables the robots.txt / .htaccess editors that Rank Math hides by default on multisite and restricted installations.

- **Text Domain:** `jpkcom-rank-math-options`
- **Min PHP:** 8.3 | **Min WP:** 6.9
- **Required Plugin:** `seo-by-rank-math`
- **Network:** `true` (can be network-activated)

---

## Architecture

Intentionally minimal. The plugin is a thin wrapper around a single Rank Math filter plus the shared JPKCom GitHub updater class.

```
Main file (jpkcom-rank-math-options.php)
├── Plugin header (incl. Requires Plugins: seo-by-rank-math, Network: true)
├── Constants (JPKCOM_RANK_MATH_OPTIONS_*)
├── add_filter( 'rank_math/can_edit_file', '__return_true' )
└── init @ priority 5: boot JPKComGitPluginUpdater
```

---

## Constants

| Constant | Default | Purpose |
|----------|---------|---------|
| `JPKCOM_RANK_MATH_OPTIONS_VERSION` | `'1.0.0'` | Plugin version |
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
- Graceful backward compatibility: missing checksum → download allowed with a debug log entry

### Hooks registered
| Hook | Purpose |
|------|---------|
| `plugins_api` | Supplies the "View Details" modal with remote plugin info |
| `site_transient_update_plugins` | Injects available update into WP's update transient |
| `upgrader_process_complete` | Clears manifest transient after a successful update |
| `upgrader_pre_download` | SHA256 checksum verification before installation |

---

## Release Workflow

Triggered by `release: published` on GitHub. Pipeline:

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

| Hook | Type | Value | Effect |
|------|------|-------|--------|
| `rank_math/can_edit_file` | filter | `__return_true` | Re-enables the robots.txt / .htaccess editors in the Rank Math UI, including on multisite |
| `rank_math/frontend/remove_credit_notice` | filter | `__return_true` | Removes the "Powered by Rank Math" HTML comment from the frontend source |
| `rank_math/sitemap/remove_credit` | filter | `__return_true` | Removes the "Generator" credit line from Rank Math's sitemap XML (singular `remove_credit` — verified in `class-sitemap-xml.php`) |
| `option_rank-math-options-general` | filter | rewrites `usage_tracking` → `'off'` | Forces Rank Math's telemetry off at the option layer. Rank Math's tracker class has no filter; the toggle is a plain option read via `Helper::get_settings()` |
| `template_redirect` (priority 0) on `/llms.txt` | action | `ob_start()` + regex strip before first `# ` | Removes Rank Math's hardcoded intro paragraph so `llms.txt` starts with the site's H1 heading as the spec expects. The offending line is echoed directly in `class-llms-txt.php::output()` with no filter. |
| `admin_bar_menu` (priority 999) | action | `$wp_admin_bar->remove_node( 'rank-math' )` | Removes Rank Math's top-level node from the WordPress admin bar. Node ID matches `Admin_Bar_Menu::MENU_IDENTIFIER`. |

---

## Security Checklist

- `declare(strict_types=1)` in every PHP file
- Typed function signatures throughout
- All remote URLs validated via `wp_http_validate_url()` before use
- Remote manifest values sanitized (`sanitize_text_field`, `esc_url_raw`, `wp_kses_post`, `sanitize_key`, `sanitize_title`)
- SHA256 checksum verification on plugin update packages
- Plugin header declares `Requires Plugins: seo-by-rank-math` so WP enforces the dependency

---

## Release checklist

1. Bump version in three places:
   - Plugin header `Version:` + `Stable tag:`
   - Constant `JPKCOM_RANK_MATH_OPTIONS_VERSION`
   - `README.md` header (`**Version:**`, `**Stable tag:**`) and `phpdoc.xml` `<version number="…">`
2. Add a `### x.y.z` section to `## Changelog` in `README.md`
3. Commit, tag `vx.y.z`, push
4. Publish a GitHub Release from that tag — the workflow builds the ZIP, manifest, docs, and deploys to `gh-pages`
