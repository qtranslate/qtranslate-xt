<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function qwc_add_filters_admin() {
    // priority 20 is used because in case other plugins add some untranslated content on normal priority
    // it will still hopefully then get translated.
    $email_ids = array(
        'backorder'                         => 20,
        'cancelled_order'                   => 20,
        'customer_completed_order'          => 20,
        'customer_invoice'                  => 20,
        'customer_invoice_paid'             => 20,
        'customer_new_account'              => 20,
        'customer_note'                     => 20,
        'customer_partially_refunded_order' => 20,
        'customer_processing_order'         => 20,
        'customer_refunded_order'           => 20,
        'customer_reset_password'           => 20,
        'failed_order'                      => 20,
        'low_stock'                         => 20,
        'new_order'                         => 20,
        'no_stock'                          => 20,
    );

    // not all combinations are in use, but it is ok, they may be added in the future.
    foreach ( $email_ids as $name => $priority ) {
        add_filter( 'woocommerce_email_recipient_' . $name, 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', $priority );
        add_filter( 'woocommerce_email_subject_' . $name, 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', $priority );
        add_filter( 'woocommerce_email_heading_' . $name, 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', $priority );
        add_filter( 'woocommerce_email_content_' . $name, 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', $priority );
    }

    $email_common = array(
        'woocommerce_email_footer_text'  => 20,
        'woocommerce_email_from_address' => 20,
        'woocommerce_email_from_name'    => 20,
    );

    foreach ( $email_common as $name => $priority ) {
        add_filter( $name, 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', $priority );
    }
}

qwc_add_filters_admin();

add_filter( 'qtranslate_load_admin_page_config', 'qwc_add_admin_page_config' );
function qwc_add_admin_page_config( $page_configs ) {
    // post.php
    // TODO refactor append config
    if ( ! isset( $page_configs['post'] ) ) {
        $page_configs['post'] = array();
    }
    $post_config = &$page_configs['post'];
    if ( ! isset( $post_config['pages'] ) ) {
        $post_config['pages'] = array( 'post.php' => '', 'post-new.php' => '' );
    }
    if ( ! isset( $post_config['anchors'] ) ) {
        $post_config['anchors'] = array( 'post-body-content' => array( 'where' => 'first last' ) );
    }
    $post_config['anchors']['woocommerce-product-data'] = array( 'where' => 'before' );

    if ( ! isset( $post_config['forms'] ) ) {
        $post_config['forms'] = array();
    }
    if ( ! isset( $post_config['forms']['post'] ) ) {
        $post_config['forms']['post'] = array();
    }
    if ( ! isset( $post_config['forms']['post']['fields'] ) ) {
        $post_config['forms']['post']['fields'] = array();
    }
    $fields                             = &$post_config['forms']['post']['fields'];
    $fields['inp-variable_description'] = array( 'jquery' => 'textarea[name^=variable_description]' );
    $fields['_purchase_note']           = array();
    $fields['td-attribute_name']        = array( 'jquery' => 'td.attribute_name', 'encode' => 'display' );
    $fields['a-wc-order-item-name']     = array( 'jquery' => 'a.wc-order-item-name', 'encode' => 'display' );
    $fields['strong-attribute_name']    = array( 'jquery' => 'strong.attribute_name', 'encode' => 'display' );
    $fields['order_number']             = array( 'jquery' => '.order_number', 'encode' => 'display' );
    $fields['display_meta']             = array( 'jquery' => '.display_meta', 'encode' => 'display' );
    $fields['select-option']            = array( 'jquery' => 'select option', 'encode' => 'display' );

    $page_configs[]              = array(
        'pages'   => array( 'edit.php' => 'post_type=product&page=product_attributes' ),
        'anchors' => array( 'col-container' ),
        'forms'   => array(
            'all' => array(
                array(
                    'form'   => array( 'id' => 'mainform' ),
                    'fields' => array(
                        array( 'id' => 'attribute_label' ),
                        array( 'jquery' => 'td a', 'container_id' => 'col-right', 'encode' => 'display' ),
                        array(
                            'jquery'       => 'td.attribute-terms',
                            'container_id' => 'col-right',
                            'encode'       => 'display'
                        )
                    )
                )
            )
        )
    );

    $page_configs[] = array(
        'pages' => array( 'admin.php' => 'page=wc-settings&tab=tax' ),
        'forms' => array(
            array(
                'form'   => array( 'id' => 'mainform' ),
                'fields' => array(
                    array( 'jquery' => '.subsubsub', 'encode' => 'display' ),
                    array( 'id' => 'woocommerce_tax_classes', 'encode' => 'byline' ),
                    array( 'id' => 'woocommerce_price_display_suffix' )
                )
            )
        )
    );

    $page_configs[] = array(
        'pages' => array( 'admin.php' => 'page=wc-settings&tab=checkout&section=bacs' ),
        'forms' => array(
            array(
                'form'   => array( 'id' => 'mainform' ),
                'fields' => array(
                    array( 'id' => 'woocommerce_bacs_title' ),
                    array( 'id' => 'woocommerce_bacs_description' ),
                    array( 'id' => 'woocommerce_bacs_instructions' )
                )
            )
        )
    );

    $page_configs[] = array(
        'pages' => array( 'admin.php' => 'page=wc-settings&tab=checkout&section=cheque' ),
        'forms' => array(
            array(
                'form'   => array( 'id' => 'mainform' ),
                'fields' => array(
                    array( 'id' => 'woocommerce_cheque_title' ),
                    array( 'id' => 'woocommerce_cheque_description' ),
                    array( 'id' => 'woocommerce_cheque_instructions' )
                )
            )
        )
    );

    $page_configs[] = array(
        'pages' => array( 'admin.php' => 'page=wc-settings&tab=checkout&section=cod' ),
        'forms' => array(
            array(
                'form'   => array( 'id' => 'mainform' ),
                'fields' => array(
                    array( 'id' => 'woocommerce_cod_title' ),
                    array( 'id' => 'woocommerce_cod_description' ),
                    array( 'id' => 'woocommerce_cod_instructions' )
                )
            )
        )
    );

    $page_configs[] = array(
        'pages' => array( 'admin.php' => 'page=wc-settings&tab=checkout&section=paypal' ),
        'forms' => array(
            array(
                'form'   => array( 'id' => 'mainform' ),
                'fields' => array(
                    array( 'id' => 'woocommerce_paypal_title' ),
                    array( 'id' => 'woocommerce_paypal_description' ),
                    array( 'id' => 'woocommerce_paypal_instructions' )
                )
            )
        )
    );

    $page_configs[] = array(
        'pages' => array( 'admin.php' => 'page=wc-settings&tab=account' ),
        'forms' => array(
            array(
                'form'   => array( 'id' => 'mainform' ),
                'fields' => array(
                    array( 'id' => 'woocommerce_registration_privacy_policy_text' ),
                    array( 'id' => 'woocommerce_checkout_privacy_policy_text' )
                )
            )
        )
    );

    // TODO fix tab=shipping, these fields are not static anymore, they have to be handled dynamically in JS
    // $fields[] = array( 'id' => 'woocommerce_free_shipping_title' );
    // $fields[] = array( 'id' => 'woocommerce_flat_rate_title' );
    // $fields[] = array( 'id' => 'woocommerce_international_delivery_title' );
    // $fields[] = array( 'id' => 'woocommerce_local_delivery_title' );
    // $fields[] = array( 'id' => 'woocommerce_local_pickup_title' );

    $page_configs[] = array(
        'pages' => array( 'admin.php' => 'page=wc-settings&tab=email(&section=|)$' ),
        'forms' => array(
            array(
                'form'   => array( 'id' => 'mainform' ),
                'fields' => array(
                    array( 'id' => 'woocommerce_email_from_name' ),
                    array( 'id' => 'woocommerce_email_footer_text' )
                )
            )
        )
    );

    $page_configs[] = array(
        'pages' => array( 'admin.php' => 'page=wc-settings&tab=email&section=wc_email' ),
        'forms' => array(
            array(
                'form'   => array( 'id' => 'mainform' ),
                'fields' => array(
                    array( 'jquery' => 'input.input-text[type=text][name^=woocommerce_]' ),
                    array( 'id' => 'woocommerce_email_footer_text' )
                )
            )
        )
    );

    return $page_configs;
}

function qwc_email_get_option( $value_translated, $wce /* WC_Email object*/, $value = null, $key = null, $empty_value = null ) {
    if ( ! $value ) {
        return $value_translated; // so that older WC versions do not get nasty output
    }

    return $value;
}

add_filter( 'woocommerce_email_get_option', 'qwc_email_get_option', 0, 4 );

add_filter( 'woocommerce_variation_option_name', 'qtranxf_term_name_encoded', 5 );

/**
 * Append the language to the link for changing the order status, so that mails are sent in the language the customer
 * used during the order process
 *
 * @param $url
 *
 * @return string
 */
function qwc_admin_url_append_language( $url ) {
    if ( strpos( $url, 'action=woocommerce_mark_order_status' ) ) {
        $components = parse_url( $url );
        $params     = array();

        parse_str( $components['query'], $params );

        $order_id      = absint( $params['order_id'] );
        $user_language = get_post_meta( $order_id, '_user_language', true );

        if ( $user_language ) {
            $url .= '&lang=' . $user_language;
        }
    }

    return $url;
}

add_filter( 'admin_url', 'qwc_admin_url_append_language' );

/**
 * Append the language to ajax links on the order edit page, so that mails are sent in the language the customer used
 * during the order process
 *
 * @param $url
 *
 * @return string
 */
function qwc_admin_url_append_language_edit_page( $url ) {
    if ( strpos( $url, 'admin-ajax.php' ) === false || ! isset( $_GET['action'] ) || ! isset( $_GET['post'] ) || $_GET['action'] != 'edit' ) {
        return $url;
    }
    $order_id = absint( $_GET['post'] );
    if ( ! $order_id ) {
        return $url;
    }
    $post = get_post( $order_id );
    if ( ! $post ) {
        return $url;
    }
    if ( $post->post_type != 'shop_order' ) {
        return $url;
    }

    $user_language = get_post_meta( $order_id, '_user_language', true );
    if ( $user_language ) {
        return $url . '?lang=' . $user_language;
    }

    return $url;
}

add_filter( 'admin_url', 'qwc_admin_url_append_language_edit_page' );

/**
 * Option 'woocommerce_email_from_name' needs to be translated for e-mails, and needs to stay untranslated for settings.
 *
 * @param $val
 *
 * @return array|mixed|string|void
 */
function qwc_admin_email_option( $val ) {
    global $q_config;
    global $pagenow;

    switch ( $pagenow ) {
        case 'admin-ajax.php':
        case 'post.php':
            return qtranxf_use( $q_config['language'], $val, false, false );
        case 'admin.php'://for sure off
        default:
            return $val;
    }
}

add_filter( 'option_woocommerce_email_from_name', 'qwc_admin_email_option' );

/**
 * This helps to use order's language on re-sent emails from post.php order edit page.
 *
 * @param $content
 * @param null $order
 *
 * @return array|mixed|string|void
 */
function qwc_admin_email_translate( $content, $order = null ) {
    global $q_config;

    $lang = null;
    if ( $order && isset( $order->id ) ) {
        $lang = get_post_meta( $order->id, '_user_language', true );
    }
    if ( ! $lang ) {
        $lang = $q_config['language'];
    }

    return qtranxf_use( $lang, $content, false, false );
}

add_filter( 'woocommerce_email_order_items_table', 'qwc_admin_email_translate', 20, 2 );

/**
 * Called to process action when button 'Save Order' pressed in /wp-admin/post.php?post=xxx&action=edit
 * Helps to partly change language in email sent, but not all, since some parts are already translated into admin language.
 *
 * @param $order
 */
function qwc_admin_before_resend_order_emails( $order ) {
    if ( ! $order || ! isset( $order->id ) ) {
        return;
    }

    $lang = get_post_meta( $order->id, '_user_language', true );
    if ( ! $lang ) {
        return;
    }

    global $q_config;
    $q_config['language'] = $lang;
}

add_action( 'woocommerce_before_resend_order_emails', 'qwc_admin_before_resend_order_emails' );

/**
 * Undo the effect of qwc_admin_before_resend_order_emails
 */
function qwc_admin_after_resend_order_emails( $order ) {
    global $q_config;
    $q_config['language'] = $q_config['url_info']['language'];
}

add_action( 'woocommerce_after_resend_order_email', 'qwc_admin_after_resend_order_emails' );

function qwc_admin_filters() {
    global $pagenow;
    switch ( $pagenow ) {
        case 'admin.php':
            if ( isset( $_SERVER['QUERY_STRING'] ) && strpos( $_SERVER['QUERY_STRING'], 'page=wc-settings&tab=checkout' ) !== false ) {
                add_filter( 'woocommerce_gateway_title', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 5 );
            }
            break;
        case 'edit.php':
            // translate column 'product_cat'
            if ( isset( $_SERVER['QUERY_STRING'] )
                 && strpos( $_SERVER['QUERY_STRING'], 'post_type=product' ) !== false
            ) {
                add_filter( 'get_term', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 6 );
            }
            break;
    }
}

qwc_admin_filters();
