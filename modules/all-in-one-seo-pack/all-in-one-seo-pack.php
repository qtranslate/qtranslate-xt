<?php
/**
 * Built-in module for All in One SEO Pack
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'qtranslate_compatibility', 'qaioseop_qtrans_compatibility' );
function qaioseop_qtrans_compatibility( $compatibility ) {
    return true;
}

function qaioseop_init_language( $url_info ) {
    if ( $url_info['doing_front_end'] ) {
        require_once( dirname( __FILE__ ) . "/qaioseop-front.php" );
    } else {
        require_once( dirname( __FILE__ ) . "/qaioseop-admin.php" );
    }
}

add_action( 'qtranslate_init_language', 'qaioseop_init_language' );
