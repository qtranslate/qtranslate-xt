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

    add_filter( 'wpseo_schema_webpage', 'qtranxf_wpseo_webpage_schema', 10, 2 );
    function qtranxf_wpseo_webpage_schema( $piece, $context ) {
        if ( array_key_exists( 'description', $piece ) ) {
            $piece['description'] = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $piece['description'] );
        }
        if ( array_key_exists( 'name', $piece ) ) {
            $piece['name'] = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $piece['name'] );
        }

        return $piece;
    }

    add_filter( 'wpseo_breadcrumb_single_link_info', 'qtranxf_wpseo_breadcrumbs_link', 10, 3 );
    function qtranxf_wpseo_breadcrumbs_link( $link_info, $index, $crumbs ) {
        $link_info['text'] = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $link_info['text'] );

        return $link_info;
    }
}

// TODO: trigger this with a proper hook - qtranslate_front_config can't be used in modules, here it's too late!
qtranxf_wpseo_add_filters_front();
