<?php
/**
 * Module: Events Made Easy
 *
 * Converted from: Events Made Easy & qTranslate-X (https://wordpress.org/plugins/events-made-easy-qtranslate-x/)
 * @author: johnclause, liedekef
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'QEME_VERSION', '1.1' );

add_filter( 'qtranslate_compatibility', 'qeme_qtrans_compatibility' );
function qeme_qtrans_compatibility( $compatibility ) {
    return true;
}

function qeme_init_language( $url_info ) {
    if ( ! $url_info['doing_front_end'] ) {
        require_once( dirname( __FILE__ ) . "/qeme-admin.php" );
    }
}

add_action( 'qtranslate_init_language', 'qeme_init_language' );
