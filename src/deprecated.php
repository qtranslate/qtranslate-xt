<?php

/**
 * @deprecated Legacy hook for `init` action, to be removed in next major release.
 * Might be wrongly used by 3rd-party plugins (for example, alo_easymail) to test qTranslate-XT presence.
 * Recommended usage: is_plugin_active( 'qtranslate-xt/qtranslate.php' )
 * @since 3.4
 */
function qtranxf_init() {
    _deprecated_function( __FUNCTION__, '3.14.0' );
}

function qtranxf_admin_loadConfig() {
    _deprecated_function( __FUNCTION__, '3.10.0', 'qtranxf_admin_load_config' );
    qtranxf_admin_load_config();
}

// TODO: this function is not used, remove it?
function qtranxf_is_multilingual_deep( $value ) {
    _deprecated_function( __FUNCTION__, '3.14.0' );
    if ( is_string( $value ) ) {
        return qtranxf_isMultilingual( $value );
    } elseif ( is_array( $value ) ) {
        foreach ( $value as $item ) {
            if ( qtranxf_is_multilingual_deep( $item ) ) {
                return true;
            }
        }
    } elseif ( is_object( $value ) || $value instanceof __PHP_Incomplete_Class ) {
        foreach ( get_object_vars( $value ) as $item ) {
            if ( qtranxf_is_multilingual_deep( $item ) ) {
                return true;
            }
        }
    }

    return false;
}

function qtranxf_getLanguageEdit() {
    _deprecated_function( __FUNCTION__, '3.10.0', 'qtranxf_get_edit_language' );
    qtranxf_get_edit_language();
}

function qtranxf_fetch_file_selection( $dir, $suffix = '.css' ) {
    _deprecated_function( __FUNCTION__, '3.14.0' );
    $files      = array();
    $dir_handle = @opendir( $dir );
    if ( ! $dir_handle ) {
        return false;
    }
    while ( false !== ( $file = readdir( $dir_handle ) ) ) {
        if ( ! qtranxf_endsWith( $file, $suffix ) ) {
            continue;
        }
        $name = basename( $file, $suffix );
        if ( ! $name ) {
            continue;
        }
        $name = str_replace( '_', ' ', $name );
        if ( qtranxf_endsWith( $name, '.min' ) ) {
            $name           = substr( $name, -4 );
            $files[ $name ] = $file;
        } elseif ( ! isset( $files[ $name ] ) ) {
            $files[ $name ] = $file;
        }
    }
    ksort( $files );

    return $files;
}

/* @since 3.3.8.7 use filter 'admin_title' instead
 * function qtranxf_filter_options_general($value){
 * global $q_config;
 * global $pagenow;
 * switch($pagenow){
 * case 'options-general.php':
 * case 'customize.php'://there is more work to do for this case
 * return $value;
 * default: break;
 * }
 * $lang = $q_config['language'];
 * return qtranxf_use_language($lang,$value,false,false);
 * }
 * add_filter('option_blogname', 'qtranxf_filter_options_general');
 * add_filter('option_blogdescription', 'qtranxf_filter_options_general');
 */

function qtranxf_updateGettextDatabases( $force = false, $only_for_language = '' ) {
    _deprecated_function( __FUNCTION__, '3.10.0', 'qtranxf_update_gettext_databases' );
    qtranxf_update_gettext_databases( $force, $only_for_language );
}

function qtranxf_json_encode( $o ) {
    _deprecated_function( __FUNCTION__, '3.10.0' );

    return json_encode( $o, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
}

/**
 * @since 3.4
 * return reference to $page_config['forms'][$name]['fields']
 */
function qtranxf_config_add_form( &$page_config, $name ) {
    _deprecated_function( __FUNCTION__, '3.10.0' );
    if ( ! isset( $page_config['forms'][ $name ] ) ) {
        $page_config['forms'][ $name ] = array( 'fields' => array() );
    } elseif ( ! isset( $page_config['forms'][ $name ]['fields'] ) ) {
        $page_config['forms'][ $name ]['fields'] = array();
    }
}

function qtranxf_updateGettextDatabasesEx( $force = false, $only_for_language = '' ) {
    _deprecated_function( __FUNCTION__, '3.10.0', 'qtranxf_update_gettext_databases_ex' );
    qtranxf_update_gettext_databases_ex( $force, $only_for_language );
}

function qtranxf_add_admin_css() {
    _deprecated_function( __FUNCTION__, '3.14.0', 'qtranxf_admin_enqueue_scripts' );
}

function qtranxf_admin_head() {
    _deprecated_function( __FUNCTION__, '3.10.0', 'qtranxf_admin_enqueue_scripts' );
}

function qtranxf_editConfig() {
    _deprecated_function( __FUNCTION__, '3.10.0', 'qtranxf_edit_config' );
    qtranxf_edit_config();
}

function qtranxf_resetConfig() {
    _deprecated_function( __FUNCTION__, '3.10.0', 'qtranxf_admin_reset_config' );
    qtranxf_reset_config();
}

function qtranxf_saveConfig() {
    _deprecated_function( __FUNCTION__, '3.10.0', 'qtranxf_save_config' );
    qtranxf_save_config();
}

function qtranxf_reloadConfig() {
    _deprecated_function( __FUNCTION__, '3.10.0', 'qtranxf_reload_config' );
    qtranxf_reload_config();
}

function qtranxf_updateSetting( $var, $type = QTX_STRING, $def = null ) {
    _deprecated_function( __FUNCTION__, '3.10.0', 'qtranxf_update_setting' );
    qtranxf_update_setting( $var, $type, $def );
}

function qtranxf_updateSettingFlagLocation( $name ) {
    _deprecated_function( __FUNCTION__, '3.10.0', 'qtranxf_update_setting_flag_location' );
    qtranxf_update_setting_flag_location( $name );
}

function qtranxf_updateSettingIgnoreFileTypes( $name ) {
    _deprecated_function( __FUNCTION__, '3.10.0', 'qtranxf_update_setting_ignore_file_types' );
    qtranxf_update_setting_ignore_file_types( $name );
}

function qtranxf_updateSettings() {
    _deprecated_function( __FUNCTION__, '3.10.0', 'qtranxf_update_settings' );
    qtranxf_update_settings();
}

/**
 * [Legacy] Converter of a format given in DateTime format, transformed to the extended "QTX-strftime" format.
 *
 * @param string $format in DateTime format.
 *
 * @return string
 * @deprecated Use qtranxf_convert_date_format_to_strftime_format.
 */
function qtranxf_convertDateFormatToStrftimeFormat( $format ) {
    _deprecated_function( __FUNCTION__, '3.13.0', 'qtranxf_convert_date_format_to_strftime_format' );

    return qtranxf_convert_date_format_to_strftime_format( $format );
}

/**
 * [Legacy] Converter of a format/default pair to "QTX-strftime" format, applying 'use_strftime' configuration.
 *
 * @param string $format ATTENTION - always given in date PHP format.
 * @param string $default_format , following the strftime configuration.
 *
 * @return string
 * @deprecated Use qtranxf_convert_to_strftime_format_using_config.
 */
function qtranxf_convertFormat( $format, $default_format ) {
    _deprecated_function( __FUNCTION__, '3.13.0', 'qtranxf_convert_to_strftime_format_using_config' );

    return qtranxf_convert_to_strftime_format_using_config( $format, $default_format );
}

/**
 * [Legacy] Converter of a date format to "QTX-strftime" format, applying qTranslate 'use_strftime' configuration.
 *
 * @param string $format
 *
 * @return string
 * @deprecated Use qtranxf_get_language_date_or_time_format.
 */
function qtranxf_convertDateFormat( $format ) {
    _deprecated_function( __FUNCTION__, '3.13.0', 'qtranxf_get_language_date_or_time_format' );
    $default_format = qtranxf_get_language_date_or_time_format( 'date_format' );

    return qtranxf_convertFormat( $format, $default_format );
}

/**
 * [Legacy] Converter of a time format to "QTX-strftime" format, applying qTranslate 'use_strftime' configuration.
 *
 * @param string $format
 *
 * @return string
 * @deprecated Use qtranxf_get_language_date_or_time_format.
 */
function qtranxf_convertTimeFormat( $format ) {
    _deprecated_function( __FUNCTION__, '3.13.0', 'qtranxf_get_language_date_or_time_format' );
    $default_format = qtranxf_get_language_date_or_time_format( 'time_format' );

    return qtranxf_convertFormat( $format, $default_format );
}

/**
 * [Legacy] Extension of PHP "QTX-strftime", valid up to PHP 8.0.
 *
 * @param string $format extended strftime with additional features such as %q
 * @param int $date timestamp
 * @param string $default Default result when $format is empty.
 * @param string $before Text copied before result.
 * @param string $after Text copied after result.
 *
 * @return mixed|string
 * @deprecated Use qxtranxf_intl_strftime, since strftime is deprecated from PHP8.1.
 * @See https://www.php.net/manual/en/function.strftime.php
 */
function qtranxf_strftime( $format, $date, $default = '', $before = '', $after = '' ) {
    _deprecated_function( __FUNCTION__, '3.13.0', 'qxtranxf_intl_strftime' );

    if ( empty( $format ) ) {
        return $default;
    }

    // add date suffix ability (%q) to strftime
    $day     = intval( ltrim( strftime( "%d", $date ), '0' ) );
    $search  = array();
    $replace = array();

    // date S
    $search[] = '/(([^%])%q|^%q)/';
    if ( $day == 1 || $day == 21 || $day == 31 ) {
        $replace[] = '$2st';
    } elseif ( $day == 2 || $day == 22 ) {
        $replace[] = '$2nd';
    } elseif ( $day == 3 || $day == 23 ) {
        $replace[] = '$2rd';
    } else {
        $replace[] = '$2th';
    }

    $search[]  = '/(([^%])%E|^%E)/';
    $replace[] = '${2}' . $day; // date j
    $search[]  = '/(([^%])%f|^%f)/';
    $replace[] = '${2}' . date( 'w', $date ); // date w
    $search[]  = '/(([^%])%F|^%F)/';
    $replace[] = '${2}' . date( 'z', $date ); // date z
    $search[]  = '/(([^%])%i|^%i)/';
    $replace[] = '${2}' . date( 'n', $date ); // date n
    $search[]  = '/(([^%])%J|^%J)/';
    $replace[] = '${2}' . date( 't', $date ); // date t
    $search[]  = '/(([^%])%k|^%k)/';
    $replace[] = '${2}' . date( 'L', $date ); // date L
    $search[]  = '/(([^%])%K|^%K)/';
    $replace[] = '${2}' . date( 'B', $date ); // date B
    $search[]  = '/(([^%])%l|^%l)/';
    $replace[] = '${2}' . date( 'g', $date ); // date g
    $search[]  = '/(([^%])%L|^%L)/';
    $replace[] = '${2}' . date( 'G', $date ); // date G
    $search[]  = '/(([^%])%N|^%N)/';
    $replace[] = '${2}' . date( 'u', $date ); // date u
    $search[]  = '/(([^%])%Q|^%Q)/';
    $replace[] = '${2}' . date( 'e', $date ); // date e
    $search[]  = '/(([^%])%o|^%o)/';
    $replace[] = '${2}' . date( 'I', $date ); // date I
    $search[]  = '/(([^%])%O|^%O)/';
    $replace[] = '${2}' . date( 'O', $date ); // date O
    $search[]  = '/(([^%])%s|^%s)/';
    $replace[] = '${2}' . date( 'P', $date ); // date P
    $search[]  = '/(([^%])%v|^%v)/';
    $replace[] = '${2}' . date( 'T', $date ); // date T
    $search[]  = '/(([^%])%1|^%1)/';
    $replace[] = '${2}' . date( 'Z', $date ); // date Z
    $search[]  = '/(([^%])%2|^%2)/';
    $replace[] = '${2}' . date( 'c', $date ); // date c
    $search[]  = '/(([^%])%3|^%3)/';
    $replace[] = '${2}' . date( 'r', $date ); // date r
    $search[]  = '/(([^%])%4|^%4)/';
    $replace[] = '${2}' . $date; // date U
    $format    = preg_replace( $search, $replace, $format );

    return $before . strftime( $format, $date ) . $after;
}

function qtranxf_validateBool( $var, $default_value ) {
    _deprecated_function( __FUNCTION__, '3.13.0' );
    if ( $var === '0' ) {
        return false;
    } elseif ( $var === '1' ) {
        return true;
    } else {
        return $default_value;
    }
}

function qtranxf_loadConfig() {
    _deprecated_function( __FUNCTION__, '3.10.0', 'qtranxf_load_config' );
    qtranxf_load_config();
}