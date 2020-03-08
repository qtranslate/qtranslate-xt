<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once( QTRANSLATE_DIR . '/admin/qtx_admin_modules.php' );

/**
 * Save language properties from configuration $cfg to database
 * @since 3.3
 */
function qtranxf_save_languages( $cfg ) {
    global $qtranslate_options;
    foreach ( $qtranslate_options['languages'] as $nm => $opn ) {
        if ( is_array( $cfg[ $nm ] ) ) {
            foreach ( $cfg[ $nm ] as $k => $v ) {
                if ( empty( $v ) ) {
                    unset( $cfg[ $nm ][ $k ] );
                }
            }
        }
        if ( empty( $cfg[ $nm ] ) ) {
            delete_option( $opn );
        } else {
            update_option( $opn, $cfg[ $nm ] );
        }
    }

    return $cfg;
}

/**
 * since 3.2.9.2
 */
function qtranxf_default_enabled_languages() {
    $locale = get_locale();
    if ( ! $locale ) {
        $locale = 'en_US';
    }
    $lang    = null;
    $locales = qtranxf_default_locale();
    foreach ( $locales as $ln => $lo ) {
        if ( $lo != $locale ) {
            continue;
        }
        $lang = $ln;
        break;
    }
    if ( ! $lang ) {
        $lang = substr( $locale, 0, 2 );
    }
    if ( ! qtranxf_language_predefined( $lang ) ) {
        $langs                           = array();
        $langs['language_name'][ $lang ] = 'Unknown';
        $langs['flag'][ $lang ]          = 'us.png';
        $langs['locale'][ $lang ]        = $locale;
        $langs['date_format'][ $lang ]   = '%A %B %e%q, %Y';
        $langs['time_format'][ $lang ]   = '%I:%M %p';
        $langs['not_available'][ $lang ] = 'Sorry, this entry is only available in %LANG:, : and %.';
        qtranxf_save_languages( $langs );
    }

    //qtranxf_dbg_log('qtranxf_default_enabled_languages: $lang='.$lang.' $locale:',$locale);
    return array( $lang, $lang != 'en' ? 'en' : 'de' );
}

/**
 * since 3.2.9.2
 */
function qtranxf_default_default_language() {
    global $q_config;
    $enabled_languages = qtranxf_default_enabled_languages();
    $default_language  = $enabled_languages[0];
    update_option( 'qtranslate_enabled_languages', $enabled_languages );
    update_option( 'qtranslate_default_language', $default_language );
    $q_config['language']          = $q_config['default_language'] = $default_language;
    $q_config['enabled_languages'] = $enabled_languages;

    return $default_language;
}

/**
 * @since 3.3.2
 */
function qtranxf_load_config_files( $json_files ) {
    $content_dir = null;
    $qtransx_dir = null;
    foreach ( $json_files as $k => $fnm ) {
        if ( file_exists( $fnm ) ) {
            continue;
        }
        $ffnm = null;
        if ( $fnm[0] == '.' && $fnm[1] == '/' ) {
            if ( ! $qtransx_dir ) {
                $qtransx_dir = QTRANSLATE_DIR;
            }
            $ffnm = $qtransx_dir . substr( $fnm, 1 );
        }
        if ( ! file_exists( $ffnm ) ) {
            if ( ! $content_dir ) {
                $content_dir = trailingslashit( WP_CONTENT_DIR );
            }
            $ffnm = $content_dir . $fnm;
        }
        if ( file_exists( $ffnm ) ) {
            $json_files[ $k ] = $ffnm;
        } else {
            qtranxf_error_log( sprintf( __( 'Could not find file "%s" listed in option "%s".', 'qtranslate' ), '<strong>' . $fnm . '</strong>', '<a href="' . admin_url( 'options-general.php?page=qtranslate-xt#integration' ) . '">' . __( 'Configuration Files', 'qtranslate' ) . '</a>' ) . ' ' . __( 'Please, either put file in place or update the option.', 'qtranslate' ) . ' ' . sprintf( __( 'Once the problem is fixed, re-save the configuration by pressing button "%s" on plugin %ssettings page%s.', 'qtranslate' ), __( 'Save Changes', 'qtranslate' ), '<a href="' . admin_url( 'options-general.php?page=qtranslate-xt#integration' ) . '">', '</a>' ) );
            unset( $json_files[ $k ] );
        }
    }

    $cfg_all = array();
    foreach ( $json_files as $fnm ) {
        $cfg_json = file_get_contents( $fnm );
        if ( $cfg_json ) {
            $cfg = json_decode( $cfg_json, true );
            if ( ! empty( $cfg ) && is_array( $cfg ) ) {
                $cfg_all = qtranxf_merge_config( $cfg_all, $cfg );
            } else {
                qtranxf_error_log( sprintf( __( 'Could not parse %s file "%s" listed in option "%s".', 'qtranslate' ), 'JSON', '<strong>' . $fnm . '</strong>', '<a href="' . admin_url( 'options-general.php?page=qtranslate-xt#integration' ) . '">' . __( 'Configuration Files', 'qtranslate' ) . '</a>' ) . ' ' . __( 'Please, correct the syntax error in the file.', 'qtranslate' ) . ' ' . sprintf( __( 'Once the problem is fixed, re-save the configuration by pressing button "%s" on plugin %ssettings page%s.', 'qtranslate' ), __( 'Save Changes', 'qtranslate' ), '<a href="' . admin_url( 'options-general.php?page=qtranslate-xt#integration' ) . '">', '</a>' ) );
            }
        } else {
            qtranxf_error_log( sprintf( __( 'Could not load file "%s" listed in option "%s".', 'qtranslate' ), '<strong>' . $fnm . '</strong>', '<a href="' . admin_url( 'options-general.php?page=qtranslate-xt#integration' ) . '">' . __( 'Configuration Files', 'qtranslate' ) . '</a>' ) . ' ' . __( 'Please, make sure the file is accessible and readable.', 'qtranslate' ) . ' ' . sprintf( __( 'Once the problem is fixed, re-save the configuration by pressing button "%s" on plugin %ssettings page%s.', 'qtranslate' ), __( 'Save Changes', 'qtranslate' ), '<a href="' . admin_url( 'options-general.php?page=qtranslate-xt#integration' ) . '">', '</a>' ) );
        }
    }
    if ( ! isset( $cfg_all['admin-config'] ) ) {
        $cfg_all['admin-config'] = array();
    }
    if ( ! isset( $cfg_all['front-config'] ) ) {
        $cfg_all['front-config'] = array();
    }

    return $cfg_all;
}

/**
 * @since 3.4
 */
function qtranxf_get_option_config_files() {
    $config_files_def = array( './i18n-config.json' );
    $config_files     = get_option( 'qtranslate_config_files', $config_files_def );
    if ( ! is_array( $config_files ) ) {
        $config_files = $config_files_def;
        delete_option( 'qtranslate_config_files' );
    }

    //qtranxf_dbg_log('qtranxf_get_option_config_files: $config_files: ', $config_files);
    return $config_files;
}

/**
 * @since 3.4
 */
function qtranxf_set_field_jquery( &$f ) {
    if ( isset( $f['jquery'] ) ) {
        return false;
    }
    if ( isset( $f['class'] ) ) {
        $jq = '.' . $f['class'];
        unset( $f['class'] );
    } else {
        $jq = '';
    }
    if ( isset( $f['tag'] ) ) {
        $jq = $f['tag'] . $jq;
        unset( $f['tag'] );
    }
    if ( isset( $f['name'] ) ) {
        $jq .= '[name="' . $f['name'] . '"]';
        unset( $f['name'] );
    }
    if ( empty( $jq ) ) {
        return false;
    }
    $f['jquery'] = $jq;

    return true;
}

/**
 * @since 3.4
 */
function qtranxf_standardize_config_fields( $fields ) {
    foreach ( $fields as $k => $f ) {
        if ( ! is_array( $f ) ) {
            continue;
        }
        if ( isset( $f['id'] ) ) {
            $id = $f['id'];
            unset( $f['id'] );
            $fields[ $id ] = $f;
            if ( $id !== $k ) {
                unset( $fields[ $k ] );
            }
        } else if ( qtranxf_set_field_jquery( $f ) ) {
            $fields[ $k ] = $f;
        }
    }

    return $fields;
}

/**
 * @since 3.4
 */
function qtranxf_standardize_config_anchor( &$anchor ) {
    if ( is_string( $anchor ) ) {
        switch ( $anchor ) {
            case '':
            case 'post':
            case 'postexcerpt':
                return null; // do not allow these, to offset obsolete configurations
            default:
                $id = $anchor;
                break;
        }
        $anchor          = array();
        $anchor['where'] = 'before';
    } else if ( isset( $anchor['id'] ) ) {
        $id = $anchor['id'];
        unset( $anchor['id'] );
    } else {
        return false;
    }

    return $id;
}

/**
 * @since 3.4
 */
function qtranxf_standardize_front_config( $cfg_front ) {
    // remove filters with empty priorities
    foreach ( $cfg_front as $k => $cfg ) {
        if ( ! isset( $cfg['filters'] ) ) {
            continue;
        }
        if ( ! empty( $cfg['filters']['text'] ) ) {
            foreach ( $cfg['filters']['text'] as $nm => $pr ) {
                if ( $pr === '' ) {
                    unset( $cfg_front[ $k ]['filters']['text'][ $nm ] );
                }
            }
        }
        if ( ! empty( $cfg['filters']['url'] ) ) {
            foreach ( $cfg['filters']['url'] as $nm => $pr ) {
                if ( $pr === '' ) {
                    unset( $cfg_front[ $k ]['filters']['url'][ $nm ] );
                }
            }
        }
        if ( ! empty( $cfg['filters']['term'] ) ) {
            foreach ( $cfg['filters']['term'] as $nm => $pr ) {
                if ( $pr === '' ) {
                    unset( $cfg_front[ $k ]['filters']['term'][ $nm ] );
                }
            }
        }
    }

    return $cfg_front;
}

/**
 * @since 3.4
 */
function qtranxf_standardize_admin_config( $configs ) {
    foreach ( $configs as $k => $config ) {
        if ( ! is_array( $config ) ) {
            continue;
        }
        if ( $k === 'forms' ) {
            foreach ( $config as $form_id => $frm ) {
                if ( isset( $frm['form']['id'] ) ) {
                    $id = $frm['form']['id'];
                    unset( $frm['form']['id'] );
                    if ( empty( $frm['form'] ) ) {
                        unset( $frm['form'] );
                    }
                    $configs['forms'][ $id ] = $frm;
                    if ( $id !== $form_id ) {
                        unset( $configs['forms'][ $form_id ] );
                    }
                    $form_id = $id;
                }
                if ( isset( $frm['fields'] ) ) {
                    $configs['forms'][ $form_id ]['fields'] = qtranxf_standardize_config_fields( $frm['fields'] );
                }
            }
        } else if ( $k === 'anchors' ) {
            if ( empty( $config ) ) {
                unset( $configs['anchors'] );
            } else {
                foreach ( $configs['anchors'] as $k_anchor => $anchor ) {
                    $id = qtranxf_standardize_config_anchor( $anchor );
                    if ( is_null( $id ) ) {
                        unset( $configs['anchors'][ $k_anchor ] );
                    } else if ( is_string( $id ) ) {
                        $configs['anchors'][ $id ] = $anchor;
                        if ( $id !== $k_anchor ) {
                            unset( $configs['anchors'][ $k_anchor ] );
                        }
                    }
                }
                if ( empty( $configs['anchors'] ) ) {
                    unset( $configs['anchors'] );
                }
            }
        } else {
            $configs[ $k ] = qtranxf_standardize_admin_config( $config );//recursive call
        }
    }

    return $configs;
}

/**
 * @since 3.4
 */
function qtranxf_standardize_i18n_config( $configs ) {
    if ( isset( $configs['admin-config'] ) ) {
        $configs['admin-config'] = qtranxf_standardize_admin_config( $configs['admin-config'] );
    }
    if ( isset( $configs['front-config'] ) ) {
        $configs['front-config'] = qtranxf_standardize_front_config( $configs['front-config'] );
    }

    return $configs;
}

/**
 * @since 3.4
 */
function qtranxf_load_config_all( $json_files, $custom_config ) {
    global $q_config;
    $nerr = isset( $q_config['url_info']['errors'] ) ? count( $q_config['url_info']['errors'] ) : 0;
    $cfg  = qtranxf_load_config_files( $json_files );
    $cfg  = qtranxf_merge_config( $cfg, $custom_config );
    $cfg  = qtranxf_standardize_i18n_config( $cfg );
    // store the errors permanently until an admin fixes them,
    // otherwise admin may not realise that not all configurations are loaded.
    if ( ! empty( $q_config['url_info']['errors'] ) && $nerr != count( $q_config['url_info']['errors'] ) ) {
        // new errors occurred
        $errs = array_slice( $q_config['url_info']['errors'], $nerr );
        update_option( 'qtranslate_config_errors', $errs );
    } else {
        delete_option( 'qtranslate_config_errors' );
    }

    return $cfg;
}

/**
 * @since 3.4
 */
function qtranxf_update_config_options( $config_files, $changed = true ) {
    //qtranxf_dbg_log('qtranxf_update_config_options: $config_files: ', $config_files);
    if ( $changed ) {
        update_option( 'qtranslate_config_files', $config_files );
        qtranxf_update_admin_notice( 'config-files-changed', true );//notify admin
    }
    $custom_config = get_option( 'qtranslate_custom_i18n_config', array() );
    $cfg           = qtranxf_load_config_all( $config_files, $custom_config );
    update_option( 'qtranslate_admin_config', $cfg['admin-config'] );
    update_option( 'qtranslate_front_config', $cfg['front-config'] );
}

/**
 * @since 3.4
 */
function qtranxf_search_config_files_theme( $theme = null, $found = null ) {
    if ( ! $theme ) {
        $theme = wp_get_theme();
    } else if ( is_string( $theme ) ) {
        $theme = wp_get_theme( $theme );
    }
    if ( ! $found ) {
        $found = array();
    }
    $fn = $theme->theme_root . '/' . $theme->stylesheet . '/i18n-config.json';
    if ( file_exists( $fn ) ) {
        $found[] = $fn;
    } else {
        $fn = QTRANSLATE_DIR . '/i18n-config/themes/' . $theme->stylesheet . '/i18n-config.json';
        if ( file_exists( $fn ) ) {
            $found[] = $fn;
        }
    }
    $parent_theme = $theme->parent();
    if ( ! empty( $parent_theme ) ) {
        return qtranxf_search_config_files_theme( $parent_theme, $found );
    }

    return $found;
}

/**
 * @since 3.4
 */
function qtranxf_normalize_config_files( $found ) {
    $nc = strlen( WP_CONTENT_DIR );
    $np = strlen( QTRANSLATE_DIR );
    foreach ( $found as $k => $fn ) {
        if ( substr( $fn, 0, $np ) === QTRANSLATE_DIR ) {
            $found[ $k ] = '.' . substr( $fn, $np );
        } else if ( substr( $fn, 0, $nc ) === WP_CONTENT_DIR ) {
            $found[ $k ] = substr( $fn, $nc + 1 );
        }
    }

    return $found;
}

/**
 * @since 3.4
 */
function qtranxf_find_plugin_by_foder( $fld, $plugins ) {
    _deprecated_function( __FUNCTION__, '3.5.5', 'qtranxf_find_plugin_by_folder()' );

    return qtranxf_find_plugin_by_folder( $fld, $plugins );
}

/**
 * @since 3.5.5
 */
function qtranxf_find_plugin_by_folder( $fld, $plugins ) {
    foreach ( $plugins as $plugin ) {
        $dir = dirname( $plugin );
        $bnm = basename( $dir );
        if ( $fld == $bnm ) {
            return $plugin;
        }
    }

    return null;
}

/**
 * Search globally for config files:
 * 1) themes
 * 2) mu-plugins
 * 3) plugins
 *
 * @link https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide/
 *
 * @since 3.4
 */
function qtranxf_search_config_files() {
    $found = qtranxf_search_config_files_theme();

    $mu_plugins = wp_get_mu_plugins();
    // Caution: plugin files are given in absolute paths
    foreach ( $mu_plugins as $plugin_file ) {
        if ( $plugin_file === QTRANSLATE_FILE ) {
            continue;
        }
        $config_file = WPMU_PLUGIN_DIR . '/' . basename( $plugin_file, '.php' ) . '/i18n-config.json';
        if ( is_readable( $config_file ) ) {
            $found[] = $config_file;
            break;
        }
        $config_file = QTRANSLATE_DIR . '/i18n-config/plugins/' . basename( $plugin_file, '.php' ) . '/i18n-config.json';
        if ( is_readable( $config_file ) ) {
            $found[] = $config_file;
            break;
        }
    }

    $plugins = wp_get_active_and_valid_plugins();
    // Caution: plugin files are given here in absolute paths, not relative - wrong WP PHPDoc!
    foreach ( $plugins as $plugin_file ) {
        if ( $plugin_file === QTRANSLATE_FILE ) {
            continue;
        }
        // absolute path to the plugin dir
        $plugin_dir  = dirname( $plugin_file );
        $config_file = $plugin_dir . '/i18n-config.json';
        if ( is_readable( $config_file ) ) {
            $found[] = $config_file;
            break;
        }
        $config_file = QTRANSLATE_DIR . '/i18n-config/plugins/' . basename( $plugin_dir ) . '/i18n-config.json';
        if ( is_readable( $config_file ) ) {
            $found[] = $config_file;
            break;
        }
    }

    return qtranxf_normalize_config_files( $found );
}

/**
 * Inserts new entry at the second position, for now.
 * Later we may need to preserve order somehow.
 * @since 3.4
 */
function qtranxf_add_config_file( $config_files, $fn ) {
    $a   = array_slice( $config_files, 0, 1 );
    $a[] = $fn;
    foreach ( array_slice( $config_files, 1 ) as $f ) {
        if ( ! is_string( $f ) ) {
            continue;
        }
        $a[] = $f;
    }

    return $a;
}

/**
 * @since 3.4
 */
function qtranxf_add_config_files( &$config_files, $found ) {
    $changed = false;
    foreach ( $found as $fn ) {
        $i = array_search( $fn, $config_files );
        if ( $i !== false ) {
            continue;
        }
        $config_files = qtranxf_add_config_file( $config_files, $fn );
        $changed      = true;
    }

    return $changed;
}

function qtranxf_del_config_files( &$config_files, $found ) {
    $changed = false;
    foreach ( $found as $fn ) {
        $i = array_search( $fn, $config_files );
        if ( $i === false ) {
            continue;
        }
        unset( $config_files[ $i ] );
        $changed = true;
    }

    return $changed;
}

/**
 * @since 3.4
 */
function qtranxf_update_config_files() {
    $config_files = qtranxf_get_option_config_files();
    $found        = qtranxf_search_config_files();
    $changed      = qtranxf_add_config_files( $config_files, $found );
    //qtranxf_dbg_log('qtranxf_update_config_files: $config_files: ',$config_files);
    qtranxf_update_config_options( $config_files, $changed );
}

function qtranxf_find_plugin_file( $fp ) {
    _deprecated_function( __FUNCTION__, '3.7.3' );

    return null;
}

function qtranxf_on_switch_theme( $new_name, $new_theme ) {
    $config_files = qtranxf_get_option_config_files();
    $changed      = false;

    $old_theme_stylesheet = get_option( 'theme_switched' );
    $found                = qtranxf_search_config_files_theme( $old_theme_stylesheet );
    $found                = qtranxf_normalize_config_files( $found );
    if ( qtranxf_del_config_files( $config_files, $found ) ) {
        $changed = true;
    }

    $found = qtranxf_search_config_files_theme( $new_theme );
    $found = qtranxf_normalize_config_files( $found );
    if ( qtranxf_add_config_files( $config_files, $found ) ) {
        $changed = true;
    }

    if ( ! $changed ) {
        return;
    }
    qtranxf_update_config_options( $config_files );
}

add_action( 'switch_theme', 'qtranxf_on_switch_theme', 10, 2 );

function qtranxf_find_plugin_config_files( &$fn_bnm, &$fn_qtx, $bnm ) {
    _deprecated_function( __FUNCTION__, '3.7.3', 'qtranxf_find_plugin_config_file()' );

    $fn_bnm = qtranxf_find_plugin_config_file( $bnm );
    $fn_qtx = null;

    return $fn_bnm;
}

/**
 * Search for i18n-config.json files for regular plugins
 * @link https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide/
 *
 * @param string $plugin name as relative dir/file.php
 *
 * @return string|bool path normalized for qTranslate config, false if not found
 */
function qtranxf_find_plugin_config_file( $plugin ) {
    $plugin_dirname = dirname( $plugin );

    // external configuration prevails
    $config_file = WP_PLUGIN_DIR . '/' . $plugin_dirname . '/i18n-config.json';
    if ( is_readable( $config_file ) ) {
        return qtranxf_normalize_config_files( [ $config_file ] );
    }

    // built-in configuration
    $config_file = QTRANSLATE_DIR . '/18n-config/plugins/' . $plugin_dirname . '/i18n-config.json';
    if ( is_readable( $config_file ) ) {
        return qtranxf_normalize_config_files( [ $config_file ] );
    }

    return false;
}

function qtranxf_adjust_config_files( $file_to_add, $file_to_del ) {
    $config_files = qtranxf_get_option_config_files();
    if ( $file_to_add ) {
        if ( in_array( $file_to_add, $config_files ) ) {
            $file_to_add = false;
        } else {
            $config_files = qtranxf_add_config_file( $config_files, $file_to_add );
        }
    }
    if ( $file_to_del ) {
        $i = array_search( $file_to_del, $config_files );
        if ( $i === false ) {
            $file_to_del = false;
        } else {
            unset( $config_files[ $i ] );
        }
    }
    if ( $file_to_add || $file_to_del ) {
        qtranxf_update_config_options( $config_files );
    }
}

function qtranxf_on_activate_plugin( $plugin, $network_wide = false ) {
    if ( $plugin === plugin_basename( QTRANSLATE_FILE ) ) {
        return;
    }
    $file_to_add = qtranxf_find_plugin_config_file( $plugin );
    if ( $file_to_add ) {
        qtranxf_adjust_config_files( $file_to_add, null );
    }
}

add_action( 'activate_plugin', 'qtranxf_on_activate_plugin' );

function qtranxf_on_deactivate_plugin( $plugin, $network_deactivating = false ) {
    if ( $plugin === plugin_basename( QTRANSLATE_FILE ) ) {
        return;
    }
    $file_to_del = qtranxf_find_plugin_config_file( $plugin );
    if ( $file_to_del ) {
        qtranxf_adjust_config_files( null, $file_to_del );
    }
}

add_action( 'deactivate_plugin', 'qtranxf_on_deactivate_plugin' );

function qtranxf_clear_debug_log() {
    //clear file debug-qtranslate.log
    $f = WP_CONTENT_DIR . '/debug-qtranslate.log';
    if ( file_exists( $f ) ) {
        if ( WP_DEBUG ) {
            $fh = fopen( $f, "a+" );
            ftruncate( $fh, 0 );
            fclose( $fh );
        } else {
            unlink( $f );
        }
    }
}


function qtranxf_activation_hook() {
    qtranxf_clear_debug_log();
    //qtranxf_dbg_log('qtranxf_activation_hook: ', __FILE__);
    if ( version_compare( PHP_VERSION, '5.4' ) < 0 ) {
        // Deactivate ourself
        load_plugin_textdomain( 'qtranslate', false, basename( QTRANSLATE_DIR ) . '/lang' );
        $msg = sprintf( __( 'Plugin %s requires PHP version %s at least. This server instance runs PHP version %s. A PHP version %s or higher is recommended. The plugin has not been activated.', 'qtranslate' ), qtranxf_get_plugin_link(), '5.4', PHP_VERSION, '7.3' );
        deactivate_plugins( plugin_basename( QTRANSLATE_FILE ) );
        wp_die( $msg );
    }

    require_once( QTRANSLATE_DIR . '/admin/qtx_admin_options.php' );
    require_once( QTRANSLATE_DIR . '/admin/qtx_import_export.php' );

    // Check if other qTranslate forks are activated.
    if ( is_plugin_active( 'mqtranslate/mqtranslate.php' ) ) {
        qtranxf_admin_notice_deactivate_plugin( 'mqTranslate', 'mqtranslate/mqtranslate.php' );
    }

    if ( is_plugin_active( 'qtranslate/qtranslate.php' ) ) {
        update_option( 'qtranslate_qtrans_compatibility', '1' );
        qtranxf_admin_notice_deactivate_plugin( 'qTranslate', 'qtranslate/qtranslate.php' );
    }

    if ( is_plugin_active( 'qtranslate-xp/ppqtranslate.php' ) ) {
        qtranxf_admin_notice_deactivate_plugin( 'qTranslate Plus', 'qtranslate-xp/ppqtranslate.php' );
    }

    if ( is_plugin_active( 'ztranslate/ztranslate.php' ) ) {
        qtranxf_admin_notice_deactivate_plugin( 'zTranslate', 'ztranslate/ztranslate.php' );
    }

    $ts                     = time();
    $next_thanks            = get_option( 'qtranslate_next_thanks' );
    $check_qtranslate_forks = $next_thanks === false;
    if ( $next_thanks !== false && $next_thanks < strtotime( '+7days', $ts ) ) {
        $next_thanks = strtotime( '+' . rand( 10, 20 ) . 'days' );
        update_option( 'qtranslate_next_thanks', $next_thanks );
    }
    $messages = qtranxf_update_admin_notice( 'next_thanks', true );

    $default_language = get_option( 'qtranslate_default_language' );
    $first_install    = $default_language === false;
    if ( $first_install ) {
        qtranxf_default_default_language();
        $check_qtranslate_forks = true;
        if ( isset( $messages['initial-install'] ) ) {
            qtranxf_update_option_admin_notices( $messages, 'initial-install' );
        }
    } else {
        if ( ! isset( $messages['initial-install'] ) ) {
            qtranxf_update_option_admin_notices( $messages, 'initial-install' );
        }
    }

    // @since 3.3.7
    if ( $check_qtranslate_forks ) { // possibly first install after a fork
        if ( get_option( 'qtranslate_qtrans_compatibility' ) === false ) {
            //to prevent most of fatal errors on upgrade
            if ( file_exists( WP_PLUGIN_DIR . '/qtranslate/qtranslate.php' )
                 || file_exists( WP_PLUGIN_DIR . '/mqtranslate/mqtranslate.php' )
                 || file_exists( WP_PLUGIN_DIR . '/ztranslate/ztranslate.php' )
                 || file_exists( WP_PLUGIN_DIR . '/qtranslate-xp/ppqtranslate.php' )
            ) {
                update_option( 'qtranslate_qtrans_compatibility', '1' );
            }
        }
    }

    QTX_Admin_Modules::update_modules_status();

    /**
     * A chance to execute activation actions specifically for this plugin.
     * @since 3.4
     */
    do_action( 'qtranslate_activation_hook' );

    qtranxf_update_config_files();
}

/**
 * @since 3.4
 */
function qtranxf_deactivation_hook() {
    //qtranxf_dbg_log('qtranxf_deactivation_hook: ', __FILE__);

    /**
     * A chance to execute deactivation actions specifically for this plugin.
     */
    do_action( 'qtranslate_deactivation_hook' );
}

function qtranxf_admin_notice_config_files_changed() {
    if ( ! qtranxf_check_admin_notice( 'config-files-changed' ) ) {
        return;
    }
    qtranxf_admin_notice_dismiss_script();
    $url = admin_url( 'options-general.php?page=qtranslate-xt#integration' );
    echo '<div class="notice notice-success qtranxs-notice-ajax is-dismissible" id="qtranxs-config-files-changed" action="unset"><p>';
    printf( __( 'Option "%s" for plugin %s has been auto-adjusted after recent changes in the site configuration. It might be a good idea to %sreview the changes%s in the list of configuration files.', 'qtranslate' ), '<a href="' . $url . '">' . __( 'Configuration Files', 'qtranslate' ) . '</a>', qtranxf_get_plugin_link(), '<a href="' . $url . '">', '</a>' );
    echo '<br/></p><p>';
    echo '<a class="button" href="' . $url . '">';
    printf( __( 'Review Option "%s"', 'qtranslate' ), __( 'Configuration Files', 'qtranslate' ) );
    echo '</a>&nbsp;&nbsp;&nbsp;<a class="button" href="https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide/" target="_blank">';
    echo __( 'Read Integration Guide', 'qtranslate' );
    echo '</a>&nbsp;&nbsp;&nbsp;<a class="button qtranxs-notice-dismiss" href="javascript:void(0);">' . __( 'I have already done it, dismiss this message.', 'qtranslate' );
    echo '</a></p></div>';
}

add_action( 'admin_notices', 'qtranxf_admin_notice_config_files_changed' );

function qtranxf_admin_notice_first_install() {
    if ( qtranxf_check_admin_notice( 'initial-install' ) ) {
        return;
    }
    qtranxf_admin_notice_dismiss_script();
    echo '<div class="notice notice-info qtranxs-notice-ajax notice is-dismissible" id="qtranxs-initial-install"><p>';
    printf( __( 'Are you new to plugin %s?', 'qtranslate' ), qtranxf_get_plugin_link() );
    echo '<br/>';
    echo '</p><p><a class="button" href="https://github.com/qtranslate/qtranslate-xt/wiki/Startup-Guide/" target="_blank">';
    echo __( 'Read Startup Guide', 'qtranslate' );
    echo '</a>&nbsp;&nbsp;&nbsp;<a class="button qtranxs-notice-dismiss" href="javascript:void(0);">' . __( 'I have already done it, dismiss this message.', 'qtranslate' );
    echo '</a></p></div>';
}

add_action( 'admin_notices', 'qtranxf_admin_notice_first_install' );

function qtranxf_admin_notice_deactivate_plugin( $nm, $plugin ) {
    deactivate_plugins( $plugin, true );
    $d        = dirname( $plugin );
    $link     = '<a href="https://wordpress.org/plugins/' . $d . '/" target="_blank">' . $nm . '</a>';
    $qtxnm    = 'qTranslate&#8209;XT';
    $qtxlink  = qtranxf_get_plugin_link();
    $imported = false;
    $f        = 'qtranxf_migrate_import_' . str_replace( '-', '_', dirname( $plugin ) );
    if ( function_exists( $f ) ) {
        global $wpdb;
        $options = $wpdb->get_col( "SELECT `option_name` FROM {$wpdb->options} WHERE `option_name` LIKE 'qtranslate_%'" );
        if ( empty( $options ) ) {
            $f();
            $imported = true;
        }
    }
    $s   = '</p><p>' . sprintf( __( 'It might be a good idea to review %smigration instructions%s, if you have not yet done so.', 'qtranslate' ), '<a href="https://github.com/qtranslate/qtranslate-xt/wiki/Migration-Guide/" target="_blank">', '</a>' ) . '</p><p><a class="button" href="">';
    $msg = sprintf( __( 'Activation of plugin %s deactivated plugin %s since they cannot run simultaneously.', 'qtranslate' ), $qtxlink, $link ) . ' ';
    if ( $imported ) {
        $msg .= sprintf( __( 'The compatible settings from %s have been imported to %s. Further tuning, import, export and reset of options can be done at Settings/Languages configuration page, once %s is running.%sContinue%s', 'qtranslate' ), $nm, $qtxnm, $qtxnm, $s, '</a>' );
    } else {
        $msg .= sprintf( __( 'You may import/export compatible settings from %s to %s on Settings/Languages configuration page, once %s is running.%sContinue%s', 'qtranslate' ), $nm, $qtxnm, $qtxnm, $s, '</a>' );
    }
    wp_die( '<p>' . $msg . '</p>' );
}

function qtranxf_admin_notice_plugin_conflict( $title, $plugin ) {
    if ( ! is_plugin_active( $plugin ) ) {
        return;
    }
    $me   = qtranxf_get_plugin_link();
    $link = '<a href="https://wordpress.org/plugins/' . dirname( $plugin ) . '/" target="_blank">' . $title . '</a>';
    echo '<div class="notice notice-error is-dismissible"><p>';
    printf( __( '%sError:%s plugin %s cannot run concurrently with plugin %s. You may import and export compatible settings between %s and %s on Settings/<a href="%s">Languages</a> configuration page. Then you have to deactivate one of the plugins to continue.', 'qtranslate' ), '<strong>', '</strong>', $me, $link, 'qTranslate&#8209;XT', $title, admin_url( 'options-general.php?page=qtranslate-xt' ), 'qtranslate' );
    echo ' ';
    printf( __( 'It might be a good idea to review %smigration instructions%s, if you have not yet done so.', 'qtranslate' ), '<a href="https://github.com/qtranslate/qtranslate-xt/wiki/Migration-Guide/" target="_blank">', '</a>' );

    $nonce = wp_create_nonce( 'deactivate-plugin_' . $plugin );
    echo '</p><p> &nbsp; &nbsp; &nbsp; &nbsp;<a class="button" href="' . admin_url( 'plugins.php?action=deactivate&plugin=' . urlencode( $plugin ) . '&plugin_status=all&paged=1&s&_wpnonce=' . $nonce ) . '"><strong>' . sprintf( __( 'Deactivate %s', 'qtranslate' ), $title ) . '</strong></a>';
    $nonce = wp_create_nonce( 'deactivate-plugin_qtranslate-xt/qtranslate.php' );
    echo ' &nbsp; &nbsp; &nbsp; &nbsp;<a class="button" href="' . admin_url( 'plugins.php?action=deactivate&plugin=' . urlencode( 'qtranslate-xt/qtranslate.php' ) . '&plugin_status=all&paged=1&s&_wpnonce=' . $nonce ) . '"><strong>' . sprintf( __( 'Deactivate %s', 'qtranslate' ), 'qTranslate&#8209;XT' ) . '</strong></a>';
    echo '</p></div>';
}

function qtranxf_admin_notices_plugin_conflicts() {
    qtranxf_admin_notice_plugin_conflict( 'qTranslate', 'qtranslate/qtranslate.php' );
    qtranxf_admin_notice_plugin_conflict( 'mqTranslate', 'mqtranslate/mqtranslate.php' );
    qtranxf_admin_notice_plugin_conflict( 'qTranslate Plus', 'qtranslate-xp/ppqtranslate.php' );
    qtranxf_admin_notice_plugin_conflict( 'zTranslate', 'ztranslate/ztranslate.php' );
    do_action( 'qtranslate_admin_notices_plugin_conflicts' );
}

add_action( 'admin_notices', 'qtranxf_admin_notices_plugin_conflicts' );

function qtranxf_get_plugin_link() {
    return '<a href="https://github.com/qTranslate/qtranslate-xt/" target="_blank">qTranslate&#8209;XT</a>';
}

function qtranxf_admin_notices_block_editor() {
    global $wp_version;
    if ( version_compare( $wp_version, '5.0' ) >= 0 &&
         ! ( class_exists( 'Classic_Editor' ) ||
             is_plugin_active( 'disable-gutenberg/disable-gutenberg.php' ) ||
             is_plugin_active( 'no-gutenberg/no-gutenberg.php' ) ) ) {
        $link = "https://wordpress.org/plugins/classic-editor/";
        ?>
        <div class="notice notice-error">
            <p><?php printf( __( 'Block editor (Gutenberg) not supported in %s yet! Please install and activate the <a href="%s"> Classic Editor</a> plugin.', 'qtranslate' ), 'qTranslate&#8209;XT', $link ); ?></p>
        </div>
        <?php
    }
}

add_action( 'admin_notices', 'qtranxf_admin_notices_block_editor' );

function qtranxf_admin_notices_errors() {
    //qtranxf_dbg_log('14.qtranxf_admin_notices_errors:');
    $msgs = get_option( 'qtranslate_config_errors' );
    if ( ! is_array( $msgs ) ) {
        return;
    }
    foreach ( $msgs as $key => $msg ) {
        echo '<div class="notice notice-error is-dismissible" id="qtranxs_config_error_' . $key . '"><p><a href="' . admin_url( 'options-general.php?page=qtranslate-xt' ) . '">qTranslate&#8209;XT</a>:&nbsp;';
        // translators: Colon after a title. Template reused from language menu item.
        echo sprintf( __( '%s:', 'qtranslate' ), '<strong>' . __( 'Error', 'qtranslate' ) . '</strong>' );
        echo '&nbsp;' . $msg . '</p></div>';
    }
}

add_action( 'admin_notices', 'qtranxf_admin_notices_errors' );

function qtranxf_check_admin_notice( $id ) {
    $messages = get_option( 'qtranslate_admin_notices' );
    if ( isset( $messages[ $id ] ) ) {
        return $messages[ $id ];
    }

    return false;
}

function qtranxf_update_option_admin_notices( $messages, $id, $set = true ) {
    if ( ! is_array( $messages ) ) {
        $messages = array();
    }
    if ( $set ) {
        $messages[ $id ] = time();
    } else {
        unset( $messages[ $id ] );
    }
    update_option( 'qtranslate_admin_notices', $messages );

    return $messages;
}

function qtranxf_update_admin_notice( $id, $set ) {
    $messages = get_option( 'qtranslate_admin_notices', array() );

    return qtranxf_update_option_admin_notices( $messages, $id, $set );
}

function qtranxf_ajax_qtranslate_admin_notice() {
    if ( ! isset( $_POST['notice_id'] ) ) {
        return;
    }
    $id  = sanitize_text_field( $_POST['notice_id'] );
    $set = empty( $_POST['notice_action'] );
    qtranxf_update_admin_notice( $id, $set );
}

add_action( 'wp_ajax_qtranslate_admin_notice', 'qtranxf_ajax_qtranslate_admin_notice' );

function qtranxf_admin_notice_dismiss_script() {
    static $admin_notice_dismiss_script;
    if ( $admin_notice_dismiss_script ) {
        return;
    }
    $admin_notice_dismiss_script = true;
    wp_register_script( 'qtx_admin_notices', plugins_url( 'js/notices.js', __FILE__ ), array( 'jquery' ), QTX_VERSION );
    wp_enqueue_script( 'qtx_admin_notices' );
}

/** register activation/deactivation hooks */
function qtranxf_register_activation_hooks() {
    $qtx_plugin_basename = plugin_basename( QTRANSLATE_FILE );
    register_activation_hook( $qtx_plugin_basename, 'qtranxf_activation_hook' );
    register_deactivation_hook( $qtx_plugin_basename, 'qtranxf_deactivation_hook' );
    QTX_Admin_Modules::register_hooks();
}
