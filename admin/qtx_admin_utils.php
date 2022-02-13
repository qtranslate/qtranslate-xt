<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 3.3.8.4
 */
function qtranxf_add_admin_notice( $msg, $kind ) {
    global $q_config;
    if ( isset( $q_config['url_info'][ $kind ] ) ) {
        if ( ! in_array( $msg, $q_config['url_info'][ $kind ] ) ) {
            $q_config['url_info'][ $kind ][] = $msg;
        }
    } else {
        if ( ! isset( $q_config['url_info'] ) ) {
            $q_config['url_info'] = array();
        }
        $q_config['url_info'][ $kind ] = array( $msg );
    }
}

/**
 * @since 3.3.7
 */
function qtranxf_add_error( $msg ) {
    qtranxf_add_admin_notice( $msg, 'errors' );
}

function qtranxf_add_warning( $msg ) {
    qtranxf_add_admin_notice( $msg, 'warnings' );
}

function qtranxf_add_message( $msg ) {
    qtranxf_add_admin_notice( $msg, 'messages' );
}

/**
 * @since 3.3.1
 */
function qtranxf_error_log( $msg ) {
    qtranxf_add_error( $msg );
    error_log( 'qTranslate-X: ' . strip_tags( $msg ) );
}

/**
 * Enqueue Javascript files listed in $jss.
 * @since 3.5.1
 */
function qtranxf_enqueue_scripts( $jss ) {
    $cnt  = 0;
    $deps = array();
    foreach ( $jss as $key => $js ) {
        if ( isset( $js['src'] ) ) {
            $handle = isset( $js['handle'] ) ? $js['handle'] : ( is_string( $key ) ? $key : 'qtranslate-admin-js-' . ( ++$cnt ) );
            $src    = $js['src'];
            $ver    = isset( $js['ver'] ) ? $js['ver'] : QTX_VERSION;
            $url    = content_url( $src );
            if ( isset( $js['deps'] ) ) {
                $deps = array_merge( $deps, $js['deps'] );
            }
            wp_register_script( $handle, $url, $deps, $ver, true );
            wp_enqueue_script( $handle );
            $deps[] = $handle;
        }
    }
}

function qtranxf_detect_admin_language( $url_info ) {
    global $q_config;
    $cs   = null;
    $lang = null;

    /** @since 3.2.9.9.6
     * Detect language from $_POST['WPLANG'].
     */
    if ( isset( $_POST['WPLANG'] ) ) {
        // User is switching the language in WordPress using "Site Language" field on page /wp-admin/options-general.php
        // The value of WPLANG corresponds to a WP locale such as fr_FR or empty for default (en_US)
        $wplang = sanitize_text_field( $_POST['WPLANG'] );
        if ( empty( $wplang ) ) {
            // TODO check for default locale other than en_US in WordPress
            $wplang = 'en';
        }
        foreach ( $q_config['enabled_languages'] as $language ) {
            if ( $q_config['locale'][ $language ] != $wplang ) {
                continue;
            }
            $lang = $language;
            break;
        }
        if ( ! $lang ) {
            // TODO extend language code check and resolve, in case the WP locale is not enabled in qTranslate
            $lang = substr( $wplang, 0, 2 );
            $lang = qtranxf_resolveLangCase( $lang, $cs );
        }
    }

    if ( ! $lang && isset( $_COOKIE[ QTX_COOKIE_NAME_ADMIN ] ) ) {
        $lang                          = qtranxf_resolveLangCase( $_COOKIE[ QTX_COOKIE_NAME_ADMIN ], $cs );
        $url_info['lang_cookie_admin'] = $lang;
    }

    if ( ! $lang ) {
        $lang = $q_config['default_language'];
    }
    $url_info['doing_front_end'] = false;
    $url_info['lang_admin']      = $lang;

    return $url_info;
}

add_filter( 'qtranslate_detect_admin_language', 'qtranxf_detect_admin_language' );

/**
 * @return bool true if $a and $b are equal.
 */
function qtranxf_array_compare( $a, $b ) {
    if ( ! is_array( $a ) || ! is_array( $b ) ) {
        return false;
    }
    if ( count( $a ) != count( $b ) ) {
        return false;
    }
    foreach ( $a as $key => $value ) {
        if ( ! isset( $b[ $key ] ) ) {
            return false;
        }
        if ( is_array( $value ) ) {
            if ( ! qtranxf_array_compare( $value, $b[ $key ] ) ) {
                return false;
            }
        } else {
            if ( $b[ $key ] !== $value ) {
                return false;
            }
        }
    }

    return true;
}

function qtranxf_join_texts( $texts, $sep ) {
    switch ( $sep ) {
        case 'byline':
            return qtranxf_join_byline( $texts );
        case '{':
            return qtranxf_join_s( $texts );
        default:
            return qtranxf_join_b( $texts );
    }
}

/**
 * @since 3.4.6.9
 */
function qtranxf_clean_request( $name ) {
    unset( $_GET[ $name ] );
    unset( $_POST[ $name ] );
    unset( $_REQUEST[ $name ] );
}

function qtranxf_ensure_language_set( &$langs, $lang, $default_value = null ) {
    if ( ! empty( $langs[ $lang ] ) ) {
        return $langs[ $lang ];
    }
    if ( ! empty( $default_value ) ) {
        return ( $langs[ $lang ] = $default_value );
    }
    global $q_config;
    foreach ( $q_config['enabled_languages'] as $lng ) {
        if ( empty( $langs[ $lng ] ) ) {
            continue;
        }

        return ( $langs[ $lang ] = $langs[ $lng ] );
    }

    return '';
}

function qtranxf_getLanguageEdit() {
    _deprecated_function( __FUNCTION__, '3.10.0', 'qtranxf_get_edit_language' );
    qtranxf_get_edit_language();
}

function qtranxf_get_edit_language() {
    global $q_config;

    if ( ! isset( $_REQUEST['qtranslate-edit-language'] ) ) {
        throw new InvalidArgumentException( 'Missing "qtranslate-edit-language" field in $_REQUEST!' );
    }

    $lang = $_REQUEST['qtranslate-edit-language'];
    if ( ! in_array( $lang, $q_config['enabled_languages'] ) ) {
        throw new UnexpectedValueException( 'The requested language "' . $lang . '" defined in "qtranslate-edit-language" is not enabled!' );
    }

    return $lang;
}

function qtranxf_language_column_header( $columns ) {
    $new_columns = array();
    if ( isset( $columns['cb'] ) ) {
        $new_columns['cb'] = '';
    }
    if ( isset( $columns['title'] ) ) {
        $new_columns['title'] = '';
    }
    if ( isset( $columns['author'] ) ) {
        $new_columns['author'] = '';
    }
    if ( isset( $columns['categories'] ) ) {
        $new_columns['categories'] = '';
    }
    if ( isset( $columns['tags'] ) ) {
        $new_columns['tags'] = '';
    }
    $new_columns['language'] = __( 'Languages', 'qtranslate' );

    return array_merge( $new_columns, $columns );
}

function qtranxf_language_column( $column ) {
    global $q_config, $post;
    if ( $column == 'language' ) {
        $missing_languages   = null;
        $available_languages = qtranxf_getAvailableLanguages( $post->post_content );
        if ( $available_languages === false ) {
            echo _x( 'Languages are not set', 'Appears in the column "Languages" on post listing pages, when content has no language tags yet.', 'qtranslate' );
        } else {
            $missing_languages        = array_diff( $q_config['enabled_languages'], $available_languages );
            $available_languages_name = array();
            $language_names           = null;
            foreach ( $available_languages as $language ) {
                if ( isset( $q_config['language_name'][ $language ] ) ) {
                    $language_name = $q_config['language_name'][ $language ];
                } else {
                    if ( ! $language_names ) {
                        $language_names = qtranxf_default_language_name();
                    }
                    $language_name = isset( $language_names[ $language ] ) ? $language_names[ $language ] : __( 'Unknown Language', 'qtranslate' );
                    $language_name .= ' (' . __( 'Not enabled', 'qtranslate' ) . ')';
                }
                $available_languages_name[] = $language_name;
            }
            $available_languages_names = join( ', ', $available_languages_name );
            echo apply_filters( 'qtranslate_available_languages_names', $available_languages_names );
        }

        do_action( 'qtranslate_language_column', $available_languages, $missing_languages );
        do_action_deprecated( 'qtranslate_languageColumn', array(
            $available_languages,
            $missing_languages
        ), '3.10.0', 'qtranslate_language_column' );
    }

    return $column;
}

function qtranxf_fetch_file_selection( $dir, $suffix = '.css' ) {
    $files      = array();
    $dir_handle = @opendir( $dir );
    if ( ! $dir_handle ) {
        return false;
    }
    while ( false !== ( $file = readdir( $dir_handle ) ) ) {
        if ( ! qtranxf_endsWith( $file, $suffix ) ) {
            continue;
        }
        $name = basename( $file, $suffix );
        if ( ! $name ) {
            continue;
        }
        $name = str_replace( '_', ' ', $name );
        if ( qtranxf_endsWith( $name, '.min' ) ) {
            $name           = substr( $name, -4 );
            $files[ $name ] = $file;
        } elseif ( ! isset( $files[ $name ] ) ) {
            $files[ $name ] = $file;
        }
    }
    ksort( $files );

    return $files;
}

function qtranxf_before_admin_bar_render() {
    global $wp_admin_bar, $q_config;
    if ( ! isset( $wp_admin_bar ) ) {
        return;
    }

    $nodes = $wp_admin_bar->get_nodes();
    if ( ! isset( $nodes ) ) {
        return;
    }

    $lang = $q_config['language'];
    foreach ( $nodes as $node ) {
        $nd = qtranxf_use( $lang, $node );
        $wp_admin_bar->add_node( $nd );
    }
}

function qtranxf_admin_the_title( $title ) {
    // For nav menus, keep the raw value as the languages are handled client-side (LSB)
    if ( wp_doing_ajax() && isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'add-menu-item' ) {
        // When a nav menu is being added it is first handled by AJAX, see "wp_ajax_add_menu_item" in ajax-actions.php
        // For the call to the filter "the_title", see "wp_setup_nav_menu_item" in nav-menus.php
        return $title;
    }
    global $pagenow;
    switch ( $pagenow ) {
        case 'nav-menus.php':
            // When the nav menu is updated, keep the raw value.
            return $title;
        default:
            // For general display purposes only
            return qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $title );
    }
}

add_filter( 'the_title', 'qtranxf_admin_the_title', 0 );

if ( ! function_exists( 'qtranxf_trim_words' ) ) {
    function qtranxf_trim_words( $text, $num_words, $more, $original_text ) {
        $blocks = qtranxf_get_language_blocks( $original_text );
        if ( count( $blocks ) <= 1 ) {
            return $text;
        }
        $texts = qtranxf_split_blocks( $blocks );
        foreach ( $texts as $key => $txt ) {
            $texts[ $key ] = wp_trim_words( $txt, $num_words, $more );
        }

        // has to be 'b', because 'c' gets stripped in /wp-admin/includes/nav-menu.php:182: esc_html( $item->description )
        return qtranxf_join_b( $texts );
    }
}

/* @since 3.3.8.7 use filter 'admin_title' instead
 * function qtranxf_filter_options_general($value){
 * global $q_config;
 * global $pagenow;
 * switch($pagenow){
 * case 'options-general.php':
 * case 'customize.php'://there is more work to do for this case
 * return $value;
 * default: break;
 * }
 * $lang = $q_config['language'];
 * return qtranxf_use_language($lang,$value,false,false);
 * }
 * add_filter('option_blogname', 'qtranxf_filter_options_general');
 * add_filter('option_blogdescription', 'qtranxf_filter_options_general');
 */

function qtranxf_updateGettextDatabases( $force = false, $only_for_language = '' ) {
    _deprecated_function( __FUNCTION__, '3.10.0', 'qtranxf_update_gettext_databases' );
    qtranxf_update_gettext_databases( $force, $only_for_language );
}

function qtranxf_update_gettext_databases( $force = false, $only_for_language = '' ) {
    require_once( QTRANSLATE_DIR . '/admin/qtx_update_gettext_db.php' );

    return qtranxf_update_gettext_databases_ex( $force, $only_for_language );
}

function qtranxf_add_conf_filters() {
    global $q_config;
    // TODO: check impact of Gutenberg, note this hook is fired too early to check the editor in current screen
    switch ( $q_config['editor_mode'] ) {
        case QTX_EDITOR_MODE_SINGLE:
        case QTX_EDITOR_MODE_RAW:
            add_filter( 'gettext', 'qtranxf_gettext', 0 );
            add_filter( 'gettext_with_context', 'qtranxf_gettext_with_context', 0 );
            add_filter( 'ngettext', 'qtranxf_ngettext', 0 );
            break;
        case QTX_EDITOR_MODE_LSB:
        default:
            // Nothing to do
            break;
    }
}

function qtranxf_del_conf_filters() {
    remove_filter( 'gettext', 'qtranxf_gettext', 0 );
    remove_filter( 'gettext_with_context', 'qtranxf_gettext_with_context', 0 );
    remove_filter( 'ngettext', 'qtranxf_ngettext', 0 );
}

/**
 * Get the currently selected admin color scheme (to be used for generated CSS)
 * @return array
 */
function qtranxf_get_user_admin_color() {
    global $_wp_admin_css_colors;
    $user_id          = get_current_user_id();
    $user_admin_color = get_user_meta( $user_id, 'admin_color', true );
    if ( ! $user_admin_color ) { //ajax calls do not have user authenticated?
        $user_admin_color = 'fresh';
    }

    return $_wp_admin_css_colors[ $user_admin_color ]->colors;
}

function qtranxf_meta_box_LSB() {
    printf( __( 'This is a set of "%s" from %s. Click any blank space between the buttons and drag it to a place where you would need it the most. Click the handle at the top-right corner of this widget to hide this message.', 'qtranslate' ), __( 'Language Switching Buttons', 'qtranslate' ), '<a href="https://github.com/qTranslate/qtranslate-xt/" target="_blank">qTranslate&#8209;XT</a>' );
}

function qtranxf_add_meta_box_LSB( $post_type, $post = null ) {
    global $q_config, $pagenow;
    if ( $q_config['editor_mode'] != QTX_EDITOR_MODE_LSB ) {
        return;
    }
    switch ( $pagenow ) {
        case 'post-new.php':
        case 'post.php':
            break;
        default:
            return;
    }
    if ( empty( $post_type ) ) {
        if ( isset( $post->post_type ) ) {
            $post_type = $post->post_type;
        } else {
            return;
        }
    }
    $page_config = qtranxf_get_admin_page_config_post_type( $post_type );
    if ( empty( $page_config ) ) {
        return;
    }
    // translators: expected in WordPress default textdomain
    add_meta_box( 'qtranxs-meta-box-lsb', qtranxf_translate_wp( 'Language' ), 'qtranxf_meta_box_LSB', $post_type, 'normal', 'low' );
}

add_action( 'add_meta_boxes', 'qtranxf_add_meta_box_LSB', 10, 2 );

/**
 * @return true if post type is listed in option 'Post Types'.
 * @since 3.3
 */
function qtranxf_post_type_optional( $post_type ) {
    return ! in_array( $post_type, [ 'revision', 'nav_menu_item' ] );
}

function qtranxf_json_encode( $o ) {
    _deprecated_function( __FUNCTION__, '3.10.0' );
    if ( version_compare( PHP_VERSION, '5.4.0' ) >= 0 ) {
        return json_encode( $o, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
    }

    return json_encode( $o );
}

/**
 * @since 3.4
 * return reference to $page_config['forms'][$name]['fields']
 */
function qtranxf_config_add_form( &$page_config, $name ) {
    _deprecated_function( __FUNCTION__, '3.10.0' );
    if ( ! isset( $page_config['forms'][ $name ] ) ) {
        $page_config['forms'][ $name ] = array( 'fields' => array() );
    } else if ( ! isset( $page_config['forms'][ $name ]['fields'] ) ) {
        $page_config['forms'][ $name ]['fields'] = array();
    }
}

/**
 * @param string $nonce_name Name specified when generating the nonce
 * @param string $nonce_field Form input name for the nonce
 *
 * @return boolean             True if the nonce is ok
 * @link https://codex.wordpress.org/Function_Reference/wp_nonce_field#Examples
 *
 * @since 3.4.5
 * check the WP Nonce - OK if POST is empty
 */
function qtranxf_verify_nonce( $nonce_name, $nonce_field = '_wpnonce' ) {
    return empty( $_POST ) || check_admin_referer( $nonce_name, $nonce_field );
}

function qtranxf_admin_debug_info() {
    $info = array();
    if ( current_user_can( 'manage_options' ) ) {
        global $q_config, $wp_version;

        $info['configuration'] = $q_config;
        // clear config information, too verbose and generally irrelevant
        unset( $info['configuration']['front_config'] );
        unset( $info['configuration']['admin_config'] );
        unset( $info['configuration']['i18n-cache'] );

        $plugins         = get_option( 'active_plugins' );
        $plugin_versions = array();
        foreach ( $plugins as $plugin ) {
            $plugin_data       = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
            $plugin_versions[] = $plugin_data['Name'] . ' ' . $plugin_data['Version'];
        }

        $info['versions'] = array(
            'PHP_VERSION' => PHP_VERSION,
            'WP_VERSION'  => $wp_version,
            'QTX_VERSION' => QTX_VERSION,
            'Plugins'     => $plugin_versions
        );
    }
    echo json_encode( $info, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
    wp_die();
}

/**
 * TODO this looks unnecessary, it might be possible to use json_decode directly with right options
 * @since 3.4.6.5
 */
function qtranxf_decode_name_value_pair( &$a, $name, $value ) {
    if ( preg_match( '#([^\[]*)\[([^]]+)](.*)#', $name, $matches ) ) {
        $n = $matches[1];
        $k = $matches[2];
        $s = $matches[3];
        if ( is_numeric( $n ) ) {
            $n = (int) $n;
        }
        if ( is_numeric( $k ) ) {
            $k = (int) $k;
        }
        if ( empty( $a[ $n ] ) ) {
            $a[ $n ] = array();
        }
        if ( empty( $s ) ) {
            $a[ $n ][ $k ] = $value;
        } else {
            qtranxf_decode_name_value_pair( $a[ $n ], $k . $s, $value );
        }
    } else {
        $a[ $name ] = $value;
    }
}

/**
 * TODO this looks unnecessary, it might be possible to use json_decode directly with right options
 * @since 3.4.6.5
 */
function qtranxf_decode_name_value( $name_values ) {
    $decoded = array();
    foreach ( $name_values as $name_value ) {
        qtranxf_decode_name_value_pair( $decoded, $name_value->name, wp_slash( $name_value->value ) );
    }

    return $decoded;
}

add_filter( 'manage_posts_columns', 'qtranxf_language_column_header' );
add_filter( 'manage_posts_custom_column', 'qtranxf_language_column' );
add_filter( 'manage_pages_columns', 'qtranxf_language_column_header' );
add_filter( 'manage_pages_custom_column', 'qtranxf_language_column' );
