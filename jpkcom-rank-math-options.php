<?php
/*
Plugin Name: JPKCom Rank Math Options
Plugin URI: https://github.com/JPKCom/jpkcom-rank-math-options
Description: Opinionated tweaks and options for the Rank Math SEO plugin.
Version: 1.0.2
Author: Jean Pierre Kolb <jpk@jpkc.com>
Author URI: https://www.jpkc.com/
Contributors: JPKCom
Tags: SEO, settings, rank math, robots.txt, htaccess
Requires Plugins: seo-by-rank-math
Requires at least: 6.9
Tested up to: 7.0
Requires PHP: 8.3
Network: true
Stable tag: 1.0.2
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
    define( 'JPKCOM_RANK_MATH_OPTIONS_VERSION', '1.0.2' );
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
 * Loads translation files from the /languages directory.
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
 * @param bool Can edit the robots & .htacess data.
 */
add_filter( 'rank_math/can_edit_file', '__return_true' );


/**
 * Remove the "Powered by Rank Math" HTML comment from the frontend source.
 *
 * @since 1.0.1
 */
add_filter( 'rank_math/frontend/remove_credit_notice', '__return_true' );


/**
 * Remove the "Generator" credit line from Rank Math's sitemap XML output.
 *
 * The filter name is singular ("remove_credit"), not plural — see
 * seo-by-rank-math/includes/modules/sitemap/class-sitemap-xml.php.
 *
 * @since 1.0.1 Initial version targeted the wrong (plural) filter name.
 * @since 1.0.2 Filter name corrected to the singular form Rank Math actually fires.
 */
add_filter( 'rank_math/sitemap/remove_credit', '__return_true' );


/**
 * Force Rank Math's anonymous usage tracking / telemetry to "off".
 *
 * Rank Math does not expose a filter for its telemetry toggle. The setting
 * lives in the 'rank-math-options-general' option under the 'usage_tracking'
 * key (default: 'off'). We defensively rewrite the option on read so the
 * tracker class can never see an "on" value, regardless of what is stored
 * in the database.
 *
 * @since 1.0.1 Initial attempt used a non-existent `rank_math/usage_tracking` filter.
 * @since 1.0.2 Replaced with an `option_*` filter that rewrites the stored value on read.
 * @param mixed $value The option value as loaded from the database.
 * @return mixed
 */
add_filter( 'option_rank-math-options-general', static function ( mixed $value ): mixed {
    if ( is_array( $value ) ) {
        $value['usage_tracking'] = 'off';
    }
    return $value;
} );


/**
 * Clean up a formatting glitch in the generated llms.txt output.
 *
 * The llms.txt format specification expects the file to start with the site's
 * H1 heading (e.g. "# Site Title"). Rank Math prepends an intro paragraph
 * before that heading, which violates the spec and confuses downstream
 * parsers. There is no filter around the offending output (the line is
 * echoed directly from class-llms-txt.php::output()), so we intercept the
 * response body on template_redirect, strip everything preceding the first
 * Markdown H1 line, and hand the cleaned body back to the client.
 *
 * @since 1.0.2
 */
add_action( 'template_redirect', static function (): void {
    if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
        return;
    }

    $path = parse_url( (string) $_SERVER['REQUEST_URI'], PHP_URL_PATH );
    $path = is_string( $path ) ? $path : '';

    if ( $path !== '/llms.txt' ) {
        return;
    }

    ob_start( static function ( string $buffer ): string {
        // Locate the first Markdown H1 line ("# ..."). Everything before it is
        // discarded (including the intro paragraph + trailing blank line).
        if ( preg_match( '/^# /m', $buffer, $matches, PREG_OFFSET_CAPTURE ) ) {
            return substr( $buffer, (int) $matches[0][1] );
        }
        return $buffer;
    } );
}, 0 );


/**
 * Remove Rank Math's top-level menu from the WordPress admin bar.
 *
 * Rank Math registers its admin bar node with the ID "rank-math". Removing
 * it via $wp_admin_bar->remove_node() after Rank Math has added it (priority
 * 100) is more robust than trying to remove the action callback directly,
 * since the callback is a method on a Rank Math singleton instance.
 *
 * @since 1.0.1
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
