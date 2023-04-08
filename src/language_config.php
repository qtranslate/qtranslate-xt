<?php

function qtranxf_getLanguage(): string {
    global $q_config;

    return $q_config['language'];
}

function qtranxf_getLanguageDefault(): string {
    global $q_config;

    return $q_config['default_language'];
}

/**
 * @since 3.4.5.4 - return language name in native language, former qtranxf_getLanguageName.
 */
function qtranxf_getLanguageNameNative( string $lang = '' ): string {
    global $q_config;
    if ( empty( $lang ) ) {
        $lang = $q_config['language'];
    }

    return $q_config['language_name'][ $lang ];
}

/**
 * @since 3.4.5.4 - return language name in active language, if available, otherwise the name in native language.
 */
function qtranxf_getLanguageName( string $lang = '' ): string {
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
            if ( strlen( $locale ) > 2 && $locale[2] == '_' ) {
                $locale = substr( $locale, 0, 2 );
                load_textdomain( 'language-names', QTRANSLATE_DIR . '/lang/language-names/language-' . $locale . '.mo' );
            }
        }
    }
    $translations = get_translations_for_domain( 'language-names' );
    $locale       = $q_config['locale'][ $lang ];
    if ( ! isset( $translations->entries[ $locale ] ) ) {
        $found_locale = false;
        if ( strlen( $locale ) > 2 && $locale[2] == '_' ) {
            $locale = substr( $locale, 0, 2 );
            if ( isset( $translations->entries[ $locale ] ) ) {
                $found_locale = true;
            }
        }
        if ( ! $found_locale ) {
            return $q_config['language-names'][ $lang ] = $q_config['language_name'][ $lang ];
        }
    }
    $n = $translations->entries[ $locale ]->translations[0];
    if ( empty( $q_config['language_name_case'] ) ) {
        // Camel Case by default
        if ( function_exists( 'mb_convert_case' ) ) {
            // module 'mbstring' may not be installed by default: https://wordpress.org/support/topic/qtranslate_utilsphp-on-line-504
            $n = mb_convert_case( $n, MB_CASE_TITLE );
        } else {
            $msg = 'qTranslate-XT: Enable PHP module "mbstring" to get names of languages printed in "Camel Case" or disable option \'Show language names in "Camel Case"\' on admin page ' . admin_url( 'options-general.php?page=qtranslate-xt#general' ) . '. You may find more information at https://php.net/manual/en/mbstring.installation.php, or search for PHP installation options on control panel of your server provider.';
            error_log( $msg );
        }
    }

    return $q_config['language-names'][ $lang ] = $n;
}

function qtranxf_isEnabled( string $lang ): bool {
    global $q_config;

    // only available languages are loaded, works quicker
    return isset( $q_config['locale'][ $lang ] );
}

function qtranxf_getSortedLanguages( bool $reverse = false ): array {
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
