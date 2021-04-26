<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'qtranslate_admin_config', 'qtranxf_wpseo_load_admin_page_config' );
function qtranxf_wpseo_load_admin_page_config( $page_configs ) {
    assert( ! isset( $page_configs['yoast_wpseo'] ) );

    $page_configs['yoast_wpseo'] = array(
        'pages' => array( 'admin.php' => 'wpseo_titles' ),
        'forms' => array(
            array(
                'form'   => array( 'wpseo-conf' ),
                'fields' => array(
                    array( 'id' => 'company_name' ),
                )
            )
        )
    );

    return $page_configs;
}
