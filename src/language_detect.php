<?php
require_once QTRANSLATE_DIR . '/src/modules/module_loader.php';

function qtranxf_detect_language( array &$url_info ) {
    global $q_config;

    if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
        $siteinfo              = qtranxf_get_site_info();
        $url_info['path-base'] = $siteinfo['path'];
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
                        ( qtranxf_is_ajax_request() || ! $url_info['cookie_front_or_admin_found'] ) );

    // parse language and front info from HTTP_REFERER
    if ( isset( $_SERVER['HTTP_REFERER'] ) && $parse_referrer ) {
        $http_referer             = $_SERVER['HTTP_REFERER'];
        $url_info['http_referer'] = $http_referer;
        // if needed, detect front- vs back-end
        $parse_referrer_language = true;
        // Attention: the referrer can be a full or partial path.
        // TODO: improve check if admin base is found at other positions in URL
        if ( strpos( $http_referer, '/' . qtranxf_get_admin_base() ) !== false ) {
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
            if ( isset( $ref_info['host'] ) && ! qtranxf_external_host( $ref_info['host'] ) ) {
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

    // REST and GraphQL API calls should be deterministic (stateless), no special language detection e.g. based on cookie
    $url_info['set_cookie'] = ! ( qtranxf_is_ajax_request() || qtranxf_is_rest_request_expected() || qtranxf_is_graphql_request_expected() );

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

function qtranxf_resolveLangCase( string $lang, ?bool &$caseredirect ): string {
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

/**
 * Parse language from the URL and/or query var, update url_info accordingly
 *
 * Expects to be set before call:
 * - $url_info['host']
 * - $url_info['path']
 * - $url_info['query']
 * - $url_info['path-base']
 *
 * @param array $url_info
 * @param bool $link true when url_info concerns internal referrer (HTTP_REFERER)
 *
 * @return bool|string
 */
function qtranxf_parse_language_info( array &$url_info, bool $link = false ) {
    global $q_config;

    qtranxf_complete_url_info_path( $url_info );
    if ( ! isset( $url_info['wp-path'] ) ) {
        return false;   // url is not from this WP installation
    }

    $lang_code  = QTX_LANG_CODE_FORMAT;
    $doredirect = false;

    // parse URL lang
    if ( ! is_admin() || $link ) {
        $url_mode = $q_config['url_mode'];
        switch ( $url_mode ) {
            case QTX_URL_PATH:
                if ( ! empty( $url_info['wp-path'] ) && preg_match( "!^/($lang_code)(/|$)!i", $url_info['wp-path'], $match ) ) {
                    $lang = qtranxf_resolveLangCase( $match[1], $doredirect );
                    if ( $lang ) {
                        $url_info['lang_url']        = $lang;
                        $url_info['wp-path']         = substr( $url_info['wp-path'], strlen( $lang ) + 1 );
                        $url_info['doing_front_end'] = true;
                    }
                }
                break;

            case QTX_URL_DOMAIN:
                if ( ! empty( $url_info['host'] ) ) {
                    if ( preg_match( "#^($lang_code)\.#i", $url_info['host'], $match ) ) {
                        $lang = qtranxf_resolveLangCase( $match[1], $doredirect );
                        if ( $lang ) {
                            $url_info['lang_url']        = $lang;
                            $url_info['host']            = substr( $url_info['host'], strlen( $lang ) + 1 );
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
        if ( isset( $_GET['lang'] ) && is_string( $_GET['lang'] ) ) {
            $query_lang = qtranxf_resolveLangCase( $_GET['lang'], $doredirect );
            if ( $query_lang ) {
                $url_info['lang_query_get'] = $query_lang;  // only used in qtranxf_url_set_language
            }
        } else if ( isset( $_POST['lang'] ) && is_string( $_POST['lang'] ) ) {
            $query_lang = qtranxf_resolveLangCase( $_POST['lang'], $doredirect );
        }
    } elseif ( ! empty( $url_info['query'] ) && preg_match( '/(^|&|&amp;|&#038;|\?)lang=($lang_code)/i', $url_info['query'], $match ) ) {
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
        $url_info['language_neutral_path'] = qtranxf_language_neutral_path( $url_info['wp-path'] );
        if ( ! $url_info['language_neutral_path'] ) {
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

function qtranxf_detect_language_admin( array &$url_info ): string {
    require_once QTRANSLATE_DIR . '/src/admin/admin_utils.php';
    $url_info = apply_filters( 'qtranslate_detect_admin_language', $url_info );

    return $url_info['lang_admin'];
}

function qtranxf_detect_language_front( array &$url_info ): string {
    global $q_config;

    $lang = null;
    if ( ! $q_config['disable_client_cookies'] && isset( $_COOKIE[ QTX_COOKIE_NAME_FRONT ] ) ) {
        $cs                            = null;
        $lang                          = qtranxf_resolveLangCase( $_COOKIE[ QTX_COOKIE_NAME_FRONT ], $cs );
        $url_info['lang_cookie_front'] = $lang;
    }

    if ( ! $lang && $q_config['detect_browser_language']
         && ( ! isset( $_SERVER['HTTP_REFERER'] ) || strpos( $_SERVER['HTTP_REFERER'], $url_info['host'] ) === false ) // no or external referrer
    ) {
        $lang                     = qtranxf_http_negotiate_language();
        $url_info['lang_browser'] = $lang;
    }

    if ( ! $lang ) {
        $lang = $q_config['default_language'];
    }

    if ( ! isset( $url_info['doredirect'] )
         && ( ! qtranxf_is_rest_request_expected() ) // fallback case where language can be read from cookie with REST
         && ( ! qtranxf_is_graphql_request_expected() )
         && ( ! $q_config['hide_default_language'] || $lang != $q_config['default_language'] )
         && ( ! qtranxf_language_neutral_path( $url_info['wp-path'] ) )
    ) {
        $url_info['doredirect'] = 'language needs to be shown in url';
    }

    return $lang;
}

function qtranxf_setcookie_language( string $lang, string $cookie_name, string $cookie_path ): void {
    global $q_config;

    // Sometimes wp_cron.php SEEMS to be the source of headers being sent prematurely.
    if ( headers_sent() && wp_doing_cron() ) {
        return;
    }

    setcookie( $cookie_name, $lang, [
        'expires'  => strtotime( '+1year' ),
        'path'     => $cookie_path,
        'secure'   => $q_config['use_secure_cookie'],
        'httponly' => true,
        'samesite' => QTX_COOKIE_SAMESITE
    ] );
}

function qtranxf_set_language_cookie( string $lang ): void {
    global $q_config;

    assert( ! qtranxf_is_rest_request_expected() );
    if ( is_admin() ) {
        qtranxf_setcookie_language( $lang, QTX_COOKIE_NAME_ADMIN, ADMIN_COOKIE_PATH );
    } elseif ( ! $q_config['disable_client_cookies'] ) {
        qtranxf_setcookie_language( $lang, QTX_COOKIE_NAME_FRONT, COOKIEPATH );
    }
}

function qtranxf_get_browser_language(): ?string {
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
    foreach ( $prefered_languages as $language => $priority ) {
        $lang = qtranxf_match_language_locale( $language );
        if ( $lang ) {
            return $lang;
        }
    }

    return null;
}

function qtranxf_http_negotiate_language(): ?string {
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

/**
 * Disables default redirection when language is not detectable from url (e.g. default language url with hide_default_language activated).
 * Exception is made for site_url(), for which language detection feature is preserved.
 *
 * @param string $url_lang proposed target URL for the active language to redirect to.
 * @param string $url_orig original URL supplied to browser, which needs to be standardized.
 * @param array $url_info a hash of various information parsed from original URL, cookies and other site configuration.
 *
 * @return string resulting redirection url
 * @see qtranxf_check_url_maybe_redirect
 */
function qtranxf_slugs_language_detect_redirect( $url_lang, $url_orig, $url_info ): string {
    global $q_config;
    // Make sure URLs with no lang info are treated as default language, unless it's site_url
    if ( untrailingslashit( site_url() ) != untrailingslashit( $url_orig ) && empty( $url_info['lang_url'] ) ) {
        return qtranxf_convertURL( $url_orig, $q_config['default_language'], false, true );
        // The following fixes the case when the browser removes language marker from default language site URL (caching?) passing directly _$SERVER['REQUEST_URI'] with language info removed.
        // In this case is not possible to switch to default language from site URL.
        // Application of this hack is narrowed down to the cases where referer is site URL in current language, to preserve language detection feature as much as possible.
        // TODO: check if a cleaner fix is applicable.
    } else if ( $q_config['hide_default_language'] && untrailingslashit( site_url() ) == untrailingslashit( $url_orig ) && untrailingslashit( $url_lang ) === untrailingslashit( wp_get_raw_referer() ) ) {
        return qtranxf_convertURL( $url_orig, $q_config['default_language'], false, true );
    }

    return $url_lang;
}

/**
 * Check if a URL redirection (301 permanent) is needed and attempt to do so.
 * Two main causes of redirect:
 *  - the fetched URL info contains already a 'doredirect' order, previously set;
 *  - the URL is not canonical for the detected language.
 *  In case a redirection is needed, the request is filtered with 'qtranslate_language_detect_redirect'.
 *
 * @param $url_info
 *
 * @return void
 */
function qtranxf_check_url_maybe_redirect( &$url_info ) {
    global $q_config, $pagenow;

    $lang     = $q_config['language'];
    $url_orig = $url_info['scheme'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $url_lang = qtranxf_convertURL( '', $lang ); // uses $q_config['url_info'] and caches information, which will be needed later anyway.
    if ( ! isset( $url_info['doredirect'] ) && $url_orig != $url_lang ) {
        $url_info['doredirect'] = '$url_orig != $url_lang';
    }
    if ( isset( $url_info['doredirect'] ) ) {
        if ( QTX_Module_Loader::is_module_active( 'slugs' ) ) {
            // Special redirect handling with Slugs
            $url_lang = qtranxf_slugs_language_detect_redirect( $url_lang, $url_orig, $url_info );
        }

        /**
         * Filter for the redirect behaviour.
         *
         * @param string $url_lang proposed target URL for the active language to redirect to.
         * @param string $url_orig original URL supplied to browser, which needs to be standardized.
         * @param array $url_info a hash of various information parsed from original URL, cookies and other site configuration. The key names should be self-explanatory.
         *
         * @return mixed A new URL to be redirected to instead of $url_lang or "false" to cancel redirection.
         */
        $target = apply_filters( 'qtranslate_language_detect_redirect', $url_lang, $url_orig, $url_info );
        if ( $target !== false && $target != $url_orig ) {
            wp_redirect( $target, 301, 'qTranslate-XT' );  # Permanent redirect.
            nocache_headers(); // prevent browser from caching redirection
            exit();
        } else {
            // neutral path
            $url_info['doredirect'] .= ' - cancelled, because it goes to the same target - neutral URL';
            if ( $pagenow == 'index.php' && $q_config['url_mode'] == QTX_URL_PATH ) {
                $_SERVER['REQUEST_URI'] = trailingslashit( $url_info['path-base'] ) . $lang . $url_info['wp-path']; // should not hurt?
            }
        }
    }
}
