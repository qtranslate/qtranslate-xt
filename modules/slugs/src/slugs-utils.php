<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

//TOTO: check if this function needs to be retained as documented or not
function qts_get_url( $lang = false ) {
    global $qtranslate_slugs;

    return $qtranslate_slugs->get_current_url( $lang );
}

function qts_convert_url( $url, $lang ) {
    if ( empty( $url ) ) {
        return qts_get_url( $lang );
    }

    return $url;
}
