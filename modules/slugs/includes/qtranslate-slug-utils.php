<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function qts_get_url( $lang = false ) {
    global $qtranslate_slug;

    return $qtranslate_slug->get_current_url( $lang );
}

function qts_convert_url( $url, $lang ) {
    if ( empty( $url ) ) {
        return qts_get_url( $lang );
    }

    return $url;
}

function qts_get_meta_key( $force_lang = false ) {
    global $qtranslate_slug;

    return $qtranslate_slug->get_meta_key( $force_lang );
}

function qts_get_terms( $terms, $taxonomy ){
    global $qtranslate_slug;

    return $qtranslate_slug->get_terms( $terms, $taxonomy );
    
}