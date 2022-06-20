<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function qtranxf_wc_add_filters_front() {

    remove_filter( 'get_post_metadata', 'qtranxf_filter_postmeta', 5 );
    add_filter( 'get_post_metadata', 'qtranxf_wc_filter_postmeta', 5, 4 );

    $front_hooks = array(
        'woocommerce_attribute'                             => 20,
        'woocommerce_attribute_label'                       => 20,
        'woocommerce_cart_item_name'                        => 20,
        'woocommerce_cart_item_thumbnail'                   => 20,
        'woocommerce_cart_shipping_method_full_label'       => 20,
        'woocommerce_cart_tax_totals'                       => 20,
        'woocommerce_email_footer_text'                     => 20,
        'woocommerce_format_content'                        => 20,
        'woocommerce_gateway_description'                   => 20,
        'woocommerce_gateway_title'                         => 20,
        'woocommerce_gateway_icon'                          => 20,
        'woocommerce_get_privacy_policy_text'               => 20,
        'woocommerce_order_item_display_meta_value'         => 20,
        'woocommerce_order_item_name'                       => 20,
        'woocommerce_order_get_tax_totals'                  => 20,
        'woocommerce_order_shipping_to_display'             => 20,
        'woocommerce_order_subtotal_to_display'             => 20,
        'woocommerce_page_title'                            => 20,
        'woocommerce_product_get_name'                      => 20,
        'woocommerce_product_title'                         => 20,
        'woocommerce_rate_label'                            => 20,
        'woocommerce_short_description'                     => 20,
        'woocommerce_variation_option_name'                 => 20,
        'wp_mail_from_name'                                 => 20,
    );

    qtranxf_add_filters(['text' => $front_hooks]);

    add_action( 'woocommerce_dropdown_variation_attribute_options_args', 'qtranxf_wc_dropdown_variation_attribute_options_args', 10, 1 );
    add_filter( 'woocommerce_paypal_args', 'qtranxf_wc_paypal_args' );
}

function qtranxf_wc_filter_postmeta( $original_value, $object_id, $meta_key = '', $single = false ) {
    switch ( $meta_key ) {
        case '_product_attributes':
            return $original_value;
        default:
            return qtranxf_filter_postmeta( $original_value, $object_id, $meta_key, $single );
    }
}

/**
 * Update the list of variation attributes for use in the cart forms
 * Only used to translate the options for custom attributes (global attributes already handled through taxonomy)
 *
 * Fun facts:
 * 1) We can't use 'woocommerce_product_get_attributes' because options are discarded when the new value (translated)
 * doesn't match exactly the raw value read from DB in 'read_variation_attributes'
 * 2) We can't use 'woocommerce_variation_option_name' because options are removed when the new value (translated)
 * doesn't match exactly the name (ID) in the browser, processed by the script 'add-to-cart-variations.js'
 *
 * @param array $args
 *
 * @return array
 * @see wc_dropdown_variation_attribute_options (single-product/add-to-cart/variable.php)
 *
 */
function qtranxf_wc_dropdown_variation_attribute_options_args( $args ) {
    if ( isset( $args['options'] ) ) {
        $args['options'] = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $args['options'] );
    }

    return $args;
}

/**
 * Store the current WordPress language along with the order, so we know later on which language the customer used while ordering
 * Called with
 * do_action( 'woocommerce_checkout_update_order_meta', $order_id, $this->posted );
 * in /woocommerce/includes/class-wc-checkout.php
 */
add_action( 'woocommerce_checkout_update_order_meta', 'qtranxf_wc_save_post_meta', 100 );
function qtranxf_wc_save_post_meta( $order_id ) {
    global $q_config;
    add_post_meta( $order_id, '_user_language', $q_config['language'], true );
}

function qtranxf_wc_paypal_args( $args ) {
    $args['lc'] = get_locale();

    return $args;
}

/**
 * Dealing with webhooks, which should always send information in Raw ML format
 */
if ( wp_doing_cron() ) {

    function qtranxf_wc_deliver_webhook_async( $webhook_id, $arg ) {
        $page_configs = qtranxf_get_front_page_config();
        if ( ! empty( $page_configs['']['filters'] ) ) {
            qtranxf_remove_filters( $page_configs['']['filters'] );
        }

        remove_filter( 'get_post_metadata', 'qtranxf_filter_postmeta', 5 );
        remove_filter( 'the_posts', 'qtranxf_postsFilter', 5 );
        remove_action( 'pre_get_posts', 'qtranxf_pre_get_posts', 99 );

        /* Raw ML format is not applicable to terms, as default lang only is stored in obj->name and translations are in qtx options.
         * Hence qtranxf_wc_get_term_raw_ML filter is added to mimic a raw ML format to be sent through webhook.
         */
        add_filter('get_term', 'qtranxf_wc_get_term_raw_ML');
        add_filter('get_terms', 'qtranxf_wc_get_term_raw_ML');
        wp_cache_flush();

        /* Remove WC cached data overwriting current objects. 'product_type' taxonomy is used as a test in WC to avoid multiple registrations.
         * This is applicable to objects in dedicated WC tables, as product attributes.
         */
        delete_transient('wc_attribute_taxonomies');
        unregister_taxonomy('product_type');
        WC_Post_Types::register_taxonomies();
    }

    add_action( 'woocommerce_deliver_webhook_async', 'qtranxf_wc_deliver_webhook_async', 5, 2 );

} else {
    qtranxf_wc_add_filters_front();
}

//TODO: check if this function is to be generalized and moved to inc/qtx_taxonomy.php
function qtranxf_wc_get_term_raw_ML($obj) {
    $term = qtranxf_useTermLib($obj);
    if (!empty($term->i18n_config['name']['ts'])) {
        $term->name = qtranxf_join_b($term->i18n_config['name']['ts']);
    }
    if (!empty($term->i18n_config['description']['ts'])) {
        $term->description = qtranxf_join_b($term->i18n_config['description']['ts']);
    }

    return $term;
}

/**
 * Dealing with mini-cart cache in internal browser storage.
 *
 * @param array $cart wc variable holding contents of the cart without language information.
 *
 * @return string cart hash with language information
 */
function qtranxf_wc_get_cart_hash( $cart ) {
    $lang = qtranxf_getLanguage();

    return md5( json_encode( $cart ) . $lang );
}

/**
 * Dealing with mini-cart cache in internal browser storage.
 * Sets 'woocommerce_cart_hash' cookie.
 *
 * @param array $cart wc variable holding contents of the cart without language information.
 */
function qtranxf_wc_set_cookies_cart_hash( $cart ) {
    $hash = qtranxf_wc_get_cart_hash( $cart );
    wc_setcookie( 'woocommerce_cart_hash', $hash );
}

/**
 * Dealing with mini-cart cache in internal browser storage.
 * Response to action 'woocommerce_cart_loaded_from_session'.
 *
 * @param WC_Cart $wc_cart wc object without language information.
 */
function qtranxf_wc_cart_loaded_from_session( $wc_cart ) {
    if ( headers_sent() ) {
        return;
    }
    $cart = $wc_cart->get_cart_for_session();
    qtranxf_wc_set_cookies_cart_hash( $cart );
}

add_action( 'woocommerce_cart_loaded_from_session', 'qtranxf_wc_cart_loaded_from_session', 5 );

/**
 * Dealing with mini-cart cache in internal browser storage.
 * Response to action 'woocommerce_set_cart_cookies', which overwrites the default WC cart hash and cookies.
 *
 * @param bool $set is true if cookies need to be set, otherwse they are unset in calling function.
 */
function qtranxf_wc_set_cart_cookies( $set ) {
    if ( $set ) {
        $wc      = WC();
        $wc_cart = $wc->cart;
        $cart    = $wc_cart->get_cart_for_session();
        qtranxf_wc_set_cookies_cart_hash( $cart );
    }
}

add_action( 'woocommerce_set_cart_cookies', 'qtranxf_wc_set_cart_cookies' );

/**
 * Dealing with mini-cart cache in internal browser storage.
 * Response to action 'woocommerce_cart_hash' which overwrites the default WC cart hash and cookies.
 *
 * @param string $hash default WC hash.
 * @param array $cart wc variable holding contents of the cart without language information.
 *
 * @return string cart hash with language information
 */
function qtranxf_wc_cart_hash( $hash, $cart ) {
    $new_hash = qtranxf_wc_get_cart_hash( $cart );
    if ( ! headers_sent() ) {
        wc_setcookie( 'woocommerce_cart_hash', $new_hash );
    }

    return $new_hash;
}

add_filter( 'woocommerce_cart_hash', 'qtranxf_wc_cart_hash', 5, 2 );
