<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once QTRANSLATE_DIR . '/src/class_translator.php';
require_once QTRANSLATE_DIR . '/src/language_blocks.php';
require_once QTRANSLATE_DIR . '/src/language_config.php';
require_once QTRANSLATE_DIR . '/src/language_detect.php';
require_once QTRANSLATE_DIR . '/src/options.php';
require_once QTRANSLATE_DIR . '/src/url.php';
require_once QTRANSLATE_DIR . '/src/utils.php';
require_once QTRANSLATE_DIR . '/src/taxonomy.php';
require_once QTRANSLATE_DIR . '/src/modules/module_loader.php';

/**
 * Main initialization of qTranslate-XT for language detection and plugin loading.
 *
 * Redirect to a canonical URL if it doesn't match the detected language.
 * @see https://github.com/qtranslate/qtranslate-xt/wiki/Browser-redirection
 * Load configuration, common hooks, detect and load front/admin configuration, load modules.
 *
 * @return void
 */
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
            $url_info['WP_DOING_AJAX_POST'] = $_POST;
        }
        if ( wp_doing_cron() ) {
            $url_info['WP_DOING_CRON_POST'] = $_POST;
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

    assert( isset( $q_config['url_info']['doing_front_end'] ) );
    if ( $q_config['url_info']['doing_front_end'] && qtranxf_can_redirect() ) {
        qtranxf_check_url_maybe_redirect( $url_info );
    } elseif ( isset( $url_info['doredirect'] ) ) {
        // This should not happen!
        // We are possibly in a bad state as the specified language is missing in the request.
        // But we can't redirect (e.g. AJAX request or CLI command), or the request is detected as doing_admin.
        // So we leave a potential bug but we avoid any HTTP interference that could break some functionalities.
        // TODO log these events for the admin (with url_info dump), they should not be left unnoticed.
        $url_info['doredirect'] .= ' - cancelled by can_redirect';
    }

    // TODO clarify fix url to prevent xss - how does this prevents xss?
    // $q_config['url_info']['url'] = qtranxf_convertURL(add_query_arg('lang',$q_config['default_language'],$q_config['url_info']['url']));

    require_once QTRANSLATE_DIR . '/src/rest_api.php';
    add_action( 'init', 'qtranxf_rest_api_register_rewrites', 11 );

    require_once QTRANSLATE_DIR . '/src/widget.php';
    add_action( 'widgets_init', 'qtranxf_widget_init' );

    require_once QTRANSLATE_DIR . '/src/hooks.php';  // Common hooks need language already detected.
    qtranxf_add_main_filters();

    require_once QTRANSLATE_DIR . '/src/date_time.php';
    qtranxf_add_date_time_filters();

    // TODO delay to `init` action?
    // See https://developer.wordpress.org/reference/functions/load_plugin_textdomain/
    // Loading the plugin translations should not be done during plugins_loaded action since that is too early and prevent
    // other language related plugins from correctly hooking up with load_textdomain() function and doing whatever they want to do.
    // Calling load_plugin_textdomain() should be delayed until init action.
    qtranxf_load_plugin_textdomain();

    /**
     * allow other plugins to initialize whatever they need before the fork between front and admin.
     */
    do_action( 'qtranslate_load_front_admin', $url_info );

    if ( $q_config['url_info']['doing_front_end'] ) {
        require_once QTRANSLATE_DIR . '/src/frontend.php';
        qtranxf_add_front_filters();
        QTX_Translator::get_translator();

    } else {
        require_once QTRANSLATE_DIR . '/src/admin/admin.php';
        qtranxf_admin_load();
        QTX_Translator_Admin::get_translator();
    }
    apply_filters_deprecated( 'wp_translator', array( null ), '3.14.0', '', 'This filter will be removed in next major release. Open a ticket on https://github.com/qtranslate/qtranslate-xt/issues if you need this!' );

    QTX_Module_Loader::load_active_modules();

    qtranxf_load_option_qtrans_compatibility();

    /**
     * allow other plugins and modules to initialize whatever they need for language
     */
    do_action( 'qtranslate_init_language', $url_info );
}

/**
 * @deprecated Legacy hook for `init` action, to be removed in next major release.
 * Might be wrongly used by 3rd-party plugins (for example, alo_easymail) to test qTranslate-XT presence.
 * Recommended usage: is_plugin_active( 'qtranslate-xt/qtranslate.php' )
 * @since 3.4
 */
function qtranxf_init() {
    _deprecated_function( __FUNCTION__, '3.14.0' );
}
