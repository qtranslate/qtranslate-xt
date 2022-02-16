<?php
/*
Plugin Name: qTranslate slug
Plugin URI: http://not-only-code.github.com/qtranslate-slug/
Description: Allows to define a slug for each language and some qTranslate bug fixes
Version: 1.1.18
Author: Carlos Sanz Garcia, Pedro Carvalho 
Author URI: http://github.com/not-only-code
*/

////////////////////////////////////////////////////////////////////////////////////////

if ( ! function_exists( '_debug' ) ):
    function _debug( $message ) {
        if ( WP_DEBUG === true ):
            if ( is_array( $message ) || is_object( $message ) ) {
                error_log( print_r( $message, true ) );
            } else {
                error_log( $message );
            }
        endif;
    }
endif;

////////////////////////////////////////////////////////////////////////////////////////

/**
 * Includes
 *
 * @since 1.1.8.1
 */
include_once( dirname( __FILE__ ) . '/includes/class-qtranslate-slug-widget.php' );
include_once( dirname( __FILE__ ) . '/includes/class-qtranslate-slug.php' );
include_once( dirname( __FILE__ ) . '/includes/termmeta-core.php' );

////////////////////////////////////////////////////////////////////////////////////////

/**
 * Define Constants
 *
 * @since 1.0
 */
if ( ! defined( "QTS_VERSION" ) ) {
    define( "QTS_VERSION", '1.1.18' );
}
if ( ! defined( "QTS_PREFIX" ) ) {
    define( "QTS_PREFIX", '_qts_' );
}
if ( ! defined( "QTS_PAGE_BASENAME" ) ) {
    define( 'QTS_PAGE_BASENAME', 'qtranslate-slug-settings' );
}
if ( ! defined( "QTS_OPTIONS_NAME" ) ) {
    define( "QTS_OPTIONS_NAME", 'qts_options' );
}
if ( ! defined( "PHP_EOL" ) ) {
    define( "PHP_EOL", "\r\n" );
}

////////////////////////////////////////////////////////////////////////////////////////

/**
 * Init the plugin
 *
 * @since 1.0
 */
global $qtranslate_slug;

$qtranslate_slug = new QtranslateSlug();

// plugin activation/deactivation
//register_activation_hook( __FILE__, array($qtranslate_slug, 'install') );
add_action( 'qtranslate_edit_config', array( $qtranslate_slug, 'qtranslate_updated_settings' ) );

// plugin deactivation
//register_deactivation_hook( __FILE__, array($qtranslate_slug, 'deactivate') );
//add_action( 'qtranslate_slug_deactivate',array($qtranslate_slug, 'deactivate'));

// plugin uninstall
register_uninstall_hook( QTRANSLATE_FILE, 'qts_uninstall' );

// plugin init
add_action( 'plugins_loaded', array( $qtranslate_slug, 'init' ) );

////////////////////////////////////////////////////////////////////////////////////////

/**
 * Language Selector Code for templating
 *
 * @package Qtranslate Slug
 * @subpackage Core
 * @since 1.0
 */
function qts_language_menu( $type = "text", $args = array() ) {
    global $qtranslate_slug;

    $qtranslate_slug->language_menu( $type, $args );
}

/**
 * Finds the translated slug of the given post by calling get_slug
 *
 * @param int $id the post id
 * @param string $lang which language to look for
 *
 * @since 1.1.13
 */

function qts_get_slug( $id, $lang ) {
    global $qtranslate_slug;

    return $qtranslate_slug->get_slug( $id, $lang );
}

/**
 * Adds support for old plugin function
 *
 * @package Qtranslate Slug
 * @subpackage Core
 * @since 1.1.5
 */
function qTranslateSlug_getSelfUrl( $lang = false ) { // bad naming, I'll keep just in case
    return qts_get_url( $lang );
}

function qts_get_url( $lang = false ) {
    global $qtranslate_slug;

    return $qtranslate_slug->get_current_url( $lang );
}

/**
 * Add a "Settings" link to the plugins.php page for Qtranslate Slug
 *
 * @return calls qts_show_msg()
 * @subpackage Settings
 * @version 1.0
 *
 * @package Qtranslate Slug
 */
function qts_add_settings_link( $links, $file ) {

    $this_plugin = plugin_basename( __FILE__ );
    if ( $file == $this_plugin ) {
        $settings_link = "<a href=\"options-general.php?page=" . QTS_PAGE_BASENAME . "\">" . __( 'Settings', 'qts' ) . '</a>';
        array_unshift( $links, $settings_link );
    }

    return $links;
}

add_filter( 'plugin_action_links', 'qts_add_settings_link', 10, 2 );

/**
 * Delete plugin stored data ( options, termmeta table and postmeta data )
 *
 * @package Qtranslate Slug
 * @subpackage Settings
 * @version 1.0
 *
 */
function qts_uninstall() {
    global $q_config, $wpdb, $wp_version;

    // options
    delete_option( QTS_OPTIONS_NAME );
    delete_option( 'qts_version' );

    // don't delete termmeta table as it will be used by wp beginning 4.4
    if ( version_compare( $wp_version, "4.4", "<" ) ) {
        $wpdb->query( "DROP TABLE IF EXISTS $wpdb->termmeta" );
    }

    // delete postmeta data
    $meta_keys = array();
    foreach ( $q_config['enabled_languages'] as $lang ) {
        $meta_keys[] = sprintf( "_qts_slug_%s", $lang );
    }
    $meta_keys = "'" . implode( "','", $meta_keys ) . "'";
    $wpdb->query( "DELETE from $wpdb->postmeta WHERE meta_key IN ($meta_keys)" );
}
