<?php
/**
 * Built-in module for WooCommerce
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function qtranxf_wc_init_language( $url_info ) {
    if ( $url_info['doing_front_end'] ) {
        require_once( dirname( __FILE__ ) . "/qwc-front.php" );
    } else {
        require_once( dirname( __FILE__ ) . "/qwc-admin.php" );
    }
}

add_action( 'qtranslate_init_language', 'qtranxf_wc_init_language' );

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
function qtranxf_wc_detect_language( $url_info ) {
    if ( isset( $url_info['cookie_lang_front'] ) && $url_info['cookie_lang_front'] != $url_info['language'] ) {
        // language is about to switch
        if ( ! empty( $_GET['wc-ajax'] ) && ! empty( $url_info['doing_front_end'] ) ) {
            // do not switch language on wc-ajax calls, rather stay with previously set language stored in cookies.
            $url_info['language']     = $url_info['cookie_lang_front'];
            $url_info['lang_wc-ajax'] = $url_info['language'];
            $url_info['doredirect']   = 'wc-ajax';
        }
    }

    return $url_info;
}

add_filter( 'qtranslate_detect_language', 'qtranxf_wc_detect_language', 5 );

/**
 * Handler for webhooks, which should always send information in Raw ML format.
 *
 * For some cases (e.g. variations updates) the webhook is generated through AJAX instead of cron.
 * In that context, qwc-admin.php is loaded instead of qwc-front.php
 */
function qtranxf_wc_deliver_webhook_async( $webhook_id, $arg ) {
    if ( function_exists( 'qtranxf_get_front_page_config' ) ) {
        $page_configs = qtranxf_get_front_page_config();
        if ( ! empty( $page_configs['']['filters'] ) ) {
            qtranxf_remove_filters( $page_configs['']['filters'] );
        }
    }

    remove_filter( 'get_post_metadata', 'qtranxf_filter_postmeta', 5 );
    remove_filter( 'the_posts', 'qtranxf_postsFilter', 5 );
    remove_action( 'pre_get_posts', 'qtranxf_pre_get_posts', 99 );

    /* Raw ML format is not applicable to terms, as default lang only is stored in obj->name and translations are in qtx options.
     * Hence qtranxf_wc_get_term_raw_ML filter is added to mimic a raw ML format to be sent through webhook.
     */
    add_filter( 'get_term', 'qtranxf_wc_get_term_raw_ML' );
    add_filter( 'get_terms', 'qtranxf_wc_get_term_raw_ML' );
    wp_cache_flush();

    /* Remove admin filters which can affect webhooks
     * TODO: check if qtranxf_wc_add_filters_admin() can be called only if not doing cron or relevant filters are needed in other cron operations.
     * In that case qtranxf_wc_add_filters_admin() can be called conditionally, following qtranxf_remove_filters call can be removed.
     * Otherwise all filters affecting webhooks added in qtranxf_wc_add_filters_admin() must be removed here.
     */
    qtranxf_remove_filters( [
        'text' => [
            'woocommerce_attribute_taxonomies'  => 20,
            'woocommerce_variation_option_name' => 20,
        ]
    ] );

    /* Remove WC cached data overwriting current objects. 'product_type' taxonomy is used as a test in WC to avoid multiple registrations.
     * This is applicable to objects in dedicated WC tables, as product attributes.
     */
    delete_transient( 'wc_attribute_taxonomies' );
    unregister_taxonomy( 'product_type' );
    WC_Post_Types::register_taxonomies();
}

add_action( 'woocommerce_deliver_webhook_async', 'qtranxf_wc_deliver_webhook_async', 5, 2 );

//TODO: check if this function is to be generalized and moved to inc/qtx_taxonomy.php
function qtranxf_wc_get_term_raw_ML( $obj ) {
    $term = qtranxf_useTermLib( $obj );
    if ( ! empty( $term->i18n_config['name']['ts'] ) ) {
        $term->name = qtranxf_join_b( $term->i18n_config['name']['ts'] );
    }
    if ( ! empty( $term->i18n_config['description']['ts'] ) ) {
        $term->description = qtranxf_join_b( $term->i18n_config['description']['ts'] );
    }

    return $term;
}
