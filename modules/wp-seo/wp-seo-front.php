<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function qtranxf_wpseo_add_filters_front() {
    $use_filters = array(
        'wpseo_opengraph_title' => 20,
    );

    foreach ( $use_filters as $name => $priority ) {
        add_filter( $name, 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', $priority );
    }
}

// TODO: trigger this with a proper action - i18_front_config can't be used in modules, here it's too late!
qtranxf_wpseo_add_filters_front();
