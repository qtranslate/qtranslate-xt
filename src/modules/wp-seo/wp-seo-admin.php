<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'qtranslate_admin_config', 'qtranxf_wpseo_load_admin_page_config' );
function qtranxf_wpseo_load_admin_page_config( array $page_configs ): array {
    assert( ! isset( $page_configs['yoast_wpseo'] ) );

    $page_configs['yoast_wpseo'] = array(
        'pages' => array( 'admin.php' => 'wpseo_titles' ),
        'forms' => array(
            array(
                'form'   => array( 'wpseo-conf' ),
                'fields' => array(
                    array( 'id' => 'company_name' ),
                )
            )
        )
    );

    return $page_configs;
}

/**
 * Store Raw ML values in Yoast indexables.
 *
 * Explicit Yoast fields are stored as Raw ML.
 * Empty fields remain null so Yoast templates and fallbacks keep working.
 * Breadcrumb title falls back to the Raw ML object title.
 */
function qtranxf_wpseo_save_raw_indexable_fields( $indexable ): void {
    if (
        ! is_object( $indexable ) ||
        empty( $indexable->object_id )
    ) {
        return;
    }

    $values = array();
    $changed = false;

    switch ( $indexable->object_type ) {
        case 'post':
            $post_id = (int) $indexable->object_id;
            $post    = get_post( $post_id );

            if ( ! $post instanceof WP_Post ) {
                return;
            }

            $meta_map = array(
                '_yoast_wpseo_title'
                    => 'title',

                '_yoast_wpseo_metadesc'
                    => 'description',

                '_yoast_wpseo_bctitle'
                    => 'breadcrumb_title',

                '_yoast_wpseo_opengraph-title'
                    => 'open_graph_title',

                '_yoast_wpseo_opengraph-description'
                    => 'open_graph_description',

                '_yoast_wpseo_twitter-title'
                    => 'twitter_title',

                '_yoast_wpseo_twitter-description'
                    => 'twitter_description',
            );

            foreach ( $meta_map as $meta_key => $field ) {
                $value = get_post_meta(
                    $post_id,
                    $meta_key,
                    true
                );

                $values[ $field ] = (
                    is_string( $value ) &&
                    $value !== ''
                ) ? $value : null;
            }

            /*
             * Breadcrumb title is the only field that must fall
             * back to the untranslated database object title.
             *
             * Other empty fields remain null so Yoast can use its
             * templates and normal fallback hierarchy.
             */
            if ( $values['breadcrumb_title'] === null ) {
                $values['breadcrumb_title'] = wp_strip_all_tags(
                    $post->post_title,
                    true
                );
            }
            break;

        case 'term':
            $term_id  = (int) $indexable->object_id;
            $taxonomy = $indexable->object_sub_type;
            $term     = get_term( $term_id, $taxonomy );

            if ( ! $term instanceof WP_Term ) {
                return;
            }

            $term_meta = array();

            if ( class_exists( 'WPSEO_Taxonomy_Meta' ) ) {
                $loaded_meta = WPSEO_Taxonomy_Meta::get_term_meta(
                    $term,
                    $taxonomy,
                    null
                );

                if ( is_array( $loaded_meta ) ) {
                    $term_meta = $loaded_meta;
                }
            }

            $meta_map = array(
                'wpseo_title'
                    => 'title',

                'wpseo_desc'
                    => 'description',

                'wpseo_bctitle'
                    => 'breadcrumb_title',

                'wpseo_opengraph-title'
                    => 'open_graph_title',

                'wpseo_opengraph-description'
                    => 'open_graph_description',

                'wpseo_twitter-title'
                    => 'twitter_title',

                'wpseo_twitter-description'
                    => 'twitter_description',
            );

            foreach ( $meta_map as $meta_key => $field ) {
                $value = $term_meta[ $meta_key ] ?? null;

                $values[ $field ] = (
                    is_string( $value ) &&
                    $value !== ''
                ) ? $value : null;
            }

            if ( $values['breadcrumb_title'] === null ) {
                if ( function_exists( 'qtranxf_get_term_joined' ) ) {
                    $term = qtranxf_get_term_joined(
                        $term,
                        $taxonomy
                    );
                }

                $values['breadcrumb_title'] = $term->name;
            }
            break;

        default:
            return;
    }

    foreach ( $values as $field => $value ) {
        if ( $indexable->{$field} === $value ) {
            continue;
        }

        $indexable->{$field} = $value;
        $changed = true;
    }

    if ( $changed ) {
        /*
         * Direct model save does not trigger the
         * wpseo_saved_indexable action recursively.
         */
        $indexable->save();
    }
}

add_action(
    'wpseo_saved_indexable',
    'qtranxf_wpseo_save_raw_indexable_fields',
    10
);
