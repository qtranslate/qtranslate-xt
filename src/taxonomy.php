<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @param WP_Term $term
 *
 * @since 3.4.6.9
 *
 */
function qtranxf_term_set_i18n_config( WP_Term $term ) {
    $term->i18n_config = array();
    if ( isset( $term->name ) ) {
        global $q_config;
        $default_language = $q_config['default_language'];
        if ( isset( $q_config['term_name'][ $term->name ] ) ) {
            $ts                      = $q_config['term_name'][ $term->name ];
            $ts[ $default_language ] = $term->name;
        } else {
            $ts = array( $default_language => $term->name );
        }
        $term->i18n_config['name'] = array( 'ts' => $ts );
    }
    if ( ! empty( $term->description ) && qtranxf_isMultilingual( $term->description ) ) {
        $ts                               = qtranxf_split( $term->description );
        $term->i18n_config['description'] = array( 'ts' => $ts, 'ml' => $term->description );
    }
}

/**
 * @since 3.4
 */
function qtranxf_term_use( string $lang, $obj, $taxonomy ) {
    global $q_config;
    if ( is_array( $obj ) ) {
        // handle arrays recursively
        foreach ( $obj as $key => $term ) {
            $obj[ $key ] = qtranxf_term_use( $lang, $term, $taxonomy );
        }

        return $obj;
    }
    if ( is_object( $obj ) ) {
        // object conversion
        if ( ! isset( $obj->i18n_config ) ) {
            qtranxf_term_set_i18n_config( $obj );
            if ( isset( $obj->i18n_config['name']['ts'][ $lang ] ) ) {
                $obj->name = $obj->i18n_config['name']['ts'][ $lang ];
            }
            if ( isset( $obj->i18n_config['description']['ts'][ $lang ] ) ) {
                $obj->description = $obj->i18n_config['description']['ts'][ $lang ];
            }
        }
    } elseif ( isset( $q_config['term_name'][ $obj ][ $lang ] ) ) {
        $obj = $q_config['term_name'][ $obj ][ $lang ];
    }

    return $obj;
}

function qtranxf_useTermLib( $obj ) {
    global $q_config;

    return qtranxf_term_use( $q_config['language'], $obj, null );
}

/**
 * @param WP_Term $term
 *
 * @return string term name in default language.
 * @since 3.4.6.9
 *
 */
function qtranxf_term_name_in( string $lang, WP_Term $term ): string {
    if ( isset( $term->i18n_config['name']['ts'][ $lang ] ) ) {
        return $term->i18n_config['name']['ts'][ $lang ];
    }

    return $term->name;
}
