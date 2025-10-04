<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once QTRANSLATE_DIR . '/src/admin/admin_notices.php';
require_once QTRANSLATE_DIR . '/src/modules/admin_module_manager.php';
require_once QTRANSLATE_DIR . '/src/utils.php';

/**
 * Save language properties from configuration to WP options
 *
 * @param array $cfg dictionary
 *
 * @return array
 * @since 3.3
 */
function qtranxf_save_languages( array $cfg ): array {
    global $qtranslate_options;
    foreach ( $qtranslate_options['languages'] as $key => $option ) {
        if ( is_array( $cfg[ $key ] ) ) {
            foreach ( $cfg[ $key ] as $language => $value ) {
                if ( empty( $value ) ) {
                    unset( $cfg[ $key ][ $language ] );
                }
            }
        }
        if ( empty( $cfg[ $key ] ) ) {
            delete_option( $option );
        } else {
            update_option( $option, $cfg[ $key ] );
        }
    }

    return $cfg;
}

/**
 * since 3.2.9.2
 */
function qtranxf_default_enabled_languages(): array {
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

    return array( $lang, $lang != 'en' ? 'en' : 'de' );
}

/**
 * since 3.2.9.2
 */
function qtranxf_default_default_language(): string {
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
 * @param array $json_files normalized paths
 *
 * @return array configuration dictionary
 * @since 3.3.2
 */
function qtranxf_load_config_files( array $json_files ): array {
    $content_dir = null;
    $qtransx_dir = null;
    foreach ( $json_files as $index => $config_file ) {
        if ( file_exists( $config_file ) ) {
            continue;
        }
        $full_path = null;
        if ( $config_file[0] == '.' && $config_file[1] == '/' ) {
            if ( ! $qtransx_dir ) {
                $qtransx_dir = QTRANSLATE_DIR;
            }
            $full_path = $qtransx_dir . substr( $config_file, 1 );
        }
        if ( ! file_exists( $full_path ) ) {
            if ( ! $content_dir ) {
                $content_dir = trailingslashit( WP_CONTENT_DIR );
            }
            $full_path = $content_dir . $config_file;
        }
        if ( file_exists( $full_path ) ) {
            $json_files[ $index ] = $full_path;
        } else {
            qtranxf_error_log( sprintf( __( 'Could not find file "%s" listed in option "%s".', 'qtranslate' ), '<strong>' . $config_file . '</strong>', '<a href="' . admin_url( 'options-general.php?page=qtranslate-xt#integration' ) . '">' . __( 'Configuration Files', 'qtranslate' ) . '</a>' ) . ' ' . __( 'Please, either put file in place or update the option.', 'qtranslate' ) . ' ' . sprintf( __( 'Once the problem is fixed, re-save the configuration by pressing button "%s" on plugin %ssettings page%s.', 'qtranslate' ), __( 'Save Changes', 'qtranslate' ), '<a href="' . admin_url( 'options-general.php?page=qtranslate-xt#integration' ) . '">', '</a>' ) );
            unset( $json_files[ $index ] );
        }
    }

    $cfg_all               = [ 'admin-config' => [], 'front-config' => [] ];
    $main_keys             = array_keys( $cfg_all );
    $deprecated_js_configs = [];

    foreach ( $json_files as $config_file ) {
        $cfg_json = file_get_contents( $config_file );
        if ( ! $cfg_json ) {
            qtranxf_error_log( sprintf( __( 'Could not load file "%s" listed in option "%s".', 'qtranslate' ), '<strong>' . $config_file . '</strong>', '<a href="' . admin_url( 'options-general.php?page=qtranslate-xt#integration' ) . '">' . __( 'Configuration Files', 'qtranslate' ) . '</a>' ) . ' ' . __( 'Please, make sure the file is accessible and readable.', 'qtranslate' ) . ' ' . sprintf( __( 'Once the problem is fixed, re-save the configuration by pressing button "%s" on plugin %ssettings page%s.', 'qtranslate' ), __( 'Save Changes', 'qtranslate' ), '<a href="' . admin_url( 'options-general.php?page=qtranslate-xt#integration' ) . '">', '</a>' ) );
            break;
        }
        $cfg = json_decode( $cfg_json, true );
        if ( empty( $cfg ) || ! is_array( $cfg ) ) {
            qtranxf_error_log( sprintf( __( 'Could not parse %s file "%s" listed in option "%s".', 'qtranslate' ), 'JSON', '<strong>' . $config_file . '</strong>', '<a href="' . admin_url( 'options-general.php?page=qtranslate-xt#integration' ) . '">' . __( 'Configuration Files', 'qtranslate' ) . '</a>' ) . ' ' . __( 'Please, correct the syntax error in the file.', 'qtranslate' ) . ' ' . sprintf( __( 'Once the problem is fixed, re-save the configuration by pressing button "%s" on plugin %ssettings page%s.', 'qtranslate' ), __( 'Save Changes', 'qtranslate' ), '<a href="' . admin_url( 'options-general.php?page=qtranslate-xt#integration' ) . '">', '</a>' ) );
            break;
        }

        // TODO: Remove check for deprecated keys in future versions
        foreach ( $main_keys as $main_key ) {
            if ( ! array_key_exists( $main_key, $cfg ) ) {
                continue;
            }
            $main_config = $cfg[ $main_key ];
            foreach ( $main_config as $page_config ) {
                if ( isset( $page_config['js-conf'] ) ) {
                    $deprecated_key = '"js-conf"';
                    if ( ! isset( $deprecated_js_configs[ $deprecated_key ] ) ) {
                        $deprecated_js_configs[ $deprecated_key ] = [];
                    }
                    $deprecated_js_configs[ $deprecated_key ][] = $config_file;
                }
                if ( isset( $page_config['js-exec'] ) ) {
                    foreach ( $page_config['js-exec'] as $js_exec ) {
                        if ( isset( $js_exec['javascript'] ) ) {
                            $deprecated_key = '"javascript" (in "js-exec")';
                            if ( ! isset( $deprecated_js_configs[ $deprecated_key ] ) ) {
                                $deprecated_js_configs[ $deprecated_key ] = [];
                            }
                            $deprecated_js_configs[ $deprecated_key ][] = $config_file;
                            break;
                        }
                    }
                }
            }
        }

        $cfg_all = qtranxf_merge_config( $cfg_all, $cfg );
    }

    if ( ! empty( $deprecated_js_configs ) ) {
        $warning_files = [];
        foreach ( $deprecated_js_configs as $deprecated_key => $deprecated_files ) {
            $unique_files = array_unique( $deprecated_files );
            foreach ( $unique_files as $file ) {
                $warning_files[] = "$file : $deprecated_key";
            }
        }
        $warning = sprintf( __( 'Deprecated configuration key(s) in %s:', 'qtranslate' ), 'qTranslate-XT' );
        $warning .= '<pre>' . implode( '<br>', $warning_files ) . '</pre>';
        $warning .= sprintf( __( 'This configuration will become incompatible in next releases. For more information, see: %s.', 'qtranslate' ), '<a href="https://github.com/qtranslate/qtranslate-xt/wiki/Custom-Javascript">Wiki Custom Javacsript</a>' );
        qtranxf_add_warning( $warning );
    }

    return $cfg_all;
}

/**
 * @since 3.4
 */
function qtranxf_get_option_config_files(): array {
    $config_files_def = array( './i18n-config.json' );
    $config_files     = get_option( 'qtranslate_config_files', $config_files_def );
    if ( ! is_array( $config_files ) ) {
        $config_files = $config_files_def;
        delete_option( 'qtranslate_config_files' );
    }

    return $config_files;
}

/**
 * @param array $fields dictionary
 *
 * @return array
 * @since 3.4
 */
function qtranxf_standardize_config_fields( array $fields ): array {
    foreach ( $fields as $key => $field ) {
        if ( ! is_array( $field ) ) {
            continue;
        }
        if ( isset( $field['id'] ) ) {
            $id = $field['id'];
            unset( $field['id'] );
            $fields[ $id ] = $field;
            if ( $id !== $key ) {
                unset( $fields[ $key ] );
            }
        } else if ( qtranxf_set_field_jquery( $field ) ) {
            $fields[ $key ] = $field;
        }
    }

    return $fields;
}

/**
 * @param array|string $anchor
 *
 * @return bool|string
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
 * Standardize front config (from file content)
 * Remove filters with empty priorities
 *
 * @param array $cfg_front dictionary
 *
 * @return array
 * @since 3.4
 */
function qtranxf_standardize_front_config( array $cfg_front ): array {
    foreach ( $cfg_front as $key => $cfg ) {
        if ( ! isset( $cfg['filters'] ) ) {
            continue;
        }
        if ( ! empty( $cfg['filters']['text'] ) ) {
            foreach ( $cfg['filters']['text'] as $name => $priority ) {
                if ( $priority === '' ) {
                    unset( $cfg_front[ $key ]['filters']['text'][ $name ] );
                }
            }
        }
        if ( ! empty( $cfg['filters']['url'] ) ) {
            foreach ( $cfg['filters']['url'] as $name => $priority ) {
                if ( $priority === '' ) {
                    unset( $cfg_front[ $key ]['filters']['url'][ $name ] );
                }
            }
        }
        if ( ! empty( $cfg['filters']['term'] ) ) {
            foreach ( $cfg['filters']['term'] as $name => $priority ) {
                if ( $priority === '' ) {
                    unset( $cfg_front[ $key ]['filters']['term'][ $name ] );
                }
            }
        }
    }

    return $cfg_front;
}

/**
 *  Standardize admin config (from file content)
 *
 * @param array $configs dictionary
 *
 * @return array
 * @since 3.4
 */
function qtranxf_standardize_admin_config( array $configs ): array {
    foreach ( $configs as $key => $config ) {
        if ( ! is_array( $config ) ) {
            continue;
        }
        if ( $key === 'forms' ) {
            foreach ( $config as $form_id => $form ) {
                if ( isset( $form['form']['id'] ) ) {
                    $id = $form['form']['id'];
                    unset( $form['form']['id'] );
                    if ( empty( $form['form'] ) ) {
                        unset( $form['form'] );
                    }
                    $configs['forms'][ $id ] = $form;
                    if ( $id !== $form_id ) {
                        unset( $configs['forms'][ $form_id ] );
                    }
                    $form_id = $id;
                }
                if ( isset( $form['fields'] ) ) {
                    $configs['forms'][ $form_id ]['fields'] = qtranxf_standardize_config_fields( $form['fields'] );
                }
            }
        } else if ( $key === 'anchors' ) {
            if ( empty( $config ) ) {
                unset( $configs['anchors'] );
            } else {
                foreach ( $configs['anchors'] as $key_anchor => $anchor ) {
                    $id = qtranxf_standardize_config_anchor( $anchor );
                    if ( is_null( $id ) ) {
                        unset( $configs['anchors'][ $key_anchor ] );
                    } else if ( is_string( $id ) ) {
                        $configs['anchors'][ $id ] = $anchor;
                        if ( $id !== $key_anchor ) {
                            unset( $configs['anchors'][ $key_anchor ] );
                        }
                    }
                }
                if ( empty( $configs['anchors'] ) ) {
                    unset( $configs['anchors'] );
                }
            }
        } else {
            $configs[ $key ] = qtranxf_standardize_admin_config( $config ); // recursive call
        }
    }

    return $configs;
}

/**
 *  Standardize admin and front configs (from file content)
 *
 * @param array $configs dictionary
 *
 * @return array
 * @since 3.4
 */
function qtranxf_standardize_i18n_config( array $configs ): array {
    if ( isset( $configs['admin-config'] ) ) {
        $configs['admin-config'] = qtranxf_standardize_admin_config( $configs['admin-config'] );
    }
    if ( isset( $configs['front-config'] ) ) {
        $configs['front-config'] = qtranxf_standardize_front_config( $configs['front-config'] );
    }

    return $configs;
}

/**
 * Load the complete i18n configuration from files and custom content
 *
 * @param array $json_files as normalized paths
 * @param array $custom_config dictionary
 *
 * @return array dictionary
 * @since 3.4
 */
function qtranxf_load_config_all( array $json_files, array $custom_config ): array {
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
 * Update the WP options holding the admin and front configurations
 *
 * @param array $config_files as normalized paths
 * @param bool $changed true if the list of config files has just changed
 *
 * @since 3.4
 */
function qtranxf_update_config_options( array $config_files, bool $changed = true ): void {
    if ( $changed ) {
        update_option( 'qtranslate_config_files', $config_files );
        qtranxf_update_admin_notice( 'config-files-changed', true ); // notify admin
    }
    $custom_config = get_option( 'qtranslate_custom_i18n_config', array() );
    $cfg           = qtranxf_load_config_all( $config_files, $custom_config );
    update_option( 'qtranslate_admin_config', $cfg['admin-config'] );
    update_option( 'qtranslate_front_config', $cfg['front-config'] );
}

/**
 * Search for theme config files, in the child and parent(s) themes
 * @link https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide/
 *
 * @param WP_Theme|string $theme
 *
 * @return array config files in absolute paths
 * @since 3.4
 */
function qtranxf_search_config_files_theme( $theme = null ): array {
    if ( ! $theme ) {
        $theme = wp_get_theme();
    } elseif ( is_string( $theme ) ) {
        $theme = wp_get_theme( $theme );
    }

    $found = array();
    while ( $theme && $theme->exists() ) {
        // external theme config
        $config_file = $theme->get_theme_root() . '/' . $theme->get_stylesheet() . '/i18n-config.json';
        if ( is_readable( $config_file ) ) {
            $found[] = $config_file;
        } else {
            // built-in theme config
            $config_file = QTRANSLATE_DIR . '/i18n-config/themes/' . $theme->get_stylesheet() . '/i18n-config.json';
            if ( is_readable( $config_file ) ) {
                $found[] = $config_file;
            }
        }

        $theme = $theme->parent();
    }

    return $found;
}

/**
 * Normalize qTranslate configuration files with relative paths such that:
 *  1) files in qTranslate start with ./
 *  2) external files are relative to WP_CONTENT_DIR (plugins, mu-plugins and themes)
 *  3) otherwise absolute path are kept
 *
 * @param array $file_paths
 *
 * @return array of normalized paths
 * @since 3.4
 */
function qtranxf_normalize_config_files( array $file_paths ): array {
    $nc = strlen( WP_CONTENT_DIR );
    $np = strlen( QTRANSLATE_DIR );
    foreach ( $file_paths as $index => $path ) {
        if ( substr( $path, 0, $np ) === QTRANSLATE_DIR ) {
            $file_paths[ $index ] = '.' . substr( $path, $np );
        } else if ( substr( $path, 0, $nc ) === WP_CONTENT_DIR ) {
            $file_paths[ $index ] = substr( $path, $nc + 1 );
        }
    }

    return $file_paths;
}

/**
 * Search globally for config files:
 * 1) themes
 * 2) mu-plugins
 * 3) plugins
 *
 * @link https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide/
 *
 * @return array of normalized paths
 * @since 3.4
 */
function qtranxf_search_config_files(): array {
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
 *
 * @param array $config_files as normalized paths
 * @param string $add_file as normalized path
 *
 * @return array of normalized paths
 * @since 3.4
 */
function qtranxf_add_config_file( array $config_files, string $add_file ): array {
    $updated   = array_slice( $config_files, 0, 1 );
    $updated[] = $add_file;
    foreach ( array_slice( $config_files, 1 ) as $file ) {
        if ( ! is_string( $file ) ) {
            continue;
        }
        $updated[] = $file;
    }

    return $updated;
}

/**
 * Add new config files to an existing set
 * New entries are inserted in second position
 *
 * @param array $config_files as normalized paths
 * @param array $add_files as normalized paths
 *
 * @return bool
 * @see qtranxf_add_config_file
 *
 * @since 3.4
 */
function qtranxf_add_config_files( array &$config_files, array $add_files ): bool {
    $changed = false;
    foreach ( $add_files as $file ) {
        if ( in_array( $file, $config_files ) ) {
            continue;
        }
        $config_files = qtranxf_add_config_file( $config_files, $file );
        $changed      = true;
    }

    return $changed;
}

function qtranxf_del_config_files( array &$config_files, array $del_files ): bool {
    $changed = false;
    foreach ( $del_files as $file ) {
        $index = array_search( $file, $config_files );
        if ( $index === false ) {
            continue;
        }
        unset( $config_files[ $index ] );
        $changed = true;
    }

    return $changed;
}

/**
 * @since 3.4
 */
function qtranxf_update_config_files(): void {
    $config_files = qtranxf_get_option_config_files();
    $found        = qtranxf_search_config_files();
    $changed      = qtranxf_add_config_files( $config_files, $found );
    qtranxf_update_config_options( $config_files, $changed );
}

function qtranxf_on_switch_theme( string $new_name, WP_Theme $new_theme ): void {
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

/**
 * Search for i18n-config.json files for regular plugins
 * @link https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide/
 *
 * @param string $plugin name as relative dir/file.php
 *
 * @return string|bool path normalized for qTranslate config, false if not found
 */
function qtranxf_find_plugin_config_file( string $plugin ) {
    $plugin_dirname = dirname( $plugin );

    // external configuration prevails
    $config_file = WP_PLUGIN_DIR . '/' . $plugin_dirname . '/i18n-config.json';
    if ( is_readable( $config_file ) ) {
        return qtranxf_normalize_config_files( [ $config_file ] )[0];
    }

    // built-in configuration
    $config_file = QTRANSLATE_DIR . '/i18n-config/plugins/' . $plugin_dirname . '/i18n-config.json';
    if ( is_readable( $config_file ) ) {
        return qtranxf_normalize_config_files( [ $config_file ] )[0];
    }

    return false;
}

function qtranxf_adjust_config_files( ?string $file_to_add, ?string $file_to_del ): void {
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

function qtranxf_on_activate_plugin( string $plugin, bool $network_wide = false ): void {
    if ( $plugin === plugin_basename( QTRANSLATE_FILE ) ) {
        return;
    }
    $file_to_add = qtranxf_find_plugin_config_file( $plugin );
    if ( $file_to_add ) {
        qtranxf_adjust_config_files( $file_to_add, null );
    }
}

add_action( 'activate_plugin', 'qtranxf_on_activate_plugin' );

function qtranxf_on_deactivate_plugin( string $plugin, bool $network_deactivating = false ): void {
    if ( $plugin === plugin_basename( QTRANSLATE_FILE ) ) {
        return;
    }
    $file_to_del = qtranxf_find_plugin_config_file( $plugin );
    if ( $file_to_del ) {
        qtranxf_adjust_config_files( null, $file_to_del );
    }
}

add_action( 'deactivate_plugin', 'qtranxf_on_deactivate_plugin' );

function qtranxf_clear_debug_log(): void {
    //clear file debug-qtranslate.log
    $file = WP_CONTENT_DIR . '/debug-qtranslate.log';
    if ( file_exists( $file ) ) {
        if ( WP_DEBUG ) {
            $handle = fopen( $file, "a+" );
            ftruncate( $handle, 0 );
            fclose( $handle );
        } else {
            unlink( $file );
        }
    }
}

function qtranxf_activation_hook(): void {
    qtranxf_clear_debug_log();
    if ( version_compare( PHP_VERSION, '7.3' ) < 0 ) {
        // Deactivate ourself
        load_plugin_textdomain( 'qtranslate', false, basename( QTRANSLATE_DIR ) . '/lang' );
        $msg = sprintf( __( 'Plugin %s requires PHP version %s at least. This server instance runs PHP version %s. A PHP version %s or higher is recommended. The plugin has not been activated.', 'qtranslate' ), qtranxf_get_plugin_link(), '7.3', PHP_VERSION, '8.4' );
        deactivate_plugins( plugin_basename( QTRANSLATE_FILE ) );
        wp_die( $msg );
    }

    require_once QTRANSLATE_DIR . '/src/admin/admin_options.php';
    require_once QTRANSLATE_DIR . '/src/admin/import_export.php';

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
    if ( is_plugin_active( 'qtranslate-x/qtranslate.php' ) ) {
        qtranxf_admin_notice_deactivate_plugin( 'qTranslate-X', 'qtranslate-x/qtranslate.php' );
    }

    // Migrate (rename/import) legacy options, temporary transitions during evolutions.
    qtranxf_rename_legacy_option( 'qtranslate_modules', QTX_OPTIONS_MODULES_STATE );
    qtranxf_import_legacy_option( 'acf_qtranslate', QTX_OPTIONS_MODULE_ACF, false );

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

    // Show block editor (Gutenberg) notice again if there is no active support for Classic or alternative.
    if ( ! qtranxf_is_classic_editor_supported() && isset( $messages['gutenberg-support'] ) ) {
        qtranxf_update_option_admin_notices( $messages, 'gutenberg-support', false );
    }

    qtranxf_unset_admin_notices_deprecated();

    // To initialize the modules state we need the default enabled modules but the `q_config` has not been loaded yet.
    // For the first activation, the default options are used.
    // After reactivation the enabled modules are reloaded, but all the conditions are checked with all plugins again.
    global $qtranslate_options;
    qtranxf_admin_set_default_options( $qtranslate_options );
    qtranxf_load_option_array( 'admin_enabled_modules', $qtranslate_options['admin']['admin_enabled_modules'] );
    QTX_Admin_Module_Manager::update_modules_state();

    /**
     * A chance to execute activation actions specifically for this plugin.
     * @since 3.4
     */
    do_action( 'qtranslate_activation_hook' );

    qtranxf_update_config_files();
}

/**
 * Check if the Classic Editor, or alternative, is supported
 *
 * @return bool
 */
function qtranxf_is_classic_editor_supported(): bool {
    return class_exists( 'Classic_Editor' ) ||
           is_plugin_active( 'disable-gutenberg/disable-gutenberg.php' ) ||
           is_plugin_active( 'no-gutenberg/no-gutenberg.php' );
}

/**
 * @since 3.4
 */
function qtranxf_deactivation_hook(): void {

    /**
     * A chance to execute deactivation actions specifically for this plugin.
     */
    do_action( 'qtranslate_deactivation_hook' );
}

function qtranxf_get_plugin_link(): string {
    return '<a href="https://github.com/qTranslate/qtranslate-xt/" target="_blank">qTranslate&#8209;XT</a>';
}


/** register activation/deactivation hooks */
function qtranxf_register_activation_hooks(): void {
    $qtx_plugin_basename = plugin_basename( QTRANSLATE_FILE );
    register_activation_hook( $qtx_plugin_basename, 'qtranxf_activation_hook' );
    register_deactivation_hook( $qtx_plugin_basename, 'qtranxf_deactivation_hook' );
    QTX_Admin_Module_Manager::register_hooks();
}
