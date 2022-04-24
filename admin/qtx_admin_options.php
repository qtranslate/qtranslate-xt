<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once( QTRANSLATE_DIR . '/admin/qtx_admin_utils.php' );
require_once( QTRANSLATE_DIR . '/modules/qtx_admin_module.php' );

function qtranxf_admin_set_default_options( &$options ) {
    // options processed in a standardized way
    $options['admin'] = array();

    $options['admin']['int'] = array(
        'editor_mode'    => QTX_EDITOR_MODE_LSB,
        'highlight_mode' => QTX_HIGHLIGHT_MODE_BORDER_LEFT,
    );

    $options['admin']['bool'] = array(
        'auto_update_mo'        => true, // automatically update .mo files
        'hide_lsb_copy_content' => false
    );

    // single line options
    $options['admin']['str'] = array(
        'lsb_style' => 'Simple_Buttons.css'
    );

    // multi-line options
    $options['admin']['text'] = array(
        'highlight_mode_custom_css' => null, // qtranxf_get_admin_highlight_css
    );

    $options['admin']['array'] = array(
        'config_files'         => array( './i18n-config.json' ),
        'admin_config'         => array(),
        'custom_i18n_config'   => array(),
        'custom_fields'        => array(),
        'custom_field_classes' => array(),
        'post_type_excluded'   => array(),
    );

    // Boolean set defining the default enabled options for each module, hard values not depending on any state.
    $options['admin']['admin_enabled_modules'] = array();
    foreach ( QTX_Admin_Module::get_modules() as $module ) {
        $options['admin']['admin_enabled_modules'][ $module->id ] = $module->is_default_enabled();
    }

    // options processed in a special way
    $options = apply_filters( 'qtranslate_option_config_admin', $options );
}

function qtranxf_admin_loadConfig() {
    _deprecated_function( __FUNCTION__, '3.10.0', 'qtranxf_admin_load_config' );
    qtranxf_admin_load_config();
}

function qtranxf_admin_load_config() {
    global $q_config, $qtranslate_options;
    qtranxf_admin_set_default_options( $qtranslate_options );

    foreach ( $qtranslate_options['admin']['int'] as $name => $default ) {
        qtranxf_load_option( $name, $default );
    }

    foreach ( $qtranslate_options['admin']['bool'] as $name => $default ) {
        qtranxf_load_option_bool( $name, $default );
    }

    foreach ( $qtranslate_options['admin']['str'] as $name => $default ) {
        qtranxf_load_option( $name, $default );
    }

    foreach ( $qtranslate_options['admin']['text'] as $name => $default ) {
        qtranxf_load_option( $name, $default );
    }

    foreach ( $qtranslate_options['admin']['array'] as $name => $default ) {
        qtranxf_load_option_array( $name, $default );
    }

    qtranxf_load_option_array( 'admin_enabled_modules', $qtranslate_options['admin']['admin_enabled_modules'] );

    if ( empty( $q_config['admin_config'] ) ) {
        require_once( QTRANSLATE_DIR . '/admin/qtx_admin_options_update.php' );
        qtranxf_update_i18n_config();
    }

    // opportunity to load additional admin features
    do_action( 'qtranslate_admin_load_config' );
    do_action_deprecated( 'qtranslate_admin_loadConfig', array(), '3.10.0', 'qtranslate_admin_load_config' );

    qtranxf_add_conf_filters();
}
