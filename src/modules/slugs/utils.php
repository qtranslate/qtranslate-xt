<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// TODO: check if this function needs to be retained as documented or not
// TODO: clarify lang type bool|string
function qtranxf_slugs_get_url( $lang = false ) {
    global $qtranslate_slugs;

    return $qtranslate_slugs->get_current_url( $lang );
}

function qtranxf_slugs_convert_url( string $url, $lang ): string {
    if ( empty( $url ) ) {
        return qtranxf_slugs_get_url( $lang );
    }

    return $url;
}
