<?php
/**
 * Define our settings sections.
 *
 * @return array key=$id, array value=$title in: add_settings_section( $id, $title, $callback, $page );
 */
function qts_options_page_sections() {
    $sections               = array();
    $sections['post_types'] = __( 'Post types', 'qts' );
    $sections['taxonomies'] = __( 'Taxonomies', 'qts' );

    return $sections;
}

/**
 * Helper for create arrays of choices.
 *
 * @return array
 */
function get_multi_txt_choices( $name = false ) {
    global $q_config;

    if ( ! $name ) {
        return array();
    }
    $choices = array();
    foreach ( $q_config['enabled_languages'] as $lang ) {
        $label     = sprintf( __( 'Slug (%s)', 'qts' ), $q_config['language_name'][ $lang ] );
        $choices[] = "$label|$lang"; // prints: 'Slug (English)|en' ( $name = books )
    }

    return $choices;
}

/**
 * Define our form fields (settings).
 *
 * @return array
 */
function qts_options_page_fields() {
    $post_types = get_post_types( array( '_builtin' => false, 'public' => true ), 'objects' );

    // each post type
    foreach ( $post_types as $post_type ):
        $options[] = array(
            "section" => "post_types",
            "id"      => QTS_PREFIX . "post_type_" . $post_type->name,
            "title"   => $post_type->labels->singular_name,
            "desc"    => sprintf( __( '<code>https://example.org/<u>%s</u>/some-%s/</code>', 'qts' ), $post_type->name, $post_type->name ),
            'class'   => 'qts-slug',
            "type"    => "multi-text",
            "choices" => get_multi_txt_choices( $post_type->name ),
            "std"     => ""
        );
    endforeach;
    // end each post type

    $options[] = array(
        "section" => "taxonomies",
        "id"      => QTS_PREFIX . "taxonomy_category",
        "title"   => __( 'Categories', 'qts' ),
        "desc"    => __( '<code>https://example.org/<u>category</u>/some-category/</code>', 'qts' ),
        "type"    => "multi-text",
        'class'   => 'qts-slug',
        "choices" => get_multi_txt_choices( 'category' ),
        "std"     => ""
    );

    $options[] = array(
        "section" => "taxonomies",
        "id"      => QTS_PREFIX . "taxonomy_post_tag",
        "title"   => __( 'Tags', 'qts' ),
        "desc"    => __( '<code>https://example.org/<u>tag</u>/some-tag/</code>', 'qts' ),
        "type"    => "multi-text",
        'class'   => 'qts-slug',
        "choices" => get_multi_txt_choices( 'post_tag' ),
        "std"     => ""
    );

    $taxonomies = get_taxonomies( array( 'public' => true, 'show_ui' => true, '_builtin' => false ), 'object' );

    // each extra taxonomy
    foreach ( $taxonomies as $taxonomy ):
        $options[] = array(
            "section" => "taxonomies",
            "id"      => QTS_PREFIX . "taxonomy_" . $taxonomy->name,
            "title"   => $taxonomy->labels->singular_name,
            "desc"    => sprintf( __( '<code>https://example.org/<u>%s</u>/some-%s/</code>', 'qts' ), $taxonomy->name, $taxonomy->name ),
            "type"    => "multi-text",
            'class'   => 'qts-slug',
            "choices" => get_multi_txt_choices( $taxonomy->name ),
            "std"     => ""
        );
    endforeach;

    // end each extra taxonomy


    return $options;
}

/**
 * Contextual Help.
 */
function qts_options_page_contextual_help() {
    $text = "<h3>" . __( 'Qtranslate Settings - Contextual Help', 'qts' ) . "</h3>";
    $text .= "<p>" . __( 'Contextual help goes here. You may want to use different html elements to format your text as you want.', 'qts' ) . "</p>";

    return $text;
}
