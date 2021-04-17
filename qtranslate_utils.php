<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( WP_DEBUG ) {
    require_once( QTRANSLATE_DIR . '/inc/qtx_dbg.php' );
}

/**
 * Default domain translation for strings already translated by WordPress.
 * Use of this function prevents xgettext, poedit and other translating parsers from including the string that does not need translation.
 */
function qtranxf_translate_wp( $string ) {
    return __( $string );
}

/**
 * @since 3.3.8.8
 */
function qtranxf_plugin_basename() {
    _deprecated_function( __FUNCTION__, '3.7.3', 'plugin_basename( QTRANSLATE_FILE )' );

    return plugin_basename( QTRANSLATE_FILE );
}

/**
 * @since 3.3.2
 */
function qtranxf_plugin_dirname() {
    _deprecated_function( __FUNCTION__, '3.7.3', 'dirname( QTRANSLATE_DIR )' );

    return dirname( QTRANSLATE_DIR );
}

/**
 * Compose path to a plugin folder relative to WP_CONTENT_DIR.
 * Takes into account linked folders in the path.
 * Works for plugin paths only. No trailing slash in the return string.
 * It may return absolute path to plugin folder in case content and plugin directories are on different devices.
 *
 * @param string $plugin is path to plugin file, like the one coming from __FILE__.
 *
 * @return string path to plugin folder relative to WP_CONTENT_DIR.
 * @since 3.4.5
 */
function qtranxf_dir_from_wp_content( $plugin ) {
    global $wp_plugin_paths;
    $plugin_realpath = wp_normalize_path( dirname( realpath( $plugin ) ) );
    $plugin_dir      = $plugin_realpath;
    foreach ( $wp_plugin_paths as $dir => $realdir ) {
        if ( $plugin_realpath != $realdir ) {
            continue;
        }
        $plugin_dir = $dir;
        break;
    }
    $plugin_len = strlen( $plugin_dir );

    $content_dir = trailingslashit( wp_normalize_path( WP_CONTENT_DIR ) );
    $content_len = strlen( $content_dir );

    $i = 0;
    while ( $i < $plugin_len && $i < $content_len && $plugin_dir[ $i ] == $content_dir[ $i ] ) {
        ++ $i;
    }
    if ( $i == $content_len ) {
        return substr( $plugin_dir, $content_len );
    }
    if ( $i == 0 ) {
        return $plugin_dir; // absolute path
    }

    $content_dir = substr( $content_dir, $i );
    $plugin_dir  = substr( $plugin_dir, $i );
    for ( $i = substr_count( $content_dir, '/' ); -- $i >= 0; ) {
        $plugin_dir = '../' . $plugin_dir;
    }

    return $plugin_dir;
}

/**
 * Return path to QTX plugin folder relative to WP_CONTENT_DIR.
 * Uses qtranxf_dir_from_wp_content
 * @since 3.4
 * @since 3.4.5 modified for multisite.
 */
function qtranxf_plugin_dirname_from_wp_content() {
    static $dirname;
    if ( ! $dirname ) {
        $dirname = qtranxf_dir_from_wp_content( QTRANSLATE_FILE );
    }

    return $dirname;
}

function qtranxf_parseURL( $url ) {
    // this is not the same as native parse_url and so it is in use
    // it should also work quicker than native parse_url, so we should keep it?
    preg_match( '!(?:(\w+)://)?(?:(\w+):(\w+)@)?([^/:?#]+)?(?::(\d*))?([^#?]+)?(?:\?([^#]+))?(?:#(.+$))?!', $url, $out );
    $result = array();
    if ( ! empty( $out[1] ) ) {
        $result['scheme'] = $out[1];
    }
    if ( ! empty( $out[2] ) ) {
        $result['user'] = $out[2];
    }
    if ( ! empty( $out[3] ) ) {
        $result['pass'] = $out[3];
    }
    if ( ! empty( $out[4] ) ) {
        $result['host'] = $out[4];
    }
    if ( ! empty( $out[6] ) ) {
        $result['path'] = $out[6];
    }
    if ( ! empty( $out[7] ) ) {
        $result['query'] = $out[7];
    }
    if ( ! empty( $out[8] ) ) {
        $result['fragment'] = $out[8];
    }
    if ( ! empty( $out[5] ) ) {
        $result['host'] .= ':' . $out[5];
    }

    return $result;
}

/**
 * @since 3.2.8
 */
function qtranxf_buildURL( $urlinfo, $homeinfo ) {
    if ( empty( $urlinfo['host'] ) ) {
        $url = ''; // relative path stays relative
    } else {
        $url = ( empty( $urlinfo['scheme'] ) ? $homeinfo['scheme'] : $urlinfo['scheme'] ) . '://';
        if ( ! empty( $urlinfo['user'] ) ) {
            $url .= $urlinfo['user'];
            if ( ! empty( $urlinfo['pass'] ) ) {
                $url .= ':' . $urlinfo['pass'];
            }
            $url .= '@';
        } elseif ( ! empty( $homeinfo['user'] ) ) {
            $url .= $homeinfo['user'];
            if ( ! empty( $homeinfo['pass'] ) ) {
                $url .= ':' . $homeinfo['pass'];
            }
            $url .= '@';
        }
        $url .= empty( $urlinfo['host'] ) ? $homeinfo['host'] : $urlinfo['host'];
    }
    if ( ! empty( $urlinfo['path-base'] ) ) {
        $url .= $urlinfo['path-base'];
    }
    if ( ! empty( $urlinfo['wp-path'] ) ) {
        $url .= $urlinfo['wp-path'];
    }
    if ( ! empty( $urlinfo['query'] ) ) {
        $url .= '?' . $urlinfo['query'];
    }
    if ( ! empty( $urlinfo['fragment'] ) ) {
        $url .= '#' . $urlinfo['fragment'];
    }

    return $url;
}

/**
 * @since 3.2.8 Copies the data needed for qtranxf_buildURL and qtranxf_url_set_language
 */
function qtranxf_copy_url_info( $urlinfo ) {
    $copy = array();
    if ( isset( $urlinfo['scheme'] ) ) {
        $copy['scheme'] = $urlinfo['scheme'];
    }
    if ( isset( $urlinfo['user'] ) ) {
        $copy['user'] = $urlinfo['user'];
    }
    if ( isset( $urlinfo['pass'] ) ) {
        $copy['pass'] = $urlinfo['pass'];
    }
    if ( isset( $urlinfo['host'] ) ) {
        $copy['host'] = $urlinfo['host'];
    }
    if ( isset( $urlinfo['path-base'] ) ) {
        $copy['path-base'] = $urlinfo['path-base'];
    }
    if ( isset( $urlinfo['wp-path'] ) ) {
        $copy['wp-path'] = $urlinfo['wp-path'];
    }
    if ( isset( $urlinfo['query'] ) ) {
        $copy['query'] = $urlinfo['query'];
    }
    if ( isset( $urlinfo['fragment'] ) ) {
        $copy['fragment'] = $urlinfo['fragment'];
    }
    if ( isset( $urlinfo['query_amp'] ) ) {
        $copy['query_amp'] = $urlinfo['query_amp'];
    }

    return $copy;
}

function qtranxf_get_address_info( $url ) {
    $info = qtranxf_parseURL( $url );
    if ( ! isset( $info['path'] ) ) {
        $info['path'] = '';
    }

    return $info;
}

function qtranxf_get_home_info() {
    static $home_info;
    if ( ! $home_info ) {
        $url       = get_option( 'home' );
        $home_info = qtranxf_get_address_info( $url );
    }

    return $home_info;
}

function qtranxf_get_site_info() {
    static $site_info;
    if ( ! $site_info ) {
        $url       = get_option( 'siteurl' );
        $site_info = qtranxf_get_address_info( $url );
    }

    return $site_info;
}

function qtranxf_get_url_info( $url ) {
    $urlinfo = qtranxf_parseURL( $url );
    qtranxf_complete_url_info( $urlinfo );
    qtranxf_complete_url_info_path( $urlinfo );

    return $urlinfo;
}

/**
 * Complete urlinfo with 'path-base' according to home and site info.
 * If they differ, 'doing_front_end' might be set.
 *
 * @param array $urlinfo
 */
function qtranxf_complete_url_info( &$urlinfo ) {
    if ( ! isset( $urlinfo['path'] ) ) {
        $urlinfo['path'] = '';
    }
    $path      = $urlinfo['path'];
    $home_info = qtranxf_get_home_info();
    $site_info = qtranxf_get_site_info();
    $home_path = $home_info['path'];
    $site_path = $site_info['path'];

    if ( $home_path === $site_path ) {
        if ( qtranxf_startsWith( $path, $home_path ) ) {
            $urlinfo['path-base'] = $home_path;
        }
    } else {
        if ( strlen( $home_path ) < strlen( $site_path ) ) {
            if ( qtranxf_startsWith( $path, $site_path ) ) {
                $urlinfo['path-base']       = $site_path;
                $urlinfo['doing_front_end'] = false;
            } elseif ( qtranxf_startsWith( $path, $home_path ) ) {
                $urlinfo['path-base']       = $home_path;
                $urlinfo['doing_front_end'] = true;
            }
        } else {
            if ( qtranxf_startsWith( $path, $home_path ) ) {
                $urlinfo['path-base']       = $home_path;
                $urlinfo['doing_front_end'] = true;
            } elseif ( qtranxf_startsWith( $path, $site_path ) ) {
                $urlinfo['path-base']       = $site_path;
                $urlinfo['doing_front_end'] = false;
            }
        }
    }
}

/**
 * Complete urlinfo with 'wp-path'.
 * If 'wp-path' is not set, this means url does not belong to this WP installation.
 *
 * @param array $urlinfo
 *
 * @since 3.2.8
 */
function qtranxf_complete_url_info_path( &$urlinfo ) {
    if ( isset( $urlinfo['path-base'] ) ) {
        if ( empty( $urlinfo['path-base'] ) ) {
            $urlinfo['wp-path'] = $urlinfo['path'];
        } elseif ( ! empty( $urlinfo['path'] ) && qtranxf_startsWith( $urlinfo['path'], $urlinfo['path-base'] ) ) {
            $base_length = strlen( $urlinfo['path-base'] );
            if ( isset( $urlinfo['path'][ $base_length ] ) ) {
                if ( $urlinfo['path'][ $base_length ] === '/' ) {
                    $urlinfo['wp-path'] = substr( $urlinfo['path'], $base_length );
                }
            } else {
                $urlinfo['wp-path'] = '';
            }
        }
    }
}

/**
 * Simplified version of WP's add_query_arg
 * @since 3.2.8
 */
function qtranxf_add_query_arg( &$query, $key_value ) {
    if ( empty( $query ) ) {
        $query = $key_value;
    } else {
        $query .= '&' . $key_value;
    }
}

/**
 * Simplified version of WP's remove_query_arg
 * @since 3.2.8
 */
function qtranxf_del_query_arg( &$query, $key ) {
    while ( preg_match( '/(&|&amp;|&#038;|^)(' . $key . '=[^&]+)(&|&amp;|&#038;|$)/i', $query, $matches ) ) {
        $pos   = strpos( $query, $matches[2] );
        $count = strlen( $matches[2] );
        if ( ! empty( $matches[1] ) ) {
            $len   = strlen( $matches[1] );
            $pos   -= $len;
            $count += $len;
        } elseif ( ! empty( $matches[3] ) ) {
            $len   = strlen( $matches[3] );
            $count += $len;
        }
        $query = substr_replace( $query, '', $pos, $count );
    }
}

/*
 * @since 2.3.8 simplified version of esc_url
 */
function qtranxf_sanitize_url( $url ) {
    $url   = preg_replace( '|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\[\]\\x80-\\xff]|i', '', $url );
    $strip = array( '%0d', '%0a', '%0D', '%0A' );
    do {
        $url = str_replace( $strip, '', $url, $count );
    } while ( $count );

    return $url;
}

function qtranxf_insertDropDownElement( $language, $url, $id ) {
    global $q_config;
    $html = "
		var sb = document.getElementById('qtranxs_select_" . $id . "');
		var o = document.createElement('option');
		var l = document.createTextNode('" . $q_config['language_name'][ $language ] . "');
		";
    if ( $q_config['language'] == $language ) {
        $html .= "o.selected = 'selected';";
    }
    $html .= "
		o.value = '" . addslashes( htmlspecialchars_decode( $url, ENT_NOQUOTES ) ) . "';
		o.appendChild(l);
		sb.appendChild(o);
		";

    return $html;
}

function qtranxf_external_host_ex( $host, $homeinfo ) {
    global $q_config;

    switch ( $q_config['url_mode'] ) {
        case QTX_URL_QUERY:
        case QTX_URL_PATH:
            return $homeinfo['host'] != $host;
        case QTX_URL_DOMAIN:
            return ! qtranxf_endsWith( $host, $homeinfo['host'] );
        case QTX_URL_DOMAINS:
            foreach ( $q_config['domains'] as $host_item ) {
                if ( $host_item == $host ) {
                    return false;
                }
            }
            if ( $homeinfo['host'] == $host ) {
                return false;
            }

            return true;
        default:
            return true;
    }
}

function qtranxf_external_host( $host ) {
    $homeinfo = qtranxf_get_home_info();

    return qtranxf_external_host_ex( $host, $homeinfo );
}

function qtranxf_isMultilingual( $str ) {
    $lang_code = QTX_LANG_CODE_FORMAT;

    return preg_match( "/<!--:$lang_code-->|\[:$lang_code]|{:$lang_code}/im", $str );
}

function qtranxf_is_multilingual_deep( $value ) {
    if ( is_string( $value ) ) {
        return qtranxf_isMultilingual( $value );
    } else if ( is_array( $value ) ) {
        foreach ( $value as $item ) {
            if ( qtranxf_is_multilingual_deep( $item ) ) {
                return true;
            }
        }
    } else if ( is_object( $value ) || $value instanceof __PHP_Incomplete_Class ) {
        foreach ( get_object_vars( $value ) as $item ) {
            if ( qtranxf_is_multilingual_deep( $item ) ) {
                return true;
            }
        }
    }

    return false;
}

function qtranxf_getLanguage() {
    global $q_config;

    return $q_config['language'];
}

function qtranxf_getLanguageDefault() {
    global $q_config;

    return $q_config['default_language'];
}

/**
 * @since 3.4.5.4 - return language name in native language, former qtranxf_getLanguageName.
 */
function qtranxf_getLanguageNameNative( $lang = '' ) {
    global $q_config;
    if ( empty( $lang ) ) {
        $lang = $q_config['language'];
    }

    return $q_config['language_name'][ $lang ];
}

/**
 * @since 3.4.5.4 - return language name in active language, if available, otherwise the name in native language.
 */
function qtranxf_getLanguageName( $lang = '' ) {
    global $q_config, $l10n;
    if ( empty( $lang ) ) {
        return $q_config['language_name'][ $q_config['language'] ];
    }
    if ( isset( $q_config['language-names'][ $lang ] ) ) {
        return $q_config['language-names'][ $lang ];
    }
    if ( ! isset( $l10n['language-names'] ) ) {
        // not loaded by default, since this place should not be hit frequently
        $locale = $q_config['locale'][ $q_config['language'] ];
        if ( ! load_textdomain( 'language-names', QTRANSLATE_DIR . '/lang/language-names/language-' . $locale . '.mo' ) ) {
            if ( $locale[2] == '_' ) {
                $locale = substr( $locale, 0, 2 );
                load_textdomain( 'language-names', QTRANSLATE_DIR . '/lang/language-names/language-' . $locale . '.mo' );
            }
        }
    }
    $translations = get_translations_for_domain( 'language-names' );
    $locale       = $q_config['locale'][ $lang ];
    while ( ! isset( $translations->entries[ $locale ] ) ) {
        if ( $locale[2] == '_' ) {
            $locale = substr( $locale, 0, 2 );
            if ( isset( $translations->entries[ $locale ] ) ) {
                break;
            }
        }

        return $q_config['language-names'][ $lang ] = $q_config['language_name'][ $lang ];
    }
    $n = $translations->entries[ $locale ]->translations[0];
    if ( empty( $q_config['language_name_case'] ) ) {
        // Camel Case by default
        if ( function_exists( 'mb_convert_case' ) ) {
            // module 'mbstring' may not be installed by default: https://wordpress.org/support/topic/qtranslate_utilsphp-on-line-504
            $n = mb_convert_case( $n, MB_CASE_TITLE );
        } else {
            $msg = 'qTranslate-XT: Enable PHP module "mbstring" to get names of languages printed in "Camel Case" or disable option \'Show language names in "Camel Case"\' on admin page ' . admin_url( 'options-general.php?page=qtranslate-xt#general' ) . '. You may find more information at http://php.net/manual/en/mbstring.installation.php, or search for PHP installation options on control panel of your server provider.';
            error_log( $msg );
        }
    }

    return $q_config['language-names'][ $lang ] = $n;
}

function qtranxf_isEnabled( $lang ) {
    global $q_config;

    // only available languages are loaded, works quicker
    return isset( $q_config['locale'][ $lang ] );
}

/**
 * @since 3.2.8 - change code to improve performance
 */
function qtranxf_startsWith( $string, $needle ) {
    $len = strlen( $needle );
    if ( $len > strlen( $string ) ) {
        return false;
    }
    for ( $i = 0; $i < $len; ++ $i ) {
        if ( $string[ $i ] != $needle[ $i ] ) {
            return false;
        }
    }

    return true;
}

/**
 * @since 3.2.8
 */
function qtranxf_endsWith( $string, $needle ) {
    $len  = strlen( $needle );
    $base = strlen( $string ) - $len;
    if ( $base < 0 ) {
        return false;
    }
    for ( $i = 0; $i < $len; ++ $i ) {
        if ( $string[ $base + $i ] != $needle[ $i ] ) {
            return false;
        }
    }

    return true;
}

function qtranxf_getAvailableLanguages( $text ) {
    global $q_config;
    $blocks = qtranxf_get_language_blocks( $text );
    if ( count( $blocks ) <= 1 ) {
        return false; // no languages set
    }
    $result  = array();
    $content = qtranxf_split_languages( $blocks );
    foreach ( $content as $language => $lang_text ) {
        $lang_text = trim( $lang_text );
        if ( ! empty( $lang_text ) ) {
            $result[] = $language;
        }
    }
    if ( sizeof( $result ) == 0 ) {
        // add default language to keep default URL
        $result[] = $q_config['language'];
    }

    return $result;
}

function qtranxf_getSortedLanguages( $reverse = false ) {
    global $q_config;
    $languages = $q_config['enabled_languages'];
    ksort( $languages );
    // fix broken order
    $clean_languages = array();
    foreach ( $languages as $lang ) {
        $clean_languages[] = $lang;
    }
    if ( $reverse ) {
        krsort( $clean_languages );
    }

    return $clean_languages;
}

/**
 * Evaluate if the request URI leads to a REST call.
 * This is only a prediction based on REST prefix, but no strict guarantee the REST request will be processed as such.
 *
 * @return bool
 * @see parse_request in wp_includes/class-wp.php for the final processing of REQUEST_URI
 * @see rest_api_register_rewrites in wp_includes/rest-api.php for the REST rewrite rules using query_var = rest_route
 */
function qtranxf_is_rest_request_expected() {
    return stripos( $_SERVER['REQUEST_URI'], '/' . rest_get_url_prefix() . '/' ) !== false;
}

/**
 * Evaluate if the current request allows HTTP redirection.
 * Admin requests (WP_ADMIN, DOING_AJAX, WP_CLI, DOING_CRON) or REST calls should not be redirected.
 *
 * @return bool
 */
function qtranxf_can_redirect() {
    return ! is_admin() && ! wp_doing_ajax() && ! ( defined( 'WP_CLI' ) && WP_CLI ) && ! wp_doing_cron() && empty( $_POST )
           && ( ! qtranxf_is_rest_request_expected() )
           // TODO clarify: 'REDIRECT_*' needs more testing --> && !isset($_SERVER['REDIRECT_URL'])
           && ( ! isset( $_SERVER['REDIRECT_STATUS'] ) || $_SERVER['REDIRECT_STATUS'] == '200' );
}

/**
 * @since 3.4
 */
function qtranxf_post_type() {
    global $post, $post_type;
    if ( $post_type ) {
        return $post_type;
    }
    if ( $post && isset( $post->post_type ) ) {
        $post_type = $post->post_type;

        return $post_type;
    }
    if ( isset( $_REQUEST['post_type'] ) ) {
        $post_type = $_REQUEST['post_type'];

        return $post_type;
    }

    return null;
}

/**
 * Test $cfg['pages'] against $url_path and $url_query ($_SERVER['QUERY_STRING'])
 * @since 3.4
 */
function qtranxf_match_page( $cfg, $url_path, $url_query, $d ) {
    if ( ! isset( $cfg['pages'] ) ) {
        return true;
    }
    foreach ( $cfg['pages'] as $page => $query ) {
        if ( preg_match( $d . $page . $d, $url_path ) !== 1 ) {
            continue;
        }
        if ( empty( $query ) || preg_match( $d . $query . $d, $url_query ) === 1 ) {
            return true;
        }
    }

    return false;
}

/**
 * @since 3.4
 */
function qtranxf_match_post_type( $cfg_post_type, $post_type ) {

    if ( is_string( $cfg_post_type ) ) {
        return preg_match( $cfg_post_type, $post_type ) === 1;
    }

    if ( isset( $cfg_post_type['exclude'] ) ) {
        if ( preg_match( $cfg_post_type['exclude'], $post_type ) === 1 ) {
            return null;
        }
    }

    return true;
}

/**
 * @since 3.3.2
 */
function qtranxf_merge_config( $cfg_all, $cfg ) {
    foreach ( $cfg as $k => $value ) {
        if ( is_array( $value ) && isset( $cfg_all[ $k ] ) ) {
            $cfg_all[ $k ] = qtranxf_merge_config( $cfg_all[ $k ], $value );
        } else {
            $cfg_all[ $k ] = $value;
        }
    }

    return $cfg_all;
}

/**
 * Parse i18n configurations, filtered for the current page URL and query.
 * The post type is not filtered yet.
 *
 * @return array of active configurations, per post type
 */
function qtranxf_parse_page_config( $config, $url_path, $url_query ) {
    global $q_config;

    if ( isset( $q_config['i18n-log-dir'] ) ) {
        if ( ! file_exists( $q_config['i18n-log-dir'] ) ) {
            if ( ! mkdir( $q_config['i18n-log-dir'] ) ) {
                unset( $q_config['i18n-log-dir'] );
            }
        }
        if ( isset( $q_config['i18n-log-dir'] ) ) {
            qtranxf_write_config_log( $config, 'all-pages' );
        }
    }

    $page_configs = array();
    foreach ( $config as $pgkey => $pgcfg ) {
        $delimiter = isset( $pgcfg['preg_delimiter'] ) ? $pgcfg['preg_delimiter'] : '!';
        $matched   = qtranxf_match_page( $pgcfg, $url_path, $url_query, $delimiter );
        if ( $matched === false ) {
            continue;
        }

        // Empty string key applies to all post types
        $post_type_key = '';
        if ( isset( $pgcfg['post_type'] ) ) {
            // Store the post type(s) as a regex pattern
            if ( is_string( $pgcfg['post_type'] ) ) {
                $post_type_key = $delimiter . $pgcfg['post_type'] . $delimiter;
                unset( $pgcfg['post_type'] );
            } else {
                $post_type_key = serialize( $pgcfg['post_type'] );
                foreach ( $pgcfg['post_type'] as $k => $item ) {
                    $pgcfg['post_type'][ $k ] = $delimiter . $item . $delimiter;
                }
            }
        }

        if ( ! isset( $page_configs[ $post_type_key ] ) ) {
            $page_configs[ $post_type_key ] = array();
        }
        $page_config = &$page_configs[ $post_type_key ];

        // Aggregate the page configs for this post type
        foreach ( $pgcfg as $key => $cfg ) {
            if ( empty( $cfg ) ) {
                continue;
            }
            switch ( $key ) {
                case 'anchors':
                    {
                        // Anchor elements are defined by id only.
                        // Merge unique id values only:
                        foreach ( $cfg as $k => $anchor ) {
                            $id = qtranxf_standardize_config_anchor( $anchor );
                            if ( is_null( $id ) ) {
                                continue;
                            }
                            if ( ! is_string( $id ) ) {
                                $id = $k;
                            }
                            if ( ! isset( $page_config['anchors'] ) ) {
                                $page_config['anchors'] = array();
                            }
                            $page_config['anchors'][ $id ] = $anchor;
                        }
                    }
                    break;
                case 'forms':
                    {
                        if ( ! isset( $page_config['forms'] ) ) {
                            $page_config['forms'] = array();
                        }
                        foreach ( $cfg as $form_id => $pgcfg_form ) {
                            if ( ! isset( $pgcfg_form['fields'] ) ) {
                                continue;
                            }
                            // convert obsolete format for 'fields'
                            foreach ( $pgcfg_form['fields'] as $k => $field ) {
                                if ( ! isset( $field['id'] ) ) {
                                    continue;
                                }
                                $id = $field['id'];
                                unset( $field['id'] );
                                $pgcfg_form['fields'][ $id ] = $field;
                                if ( $id !== $k ) {
                                    unset( $pgcfg_form['fields'][ $k ] );
                                }
                            }
                            // figure out obsolete id of form/collection
                            if ( is_string( $form_id ) ) {
                                $id = $form_id;
                            } else if ( isset( $pgcfg_form['form']['id'] ) ) {
                                $id = $pgcfg_form['form']['id'];
                                unset( $pgcfg_form['form']['id'] );
                                if ( empty( $pgcfg_form['form'] ) ) {
                                    unset( $pgcfg_form['form'] );
                                }
                            } else {
                                $id = '';
                            }
                            if ( ! isset( $page_config['forms'][ $id ] ) ) {
                                $page_config['forms'][ $id ] = $pgcfg_form;
                            } else {
                                $page_config['forms'][ $id ] = qtranxf_merge_config( $page_config['forms'][ $id ], $pgcfg_form );
                            }
                        }
                    }
                    break;
                default:
                {
                    if ( ! isset( $page_config[ $key ] ) ) {
                        $page_config[ $key ] = $cfg;
                    } else {
                        $page_config[ $key ] = qtranxf_merge_config( $page_config[ $key ], $cfg );
                    }
                }
            }
        }

        // Store all page config keys for this post type
        if (! isset ($page_config['keys'])) {
            $page_config['keys'] = array( $pgkey );
        }
        else {
            $page_config['keys'][] = $pgkey;
        }
    }

    // Clean up empty configs
    foreach ( $page_configs as $post_type_key => &$page_config ) {
        if ( ! empty( $page_config ) ) {
            // clean up 'fields'
            if ( ! empty( $page_config['forms'] ) ) {
                foreach ( $page_config['forms'] as $form_id => $form ) {
                    if ( ! isset( $form['fields'] ) ) {
                        continue;
                    }
                    foreach ( $form['fields'] as $k => $field ) {
                        if ( qtranxf_set_field_jquery( $field ) ) {
                            $page_config['forms'][ $form_id ]['fields'][ $k ] = $field;
                        }
                    }
                }
            }
            foreach ( $page_config as $k => $cfg ) {
                if ( empty( $cfg ) ) {
                    unset( $page_config[ $k ] );
                }
            }
        }
        if ( empty( $page_config ) ) {
            unset( $page_configs[ $post_type_key ] );
        }
    }

    if ( isset( $q_config['i18n-log-dir'] ) ) {
        qtranxf_write_config_log( $page_configs, 'by-post-type', $url_path, $url_query );
    }

    return $page_configs;
}

function qtranxf_write_config_log( $config, $suffix = '', $url_path = null, $url_query = null, $post_type = null ) {
    global $q_config;
    if ( empty( $q_config['i18n-log-dir'] ) ) {
        return;
    }
    if ( ! is_null( $url_path ) && empty( $url_path ) ) {
        if ( is_admin() ) {
            global $pagenow;
            $url_path = $pagenow;
        } else {
            $url_path = $q_config['url_info']['wp-path'];
        }
    }
    if ( ! is_null( $url_query ) && empty( $url_query ) ) {
        $url_query = isset( $q_config['url_info']['query'] ) ? $q_config['url_info']['query'] : '';
    }

    $name = '';
    if ( ! empty( $url_path ) ) {
        $name = preg_replace( '![/?&=#.]+!', '-', trim( $url_path, '/' ) );
    }
    if ( ! empty( $url_query ) ) {
        $name .= '-' . preg_replace( '![/?&=#.]+!', '-', $url_query );
    }
    if ( empty( $name ) && ! is_null( $url_path ) ) {
        $name = 'fronthome';
    }
    if ( ! empty( $suffix ) ) {
        if ( ! empty( $name ) ) {
            $name .= '-';
        }
        $name .= $suffix;
    }

    $file_path = $q_config['i18n-log-dir'] . '/i18n-config-' . $name . '.json';
    if ( empty( $config ) ) {
        if ( file_exists( $file_path ) ) {
            unlink( $file_path );
        }

        return;
    }
    $file_handle = fopen( $file_path, 'w' );
    if ( $file_handle ) {
        if ( ! empty( $url_path ) ) {
            fwrite( $file_handle, 'url_path: "' . $url_path . '"' . PHP_EOL );
        }
        if ( ! empty( $url_query ) ) {
            fwrite( $file_handle, 'url_query: "' . $url_query . '"' . PHP_EOL );
        }
        if ( ! empty( $post_type ) ) {
            fwrite( $file_handle, 'post_type: "' . $post_type . '"' . PHP_EOL );
        }
        $title = 'config';
        if ( ! empty( $suffix ) ) {
            $title .= '-' . $suffix;
        }
        fwrite( $file_handle, $title . ': ' . PHP_EOL . json_encode( $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . PHP_EOL );
        fclose( $file_handle );
    }
}

/**
 * @since 3.4
 */
function qtranxf_add_filters( $filters ) {
    if ( ! empty( $filters['text'] ) ) {
        foreach ( $filters['text'] as $name => $prio ) {
            if ( $prio === '' ) {
                continue;
            }
            add_filter( $name, 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', $prio );
        }
    }
    if ( ! empty( $filters['url'] ) ) {
        foreach ( $filters['url'] as $name => $prio ) {
            if ( $prio === '' ) {
                continue;
            }
            add_filter( $name, 'qtranxf_convertURL', $prio );
        }
    }
    if ( ! empty( $filters['term'] ) ) {
        foreach ( $filters['term'] as $name => $prio ) {
            if ( $prio === '' ) {
                continue;
            }
            add_filter( $name, 'qtranxf_useTermLib', $prio );
        }
    }
}

/**
 * @since 3.4.6.9
 */
function qtranxf_remove_filters( $filters ) {
    if ( ! empty( $filters['text'] ) ) {
        foreach ( $filters['text'] as $name => $prio ) {
            if ( $prio === '' ) {
                continue;
            }
            remove_filter( $name, 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', $prio );
        }
    }
    if ( ! empty( $filters['url'] ) ) {
        foreach ( $filters['url'] as $name => $prio ) {
            if ( $prio === '' ) {
                continue;
            }
            remove_filter( $name, 'qtranxf_convertURL', $prio );
        }
    }
    if ( ! empty( $filters['term'] ) ) {
        foreach ( $filters['term'] as $name => $prio ) {
            if ( $prio === '' ) {
                continue;
            }
            remove_filter( $name, 'qtranxf_useTermLib', $prio );
        }
    }
}

/**
 * @since 3.4
 */
function qtranxf_html_locale( $locale ) {
    return str_replace( '_', '-', $locale );
}

function qtranxf_match_language_locale( $locale ) {
    global $q_config;
    foreach ( $q_config['enabled_languages'] as $lang ) {
        if ( qtranxf_html_locale( $q_config['locale'][ $lang ] ) == $locale ) {
            return $lang;
        }
        if ( $q_config['locale'][ $lang ] == $locale ) {
            return $lang;
        }
        if ( ! empty( $q_config['locale_html'][ $lang ] ) && $q_config['locale_html'][ $lang ] == $locale ) {
            return $lang;
        }
    }
    $locale_code = substr( $locale, 0, 2 );
    foreach ( $q_config['enabled_languages'] as $lang ) {
        if ( $locale_code == $lang ) {
            return $lang;
        }
    }

    return null;
}

function qtranxf_get_page_referer() {
    if ( wp_doing_ajax() ) {
        global $q_config;
        if ( isset( $q_config['url_info']['page_referer'] ) ) {
            return $q_config['url_info']['page_referer'];
        }
        if ( ! empty( $q_config['url_info']['http_referer'] ) ) {
            $page                                 = basename( $q_config['url_info']['http_referer'] );
            $epage                                = explode( '?', $page );
            $page                                 = $epage[0];
            $q_config['url_info']['page_referer'] = $page;

            return $page;
        }
    }
    global $pagenow;

    return $pagenow;
}
