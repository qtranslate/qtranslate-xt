<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once QTRANSLATE_DIR . '/src/language_blocks.php';
require_once QTRANSLATE_DIR . '/src/language_config.php';
require_once QTRANSLATE_DIR . '/src/language_detect.php';
require_once QTRANSLATE_DIR . '/src/options.php';
require_once QTRANSLATE_DIR . '/src/url.php';
require_once QTRANSLATE_DIR . '/src/modules/module_loader.php';

function qtranxf_init_language() {
    global $q_config, $pagenow;

    qtranxf_load_config();

    // 'url_info' hash is not for external use, it is subject to change at any time.
    // 'url_info' is preserved on reloadConfig
    if ( ! isset( $q_config['url_info'] ) ) {
        $q_config['url_info'] = array();
    }

    $url_info = &$q_config['url_info'];

    // TODO clarify url_info fields that are exposed in API
    if ( ! $q_config['disable_client_cookies'] && isset( $_COOKIE[ QTX_COOKIE_NAME_FRONT ] ) ) {
        $url_info['cookie_lang_front'] = $_COOKIE[ QTX_COOKIE_NAME_FRONT ];
    }
    if ( isset( $_COOKIE[ QTX_COOKIE_NAME_ADMIN ] ) ) {
        $url_info['cookie_lang_admin'] = $_COOKIE[ QTX_COOKIE_NAME_ADMIN ];
    }
    // TODO this field should be removed, to be avoided as much as possible!
    $url_info['cookie_front_or_admin_found'] = isset ( $url_info['cookie_lang_front'] ) || isset( $url_info['cookie_lang_admin'] );

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

    $url_info['language'] = qtranxf_detect_language( $url_info );
    $q_config['language'] = apply_filters( 'qtranslate_language', $url_info['language'], $url_info );

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
        }
    } elseif ( isset( $url_info['doredirect'] ) ) {
        $url_info['doredirect'] .= ' - cancelled by can_redirect';
        // this should never happen! We are now in a bad state.
        assert( false, $url_info['doredirect'] . ', url_info=' . json_encode( $url_info, JSON_PRETTY_PRINT ) );
    }

    // TODO clarify fix url to prevent xss - how does this prevents xss?
    // $q_config['url_info']['url'] = qtranxf_convertURL(add_query_arg('lang',$q_config['default_language'],$q_config['url_info']['url']));

    // qtranslate_hooks.php has to go before load_plugin_textdomain()
    require_once QTRANSLATE_DIR . '/src/hooks.php';  // Common hooks need language already detected.
    qtranxf_add_main_filters();

    require_once QTRANSLATE_DIR . '/src/widget.php';
    add_action( 'widgets_init', 'qtranxf_widget_init' );

    // load plugin translations
    // since 3.2-b3 moved it here as https://codex.wordpress.org/Function_Reference/load_plugin_textdomain seem to recommend to run load_plugin_textdomain in 'plugins_loaded' action, which is this function responds to
    qtranxf_load_plugin_textdomain();

    /**
     * allow other plugins to initialize whatever they need before the fork between front and admin.
     */
    do_action( 'qtranslate_load_front_admin', $url_info );

    if ( $q_config['url_info']['doing_front_end'] ) {
        require_once QTRANSLATE_DIR . '/src/frontend.php';
    } else {
        require_once QTRANSLATE_DIR . '/src/admin/admin.php';
    }
    apply_filters( 'wp_translator', null );//create QTX_Translator object

    QTX_Module_Loader::load_active_modules();

    qtranxf_load_option_qtrans_compatibility();

    /**
     * allow other plugins and modules to initialize whatever they need for language
     */
    do_action( 'qtranslate_init_language', $url_info );
}

function qtranxf_load_option_qtrans_compatibility() {
    global $q_config;
    qtranxf_load_option_bool( 'qtrans_compatibility', false );
    $q_config['qtrans_compatibility'] = apply_filters( 'qtranslate_compatibility', $q_config['qtrans_compatibility'] );
    if ( ! isset( $q_config['qtrans_compatibility'] ) || ! $q_config['qtrans_compatibility'] ) {
        return;
    }
    require_once QTRANSLATE_DIR . '/src/compatibility.php';
}

function qtranxf_load_plugin_textdomain() {
    if ( load_plugin_textdomain( 'qtranslate', false, basename( QTRANSLATE_DIR ) . '/lang' ) ) {
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
    _deprecated_function( __FUNCTION__, '3.13.0' );
    if ( $var === '0' ) {
        return false;
    } elseif ( $var === '1' ) {
        return true;
    } else {
        return $default_value;
    }
}

function qtranxf_is_permalink_structure_query() {
    $permalink_structure = get_option( 'permalink_structure' );

    return empty( $permalink_structure ) || strpos( $permalink_structure, '?' ) !== false || strpos( $permalink_structure, 'index.php' ) !== false;
}

function qtranxf_loadConfig() {
    _deprecated_function( __FUNCTION__, '3.10.0', 'qtranxf_load_config' );
    qtranxf_load_config();
}

function qtranxf_load_config() {
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
        $vals = preg_split( '/[\s,]+/', strtolower( $ignore_file_types ), -1, PREG_SPLIT_NO_EMPTY );
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
        require_once QTRANSLATE_DIR . '/src/admin/activation_hook.php';
        require_once QTRANSLATE_DIR . '/src/admin/admin_options_update.php';
        qtranxf_update_i18n_config();
    }

    /**
     * Opportunity to load additional front-end features.
     */
    do_action( 'qtranslate_load_config' );
    do_action_deprecated( 'qtranslate_loadConfig', array(), '3.10.0', 'qtranslate_load_config' );
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
