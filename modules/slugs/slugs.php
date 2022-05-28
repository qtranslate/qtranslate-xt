<?php

if ( ! defined( "QTS_PREFIX" ) ) {
    define( "QTS_PREFIX", '_qts_' );
}
if ( ! defined( "QTS_META_PREFIX" ) ) {
    define( "QTS_META_PREFIX", QTS_PREFIX . 'slug_' );
}

include_once( dirname( __FILE__ ) . '/src/slugs-class-slugs.php' );
include_once( dirname( __FILE__ ) . '/src/slugs-utils.php' );

global $qtranslate_slugs;
$qtranslate_slugs = new QTX_Slugs();

if ( is_admin() ) {
    include_once( dirname( __FILE__ ) . '/admin/slugs-admin.php' );
}

add_action( 'plugins_loaded', array( $qtranslate_slugs, 'init' ) );
add_filter( 'qtranslate_convert_url', 'qts_convert_url', 10, 2 );
