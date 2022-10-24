<?php
const QTX_SLUGS_META_PREFIX = 'qtranslate_slug_';   // The language code is appended.

include_once( dirname( __FILE__ ) . '/qtx_module_slugs.php' );
include_once( dirname( __FILE__ ) . '/src/slugs-utils.php' );

global $qtranslate_slugs;
$qtranslate_slugs = new QTX_Module_Slugs();

if ( is_admin() ) {
    include_once( dirname( __FILE__ ) . '/admin/slugs-admin.php' );
}

add_action( 'plugins_loaded', array( $qtranslate_slugs, 'init' ) );
add_filter( 'qtranslate_convert_url', 'qtranxf_slugs_convert_url', 10, 2 );
