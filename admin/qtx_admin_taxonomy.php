<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Used in Woocommerce
 *
 * @param string $name
 *
 * @return string Raw ML string
 */
function qtranxf_term_name_encoded( $name ) {
    global $q_config;
    if ( isset( $q_config['term_name'][ $name ] ) ) {
        $name = qtranxf_join_b( $q_config['term_name'][ $name ] );
    }

    return $name;
}

function qtranxf_get_term_joined( $obj, $taxonomy = null ) {
    global $q_config;
    if ( is_object( $obj ) ) {
        // WP_Term object conversion
        if ( ! isset( $obj->i18n_config ) ) {
            qtranxf_term_set_i18n_config( $obj );
            if ( isset( $obj->i18n_config['name']['ts'] ) ) {
                $ml        = qtranxf_join_b( $obj->i18n_config['name']['ts'] );
                $obj->name = $obj->i18n_config['name']['ml'] = $ml;
            }
        }
    } elseif ( isset( $q_config['term_name'][ $obj ] ) ) {
        $obj = qtranxf_join_b( $q_config['term_name'][ $obj ] );
        // TODO dead code? we probably do not need it
    }

    return $obj;
}

/**
 * @param string $lang two-letter language code to search for $term.
 * @param string $default_lang two-letter language code of the default language.
 * @param string $term name of term in language $lang.
 * @param string $taxonomy is not used for now.
 *
 * @return array translations of the term found.
 *
 * @since 3.4.6.8
 */
function qtranxf_term_find_translations( $lang, $default_lang, $term, $taxonomy = null ) {
    global $q_config;
    if ( empty( $default_lang ) ) {
        $default_lang = $q_config['default_language'];
    }
    $term_name = &$q_config['term_name'];
    while ( ! isset( $term_name[ $term ] ) ) {
        if ( empty( $lang ) ) {
            $lang = $q_config['language'];
        }
        if ( $lang != $default_lang ) {
            foreach ( $term_name as $nm => $ts ) {
                if ( empty( $ts[ $lang ] ) ) {
                    continue;
                }
                if ( $ts[ $lang ] != $term ) {
                    continue;
                }
                $term = $nm;
                break 2;
            }
        }

        return null;
    }
    $ts = &$term_name[ $term ];
    if ( ! isset( $ts[ $default_lang ] ) ) {
        $ts[ $default_lang ] = $term;
    }

    return $ts;
}

function qtranxf_get_terms_joined( $terms, $taxonomy = null, $args = null ) {
    if ( is_array( $terms ) ) {
        // handle arrays recursively
        foreach ( $terms as $key => $term ) {
            $terms[ $key ] = qtranxf_get_terms_joined( $term, $taxonomy );
        }
    } else {
        $terms = qtranxf_get_term_joined( $terms, $taxonomy );
    }

    return $terms;
}

function qtranxf_useAdminTermLibJoin( $obj, $taxonomies = null, $args = null ) {
    $page = qtranxf_get_page_referer();
    switch ( $page ) {
        case 'nav-menus.php':
        case 'edit-tags.php':
        case 'term.php':
        case 'edit.php':
            return qtranxf_get_terms_joined( $obj );
        default:
            return qtranxf_useTermLib( $obj );
    }
}

/**
 * @since 3.4.6.8
 * Called in 'sanitize_term_field' with default context like 'display'.
 */
function qtranxf_term_sanitize_name( $value, $term, $taxonomy = null, $context = null ) {
    global $pagenow;
    if ( $context == 'display' && $pagenow == 'edit.php' ) {
        return qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $value );
    }

    return $value;
}

add_filter( 'term_name', 'qtranxf_term_sanitize_name', 5, 4 );//used in function sanitize_term_field called from function sanitize_term with default context like 'display'

/**
 * @since 3.4.6.9
 * Version of function qtranxf_term_sanitize_name_db for unslashed argument $term.
 * ['terms_sanitized'][$term] for possible further processing.
 */
function qtranxf_term_sanitize_name_unslashed( $term, $taxonomy = null ) {
    global $q_config;
    $default_lang = $q_config['default_language'];
    if ( qtranxf_isMultilingual( $term ) ) {
        $langs = qtranxf_split( $term );
        $term  = qtranxf_ensure_language_set( $langs, $default_lang );
        if ( ! isset( $q_config['terms_sanitized'] ) ) {
            $q_config['terms_sanitized'] = array();
        }
        $q_config['terms_sanitized'][ $term ] = $langs;
    } else {
        if ( isset( $q_config['terms_sanitized'][ $term ] ) ) {
            return $term;
        }
        $lang  = $q_config['language'];
        $langs = qtranxf_term_find_translations( $lang, $default_lang, $term, $taxonomy );
        if ( $langs ) {
            $term = $langs[ $default_lang ];
            if ( ! isset( $q_config['terms_sanitized'] ) ) {
                $q_config['terms_sanitized'] = array();
            }
            $q_config['terms_sanitized'][ $term ] = $langs;
        }
    }

    return $term;
}

/**
 * @param string $term slashed value of a term name, which may be an ML value.
 * @param string $taxonomy provided to the filter, but is not used here.
 *
 * @return string slashed term name in the default language. Translations found are stored for possible further processing in $q_config['terms_sanitized'][$term_db], where $term_db is an unslashed value of term name in the default language.
 * @since 3.4.6.9
 * Response to filter "pre_term_{$field}" in function 'sanitize_term_field' with $field='name' and $context='db'
 *
 */
function qtranxf_term_sanitize_name_db( $term, $taxonomy = null ) {
    global $q_config;
    $term_db = stripcslashes( $term );
    if ( isset( $q_config['terms_sanitized'][ $term_db ] ) ) {
        return $term;
    }
    $term_db = qtranxf_term_sanitize_name_unslashed( $term_db, $taxonomy );

    return addslashes( $term_db );
}

/**
 * @since 3.4.6.9
 * Response to filter 'get_terms_args', data is unslashed.
 */
function qtranxf_term_get_args( $args, $taxonomies = null ) {
    if ( ! empty( $args['name'] ) ) {
        // expected in default language after applying sanitize_term_field
        $names = $args['name'];
        if ( is_array( $names ) ) {
            foreach ( $names as $key => $name ) {
                $names[ $key ] = qtranxf_term_sanitize_name_unslashed( $name );
            }
        } else {
            $names = qtranxf_term_sanitize_name_unslashed( $names );
        }
        $args['name'] = $names;
    }
    if ( ! empty( $args['name__like'] ) ) {
        global $q_config;
        $lang = $q_config['language'];
        if ( $lang != $q_config['default_language'] ) {
            $names = array();
            $s     = $args['name__like'];
            foreach ( $q_config['term_name'] as $name => $ts ) {
                if ( empty( $ts[ $lang ] ) ) {
                    continue;
                }
                $t = $ts[ $lang ];
                if ( function_exists( 'mb_stripos' ) ) {
                    $p = mb_stripos( $t, $s );
                } else {
                    $p = stripos( $t, $s );
                }
                if ( $p === false ) {
                    continue;
                }
                $names[] = $name;
            }
            if ( ! empty( $names ) ) {
                $args['name']       = $names;
                $args['name__like'] = '';
            }
        }
    }

    return $args;
}

/**
 * response to action 'edit_term', removes old translations
 * @since 3.4.6.9
 */
function qtranxf_term_del_translation( $term_id, $tt_id, $taxonomy ) {
    $term = wp_cache_get( $term_id, 'terms' );
    if ( ! $term ) {
        return;
    }
    global $q_config;
    $term_name = &$q_config['term_name'];
    $name      = $term->name;
    $changed   = false;
    if ( isset( $term_name[ $name ] ) ) {
        unset( $term_name[ $name ] );
        $changed = true;
    }
    if ( qtranxf_isMultilingual( $name ) ) {
        $default_language = $q_config['default_language'];
        $name             = qtranxf_use_language( $default_language, $name, false, true );
        if ( isset( $term_name[ $name ] ) ) {
            unset( $term_name[ $name ] );
            $changed = true;
        }
    }
    if ( $changed ) {
        update_option( 'qtranslate_term_name', $term_name );
    }
}

add_action( 'edit_term', 'qtranxf_term_del_translation', 5, 3 );

/**
 * response to actions 'created_term' and 'edited_term'
 * @since 3.4.6.9
 */
function qtranxf_term_set_translation( $term_id, $tt_id, $taxonomy ) {
    global $q_config;
    if ( empty( $q_config['terms_sanitized'] ) ) {
        return;
    }
    $default_language = $q_config['default_language'];
    $term             = get_term( $term_id, $taxonomy );
    $name             = qtranxf_term_name_in( $default_language, $term );
    if ( ! isset( $q_config['terms_sanitized'][ $name ] ) ) {
        return;
    }
    $langs = $q_config['terms_sanitized'][ $name ];
    unset( $q_config['terms_sanitized'][ $name ] );
    if ( empty( $langs ) ) {
        return;
    }

    if ( isset( $langs[ $default_language ] ) ) {
        unset( $langs[ $default_language ] );
        if ( empty( $langs ) ) {
            return;
        }
    }

    // TODO ensure unique name for each language within taxonomy

    $langs[ $default_language ] = $name;

    // keep enabled languages only in the order
    $ts = array();
    foreach ( $q_config['enabled_languages'] as $lang ) {
        if ( empty( $langs[ $lang ] ) ) {
            continue;
        }
        $val = trim( $langs[ $lang ] );
        if ( empty( $val ) ) {
            continue;
        }
        $ts[ $lang ] = $val;
    }

    if ( count( $ts ) == 1 ) {
        return; // default only
    }

    // store new translations
    $term_name          = &$q_config['term_name'];
    $term_name[ $name ] = $ts;
    update_option( 'qtranslate_term_name', $term_name );
}

add_action( 'created_term', 'qtranxf_term_set_translation', 5, 3 );
add_action( 'edited_term', 'qtranxf_term_set_translation', 5, 3 );

function qtranxf_term_delete( $term, $tt_id, $taxonomy, $deleted_term, $object_ids ) {
    global $q_config;
    if ( isset( $deleted_term->i18n_config['name'] ) ) {
        $default_language = $q_config['default_language'];
        $name             = $deleted_term->i18n_config['name']['ts'][ $default_language ];
    } else {
        $name = $deleted_term->name;
    }
    $term_name = &$q_config['term_name'];
    if ( ! isset( $term_name[ $name ] ) ) {
        return;
    }
    unset( $term_name[ $name ] );
    update_option( 'qtranslate_term_name', $term_name );
}

add_action( 'delete_term', 'qtranxf_term_delete', 5, 5 );

function qtranxf_admin_list_cats( $text ) {
    global $pagenow;
    switch ( $pagenow ) {
        case 'edit-tags.php':
        case 'term.php':
            //replace [:] with <:>
            $blocks = qtranxf_get_language_blocks( $text );
            if ( count( $blocks ) <= 1 ) {
                return $text;
            }
            $texts = qtranxf_split_blocks( $blocks );
            $text  = qtranxf_join_b( $texts ); // with closing tag

            return $text;
        default:
            return qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $text );
    }
}

add_filter( 'list_cats', 'qtranxf_admin_list_cats', 0 );

function qtranxf_admin_dropdown_cats( $text ) {
    global $pagenow;
    switch ( $pagenow ) {
        case 'edit-tags.php':
        case 'term.php':
            return $text;
        default:
            return qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $text );
    }
}

add_filter( 'wp_dropdown_cats', 'qtranxf_admin_dropdown_cats', 0 );

function qtranxf_admin_category_description( $text ) {
    global $pagenow;
    switch ( $pagenow ) {
        case 'term.php':
        case 'edit-tags.php':
            return $text;
        default:
            return qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $text );
    }
}

add_filter( 'category_description', 'qtranxf_admin_category_description', 0 );

function qtranxf_term_admin_remove_filters() {
    remove_filter( 'get_term', 'qtranxf_useAdminTermLibJoin', 5 );
    remove_filter( 'get_terms', 'qtranxf_useAdminTermLibJoin', 5 );
    remove_filter( 'get_terms_args', 'qtranxf_term_get_args', 5 );
    remove_filter( 'pre_term_name', 'qtranxf_term_sanitize_name_db', 999 );
}

function qtranxf_term_admin_add_filters() {
    add_filter( 'get_term', 'qtranxf_useAdminTermLibJoin', 5, 2 );
    add_filter( 'get_terms', 'qtranxf_useAdminTermLibJoin', 5, 3 );
    add_filter( 'get_terms_args', 'qtranxf_term_get_args', 5, 2 );
    add_filter( 'pre_term_name', 'qtranxf_term_sanitize_name_db', 999, 2 );//"pre_term_{$field}" in function sanitize_term_field with $field='name' and $context='db'
}

qtranxf_term_admin_add_filters();
