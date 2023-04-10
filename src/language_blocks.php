<?php

/**
 * Check if a string contains at least one language token
 *
 * @param string|null $str
 *
 * @return bool
 */
function qtranxf_isMultilingual( ?string $str ): bool {
    $lang_code = QTX_LANG_CODE_FORMAT;
    return ! is_null( $str ) && preg_match( "/<!--:$lang_code-->|\[:$lang_code]|{:$lang_code}/im", $str );
}

/**
 * Break a multilingual string into string blocks as tokens.
 * Not related to WordPress block editor (gutenberg).
 *
 * @param string $text multilingual string e.g. "[:en]English text[:fr]Texte francais[:]".
 *
 * @return string[] array of string tokens, including the ML tags.
 * @since 3.3.6 swirly bracket encoding added
 */
function qtranxf_get_language_blocks( $text ): array {
    $lang_code   = QTX_LANG_CODE_FORMAT;
    $split_regex = "#(<!--:$lang_code-->|<!--:-->|\[:$lang_code\]|\[:\]|\{:$lang_code\}|\{:\})#ism";

    return preg_split( $split_regex, $text, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
}

/**
 * Split a multilingual string into array of tokens by language.
 *
 * @param string $text multilingual string.
 *
 * @return string[] array of string items indexed by language.
 */
function qtranxf_split( $text ): array {
    $blocks = qtranxf_get_language_blocks( $text );

    return qtranxf_split_blocks( $blocks );
}

/**
 * Split blocks into string items indexed by language.
 *
 * @param string[] $blocks array of string tokens, including tags.
 * @param bool[] $found array of booleans indicating which languages are found.
 *
 * @return string[] array of string items indexed by language.
 * @since 3.4.5.2 $found added
 */
function qtranxf_split_blocks( array $blocks, array &$found = array() ): array {
    global $q_config;

    $result = array();
    foreach ( $q_config['enabled_languages'] as $language ) {
        $result[ $language ] = '';
    }

    $current_language = false;
    $lang_code        = QTX_LANG_CODE_FORMAT;

    foreach ( $blocks as $block ) {
        // detect c-tags
        if ( preg_match( "#^<!--:($lang_code)-->$#ism", $block, $matches ) ) {
            $current_language = $matches[1];
            continue;
            // detect b-tags
        } elseif ( preg_match( "#^\[:($lang_code)]$#ism", $block, $matches ) ) {
            $current_language = $matches[1];
            continue;
            // detect s-tags @since 3.3.6 swirly bracket encoding added
        } elseif ( preg_match( "#^{:($lang_code)}$#ism", $block, $matches ) ) {
            $current_language = $matches[1];
            continue;
        }
        switch ( $block ) {
            case '[:]':
            case '{:}':
            case '<!--:-->':
                $current_language = false;
                break;
            default:
                // correctly categorize text block
                if ( $current_language ) {
                    if ( ! isset( $result[ $current_language ] ) ) {
                        $result[ $current_language ] = '';
                    }
                    $result[ $current_language ] .= $block;
                    $found[ $current_language ]  = true;
                    $current_language            = false;
                } else {
                    foreach ( $q_config['enabled_languages'] as $language ) {
                        $result[ $language ] .= $block;
                    }
                }
                break;
        }
    }
    // it gets trimmed later in qtranxf_use() anyway, better to do it here
    foreach ( $result as $lang => $text ) {
        $result[ $lang ] = trim( $text );
    }

    return $result;
}

/**
 * gets only part with encoded languages
 */
function qtranxf_split_languages( array $blocks ): array {
    $result           = array();
    $current_language = false;
    $lang_code        = QTX_LANG_CODE_FORMAT;

    foreach ( $blocks as $block ) {
        // detect c-tags
        if ( preg_match( "#^<!--:($lang_code)-->$#ism", $block, $matches ) ) {
            $current_language = $matches[1];
            continue;
            // detect b-tags
        } elseif ( preg_match( "#^\[:($lang_code)]$#ism", $block, $matches ) ) {
            $current_language = $matches[1];
            continue;
            // detect s-tags @since 3.3.6 swirly bracket encoding added
        } elseif ( preg_match( "#^{:($lang_code)}$#ism", $block, $matches ) ) {
            $current_language = $matches[1];
            continue;
        }
        switch ( $block ) {
            case '[:]':
            case '{:}':
            case '<!--:-->':
                $current_language = false;
                break;
            default:
                // correctly categorize text block
                if ( $current_language ) {
                    if ( ! isset( $result[ $current_language ] ) ) {
                        $result[ $current_language ] = '';
                    }
                    $result[ $current_language ] .= $block;
                    $current_language            = false;
                }
                break;
        }
    }
    // it gets trimmed later in qtranxf_getAvailableLanguages() anyway, better to do it here
    foreach ( $result as $lang => $text ) {
        $result[ $lang ] = trim( $text );
    }

    return $result;
}


function qtranxf_getAvailableLanguages( $text ) {
    global $q_config;
    $blocks = qtranxf_get_language_blocks( $text );
    if ( count( $blocks ) <= 1 ) {
        return false; // no languages set
    }
    $result  = array();
    $content = qtranxf_split_languages( $blocks );
    foreach ( $content as $language => $lang_text ) {
        $lang_text = trim( $lang_text );
        if ( ! empty( $lang_text ) ) {
            $result[] = $language;
        }
    }
    if ( sizeof( $result ) == 0 ) {
        // add default language to keep default URL
        $result[] = $q_config['language'];
    }

    return $result;
}

function qtranxf_allthesame( array $texts ): ?string {
    $text = null;
    // take first not empty
    foreach ( $texts as $lang_text ) {
        if ( ! $lang_text || $lang_text == '' ) {
            continue;
        }
        $text = $lang_text;
        break;
    }
    if ( empty( $text ) ) {
        return '';
    }
    foreach ( $texts as $lang_text ) {
        if ( $lang_text != $text ) {
            return null;
        }
    }

    return $text;
}

function qtranxf_join_c( array $texts ): string {
    $text = qtranxf_allthesame( $texts );
    if ( ! is_null( $text ) ) {
        return $text;
    }
    $text = '';
    foreach ( $texts as $lang => $lang_text ) {
        if ( empty( $lang_text ) ) {
            continue;
        }
        $text .= '<!--:' . $lang . '-->' . $lang_text . '<!--:-->';
    }

    return $text;
}

function qtranxf_join_b_no_closing( array $texts ): string {
    $text = qtranxf_allthesame( $texts );
    if ( ! is_null( $text ) ) {
        return $text;
    }
    $text = '';
    foreach ( $texts as $lang => $lang_text ) {
        if ( empty( $lang_text ) ) {
            continue;
        }
        $text .= '[:' . $lang . ']' . $lang_text;
    }

    return $text;
}

function qtranxf_join_b( array $texts ): string {
    $text = qtranxf_allthesame( $texts );
    if ( ! is_null( $text ) ) {
        return $text;
    }
    $text = '';
    foreach ( $texts as $lang => $lang_text ) {
        if ( empty( $lang_text ) ) {
            continue;
        }
        $text .= '[:' . $lang . ']' . $lang_text;
    }
    if ( ! empty( $text ) ) {
        $text .= '[:]';
    }

    return $text;
}

/**
 * @since 3.3.6 swirly bracket encoding
 */
function qtranxf_join_s( array $texts ): string {
    $text = qtranxf_allthesame( $texts );
    if ( ! is_null( $text ) ) {
        return $text;
    }
    $text = '';
    foreach ( $texts as $lang => $lang_text ) {
        if ( empty( $lang_text ) ) {
            continue;
        }
        $text .= '{:' . $lang . '}' . $lang_text;
    }
    if ( ! empty( $text ) ) {
        $text .= '{:}';
    }

    return $text;
}

/**
 * Prepares multilingual text leaving text that matches $regex_sep outside of language tags.
 * @since 3.4.6.2
 */
function qtranxf_join_byseparator( array $texts, string $regex_sep ): string {
    $text = qtranxf_allthesame( $texts );
    if ( ! is_null( $text ) ) {
        return $text;
    }

    $lines = array();
    foreach ( $texts as $lang => $lang_text ) {
        $lines[ $lang ] = preg_split( $regex_sep, $lang_text, -1, PREG_SPLIT_DELIM_CAPTURE );
    }

    $text = '';
    while ( true ) {
        $done    = true;
        $to_join = array();
        $sep     = '';
        foreach ( $lines as $lang => $lang_lines ) {
            $t = next( $lang_lines );
            if ( $t === false ) {
                continue;
            }
            if ( preg_match( $regex_sep, $t ) ) {
                $sep = $t;
                $t   = next( $lang_lines );
            }
            $done             = false;
            $to_join[ $lang ] = $t;
        }
        if ( $done ) {
            break;
        }
        $text .= qtranxf_join_b( $to_join ) . $sep;
    }

    return $text;
}

/**
 * Prepare multilingal text leaving new line outside of language tags '[:]'.
 */
function qtranxf_join_byline( array $texts ): string {
    $text = qtranxf_allthesame( $texts );
    if ( ! is_null( $text ) ) {
        return $text;
    }

    $lines = array();
    foreach ( $texts as $lang => $text ) {
        $lines[ $lang ] = preg_split( '/\r?\n\r?/', $text );
    }

    $text = '';
    for ( $i = 0; true; ++$i ) {
        $done    = true;
        $to_join = array();
        foreach ( $lines as $lang => $lang_lines ) {
            if ( sizeof( $lang_lines ) <= $i ) {
                continue;
            }
            $done = false;
            $line = $lang_lines[ $i ];
            if ( ! $line || $line == '' ) {
                continue;
            }
            $to_join[ $lang ] = $line;
        }
        if ( $done ) {
            break;
        }
        $text .= qtranxf_join_b( $to_join ) . PHP_EOL;
    }

    return $text;
}

// TODO: this function signature is way too generic and weakly typed, break it by input type.
function qtranxf_use( string $lang, $text, bool $show_available = false, bool $show_empty = false ) {
    // return full string if language is not enabled
    if ( is_array( $text ) ) {
        // handle arrays recursively
        foreach ( $text as $key => $t ) {
            $text[ $key ] = qtranxf_use( $lang, $t, $show_available, $show_empty );
        }

        return $text;
    }

    if ( is_object( $text ) || $text instanceof __PHP_Incomplete_Class ) {
        foreach ( get_object_vars( $text ) as $key => $t ) {
            if ( ! isset( $text->$key ) ) {
                continue;
            }
            $text->$key = qtranxf_use( $lang, $t, $show_available, $show_empty );
        }

        return $text;
    }

    // prevent filtering weird data types and save some resources
    if ( ! is_string( $text ) || empty( $text ) ) {
        return $text;
    }

    return qtranxf_use_language( $lang, $text, $show_available, $show_empty );
}

/** when $text is already known to be string */
function qtranxf_use_language( string $lang, string $text, bool $show_available = false, bool $show_empty = false ) {
    $blocks = qtranxf_get_language_blocks( $text );
    if ( count( $blocks ) <= 1 )//no language is encoded in the $text, the most frequent case
    {
        return $text;
    }

    return qtranxf_use_block( $lang, $blocks, $show_available, $show_empty );
}

function qtranxf_use_block( string $lang, array $blocks, bool $show_available = false, bool $show_empty = false ): string {
    $available_langs = array();
    $content         = qtranxf_split_blocks( $blocks, $available_langs );

    return qtranxf_use_content( $lang, $content, $available_langs, $show_available, $show_empty );
}

function qtranxf_use_content( string $lang, $content, array $available_langs, bool $show_available = false, bool $show_empty = false ): string {
    global $q_config;
    // show the content in the requested language, if available
    if ( ! empty( $available_langs[ $lang ] ) ) {
        return $content[ $lang ];
    } elseif ( $show_empty ) {
        return '';
    }

    // content is not available in requested language (bad!!) what now?
    $alangs = array();
    foreach ( $q_config['enabled_languages'] as $language ) {
        if ( empty( $available_langs[ $language ] ) ) {
            continue;
        }
        $alangs[] = $language;
    }
    if ( empty( $alangs ) ) {
        return '';
    }

    $available_langs = $alangs;
    // set alternative language to the first available in the order of enabled languages
    $alt_lang    = current( $available_langs );
    $alt_content = $content[ $alt_lang ];

    if ( ! $show_available ) {
        if ( $q_config['show_displayed_language_prefix'] ) {
            return '(' . $q_config['language_name'][ $alt_lang ] . ') ' . $alt_content;
        } else {
            return $alt_content;
        }
    }

    // display selection for available languages
    $language_list = '';
    if ( preg_match( '/%LANG:([^:]*):([^%]*)%/', $q_config['not_available'][ $lang ], $match ) ) {
        $normal_separator = $match[1];
        $end_separator    = $match[2];
        // build available languages string backward
        $i = 0;
        foreach ( array_reverse( $available_langs ) as $language ) {
            if ( $i == 1 ) {
                $language_list = $end_separator . $language_list;
            } elseif ( $i > 1 ) {
                $language_list = $normal_separator . $language_list;
            }
            $language_name = qtranxf_getLanguageName( $language );
            $language_list = '<a href="' . qtranxf_convertURL( '', $language, false, true ) . '" class="qtranxs-available-language-link qtranxs-available-language-link-' . $language . '" title="' . $q_config['language_name'][ $language ] . '">' . $language_name . '</a>' . $language_list;
            ++$i;
        }
    }

    $msg    = preg_replace( '/%LANG:([^:]*):([^%]*)%/', $language_list, $q_config['not_available'][ $lang ] );
    $output = '<p class="qtranxs-available-languages-message qtranxs-available-languages-message-' . $lang . '">' . $msg . '</p>';
    if ( ! empty( $q_config['show_alternative_content'] ) ) {
        $output .= $alt_content;
    }

    return apply_filters( 'i18n_content_translation_not_available', $output, $lang, $language_list, $alt_lang, $alt_content, $msg, $q_config );
}

function qtranxf_showAllSeparated( $text ): string {
    if ( empty( $text ) ) {
        return $text;
    }
    global $q_config;
    $result = '';
    foreach ( qtranxf_getSortedLanguages() as $language ) {
        $result .= $q_config['language_name'][ $language ] . ':' . PHP_EOL . qtranxf_use( $language, $text ) . PHP_EOL . PHP_EOL;
    }

    return $result;
}
