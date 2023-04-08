<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Load array of stored in options language properties
 * @since 3.3
 */
function qtranxf_load_languages( array &$cfg ): void {
    global $qtranslate_options;
    //$cfg = array();
    foreach ( $qtranslate_options['languages'] as $name => $opn ) {
        $cfg[ $name ] = get_option( $opn, array() );
    }
}

// function qtranxf_save_languages($cfg) is in activation_hook.php as it is in use there

/**
 * Remove language $lang properties from hash $langs.
 * @since 3.3
 */
function qtranxf_unsetLanguage( array &$langs, string $lang ): void {
    unset( $langs['language_name'][ $lang ] );
    unset( $langs['flag'][ $lang ] );
    unset( $langs['locale'][ $lang ] );
    unset( $langs['locale_html'][ $lang ] );
    unset( $langs['date_format'][ $lang ] );
    unset( $langs['time_format'][ $lang ] );
    unset( $langs['not_available'][ $lang ] );
    //unset($langs['languages'][$lang]);
}

/**
 * @since 3.4.2
 */
function qtranxf_setLanguageAdmin( string $lang ): void {
    global $q_config;

    $q_config['language'] = $lang;
    qtranxf_set_language_cookie( $lang );
}

/**
 * Remove language $lang properties from hash $langs.
 * @since 3.3
 */
function qtranxf_copyLanguage( array &$langs, array $cfg, string $lang ): void {
    $langs['language_name'][ $lang ] = $cfg['language_name'][ $lang ];
    $langs['flag'][ $lang ]          = $cfg['flag'][ $lang ];
    $langs['locale'][ $lang ]        = $cfg['locale'][ $lang ];
    if ( empty( $cfg['locale_html'][ $lang ] ) ) {
        unset( $langs['locale_html'][ $lang ] );
    } else {
        $langs['locale_html'][ $lang ] = $cfg['locale_html'][ $lang ];
    }
    $langs['date_format'][ $lang ]   = $cfg['date_format'][ $lang ];
    $langs['time_format'][ $lang ]   = $cfg['time_format'][ $lang ];
    $langs['not_available'][ $lang ] = $cfg['not_available'][ $lang ];
}

function qtranxf_update_config_header_css(): void {
    global $q_config;

    $header_css = get_option( 'qtranslate_header_css' );
    if ( $header_css === false ) {
        $q_config['header_css'] = qtranxf_front_header_css_default();
    }
    if ( ! $q_config['header_css_on'] || ! empty( $header_css ) ) {
        qtranxf_add_warning( sprintf( __( 'A manual update to option "%s" or to the theme custom CSS may be needed, after some languages are changed.', 'qtranslate' ), __( 'Head inline CSS', 'qtranslate' ) ) . ' ' . __( 'If you do not wish to customize this option, then reset it to the default by emptying its value.', 'qtranslate' ) );
    }
}

function qtranxf_disableLanguage( string $lang ): bool {
    global $q_config;

    if ( ! qtranxf_isEnabled( $lang ) ) {
        return false;
    }
    foreach ( $q_config['enabled_languages'] as $k => $l ) {
        if ( $l != $lang ) {
            continue;
        }
        unset( $q_config['enabled_languages'][ $k ] );
        break;
    }
    qtranxf_unsetLanguage( $q_config, $lang );
    if ( $q_config['language'] == $lang ) {
        qtranxf_setLanguageAdmin( $q_config['default_language'] );
    }
    qtranxf_update_config_header_css();

    return true;
}

function qtranxf_enableLanguage( string $lang ): bool {
    global $q_config;

    if ( qtranxf_isEnabled( $lang ) ) {
        return false;
    }

    $q_config['enabled_languages'][] = $lang;
    qtranxf_load_languages_enabled();
    qtranxf_update_config_header_css();

    // force update of .mo files
    if ( $q_config['auto_update_mo'] ) {
        qtranxf_update_gettext_databases( true, $lang );
    }

    return true;
}

/**
 * Remove language $lang from the database.
 * @since 3.3
 */
function qtranxf_deleteLanguage( string $lang ): string {
    global $q_config;

    if ( ! qtranxf_language_predefined( $lang ) ) {
        if ( $q_config['default_language'] == $lang ) {
            return __( 'Cannot delete Default Language!', 'qtranslate' );
        }
        qtranxf_disableLanguage( $lang );
    }
    $langs = array();
    qtranxf_load_languages( $langs );
    qtranxf_unsetLanguage( $langs, $lang );
    qtranxf_save_languages( $langs );
    if ( $q_config['language'] == $lang ) {
        qtranxf_setLanguageAdmin( $q_config['default_language'] );
    }

    return '';
}
