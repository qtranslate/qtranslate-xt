<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function qwc_add_filters_admin() {
	//priority 20 is used because in case other plugins add some untranslated content on normal priority, it will still hopefully then get translated.

	$email_ids = array(
		//recipient, subject, content
		'low_stock'                         => 20,
		'no_stock'                          => 20,
		'backorder'                         => 20,
		//recipient, subject, heading
		'new_order'                         => 20,
		'cancelled_order'                   => 20,
		'failed_order'                      => 20,
		'customer_processing_order'         => 20,
		'customer_completed_order'          => 20,
		'customer_refunded_order'           => 20,
		'customer_partially_refunded_order' => 20,
		'customer_invoice'                  => 20,
		'customer_invoice_paid'             => 20,
		'customer_note'                     => 20,
		'customer_reset_password'           => 20,
		'customer_new_account'              => 20
	);

	//not  all combinations are in use, but it is ok, they may be added in the future.
	foreach ( $email_ids as $name => $priority ) {
		add_filter( 'woocommerce_email_recipient_' . $name, 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', $priority );
		add_filter( 'woocommerce_email_subject_' . $name, 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', $priority );
		add_filter( 'woocommerce_email_heading_' . $name, 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', $priority );
		add_filter( 'woocommerce_email_content_' . $name, 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', $priority );
	}

	$email_common = array(
		//'woocommerce_email_title' => 20,
		'woocommerce_email_from_name'    => 20,
		'woocommerce_email_from_address' => 20,
		'woocommerce_email_footer_text'  => 20
		//'woocommerce_email_order_items_table' => 20,//see below
	);

	foreach ( $email_common as $name => $priority ) {
		add_filter( $name, 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', $priority );
	}
}

qwc_add_filters_admin();

//add_filter('i18n_admin_config','qwc_add_admin_page_config');
add_filter( 'qtranslate_load_admin_page_config', 'qwc_add_admin_page_config' );
function qwc_add_admin_page_config( $page_configs ) {
	{//post.php //since 1.0.1
		if ( ! isset( $page_configs['post'] ) ) {
			$page_configs['post'] = array();
		}
		$pgcfg = &$page_configs['post'];
		//$page_config = array();
		if ( ! isset( $pgcfg['pages'] ) ) {
			$pgcfg['pages'] = array( 'post.php' => '', 'post-new.php' => '' );
		}
		if ( ! isset( $pgcfg['anchors'] ) ) {
			$pgcfg['anchors'] = array( 'post-body-content' => array( 'where' => 'first last' ) );
		}
		//$page_config['pages'] = array( 'post.php' => '');
		//$page_config['anchors'] = array( 'post', 'woocommerce-product-data', 'postexcerpt' );
		$pgcfg['anchors']['woocommerce-product-data'] = array( 'where' => 'before' );

		if ( ! isset( $pgcfg['forms'] ) ) {
			$pgcfg['forms'] = array();
		}
		if ( ! isset( $pgcfg['forms']['post'] ) ) {
			$pgcfg['forms']['post'] = array();
		}
		if ( ! isset( $pgcfg['forms']['post']['fields'] ) ) {
			$pgcfg['forms']['post']['fields'] = array();
		}
		//$f = array();
		//$f['form'] = array( 'id' => 'post' );
		//$f['fields'] = array();
		//$fields = &$f['fields']; // shorthand
		$fields = &$pgcfg['forms']['post']['fields'];

		//Custom Product Attributes need more work
		//$fields['inp-attribute_name'] = array( 'jquery' => 'input.attribute_name' );
		//$fields['inp-attribute_values'] = array( 'jquery' => 'textarea[name^=attribute_values]', 'encode' => 'byseparator', 'separator' => '/\\s*\\|\\s*/' );

		$fields['inp-variable_description'] = array( 'jquery' => 'textarea[name^=variable_description]' );
		$fields['_purchase_note']           = array();
		$fields['td-attribute_name']        = array( 'jquery' => 'td.attribute_name', 'encode' => 'display' );
		$fields['strong-attribute_name']    = array( 'jquery' => 'strong.attribute_name', 'encode' => 'display' );
		$fields['order_number']             = array( 'jquery' => '.order_number', 'encode' => 'display' );
		$fields['display_meta']             = array( 'jquery' => '.display_meta', 'encode' => 'display' );
		$fields['select-option']            = array( 'jquery' => 'select option', 'encode' => 'display' );
		//$fields['attr_tax_optn'] = array( 'jquery' => 'select.attribute_taxonomy option', 'encode' => 'display' );
		//$fields[] = array( 'class' => 'attribute_values', 'encode' => 'display' );

		//$page_config['forms'][] = $f;
		//$page_configs[] = $page_config;
	}

	{//edit.php?post_type=product&page=product_attributes
		$page_config            = array();
		$page_config['pages']   = array( 'edit.php' => 'post_type=product&page=product_attributes' );
		$page_config['anchors'] = array( 'col-container' );

		$page_config['forms'] = array();

		$f = array();
		//$f['form'] = array( 'id' => 'mainform' );

		$f['fields'] = array();
		$fields      = &$f['fields']; // shorthand

		$fields['attribute_label'] = array();
		$fields['Name']            = array( 'jquery' => 'td a', 'container_id' => 'col-right', 'encode' => 'display' );
		$fields['Terms']           = array( 'jquery'       => 'td.attribute-terms',
		                                    'container_id' => 'col-right',
		                                    'encode'       => 'display'
		);

		$page_config['forms']['all'] = $f;
		$page_configs[]              = $page_config;
	}

	{//edit-tags.php?taxonomy=xxx&post_type=product
		$page_config          = array();
		$page_config['pages'] = array( 'edit-tags.php' => 'post_type=product' );
		//$page_config['anchors'] = array( 'col-container'  );

		$page_config['forms'] = array();

		$f = array();
		//$f['form'] = array( 'id' => 'mainform' );

		$f['fields'] = array();
		$fields      = &$f['fields']; // shorthand

		//all input fields are ok from default qTranslate-X configuration

		$fields[]     = array( 'tag' => 'LABEL', 'container_class' => 'screen-options', 'encode' => 'display' );
		$fields['h1'] = array( 'jquery' => 'h1', 'container_class' => 'wrap', 'encode' => 'display' );
		$fields[]     = array( 'tag' => 'H2', 'container_class' => 'wrap', 'encode' => 'display' );
		$fields[]     = array( 'tag' => 'H3', 'container_id' => 'col-left', 'encode' => 'display' );
		$fields[]     = array( 'id' => 'search-submit', 'attr' => 'value', 'encode' => 'display' );
		$fields[]     = array( 'id' => 'submit', 'attr' => 'value', 'encode' => 'display' );
		$fields[]     = array( 'id' => 'search-submit', 'attr' => 'value', 'encode' => 'display' );
		//$fields[] = array( 'id' => '' );

		$page_config['forms'][] = $f;
		$page_configs[]         = $page_config;
	}

	{//tab=tax
		$page_config          = array();
		$page_config['pages'] = array( 'admin.php' => 'page=wc-settings&tab=tax' );
		//$page_config['anchors'] = array( 'titlediv'  );

		$page_config['forms'] = array();

		$f         = array();
		$f['form'] = array( 'id' => 'mainform' );

		$f['fields'] = array();
		$fields      = &$f['fields']; // shorthand

		$fields['subsubsub']                        = array( 'jquery' => '.subsubsub', 'encode' => 'display' );
		$fields['woocommerce_tax_classes']          = array( 'encode' => 'byline' );
		$fields['woocommerce_price_display_suffix'] = array();

		$page_config['forms'][] = $f;
		$page_configs[]         = $page_config;
	}

	{//tab=checkout&section=wc_gateway_bacs
		$page_config          = array();
		$page_config['pages'] = array( 'admin.php' => 'page=wc-settings&tab=checkout&section=wc_gateway_bacs' );
		//$page_config['anchors'] = array( 'titlediv'  );

		$page_config['forms'] = array();

		$f         = array();
		$f['form'] = array( 'id' => 'mainform' );

		$f['fields'] = array();
		$fields      = &$f['fields']; // shorthand

		$fields[] = array( 'id' => 'woocommerce_bacs_title' );
		$fields[] = array( 'id' => 'woocommerce_bacs_description' );
		$fields[] = array( 'id' => 'woocommerce_bacs_instructions' );
		//$fields[] = array( 'id' => '' );

		$page_config['forms'][] = $f;
		$page_configs[]         = $page_config;
	}

	{//tab=checkout&section=wc_gateway_cheque
		$page_config          = array();
		$page_config['pages'] = array( 'admin.php' => 'page=wc-settings&tab=checkout&section=wc_gateway_cheque' );
		//$page_config['anchors'] = array( 'titlediv'  );

		$page_config['forms'] = array();

		$f         = array();
		$f['form'] = array( 'id' => 'mainform' );

		$f['fields'] = array();
		$fields      = &$f['fields']; // shorthand

		$fields[] = array( 'id' => 'woocommerce_cheque_title' );
		$fields[] = array( 'id' => 'woocommerce_cheque_description' );
		$fields[] = array( 'id' => 'woocommerce_cheque_instructions' );

		$page_config['forms'][] = $f;
		$page_configs[]         = $page_config;
	}

	{//tab=checkout&section=wc_gateway_cod
		$page_config          = array();
		$page_config['pages'] = array( 'admin.php' => 'page=wc-settings&tab=checkout&section=wc_gateway_cod' );
		//$page_config['anchors'] = array( 'titlediv'  );

		$page_config['forms'] = array();

		$f         = array();
		$f['form'] = array( 'id' => 'mainform' );

		$f['fields'] = array();
		$fields      = &$f['fields']; // shorthand

		$fields[] = array( 'id' => 'woocommerce_cod_title' );
		$fields[] = array( 'id' => 'woocommerce_cod_description' );
		$fields[] = array( 'id' => 'woocommerce_cod_instructions' );

		$page_config['forms'][] = $f;
		$page_configs[]         = $page_config;
	}

	{//tab=checkout&section=wc_gateway_paypal
		$page_config          = array();
		$page_config['pages'] = array( 'admin.php' => 'page=wc-settings&tab=checkout&section=wc_gateway_paypal' );
		//$page_config['anchors'] = array( 'titlediv'  );

		$page_config['forms'] = array();

		$f         = array();
		$f['form'] = array( 'id' => 'mainform' );

		$f['fields'] = array();
		$fields      = &$f['fields']; // shorthand

		$fields[] = array( 'id' => 'woocommerce_paypal_title' );
		$fields[] = array( 'id' => 'woocommerce_paypal_description' );
		$fields[] = array( 'id' => 'woocommerce_paypal_instructions' );

		$page_config['forms'][] = $f;
		$page_configs[]         = $page_config;
	}

	{//tab=shipping&section=wc_shipping_free_shipping
		$page_config          = array();
		$page_config['pages'] = array( 'admin.php' => 'page=wc-settings&tab=shipping&section=wc_shipping_free_shipping' );
		//$page_config['anchors'] = array( 'titlediv'  );

		$page_config['forms'] = array();

		$f         = array();
		$f['form'] = array( 'id' => 'mainform' );

		$f['fields'] = array();
		$fields      = &$f['fields']; // shorthand

		$fields[] = array( 'id' => 'woocommerce_free_shipping_title' );

		$page_config['forms'][] = $f;
		$page_configs[]         = $page_config;
	}

	{//tab=shipping&section=wc_shipping_flat_rate
		$page_config          = array();
		$page_config['pages'] = array( 'admin.php' => 'page=wc-settings&tab=shipping&section=wc_shipping_flat_rate' );
		//$page_config['anchors'] = array( 'titlediv'  );

		$page_config['forms'] = array();

		$f         = array();
		$f['form'] = array( 'id' => 'mainform' );

		$f['fields'] = array();
		$fields      = &$f['fields']; // shorthand

		$fields[] = array( 'id' => 'woocommerce_flat_rate_title' );

		$page_config['forms'][] = $f;
		$page_configs[]         = $page_config;
	}

	{//tab=shipping&section=wc_shipping_international_delivery
		$page_config          = array();
		$page_config['pages'] = array( 'admin.php' => 'page=wc-settings&tab=shipping&section=wc_shipping_international_delivery' );
		//$page_config['anchors'] = array( 'titlediv'  );

		$page_config['forms'] = array();

		$f         = array();
		$f['form'] = array( 'id' => 'mainform' );

		$f['fields'] = array();
		$fields      = &$f['fields']; // shorthand

		$fields[] = array( 'id' => 'woocommerce_international_delivery_title' );

		$page_config['forms'][] = $f;
		$page_configs[]         = $page_config;
	}

	{//tab=shipping&section=wc_shipping_local_delivery
		$page_config          = array();
		$page_config['pages'] = array( 'admin.php' => 'page=wc-settings&tab=shipping&section=wc_shipping_local_delivery' );
		//$page_config['anchors'] = array( 'titlediv'  );

		$page_config['forms'] = array();

		$f         = array();
		$f['form'] = array( 'id' => 'mainform' );

		$f['fields'] = array();
		$fields      = &$f['fields']; // shorthand

		$fields[] = array( 'id' => 'woocommerce_local_delivery_title' );

		$page_config['forms'][] = $f;
		$page_configs[]         = $page_config;
	}

	{//tab=shipping&section=wc_shipping_local_pickup
		$page_config          = array();
		$page_config['pages'] = array( 'admin.php' => 'page=wc-settings&tab=shipping&section=wc_shipping_local_pickup' );
		//$page_config['anchors'] = array( 'titlediv'  );

		$page_config['forms'] = array();

		$f         = array();
		$f['form'] = array( 'id' => 'mainform' );

		$f['fields'] = array();
		$fields      = &$f['fields']; // shorthand

		$fields[] = array( 'id' => 'woocommerce_local_pickup_title' );

		$page_config['forms'][] = $f;
		$page_configs[]         = $page_config;
	}

	{//tab=email
		$page_config          = array();
		$page_config['pages'] = array( 'admin.php' => 'page=wc-settings&tab=email(&section=|)$' );
		//$page_config['anchors'] = array( 'titlediv'  );

		$page_config['forms'] = array();

		$f         = array();
		$f['form'] = array( 'id' => 'mainform' );

		$f['fields'] = array();
		$fields      = &$f['fields']; // shorthand

		$fields[] = array( 'id' => 'woocommerce_email_from_name' );
		//$fields[] = array( 'id' => 'woocommerce_email_from_address' );//?
		//$fields[] = array( 'id' => 'woocommerce_email_header_image' );
		$fields[] = array( 'id' => 'woocommerce_email_footer_text' );

		$page_config['forms'][] = $f;
		$page_configs[]         = $page_config;
	}

	{//tab=email&section=XXX
		$page_config          = array();
		$page_config['pages'] = array( 'admin.php' => 'page=wc-settings&tab=email&section=wc_email' );
		//$page_config['anchors'] = array( 'titlediv'  );

		$page_config['forms'] = array();

		$f         = array();
		$f['form'] = array( 'id' => 'mainform' );

		$f['fields'] = array();
		$fields      = &$f['fields']; // shorthand

		$fields[] = array( 'jquery' => 'input.input-text[type=text][name^=woocommerce_]' );

		$page_config['forms'][] = $f;
		$page_configs[]         = $page_config;
	}
	/*
		{//tab=email&section=wc_email_new_order
		$page_config = array();
		$page_config['pages'] = array( 'admin.php' => 'page=wc-settings&tab=email&section=wc_email_new_order');
		//$page_config['anchors'] = array( 'titlediv'  );

		$page_config['forms'] = array();

		$f = array();
		$f['form'] = array( 'id' => 'mainform' );

		$f['fields'] = array();
		$fields = &$f['fields']; // shorthand

		$fields[] = array( 'id' => 'woocommerce_new_order_recipient' );
		$fields[] = array( 'id' => 'woocommerce_new_order_subject' );
		$fields[] = array( 'id' => 'woocommerce_new_order_heading' );

		$page_config['forms'][] = $f;
		$page_configs[] = $page_config;
		}

		{//tab=email&section=wc_email_cancelled_order
		$page_config = array();
		$page_config['pages'] = array( 'admin.php' => 'page=wc-settings&tab=email&section=wc_email_cancelled_order');
		//$page_config['anchors'] = array( 'titlediv'  );

		$page_config['forms'] = array();

		$f = array();
		$f['form'] = array( 'id' => 'mainform' );

		$f['fields'] = array();
		$fields = &$f['fields']; // shorthand

		$fields[] = array( 'id' => 'woocommerce_cancelled_order_recipient' );
		$fields[] = array( 'id' => 'woocommerce_cancelled_order_subject' );
		$fields[] = array( 'id' => 'woocommerce_cancelled_order_heading' );

		$page_config['forms'][] = $f;
		$page_configs[] = $page_config;
		}

		{//tab=email&section=wc_email_customer_processing_order
		$page_config = array();
		$page_config['pages'] = array( 'admin.php' => 'page=wc-settings&tab=email&section=wc_email_customer_processing_order');
		//$page_config['anchors'] = array( 'titlediv'  );

		$page_config['forms'] = array();

		$f = array();
		$f['form'] = array( 'id' => 'mainform' );

		$f['fields'] = array();
		$fields = &$f['fields']; // shorthand

		//$fields[] = array( 'id' => 'woocommerce_customer_processing_order_recipient' );
		$fields[] = array( 'id' => 'woocommerce_customer_processing_order_subject' );
		$fields[] = array( 'id' => 'woocommerce_customer_processing_order_heading' );

		$page_config['forms'][] = $f;
		$page_configs[] = $page_config;
		}

		{//tab=email&section=wc_email_customer_completed_order
		$page_config = array();
		$page_config['pages'] = array( 'admin.php' => 'page=wc-settings&tab=email&section=wc_email_customer_completed_order');
		//$page_config['anchors'] = array( 'titlediv'  );

		$page_config['forms'] = array();

		$f = array();
		$f['form'] = array( 'id' => 'mainform' );

		$f['fields'] = array();
		$fields = &$f['fields']; // shorthand

		$fields[] = array( 'id' => 'woocommerce_customer_completed_order_subject' );
		$fields[] = array( 'id' => 'woocommerce_customer_completed_order_heading' );
		$fields[] = array( 'id' => 'woocommerce_customer_completed_order_subject_downloadable' );
		$fields[] = array( 'id' => 'woocommerce_customer_completed_order_heading_downloadable' );

		$page_config['forms'][] = $f;
		$page_configs[] = $page_config;
		}

		{//tab=email&section=wc_email_customer_refunded_order
		$page_config = array();
		$page_config['pages'] = array( 'admin.php' => 'page=wc-settings&tab=email&section=wc_email_customer_refunded_order');
		//$page_config['anchors'] = array( 'titlediv'  );

		$page_config['forms'] = array();

		$f = array();
		$f['form'] = array( 'id' => 'mainform' );

		$f['fields'] = array();
		$fields = &$f['fields']; // shorthand

		$fields[] = array( 'id' => 'woocommerce_customer_refunded_order_subject_full' );
		$fields[] = array( 'id' => 'woocommerce_customer_refunded_order_subject_partial' );
		$fields[] = array( 'id' => 'woocommerce_customer_refunded_order_subject_partial' );
		$fields[] = array( 'id' => 'woocommerce_customer_refunded_order_heading_partial' );
		$fields[] = array( 'id' => 'woocommerce_customer_refunded_order_heading_full' );

		$page_config['forms'][] = $f;
		$page_configs[] = $page_config;
		}

		{//tab=email&section=wc_email_customer_invoice
		$page_config = array();
		$page_config['pages'] = array( 'admin.php' => 'page=wc-settings&tab=email&section=wc_email_customer_invoice');
		//$page_config['anchors'] = array( 'titlediv'  );

		$page_config['forms'] = array();

		$f = array();
		$f['form'] = array( 'id' => 'mainform' );

		$f['fields'] = array();
		$fields = &$f['fields']; // shorthand

		$fields[] = array( 'id' => 'woocommerce_customer_invoice_subject' );
		$fields[] = array( 'id' => 'woocommerce_customer_invoice_heading' );
		$fields[] = array( 'id' => 'woocommerce_customer_invoice_subject_paid' );
		$fields[] = array( 'id' => 'woocommerce_customer_invoice_heading_paid' );

		$page_config['forms'][] = $f;
		$page_configs[] = $page_config;
		}

		{//tab=email&section=wc_email_customer_note
		$page_config = array();
		$page_config['pages'] = array( 'admin.php' => 'page=wc-settings&tab=email&section=wc_email_customer_note');
		//$page_config['anchors'] = array( 'titlediv'  );

		$page_config['forms'] = array();

		$f = array();
		$f['form'] = array( 'id' => 'mainform' );

		$f['fields'] = array();
		$fields = &$f['fields']; // shorthand

		$fields[] = array( 'id' => 'woocommerce_customer_note_subject' );
		$fields[] = array( 'id' => 'woocommerce_customer_note_heading' );

		$page_config['forms'][] = $f;
		$page_configs[] = $page_config;
		}

		{//tab=email&section=wc_email_customer_reset_password
		$page_config = array();
		$page_config['pages'] = array( 'admin.php' => 'page=wc-settings&tab=email&section=wc_email_customer_reset_password');
		//$page_config['anchors'] = array( 'titlediv'  );

		$page_config['forms'] = array();

		$f = array();
		$f['form'] = array( 'id' => 'mainform' );

		$f['fields'] = array();
		$fields = &$f['fields']; // shorthand

		$fields[] = array( 'id' => 'woocommerce_customer_reset_password_subject' );
		$fields[] = array( 'id' => 'woocommerce_customer_reset_password_heading' );

		$page_config['forms'][] = $f;
		$page_configs[] = $page_config;
		}

		{//tab=email&section=wc_email_customer_new_account
		$page_config = array();
		$page_config['pages'] = array( 'admin.php' => 'page=wc-settings&tab=email&section=wc_email_customer_new_account');
		//$page_config['anchors'] = array( 'titlediv'  );

		$page_config['forms'] = array();

		$f = array();
		$f['form'] = array( 'id' => 'mainform' );

		$f['fields'] = array();
		$fields = &$f['fields']; // shorthand

		$fields[] = array( 'id' => 'woocommerce_customer_new_account_subject' );
		$fields[] = array( 'id' => 'woocommerce_customer_new_account_heading' );

		$page_config['forms'][] = $f;
		$page_configs[] = $page_config;
		}
	*/
	return $page_configs;
}

function qwc_email_get_option( $value_translated, $wce /* WC_Email object*/, $value = null, $key = null, $empty_value = null ) {
	if ( ! $value ) {
		return $value_translated;
	}//so that older WC versions do not get nasty output.

	return $value;
}

add_filter( 'woocommerce_email_get_option', 'qwc_email_get_option', 0, 4 );

/**
 * @since 1.0.1
 */
/*
function qwc_attribute_label($label, $name){
	//global $q_config;
	//if(isset($q_config['term_name'][$name])) {
	//	$label = qtranxf_join_b($q_config['term_name'][$name]);
	//}
	//qtranxf_dbg_log('qwc_attribute_label: label="'.$label.'"; name: ',$name);
	$label = qtranxf_term_name_encoded($label);
	//qtranxf_dbg_log('qwc_attribute_label: label: ',$label);
	return $label;
}
add_filter( 'woocommerce_attribute_label', 'qwc_attribute_label', 0, 2 );
*/
add_filter( 'woocommerce_variation_option_name', 'qtranxf_term_name_encoded', 5 );

/*
// it does the job, but WC javascript breaks it anyway later.
function qwc_useAdminTermLibJoin($obj, $taxonomies=null, $args=null) {
	global $pagenow;
	//qtranxf_dbg_log('qwc_useAdminTermLibJoin: $pagenow='.$pagenow);
	//qtranxf_dbg_log('qwc_useAdminTermLibJoin: $obj:',$obj);
	//qtranxf_dbg_log('qwc_useAdminTermLibJoin: $taxonomies:',$taxonomies);
	//qtranxf_dbg_log('qwc_useAdminTermLibJoin: $args:',$args);
	switch($pagenow){
		case 'post.php':
			if($taxonomies){
				foreach($taxonomies as $t){
					if(strpos($t,'pa_')===0)
						return qtranxf_get_terms_joined($obj);
				}
			}
		default: return $obj;// is done in qtranxf_useAdminTermLibJoin
	}
}
//add_filter('get_term', 'qwc_useAdminTermLibJoin', 4, 2);
add_filter('get_terms', 'qwc_useAdminTermLibJoin', 4, 3);
*/

/**
 * Append the language to the link for changing the order status, so that mails are sent in the language the customer used during the order process
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
 * Append the language to ajax links on the order edit page, so that mails are sent in the language the customer used during the order process
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
 */
function qwc_admin_email_option( $val ) {
	global $q_config;
	global $pagenow;
	//qtranxf_dbg_log('qwc_admin_email_option('.$val.'): $pagenow: ',$pagenow);
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
//add_filter('option_woocommerce_email_from_address', 'qwc_admin_email_option');//not yet translatable

/**
 * This helps to use order's language on re-sent emails from post.php order edit page.
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
			{
				//translate column 'product_cat'
				if ( isset( $_SERVER['QUERY_STRING'] )
				     && strpos( $_SERVER['QUERY_STRING'], 'post_type=product' ) !== false
				) {
					add_filter( 'get_term', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 6 );
				}
			}
			break;
	}
}

qwc_admin_filters();
