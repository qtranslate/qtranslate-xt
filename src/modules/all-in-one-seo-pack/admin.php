<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'qtranslate_admin_config', 'qtranxf_aioseop_add_admin_page_config' );
function qtranxf_aioseop_add_admin_page_config( array $page_configs ): array {
    // post.php
    $page_config          = array();
    $page_config['pages'] = array( 'post.php' => '', 'term.php' => '' );
    //$page_config['anchors'] = array( 'titlediv' );

    $page_config['forms'] = array();

    $f         = array();
    $f['form'] = array( 'id' => 'post' );

    $f['fields'] = array();
    $fields      = &$f['fields']; // shorthand

    $fields[] = array( 'id' => 'aioseop_snippet_title', 'encode' => 'display' );

    $fields[] = array( 'tag' => 'input', 'name' => 'aiosp_title' );
    $fields[] = array( 'tag' => 'textarea', 'name' => 'aiosp_description' );
    $fields[] = array( 'tag' => 'input', 'name' => 'aiosp_keywords' );
    $fields[] = array( 'tag' => 'input', 'name' => 'aiosp_custom_link' );

    $page_config['forms'][] = $f;
    $page_configs[]         = $page_config;

    // admin.php?page=all-in-one-seo-pack/aioseop_class.php
    $page_config            = array();
    $page_config['pages']   = array( 'admin.php' => 'page=all-in-one-seo-pack/aioseop_class.php' );
    $page_config['anchors'] = array( 'aiosp_home_metabox' );

    $page_config['forms'] = array();

    $f         = array();
    $f['form'] = array( 'id' => 'aiosp_settings_form' );

    $f['fields'] = array();
    $fields      = &$f['fields']; // shorthand

    $fields[] = array( 'tag' => 'textarea', 'name' => 'aiosp_home_title' );
    $fields[] = array( 'tag' => 'textarea', 'name' => 'aiosp_home_description' );
    $fields[] = array( 'tag' => 'textarea', 'name' => 'aiosp_home_keywords' );

    $page_config['forms'][] = $f;
    $page_configs[]         = $page_config;

    return $page_configs;
}
