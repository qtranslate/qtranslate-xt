<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

//add_action('admin_enqueue_scripts','qeme_enqueue_scripts',11);
//function qeme_enqueue_scripts()
//{
//	remove_action('admin_head', 'eme_admin_map_script');//for now, not sure if it is needed, but it was breaking pages
//}

add_filter( 'qtranslate_load_admin_page_config', 'qtranxf_eme_add_admin_page_config' );
function qtranxf_eme_add_admin_page_config( $page_configs ) {
    {//admin.php?page=eme-manager
        $page_config = array();

        /**
         * ['pages'] tested against $pagenow & $_SERVER['QUERY_STRING'] like preg_match('!'.$page.'!',$pagenow)
         * to enable use of regular expressions to identify pages, where fields need to become translatable.
         */
        $page_config['pages'] = array( 'admin.php' => 'page=eme-manager&eme_admin_action=edit_event|page=eme-manager&eme_admin_action=edit_recurrence|page=eme-new_event' );

        $page_config['anchors'] = array(
            'titlediv',
            'div_event_notes'
        );//id of elements, at front of which the Language Switching Buttons are placed

        $page_config['forms'] = array();

        $f         = array();
        $f['form'] = array( 'id' => 'eventForm' );//identify the form which fields described below belong to

        /**
         * List of all translatable fields within the form on the page.
         * Possible attributes for a field: 'tag', 'id', 'class', 'name', 'encode'
         * No need to specify all possible attributes, but enough to define the field uniquely.
         */
        $f['fields'] = array();
        $fields      = &$f['fields']; // shortcut

        /**
         * 'encode' here is excessive, since its value coincides with the default,
         * but it does not hurt to show it for the clarity.
         * (obsolete) '<' means to encode the multilingual field with <!--:--> kind of language tags (default for textarea).
         * (obsolete) '[' means to encode the multilingual field with [:] kind of language tags (default for all input fields except textarea).
         * 'display' means that the filed is used to display multilingual value in its innerHTML or attributes.
         */
        $fields[] = array( 'id' => 'title', 'encode' => '[' );

        /**
         * 'encode' is also excessive here, the default for textarea is '<'
         */
        $fields[] = array( 'id' => 'content', 'encode' => '<' );

        $fields[] = array( 'id' => 'event_page_title_format' );// 'encode' by default, will be '<'
        $fields[] = array( 'id' => 'event_single_event_format' );
        $fields[] = array( 'id' => 'event_registration_cancelled_email_body' );
        $fields[] = array( 'id' => 'event_registration_denied_email_body' );
        $fields[] = array( 'id' => 'event_contactperson_email_body' );
        $fields[] = array( 'id' => 'event_registration_recorded_ok_html' );
        $fields[] = array( 'id' => 'event_registration_updated_email_body' );
        $fields[] = array( 'id' => 'event_cancel_form_format' );
        $fields[] = array( 'id' => 'event_registration_form_format' );
        $fields[] = array( 'id' => 'event_respondent_email_body' );
        $fields[] = array( 'id' => 'event_registration_pending_email_body' );
        //and so on for fields

        $page_config['forms'][] = $f;
        //and so on for forms

        $page_configs[] = $page_config;
    }

    {// for locations
        $page_config            = array();
        $page_config['pages']   = array( 'admin.php' => 'page=eme-locations' );
        $page_config['anchors'] = array(
            'titlediv',
            'loc_description'
        ); //id of elements, at front of which the Language Switching Buttons are placed
        $page_config['forms']   = array();
        $f                      = array();
        $f['form']              = array( 'id' => 'editloc' ); //identify the form which fields described below belong to
        $f['fields']            = array();
        $fields                 = &$f['fields']; // shortcut
        $fields[]               = array( 'id' => 'title' );
        $fields[]               = array( 'id' => 'content' );
        $fields[]               = array( 'id' => 'location_address' );
        $fields[]               = array( 'id' => 'location_town' );
        $page_config['forms'][] = $f;
        $page_configs[]         = $page_config;
    }

    {// for categories
        $page_config            = array();
        $page_config['pages']   = array( 'admin.php' => 'page=eme-categories&eme_admin_action=edit_category' );
        $page_config['anchors'] = array( 'category_name' ); //id of elements, at front of which the Language Switching Buttons are placed
        $page_config['forms']   = array();
        $f                      = array();
        $f['form']              = array( 'id' => 'edit_category' ); //identify the form which fields described below belong to
        $f['fields']            = array();
        $fields                 = &$f['fields']; // shortcut
        $fields[]               = array( 'id' => 'category_name' );
        $page_config['forms'][] = $f;
        $page_configs[]         = $page_config;
    }

    {// for templates
        $page_config            = array();
        $page_config['pages']   = array( 'admin.php' => 'page=eme-templates' );
        $page_config['anchors'] = array( 'description' ); //id of elements, at front of which the Language Switching Buttons are placed
        $page_config['forms']   = array();
        $f                      = array();
        $f['form']              = array( 'id' => 'edit_template' ); //identify the form which fields described below belong to
        $f['fields']            = array();
        $fields                 = &$f['fields']; // shortcut
        $fields[]               = array( 'id' => 'description' );
        $fields[]               = array( 'id' => 'template_format' );
        $page_config['forms'][] = $f;
        $page_configs[]         = $page_config;
    }

    {// for formfields
        $page_config            = array();
        $page_config['pages']   = array( 'admin.php' => 'page=eme-formfields&eme_admin_action=edit_formfield' );
        $page_config['anchors'] = array( 'field_name' ); //id of elements, at front of which the Language Switching Buttons are placed
        $page_config['forms']   = array();
        $f                      = array();
        $f['form']              = array( 'id' => 'edit_formfield' ); //identify the form which fields described below belong to
        $f['fields']            = array();
        $fields                 = &$f['fields']; // shortcut
        $fields[]               = array( 'id' => 'field_name' );
        $fields[]               = array( 'id' => 'field_info' );
        $fields[]               = array( 'id' => 'field_tags' );
        $page_config['forms'][] = $f;
        $page_configs[]         = $page_config;
    }

    {// for mails
        $page_config            = array();
        $page_config['pages']   = array( 'admin.php' => 'page=eme-send-mails' );
        $page_config['anchors'] = array( 'subject' ); //id of elements, at front of which the Language Switching Buttons are placed
        $page_config['forms']   = array();
        $f                      = array();
        $f['form']              = array( 'id' => 'send_mail' ); //identify the form which fields described below belong to
        $f['fields']            = array();
        $fields                 = &$f['fields']; // shortcut
        $fields[]               = array( 'id' => 'mail_subject' );
        $fields[]               = array( 'id' => 'mail_message' );
        $page_config['forms'][] = $f;
        $page_configs[]         = $page_config;
    }

    {
        $page_config            = array();
        $page_config['pages']   = array( 'admin.php' => 'page=eme-options&tab=events' );
        $page_config['anchors'] = array(); //id of elements, at front of which the Language Switching Buttons are placed
        $page_config['forms']   = array();
        $f                      = array();
        $f['form']              = array( 'id' => 'eme_options_form' ); //identify the form which fields described below belong to
        $f['fields']            = array();
        $fields                 = &$f['fields']; // shortcut
        $fields[]               = array( 'id' => 'eme_event_list_item_format_header' );
        $fields[]               = array( 'id' => 'eme_cat_event_list_item_format_header' );
        $fields[]               = array( 'id' => 'eme_event_list_item_format' );
        $fields[]               = array( 'id' => 'eme_event_list_item_format_footer' );
        $fields[]               = array( 'id' => 'eme_cat_event_list_item_format_footer' );
        $fields[]               = array( 'id' => 'eme_event_page_title_format' );
        $fields[]               = array( 'id' => 'eme_event_html_title_format' );
        $fields[]               = array( 'id' => 'eme_single_event_format' );
        $fields[]               = array( 'id' => 'eme_events_page_title' );
        $fields[]               = array( 'id' => 'eme_no_events_message' );
        $fields[]               = array( 'id' => 'eme_filter_form_format' );
        $page_config['forms'][] = $f;
        $page_configs[]         = $page_config;
    }
    {
        $page_config            = array();
        $page_config['pages']   = array( 'admin.php' => 'page=eme-options&tab=locations' );
        $page_config['anchors'] = array( '' ); //id of elements, at front of which the Language Switching Buttons are placed
        $page_config['forms']   = array();
        $f                      = array();
        $f['form']              = array( 'id' => 'eme_options_form' ); //identify the form which fields described below belong to
        $f['fields']            = array();
        $fields                 = &$f['fields']; // shortcut
        $fields[]               = array( 'id' => 'eme_location_list_format_header' );
        $fields[]               = array( 'id' => 'eme_location_list_format_item' );
        $fields[]               = array( 'id' => 'eme_location_list_format_footer' );
        $fields[]               = array( 'id' => 'eme_location_page_title_format' );
        $fields[]               = array( 'id' => 'eme_location_html_title_format' );
        $fields[]               = array( 'id' => 'eme_single_location_format' );
        $fields[]               = array( 'id' => 'eme_location_baloon_format' );
        $fields[]               = array( 'id' => 'eme_location_event_list_item_format' );
        $fields[]               = array( 'id' => 'eme_location_no_events_message' );
        $page_config['forms'][] = $f;
        $page_configs[]         = $page_config;
    }
    {
        $page_config            = array();
        $page_config['pages']   = array( 'admin.php' => 'page=eme-options&tab=calendar' );
        $page_config['anchors'] = array( '' ); //id of elements, at front of which the Language Switching Buttons are placed
        $page_config['forms']   = array();
        $f                      = array();
        $f['form']              = array( 'id' => 'eme_options_form' ); //identify the form which fields described below belong to
        $f['fields']            = array();
        $fields                 = &$f['fields']; // shortcut
        $fields[]               = array( 'id' => 'eme_small_calendar_event_title_format' );
        $fields[]               = array( 'id' => 'eme_full_calendar_event_format' );
        $page_config['forms'][] = $f;
        $page_configs[]         = $page_config;
    }
    {
        $page_config            = array();
        $page_config['pages']   = array( 'admin.php' => 'page=eme-options&tab=rss' );
        $page_config['anchors'] = array( '' ); //id of elements, at front of which the Language Switching Buttons are placed
        $page_config['forms']   = array();
        $f                      = array();
        $f['form']              = array( 'id' => 'eme_options_form' ); //identify the form which fields described below belong to
        $f['fields']            = array();
        $fields                 = &$f['fields']; // shortcut
        $fields[]               = array( 'id' => 'eme_rss_main_title' );
        $fields[]               = array( 'id' => 'eme_rss_main_description' );
        $fields[]               = array( 'id' => 'eme_rss_title_format' );
        $fields[]               = array( 'id' => 'eme_rss_description_format' );
        $fields[]               = array( 'id' => 'eme_ical_title_format' );
        $fields[]               = array( 'id' => 'eme_ical_description_format' );
        $page_config['forms'][] = $f;
        $page_configs[]         = $page_config;
    }
    {
        $page_config            = array();
        $page_config['pages']   = array( 'admin.php' => 'page=eme-options&tab=rsvp' );
        $page_config['anchors'] = array( '' ); //id of elements, at front of which the Language Switching Buttons are placed
        $page_config['forms']   = array();
        $f                      = array();
        $f['form']              = array( 'id' => 'eme_options_form' ); //identify the form which fields described below belong to
        $f['fields']            = array();
        $fields                 = &$f['fields']; // shortcut
        $fields[]               = array( 'id' => 'eme_rsvp_addbooking_submit_string' );
        $fields[]               = array( 'id' => 'eme_rsvp_delbooking_submit_string' );
        $fields[]               = array( 'id' => 'eme_attendees_list_format' );
        $fields[]               = array( 'id' => 'eme_bookings_list_header_format' );
        $fields[]               = array( 'id' => 'eme_bookings_list_format' );
        $fields[]               = array( 'id' => 'eme_bookings_list_footer_format' );
        $fields[]               = array( 'id' => 'eme_registration_recorded_ok_html' );
        $fields[]               = array( 'id' => 'eme_registration_form_format' );
        $fields[]               = array( 'id' => 'eme_cancel_form_format' );
        $page_config['forms'][] = $f;
        $page_configs[]         = $page_config;
    }
    {
        $page_config            = array();
        $page_config['pages']   = array( 'admin.php' => 'page=eme-options&tab=mailtemplates' );
        $page_config['anchors'] = array( '' ); //id of elements, at front of which the Language Switching Buttons are placed
        $page_config['forms']   = array();
        $f                      = array();
        $f['form']              = array( 'id' => 'eme_options_form' ); //identify the form which fields described below belong to
        $f['fields']            = array();
        $fields                 = &$f['fields']; // shortcut
        $fields[]               = array( 'id' => 'eme_contactperson_email_subject' );
        $fields[]               = array( 'id' => 'eme_contactperson_email_body' );
        $fields[]               = array( 'id' => 'eme_contactperson_cancelled_email_subject' );
        $fields[]               = array( 'id' => 'eme_contactperson_cancelled_email_body' );
        $fields[]               = array( 'id' => 'eme_contactperson_pending_email_subject' );
        $fields[]               = array( 'id' => 'eme_contactperson_pending_email_body' );
        $fields[]               = array( 'id' => 'eme_respondent_email_subject' );
        $fields[]               = array( 'id' => 'eme_respondent_email_body' );
        $fields[]               = array( 'id' => 'eme_registration_pending_email_subject' );
        $fields[]               = array( 'id' => 'eme_registration_pending_email_body' );
        $fields[]               = array( 'id' => 'eme_registration_cancelled_email_subject' );
        $fields[]               = array( 'id' => 'eme_registration_cancelled_email_body' );
        $fields[]               = array( 'id' => 'eme_registration_denied_email_subject' );
        $fields[]               = array( 'id' => 'eme_registration_denied_email_body' );
        $fields[]               = array( 'id' => 'eme_registration_updated_email_subject' );
        $fields[]               = array( 'id' => 'eme_registration_updated_email_body' );
        $page_config['forms'][] = $f;
        $page_configs[]         = $page_config;
    }
    {
        $page_config            = array();
        $page_config['pages']   = array( 'admin.php' => 'page=eme-options&tab=payments' );
        $page_config['anchors'] = array( '' ); //id of elements, at front of which the Language Switching Buttons are placed
        $page_config['forms']   = array();
        $f                      = array();
        $f['form']              = array( 'id' => 'eme_options_form' ); //identify the form which fields described below belong to
        $f['fields']            = array();
        $fields                 = &$f['fields']; // shortcut
        $fields[]               = array( 'id' => 'eme_payment_form_header_format' );
        $fields[]               = array( 'id' => 'eme_payment_form_footer_format' );
        $fields[]               = array( 'id' => 'eme_multipayment_form_header_format' );
        $fields[]               = array( 'id' => 'eme_multipayment_form_footer_format' );
        $fields[]               = array( 'id' => 'eme_payment_succes_format' );
        $fields[]               = array( 'id' => 'eme_payment_fail_format' );
        $fields[]               = array( 'id' => 'eme_offline_payment' );
        $fields[]               = array( 'id' => 'eme_paypal_button_label' );
        $fields[]               = array( 'id' => 'eme_paypal_button_label' );
        $fields[]               = array( 'id' => 'eme_paypal_button_above' );
        $fields[]               = array( 'id' => 'eme_paypal_button_below' );
        $fields[]               = array( 'id' => 'eme_2co_button_label' );
        $fields[]               = array( 'id' => 'eme_2co_button_above' );
        $fields[]               = array( 'id' => 'eme_2co_button_below' );
        $fields[]               = array( 'id' => 'eme_webmoney_button_label' );
        $fields[]               = array( 'id' => 'eme_webmoney_button_above' );
        $fields[]               = array( 'id' => 'eme_webmoney_button_below' );
        $fields[]               = array( 'id' => 'eme_fdgg_button_label' );
        $fields[]               = array( 'id' => 'eme_fdgg_button_above' );
        $fields[]               = array( 'id' => 'eme_fdgg_button_below' );
        $fields[]               = array( 'id' => 'eme_mollie_button_label' );
        $fields[]               = array( 'id' => 'eme_mollie_button_above' );
        $fields[]               = array( 'id' => 'eme_mollie_button_below' );
        $fields[]               = array( 'id' => 'eme_sagepay_button_label' );
        $fields[]               = array( 'id' => 'eme_sagepay_button_above' );
        $fields[]               = array( 'id' => 'eme_sagepay_button_below' );
        $fields[]               = array( 'id' => 'eme_worldpay_button_label' );
        $fields[]               = array( 'id' => 'eme_worldpay_button_above' );
        $fields[]               = array( 'id' => 'eme_worldpay_button_below' );
        $fields[]               = array( 'id' => 'eme_stripe_button_label' );
        $fields[]               = array( 'id' => 'eme_stripe_button_above' );
        $fields[]               = array( 'id' => 'eme_stripe_button_below' );
        $fields[]               = array( 'id' => 'eme_braintree_button_label' );
        $fields[]               = array( 'id' => 'eme_braintree_button_above' );
        $fields[]               = array( 'id' => 'eme_braintree_button_below' );
        $page_config['forms'][] = $f;
        $page_configs[]         = $page_config;
    }

    return $page_configs;
}
