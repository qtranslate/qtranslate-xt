<?php
// module for Yoast SEO

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function qwpseo_init_language( $url_info ) {
    if ( $url_info['doing_front_end'] ) {
        require_once( dirname( __FILE__ ) . '/wp-seo-front.php' );
    } else {
        require_once( dirname( __FILE__ ) . '/wp-seo-admin.php' );
    }
}

add_action( 'qtranslate_init_language', 'qwpseo_init_language' );
