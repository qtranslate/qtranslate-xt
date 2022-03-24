<?php

if ( ! defined( "QTS_PREFIX" ) ) {
    define( "QTS_PREFIX", '_qts_' );
}
if ( ! defined( "QTS_OPTIONS_NAME" ) ) {
    define( "QTS_OPTIONS_NAME", 'qts_options' );
}
if ( ! defined( "QTS_META_PREFIX" ) ) {
    define( "QTS_META_PREFIX", QTS_PREFIX . 'slug_' );
}

// Init the module

include_once( dirname( __FILE__ ) . '/includes/class-qtranslate-slug.php' );

global $qtranslate_slug;
$qtranslate_slug = new QtranslateSlug();

include_once( dirname( __FILE__ ) . '/includes/qtranslate-slug-utils.php' );
include_once( dirname( __FILE__ ) . '/includes/qtranslate-slug-admin.php' );

// plugin init
add_action( 'plugins_loaded', array( $qtranslate_slug, 'init' ) );
add_filter( 'qtranslate_convert_url', 'qts_convert_url', 10, 2 );
