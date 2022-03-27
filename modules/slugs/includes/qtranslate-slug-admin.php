<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
include_once( dirname( __FILE__ ) . '/qtranslate-slug-settings.php' );

// add filters
add_filter( 'qts_validate_post_slug', 'qts_validate_post_slug', 0, 3 );
add_filter( 'qts_validate_post_slug', 'qts_unique_post_slug', 1, 3 );
add_filter( 'qts_validate_term_slug', 'qts_validate_term_slug', 0, 3 );
add_filter( 'qts_validate_term_slug', 'qts_unique_term_slug', 1, 3 );
add_filter( 'wp_get_object_terms', 'qts_get_object_terms', 0, 4 );
add_filter( 'get_terms', 'qts_get_terms', 0, 3 );
// admin actions
add_action( 'add_meta_boxes', 'qts_add_slug_meta_box' );
add_action( 'save_post', 'qts_save_postdata', 605, 2 );
add_action( 'edit_attachment', 'qts_save_postdata' );
add_action( 'created_term', 'qts_save_term', 605, 3 );
add_action( 'edited_term', 'qts_save_term', 605, 3 );
add_action( 'admin_head', 'qts_hide_term_slug_box', 900 );
add_action( 'init', 'qts_taxonomies_hooks', 805 );
add_action( 'admin_head', 'qts_hide_quick_edit', 600 );
// plugin deactivation/uninstall
register_deactivation_hook( QTRANSLATE_FILE, 'qts_deactivate' );
register_uninstall_hook( QTRANSLATE_FILE, 'qts_uninstall' );

/**
 * Adds support for qtranslate in taxonomies.
 */
function qts_taxonomies_hooks() {
    global $qtranslate_slug;

    $taxonomies = $qtranslate_slug->get_public_taxonomies();

    if ( $taxonomies ) {
        foreach ( $taxonomies as $taxonomy ) {
            add_action( $taxonomy->name . '_add_form_fields', 'qts_show_add_term_fields' );
            add_action( $taxonomy->name . '_edit_form_fields', 'qts_show_edit_term_fields' );
            add_filter( 'manage_edit-' . $taxonomy->name . '_columns', 'qts_taxonomy_columns' );
            add_filter( 'manage_' . $taxonomy->name . '_custom_column', 'qts_taxonomy_custom_column', 0, 3 );
        }
    }
}

/**
 * Do the installation, support multisite.
 */
function qts_multi_activate() {
    if ( is_plugin_active_for_network( plugin_basename( QTRANSLATE_FILE ) ) ) {
        $old_blog = get_current_blog_id();
        $blogs    = get_sites();
        foreach ( $blogs as $blog ) {
            switch_to_blog( $blog->blog_id );
            qts_activate();
        }
        switch_to_blog( $old_blog );

        return;
    }

    qts_activate();
}

/**
 * Delete plugin stored data ( options and postmeta data ).
 */
function qts_uninstall() {
    global $q_config, $wpdb;

    delete_option( QTS_OPTIONS_NAME );

    $meta_keys = array();
    foreach ( $q_config['enabled_languages'] as $lang ) {
        $meta_keys[] = QTS_META_PREFIX . $lang;
    }
    $meta_keys = "'" . implode( "','", $meta_keys ) . "'";
    $wpdb->query( "DELETE from $wpdb->postmeta WHERE meta_key IN ($meta_keys)" );

    qts_deactivate();

}

/**
 * Activates and do the installation.
 */
function qts_activate() {
    global $qtranslate_slug;

    // regenerate rewrite rules in db
    add_action( 'generate_rewrite_rules', array( &$qtranslate_slug, 'modify_rewrite_rules' ) );
    flush_rewrite_rules();
}

/**
 * Actions when deactivating the plugin.
 */
function qts_deactivate() {
    global $wp_rewrite;
    global $qtranslate_slug;

    // regenerate rewrite rules in db
    remove_action( 'generate_rewrite_rules', array( &$qtranslate_slug, 'modify_rewrite_rules' ) );
    $wp_rewrite->flush_rules();
}

/**
 * Creates a metabox for every post type available.
 */
function qts_add_slug_meta_box() {
    remove_meta_box( 'slugdiv', null, 'normal' );
    add_meta_box( 'qts_sectionid', __( 'Slugs per language', 'qtranslate' ), array(
        &$this,
        'qts_draw_meta_box'
    ), null, 'side', 'high' );
}

/**
 * Shows the fields where insert the translated slugs in the post and page edit form.
 *
 * @param $post (object) current post object
 */
function qts_draw_meta_box( $post ) {
    global $q_config; // //TODO: q_config  : language_name

    // Use nonce for verification
    echo "<table style=\"width:100%\">" . PHP_EOL;
    echo "<input type=\"hidden\" name=\"qts_nonce\" id=\"qts_nonce\" value=\"" . wp_create_nonce( 'qts_nonce' ) . "\" />" . PHP_EOL;

    foreach ( $q_config['enabled_languages'] as $lang ):

        $slug = get_post_meta( $post->ID, QTS_META_PREFIX . $lang, true );

        $value = ( $slug ) ? htmlspecialchars( $slug, ENT_QUOTES ) : '';

        echo "<tr>" . PHP_EOL;
        echo "<th style=\"text-align:left; width:10%; color:#555 \"><label for=\"qts_{$lang}_slug\">" . __( $q_config['language_name'][ $lang ], 'qtranslate' ) . "</label></th>" . PHP_EOL;
        echo "<td><input type=\"text\" id=\"qts_{$lang}_slug\" name=\"qts_{$lang}_slug\" value=\"" . urldecode( $value ) . "\" style=\"width:90%; margin-left:10%; color:#777\" /></td>" . PHP_EOL;
        echo "</tr>" . PHP_EOL;

    endforeach;

    echo '</table>' . PHP_EOL;
}

/**
 * Sanitize title as slug, if empty slug.
 *
 * @param $post (object) the post object
 * @param $slug (string) the slug name
 * @param $lang (string) the language
 *
 * @return string the slug validated
 */
function qts_validate_post_slug( $slug, $post, $lang ) {
    $post_title = trim( qtranxf_use( $lang, $post->post_title ) );
    $post_name  = get_post_meta( $post->ID, QTS_META_PREFIX . $lang, true );
    if ( ! $post_name ) {
        $post_name = $post->post_name;
    }

    //TODO: if has a slug, test and use it
    //TODO: and then replace the default slug with the dafault language slug
    $name = ( $post_title === '' ) ? $post_name : $post_title;

    $slug = trim( $slug );

    $slug = ( $slug === '' ) ? sanitize_title( $name ) : sanitize_title( $slug );

    return htmlspecialchars( $slug, ENT_QUOTES );
}

/**
 * Validates post slug against repetitions per language
 *
 * @param $post (object) the post object
 * @param $slug (string) the slug name
 * @param $lang (string) the language
 *
 * @return string the slug validated
 */
function qts_unique_post_slug( $slug, $post, $lang ) {

    $original_status = $post->post_status;

    if ( in_array( $post->post_status, array( 'draft', 'pending' ) ) ) {
        $post->post_status = 'publish';
    }

    $slug = qts_wp_unique_post_slug( $slug, $post->ID, $post->post_status, $post->post_type, $post->post_parent, $lang );

    $post->post_status = $original_status;

    return $slug;
}

/**
 * Computes a unique slug for the post and language, when given the desired slug and some post details.
 *
 * @param string $slug the desired slug (post_name)
 * @param integer $post_ID
 * @param string $post_status no uniqueness checks are made if the post is still draft or pending
 * @param string $post_type
 * @param integer $post_parent
 *
 * @return string unique slug for the post, based on language meta_value (with a -1, -2, etc. suffix)
 */
function qts_wp_unique_post_slug( $slug, $post_ID, $post_status, $post_type, $post_parent, $lang ) {
    if ( in_array( $post_status, array( 'draft', 'pending', 'auto-draft' ) ) ) {
        return $slug;
    }

    global $wpdb, $wp_rewrite;

    $feeds = $wp_rewrite->feeds;
    if ( ! is_array( $feeds ) ) {
        $feeds = array();
    }

    if ( 'attachment' == $post_type ) {
        // Attachment slugs must be unique across all types.
        $check_sql       = "SELECT post_name FROM $wpdb->posts WHERE post_name = %s AND ID != %d LIMIT 1";
        $post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $slug, $post_ID ) );

        if ( $post_name_check || in_array( $slug, $feeds ) || apply_filters( 'wp_unique_post_slug_is_bad_attachment_slug', false, $slug ) ) {
            $suffix = 2;
            do {
                // TODO: update unique_slug :: differs from current wp func ( 4.3.1 )
                $alt_post_name   = substr( $slug, 0, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
                $post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $alt_post_name, $post_ID ) );
                $suffix++;
            } while ( $post_name_check );
            $slug = $alt_post_name;
        }
    } else {
        // TODO: update unique_slug :: missing hieararchical from current wp func ( 4.3.1 )
        // Post slugs must be unique across all posts.
        $check_sql       = "SELECT $wpdb->postmeta.meta_value FROM $wpdb->posts,$wpdb->postmeta WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_key = '%s' AND $wpdb->postmeta.meta_value = '%s' AND $wpdb->posts.post_type = %s AND ID != %d LIMIT 1";
        $post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, QTS_META_PREFIX . $lang, $slug, $post_type, $post_ID ) );

        // TODO: update unique_slug :: missing check for conflict with dates archive from current wp func ( 4.3.1 )
        if ( $post_name_check || in_array( $slug, $feeds ) || apply_filters( 'wp_unique_post_slug_is_bad_flat_slug', false, $slug, $post_type ) ) {
            $suffix = 2;
            do {
                // TODO: update unique_slug :: same as above: differs from current wp func ( 4.3.1 )
                $alt_post_name   = substr( $slug, 0, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
                $post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, QTS_META_PREFIX . $lang, $alt_post_name, $post_type, $post_ID ) );
                $suffix++;
            } while ( $post_name_check );
            $slug = $alt_post_name;
        }
    }

    return $slug;
}


/**
 * Saves the translated slug when the page is saved.
 *
 * @param $post_id int the post id
 * @param $post object the post object
 *
 * @return void
 */
function qts_save_postdata( $post_id, $post = null ) {
    global $q_config;
    if ( is_null( $post ) ) {
        $post = get_post( $post_id );
    }
    $post_type_object = get_post_type_object( $post->post_type );

    if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )                       // check autosave
         || ( ! isset( $_POST['post_ID'] ) || $post_id != $_POST['post_ID'] ) // check revision
         || ( isset( $_POST['qts_nonce'] ) && ! wp_verify_nonce( $_POST['qts_nonce'], 'qts_nonce' ) )   // verify nonce
         || ( ! current_user_can( $post_type_object->cap->edit_post, $post_id ) ) ) {  // check permission
        return;
    }
    foreach ( $q_config['enabled_languages'] as $lang ) {

        // check required because it is not available inside quick edit
        if ( isset( $_POST["qts_{$lang}_slug"] ) ) {
            $meta_value = apply_filters( 'qts_validate_post_slug', $_POST["qts_{$lang}_slug"], $post, $lang );
            delete_post_meta( $post_id, QTS_META_PREFIX . $lang );
            update_post_meta( $post_id, QTS_META_PREFIX . $lang, $meta_value );
        }
    }
}

/**
 * Sanitize title as slug, if empty slug.
 *
 * @param $term (object) the term object
 * @param $slug (string) the slug name
 * @param $lang (string) the language
 *
 * @return string the slug validated
 */
function qts_validate_term_slug( $slug, $term, $lang ) {
    $term_name = trim( qtranxf_use( $lang, $term->name, false, true ) );
    if ( $term_name === '' ) {
        $term_name = trim( qtranxf_use( $q_config['default_language'], $term->name ) );
    }
    $slug = trim( $slug );
    $slug = $slug === '' ? sanitize_title( $term_name ) : sanitize_title( $slug );

    return htmlspecialchars( $slug, ENT_QUOTES );
}

/**
 * Will make slug unique per language, if it isn't already.
 *
 * @param string $slug The string that will be tried for a unique slug
 * @param object $term The term object that the $slug will belong too
 * @param object $lang The language reference
 *
 * @return string Will return a true unique slug.
 *
 * @since 1.0
 */
function qts_unique_term_slug( $slug, $term, $lang ) {
    global $wpdb;

    $query       = $wpdb->prepare( "SELECT term_id FROM $wpdb->termmeta WHERE meta_key = '%s' AND meta_value = '%s' AND term_id != %d ", QTS_META_PREFIX . $lang, $slug, $term->term_id );
    $exists_slug = $wpdb->get_results( $query );

    if ( empty( $exists_slug ) ) {
        return $slug;
    }

    // If we didn't get a unique slug, try appending a number to make it unique.
    $query = $wpdb->prepare( "SELECT meta_value FROM $wpdb->termmeta WHERE meta_key = '%s' AND meta_value = '%s' AND term_id != %d", QTS_META_PREFIX . $lang, $slug, $term->term_id );

    if ( $wpdb->get_var( $query ) ) {
        $num = 2;
        do {
            $alt_slug = $slug . "-$num";
            $num++;
            $slug_check = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT meta_value FROM $wpdb->termmeta WHERE meta_key = '%s' AND meta_value = '%s'",
                    QTS_META_PREFIX . $lang,
                    $alt_slug ) );
        } while ( $slug_check );
        $slug = $alt_slug;
    }

    return $slug;
}

/**
 * Display multiple input fields, one per language.
 *
 * @param $term_id int the term id
 * @param $tt_id int the term taxonomy id
 * @param $taxonomy object the term object
 *
 * @return void
 */
function qts_save_term( $term_id, $tt_id, $taxonomy ) {
    global $q_config;
    $cur_screen = get_current_screen();
    if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )  // check autosave
         || ( ! current_user_can( 'edit_posts' ) ) // check permission
         || ( isset( $cur_screen ) && $cur_screen->id === "nav-menus" ) //TODO: check if this condition is really needed
    ) {
        return;
    }

    $term = get_term( $term_id, $taxonomy );
    foreach ( $q_config['enabled_languages'] as $lang ) {
        //condition is needed in case term is added through ajax e.g. in post edit page
        $term_slug = isset( $_POST["qts_{$lang}_slug"] ) ? $_POST["qts_{$lang}_slug"] : '';

        $meta_value = apply_filters( 'qts_validate_term_slug', $term_slug, $term, $lang );

        delete_metadata( 'term', $term_id, QTS_META_PREFIX . $lang );
        update_metadata( 'term', $term_id, QTS_META_PREFIX . $lang, $meta_value );
    }
}

/**
 * Display multiple input fields, one per language for add term page.
 *
 * @param $term string the term object
 */
function qts_show_add_term_fields( $term ) {
    global $q_config;

    echo "<div id=\"form-field term-slug-wrap\">" . PHP_EOL;
    foreach ( $q_config['enabled_languages'] as $lang ) {
        echo "<div class=\"form-field\">" . PHP_EOL;
        $slug  = ( is_object( $term ) ) ? get_metadata( 'term', $term->term_id, QTS_META_PREFIX . $lang, true ) : '';
        $value = ( $slug ) ? htmlspecialchars( $slug, ENT_QUOTES ) : '';
        echo "<label for=\"qts_{$lang}_slug\">" . sprintf( __( 'Slug' ) . ' (%s)', $q_config['language_name'][ $lang ] ) . "</label>" . PHP_EOL;
        echo "<input type=\"text\" name=\"qts_{$lang}_slug\" value=\"" . urldecode( $value ) . "\" aria-required=\"true\">" . PHP_EOL;
        echo '</div>';
    }
    echo '</div>';
}

/**
 * Display multiple input fields, one per language for edit term page.
 *
 * @param $term string the term object
 */
function qts_show_edit_term_fields( $term ) {
    global $q_config;

    echo "<table class=\"form-table\">" . PHP_EOL;
    foreach ( $q_config['enabled_languages'] as $lang ) {
        $slug  = ( is_object( $term ) ) ? get_metadata( 'term', $term->term_id, QTS_META_PREFIX . $lang, true ) : '';
        $value = ( $slug ) ? htmlspecialchars( $slug, ENT_QUOTES ) : '';
        echo "<tr class=\"form-field term-slug-wrap\">" . PHP_EOL;
        echo "<th scope=\"row\"><label for=\"qts_{$lang}_slug\">" . sprintf( __( 'Slug' ) . ' (%s)', $q_config['language_name'][ $lang ] ) . "</label></th>" . PHP_EOL;
        echo "<td><input type=\"text\" name=\"qts_{$lang}_slug\" value=\"" . urldecode( $value ) . "\" /></td></tr>" . PHP_EOL;
    }
    echo "</table>";
}

/**
 * Hide automatically the wordpress slug box in edit terms page.
 */
function qts_hide_term_slug_box() {
    global $pagenow;
    switch ( $pagenow ):
        case 'edit-tags.php':
            $id = 'tag-slug';
            break;
        case 'term.php':
            $id = 'slug';
            break;
        default:
            return;
    endswitch;

    echo "<!-- QTS remove slug box -->" . PHP_EOL;
    echo "<script type=\"text/javascript\" charset=\"utf-8\">" . PHP_EOL;
    echo "  jQuery(document).ready(function($){" . PHP_EOL;
    echo "      $(\"#" . $id . "\").parent().hide();" . PHP_EOL;
    echo "      $(\".form-field td #slug\").parent().parent().hide();" . PHP_EOL;
    echo "  });" . PHP_EOL;
    echo "</script>" . PHP_EOL;
}

/**
 * Hide quickedit slug.
 */
function qts_hide_quick_edit() {
    echo "<!-- QTS remove quick edit box -->" . PHP_EOL;
    echo "<style media=\"screen\">" . PHP_EOL;
    echo "  .inline-edit-row fieldset.inline-edit-col-left .inline-edit-col *:first-child + label { display: none !important }" . PHP_EOL;
    echo "</style>" . PHP_EOL;
}

function qts_taxonomy_columns( $columns ) {
    unset( $columns['slug'] );
    $columns['qts-slug'] = __( 'Slug' );

    return $columns;
}

function qts_taxonomy_custom_column( $str, $column_name, $term_id ) {
    global $q_config;

    switch ( $column_name ) {
        case 'qts-slug':
            echo get_metadata( 'term', $term_id, QTS_META_PREFIX . $q_config['language'], true );
            break;
    }

    return false;
}


//TODO: check if following function is needed
/**
 * Fix for:
 * - Taxonomy & custom taxonomy names in Post Manage page
 * - List of tags already added to the post in Post
 * - Edit page (but have issues when saving)
 *
 * @param (array) $terms
 * @param (int|array) $obj_id
 * @param (string|array) $taxonomy
 * @param (array) $taxonomy
 */
function qts_get_object_terms( $terms, $obj_id, $taxonomy, $args ) {

    global $pagenow;

    // Although in post edit page the tags are translated,
    // but when saving/updating the post Wordpress considers
    // the translated tags as new tags. Due to this
    // issue I limit this 'hack' to the post manage
    // page only.
    if ( $pagenow == 'edit.php' ) {
        $meta = get_option( 'qtranslate_term_name' );

        if ( ! empty( $terms ) ) {
            foreach ( $terms as $term ) {
                if ( isset( $meta[ $term->name ][ $q_config['language'] ] ) ) {
                    $term->name = $meta[ $term->name ][ $q_config['language'] ];
                }
            }
        }

    }

    return $terms;
}

/**
 * Fix for:
 * - Taxonomy names in Taxonomy Manage page
 * - 'Popular Tags' in Taxonomy (Tags) Manage page
 * - Category filter dropdown menu in Post Manage page
 * - Category list in Post Edit page
 * - 'Most Used' tags list in Post Edit page (but have issues when saving)
 *
 * @param (array) $terms
 * @param (string|array) $taxonomy
 */
function qts_get_terms( $terms, $taxonomy ) {

    global $pagenow;
    global $q_config;

    if ( $pagenow != 'admin-ajax.php' ) {

        $meta = get_option( 'qtranslate_term_name' );

        if ( ! empty( $terms ) ) {
            foreach ( $terms as $term ) {
                // after saving, dont do anything
                if ( ( isset( $_POST['action'] ) && $_POST['action'] == "editedtag" ) ||
                     ! is_object( $term ) ) {
                    return $terms;
                }
                if ( isset( $meta[ $term->name ][ $q_config['language'] ] ) ) {
                    $term->name = $meta[ $term->name ][ $q_config['language'] ];
                }
            }
        }
    }

    return $terms;
}

function qts_ma_module_updated() {
    global $q_config;
    if ( $q_config['ma_module_enabled']['slugs'] ) {
        qts_multi_activate();
    } else {
        qts_deactivate();
    }
}