<?php
/**
 * Built-in module for All in One SEO Pack
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'qtranslate_compatibility', 'qtranxf_aioseop_qtrans_compatibility' );
function qtranxf_aioseop_qtrans_compatibility( $compatibility ) {
    return true;
}

function qtranxf_aioseop_init_language( $url_info ) {
    if ( $url_info['doing_front_end'] ) {
        require_once( dirname( __FILE__ ) . "/qaioseop-front.php" );
    } else {
        require_once( dirname( __FILE__ ) . "/qaioseop-admin.php" );
    }
}

add_action( 'qtranslate_init_language', 'qtranxf_aioseop_init_language' );
