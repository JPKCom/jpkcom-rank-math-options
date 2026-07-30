<?php
/**
 * Regression tests for the hook surface of jpkcom-rank-math-options.
 *
 * Runs standalone (no WordPress, no Rank Math): the WordPress functions the main
 * file touches at load time are stubbed, the plugin file is required, and the
 * recorded registrations are then asserted and the callbacks invoked.
 *
 * The Rank Math side of each hook was verified by hand against Rank Math
 * 1.0.275; the file references in the comments below point at the code that
 * consumes them.
 *
 * Every case below is red against 1.0.9.
 *
 * @package JPKCom_Rank_Math_Options
 * @since 1.0.10
 */

declare(strict_types=1);

if ( ! defined( constant_name: 'WPINC' ) ) {
    define( constant_name: 'WPINC', value: true );
}

/** Recorded hook registrations: $GLOBALS['jpkcom_hooks'][type][hook][] = [cb, priority]. */
$GLOBALS['jpkcom_hooks'] = array();

if ( ! function_exists( function: 'add_action' ) ) {
    function add_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
        $GLOBALS['jpkcom_hooks']['action'][ $hook ][] = array( $callback, $priority );
    }
}

if ( ! function_exists( function: 'add_filter' ) ) {
    function add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
        $GLOBALS['jpkcom_hooks']['filter'][ $hook ][] = array( $callback, $priority );
    }
}

if ( ! function_exists( function: 'plugin_basename' ) ) {
    function plugin_basename( string $file ): string {
        return basename( path: dirname( path: $file ) ) . '/' . basename( path: $file );
    }
}

if ( ! function_exists( function: 'plugin_dir_path' ) ) {
    function plugin_dir_path( string $file ): string {
        return dirname( path: $file ) . DIRECTORY_SEPARATOR;
    }
}

if ( ! function_exists( function: 'plugin_dir_url' ) ) {
    function plugin_dir_url( string $file ): string {
        return 'https://example.test/wp-content/plugins/' . basename( path: dirname( path: $file ) ) . '/';
    }
}

if ( ! function_exists( function: 'load_plugin_textdomain' ) ) {
    function load_plugin_textdomain( string $domain, bool $deprecated = false, string $plugin_rel_path = '' ): bool {
        $GLOBALS['jpkcom_textdomain'] = array( $domain, $plugin_rel_path );
        return true;
    }
}

if ( ! function_exists( function: '__return_true' ) ) {
    function __return_true(): bool {
        return true;
    }
}

if ( ! function_exists( function: '__return_false' ) ) {
    function __return_false(): bool {
        return false;
    }
}

if ( ! class_exists( class: 'WP_Admin_Bar' ) ) {
    class WP_Admin_Bar {
        /** @var array<int,string> */
        public array $removed = array();

        public function remove_node( string $id ): void {
            $this->removed[] = $id;
        }
    }
}

require_once dirname( path: __DIR__ ) . '/jpkcom-rank-math-options.php';

$failed = 0;
$passed = 0;

/**
 * Assert a condition and report it.
 *
 * @param string $name Case name.
 * @param bool   $ok   Whether the case passed.
 * @param string $note Extra detail printed on failure.
 * @return void
 */
function jpkcom_check( string $name, bool $ok, string $note = '' ): void {
    global $failed, $passed;

    if ( $ok ) {
        ++$passed;
        printf( "  ok   %s\n", $name );
        return;
    }

    ++$failed;
    printf( "  FAIL %s%s\n", $name, $note !== '' ? ' -- ' . $note : '' );
}

/**
 * Fetch the registered callbacks for a hook.
 *
 * @param string $type action|filter
 * @param string $hook Hook name.
 * @return array<int,array{0:callable,1:int}>
 */
function jpkcom_hooked( string $type, string $hook ): array {
    return $GLOBALS['jpkcom_hooks'][ $type ][ $hook ] ?? array();
}

echo "jpkcom-rank-math-options: hook regressions\n";

/*
 * 1.0.9 rewrote a `usage_tracking` key in `option_rank-math-options-general`.
 * That field is declared 'save_field' => false in Rank Math
 * (includes/settings/general/others.php), is never stored in that option, and
 * nothing reads the tracking state from it. The real switch is the standalone
 * `rank_math_mixpanel_optin` option, read by Optin::can_track() and
 * Optin::is_enabled() (vendor/wp-media/wp-mixpanel/src/Optin.php).
 */
foreach ( array( 'option_rank_math_mixpanel_optin', 'default_option_rank_math_mixpanel_optin' ) as $hook ) {
    $entries = jpkcom_hooked( 'filter', $hook );
    jpkcom_check(
        sprintf( '%s is filtered at PHP_INT_MAX', $hook ),
        $entries !== array() && $entries[0][1] === PHP_INT_MAX,
        $entries === array() ? 'not registered - telemetry stays reachable' : sprintf( 'priority %d', $entries[0][1] )
    );

    if ( $entries !== array() ) {
        jpkcom_check(
            sprintf( '%s reports false even for a stored true', $hook ),
            $entries[0][0]( true ) === false
        );
    }
}

/*
 * Without this, an opt-in writes true to the database and deactivating this
 * plugin would silently switch tracking on.
 */
$pre_update = jpkcom_hooked( 'filter', 'pre_update_option_rank_math_mixpanel_optin' );
jpkcom_check(
    'the stored opt-in value can never become true',
    $pre_update !== array() && $pre_update[0][0]( true ) === false,
    $pre_update === array() ? 'not registered' : 'filter returned a truthy value'
);

jpkcom_check(
    'the inert option_rank-math-options-general filter is gone',
    jpkcom_hooked( 'filter', 'option_rank-math-options-general' ) === array(),
    'still filtering an option Rank Math does not consult for tracking'
);

/*
 * 1.0.9 stripped Rank Math's llms.txt intro by buffering the response on
 * template_redirect and comparing the request path against the literal
 * '/llms.txt' - which never matches on an installation in a subdirectory.
 * Rank Math now wraps those two output lines in a filter
 * (LLMS_Txt::add_header_content()).
 */
$llms = jpkcom_hooked( 'filter', 'rank_math/llms_txt/remove_credit' );
jpkcom_check(
    'the llms.txt intro is removed through Rank Math\'s filter',
    $llms !== array() && $llms[0][0]() === true,
    'not registered'
);

jpkcom_check(
    'no output buffering on template_redirect any more',
    jpkcom_hooked( 'action', 'template_redirect' ) === array(),
    'still buffering the response and matching on $_SERVER[REQUEST_URI]'
);

/* Hooks that must not regress; each verified against Rank Math 1.0.275. */
$expected_true = array(
    // Helper::is_edit_allowed(), includes/helpers/class-conditional.php:160.
    'rank_math/can_edit_file',
    // Head::credits(), includes/frontend/class-head.php:415.
    'rank_math/frontend/remove_credit_notice',
    // Sitemap_XML, includes/modules/sitemap/class-sitemap-xml.php:113.
    'rank_math/sitemap/remove_credit',
);

foreach ( $expected_true as $hook ) {
    $entries = jpkcom_hooked( 'filter', $hook );
    jpkcom_check(
        sprintf( '%s returns true', $hook ),
        $entries !== array() && $entries[0][0]() === true,
        'not registered'
    );
}

/* Admin_Bar_Menu::MENU_IDENTIFIER is 'rank-math'. */
$bar = jpkcom_hooked( 'action', 'admin_bar_menu' );
jpkcom_check( 'the admin bar node is removed', $bar !== array() );

if ( $bar !== array() ) {
    $admin_bar = new WP_Admin_Bar();
    $bar[0][0]( $admin_bar );

    jpkcom_check(
        'it removes exactly the rank-math node',
        $admin_bar->removed === array( 'rank-math' ),
        'removed: ' . implode( ', ', $admin_bar->removed )
    );
    jpkcom_check(
        'it runs after Rank Math registers at priority 100',
        $bar[0][1] > 100,
        sprintf( 'priority %d', $bar[0][1] )
    );
}

/* The updater must still boot, and ahead of the default priority. */
$init = jpkcom_hooked( 'action', 'init' );
jpkcom_check(
    'the updater bootstrap is registered on init',
    $init !== array() && $init[0][1] === 5,
    $init === array() ? 'not registered' : sprintf( 'priority %d', $init[0][1] )
);

/* Translation loading stays wired up even though no strings exist yet. */
foreach ( jpkcom_hooked( 'action', 'plugins_loaded' ) as $entry ) {
    $entry[0]();
}

jpkcom_check(
    'the text domain is loaded from the bundled languages directory',
    ( $GLOBALS['jpkcom_textdomain'][0] ?? '' ) === 'jpkcom-rank-math-options'
    && str_ends_with( haystack: $GLOBALS['jpkcom_textdomain'][1] ?? '', needle: '/languages' )
);

/* Version constant and header must agree. */
$header = array();
preg_match(
    '/^Version:\s*(\S+)/m',
    (string) file_get_contents( dirname( path: __DIR__ ) . '/jpkcom-rank-math-options.php' ),
    $header
);

jpkcom_check(
    'the version constant matches the plugin header',
    defined( constant_name: 'JPKCOM_RANK_MATH_OPTIONS_VERSION' )
    && constant( 'JPKCOM_RANK_MATH_OPTIONS_VERSION' ) === ( $header[1] ?? '' ),
    sprintf(
        'constant %s vs header %s',
        defined( constant_name: 'JPKCOM_RANK_MATH_OPTIONS_VERSION' ) ? (string) constant( 'JPKCOM_RANK_MATH_OPTIONS_VERSION' ) : 'undefined',
        $header[1] ?? 'not found'
    )
);

printf( "\n  %d passed, %d failed\n", $passed, $failed );

exit( $failed > 0 ? 1 : 0 );
