<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once QTRANSLATE_DIR . '/src/admin/admin_utils.php';
require_once QTRANSLATE_DIR . '/src/modules/admin_module.php';

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
        'lsb_style' => 'simple-buttons.css'
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
        require_once QTRANSLATE_DIR . '/src/admin/admin_options_update.php';
        qtranxf_update_i18n_config();
    }

    // TODO in future versions, remove temporary conversion for legacy CSS LSB values before 3.13.1
    $q_config['lsb_style'] = str_replace( '_', '-', strtolower( $q_config['lsb_style'] ) );

    // opportunity to load additional admin features
    do_action( 'qtranslate_admin_load_config' );
    do_action_deprecated( 'qtranslate_admin_loadConfig', array(), '3.10.0', 'qtranslate_admin_load_config' );

    qtranxf_add_conf_filters();
}

/**
 * Import legacy option by recopying its value if the new option doesn't already exist.
 *
 * @param string $old_name
 * @param string $new_name
 * @param bool|string $autoload as in update_option
 *
 * @return void
 */
function qtranxf_import_legacy_option( $old_name, $new_name, $autoload = null ) {
    assert( strpos( $new_name, 'qtranslate_' ) === 0 );
    if ( ! get_option( $new_name ) ) {
        $old_value = get_option( $old_name );
        if ( $old_value ) {
            update_option( $new_name, $old_value, $autoload );
        }
    }
}

/**
 * Rename a legacy option: import and cleanup.
 * Only applies to `qtranslate` options, preserve external plugin options.
 *
 * @param string $old_name
 * @param string $new_name
 * @param bool|string $autoload as in update_option
 *
 * @return void
 */
function qtranxf_rename_legacy_option( $old_name, $new_name, $autoload = null ) {
    assert( strpos( $new_name, 'qtranslate_' ) === 0 );
    assert( strpos( $old_name, 'qtranslate_' ) === 0 );
    qtranxf_import_legacy_option( $old_name, $new_name, $autoload );
    delete_option( $old_name );
}
