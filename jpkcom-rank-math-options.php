<?php
/*
Plugin Name: JPKCom Rank Math Options
Plugin URI: https://github.com/JPKCom/jpkcom-rank-math-options
Description: Opinionated tweaks and options for the Rank Math SEO plugin.
Version: 1.0.0
Author: Jean Pierre Kolb <jpk@jpkc.com>
Author URI: https://www.jpkc.com/
Contributors: JPKCom
Tags: SEO, settings, rank math, robots.txt, htaccess
Requires Plugins: seo-by-rank-math
Requires at least: 6.9
Tested up to: 7.0
Requires PHP: 8.3
Network: true
Stable tag: 1.0.0
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
    define( 'JPKCOM_RANK_MATH_OPTIONS_VERSION', '1.0.0' );
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
