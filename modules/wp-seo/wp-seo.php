<?php
// module for Yoast SEO

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function qtranxf_wpseo_init_language( $url_info ) {
    if ( $url_info['doing_front_end'] ) {
        require_once __DIR__ . '/wp-seo-front.php';
    } else {
        require_once __DIR__ . '/wp-seo-admin.php';
    }
}

add_action( 'qtranslate_init_language', 'qtranxf_wpseo_init_language' );
