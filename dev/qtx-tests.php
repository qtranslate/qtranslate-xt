<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function qtranxf_tst_log( $msg, $var = 'novar', $bt = false, $exit = false ) {
    qtranxf_dbg_log( $msg, $var, $bt, $exit );
}

function qtranxf_check_test( $result, $expected, $test_name ) {
    //qtranxf_tst_log('qtranxf_check_test: $result='.$result. PHP_EOL .'                 $expected=',$expected);
    if ( $result == $expected ) {
        return true;
    }
    qtranxf_tst_log( $test_name . ': ' . $result . ' != ', $expected );

    return false;
}

function qtranxf_test_interface() {
    $text = apply_filters( 'qtranslate_text', '[:en](EN)text[:de](DE)text[:]' );
    qtranxf_tst_log( 'qtranxf_test_interface: $text: ', $text );

    $text = apply_filters( 'qtranslate_text', '[:en](EN)text[:de](DE)text[:]', 'de' );
    qtranxf_check_test( $text, '(DE)text', 'qtranxf_test_interface: translate_text' );

    $url = apply_filters( 'qtranslate_url', '' );
    qtranxf_tst_log( 'qtranxf_test_interface: $url: ', $url );

    $term = apply_filters( 'qtranslate_term', '(EN) Cat1' );
    qtranxf_tst_log( 'qtranxf_test_interface: $term: ', $term );
}

function qtranxf_test_meta_cache() {
    global $post;
    if ( ! is_singular() || ! $post || 'post' != $post->post_type ) {
        qtranxf_tst_log( 'qtranxf_test_meta_cache: return' );

        return;
    }
    $views = get_post_meta( $post->ID, 'views', true );
    $views = $views ? $views : 0;
    $views++;
    update_post_meta( $post->ID, 'views', $views );
    $views_fetched = get_post_meta( $post->ID, 'views', true );
    if ( qtranxf_check_test( $views_fetched, $views, 'qtranxf_test_meta_cache' ) ) {
        qtranxf_tst_log( 'qtranxf_test_meta_cache: ok' );
    }
}
// TODO clarify how to enable test related to wp_head -> add_filter( 'wp_head', 'qtranxf_test_meta_cache', 1 );
