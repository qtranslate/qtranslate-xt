<?php // encoding: utf-8
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once QTRANSLATE_DIR . '/src/default_language_config.php';

/**
 * Option types for front/admin settings.
 */
const QTX_STRING      = 1;
const QTX_BOOLEAN     = 2;
const QTX_INTEGER     = 3;
const QTX_URL         = 4;
const QTX_LANGUAGE    = 5;
const QTX_ARRAY       = 6;
const QTX_BOOLEAN_SET = 7;
const QTX_TEXT        = 8;  // multi-line string

/**
 * URL modes defining how the languages are set for HTTP.
 */
const QTX_URL_QUERY   = 1;  // Query string: domain.com?lang=en
const QTX_URL_PATH    = 2;  // Pre-path: domain.com/en (default)
const QTX_URL_DOMAIN  = 3;  // Pre-domain: en.domain.com
const QTX_URL_DOMAINS = 4;  // Domain per language

/**
 * Date/time conversion, see "use_strftime" option.
 */
const QTX_DATE_WP           = 0;  // Use WordPress options and translation, disable all date / time hooks.
const QTX_STRFTIME_OVERRIDE = 1;  // TODO: deprecate strftime format
const QTX_DATE_OVERRIDE     = 2;  // deprecated
const QTX_DATE              = 3;  // default format at first activation - not consistent with default date/time values
const QTX_STRFTIME          = 4;  // deprecated

/**
 * Translation of WordPress / qTranslate options.
 */
const QTX_FILTER_OPTIONS_ALL     = 0;
const QTX_FILTER_OPTIONS_LIST    = 1;
const QTX_FILTER_OPTIONS_DEFAULT = 'blogname blogdescription widget_%';

/**
 * Editor mode to handle the ML translations.
 */
const QTX_EDITOR_MODE_LSB    = 0;  // Language Switching Buttons
const QTX_EDITOR_MODE_RAW    = 1;  // ML not translated
const QTX_EDITOR_MODE_SINGLE = 2;  // ML translated for current admin language

/**
 * CSS style for the Language Switching Buttons (LSB).
 */
const QTX_LSB_STYLE_SIMPLE_BUTTONS = 'simple-buttons.css';
const QTX_LSB_STYLE_SIMPLE_TABS    = 'simple-tabs.css';
const QTX_LSB_STYLE_TABS_IN_BLOCK  = 'tabs-in-block.css';
const QTX_LSB_STYLE_CUSTOM         = 'custom';

/**
 * How the translatable fields are highlighted (CSS styles).
 */
const QTX_HIGHLIGHT_MODE_NONE        = 0;
const QTX_HIGHLIGHT_MODE_BORDER_LEFT = 1;
const QTX_HIGHLIGHT_MODE_BORDER      = 2;
const QTX_HIGHLIGHT_MODE_LEFT_SHADOW = 3;
const QTX_HIGHLIGHT_MODE_OUTLINE     = 4;
const QTX_HIGHLIGHT_MODE_CUSTOM_CSS  = 9;

/**
 * Cookies settings.
 */
const QTX_COOKIE_NAME_FRONT = 'qtrans_front_language';
const QTX_COOKIE_NAME_ADMIN = 'qtrans_admin_language';
const QTX_COOKIE_SAMESITE   = 'Lax';

/**
 * File extensions excluded for the translations of URL links, assumed to be language-independent.
 */
const QTX_IGNORE_FILE_TYPES = 'gif,jpg,jpeg,png,svg,pdf,swf,tif,rar,zip,7z,mpg,divx,mpeg,avi,css,js,mp3,mp4,apk';

/**
 * Language code format: ISO 639-1 (2 alpha), 639-2 or 639-3 (3 alpha).
 */
const QTX_LANG_CODE_FORMAT = '[a-z]{2,3}';

/**
 * Module option names.
 */
const QTX_OPTIONS_MODULES_STATE = 'qtranslate_modules_state';
const QTX_OPTIONS_MODULE_ACF    = 'qtranslate_module_acf';
const QTX_OPTIONS_MODULE_SLUGS  = 'qtranslate_module_slugs';

/**
 * @global array $q_config Global configuration, interpreted from settings and i18n configuration loaded from JSON.
 */
global $q_config;

/**
 * @global array $qtranslate_options Global options, mapped at a lower level to the settings.
 */
global $qtranslate_options;

/**
 * array of default option values
 * other plugins and themes should not use global variables directly, they are subject to change at any time.
 * @since 3.3
 */
function qtranxf_set_default_options( &$ops ) {
    $ops = array();

    // options processed in a standardized way
    $ops['front'] = array();

    $ops['front']['int'] = array(
        'url_mode'            => QTX_URL_PATH,  // sets default url mode
        'use_strftime'        => QTX_DATE,  // strftime usage (backward compatibility)
        'filter_options_mode' => QTX_FILTER_OPTIONS_ALL,
        'language_name_case'  => 0  //Camel Case
    );

    $ops['front']['bool'] = array(
        'detect_browser_language'        => true,   // enables browser language detection
        'hide_untranslated'              => false,  // hide pages without content
        'show_menu_alternative_language' => false,  // hide menu items without a translation
        'show_displayed_language_prefix' => true,
        'show_alternative_content'       => false,
        'hide_default_language'          => true,   // hide language tag for default language in urls
        'use_secure_cookie'              => false,
        'header_css_on'                  => true,
    );

    // single line options
    $ops['front']['str'] = array();

    // multi-line options
    $ops['front']['text'] = array(
        'header_css' => 'qtranxf_front_header_css_default',
    );

    $ops['front']['array'] = array(
        //'term_name'// uniquely special treatment
        'text_field_filters' => array(),
        'front_config'       => array(),
    );

    // options processed in a special way

    // store other default values of specially handled options
    $ops['default_value'] = array(
        'default_language'       => null,   // string
        'enabled_languages'      => null,   // array
        'qtrans_compatibility'   => false,  // enables compatibility with former qtrans_* functions
        'disable_client_cookies' => false,  // bool
        'flag_location'          => null,   // string
        'filter_options'         => QTX_FILTER_OPTIONS_DEFAULT, // array
        'ignore_file_types'      => QTX_IGNORE_FILE_TYPES,  // array
        'domains'                => null,   // array
    );

    // must have function 'qtranxf_default_option_name()' which returns a default value for option 'option_name'.
    $ops['languages'] = array(
        'language_name' => 'qtranslate_language_names',
        'locale'        => 'qtranslate_locales',
        'locale_html'   => 'qtranslate_locales_html',
        'not_available' => 'qtranslate_na_messages',
        'date_format'   => 'qtranslate_date_formats',
        'time_format'   => 'qtranslate_time_formats',
        'flag'          => 'qtranslate_flags',
    );

    /**
     * A chance to add additional options
     */
    $ops = apply_filters( 'qtranslate_option_config', $ops );
}


function qtranxf_language_predefined( $lang ) {
    $language_names = qtranxf_default_language_name();

    return isset( $language_names[ $lang ] );
}

function qtranxf_language_configured( $prop, $opn = null ) {
    global $qtranslate_options;
    $val = call_user_func( 'qtranxf_default_' . $prop );
    if ( ! $opn ) {
        if ( isset( $qtranslate_options['languages'][ $prop ] ) ) {
            $opn = $qtranslate_options['languages'][ $prop ];
        } else {
            $opn = 'qtranslate_' . $prop;
        }
    }
    $opt = get_option( $opn, array() );
    if ( $opt ) {
        $val = array_merge( $val, $opt );
    }

    return $val;
}

/**
 * Fill merged array of stored and pre-defined language properties
 * @since 3.3
 */
function qtranxf_languages_configured( &$cfg ) {
    global $qtranslate_options;
    foreach ( $qtranslate_options['languages'] as $name => $option ) {
        $cfg[ $name ] = qtranxf_language_configured( $name, $option );
    }

    return $cfg;
}

/**
 * Load enabled languages properties from  database
 * @since 3.3
 */
function qtranxf_load_languages_enabled() {
    global $q_config, $qtranslate_options;
    foreach ( $qtranslate_options['languages'] as $name => $option ) {
        $func = 'qtranxf_default_' . $name;
        qtranxf_load_option_func( $name, $option, $func );
        $val = array();
        $def = null;
        foreach ( $q_config['enabled_languages'] as $lang ) {
            if ( isset( $q_config[ $name ][ $lang ] ) ) {
                $val[ $lang ] = $q_config[ $name ][ $lang ];
            } else {
                if ( is_null( $def ) && function_exists( $func ) ) {
                    $def = call_user_func( $func );
                }
                $val[ $lang ] = $def[ $lang ] ?? '';
            }
        }
        $q_config[ $name ] = $val;
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
                $vals = preg_split( '/[\s,]+/', $default_value, -1, PREG_SPLIT_NO_EMPTY );
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
        if ( isset( $val ) ) {
            continue;
        }
        unset( $vals[ $key ] );
        if ( isset( $vals ) ) {
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

function qtranxf_is_permalink_structure_query() {
    $permalink_structure = get_option( 'permalink_structure' );

    return empty( $permalink_structure ) || strpos( $permalink_structure, '?' ) !== false || strpos( $permalink_structure, 'index.php' ) !== false;
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
