<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * locale for current language and set it on PHP.
 */
function qtranxf_localeForCurrentLanguage( string $locale ): string {
    static $locale_lang;
    if ( ! empty( $locale_lang ) ) {
        return $locale_lang;
    }
    global $q_config;
    $lang        = $q_config['language'];
    $locale_lang = $q_config['locale'][ $lang ];

    // submit a few possible locales
    $lc             = array();
    $lc[]           = $locale_lang . '.utf8';
    $lc[]           = $locale_lang . '@euro';
    $lc[]           = $locale_lang;
    $windows_locale = qtranxf_default_windows_locale();
    if ( isset( $windows_locale[ $lang ] ) ) {
        $lc[] = $windows_locale[ $lang ];
    }
    $lc[] = $lang;

    // return the correct locale and most importantly set it (wordpress doesn't, which is bad)
    // only set LC_TIME as everything else doesn't seem to work with windows
    $loc = setlocale( LC_TIME, $lc );
    if ( ! $loc ) {
        $lc2 = array();
        if ( strlen( $locale_lang ) == 2 ) {
            $lc2[] = $locale_lang . '_' . strtoupper( $locale_lang );
            $loc   = $locale_lang . '_' . strtoupper( $lang );
            if ( ! in_array( $loc, $lc2 ) ) {
                $lc2[] = $loc;
            }
        }
        $loc = $lang . '_' . strtoupper( $lang );
        if ( ! in_array( $loc, $lc2 ) ) {
            $lc2[] = $loc;
        }
        setlocale( LC_TIME, $lc2 );
    }

    return $locale_lang;
}

function qtranxf_useCurrentLanguageIfNotFoundShowEmpty( $content ) {
    global $q_config;

    return qtranxf_use( $q_config['language'], $content, false, true );
}

function qtranxf_useCurrentLanguageIfNotFoundShowAvailable( $content ) {
    global $q_config;

    return qtranxf_use( $q_config['language'], $content, true, false );
}

function qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $content ) {
    global $q_config;

    return qtranxf_use( $q_config['language'], $content, false, false );
}

function qtranxf_useDefaultLanguage( $content ) {
    global $q_config;

    return qtranxf_use( $q_config['default_language'], $content, false, false );
}

function qtranxf_versionLocale(): string {
    return 'en_US';
}

function qtranxf_useRawTitle( string $title, string $raw_title = '', string $context = 'save' ): string {
    switch ( $context ) {
        case 'save':
            {
                if ( empty( $raw_title ) ) {
                    $raw_title = $title;
                }
                $raw_title = qtranxf_useDefaultLanguage( $raw_title );
                $title     = remove_accents( $raw_title );
            }
            break;
        default:
            break;
    }

    return $title;
}

function qtranxf_gettext( $translated_text ) {
    //same as qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage
    global $q_config;

    return qtranxf_use( $q_config['language'], $translated_text, false );
}

function qtranxf_gettext_with_context( $translated_text ) {
    //same as qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage
    global $q_config;

    return qtranxf_use( $q_config['language'], $translated_text, false );
}

function qtranxf_ngettext( $translated_text ) {
    //same as qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage
    global $q_config;

    return qtranxf_use( $q_config['language'], $translated_text, false );
}

/**
 * Add main filters, shared by front and admin.
 *
 * @return void
 */
function qtranxf_add_main_filters(): void {
    add_filter( 'wp_trim_words', 'qtranxf_trim_words', 0, 4 );
    add_filter( 'sanitize_title', 'qtranxf_useRawTitle', 0, 3 );
    add_filter( 'comment_moderation_subject', 'qtranxf_useDefaultLanguage', 0 );
    add_filter( 'comment_moderation_text', 'qtranxf_useDefaultLanguage', 0 );
    // since 3.1 changed priority from 0 to 100, since other plugins,
    // like https://wordpress.org/plugins/siteorigin-panels generate additional content, which also needs to be translated.
    add_filter( 'the_content', 'qtranxf_useCurrentLanguageIfNotFoundShowAvailable', 100 );
    add_filter( 'the_excerpt', 'qtranxf_useCurrentLanguageIfNotFoundShowAvailable', 0 );
    add_filter( 'the_excerpt_rss', 'qtranxf_useCurrentLanguageIfNotFoundShowAvailable', 0 );
    add_filter( 'locale', 'qtranxf_localeForCurrentLanguage', 99 );
    add_filter( 'core_version_check_locale', 'qtranxf_versionLocale' );
    add_filter( 'post_title', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 0 );
    add_filter( 'tag_rows', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 0 );
    add_filter( 'wp_list_categories', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 0 );
    add_filter( 'wp_title', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 0 );
    add_filter( 'single_post_title', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 0 );
    add_filter( 'bloginfo', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 0 );
    add_filter( 'get_others_drafts', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 0 );
    add_filter( 'get_bloginfo_rss', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 0 );
    add_filter( 'get_wp_title_rss', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 0 );
    add_filter( 'wp_title_rss', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 0 );
    add_filter( 'the_title_rss', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 0 );
    add_filter( 'the_content_rss', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 0 );
    add_filter( 'get_pages', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 0 );
    add_filter( 'bloginfo_rss', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 0 );
    add_filter( 'the_category_rss', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 0 );
    add_filter( 'term_links-post_tag', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 0 );
    add_filter( 'link_name', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 0 );
    add_filter( 'link_description', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 0 );
    add_filter( 'the_author', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 0 );
    add_filter( 'comment_notification_text', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage' );
    add_filter( 'comment_notification_headers', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage' );
    add_filter( 'comment_notification_subject', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage' );
    add_filter( 'oembed_response_data', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage' );
    add_filter( 'pre_option_rss_language', 'qtranxf_getLanguage', 0 );
    add_filter( '_wp_post_revision_field_post_title', 'qtranxf_showAllSeparated', 0 );
    add_filter( '_wp_post_revision_field_post_content', 'qtranxf_showAllSeparated', 0 );
    add_filter( '_wp_post_revision_field_post_excerpt', 'qtranxf_showAllSeparated', 0 );
}
