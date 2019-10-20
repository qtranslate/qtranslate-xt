<?php
/**
 * Built-in module for WooCommerce
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function qwc_init_language( $url_info ) {
    if ( $url_info['doing_front_end'] ) {
        require_once( dirname( __FILE__ ) . "/qwc-front.php" );
    } else {
        require_once( dirname( __FILE__ ) . "/qwc-admin.php" );
    }
}

add_action( 'qtranslate_init_language', 'qwc_init_language' );

/**
 * Dealing with mini-cart cache in internal browser storage.
 * Prevents language switch on wc-ajax calls.
 * This is needed when user has a few tabs in browser open in different languages, and mini-cart is being refreshed in all of them with wc-ajax calls.
 * This way mini-cart will be shown on all tabs with the same language, which user set the most recently.
 * This makes better sense comparing to showing mini-cart in the language of last change of cart.
 *
 * @param array $url_info qtx variable.
 *
 * @return array possibly modified $url_info.
 */
function qwc_detect_language( $url_info ) {
    if ( isset( $url_info['cookie_lang_front'] ) && $url_info['cookie_lang_front'] != $url_info['language'] ) {
        //language is about to switch
        if ( ! empty( $_GET['wc-ajax'] ) && ! empty( $url_info['doing_front_end'] ) ) {
            //do not switch language on wc-ajax calls, rather stay with previously set language stored in cookies.
            $url_info['language']     = $url_info['cookie_lang_front'];
            $url_info['lang_wc-ajax'] = $url_info['language'];
            $url_info['doredirect']   = 'wc-ajax';
        }
    }

    return $url_info;
}

add_filter( 'qtranslate_detect_language', 'qwc_detect_language', 5 );
