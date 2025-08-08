<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Default domain translation for strings already translated by WordPress.
 * Use of this function prevents xgettext, poedit and other translating parsers from including the string that does not need translation.
 */
function qtranxf_translate_wp( $string ): ?string {
    return __( $string );
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
function qtranxf_dir_from_wp_content( string $plugin ): string {
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
        ++$i;
    }
    if ( $i == $content_len ) {
        return substr( $plugin_dir, $content_len );
    }
    if ( $i == 0 ) {
        return $plugin_dir; // absolute path
    }

    $content_dir = substr( $content_dir, $i );
    $plugin_dir  = substr( $plugin_dir, $i );
    for ( $i = substr_count( $content_dir, '/' ); --$i >= 0; ) {
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
function qtranxf_plugin_dirname_from_wp_content(): string {
    static $dirname;
    if ( ! $dirname ) {
        $dirname = qtranxf_dir_from_wp_content( QTRANSLATE_FILE );
    }

    return $dirname;
}

function qtranxf_get_address_info( string $url ): array {
    $info = qtranxf_parseURL( $url );
    if ( ! isset( $info['path'] ) ) {
        $info['path'] = '';
    }

    return $info;
}

function qtranxf_get_home_info(): array {
    static $home_info;
    if ( ! $home_info ) {
        $url       = get_option( 'home' );
        $home_info = qtranxf_get_address_info( $url );
    }

    return $home_info;
}

function qtranxf_get_site_info(): array {
    static $site_info;
    if ( ! $site_info ) {
        $url       = get_option( 'siteurl' );
        $site_info = qtranxf_get_address_info( $url );
    }

    return $site_info;
}

/**
 * Simplified version of WP's add_query_arg
 * @since 3.2.8
 */
function qtranxf_add_query_arg( ?string &$query, string $key_value ): void {
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
function qtranxf_del_query_arg( ?string &$query, string $key ): void {
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


function qtranxf_insertDropDownElement( string $language, string $url, $id ): string {
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

/**
 * @since 3.2.8 - change code to improve performance
 */
function qtranxf_startsWith( string $string, string $needle ): bool {
    $len = strlen( $needle );
    if ( $len > strlen( $string ) ) {
        return false;
    }
    for ( $i = 0; $i < $len; ++$i ) {
        if ( $string[ $i ] != $needle[ $i ] ) {
            return false;
        }
    }

    return true;
}

/**
 * @since 3.2.8
 */
function qtranxf_endsWith( string $string, string $needle ): bool {
    $len  = strlen( $needle );
    $base = strlen( $string ) - $len;
    if ( $base < 0 ) {
        return false;
    }
    for ( $i = 0; $i < $len; ++$i ) {
        if ( $string[ $base + $i ] != $needle[ $i ] ) {
            return false;
        }
    }

    return true;
}

/**
 * Evaluate if the request URI leads to a REST call.
 * This is only a prediction based on REST prefix, but no strict guarantee the REST request will be processed as such.
 *
 * @return bool
 * @see parse_request in wp_includes/class-wp.php for the final processing of REQUEST_URI
 * @see rest_api_register_rewrites in wp_includes/rest-api.php for the REST rewrite rules using query_var = rest_route
 */
function qtranxf_is_rest_request_expected(): bool {
    return stripos( $_SERVER['REQUEST_URI'], '/' . rest_get_url_prefix() . '/' ) !== false;
}

/**
 * Evaluate if the request URI leads to a GraphQL API call.
 *
 * @return bool
 * @see is_graphql_http_request in https://github.com/wp-graphql/wp-graphql/blob/develop/src/Router.php
 */
function qtranxf_is_graphql_request_expected(): bool {
    return function_exists( 'is_graphql_http_request' ) && is_graphql_http_request();
}

/**
 * Evaluate if the request is an AJAX request, from WordPress or another plugin.
 *
 * @return bool
 * @link https://datatracker.ietf.org/doc/html/rfc6648 RFC6648 Deprecating the "X-" Prefix
 */
function qtranxf_is_ajax_request(): bool {
    return wp_doing_ajax() ||
           ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' ) ||
           ( isset( $_SERVER['HTTP_REQUESTED_WITH'] ) && $_SERVER['HTTP_REQUESTED_WITH'] === 'XMLHttpRequest' );
}

/**
 * Evaluate if the current request allows HTTP redirection.
 * Admin requests (WP_ADMIN, DOING_AJAX, WP_CLI, DOING_CRON) or REST calls should not be redirected.
 *
 * @return bool
 */
function qtranxf_can_redirect(): bool {
    return ! is_admin() && ! qtranxf_is_ajax_request() && ! ( defined( 'WP_CLI' ) && WP_CLI ) && ! wp_doing_cron() && empty( $_POST )
           && ( ! qtranxf_is_rest_request_expected() )
           && ( ! qtranxf_is_graphql_request_expected() )
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
        return $post->post_type;
    }
    if ( isset( $_REQUEST['post_type'] ) ) {
        return $_REQUEST['post_type'];
    }

    return null;
}

/**
 * Test $cfg['pages'] against $url_path and $url_query ($_SERVER['QUERY_STRING'])
 * @since 3.4
 */
function qtranxf_match_page( ?array $cfg, string $url_path, string $url_query, string $d ): bool {
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
function qtranxf_match_post_type( $cfg_post_type, $post_type ): ?bool {

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
function qtranxf_merge_config( array $cfg_all, array $cfg ): array {
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
function qtranxf_parse_page_config( array $config, string $url_path, string $url_query ): array {
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
        $delimiter = $pgcfg['preg_delimiter'] ?? '!';
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

        // Store all page config keys when selectors exist (pages or post_type)
        if ( array_key_exists( 'pages', $pgcfg ) || ! empty( $post_type_key ) ) {
            if ( ! isset ( $page_config['keys'] ) ) {
                $page_config['keys'] = array( $pgkey );
            } else {
                $page_config['keys'][] = $pgkey;
            }
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

function qtranxf_write_config_log( array $config, string $suffix = '', ?string $url_path = null, ?string $url_query = null, $post_type = null ): void {
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
        $url_query = $q_config['url_info']['query'] ?? '';
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
function qtranxf_add_filters( array $filters ): void {
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
function qtranxf_remove_filters( array $filters ): void {
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
function qtranxf_html_locale( string $locale ): string {
    return str_replace( '_', '-', $locale );
}

function qtranxf_match_language_locale( string $locale ): ?string {
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

