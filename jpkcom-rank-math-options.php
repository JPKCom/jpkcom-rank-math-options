<?php
/*
Plugin Name: JPKCom Rank Math Options
Plugin URI: https://github.com/JPKCom/jpkcom-rank-math-options
Description: Opinionated tweaks and options for the Rank Math SEO plugin.
Version: 1.0.10
Author: Jean Pierre Kolb <jpk@jpkc.com>
Author URI: https://www.jpkc.com/
Contributors: JPKCom
Tags: SEO, settings, rank math, robots.txt, htaccess
Requires Plugins: seo-by-rank-math
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 8.3
Network: true
Stable tag: 1.0.10
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: jpkcom-rank-math-options
Domain Path: /languages
*/

declare(strict_types=1);

if ( ! defined( constant_name: 'WPINC' ) ) {
    die;
}

/**
 * Plugin Constants
 *
 * @since 1.0.0
 */
if ( ! defined( 'JPKCOM_RANK_MATH_OPTIONS_VERSION' ) ) {
    define( 'JPKCOM_RANK_MATH_OPTIONS_VERSION', '1.0.10' );
}

if ( ! defined( 'JPKCOM_RANK_MATH_OPTIONS_BASENAME' ) ) {
    define( 'JPKCOM_RANK_MATH_OPTIONS_BASENAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'JPKCOM_RANK_MATH_OPTIONS_PLUGIN_PATH' ) ) {
    define( 'JPKCOM_RANK_MATH_OPTIONS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'JPKCOM_RANK_MATH_OPTIONS_PLUGIN_URL' ) ) {
    define( 'JPKCOM_RANK_MATH_OPTIONS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Load plugin text domain for translations
 *
 * Loads translation files from the /languages directory. Kept even though the
 * plugin currently ships no translatable strings: WordPress does not register a
 * plugin's own languages directory from the `Domain Path` header on its own, so
 * this call is what a bundled translation would need.
 *
 * @since 1.0.0
 * @return void
 */
function jpkcom_rank_math_options_textdomain(): void {
    load_plugin_textdomain(
        'jpkcom-rank-math-options',
        false,
        dirname( path: JPKCOM_RANK_MATH_OPTIONS_BASENAME ) . '/languages'
    );
}

add_action( 'plugins_loaded', 'jpkcom_rank_math_options_textdomain' );


/**
 * Allow editing the robots.txt & .htaccess data.
 *
 * Rank Math's own default (`Helper::is_edit_allowed()`,
 * includes/helpers/class-conditional.php) is:
 *
 *     ( ! defined( 'DISALLOW_FILE_EDIT' ) || ! DISALLOW_FILE_EDIT ) &&
 *     ( ! defined( 'DISALLOW_FILE_MODS' ) || ! DISALLOW_FILE_MODS )
 *
 * With neither constant defined that is already true, so this filter only ever
 * changes anything when one of them *is* defined - it deliberately overrides
 * that hardening so the robots.txt and .htaccess editors stay usable. Both
 * remain behind Rank Math's own capability checks. Note that .htaccess is
 * effectively server configuration on Apache; this is a conscious trade.
 *
 * @since 1.0.0
 *
 * @param bool $can_edit Whether the robots.txt / .htaccess data may be edited.
 */
add_filter( 'rank_math/can_edit_file', '__return_true' );


/**
 * Remove the "Search Engine Optimization by Rank Math" HTML comment from the
 * frontend source.
 *
 * Gated in Rank Math by `Head::credits()`, which returns early on this filter.
 *
 * @since 1.0.1
 */
add_filter( 'rank_math/frontend/remove_credit_notice', '__return_true' );


/**
 * Remove the "Generator" credit line from Rank Math's sitemap XML output.
 *
 * The filter name is singular ("remove_credit"), not plural — see
 * seo-by-rank-math/includes/modules/sitemap/class-sitemap-xml.php and
 * sitemap-xsl.php.
 *
 * @since 1.0.1 Initial version targeted the wrong (plural) filter name.
 * @since 1.0.2 Filter name corrected to the singular form Rank Math actually fires.
 */
add_filter( 'rank_math/sitemap/remove_credit', '__return_true' );


/**
 * Remove Rank Math's intro paragraph from the generated llms.txt.
 *
 * The llms.txt format expects the file to open with the site's H1 heading.
 * Rank Math prints an intro line plus a blank line before it; both are wrapped
 * in this filter inside `LLMS_Txt::add_header_content()`.
 *
 * @since 1.0.2  Implemented by buffering the response on `template_redirect` and
 *               stripping everything before the first Markdown H1, because Rank
 *               Math offered no filter at the time.
 * @since 1.0.10 Replaced by the filter Rank Math now provides. The buffered
 *               variant keyed off `parse_url( $_SERVER['REQUEST_URI'] ) === '/llms.txt'`
 *               and therefore never ran on an installation in a subdirectory,
 *               where the path is `/subdir/llms.txt` — Rank Math itself detects
 *               the request through `get_query_var( 'llms_txt' )`. The filter is
 *               independent of the request path and produces byte-identical
 *               output.
 */
add_filter( 'rank_math/llms_txt/remove_credit', '__return_true' );


/**
 * Force Rank Math's anonymous usage tracking / telemetry to "off".
 *
 * The switch is the standalone `rank_math_mixpanel_optin` option. Both
 * `Optin::can_track()` and `Optin::is_enabled()` (vendor/wp-media/wp-mixpanel)
 * read it through `get_option( 'rank_math_mixpanel_optin', false )`, so
 * filtering the read is enough to keep tracking off no matter what is stored.
 *
 * `pre_update_option_*` additionally keeps the stored value from ever becoming
 * true. Without it an opt-in would write `true` to the database, and disabling
 * or removing this plugin later would silently switch tracking on.
 *
 * @since 1.0.1  Initial attempt used a non-existent `rank_math/usage_tracking` filter.
 * @since 1.0.2  Replaced with an `option_rank-math-options-general` filter that
 *               rewrote a `usage_tracking` key on read.
 * @since 1.0.10 Retargeted at the option Rank Math actually consults. The
 *               previous approach was inert: the `usage_tracking` settings field
 *               is declared `'save_field' => false`, is never stored in
 *               `rank-math-options-general`, and nothing reads the tracking
 *               state from there. Measured before the change: with tracking
 *               opted in, `Optin::can_track()` returned true despite this plugin
 *               being active.
 */
add_filter( 'option_rank_math_mixpanel_optin', '__return_false', PHP_INT_MAX );
add_filter( 'default_option_rank_math_mixpanel_optin', '__return_false', PHP_INT_MAX );
add_filter( 'pre_update_option_rank_math_mixpanel_optin', '__return_false', PHP_INT_MAX );


/**
 * Remove Rank Math's top-level menu from the WordPress admin bar.
 *
 * Rank Math registers its admin bar node with the ID "rank-math"
 * (`Admin_Bar_Menu::MENU_IDENTIFIER`). Removing it via
 * `$wp_admin_bar->remove_node()` after Rank Math has added it (priority 100) is
 * more robust than trying to unhook the callback, which is a method on a Rank
 * Math singleton instance.
 *
 * @since 1.0.1
 *
 * @param \WP_Admin_Bar $wp_admin_bar The admin bar instance.
 * @return void
 */
add_action( 'admin_bar_menu', static function ( \WP_Admin_Bar $wp_admin_bar ): void {
    $wp_admin_bar->remove_node( 'rank-math' );
}, 999 );


/**
 * Initialize Plugin Updater
 *
 * Loads and initializes the GitHub-based plugin updater with SHA256 checksum verification.
 *
 * @since 1.0.0
 * @return void
 */
add_action( 'init', static function (): void {
    $updater_file = JPKCOM_RANK_MATH_OPTIONS_PLUGIN_PATH . 'includes/class-plugin-updater.php';

    if ( file_exists( $updater_file ) ) {
        require_once $updater_file;

        if ( class_exists( 'JPKComRankMathOptionsGitUpdate\\JPKComGitPluginUpdater' ) ) {
            new \JPKComRankMathOptionsGitUpdate\JPKComGitPluginUpdater(
                plugin_file: __FILE__,
                current_version: JPKCOM_RANK_MATH_OPTIONS_VERSION,
                manifest_url: 'https://jpkcom.github.io/jpkcom-rank-math-options/plugin_jpkcom-rank-math-options.json'
            );
        }
    }
}, 5 );
