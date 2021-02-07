<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function qtranxf_wpseo_add_filters_front() {
    # For reference: https://developer.yoast.com/customization/apis/metadata-api/
    $use_filters = array(
        # Generic presenters
        'wpseo_metadesc'            => 20,
        'wpseo_title'               => 20,
        # Twitter presenters
        'wpseo_twitter_description' => 20,
        'wpseo_twitter_title'       => 20,
        # OpenGraph presenters
        'wpseo_opengraph_desc'      => 20,
        'wpseo_opengraph_title'     => 20,
    );

    foreach ( $use_filters as $name => $priority ) {
        add_filter( $name, 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', $priority );
    }
}

// TODO: trigger this with a proper hook - i18n_front_config can't be used in modules, here it's too late!
qtranxf_wpseo_add_filters_front();
