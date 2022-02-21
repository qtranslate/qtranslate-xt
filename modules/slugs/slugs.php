<?php

include_once( dirname( __FILE__ ) . '/includes/class-qtranslate-slug-widget.php' );
include_once( dirname( __FILE__ ) . '/includes/class-qtranslate-slug.php' );

if ( ! defined( "QTS_PREFIX" ) ) {
    define( "QTS_PREFIX", '_qts_' );
}
if ( ! defined( "QTS_PAGE_BASENAME" ) ) {
    define( 'QTS_PAGE_BASENAME', 'qtranslate-slug-settings' );
}
if ( ! defined( "QTS_OPTIONS_NAME" ) ) {
    define( "QTS_OPTIONS_NAME", 'qts_options' );
}
if ( ! defined( "QTS_META_PREFIX" ) ) {
    define( "QTS_META_PREFIX", QTS_PREFIX . 'slug_' );
}

// Init the module
global $qtranslate_slug;

$qtranslate_slug = new QtranslateSlug();

add_action( 'qtranslate_edit_config', array( $qtranslate_slug, 'qtranslate_updated_settings' ) );
// TODO: check if something to do when QTX deactivated
//add_action( 'qtranslate_slug_deactivate',array($qtranslate_slug, 'deactivate'));

// plugin uninstall
register_uninstall_hook( QTRANSLATE_FILE, 'qts_uninstall' );

// plugin init
add_action( 'plugins_loaded', array( $qtranslate_slug, 'init' ) );

////////////////////////////////////////////////////////////////////////////////////////

/**
 * Language Selector Code for templating.
 */
function qts_language_menu( $type = "text", $args = array() ) {
    global $qtranslate_slug;

    $qtranslate_slug->language_menu( $type, $args );
}

function qts_get_url( $lang = false ) {
    global $qtranslate_slug;

    return $qtranslate_slug->get_current_url( $lang );
}

/**
 * Add a "Settings" link to the plugins.php page for Qtranslate Slug.
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
 * Delete plugin stored data ( options and postmeta data ).
 */
function qts_uninstall() {
    global $q_config, $wpdb;

    delete_option( QTS_OPTIONS_NAME );

    $meta_keys = array();
    foreach ( $q_config['enabled_languages'] as $lang ) {
        $meta_keys[] = sprintf( QTS_META_PREFIX . "%s", $lang );
    }
    $meta_keys = "'" . implode( "','", $meta_keys ) . "'";
    $wpdb->query( "DELETE from $wpdb->postmeta WHERE meta_key IN ($meta_keys)" );
}
