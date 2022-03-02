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
function get_multi_txt_choices() {
    global $q_config;

    $choices = array();
    foreach ( $q_config['enabled_languages'] as $lang ) {
        $label     = sprintf( __( 'Slug', 'qts' ).' (%s)', $q_config['language_name'][ $lang ] );
        $choices[] = "$label|$lang"; // prints: 'Slug (English)|en'
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
        $options[] = qts_options_page_build_slug_fields($post_type,"post_types", "post_type_");
    endforeach;
    // end each post type

    $taxonomies = get_taxonomies( array( 'public' => true, 'show_ui' => true), 'object' );

    // each extra taxonomy
    foreach ( $taxonomies as $taxonomy ):
        $options[] = qts_options_page_build_slug_fields($taxonomy,"taxonomies", "taxonomy_");
    endforeach;
    // end each extra taxonomy

    return $options;
}

function qts_options_page_build_slug_fields($object,$target_section, $id_prefix) {
    return array(
            "section" => $target_section,
            "id"      => QTS_PREFIX . $id_prefix . $object->name,
            "title"   => qtranxf_use(qtranxf_getLanguage(), $object->label),
            "desc"    => sprintf( '<code>https://example.org/<u>%s</u>/some-%s/</code>', array_key_exists('slug',$object->rewrite)?ltrim($object->rewrite['slug'],"/"):$object->name, $object->name ),
            'class'   => 'qts-slug', //used in qts_validate_options. TODO: cleaner way to be considered...
            "type"    => "multi-text",
            "choices" => get_multi_txt_choices(),
            "std"     => ""
        );
}
