<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once( QTRANSLATE_DIR . '/modules/qtx_modules_handler.php' );

function qtranxf_init_language() {
    global $q_config, $pagenow;
    //qtranxf_dbg_log('1.qtranxf_init_language:');

    qtranxf_loadConfig();

    // 'url_info' hash is not for external use, it is subject to change at any time.
    // 'url_info' is preserved on reloadConfig
    if ( ! isset( $q_config['url_info'] ) ) {
        $q_config['url_info'] = array();
    }

    $url_info                   = &$q_config['url_info'];
    $url_info['cookie_enabled'] = isset( $_COOKIE[ QTX_COOKIE_NAME_FRONT ] ) || isset( $_COOKIE[ QTX_COOKIE_NAME_ADMIN ] );
    if ( $url_info['cookie_enabled'] ) {
        if ( isset( $_COOKIE[ QTX_COOKIE_NAME_FRONT ] ) ) {
            $url_info['cookie_lang_front'] = $_COOKIE[ QTX_COOKIE_NAME_FRONT ];
        }
        if ( isset( $_COOKIE[ QTX_COOKIE_NAME_ADMIN ] ) ) {
            $url_info['cookie_lang_admin'] = $_COOKIE[ QTX_COOKIE_NAME_ADMIN ];
        }
    }

    if ( WP_DEBUG ) {
        $url_info['pagenow']        = $pagenow;
        $url_info['REQUEST_METHOD'] = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '';
        if ( is_admin() ) {
            $url_info['WP_ADMIN'] = true;
        }
        if ( wp_doing_ajax() ) {
            $url_info['DOING_AJAX_POST'] = $_POST;
        }
        if ( wp_doing_cron() ) {
            $url_info['DOING_CRON_POST'] = $_POST;
        }
    }

    // fill url_info similarly to qtranxf_parseURL
    $url_info['scheme'] = is_ssl() ? 'https' : 'http';
    // see https://wordpress.org/support/topic/messy-wp-cronphp-command-line-output
    $url_info['host'] = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '';
    $url_info['path'] = strtok( $_SERVER['REQUEST_URI'], '?' );
    if ( ! empty ( $_SERVER['QUERY_STRING'] ) ) {
        $url_info['query'] = qtranxf_sanitize_url( $_SERVER['QUERY_STRING'] ); // to prevent xss

        if ( isset( $_GET['qtranslate-mode'] ) && $_GET['qtranslate-mode'] == 'raw' ) {
            $url_info['qtranslate-mode']      = 'raw';
            $url_info['doing_front_end']      = true;
            $q_config['url_info']             = $url_info;
            $q_config['url_info']['language'] = $q_config['default_language'];
            $q_config['language']             = $q_config['default_language'];

            return;
        }
    }

    // TODO fix qtranslate-slug still using 'original_url' field and remove it from here, this has no sense!
    if ( defined( 'QTS_VERSION' ) ) {
        $url_info['original_url'] = $_SERVER['REQUEST_URI'];
    }

    //qtranxf_dbg_log('qtranxf_init_language: SERVER: ',$_SERVER);
    $url_info['language'] = qtranxf_detect_language( $url_info );
    $q_config['language'] = apply_filters( 'qtranslate_language', $url_info['language'], $url_info );
    //qtranxf_dbg_log('qtranxf_init_language: detected: url_info: ',$url_info);

    if ( $q_config['url_info']['doing_front_end'] && qtranxf_can_redirect() ) {
        $lang     = $q_config['language'];
        $url_orig = $url_info['scheme'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $url_lang = qtranxf_convertURL( '', $lang ); // uses $q_config['url_info'] and caches information, which will be needed later anyway.
        if ( ! isset( $url_info['doredirect'] ) && $url_orig != $url_lang ) {
            $url_info['doredirect'] = '$url_orig != $url_lang';
        }
        if ( isset( $url_info['doredirect'] ) ) {
            /**
             * Filter provides a chance to alter redirect behaviour.
             *
             * @param string $url_lang proposed target URL for the active language to redirect to.
             * @param string $url_orig original URL supplied to browser, which needs to be standardized.
             * @param array $url_info a hash of various information parsed from original URL, coockies and other site configuration. The key names should be self-explanatory.
             *
             * @return mixed A new URL to be redirected to instead of $url_lang or "false" to cancel redirection.
             */
            $target = apply_filters( 'qtranslate_language_detect_redirect', $url_lang, $url_orig, $url_info );
            //qtranxf_dbg_log('qtranxf_init_language: doredirect to '.$lang.PHP_EOL .'urlorg:'.$url_orig.PHP_EOL .'target:'.$target.PHP_EOL .'url_info: ',$url_info);
            if ( $target !== false && $target != $url_orig ) {
                wp_redirect( $target );
                nocache_headers(); // prevent browser from caching redirection
                exit();
            } else {
                // neutral path
                $url_info['doredirect'] .= ' - cancelled, because it goes to the same target - neutral URL';
                if ( $pagenow == 'index.php' && $q_config['url_mode'] == QTX_URL_PATH ) {
                    $_SERVER['REQUEST_URI'] = trailingslashit( $url_info['path-base'] ) . $lang . $url_info['wp-path']; // should not hurt?
                }
            }
            //qtranxf_dbg_log('qtranxf_init_language: doredirect canceled: $url_info: ',$url_info);
        }
    } elseif ( isset( $url_info['doredirect'] ) ) {
        $url_info['doredirect'] .= ' - cancelled by can_redirect';
        // this should never happen! We are now in a bad state.
        assert( false, $url_info['doredirect'] );
    }

    // TODO clarify fix url to prevent xss - how does this prevents xss?
    // $q_config['url_info']['url'] = qtranxf_convertURL(add_query_arg('lang',$q_config['default_language'],$q_config['url_info']['url']));

    // qtranslate_hooks.php has to go before load_plugin_textdomain()
    require_once( dirname( __FILE__ ) . '/qtranslate_hooks.php' );//common hooks moved here from qtranslate.php since 3.2.9.2, because they all need language already detected

    // load plugin translations
    // since 3.2-b3 moved it here as https://codex.wordpress.org/Function_Reference/load_plugin_textdomain seem to recommend to run load_plugin_textdomain in 'plugins_loaded' action, which is this function responds to
    qtranxf_load_plugin_textdomain();

    /**
     * allow other plugins to initialize whatever they need before the fork between front and admin.
     */
    do_action( 'qtranslate_load_front_admin', $url_info );

    if ( $q_config['url_info']['doing_front_end'] ) {
        require_once( QTRANSLATE_DIR . '/qtranslate_frontend.php' );
    } else {
        require_once( QTRANSLATE_DIR . '/admin/qtx_admin.php' );
    }
    apply_filters( 'wp_translator', null );//create QTX_Translator object

    qtranxf_load_option_qtrans_compatibility();

    QTX_Modules_Handler::load_modules_enabled();

    /**
     * allow other plugins and modules to initialize whatever they need for language
     */
    do_action( 'qtranslate_init_language', $url_info );
    //qtranxf_dbg_log('qtranxf_init_language: done: url_info: ',$url_info);
}

function qtranxf_detect_language( &$url_info ) {
    global $q_config;

    if ( is_admin() || defined( 'WP_CLI' ) ) {
        $siteinfo                     = qtranxf_get_site_info();
        $url_info['path-base']        = $siteinfo['path'];
        $url_info['path-base-length'] = $siteinfo['path-length'];
    } else {
        qtranxf_complete_url_info( $url_info );
        if ( ! isset( $url_info['path-base'] ) ) {
            // path did not match neither 'site' nor 'home', but it came to this code, then redirect to home.
            // may happen for testing
            $target = get_option( 'home' );
            wp_redirect( $target );
            exit();
        }
        $url_info['doing_front_end'] = true;
    }

    $lang = qtranxf_parse_language_info( $url_info );

    // TODO check if we shouldn't generalize the referrer parsing to all cases, do we need all these limitations?
    $parse_referrer = qtranxf_is_rest_request_expected() ||
                      ( ( ! $lang || ! isset( $url_info['doing_front_end'] ) ) &&
                        ( wp_doing_ajax() || ! $url_info['cookie_enabled'] ) );

    // parse language and front info from HTTP_REFERER
    if ( isset( $_SERVER['HTTP_REFERER'] ) && $parse_referrer ) {
        $http_referer             = $_SERVER['HTTP_REFERER'];
        $url_info['http_referer'] = $http_referer;

        // if needed, detect front- vs back-end
        $parse_referrer_language = true;
        if ( strpos( $http_referer, '/wp-admin' ) !== false ) {
            $url_info['referer_admin'] = true;
            if ( ! isset( $url_info['doing_front_end'] ) ) {
                $url_info['doing_front_end'] = false;
            }
            if ( qtranxf_is_rest_request_expected() ) {
                // TODO see if we can detect front better for REST to avoid overriding here
                $url_info['doing_front_end'] = false;
            } else {
                // TODO check if language shouldn't be parsed for non-admin referrer as well, potentially a legacy bug!
                $parse_referrer_language = false;
            }
        }

        if ( $parse_referrer_language ) {
            $ref_info = qtranxf_parseURL( $http_referer );
            if ( ! qtranxf_external_host( $ref_info['host'] ) ) {
                // determine $ref_info['path-base']
                qtranxf_complete_url_info( $ref_info );
                if ( isset( $ref_info['path-base'] ) ) {
                    // TODO remove internal_refer, not used
                    $url_info['internal_referer'] = true;
                    if ( ! $lang || ! ( isset( $url_info['doing_front_end'] ) || isset( $ref_info['doing_front_end'] ) ) ) {
                        $lang = qtranxf_parse_language_info( $ref_info, true );
                        if ( $lang === false ) {
                            unset( $url_info['internal_referer'] );
                        }
                    }
                    $url_info['referer_language'] = $lang;
                    if ( ! isset( $url_info['doing_front_end'] ) && isset( $ref_info['doing_front_end'] ) ) {
                        $url_info['doing_front_end'] = $ref_info['doing_front_end'];
                    }
                }
            }
            if ( ! $lang && $q_config['hide_default_language']
                 && isset( $url_info['doing_front_end'] ) && $url_info['doing_front_end'] ) {
                $lang = $q_config['default_language'];
            }
        }
    }

    if ( ! isset( $url_info['doing_front_end'] ) ) {
        $url_info['doing_front_end'] = ! is_admin();
    }

    if ( ! $lang ) {
        $lang = $url_info['doing_front_end'] ? qtranxf_detect_language_front( $url_info ) : qtranxf_detect_language_admin( $url_info );
    }

    $url_info['language'] = $lang;

    // REST calls should be deterministic (stateless), no special language detection e.g. based on cookie
    $url_info['set_cookie'] = ! wp_doing_ajax() && ! qtranxf_is_rest_request_expected();

    /**
     * Hook for possible other methods
     * Set $url_info['language'] with the result
     */
    $url_info = apply_filters( 'qtranslate_detect_language', $url_info );

    $lang = $url_info['language'];
    if ( $url_info['set_cookie'] ) {
        qtranxf_set_language_cookie( $lang );
    }

    return $lang;
}

/**
 * Parse language from the URL and/or query var, update url_info accordingly
 *
 * Expects to be set before call:
 * - $url_info['host']
 * - $url_info['path']
 * - $url_info['query']
 * - $url_info['path-base']
 * - $url_info['path-base-length']
 *
 * @param array $url_info
 * @param bool $link true when url_info concerns internal referrer (HTTP_REFERER)
 *
 * @return bool|string
 */
function qtranxf_parse_language_info( &$url_info, $link = false ) {
    global $q_config;

    qtranxf_complete_url_info_path( $url_info );
    if ( ! isset( $url_info['wp-path'] ) ) {
        return false;   // url is not from this WP installation
    }

    $doredirect = false;

    // parse URL lang
    if ( ! is_admin() || $link ) {
        $url_mode = $q_config['url_mode'];
        switch ( $url_mode ) {
            case QTX_URL_PATH:
                if ( ! empty( $url_info['wp-path'] ) && preg_match( '!^/([a-z]{2})(/|$)!i', $url_info['wp-path'], $match ) ) {
                    $lang = qtranxf_resolveLangCase( $match[1], $doredirect );
                    if ( $lang ) {
                        $url_info['lang_url']        = $lang;
                        $url_info['wp-path']         = substr( $url_info['wp-path'], 3 );
                        $url_info['doing_front_end'] = true;
                    }
                }
                break;

            case QTX_URL_DOMAIN:
                if ( ! empty( $url_info['host'] ) ) {
                    if ( preg_match( '#^([a-z]{2})\.#i', $url_info['host'], $match ) ) {
                        $lang = qtranxf_resolveLangCase( $match[1], $doredirect );
                        if ( $lang ) {
                            $url_info['lang_url']        = $lang;
                            $url_info['host']            = substr( $url_info['host'], 3 );
                            $url_info['doing_front_end'] = true;
                        }
                    }
                }
                break;

            case QTX_URL_DOMAINS:
                if ( ! empty( $url_info['host'] ) ) {
                    // TODO should 'enabled_languages' be defined as host->lang for domains?
                    foreach ( $q_config['enabled_languages'] as $lang ) {
                        if ( ! isset( $q_config['domains'][ $lang ] ) ) {
                            continue;
                        }
                        if ( $q_config['domains'][ $lang ] != $url_info['host'] ) {
                            continue;
                        }
                        $url_info['lang_url'] = $lang;
                        if ( $lang != $q_config['default_language'] || strpos( get_option( 'siteurl' ), $url_info['host'] ) === false ) {
                            $url_info['doing_front_end'] = true;
                        }
                        break;
                    }
                }
                break;

            default:
                // TODO why don't we parse query lang here as 'lang_url'?!
                assert( $url_mode == QTX_URL_QUERY );
                /**
                 * Hook for possible other methods
                 * Set, if applicable:
                 * $url_info['lang_url']
                 * $url_info['doing_front_end']
                 * $url_info['path'] - convert to language neutral or default
                 */
                // TODO why do we have this spooky hook only for query mode?!
                $url_info = apply_filters( 'qtranslate_parse_language_info_mode', $url_info, $q_config['url_mode'] );
                break;
        }
    }

    // parse query lang (even for non query mode!)
    $query_lang = false;
    if ( ! $link ) {
        if ( isset( $_GET['lang'] ) ) {
            $query_lang = qtranxf_resolveLangCase( $_GET['lang'], $doredirect );
            if ( $query_lang ) {
                $url_info['lang_query_get'] = $query_lang;  // only used in qtranxf_url_set_language
            }
        } else if ( isset( $_POST['lang'] ) ) {
            $query_lang = qtranxf_resolveLangCase( $_POST['lang'], $doredirect );
        }
    } elseif ( ! empty( $url_info['query'] ) && preg_match( '/(^|&|&amp;|&#038;|\?)lang=([a-z]{2})/i', $url_info['query'], $match ) ) {
        // checked for query mode, see https://github.com/qTranslate-Team/qtranslate-x/issues/288
        $query_lang = qtranxf_resolveLangCase( $match[2], $doredirect );
    }

    $parsed_lang = false;

    // don't allow query lang switch with REST request
    if ( qtranxf_is_rest_request_expected() ) {
        if ( isset( $url_info['lang_url'] ) ) {
            $parsed_lang = $url_info['lang_url'];
        } elseif ( $query_lang && ( $q_config['url_mode'] == QTX_URL_QUERY || $link ) ) {
            // consider query lang for query mode or fallback for referrer links (from REST)
            $parsed_lang = $query_lang;
        }
        // 'hide_default_language' should also be set in query mode
        if ( ! isset( $parsed_lang ) && $q_config['hide_default_language'] ) {
            $parsed_lang = $q_config['default_language'];
        }
        // TODO validate URL-case redirections
        if ( $doredirect ) {
            assert( isset( $parsed_lang ) );
            $doredirect = false;
        }
    } else {
        if ( $query_lang ) {
            // query overrides URL lang for a language switch
            $parsed_lang = $query_lang;
            // TODO can we avoid removing query args?
            qtranxf_del_query_arg( $url_info['query'], 'lang' );
            if ( $q_config['url_mode'] != QTX_URL_QUERY && ! is_admin() ) {
                // force lang switch from query var
                $doredirect = true;
            }
        } elseif ( isset( $url_info['lang_url'] ) ) {
            $parsed_lang = $url_info['lang_url'];
            assert( $parsed_lang !== false );
            if ( $q_config['hide_default_language'] && $parsed_lang == $q_config['default_language'] ) {
                // default lang should not be part of the URL when hidden
                $doredirect = true;
            }
        }
    }

    if ( $parsed_lang ) {
        $url_info['language'] = $parsed_lang;
    }

    if ( $doredirect ) {
        $url_info['doredirect'] = 'detected in parse_language_info';
    }

    if ( ! isset( $url_info['doing_front_end'] ) ) {
        $language_neutral_path             = qtranxf_language_neutral_path( $url_info['wp-path'] );
        $url_info['language_neutral_path'] = $language_neutral_path;
        if ( ! $language_neutral_path ) {
            $url_info['doing_front_end'] = true;
        }
    }

    /**
     * Hook for possible other methods
     * Set $url_info['language'] with the result
     */
    $url_info = apply_filters( 'qtranslate_parse_language_info', $url_info, $link );

    if ( isset( $url_info['language'] ) ) {
        $parsed_lang = $url_info['language'];
    }

    assert( isset( $parsed_lang ) );

    return $parsed_lang;
}

function qtranxf_detect_language_admin( &$url_info ) {
    require_once( dirname( __FILE__ ) . '/admin/qtx_admin_utils.php' );
    $url_info = apply_filters( 'qtranslate_detect_admin_language', $url_info );

    return $url_info['lang_admin'];
}

function qtranxf_detect_language_front( &$url_info ) {
    global $q_config;

    $lang = null;
    if ( isset( $_COOKIE[ QTX_COOKIE_NAME_FRONT ] ) ) {
        $cs                            = null;
        $lang                          = qtranxf_resolveLangCase( $_COOKIE[ QTX_COOKIE_NAME_FRONT ], $cs );
        $url_info['lang_cookie_front'] = $lang;
    }

    if ( ! $lang && $q_config['detect_browser_language']
         && ( ! isset( $_SERVER['HTTP_REFERER'] ) || strpos( $_SERVER['HTTP_REFERER'], $url_info['host'] ) === false )//external referrer or no referrer
         && ( empty( $url_info['wp-path'] ) || $url_info['wp-path'] == '/' ) // home page is requested
    ) {
        $lang                     = qtranxf_http_negotiate_language();
        $url_info['lang_browser'] = $lang;
    }

    if ( ! $lang ) {
        $lang = $q_config['default_language'];
    }

    if ( ! isset( $url_info['doredirect'] )
         && ( ! qtranxf_is_rest_request_expected() ) // fallback case where language can be read from cookie with REST
         && ( ! $q_config['hide_default_language'] || $lang != $q_config['default_language'] )
    ) {
        $url_info['doredirect'] = 'language needs to be shown in url';
    }

    return $lang;
}

function qtranxf_setcookie_language( $lang, $cookie_name, $cookie_path ) {
    global $q_config;

    // only meant for server-side, set 'httponly' flag
    setcookie( $cookie_name, $lang, strtotime( '+1year' ), $cookie_path, null, $q_config['use_secure_cookie'], true );
}

function qtranxf_set_language_cookie( $lang ) {
    global $q_config;

    assert( ! qtranxf_is_rest_request_expected() );
    if ( is_admin() ) {
        qtranxf_setcookie_language( $lang, QTX_COOKIE_NAME_ADMIN, ADMIN_COOKIE_PATH );
    } elseif ( ! $q_config['disable_client_cookies'] ) {
        qtranxf_setcookie_language( $lang, QTX_COOKIE_NAME_FRONT, COOKIEPATH );
    }
}

function qtranxf_get_browser_language() {
    //qtranxf_dbg_log('qtranxf_get_browser_language: HTTP_ACCEPT_LANGUAGE:',$_SERVER["HTTP_ACCEPT_LANGUAGE"]);
    if ( ! isset( $_SERVER["HTTP_ACCEPT_LANGUAGE"] ) ) {
        return null;
    }
    if ( ! preg_match_all( "#([^;,]+)(;[^,0-9]*([0-9.]+)[^,]*)?#i", $_SERVER["HTTP_ACCEPT_LANGUAGE"], $matches, PREG_SET_ORDER ) ) {
        return null;
    }
    $prefered_languages = array();
    $priority           = 1.0;
    foreach ( $matches as $match ) {
        if ( ! isset( $match[3] ) ) {
            $pr       = $priority;
            $priority -= 0.001;
        } else {
            $pr = floatval( $match[3] );
        }
        $prefered_languages[ $match[1] ] = $pr;
    }
    arsort( $prefered_languages, SORT_NUMERIC );
    //qtranxf_dbg_log('qtranxf_get_browser_language: prefered_languages:',$prefered_languages);
    foreach ( $prefered_languages as $language => $priority ) {
        $lang = qtranxf_match_language_locale( $language );
        if ( $lang ) {
            return $lang;
        }
    }

    return null;
}

function qtranxf_http_negotiate_language() {
    global $q_config;
    if ( function_exists( 'http_negotiate_language' ) ) {
        $default_language = $q_config['default_language'];
        $supported        = array();
        $supported[]      = qtranxf_html_locale( $q_config['locale'][ $default_language ] ); // needs to be the first
        if ( ! empty( $q_config['locale_html'][ $default_language ] ) ) {
            $supported[] = $q_config['locale_html'][ $default_language ];
        }
        foreach ( $q_config['enabled_languages'] as $lang ) {
            if ( $lang == $default_language ) {
                continue;
            }
            $supported[] = qtranxf_html_locale( $q_config['locale'][ $lang ] );
            if ( ! empty( $q_config['locale_html'][ $lang ] ) ) {
                $supported[] = $q_config['locale_html'][ $lang ];
            }
        }
        $locale_negotiated = http_negotiate_language( $supported );

        return qtranxf_match_language_locale( $locale_negotiated );
    } else {
        return qtranxf_get_browser_language();
    }
}

function qtranxf_resolveLangCase( $lang, &$caseredirect ) {
    if ( qtranxf_isEnabled( $lang ) ) {
        return $lang;
    }
    $lng = strtolower( $lang );
    if ( qtranxf_isEnabled( $lng ) ) {
        $caseredirect = true;

        return $lng;
    }
    $lng = strtoupper( $lang );
    if ( qtranxf_isEnabled( $lng ) ) {
        $caseredirect = true;

        return $lng;
    }

    return false;
}

function qtranxf_load_option_qtrans_compatibility() {
    global $q_config;
    qtranxf_load_option_bool( 'qtrans_compatibility', false );
    $q_config['qtrans_compatibility'] = apply_filters( 'qtranslate_compatibility', $q_config['qtrans_compatibility'] );
    if ( ! isset( $q_config['qtrans_compatibility'] ) || ! $q_config['qtrans_compatibility'] ) {
        return;
    }
    require_once( dirname( __FILE__ ) . '/qtranslate_compatibility.php' );
}

function qtranxf_load_plugin_textdomain() {
    $domain   = 'qtranslate';
    $lang_dir = qtranxf_plugin_dirname() . '/lang';
    if ( load_plugin_textdomain( $domain, false, $lang_dir ) ) {
        return true;
    }

    return false;
}

/**
 * Response to action 'init', which runs after user is authenticated.
 * Currently unused.
 * Is in use by 3rd-party plugins (for example, alo_easymail) to test q-X presence,
 * which they should have done by testing "if ( defined( 'QTRANSLATE_FILE' )" instead.
 * @since 3.4
 */
// TODO this function should be removed but some legacy plugins might still use this to check if q-XT is enabled...
function qtranxf_init() {
    //qtranxf_dbg_log('3.qtranxf_init:');
}

function qtranxf_front_header_css_default() {
    global $q_config;
    $flag_location = qtranxf_flag_location();
    $css           = '';
    foreach ( $q_config['enabled_languages'] as $lang ) {
        $css .= '.qtranxs_flag_' . $lang . ' {background-image: url(' . $flag_location . $q_config['flag'][ $lang ] . '); background-repeat: no-repeat;}' . PHP_EOL;
    }

    return $css;
}

function qtranxf_flag_location() {
    global $q_config;

    return trailingslashit( content_url() ) . $q_config['flag_location'];
}

function qtranxf_flag_location_default() {
    return qtranxf_plugin_dirname_from_wp_content() . '/flags/';
}

function qtranxf_load_option_flag_location( $nm ) {
    global $q_config;
    $default_value = qtranxf_flag_location_default();
    $option_value  = get_option( 'qtranslate_' . $nm );
    if ( $option_value === false ) {
        $q_config[ $nm ] = $default_value;
    } else {
        // url fix for upgrading users
        $flag_location = trailingslashit( preg_replace( '#^wp-content/#', '', $option_value ) );
        if ( file_exists( trailingslashit( WP_CONTENT_DIR ) . $flag_location ) && $default_value != $flag_location ) {
            $q_config[ $nm ] = $flag_location;
        } else {
            $q_config[ $nm ] = $default_value;
            delete_option( 'qtranslate_' . $nm );
        }
    }
}

function qtranxf_validateBool( $var, $default_value ) {
    if ( $var === '0' ) {
        return false;
    } elseif ( $var === '1' ) {
        return true;
    } else {
        return $default_value;
    }
}

function qtranxf_load_option( $name, $default_value = null ) {
    global $q_config, $qtranslate_options;
    $val = get_option( 'qtranslate_' . $name );
    if ( $val === false ) {
        if ( is_null( $default_value ) ) {
            if ( ! isset( $qtranslate_options['default_value'][ $name ] ) ) {
                return;
            }
            $default_value = $qtranslate_options['default_value'][ $name ];
        }
        if ( is_string( $default_value ) && function_exists( $default_value ) ) {
            $val = call_user_func( $default_value );
        } else {
            $val = $default_value;
        }
    }
    $q_config[ $name ] = $val;
}

function qtranxf_load_option_array( $name, $default_value = null ) {
    global $q_config;
    $vals = get_option( 'qtranslate_' . $name );
    if ( $vals === false ) {
        if ( is_null( $default_value ) ) {
            return;
        }
        if ( is_string( $default_value ) ) {
            if ( function_exists( $default_value ) ) {
                $vals = call_user_func( $default_value );
            } else {
                $vals = preg_split( '/[\s,]+/', $default_value, null, PREG_SPLIT_NO_EMPTY );
            }
        } else if ( is_array( $default_value ) ) {
            $vals = $default_value;
        }
    }
    if ( ! is_array( $vals ) ) {
        return;
    }

    // clean up array due to previous configuration imperfections
    foreach ( $vals as $key => $val ) {
        if ( ! empty( $val ) ) {
            continue;
        }
        unset( $vals[ $key ] );
        if ( ! empty( $vals ) ) {
            continue;
        }
        delete_option( 'qtranslate_' . $name );
        break;
    }
    $q_config[ $name ] = $vals;
}

function qtranxf_load_option_bool( $name, $default_value = null ) {
    global $q_config;
    $val = get_option( 'qtranslate_' . $name );
    if ( $val === false ) {
        if ( ! is_null( $default_value ) ) {
            $q_config[ $name ] = $default_value;
        }
    } else {
        switch ( $val ) {
            case '0':
                $q_config[ $name ] = false;
                break;
            case '1':
                $q_config[ $name ] = true;
                break;
            default:
                $val = strtolower( $val );
                switch ( $val ) {
                    case 'n':
                    case 'no':
                        $q_config[ $name ] = false;
                        break;
                    case 'y':
                    case 'yes':
                        $q_config[ $name ] = true;
                        break;
                    default:
                        $q_config[ $name ] = ! empty( $val );
                        break;
                }
                break;
        }
    }
}

function qtranxf_load_option_func( $name, $opn = null, $func = null ) {
    global $q_config;
    if ( ! $opn ) {
        $opn = 'qtranslate_' . $name;
    }
    $val = get_option( $opn );
    if ( $val === false ) {
        if ( ! $func ) {
            $func = 'qtranxf_default_' . $name;
        }
        $val = call_user_func( $func );
    }
    $q_config[ $name ] = $val;
}

function qtranxf_is_permalink_structure_query() {
    $permalink_structure = get_option( 'permalink_structure' );

    //qtranxf_dbg_echo('qtranxf_is_permalink_structure_query: ', $permalink_structure);
    return empty( $permalink_structure ) || strpos( $permalink_structure, '?' ) !== false || strpos( $permalink_structure, 'index.php' ) !== false;
}

function qtranxf_loadConfig() {
    global $qtranslate_options, $q_config;
    qtranxf_set_default_options( $qtranslate_options );

    $q_config = array();

    qtranxf_load_option_func( 'default_language' );
    qtranxf_load_option_array( 'enabled_languages' );

    qtranxf_load_option_flag_location( 'flag_location' );
    qtranxf_load_languages_enabled();

    foreach ( $qtranslate_options['front']['int'] as $name => $def ) {
        qtranxf_load_option( $name, $def );
    }

    foreach ( $qtranslate_options['front']['bool'] as $name => $def ) {
        qtranxf_load_option_bool( $name, $def );
    }

    foreach ( $qtranslate_options['front']['str'] as $name => $def ) {
        qtranxf_load_option( $name, $def );
    }

    foreach ( $qtranslate_options['front']['text'] as $name => $def ) {
        qtranxf_load_option( $name, $def );
    }

    foreach ( $qtranslate_options['front']['array'] as $name => $def ) {
        qtranxf_load_option_array( $name, $def );
    }

    qtranxf_load_option_array( 'term_name', array() );

    if ( $q_config['filter_options_mode'] == QTX_FILTER_OPTIONS_LIST ) {
        qtranxf_load_option_array( 'filter_options', QTX_FILTER_OPTIONS_DEFAULT );
    }

    $url_mode = $q_config['url_mode'];
    // check for invalid permalink/url mode combinations
    if ( qtranxf_is_permalink_structure_query() ) {
        switch ( $url_mode ) {
            case QTX_URL_QUERY:
            case QTX_URL_DOMAIN:
            case QTX_URL_DOMAINS:
                break;
            default:
                $q_config['url_mode'] = $url_mode = QTX_URL_QUERY;
                break;
        }
    }

    switch ( $url_mode ) {
        case QTX_URL_DOMAINS:
            $q_config['domains'] = array();
            qtranxf_load_option_array( 'domains' );
            //qtranxf_dbg_echo('domains loaded: ',$q_config['domains']);
            foreach ( $q_config['enabled_languages'] as $lang ) {
                if ( isset( $q_config['domains'][ $lang ] ) ) {
                    continue;
                }
                $homeinfo                     = qtranxf_get_home_info();
                $q_config['domains'][ $lang ] = $lang . '.' . $homeinfo['host'];
            }
            $q_config['disable_client_cookies'] = true;
            $q_config['hide_default_language']  = false;
            break;
        case QTX_URL_QUERY:
        case QTX_URL_PATH:
            $q_config['disable_client_cookies'] = false;
            qtranxf_load_option_bool( 'disable_client_cookies' );
            break;
        case QTX_URL_DOMAIN:
        default:
            $q_config['disable_client_cookies'] = true;
            break;
    }

    $ignore_file_types = get_option( 'qtranslate_ignore_file_types' );
    $val               = explode( ',', QTX_IGNORE_FILE_TYPES );
    if ( ! empty( $ignore_file_types ) ) {
        $vals = preg_split( '/[\s,]+/', strtolower( $ignore_file_types ), null, PREG_SPLIT_NO_EMPTY );
        foreach ( $vals as $v ) {
            if ( empty( $v ) ) {
                continue;
            }
            if ( in_array( $v, $val ) ) {
                continue;
            }
            $val[] = $v;
        }
    }
    $q_config['ignore_file_types'] = $val;

    if ( empty( $q_config['front_config'] ) ) {
        // TODO this should be granulated to load only what is needed
        require_once( QTRANSLATE_DIR . '/admin/qtx_activation_hook.php' );
        require_once( QTRANSLATE_DIR . '/admin/qtx_admin_options_update.php' );
        qtranxf_update_i18n_config();
    }

    /**
     * Opportunity to load additional front-end features.
     */
    do_action( 'qtranslate_loadConfig' );
}

// check if it is a link to an ignored file type
function qtranxf_ignored_file_type( $path ) {
    global $q_config;
    //qtranxf_dbg_echo('qtranxf_ignored_file_type: pathinfo:',$pathinfo);
    $i = strpos( $path, '?' );
    if ( $i !== false ) {
        $path = substr( $path, 0, $i );
    }
    $i = strpos( $path, '#' );
    if ( $i !== false ) {
        $path = substr( $path, 0, $i );
    }
    $i = strrpos( $path, '.' );
    //qtranxf_dbg_echo('qtranxf_ignored_file_type: path='.$path.'; i='.$i);
    if ( $i === false ) {
        return false;
    }
    $ext = substr( $path, $i + 1 );

    //qtranxf_dbg_echo('qtranxf_ignored_file_type: ext='.$ext);
    return in_array( $ext, $q_config['ignore_file_types'] );
}

function qtranxf_language_neutral_path( $path ) {
    //qtranxf_dbg_log('qtranxf_language_neutral_path: path='.$path);
    if ( empty( $path ) ) {
        return false;
    }
    static $language_neutral_path_cache;
    if ( isset( $language_neutral_path_cache[ $path ] ) ) {
        //qtranxf_dbg_log('qtranxf_language_neutral_path: cached='.$language_neutral_path_cache[$path].': path='.$path);
        return $language_neutral_path_cache[ $path ];
    }
    if ( preg_match( '#^/(wp-.*\.php|wp-login/|wp-admin/|xmlrpc.php|robots.txt|oauth/)#', $path ) ) {
        $language_neutral_path_cache[ $path ] = true;

        //qtranxf_dbg_log('qtranxf_language_neutral_path: preg_match: path='.$path);
        return true;
    }
    if ( qtranxf_ignored_file_type( $path ) ) {
        $language_neutral_path_cache[ $path ] = true;

        //qtranxf_dbg_log('qtranxf_language_neutral_path: file_type: path='.$path);
        return true;
    }
    $language_neutral_path_cache[ $path ] = false;

    return false;
}

/**
 * @since 3.0
 */
function qtranxf_url_del_language( &$urlinfo ) {
    global $q_config;

    if ( ! empty( $urlinfo['query'] ) ) {
        $query = &$urlinfo['query'];
        // &amp; workaround
        if ( strpos( $query, '&amp;' ) !== false ) {
            $query                = str_replace( '&amp;', '&', $query );
            $urlinfo['query_amp'] = true;
        }
        if ( strpos( $urlinfo['query'], '&#038;' ) !== false ) {
            $query                = str_replace( '&#038;', '&', $query );
            $urlinfo['query_amp'] = true;
        }
        qtranxf_del_query_arg( $query, 'lang' );
    }

    $url_mode = $q_config['url_mode'];
    switch ( $url_mode ) {
        case QTX_URL_PATH:
            // might already have language information
            if ( ! empty( $urlinfo['wp-path'] ) && preg_match( '!^/([a-z]{2})(/|$)!i', $urlinfo['wp-path'], $match ) ) {
                if ( qtranxf_isEnabled( $match[1] ) ) {
                    // found language information, remove it
                    $urlinfo['wp-path'] = substr( $urlinfo['wp-path'], 3 );
                }
            }
            break;

        case QTX_URL_DOMAIN:
            // remove language information
            $homeinfo        = qtranxf_get_home_info();
            $urlinfo['host'] = $homeinfo['host'];
            break;

        case QTX_URL_DOMAINS:
            $urlinfo['host'] = $q_config['domains'][ $q_config['default_language'] ];
            break;

        case QTX_URL_QUERY:
            break;

        default:
            $urlinfo = apply_filters( 'qtranslate_url_del_language', $urlinfo, $url_mode );
            break;
    }
    //qtranxf_dbg_log('qtranxf_url_del_language: $urlinfo:',$urlinfo);
}

function qtranxf_url_set_language( $urlinfo, $lang, $showLanguage ) {
    global $q_config;
    $urlinfo = qtranxf_copy_url_info( $urlinfo );
    //qtranxf_dbg_log('qtranxf_url_set_language: $urlinfo:',$urlinfo);
    if ( $showLanguage ) {
        $url_mode = $q_config['url_mode'];
        switch ( $url_mode ) {
            case QTX_URL_PATH:
                //qtranxf_dbg_log_if(!isset($urlinfo['wp-path']),'qtranxf_url_set_language: wp-path not set $urlinfo:',$urlinfo,true);
                $urlinfo['wp-path'] = '/' . $lang . $urlinfo['wp-path'];
                break;

            case QTX_URL_DOMAIN:
                $urlinfo['host'] = $lang . '.' . $urlinfo['host'];
                //qtranxf_dbg_log('qtranxf_url_set_language: QTX_URL_DOMAIN: urlinfo:',$urlinfo);
                break;

            case QTX_URL_DOMAINS:
                $urlinfo['host'] = $q_config['domains'][ $lang ];
                break;

            case QTX_URL_QUERY:
                qtranxf_add_query_arg( $urlinfo['query'], 'lang=' . $lang );
                break;
        }
        $urlinfo = apply_filters( 'qtranslate_url_set_language', $urlinfo, $lang, $url_mode );
    }


    // see if cookies are activated
    if ( ! $showLanguage//there still is no language information in the converted URL
         && ! $q_config['url_info']['cookie_enabled']// there will be no way to take language from the cookie
         && $q_config['language'] != $q_config['default_language']//we need to be able to get language other than default
         && empty( $q_config['url_info']['lang_url'] )//we will not be able to get language from referrer path
         && empty( $q_config['url_info']['lang_query_get'] )//we will not be able to get language from referrer query
    ) {
        // :( now we have to make unpretty URLs
        qtranxf_add_query_arg( $urlinfo['query'], 'lang=' . $lang );
    }

    // &amp; workaround
    if ( isset( $urlinfo['query_amp'] ) ) {
        $urlinfo['query'] = str_replace( '&', '&amp;', $urlinfo['query'] );
    }

    return $urlinfo;
}

function qtranxf_get_url_for_language( $url, $lang, $showLanguage = true ) {
    global $q_config;
    static $url_cache = array();
    //qtranxf_dbg_log('qtranxf_get_url_for_language: $url_cache:',$url_cache);
    if ( ! isset( $url_cache[ $url ] ) ) {
        $url_cache[ $url ] = array();
    }
    $urlinfo = &$url_cache[ $url ];

    if ( $showLanguage ) {
        if ( isset( $urlinfo[ $lang ] ) ) {
            //qtranxf_dbg_log('qtranxf_get_url_for_language: cached: lang='.$lang.': ',$urlinfo);
            return $urlinfo[ $lang ];
        }
    } else {
        if ( isset( $urlinfo['bare'] ) ) {
            //qtranxf_dbg_log('qtranxf_get_url_for_language: cached: bare: ',$urlinfo);
            return $urlinfo['bare'];
        }
    }

    if ( isset( $urlinfo['language_neutral'] ) ) {
        //qtranxf_dbg_log('qtranxf_get_url_for_language: cached: language_neutral: ',$urlinfo);
        return $urlinfo['language_neutral'];
    }

    $homeinfo = qtranxf_get_home_info();
    if ( ! isset( $urlinfo['url_parsed'] ) ) {
        if ( empty( $url ) ) {
            $urlinfo = qtranxf_copy_url_info( $q_config['url_info'] );

            if ( isset( $urlinfo['wp-path'] ) && qtranxf_language_neutral_path( $urlinfo['wp-path'] ) ) {
                //qtranxf_dbg_log('qtranxf_get_url_for_language: language_neutral: wp-path: url='.$url.':',$urlinfo);
                $complete = qtranxf_buildURL( $urlinfo, $homeinfo );
                if ( ! isset( $url_cache[ $complete ] ) ) {
                    $url_cache[ $complete ] = $urlinfo;
                }
                $urlinfo['language_neutral'] = $complete;

                return $complete;
            }

        } else {
            $urlinfo = qtranxf_get_url_info( $url );

            // check if it's an external link
            if ( ! isset( $urlinfo['wp-path'] ) ) {
                $urlinfo['language_neutral'] = $url;

                //qtranxf_dbg_log('qtranxf_get_url_for_language: language_neutral: external path: ',$urlinfo);
                return $url;
            }

            if ( empty( $urlinfo['host'] ) ) {
                if ( empty( $urlinfo['wp-path'] ) ) {
                    if ( empty( $urlinfo['query'] ) ) {
                        $urlinfo['language_neutral'] = $url;

                        //qtranxf_dbg_log('qtranxf_get_url_for_language: language_neutral: relative path: ',$urlinfo);
                        return $url;
                    }
                } else {
                    switch ( $urlinfo['wp-path'][0] ) {
                        case '/':
                            break;
                        case '#':
                        {
                            $urlinfo['language_neutral'] = $url;

                            //qtranxf_dbg_log('qtranxf_get_url_for_language: language_neutral: relative hash: ',$urlinfo);
                            return $url;
                        }
                        default:
                            $urlinfo['wp-path'] = trailingslashit( $q_config['url_info']['wp-path'] ) . $urlinfo['wp-path'];
                            break;
                    }
                }
            } elseif ( qtranxf_external_host_ex( $urlinfo['host'], $homeinfo ) ) {
                $urlinfo['language_neutral'] = $url;

                //qtranxf_dbg_log('qtranxf_get_url_for_language: language_neutral: external host: ',$urlinfo);
                return $url;
            }

            if ( qtranxf_language_neutral_path( $urlinfo['wp-path'] ) ) {
                $urlinfo['language_neutral'] = $url;

                //qtranxf_dbg_log('qtranxf_get_url_for_language: language_neutral: wp-path: ',$urlinfo);
                return $url;
            }

            qtranxf_url_del_language( $urlinfo );
        }
        $urlinfo['url_parsed'] = $url;
    }

    $urlinfo_lang = qtranxf_url_set_language( $urlinfo, $lang, $showLanguage );
    $complete     = qtranxf_buildURL( $urlinfo_lang, $homeinfo );

    if ( $showLanguage ) {
        $urlinfo[ $lang ] = $complete;
    } else {
        $urlinfo['bare'] = $complete;
    }
    if ( ! isset( $url_cache[ $complete ] ) ) {
        $url_cache[ $complete ] = $urlinfo;
    }

    //qtranxf_dbg_log('done: qtranxf_get_url_for_language('.$lang.($showLanguage?', true':', false').'): $urlinfo=',$urlinfo,false);
    return $complete;
}

/**
 * Encode URL $url with language $lang.
 *
 * @param string $url URL to be converted.
 * @param string $lang two-letter language code of the language to convert $url to.
 * @param bool $forceadmin $url is not converted on admin side, unless $forceadmin is set to true.
 * @param bool $showDefaultLanguage When set to true, $url is always encoded with a language, otherwise it senses option "Hide URL language information for default language" to keep $url consistent with the currently active language.
 *
 * If you need a URL to switch the language, set $showDefaultLanguage=true, if you need a URL to keep the current language, set it to false.
 *
 * @return string
 */
function qtranxf_convertURL( $url = '', $lang = '', $forceadmin = false, $showDefaultLanguage = false ) {
    global $q_config;

    if ( empty( $lang ) ) {
        $lang = $q_config['language'];
    }
    if ( empty( $url ) ) {
        // TODO refactor this hack for qtranslate-slug! We might need a hook here.
        if ( $q_config['url_info']['doing_front_end'] && defined( 'QTS_VERSION' ) && $q_config['url_mode'] != QTX_URL_QUERY ) {
            // quick workaround, but need a permanent solution
            $url = qts_get_url( $lang );
            //qtranxf_dbg_log('qtranxf_convertURL: qts_get_url: url=', $url);
            if ( ! empty( $url ) ) {
                if ( $showDefaultLanguage && $q_config['hide_default_language'] && $lang == $q_config['default_language'] ) {
                    $url = qtranxf_convertURL( $url, $lang, $forceadmin, true );
                }

                return $url;
            }
        }
    }
    if ( ! $q_config['url_info']['doing_front_end'] && ! $forceadmin ) {
        return $url;
    }
    if ( ! qtranxf_isEnabled( $lang ) ) {
        return '';
    }

    if ( ! $showDefaultLanguage ) {
        $showDefaultLanguage = ! $q_config['hide_default_language'];
    }
    $showLanguage = $showDefaultLanguage || $lang != $q_config['default_language'];
    //qtranxf_dbg_log('qtranxf_convertURL('.$url.','.$lang.'): showLanguage=',$showLanguage);
    $complete = qtranxf_get_url_for_language( $url, $lang, $showLanguage );

    //qtranxf_dbg_log('qtranxf_convertURL: complete: ',$complete);
    return $complete;
}

function qtranxf_convertURLs( $url, $lang = '', $forceadmin = false, $showDefaultLanguage = false ) {
    global $q_config;
    if ( empty( $lang ) ) {
        $lang = $q_config['language'];
    }
    if ( is_array( $url ) ) {
        foreach ( $url as $k => $value ) {
            $url[ $k ] = qtranxf_convertURLs( $value, $lang, $forceadmin, $showDefaultLanguage );
        }

        return $url;
    } else if ( is_string( $url ) && ! empty( $url ) ) {
        return qtranxf_convertURL( $url, $lang, $forceadmin, $showDefaultLanguage );
    }

    return $url;
}

/**
 * split text at all language comments and quick tags
 * @since 3.3.6 swirly bracket encoding added
 */
function qtranxf_get_language_blocks( $text ) {
    $split_regex = "#(<!--:[a-z]{2}-->|<!--:-->|\[:[a-z]{2}\]|\[:\]|\{:[a-z]{2}\}|\{:\})#ism";

    return preg_split( $split_regex, $text, - 1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
}

function qtranxf_split( $text ) {
    $blocks = qtranxf_get_language_blocks( $text );

    return qtranxf_split_blocks( $blocks );
}

/*
 * @since 3.4.5.2 $found added
**/
function qtranxf_split_blocks( $blocks, &$found = array() ) {
    global $q_config;
    $result = array();
    foreach ( $q_config['enabled_languages'] as $language ) {
        $result[ $language ] = '';
    }
    $current_language = false;
    foreach ( $blocks as $block ) {
        // detect c-tags
        if ( preg_match( "#^<!--:([a-z]{2})-->$#ism", $block, $matches ) ) {
            $current_language = $matches[1];
            continue;
            // detect b-tags
        } elseif ( preg_match( "#^\[:([a-z]{2})]$#ism", $block, $matches ) ) {
            $current_language = $matches[1];
            continue;
            // detect s-tags @since 3.3.6 swirly bracket encoding added
        } elseif ( preg_match( "#^{:([a-z]{2})}$#ism", $block, $matches ) ) {
            $current_language = $matches[1];
            continue;
        }
        switch ( $block ) {
            case '[:]':
            case '{:}':
            case '<!--:-->':
                $current_language = false;
                break;
            default:
                // correctly categorize text block
                if ( $current_language ) {
                    if ( ! isset( $result[ $current_language ] ) ) {
                        $result[ $current_language ] = '';
                    }
                    $result[ $current_language ] .= $block;
                    $found[ $current_language ]  = true;
                    $current_language            = false;
                } else {
                    foreach ( $q_config['enabled_languages'] as $language ) {
                        $result[ $language ] .= $block;
                    }
                }
                break;
        }
    }
    // it gets trimmed later in qtranxf_use() anyway, better to do it here
    foreach ( $result as $lang => $text ) {
        $result[ $lang ] = trim( $text );
    }

    return $result;
}

/**
 * gets only part with encoded languages
 */
function qtranxf_split_languages( $blocks ) {
    $result           = array();
    $current_language = false;
    foreach ( $blocks as $block ) {
        // detect c-tags
        if ( preg_match( "#^<!--:([a-z]{2})-->$#ism", $block, $matches ) ) {
            $current_language = $matches[1];
            continue;
            // detect b-tags
        } elseif ( preg_match( "#^\[:([a-z]{2})]$#ism", $block, $matches ) ) {
            $current_language = $matches[1];
            continue;
            // detect s-tags @since 3.3.6 swirly bracket encoding added
        } elseif ( preg_match( "#^{:([a-z]{2})}$#ism", $block, $matches ) ) {
            $current_language = $matches[1];
            continue;
        }
        switch ( $block ) {
            case '[:]':
            case '{:}':
            case '<!--:-->':
                $current_language = false;
                break;
            default:
                // correctly categorize text block
                if ( $current_language ) {
                    if ( ! isset( $result[ $current_language ] ) ) {
                        $result[ $current_language ] = '';
                    }
                    $result[ $current_language ] .= $block;
                    $current_language            = false;
                }
                break;
        }
    }
    // it gets trimmed later in qtranxf_getAvailableLanguages() anyway, better to do it here
    foreach ( $result as $lang => $text ) {
        $result[ $lang ] = trim( $text );
    }

    return $result;
}

function qtranxf_allthesame( $texts ) {
    $text = null;
    // take first not empty
    foreach ( $texts as $lang => $lang_text ) {
        if ( ! $lang_text || $lang_text == '' ) {
            continue;
        }
        $text = $lang_text;
        break;
    }
    if ( empty( $text ) ) {
        return '';
    }
    foreach ( $texts as $lang => $lang_text ) {
        if ( $lang_text != $text ) {
            return null;
        }
    }

    return $text;
}

function qtranxf_join_c( $texts ) {
    $text = qtranxf_allthesame( $texts );
    if ( ! is_null( $text ) ) {
        return $text;
    }
    $text = '';
    foreach ( $texts as $lang => $lang_text ) {
        if ( empty( $lang_text ) ) {
            continue;
        }
        $text .= '<!--:' . $lang . '-->' . $lang_text . '<!--:-->';
    }

    return $text;
}

function qtranxf_join_b_no_closing( $texts ) {
    $text = qtranxf_allthesame( $texts );
    if ( ! is_null( $text ) ) {
        return $text;
    }
    $text = '';
    foreach ( $texts as $lang => $lang_text ) {
        if ( empty( $lang_text ) ) {
            continue;
        }
        $text .= '[:' . $lang . ']' . $lang_text;
    }

    return $text;
}

function qtranxf_join_b( $texts ) {
    $text = qtranxf_allthesame( $texts );
    if ( ! is_null( $text ) ) {
        return $text;
    }
    $text = '';
    foreach ( $texts as $lang => $lang_text ) {
        if ( empty( $lang_text ) ) {
            continue;
        }
        $text .= '[:' . $lang . ']' . $lang_text;
    }
    if ( ! empty( $text ) ) {
        $text .= '[:]';
    }

    return $text;
}

/**
 * @since 3.3.6 swirly bracket encoding
 */
function qtranxf_join_s( $texts ) {
    $text = qtranxf_allthesame( $texts );
    if ( ! is_null( $text ) ) {
        return $text;
    }
    $text = '';
    foreach ( $texts as $lang => $lang_text ) {
        if ( empty( $lang_text ) ) {
            continue;
        }
        $text .= '{:' . $lang . '}' . $lang_text;
    }
    if ( ! empty( $text ) ) {
        $text .= '{:}';
    }

    return $text;
}

/**
 * Prepares multilingual text leaving text that matches $regex_sep outside of language tags.
 * @since 3.4.6.2
 */
function qtranxf_join_byseparator( $texts, $regex_sep ) {
    $text = qtranxf_allthesame( $texts );
    if ( ! is_null( $text ) ) {
        return $text;
    }

    $lines = array();
    foreach ( $texts as $lang => $lang_text ) {
        $lines[ $lang ] = preg_split( $regex_sep, $lang_text, null, PREG_SPLIT_DELIM_CAPTURE );
    }

    $text = '';
    while ( true ) {
        $done    = true;
        $to_join = array();
        $sep     = '';
        foreach ( $lines as $lang => $lang_lines ) {
            $t = next( $lang_lines );
            if ( $t === false ) {
                continue;
            }
            if ( preg_match( $regex_sep, $t ) ) {
                $sep = $t;
                $t   = next( $lang_lines );
            }
            $done             = false;
            $to_join[ $lang ] = $t;
        }
        if ( $done ) {
            break;
        }
        $text .= qtranxf_join_b( $to_join ) . $sep;
    }

    return $text;
}

/**
 * Prepare multilingal text leaving new line outside of language tags '[:]'.
 */
function qtranxf_join_byline( $texts ) {
    $text = qtranxf_allthesame( $texts );
    if ( ! is_null( $text ) ) {
        return $text;
    }

    $lines = array();
    foreach ( $texts as $lang => $text ) {
        $lines[ $lang ] = preg_split( '/\r?\n\r?/', $text );
    }

    $text = '';
    for ( $i = 0; true; ++ $i ) {
        $done    = true;
        $to_join = array();
        foreach ( $lines as $lang => $lang_lines ) {
            if ( sizeof( $lang_lines ) <= $i ) {
                continue;
            }
            $done = false;
            $line = $lang_lines[ $i ];
            if ( ! $line || $line == '' ) {
                continue;
            }
            $to_join[ $lang ] = $line;
        }
        if ( $done ) {
            break;
        }
        $text .= qtranxf_join_b( $to_join ) . PHP_EOL;
    }

    return $text;
}

function qtranxf_use( $lang, $text, $show_available = false, $show_empty = false ) {
    // return full string if language is not enabled
    if ( is_array( $text ) ) {
        // handle arrays recursively
        foreach ( $text as $key => $t ) {
            $text[ $key ] = qtranxf_use( $lang, $t, $show_available, $show_empty );
        }

        return $text;
    }

    if ( is_object( $text ) || $text instanceof __PHP_Incomplete_Class ) {
        foreach ( get_object_vars( $text ) as $key => $t ) {
            if ( ! isset( $text->$key ) ) {
                continue;
            }
            $text->$key = qtranxf_use( $lang, $t, $show_available, $show_empty );
        }

        return $text;
    }

    // prevent filtering weird data types and save some resources
    if ( ! is_string( $text ) || empty( $text ) ) {
        return $text;
    }

    return qtranxf_use_language( $lang, $text, $show_available, $show_empty );
}

/** when $text is already known to be string */
function qtranxf_use_language( $lang, $text, $show_available = false, $show_empty = false ) {
    $blocks = qtranxf_get_language_blocks( $text );
    if ( count( $blocks ) <= 1 )//no language is encoded in the $text, the most frequent case
    {
        return $text;
    }

    return qtranxf_use_block( $lang, $blocks, $show_available, $show_empty );
}

function qtranxf_use_block( $lang, $blocks, $show_available = false, $show_empty = false ) {
    //qtranxf_dbg_log('qtranxf_use_block:('.$lang.') $blocks: ', $blocks);
    $available_langs = array();
    $content         = qtranxf_split_blocks( $blocks, $available_langs );
    //qtranxf_dbg_log('qtranxf_use_block: $content: ',$content);
    //qtranxf_dbg_log('qtranxf_use_block: $available_langs: ',$available_langs);
    return qtranxf_use_content( $lang, $content, $available_langs, $show_available, $show_empty );
}

function qtranxf_use_content( $lang, $content, $available_langs, $show_available = false, $show_empty = false ) {
    global $q_config;
    // show the content in the requested language, if available
    if ( ! empty( $available_langs[ $lang ] ) ) {
        return $content[ $lang ];
    } elseif ( $show_empty ) {
        return '';
    }

    // content is not available in requested language (bad!!) what now?
    $alangs = array();
    foreach ( $q_config['enabled_languages'] as $language ) {
        if ( empty( $available_langs[ $language ] ) ) {
            continue;
        }
        $alangs[] = $language;
    }
    if ( empty( $alangs ) ) {
        return '';
    }

    $available_langs = $alangs;
    // set alternative language to the first available in the order of enabled languages
    $alt_lang    = current( $available_langs );
    $alt_content = $content[ $alt_lang ];

    if ( ! $show_available ) {
        if ( $q_config['show_displayed_language_prefix'] ) {
            return '(' . $q_config['language_name'][ $alt_lang ] . ') ' . $alt_content;
        } else {
            return $alt_content;
        }
    }
    //qtranxf_dbg_log('$alt_content=',$alt_content);

    // display selection for available languages
    $language_list = '';
    if ( preg_match( '/%LANG:([^:]*):([^%]*)%/', $q_config['not_available'][ $lang ], $match ) ) {
        $normal_separator = $match[1];
        $end_separator    = $match[2];
        // build available languages string backward
        $i = 0;
        foreach ( array_reverse( $available_langs ) as $language ) {
            if ( $i == 1 ) {
                $language_list = $end_separator . $language_list;
            } elseif ( $i > 1 ) {
                $language_list = $normal_separator . $language_list;
            }
            $language_name = qtranxf_getLanguageName( $language );
            //$language_list = '&ldquo;<a href="'.qtranxf_convertURL('', $language, false, true).'" class="qtranxs-available-language-link qtranxs-available-language-link-'.$language.'">'.$language_name.'</a>&rdquo;'.$language_list;
            $language_list = '<a href="' . qtranxf_convertURL( '', $language, false, true ) . '" class="qtranxs-available-language-link qtranxs-available-language-link-' . $language . '" title="' . $q_config['language_name'][ $language ] . '">' . $language_name . '</a>' . $language_list;
            ++ $i;
        }
    }
    //qtranxf_dbg_log('$language_list=',$language_list);

    $msg    = preg_replace( '/%LANG:([^:]*):([^%]*)%/', $language_list, $q_config['not_available'][ $lang ] );
    $output = '<p class="qtranxs-available-languages-message qtranxs-available-languages-message-' . $lang . '">' . $msg . '</p>';
    if ( ! empty( $q_config['show_alternative_content'] ) && $q_config['show_alternative_content'] ) {
        $output .= $alt_content;
    }

    return apply_filters( 'i18n_content_translation_not_available', $output, $lang, $language_list, $alt_lang, $alt_content, $msg, $q_config );
}

function qtranxf_showAllSeparated( $text ) {
    if ( empty( $text ) ) {
        return $text;
    }
    global $q_config;
    $result = '';
    foreach ( qtranxf_getSortedLanguages() as $language ) {
        $result .= $q_config['language_name'][ $language ] . ':' . PHP_EOL . qtranxf_use( $language, $text ) . PHP_EOL . PHP_EOL;
    }

    return $result;
}

/**
 * Add specific rewrites to handle API REST for the default language, when hidden in QTX_URL_PATH mode.
 * Most of the requests don't need this, as they are handled through custom home_url with the language.
 * Note: to make it work you have to flush your rewrite rules by saving the permalink structures from the admin page!
 *
 * Example with 'en' as default language and hidden option enabled:
 *   /wp-json/wp/...    -> home_url = '/' (default, hidden)
 *   /fr/wp-json/wp/... -> home_url = '/fr'
 *   /en/wp-json/wp/... -> home_url = '/' (default, hidden but requested) -> fails with standard rewrites (404)
 * This function allows to handle specifically this last case.
 *
 * @see rest_api_register_rewrites in wp_includes/rest-api.php
 */
function qtranxf_rest_api_register_rewrites() {
    global $q_config;
    if ( ! $q_config['hide_default_language'] || $q_config['url_mode'] !== QTX_URL_PATH ) {
        return;
    }

    global $wp_rewrite;
    $default_lang = $q_config['default_language'];
    add_rewrite_rule( '^' . $default_lang . '/' . rest_get_url_prefix() . '/?$', 'index.php?rest_route=/', 'top' );
    add_rewrite_rule( '^' . $default_lang . '/' . rest_get_url_prefix() . '/(.*)?', 'index.php?rest_route=/$matches[1]', 'top' );
    add_rewrite_rule( '^' . $default_lang . '/' . $wp_rewrite->index . '/' . rest_get_url_prefix() . '/?$', 'index.php?rest_route=/', 'top' );
    add_rewrite_rule( '^' . $default_lang . '/' . $wp_rewrite->index . '/' . rest_get_url_prefix() . '/(.*)?', 'index.php?rest_route=/$matches[1]', 'top' );
}

// core setup
add_action( 'plugins_loaded', 'qtranxf_init_language', 2 ); // user is not authenticated yet
add_action( 'init', 'qtranxf_init', 2 ); // user is authenticated
add_action( 'init', 'qtranxf_rest_api_register_rewrites', 11 );
