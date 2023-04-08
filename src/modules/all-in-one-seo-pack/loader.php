<?php
/**
 * Built-in module for All in One SEO Pack
 */

function qtranxf_aioseop_init_language( array $url_info ): void {
    if ( $url_info['doing_front_end'] ) {
        require_once __DIR__ . '/front.php';
    } else {
        require_once __DIR__ . '/admin.php';
    }
}

add_action( 'qtranslate_init_language', 'qtranxf_aioseop_init_language' );
