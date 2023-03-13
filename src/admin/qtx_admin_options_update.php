<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once QTRANSLATE_DIR . '/src/admin/qtx_admin_options.php';
require_once QTRANSLATE_DIR . '/src/admin/qtx_import_export.php';
require_once QTRANSLATE_DIR . '/modules/qtx_admin_module_manager.php';

function qtranxf_editConfig() {
    _deprecated_function( __FUNCTION__, '3.10.0', 'qtranxf_edit_config' );
    qtranxf_edit_config();
}

function qtranxf_edit_config() {
    global $q_config;
    if ( ! qtranxf_verify_nonce( 'qtranslate-x_configuration_form' ) ) {
        return;
    }
    // init some needed variables
    if ( ! isset( $q_config['url_info']['errors'] ) ) {
        $q_config['url_info']['errors'] = array();
    }
    if ( ! isset( $q_config['url_info']['warnings'] ) ) {
        $q_config['url_info']['warnings'] = array();
    }
    if ( ! isset( $q_config['url_info']['messages'] ) ) {
        $q_config['url_info']['messages'] = array();
    }

    $errors   = &$q_config['url_info']['errors'];
    $warnings = &$q_config['url_info']['warnings'];
    $messages = &$q_config['url_info']['messages'];

    $q_config['posted']                  = array();
    $q_config['posted']['lang_props']    = array();
    $q_config['posted']['language_code'] = '';
    $q_config['posted']['original_lang'] = '';

    $language_code = &$q_config['posted']['language_code'];
    $lang_props    = &$q_config['posted']['lang_props'];
    $original_lang = &$q_config['posted']['original_lang'];

    // check for action
    if ( isset( $_POST['qtranslate_reset_all'] ) && isset( $_POST['qtranslate_reset_confirm'] ) ) {
        $messages[] = __( 'qTranslate has been reset.', 'qtranslate' );
    } elseif ( isset( $_POST['default_language'] ) ) {
        qtranxf_update_settings();
        // execute actions
        qtranxf_executeOnUpdate();
    }

    if ( isset( $_POST['original_lang'] ) ) {
        // validate form input
        $original_lang = sanitize_text_field( $_POST['original_lang'] );
        $lang          = sanitize_text_field( $_POST['language_code'] );
        if ( $_POST['language_na_message'] == '' ) {
            $errors[] = __( 'The Language must have a Not-Available Message!', 'qtranslate' );
        }
        if ( strlen( $_POST['language_locale'] ) < 2 ) {
            $errors[] = __( 'The Language must have a Locale!', 'qtranslate' );
        }
        if ( $_POST['language_name'] == '' ) {
            $errors[] = __( 'The Language must have a name!', 'qtranslate' );
        }
        if ( ! preg_match( '/^' . QTX_LANG_CODE_FORMAT . '$/', $lang ) ) {
            // TODO: still allow 2-letter upper case for existing values, keep only case-sensitive check once legacy fixed in DB
            if ( ! empty ( $original_lang ) && $lang === $original_lang && preg_match( '/^[a-z]{2}$/i', $lang ) ) {
                $warnings[] = sprintf( _( 'The 2-letter language code "%s" should be lower case (ISO 639-1). Upper case is still allowed for legacy codes but not for new entries.', 'qtranslate' ), $lang );
            } else {
                $errors[] = __( 'Invalid language code!', 'qtranslate' );
            }
        }
        $langs = array();
        qtranxf_load_languages( $langs );
        $language_names = $langs['language_name'];
        if ( empty( $errors ) ) {
            if ( empty( $original_lang ) ) {
                // new language
                if ( isset( $language_names[ $lang ] ) ) {
                    $errors[] = __( 'There is already a language with the same Language Code!', 'qtranslate' );
                }
            } else {
                // language update
                if ( $lang != $original_lang && isset( $language_names[ $lang ] ) ) {
                    $errors[] = __( 'There is already a language with the same Language Code!', 'qtranslate' );
                } else {
                    if ( $lang != $original_lang ) {
                        // remove old language
                        qtranxf_unsetLanguage( $langs, $original_lang );
                        qtranxf_unsetLanguage( $q_config, $original_lang );
                        // if language was enabled, set modified one to enabled too
                        foreach ( $q_config['enabled_languages'] as $k => $lng ) {
                            if ( $lng != $original_lang ) {
                                continue;
                            }
                            $q_config['enabled_languages'][ $k ] = $lng;
                            break;
                        }
                    }
                    if ( $original_lang == $q_config['default_language'] ) {
                        // was default, so set modified the default
                        $q_config['default_language'] = $lang;
                    }
                    if ( $q_config['language'] == $original_lang ) {
                        qtranxf_setLanguageAdmin( $lang );
                    }
                }
            }
        }

        $lang_props['language_name'] = sanitize_text_field( $_POST['language_name'] );
        $lang_props['flag']          = sanitize_text_field( $_POST['language_flag'] );
        $lang_props['locale']        = sanitize_text_field( $_POST['language_locale'] );
        $lang_props['locale_html']   = sanitize_text_field( $_POST['language_locale_html'] );
        $lang_props['date_format']   = sanitize_text_field( stripslashes( $_POST['language_date_format'] ) );
        $lang_props['time_format']   = sanitize_text_field( stripslashes( $_POST['language_time_format'] ) );
        $lang_props['not_available'] = wp_kses_post( stripslashes( $_POST['language_na_message'] ) );//allow valid HTML
        if ( empty( $errors ) ) {
            // everything is fine, insert language
            foreach ( $lang_props as $key => $value ) {
                $q_config[ $key ][ $lang ] = $value;
            }
            qtranxf_copyLanguage( $langs, $q_config, $lang );
            qtranxf_save_languages( $langs );
            qtranxf_enableLanguage( $lang );
            //qtranxf_update_config_header_css();   TODO keep or remove

            $original_lang = $lang;
            $s             = 'Custom Language Properties Used';
            $b             = 'I use the following language properties for ' . $lang . ':' . PHP_EOL . PHP_EOL;
            foreach ( $lang_props as $key => $value ) {
                $b .= $key . ': ' . $value . PHP_EOL;
            }
            $b          .= PHP_EOL . 'which should probably be used as a default preset on the plugin.' . PHP_EOL;
            $b          .= PHP_EOL . 'Thank you very much!' . PHP_EOL;
            $u          = 'qtranslateteam@gmail.com?subject=' . rawurlencode( $s ) . '&body=' . rawurlencode( $b );
            $messages[] = sprintf( __( 'The new language properties have been saved. If you think these properties should be the preset default, please %ssend email%s to the development team.', 'qtranslate' ), '<a href="mailto:' . $u . '"><strong>', '</strong></a>' );
        }
        if ( ! empty( $errors ) || isset( $_GET['edit'] ) ) {
            // get old values in the form
            $language_code = $lang;
        } else {
            // reset form for new language
            $lang_props    = array();
            $original_lang = '';
        }
    } elseif ( isset( $_GET['convert'] ) ) {
        // update language tags
        global $wpdb;
        $wpdb->show_errors();
        @set_time_limit( 0 );
        $cnt = 0;
        // this will not work correctly if set of languages is different
        foreach ( $q_config['enabled_languages'] as $lang ) {
            $cnt +=
                $wpdb->query( 'UPDATE ' . $wpdb->posts . ' set post_title = REPLACE(post_title, "[lang_' . $lang . ']","[:' . $lang . ']"),  post_content = REPLACE(post_content, "[lang_' . $lang . ']","[:' . $lang . ']")' );
            $wpdb->query( 'UPDATE ' . $wpdb->posts . ' set post_title = REPLACE(post_title, "[/lang_' . $lang . ']","[:]"),  post_content = REPLACE(post_content, "[/lang_' . $lang . ']","[:]")' );
        }
        if ( $cnt > 0 ) {
            $messages[] = sprintf( __( '%d database entries have been converted.', 'qtranslate' ), $cnt );
        } else {
            $messages[] = __( 'No database entry has been affected while processing the conversion request.', 'qtranslate' );
        }
    } elseif ( isset( $_GET['markdefault'] ) ) {
        // update language tags
        global $wpdb;
        $wpdb->show_errors();
        @set_time_limit( 0 );
        $result = $wpdb->get_results( 'SELECT ID, post_content, post_title, post_excerpt, post_type FROM ' . $wpdb->posts . ' WHERE post_status = \'publish\' AND  (post_type = \'post\' OR post_type = \'page\') AND NOT (post_content LIKE \'%<!--:-->%\' OR post_title LIKE \'%<!--:-->%\' OR post_content LIKE \'%![:!]%\' ESCAPE \'!\' OR post_title LIKE \'%![:!]%\' ESCAPE \'!\')' );
        if ( is_array( $result ) ) {
            $cnt_page = 0;
            $cnt_post = 0;
            foreach ( $result as $post ) {
                $title   = qtranxf_mark_default( $post->post_title );
                $content = qtranxf_mark_default( $post->post_content );
                $excerpt = qtranxf_mark_default( $post->post_excerpt );
                if ( $title == $post->post_title && $content == $post->post_content && $excerpt == $post->post_excerpt ) {
                    continue;
                }
                switch ( $post->post_type ) {
                    case 'post':
                        ++$cnt_post;
                        break;
                    case 'page':
                        ++$cnt_page;
                        break;
                }
                $wpdb->query( $wpdb->prepare( 'UPDATE ' . $wpdb->posts . ' set post_content = %s, post_title = %s, post_excerpt = %s WHERE ID = %d', $content, $title, $excerpt, $post->ID ) );
            }

            if ( $cnt_page > 0 ) {
                $messages[] = sprintf( __( '%d pages have been processed to set the default language.', 'qtranslate' ), $cnt_page );
            } else {
                $messages[] = __( 'No initially untranslated pages found to set the default language', 'qtranslate' );
            }

            if ( $cnt_post > 0 ) {
                $messages[] = sprintf( __( '%d posts have been processed to set the default language.', 'qtranslate' ), $cnt_post );
            } else {
                $messages[] = __( 'No initially untranslated posts found to set the default language.', 'qtranslate' );
            }

            $messages[] = __( 'Post types other than "post" or "page", as well as unpublished entries, will have to be adjusted manually as needed, since there is no common way to automate setting the default language otherwise.', 'qtranslate' );
        }
    } elseif ( isset( $_GET['edit'] ) ) {
        $lang = sanitize_text_field( $_GET['edit'] );
        if ( ! preg_match( '/^' . QTX_LANG_CODE_FORMAT . '$/', $lang ) ) {
            // TODO: still allow 2-letter upper case for existing values, keep only case-sensitive check once legacy fixed in DB
            if ( preg_match( '/^[a-z]{2}$/i', $lang ) ) {
                $warnings[] = sprintf( _( 'The 2-letter language code "%s" should be lower case (ISO 639-1). Upper case is still allowed for legacy codes but not for new entries.', 'qtranslate' ), $lang );
            } else {
                $errors[] = __( 'Invalid language code!', 'qtranslate' );
            }
        }
        $original_lang = $lang;
        $language_code = $lang;

        $langs = array();
        qtranxf_languages_configured( $langs );
        $lang_props['language_name'] = isset( $langs['language_name'][ $lang ] ) ? $langs['language_name'][ $lang ] : '';
        $lang_props['locale']        = isset( $langs['locale'][ $lang ] ) ? $langs['locale'][ $lang ] : '';
        $lang_props['locale_html']   = isset( $langs['locale_html'][ $lang ] ) ? $langs['locale_html'][ $lang ] : '';
        $lang_props['date_format']   = isset( $langs['date_format'][ $lang ] ) ? $langs['date_format'][ $lang ] : '';
        $lang_props['time_format']   = isset( $langs['time_format'][ $lang ] ) ? $langs['time_format'][ $lang ] : '';
        $lang_props['not_available'] = isset( $langs['not_available'][ $lang ] ) ? $langs['not_available'][ $lang ] : '';
        $lang_props['flag']          = isset( $langs['flag'][ $lang ] ) ? $langs['flag'][ $lang ] : '';
    } elseif ( isset( $_GET['delete'] ) ) {
        $lang = sanitize_text_field( $_GET['delete'] );
        $err  = qtranxf_deleteLanguage( $lang );
        if ( ! empty( $err ) ) {
            $errors[] = $err;
        }
    } elseif ( isset( $_GET['enable'] ) ) {
        $lang = sanitize_text_field( $_GET['enable'] );
        // enable validate
        if ( ! qtranxf_enableLanguage( $lang ) ) {
            $errors[] = __( 'Language is already enabled or invalid!', 'qtranslate' );
        }
    } elseif ( isset( $_GET['disable'] ) ) {
        $lang = sanitize_text_field( $_GET['disable'] );
        // enable validate
        if ( $lang == $q_config['default_language'] ) {
            $errors[] = __( 'Cannot disable Default Language!', 'qtranslate' );
        }
        if ( ! qtranxf_isEnabled( $lang ) ) {
            if ( ! isset( $q_config['language_name'][ $lang ] ) ) {
                $errors[] = __( 'No such language!', 'qtranslate' );
            }
        }
        // everything seems fine, disable language
        if ( empty( $errors ) && ! qtranxf_disableLanguage( $lang ) ) {
            $errors[] = __( 'Language is already disabled!', 'qtranslate' );
        }
    } elseif ( isset( $_GET['moveup'] ) ) {
        $lang      = sanitize_text_field( $_GET['moveup'] );
        $languages = qtranxf_getSortedLanguages();
        $msg       = __( 'No such language!', 'qtranslate' );
        foreach ( $languages as $key => $language ) {
            if ( $language != $lang ) {
                continue;
            }
            if ( $key == 0 ) {
                $msg = __( 'Language is already first!', 'qtranslate' );
                break;
            }
            $languages[ $key ]             = $languages[ $key - 1 ];
            $languages[ $key - 1 ]         = $language;
            $q_config['enabled_languages'] = $languages;
            $msg                           = __( 'New order saved.', 'qtranslate' );
            qtranxf_update_config_header_css();
            break;
        }
        $messages[] = $msg;
    } elseif ( isset( $_GET['movedown'] ) ) {
        $lang      = sanitize_text_field( $_GET['movedown'] );
        $languages = qtranxf_getSortedLanguages();
        $msg       = __( 'No such language!', 'qtranslate' );
        foreach ( $languages as $key => $language ) {
            if ( $language != $lang ) {
                continue;
            }
            if ( $key == sizeof( $languages ) - 1 ) {
                $msg = __( 'Language is already last!', 'qtranslate' );
                break;
            }
            $languages[ $key ]             = $languages[ $key + 1 ];
            $languages[ $key + 1 ]         = $language;
            $q_config['enabled_languages'] = $languages;
            $msg                           = __( 'New order saved.', 'qtranslate' );
            qtranxf_update_config_header_css();
            break;
        }
        $messages[] = $msg;
    }

    do_action( 'qtranslate_edit_config' );
    do_action_deprecated( 'qtranslate_editConfig', array(), '3.10.0', 'qtranslate_edit_config' );

    $everything_fine = ( ( isset( $_POST['submit'] ) || isset( $_GET['delete'] ) || isset( $_GET['enable'] ) || isset( $_GET['disable'] ) || isset( $_GET['moveup'] ) || isset( $_GET['movedown'] ) ) && empty( $errors ) );
    if ( $everything_fine ) {
        // settings might have changed, so save
        qtranxf_save_config();
        if ( empty( $messages ) ) {
            $messages[] = __( 'Options saved.', 'qtranslate' );
        }
    }

    if ( $q_config['auto_update_mo'] ) {
        if ( ! is_dir( WP_LANG_DIR ) || ! $ll = @fopen( trailingslashit( WP_LANG_DIR ) . 'qtranslate.test', 'a' ) ) {
            $errors[] = sprintf( __( 'Could not write to "%s", Gettext Databases could not be downloaded!', 'qtranslate' ), WP_LANG_DIR );
        } else {
            @fclose( $ll );
            @unlink( trailingslashit( WP_LANG_DIR ) . 'qtranslate.test' );
        }
    }
}

function qtranxf_resetConfig() {
    _deprecated_function( __FUNCTION__, '3.10.0', 'qtranxf_admin_reset_config' );
    qtranxf_reset_config();
}

function qtranxf_reset_config() {
    global $qtranslate_options;

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_POST['qtranslate_reset_admin_notices'] ) ) {
        delete_option( 'qtranslate_admin_notices' );
        qtranxf_add_message( __( 'Admin notices have been reset. You will see all applicable notices on admin pages and may dismiss them again.', 'qtranslate' ) );
    }

    if ( ! isset( $_POST['qtranslate_reset_all'] ) || ! isset( $_POST['qtranslate_reset_confirm'] ) ) {
        return;
    }

    // reset all settings
    foreach ( $qtranslate_options['front'] as $ops ) {
        foreach ( $ops as $nm => $def ) {
            delete_option( 'qtranslate_' . $nm );
        }
    }
    foreach ( $qtranslate_options['admin'] as $ops ) {
        foreach ( $ops as $nm => $def ) {
            delete_option( 'qtranslate_' . $nm );
        }
    }
    foreach ( $qtranslate_options['default_value'] as $nm => $def ) {
        delete_option( 'qtranslate_' . $nm );
    }
    foreach ( $qtranslate_options['languages'] as $nm => $opn ) {
        delete_option( $opn );
    }

    // internal private options not loaded by default
    delete_option( 'qtranslate_next_update_mo' );
    delete_option( 'qtranslate_next_thanks' );
    delete_option( QTX_OPTIONS_MODULES_STATE );
    delete_option( QTX_OPTIONS_MODULE_ACF );
    delete_option( QTX_OPTIONS_MODULE_SLUGS );

    // obsolete options
    delete_option( 'qtranslate_custom_pages' );
    delete_option( 'qtranslate_plugin_js_composer_off' );
    delete_option( 'qtranslate_widget_css' );
    delete_option( 'qtranslate_version' );
    delete_option( 'qtranslate_version_previous' );
    delete_option( 'qtranslate_versions' );
    delete_option( 'qtranslate_disable_header_css' );

    if ( isset( $_POST['qtranslate_reset_terms'] ) ) {
        delete_option( 'qtranslate_term_name' );
    }

    remove_filter( 'locale', 'qtranxf_localeForCurrentLanguage', 99 );
    qtranxf_reload_config();
    add_filter( 'locale', 'qtranxf_localeForCurrentLanguage', 99 );

    QTX_Admin_Module_Manager::update_modules_state();
}

add_action( 'qtranslate_save_config', 'qtranxf_reset_config', 20 );

function qtranxf_update_option( $nm, $default_value = null ) {
    global $q_config;
    if ( ! isset( $q_config[ $nm ] ) || ( ! is_integer( $q_config[ $nm ] ) && empty( $q_config[ $nm ] ) ) ) {
        delete_option( 'qtranslate_' . $nm );

        return;
    }
    if ( ! is_null( $default_value ) ) {
        if ( is_string( $default_value ) ) {
            if ( function_exists( $default_value ) ) {
                $default_value = call_user_func( $default_value );
            } elseif ( is_array( $q_config[ $nm ] ) ) {
                $default_value = preg_split( '/[\s,]+/', $default_value, -1, PREG_SPLIT_NO_EMPTY );
            }
        }
        if ( $default_value === $q_config[ $nm ] ) {
            delete_option( 'qtranslate_' . $nm );

            return;
        }
    }
    update_option( 'qtranslate_' . $nm, $q_config[ $nm ] );
}

function qtranxf_update_option_bool( $nm, $default_value = null ) {
    global $q_config, $qtranslate_options;
    if ( ! isset( $q_config[ $nm ] ) ) {
        delete_option( 'qtranslate_' . $nm );

        return;
    }
    if ( is_null( $default_value ) ) {
        if ( isset( $qtranslate_options['default_value'][ $nm ] ) ) {
            $default_value = $qtranslate_options['default_value'][ $nm ];
        } elseif ( isset( $qtranslate_options['front']['bool'][ $nm ] ) ) {
            $default_value = $qtranslate_options['front']['bool'][ $nm ];
        }
    }
    if ( ! is_null( $default_value ) && $default_value === $q_config[ $nm ] ) {
        delete_option( 'qtranslate_' . $nm );
    } else {
        update_option( 'qtranslate_' . $nm, $q_config[ $nm ] ? '1' : '0' );
    }
}

function qtranxf_saveConfig() {
    _deprecated_function( __FUNCTION__, '3.10.0', 'qtranxf_save_config' );
    qtranxf_save_config();
}

/**
 * saves entire configuration
 */
function qtranxf_save_config() {
    global $q_config, $qtranslate_options;

    qtranxf_update_option( 'default_language' );
    qtranxf_update_option( 'enabled_languages' );

    foreach ( $qtranslate_options['front']['int'] as $nm => $def ) {
        qtranxf_update_option( $nm, $def );
    }

    foreach ( $qtranslate_options['front']['bool'] as $nm => $def ) {
        qtranxf_update_option_bool( $nm, $def );
    }
    qtranxf_update_option_bool( 'qtrans_compatibility' );
    qtranxf_update_option_bool( 'disable_client_cookies' );

    foreach ( $qtranslate_options['front']['str'] as $nm => $def ) {
        qtranxf_update_option( $nm, $def );
    }

    foreach ( $qtranslate_options['front']['text'] as $nm => $def ) {
        qtranxf_update_option( $nm, $def );
    }

    foreach ( $qtranslate_options['front']['array'] as $nm => $def ) {
        qtranxf_update_option( $nm, $def );
    }
    qtranxf_update_option( 'domains' );

    update_option( 'qtranslate_ignore_file_types', implode( ',', $q_config['ignore_file_types'] ) );

    qtranxf_update_option( 'flag_location', qtranxf_flag_location_default() );

    qtranxf_update_option( 'filter_options', explode( ' ', QTX_FILTER_OPTIONS_DEFAULT ) );

    qtranxf_update_option( 'term_name' );//uniquely special case

    // save admin options
    foreach ( $qtranslate_options['admin']['int'] as $nm => $def ) {
        qtranxf_update_option( $nm, $def );
    }

    foreach ( $qtranslate_options['admin']['bool'] as $nm => $def ) {
        qtranxf_update_option_bool( $nm, $def );
    }

    foreach ( $qtranslate_options['admin']['str'] as $nm => $def ) {
        qtranxf_update_option( $nm, $def );
    }

    foreach ( $qtranslate_options['admin']['text'] as $nm => $def ) {
        qtranxf_update_option( $nm, $def );
    }

    foreach ( $qtranslate_options['admin']['array'] as $nm => $def ) {
        qtranxf_update_option( $nm, $def );
    }

    do_action( 'qtranslate_save_config' );
    do_action_deprecated( 'qtranslate_saveConfig', array(), '3.10.0', 'qtranslate_save_config' );
}

function qtranxf_reloadConfig() {
    _deprecated_function( __FUNCTION__, '3.10.0', 'qtranxf_reload_config' );
    qtranxf_reload_config();
}

function qtranxf_reload_config() {
    global $q_config;
    $url_info = isset( $q_config['url_info'] ) ? $q_config['url_info'] : null;
    qtranxf_del_conf_filters();
    qtranxf_load_config();
    qtranxf_admin_load_config();
    if ( $url_info ) {
        $q_config['url_info'] = $url_info;
        if ( isset( $q_config['url_info']['language'] ) ) {
            $q_config['language'] = $q_config['url_info']['language'];
        }
        if ( ! qtranxf_isEnabled( $q_config['language'] ) ) {
            $q_config['language'] = $q_config['default_language'];
        }
    }
    qtranxf_load_option_qtrans_compatibility();
}

function qtranxf_updateSetting( $var, $type = QTX_STRING, $def = null ) {
    _deprecated_function( __FUNCTION__, '3.10.0', 'qtranxf_update_setting' );
    qtranxf_update_setting( $var, $type, $def );
}

function qtranxf_update_setting( $var, $type = QTX_STRING, $def = null ) {
    global $q_config, $qtranslate_options;
    if ( ! isset( $_POST['submit'] ) ) {
        return false;
    }
    // Require POST data except for booleans, as unchecked boxes are not sent with the form.
    if ( ! isset( $_POST[ $var ] ) && $type != QTX_BOOLEAN && $type != QTX_BOOLEAN_SET ) {
        return false;
    }

    if ( is_null( $def ) && isset( $qtranslate_options['default_value'][ $var ] ) ) {
        $def = $qtranslate_options['default_value'][ $var ];
    }
    if ( is_string( $def ) && function_exists( $def ) ) {
        $def = call_user_func( $def );
    }
    switch ( $type ) {
        case QTX_URL:
        case QTX_LANGUAGE:
        case QTX_STRING:
            $val = sanitize_text_field( $_POST[ $var ] );
            if ( $type == QTX_URL ) {
                $val = trailingslashit( $val );
            } else if ( $type == QTX_LANGUAGE && ! qtranxf_isEnabled( $val ) ) {
                return false;
            }
            if ( isset( $q_config[ $var ] ) ) {
                // empty val means reset so we shouldn't skip the default value that could be different
                if ( ! empty( $val ) && $q_config[ $var ] === $val ) {
                    return false;
                }
            } elseif ( ! is_null( $def ) ) {
                if ( empty( $val ) || $def === $val ) {
                    return false;
                }
            }
            if ( empty( $val ) && $def ) {
                $val = $def;
            }
            $q_config[ $var ] = $val;
            qtranxf_update_option( $var, $def );

            return true;

        case QTX_TEXT:
            $val = $_POST[ $var ];
            // standardize multi-line string
            $lns = preg_split( '/\r?\n\r?/', $val );
            foreach ( $lns as $key => $ln ) {
                $lns[ $key ] = sanitize_text_field( $ln );
            }
            $val = implode( PHP_EOL, $lns );
            if ( isset( $q_config[ $var ] ) ) {
                // empty val means reset so we shouldn't skip the default value that could be different
                if ( ! empty( $val ) && $q_config[ $var ] === $val ) {
                    return false;
                }
            } elseif ( ! is_null( $def ) ) {
                if ( empty( $val ) || $def === $val ) {
                    return false;
                }
            }
            if ( empty( $val ) && $def ) {
                $val = $def;
            }
            $q_config[ $var ] = $val;
            qtranxf_update_option( $var, $def );

            return true;

        case QTX_ARRAY:
            $val = isset( $_POST[ $var ] ) ? $_POST[ $var ] : array();
            if ( ! is_array( $val ) ) {
                $val = sanitize_text_field( $val );
                $val = preg_split( '/[\s,]+/', $val, -1, PREG_SPLIT_NO_EMPTY );
            }
            if ( empty( $val ) ) {
                if ( is_string( $def ) ) {
                    $val = preg_split( '/[\s,]+/', $def, -1, PREG_SPLIT_NO_EMPTY );
                } else if ( is_array( $def ) ) {
                    $val = $def;  // TODO: why replace all the array? Check if shouldn't it be merged with default.
                }
            }
            if ( isset( $q_config[ $var ] ) && qtranxf_array_compare( $q_config[ $var ], $val ) ) {
                return false;
            }
            $q_config[ $var ] = $val;
            qtranxf_update_option( $var, $def );

            return true;

        case QTX_BOOLEAN_SET:
            $val = isset( $_POST[ $var ] ) ? $_POST[ $var ] : array();
            // Convert all input values to boolean types
            foreach ( $val as &$value ) {
                $value = (bool) $value;
            }
            // Input checkboxes that are unchecked are not in $_POST so default values are used to detect missing keys.
            if ( isset ( $def ) ) {
                foreach ( array_keys( $def ) as $key ) {
                    if ( ! array_key_exists( $key, $val ) ) {
                        $val[ $key ] = false;   // Ignore the default value, enforce `false` for that key.
                    }
                }
            }
            if ( isset( $q_config[ $var ] ) && qtranxf_array_compare( $q_config[ $var ], $val ) ) {
                return false;
            }
            $q_config[ $var ] = $val;
            qtranxf_update_option( $var, $def );

            return true;

        case QTX_BOOLEAN:
            if ( isset( $_POST[ $var ] ) && $_POST[ $var ] == 1 ) {
                if ( $q_config[ $var ] ) {
                    return false;
                }
                $q_config[ $var ] = true;
            } else {
                if ( ! $q_config[ $var ] ) {
                    return false;
                }
                $q_config[ $var ] = false;
            }
            qtranxf_update_option_bool( $var, $def );

            return true;

        case QTX_INTEGER:
            $val = sanitize_text_field( $_POST[ $var ] );
            $val = intval( $val );
            if ( $q_config[ $var ] == $val ) {
                return false;
            }
            $q_config[ $var ] = $val;
            qtranxf_update_option( $var, $def );

            return true;
    }

    return false;
}

/**
 * Updates 'admin_config' and 'front_config' from *.json files listed in option 'config_files', and option 'custom_i18n_config'.
 * @since 3.3.1
 */
function qtranxf_update_i18n_config() {
    global $q_config;
    if ( ! isset( $q_config['config_files'] ) ) {
        global $qtranslate_options;
        qtranxf_admin_set_default_options( $qtranslate_options );
        qtranxf_load_option_array( 'config_files', $qtranslate_options['admin']['array']['config_files'] );
        qtranxf_load_option_array( 'custom_i18n_config', $qtranslate_options['admin']['array']['custom_i18n_config'] );
    }
    $json_files         = $q_config['config_files'];
    $custom_i18n_config = $q_config['custom_i18n_config'];
    $cfg                = qtranxf_load_config_all( $json_files, $custom_i18n_config );
    // TODO clarify admin/front contexts before call
    if ( isset( $q_config['admin_config'] ) && $q_config['admin_config'] !== $cfg['admin-config'] ) {
        $q_config['admin_config'] = $cfg['admin-config'];
        qtranxf_update_option( 'admin_config' );
    }
    if ( isset( $q_config['front_config'] ) && $q_config['front_config'] !== $cfg['front-config'] ) {
        $q_config['front_config'] = $cfg['front-config'];
        qtranxf_update_option( 'front_config' );
    }
}

function qtranxf_updateSettingFlagLocation( $name ) {
    _deprecated_function( __FUNCTION__, '3.10.0', 'qtranxf_update_setting_flag_location' );
    qtranxf_update_setting_flag_location( $name );
}

function qtranxf_update_setting_flag_location( $nm ) {
    global $q_config;
    if ( ! isset( $_POST['submit'] ) ) {
        return false;
    }
    if ( ! isset( $_POST[ $nm ] ) ) {
        return false;
    }
    $flag_location = untrailingslashit( sanitize_text_field( $_POST[ $nm ] ) );
    if ( empty( $flag_location ) ) {
        $flag_location = qtranxf_flag_location_default();
    }
    $flag_location = trailingslashit( $flag_location );
    if ( ! file_exists( trailingslashit( WP_CONTENT_DIR ) . $flag_location ) ) {
        return null;
    }
    if ( $flag_location != $q_config[ $nm ] ) {
        $q_config[ $nm ] = $flag_location;
        if ( $flag_location == qtranxf_flag_location_default() ) {
            delete_option( 'qtranslate_' . $nm );
        } else {
            update_option( 'qtranslate_' . $nm, $flag_location );
        }
    }

    return true;
}

function qtranxf_updateSettingIgnoreFileTypes( $name ) {
    _deprecated_function( __FUNCTION__, '3.10.0', 'qtranxf_update_setting_ignore_file_types' );
    qtranxf_update_setting_ignore_file_types( $name );
}

function qtranxf_update_setting_ignore_file_types( $name ) {
    global $q_config;
    if ( ! isset( $_POST['submit'] ) ) {
        return false;
    }
    if ( ! isset( $_POST[ $name ] ) ) {
        return false;
    }
    $posted  = preg_split( '/[\s,]+/', strtolower( sanitize_text_field( $_POST[ $name ] ) ), -1, PREG_SPLIT_NO_EMPTY );
    $ignored = explode( ',', QTX_IGNORE_FILE_TYPES );
    if ( is_array( $posted ) ) {
        foreach ( $posted as $posted_value ) {
            if ( empty( $posted_value ) ) {
                continue;
            }
            if ( in_array( $posted_value, $ignored ) ) {
                continue;
            }
            $ignored[] = $posted_value;
        }
    }
    if ( qtranxf_array_compare( $q_config[ $name ], $ignored ) ) {
        return false;
    }
    $q_config[ $name ] = $ignored;
    update_option( 'qtranslate_' . $name, implode( ',', $ignored ) );

    return true;
}

function qtranxf_parse_post_type_excluded() {
    if ( ! isset( $_POST['submit'] ) ) {
        return false;
    }
    if ( ! isset( $_POST['post_types_all'] ) ) {
        return false;
    }
    if ( ! is_array( $_POST['post_types_all'] ) ) {
        return false;
    }
    $post_type_excluded = array();
    foreach ( $_POST['post_types_all'] as $post_type => $value ) {
        if ( isset( $_POST['post_types'][ $post_type ] ) ) {
            continue;
        }
        $post_type_excluded[] = $post_type;
    }
    unset( $_POST['post_types'] );
    unset( $_POST['post_types_all'] );
    $_POST['post_type_excluded'] = $post_type_excluded;

    return true;
}

function qtranxf_updateSettings() {
    _deprecated_function( __FUNCTION__, '3.10.0', 'qtranxf_update_settings' );
    qtranxf_update_settings();
}

function qtranxf_update_settings() {
    global $qtranslate_options, $q_config;

    $errors = &$q_config['url_info']['errors'];

    // update front settings

    // opportunity to prepare special custom settings update on sub-plugins
    do_action( 'qtranslate_update_settings_pre' );

    // special cases handling for front options
    qtranxf_update_setting( 'default_language', QTX_LANGUAGE );
    // enabled_languages are not changed at this place
    qtranxf_update_setting_flag_location( 'flag_location' );
    qtranxf_update_setting_ignore_file_types( 'ignore_file_types' );
    $_POST['language_name_case'] = isset( $_POST['camel_case'] ) ? '0' : '1';
    // special cases handling for front options - end

    foreach ( $qtranslate_options['front']['int'] as $name => $default ) {
        qtranxf_update_setting( $name, QTX_INTEGER, $default );
    }

    foreach ( $qtranslate_options['front']['bool'] as $name => $default ) {
        qtranxf_update_setting( $name, QTX_BOOLEAN, $default );
    }
    qtranxf_update_setting( 'qtrans_compatibility', QTX_BOOLEAN );

    foreach ( $qtranslate_options['front']['str'] as $name => $default ) {
        qtranxf_update_setting( $name, QTX_STRING, $default );
    }

    foreach ( $qtranslate_options['front']['text'] as $name => $default ) {
        qtranxf_update_setting( $name, QTX_TEXT, $default );
    }

    foreach ( $qtranslate_options['front']['array'] as $name => $default ) {
        qtranxf_update_setting( $name, QTX_ARRAY, $default );
    }

    qtranxf_update_setting( 'filter_options', QTX_ARRAY );

    switch ( $q_config['url_mode'] ) {
        case QTX_URL_DOMAIN:
        case QTX_URL_DOMAINS:
            $q_config['disable_client_cookies'] = true;
            break;
        case QTX_URL_QUERY:
        case QTX_URL_PATH:
        default:
            qtranxf_update_setting( 'disable_client_cookies', QTX_BOOLEAN );
            break;
    }

    $domains = isset( $q_config['domains'] ) ? $q_config['domains'] : array();
    foreach ( $q_config['enabled_languages'] as $lang ) {
        $id = 'language_domain_' . $lang;
        if ( ! isset( $_POST[ $id ] ) ) {
            continue;
        }
        $domain           = preg_replace( '#^/*#', '', untrailingslashit( trim( $_POST[ $id ] ) ) );
        $domains[ $lang ] = $domain;
    }
    if ( ! empty( $domains ) && ( ! isset( $q_config['domains'] ) || ! qtranxf_array_compare( $q_config['domains'], $domains ) ) ) {
        $q_config['domains'] = $domains;
        qtranxf_update_option( 'domains' );
    }

    // update admin settings

    // special cases handling for admin options
    if ( isset( $_POST['json_config_files'] ) ) {
        // verify that files are loadable
        $json_config_files_post = sanitize_text_field( stripslashes( $_POST['json_config_files'] ) );
        $json_files             = preg_split( '/[\s,]+/', $json_config_files_post, -1, PREG_SPLIT_NO_EMPTY );
        if ( empty( $json_files ) ) {
            $_POST['config_files'] = array();
            unset( $_POST['json_config_files'] );
        } else {
            $json_config_files          = implode( PHP_EOL, $json_files );
            $_POST['json_config_files'] = $json_config_files;
            $nerr                       = isset( $q_config['url_info']['errors'] ) ? count( $q_config['url_info']['errors'] ) : 0;
            qtranxf_load_config_files( $json_files );
            if ( ! empty( $q_config['url_info']['errors'] ) && $nerr != count( $q_config['url_info']['errors'] ) ) {//new errors occurred
                remove_action( 'admin_notices', 'qtranxf_admin_notices_errors' );
                if ( $json_files == $q_config['config_files'] ) {
                    // option is not changed, apparently something happened to files, then make the error permanent
                    update_option( 'qtranslate_config_errors', array_slice( $q_config['url_info']['errors'], $nerr ) );
                }
            } else {
                $_POST['config_files'] = $json_config_files;
                unset( $_POST['json_config_files'] );
                delete_option( 'qtranslate_config_errors' );
            }
        }
    }

    if ( isset( $_POST['json_custom_i18n_config'] ) ) {
        // verify that JSON string can be parsed
        $cfg_json = sanitize_text_field( stripslashes( $_POST['json_custom_i18n_config'] ) );
        if ( empty( $cfg_json ) ) {
            $_POST['custom_i18n_config'] = array();
        } else {
            $cfg = json_decode( $cfg_json, true );
            if ( $cfg ) {
                $_POST['custom_i18n_config'] = $cfg;
                unset( $_POST['json_custom_i18n_config'] );
            } else {
                $_POST['json_custom_i18n_config'] = stripslashes( $_POST['json_custom_i18n_config'] );
                $errors[]                         = sprintf( __( 'Cannot parse JSON code in the field "%s".', 'qtranslate' ), __( 'Custom Configuration', 'qtranslate' ) );
            }
        }
    }

    if ( $_POST['highlight_mode'] != QTX_HIGHLIGHT_MODE_CUSTOM_CSS ) {
        $_POST['highlight_mode_custom_css'] = '';
    }

    qtranxf_parse_post_type_excluded();
    // special cases handling for admin options - end

    do_action( 'qtranslate_update_settings_admin' );

    foreach ( $qtranslate_options['admin']['int'] as $name => $default ) {
        qtranxf_update_setting( $name, QTX_INTEGER, $default );
    }

    foreach ( $qtranslate_options['admin']['bool'] as $name => $default ) {
        qtranxf_update_setting( $name, QTX_BOOLEAN, $default );
    }

    foreach ( $qtranslate_options['admin']['str'] as $name => $default ) {
        qtranxf_update_setting( $name, QTX_STRING, $default );
    }

    foreach ( $qtranslate_options['admin']['text'] as $name => $default ) {
        qtranxf_update_setting( $name, QTX_TEXT, $default );
    }

    foreach ( $qtranslate_options['admin']['array'] as $name => $default ) {
        qtranxf_update_setting( $name, QTX_ARRAY, $default );
    }

    if ( empty( $_POST['json_config_files'] ) ) {
        qtranxf_update_i18n_config(); // only update if config files parsed successfully
    }

    $q_config['i18n-cache'] = array(); // clear i18n-config cache

    qtranxf_update_setting( 'admin_enabled_modules', QTX_BOOLEAN_SET, $qtranslate_options['admin']['admin_enabled_modules'] );

    QTX_Admin_Module_Manager::update_modules_state();

    // opportunity to update special custom settings on sub-plugins
    do_action( 'qtranslate_update_settings' );
}

function qtranxf_executeOnUpdate() {
    global $q_config;
    $messages = &$q_config['url_info']['messages'];

    if ( isset( $_POST['update_mo_now'] ) && $_POST['update_mo_now'] == '1' ) {
        $result = qtranxf_update_gettext_databases( true );
        if ( $result === 0 ) {
            $messages[] = __( 'Gettext databases updated.', 'qtranslate' );
        }
    }

    // ==== import/export msg was here
    if ( isset( $_POST['convert_database'] ) ) {
        require_once QTRANSLATE_DIR . '/src/admin/qtx_admin_utils_db.php';
        $msg = qtranxf_convert_database( $_POST['convert_database'] );
        if ( $msg ) {
            $messages[] = $msg;
        }
    }

    if ( isset( $_POST['qtranslate_import_slugs_migrate'] ) && $_POST['qtranslate_import_slugs_migrate'] ) {
        require_once QTRANSLATE_DIR . '/modules/slugs/admin/slugs-migrate-qts.php';
        $db_commit  = isset( $_POST['qtranslate_import_slugs_confirm'] ) && $_POST['qtranslate_import_slugs_confirm'];
        $messages[] = qtranxf_slugs_migrate_qts_data( $db_commit );
    }
}

function qtranxf_mark_default( $text ) {
    global $q_config;
    $blocks = qtranxf_get_language_blocks( $text );
    if ( count( $blocks ) > 1 ) {
        return $text; // already has other languages
    }
    $content = array();
    foreach ( $q_config['enabled_languages'] as $language ) {
        if ( $language == $q_config['default_language'] ) {
            $content[ $language ] = $text;
        } else {
            $content[ $language ] = '';
        }
    }

    return qtranxf_join_b( $content );
}

// Allow 3rd-party to include additional code here
do_action( 'qtranslate_admin_options_update.php' );
