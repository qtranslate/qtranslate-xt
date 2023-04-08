<?php
/**
 * Module: Events Made Easy
 *
 * Converted from: Events Made Easy & qTranslate-X (https://wordpress.org/plugins/events-made-easy-qtranslate-x/)
 * @author: johnclause, liedekef
 */

function qtranxf_eme_init_language( array $url_info ): void {
    if ( ! $url_info['doing_front_end'] ) {
        require_once __DIR__ . '/admin.php';
    }
}

add_action( 'qtranslate_init_language', 'qtranxf_eme_init_language' );
