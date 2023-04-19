<?php

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
function qtranxf_convertURL( string $url = '', string $lang = '', bool $forceadmin = false, bool $showDefaultLanguage = false ): string {
    global $q_config;

    if ( empty( $lang ) ) {
        $lang = $q_config['language'];
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

    $url = apply_filters( 'qtranslate_convert_url', $url, $lang );

    return qtranxf_get_url_for_language( $url, $lang, $showLanguage );
}

function qtranxf_convertURLs( $url, string $lang = '', bool $forceadmin = false, bool $showDefaultLanguage = false ) {
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
 * @since 3.0
 */
function qtranxf_url_del_language( array &$urlinfo ): void {
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
            $lang_code = QTX_LANG_CODE_FORMAT;
            if ( ! empty( $urlinfo['wp-path'] ) && preg_match( "!^/($lang_code)(/|$)!i", $urlinfo['wp-path'], $match ) ) {
                if ( qtranxf_isEnabled( $match[1] ) ) {
                    // found language information, remove it
                    $urlinfo['wp-path'] = substr( $urlinfo['wp-path'], strlen( $match[1] ) + 1 );
                }
            }
            break;

        case QTX_URL_DOMAIN:
            // remove language information
            $homeinfo        = qtranxf_get_home_info();
            $urlinfo['host'] = $homeinfo['host'];
            break;

        case QTX_URL_DOMAINS:
            if ( isset( $q_config['domains'][ $q_config['default_language'] ] ) ) {
                $urlinfo['host'] = $q_config['domains'][ $q_config['default_language'] ];
            }
            break;

        case QTX_URL_QUERY:
            break;

        default:
            $urlinfo = apply_filters( 'qtranslate_url_del_language', $urlinfo, $url_mode );
            break;
    }
}

function qtranxf_url_set_language( array $urlinfo, $lang, bool $showLanguage ): array {
    global $q_config;
    $urlinfo = qtranxf_copy_url_info( $urlinfo );
    if ( $showLanguage ) {
        $url_mode = $q_config['url_mode'];
        switch ( $url_mode ) {
            case QTX_URL_PATH:
                $urlinfo['wp-path'] = '/' . $lang . $urlinfo['wp-path'];
                break;

            case QTX_URL_DOMAIN:
                $urlinfo['host'] = $lang . '.' . $urlinfo['host'];
                break;

            case QTX_URL_DOMAINS:
                if ( isset( $q_config['domains'][ $lang ] ) ) {
                    $urlinfo['host'] = $q_config['domains'][ $lang ];
                }
                break;

            case QTX_URL_QUERY:
                qtranxf_add_query_arg( $urlinfo['query'], 'lang=' . $lang );
                break;
        }
        $urlinfo = apply_filters( 'qtranslate_url_set_language', $urlinfo, $lang, $url_mode );
    }


    // see if cookies are activated
    if ( ! $showLanguage//there still is no language information in the converted URL
         && ! $q_config['url_info']['cookie_front_or_admin_found']// there will be no way to take language from the cookie
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

function qtranxf_get_url_for_language( string $url, string $lang, bool $showLanguage = true ): string {
    global $q_config;
    static $url_cache = array();
    if ( ! isset( $url_cache[ $url ] ) ) {
        $url_cache[ $url ] = array();
    }
    $urlinfo = &$url_cache[ $url ];

    if ( $showLanguage ) {
        if ( isset( $urlinfo[ $lang ] ) ) {
            return $urlinfo[ $lang ];
        }
    } else {
        if ( isset( $urlinfo['bare'] ) ) {
            return $urlinfo['bare'];
        }
    }

    if ( isset( $urlinfo['language_neutral'] ) ) {
        return $urlinfo['language_neutral'];
    }

    $homeinfo = qtranxf_get_home_info();
    if ( ! isset( $urlinfo['url_parsed'] ) ) {
        if ( empty( $url ) ) {
            $urlinfo = qtranxf_copy_url_info( $q_config['url_info'] );

            if ( isset( $urlinfo['wp-path'] ) && qtranxf_language_neutral_path( $urlinfo['wp-path'] ) ) {
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

                return $url;
            }

            if ( empty( $urlinfo['host'] ) ) {
                if ( empty( $urlinfo['wp-path'] ) ) {
                    if ( empty( $urlinfo['query'] ) ) {
                        $urlinfo['language_neutral'] = $url;

                        return $url;
                    }
                } else {
                    switch ( $urlinfo['wp-path'][0] ) {
                        case '/':
                            break;
                        case '#':
                        {
                            $urlinfo['language_neutral'] = $url;

                            return $url;
                        }
                        default:
                            $urlinfo['wp-path'] = trailingslashit( $q_config['url_info']['wp-path'] ) . $urlinfo['wp-path'];
                            break;
                    }
                }
            } elseif ( qtranxf_external_host_ex( $urlinfo['host'], $homeinfo ) ) {
                $urlinfo['language_neutral'] = $url;

                return $url;
            }

            if ( qtranxf_language_neutral_path( $urlinfo['wp-path'] ) ) {
                $urlinfo['language_neutral'] = $url;

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

    return $complete;
}

// check if it is a link to an ignored file type
function qtranxf_ignored_file_type( string $path ): bool {
    global $q_config;
    $i = strpos( $path, '?' );
    if ( $i !== false ) {
        $path = substr( $path, 0, $i );
    }
    $i = strpos( $path, '#' );
    if ( $i !== false ) {
        $path = substr( $path, 0, $i );
    }
    $i = strrpos( $path, '.' );
    if ( $i === false ) {
        return false;
    }
    $ext = substr( $path, $i + 1 );

    return in_array( $ext, $q_config['ignore_file_types'] );
}

function qtranxf_language_neutral_path( string $path ): bool {
    if ( empty( $path ) ) {
        return false;
    }
    static $language_neutral_path_cache;
    if ( isset( $language_neutral_path_cache[ $path ] ) ) {
        return $language_neutral_path_cache[ $path ];
    }

    if ( preg_match( '#^/(wp-.*\.php|'.qtranxf_get_login_base().'/|'.qtranxf_get_admin_base().'/|xmlrpc.php|robots.txt|oauth/)#', $path ) ) {
        $language_neutral_path_cache[ $path ] = true;

        return true;
    }
    if ( qtranxf_ignored_file_type( $path ) ) {
        $language_neutral_path_cache[ $path ] = true;

        return true;
    }
    $language_neutral_path_cache[ $path ] = false;

    return false;
}

/*
 * @since 2.3.8 simplified version of esc_url
 */
function qtranxf_sanitize_url( string $url ): string {
    $url   = preg_replace( '|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\[\]\\x80-\\xff]|i', '', $url );
    $strip = array( '%0d', '%0a', '%0D', '%0A' );
    do {
        $url = str_replace( $strip, '', $url, $count );
    } while ( $count );

    return $url;
}


function qtranxf_get_url_info( string $url ): array {
    $urlinfo = qtranxf_parseURL( $url );
    qtranxf_complete_url_info( $urlinfo );
    qtranxf_complete_url_info_path( $urlinfo );

    return $urlinfo;
}

/**
 * Returns the base admin url of the WordPress backend name e.g. wp-admin
 *
 * @author Sebastian Poetter https://github.com/poetter-sebastian
 * @link https://github.com/qtranslate/qtranslate-xt/pull/1324 repo pull request
 *
 * @return string WordPress backend name
 */
function qtranxf_get_admin_base():string
{
    return trim( str_replace( site_url(), '', admin_url() ), '/');
}

/**
 * Returns the base admin url of the WordPress backend login url e.g. wp-login.php
 *
 * @author Sebastian Poetter https://github.com/poetter-sebastian
 * @link https://github.com/qtranslate/qtranslate-xt/pull/1324 repo pull request
 *
 * @return string WordPress backend login name
 */
function qtranxf_get_login_base():string
{
    return trim( str_replace( site_url(), '', wp_login_url() ), '/');
}

/**
 * Complete urlinfo with 'path-base' according to home and site info.
 * If they differ, 'doing_front_end' might be set.
 *
 * @param array $urlinfo
 */
function qtranxf_complete_url_info( array &$urlinfo ): void {
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
function qtranxf_complete_url_info_path( array &$urlinfo ): void {
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

function qtranxf_parseURL( string $url ): array {
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
function qtranxf_buildURL( array $urlinfo, array $homeinfo ): string {
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
        $url .= $urlinfo['host'];
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
function qtranxf_copy_url_info( array $urlinfo ): array {
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

function qtranxf_external_host_ex( string $host, array $homeinfo ): bool {
    global $q_config;

    switch ( $q_config['url_mode'] ) {
        case QTX_URL_QUERY:
        case QTX_URL_PATH:
            return $homeinfo['host'] != $host;
        case QTX_URL_DOMAIN:
            return ! qtranxf_endsWith( $host, $homeinfo['host'] );
        case QTX_URL_DOMAINS:
            return ( $homeinfo['host'] != $host && isset( $q_config['domains'] ) && ! in_array( $host, $q_config['domains'] ) );
        default:
            return true;
    }
}

function qtranxf_external_host( string $host ): bool {
    $homeinfo = qtranxf_get_home_info();

    return qtranxf_external_host_ex( $host, $homeinfo );
}

function qtranxf_get_page_referer(): string {
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
