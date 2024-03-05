<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
require_once __DIR__ . '/admin_settings.php';

// add filters
add_filter( 'wp_get_object_terms', 'qtranxf_slugs_get_object_terms', 0, 4 );
add_filter( 'get_terms', 'qtranxf_slugs_get_terms', 0, 3 );

// admin actions
add_action( 'add_meta_boxes', 'qtranxf_slugs_add_slug_meta_box', 900 );
add_action( 'save_post', 'qtranxf_slugs_save_postdata', 605, 2 );
add_action( 'edit_attachment', 'qtranxf_slugs_save_postdata' );
add_action( 'created_term', 'qtranxf_slugs_save_term', 605, 3 );
add_action( 'edited_term', 'qtranxf_slugs_save_term', 605, 3 );
add_action( 'admin_head', 'qtranxf_slugs_hide_term_slug_box', 900 );
add_action( 'init', 'qtranxf_slugs_taxonomies_hooks', 805 );
add_action( 'admin_head', 'qtranxf_slugs_hide_quick_edit', 600 );
add_action( 'qtranslate_save_config', 'qtranxf_slugs_ma_module_updated' );

// plugin deactivation/uninstall
register_deactivation_hook( QTRANSLATE_FILE, 'qtranxf_slugs_deactivate' );
register_uninstall_hook( QTRANSLATE_FILE, 'qtranxf_slugs_uninstall' );

/**
 * Add support for taxonomies and optional integration with WooCommerce.
 */
function qtranxf_slugs_taxonomies_hooks(): void {
    global $qtranslate_slugs;

    $taxonomies = $qtranslate_slugs->get_public_taxonomies();

    if ( $taxonomies ) {
        foreach ( $taxonomies as $taxonomy ) {
            add_action( $taxonomy->name . '_add_form_fields', 'qtranxf_slugs_show_add_term_fields' );
            add_action( $taxonomy->name . '_edit_form_fields', 'qtranxf_slugs_show_edit_term_fields' );
            add_filter( 'manage_edit-' . $taxonomy->name . '_columns', 'qtranxf_slugs_taxonomy_columns' );
            add_filter( 'manage_' . $taxonomy->name . '_custom_column', 'qtranxf_slugs_taxonomy_custom_column', 0, 3 );
        }
    }

    if ( QTX_Module_Loader::is_module_active( 'woo-commerce' ) ) {
        add_action( 'woocommerce_after_add_attribute_fields', 'qtranxf_slugs_show_add_taxonomy_slugs_option_link' );
        add_action( 'woocommerce_after_edit_attribute_fields', 'qtranxf_slugs_show_edit_taxonomy_slugs_option_link' );
    }
}

/**
 * Do the installation, support multisite.
 */
function qtranxf_slugs_multi_activate(): void {
    if ( is_plugin_active_for_network( plugin_basename( QTRANSLATE_FILE ) ) ) {
        $old_blog = get_current_blog_id();
        $blogs    = get_sites();
        foreach ( $blogs as $blog ) {
            switch_to_blog( $blog->blog_id );
            qtranxf_slugs_activate();
        }
        switch_to_blog( $old_blog );

        return;
    }

    qtranxf_slugs_activate();
}

/**
 * Delete plugin stored data ( options and postmeta data ).
 */
function qtranxf_slugs_uninstall(): void {
    global $q_config, $wpdb;

    delete_option( QTX_OPTIONS_MODULE_SLUGS );

    $meta_keys = array();
    foreach ( $q_config['enabled_languages'] as $lang ) {
        $meta_keys[] = QTX_SLUGS_META_PREFIX . $lang;
    }
    $meta_keys = "'" . implode( "','", $meta_keys ) . "'";
    $wpdb->query( "DELETE from $wpdb->postmeta WHERE meta_key IN ($meta_keys)" );
    $wpdb->query( "DELETE from $wpdb->termmeta WHERE meta_key IN ($meta_keys)" );

    qtranxf_slugs_deactivate();

}

/**
 * Activates and do the installation.
 */
function qtranxf_slugs_activate(): void {
    global $qtranslate_slugs;

    // regenerate rewrite rules in db
    add_action( 'generate_rewrite_rules', array( &$qtranslate_slugs, 'modify_rewrite_rules' ) );
    flush_rewrite_rules();
}

/**
 * Actions when deactivating the plugin.
 */
function qtranxf_slugs_deactivate(): void {
    global $wp_rewrite;
    global $qtranslate_slugs;

    // regenerate rewrite rules in db
    remove_action( 'generate_rewrite_rules', array( &$qtranslate_slugs, 'modify_rewrite_rules' ) );
    $wp_rewrite->flush_rules();
}

/**
 * Creates a metabox for every post type available.
 */
function qtranxf_slugs_add_slug_meta_box(): void {
    global $wp_meta_boxes;

    //Replace slugs metabox only if existing and not already removed
    if ( ! empty( $wp_meta_boxes[ get_current_screen()->id ]['normal']['core']['slugdiv'] ) ) {
        remove_meta_box( 'slugdiv', null, 'normal' );
        add_meta_box( 'qts_sectionid', __( 'Slugs per language', 'qtranslate' ), 'qtranxf_slugs_draw_meta_box', null, 'side', 'high' );
    }
}

/**
 * Shows the fields where insert the translated slugs in the post and page edit form.
 *
 * @param $post (object) current post object
 */
function qtranxf_slugs_draw_meta_box( $post ): void {
    global $q_config;

    // Use nonce for verification
    echo '<table class="qtranxs-slugs-metabox">' . PHP_EOL;
    echo '<input type="hidden" name="qts_nonce" id="qts_nonce" value="' . wp_create_nonce( 'qts_nonce' ) . '" />' . PHP_EOL;
    $flag_location = qtranxf_flag_location();
    foreach ( $q_config['enabled_languages'] as $lang ):
        $slug  = get_post_meta( $post->ID, QTX_SLUGS_META_PREFIX . $lang, true );
        $value = ( $slug ) ? htmlspecialchars( $slug, ENT_QUOTES ) : '';
        $name  = $q_config['language_name'][ $lang ];
        $title = sprintf( __( 'Slug' ) . ' (%s)', $name );
        echo "<tr>" . PHP_EOL;
        echo "<th><img class='qtranxs-lang-flag' src='{$flag_location}{$q_config['flag'][ $lang ]}' alt='{$name}' title='{$name}' /></th>" . PHP_EOL;
        echo "<td><input type='text' id='qts_{$lang}_slug' name='qts_{$lang}_slug' value=\"" . urldecode( $value ) . "\" title='$title' /></td>" . PHP_EOL;
        echo "</tr>" . PHP_EOL;
    endforeach;
    echo '</table>' . PHP_EOL;
}

/**
 * Sanitize a post slug for a given language.
 *
 * @param string $slug slug name
 * @param WP_Post $post the post object
 * @param string $lang the language
 *
 * @return string sanitized slug
 */
function qtranxf_slugs_sanitize_post_slug( string $slug, WP_Post $post, string $lang ): string {
    $post_title = trim( qtranxf_use( $lang, $post->post_title ) );
    $post_name  = get_post_meta( $post->ID, QTX_SLUGS_META_PREFIX . $lang, true );
    if ( ! $post_name ) {
        $post_name = $post->post_name;
    }

    //TODO: if has a slug, test and use it
    //TODO: and then replace the default slug with the default language slug
    $name = ( $post_title === '' ) ? $post_name : $post_title;
    $slug = trim( $slug );
    $slug = ( $slug === '' ) ? sanitize_title( $name ) : sanitize_title( $slug );

    return htmlspecialchars( $slug, ENT_QUOTES );
}

/**
 * Validates post slug against repetitions per language
 *
 * @param string $slug the slug name
 * @param WP_Post $post the post object
 * @param string $lang the language
 *
 * @return string the slug validated
 */
function qtranxf_slugs_unique_post_slug( string $slug, WP_Post $post, string $lang ): string {

    $original_status = $post->post_status;

    if ( in_array( $post->post_status, array( 'draft', 'pending' ) ) ) {
        $post->post_status = 'publish';
    }

    $slug = qtranxf_slugs_wp_unique_post_slug( $slug, $post->ID, $post->post_status, $post->post_type, $post->post_parent, $lang );

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
 * @param string $lang
 *
 * @return string unique slug for the post, based on language meta_value (with a -1, -2, etc. suffix)
 */
function qtranxf_slugs_wp_unique_post_slug( string $slug, int $post_ID, string $post_status, string $post_type, int $post_parent, string $lang ): string {
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
        $check_sql       = "SELECT $wpdb->postmeta.meta_value FROM $wpdb->posts,$wpdb->postmeta WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_key = '%s' AND $wpdb->postmeta.meta_value = '%s' AND $wpdb->posts.post_type = %s AND $wpdb->posts.ID != %d LIMIT 1";
        $post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, QTX_SLUGS_META_PREFIX . $lang, $slug, $post_type, $post_ID ) );

        // TODO: update unique_slug :: missing check for conflict with dates archive from current wp func ( 4.3.1 )
        if ( $post_name_check || in_array( $slug, $feeds ) || apply_filters( 'wp_unique_post_slug_is_bad_flat_slug', false, $slug, $post_type ) ) {
            $suffix = 2;
            do {
                // TODO: update unique_slug :: same as above: differs from current wp func ( 4.3.1 )
                $alt_post_name   = substr( $slug, 0, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
                $post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, QTX_SLUGS_META_PREFIX . $lang, $alt_post_name, $post_type, $post_ID ) );
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
 * @param int $post_id the post id
 * @param WP_Post|null $post the post object
 *
 * @return void
 */
function qtranxf_slugs_save_postdata( int $post_id, ?WP_Post $post = null ): void {
    global $q_config;
    if ( is_null( $post ) ) {
        $post = get_post( $post_id );
    }
    $post_type_object = get_post_type_object( $post->post_type );

    if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
         || ( ! isset( $_POST['post_ID'] ) || $post_id != $_POST['post_ID'] )
         || ( isset( $_POST['qts_nonce'] ) && ! wp_verify_nonce( $_POST['qts_nonce'], 'qts_nonce' ) )
         || ( ! current_user_can( $post_type_object->cap->edit_post, $post_id ) ) ) {
        return;
    }
    foreach ( $q_config['enabled_languages'] as $lang ) {
        // check required because it is not available inside quick edit
        if ( isset( $_POST["qts_{$lang}_slug"] ) ) {
            $slug = $_POST["qts_{$lang}_slug"];
            $slug = qtranxf_slugs_sanitize_post_slug( $slug, $post, $lang );
            $slug = qtranxf_slugs_unique_post_slug( $slug, $post, $lang );

            delete_post_meta( $post_id, QTX_SLUGS_META_PREFIX . $lang );
            update_post_meta( $post_id, QTX_SLUGS_META_PREFIX . $lang, $slug );
        }
    }
}

/**
 * Sanitize a term slug.
 *
 * @param string $slug the slug name
 * @param WP_Term $term the term object
 * @param string $lang the language
 *
 * @return string sanitized slug
 */
function qtranxf_slugs_sanitize_term_slug( $slug, $term, $lang ) {
    global $q_config;

    $term_name = trim( qtranxf_use( $lang, $term->name, false, true ) );
    if ( $term_name === '' ) {
        $term_name = trim( qtranxf_use( $q_config['default_language'], $term->name ) );
    }
    $slug = trim( $slug );
    $slug = $slug === '' ? sanitize_title( $term_name ) : sanitize_title( $slug );

    return htmlspecialchars( $slug, ENT_QUOTES );
}

/**
 * Make a term slug unique for a given language.
 *
 * @param string $slug term slug to be made unique
 * @param WP_Term $term the term object the slug belongs to
 * @param string $lang language
 *
 * @return string unique slug
 *
 * @since 1.0
 */
function qtranxf_slugs_unique_term_slug( string $slug, $term, string $lang ): string {
    global $wpdb;

    $query       = $wpdb->prepare( "SELECT term_id FROM $wpdb->termmeta WHERE meta_key = '%s' AND meta_value = '%s' AND term_id != %d ", QTX_SLUGS_META_PREFIX . $lang, $slug, $term->term_id );
    $exists_slug = $wpdb->get_results( $query );

    if ( empty( $exists_slug ) ) {
        return $slug;
    }

    // If we didn't get a unique slug, try appending a number to make it unique.
    $query = $wpdb->prepare( "SELECT meta_value FROM $wpdb->termmeta WHERE meta_key = '%s' AND meta_value = '%s' AND term_id != %d", QTX_SLUGS_META_PREFIX . $lang, $slug, $term->term_id );

    if ( $wpdb->get_var( $query ) ) {
        $num = 2;
        do {
            $alt_slug = $slug . "-$num";
            $num++;
            $slug_check = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT meta_value FROM $wpdb->termmeta WHERE meta_key = '%s' AND meta_value = '%s'",
                    QTX_SLUGS_META_PREFIX . $lang,
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
function qtranxf_slugs_save_term( int $term_id, int $tt_id, $taxonomy ): void {
    global $q_config;
    $cur_screen = get_current_screen();
    if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
         || ( ! current_user_can( 'edit_posts' ) )
         || ( isset( $cur_screen ) && $cur_screen->id === "nav-menus" ) //TODO: check if this condition is really needed
    ) {
        return;
    }

    $term = get_term( $term_id, $taxonomy );
    foreach ( $q_config['enabled_languages'] as $lang ) {
        // condition is needed in case term is added through ajax e.g. in post edit page
        $slug = $_POST["qts_{$lang}_slug"] ?? '';
        $slug = qtranxf_slugs_sanitize_term_slug( $slug, $term, $lang );
        $slug = qtranxf_slugs_unique_term_slug( $slug, $term, $lang );

        delete_metadata( 'term', $term_id, QTX_SLUGS_META_PREFIX . $lang );
        update_metadata( 'term', $term_id, QTX_SLUGS_META_PREFIX . $lang, $slug );
    }
}

/**
 * Display a list of multiple input fields, one per language for add/edit term.
 *
 * @param WP_Term|null $term If a term object is given, the values are retrieved from meta, otherwise left empty.
 *
 * @return void
 */
function qtranxf_slugs_show_list_term_fields( $term ): void {
    global $q_config;

    $flag_location = qtranxf_flag_location(); ?>
    <ul class="qtranxs-slugs-list qtranxs-slugs-terms"><?php
        foreach ( $q_config['enabled_languages'] as $lang ) {
            $slug  = is_object( $term ) ? get_metadata( 'term', $term->term_id, QTX_SLUGS_META_PREFIX . $lang, true ) : '';
            $value = $slug ? htmlspecialchars( $slug, ENT_QUOTES ) : '';
            $flag  = $q_config['flag'][ $lang ];
            $name  = $q_config['language_name'][ $lang ];
            $title = sprintf( __( 'Slug' ) . ' (%s)', $name );
            echo "<li><img class='qtranxs-lang-flag' src='{$flag_location}{$flag}' alt='$name' title='$name' />" . PHP_EOL;
            echo "<input type='text' name='qts_{$lang}_slug' value='" . urldecode( $value ) . "' title='$title' /></li>" . PHP_EOL;
        } ?>
    </ul>
    <?php
}

/**
 * Display multiple input fields, one per language for add term page.
 *
 */
function qtranxf_slugs_show_add_term_fields(): void {
    ?>
    <div class="form-field term-slug-wrap">
        <label><?php _e( 'Slugs per language', 'qtranslate' ) ?></label>
        <?php qtranxf_slugs_show_list_term_fields( null ); ?>
    </div>
    <?php
}

/**
 * Display multiple input fields, one per language for edit term page.
 *
 * @param WP_Term $term the term object
 */
function qtranxf_slugs_show_edit_term_fields( $term ): void {
    ?>
    <tr class="form-field term-slug-wrap">
        <th><?php _e( 'Slugs per language', 'qtranslate' ) ?></th>
        <td><?php qtranxf_slugs_show_list_term_fields( $term ); ?></td>
    </tr>
    <?php
}

/**
 * Display link to slugs settings for add custom tax admin page (e.g. WooCommerce product attributes).
 *
 */
function qtranxf_slugs_show_add_taxonomy_slugs_option_link(): void {
    ?>
    <div class="form-field term-slug-wrap">
        <label><?php _e( 'Slugs per language', 'qtranslate' ) ?></label>
        <?php
        //TODO: link destination should not be hardcoded here, but currently $options_uri property is private in QTX_Admin_Settings class (base options page) and module id is hardcoded independently from module definitions in QTX_Admin_Module class (module href).
        echo sprintf( "<p>" . __( 'Multilanguage slugs can be set up in <a href="%s">slugs module settings</a> once the new item is added.', 'qtranslate' ) . "</p>", admin_url( 'options-general.php?page=qtranslate-xt#slugs' ) );
        ?>
    </div>
    <?php
}

/**
 * Display link to slugs settings for edit custom tax admin page (e.g. WooCommerce product attributes).
 *
 */
function qtranxf_slugs_show_edit_taxonomy_slugs_option_link(): void {
    ?>
    <tr class="form-field term-slug-wrap">
        <th><?php _e( 'Slugs per language', 'qtranslate' ) ?></th>
        <td>
            <?php
            //TODO: link destination should not be hardcoded here, but currently $options_uri property is private in QTX_Admin_Settings class (base options page) and module id is hardcoded independently from module definitions in QTX_Admin_Module class (module href).
            echo sprintf( "<p>" . __( 'Multilanguage slugs can be set up in <a href="%s">slugs module settings</a>', 'qtranslate' ) . "</p>", admin_url( 'options-general.php?page=qtranslate-xt#slugs' ) );
            ?>
        </td>
    </tr>
    <?php
}

/**
 * Hide automatically the wordpress slug box in edit terms page.
 */
function qtranxf_slugs_hide_term_slug_box(): void {
    global $pagenow;
    switch ( $pagenow ):
        case 'edit-tags.php':
            $id = 'tag-slug';
            break;
        case 'term.php':
            $id = 'slug';
            break;
        case 'edit.php':
            // Handle WooCommerce edit product attributes page.
            if ( isset( $_GET['page'] ) && $_GET['page'] == 'product_attributes' ) {
                // Hide the regular slug input field.
                $id = 'attribute_name';
                if ( isset( $_GET['edit'] ) ) {
                    // Hide the slug header left of the input field.
                    $additional_jquery =
                        "$(\"#" . $id . "\").parent().prev(\"th\").hide()" . PHP_EOL;
                } else {
                    // Hide the slug column in the table.
                    // TODO: actual slug column to be added (javascript seems the only way currently). For the time being, possibly overridden slugs column is hidden.
                    $additional_jquery =
                        "$('table tr th:nth-child(2)').hide()" . PHP_EOL .
                        "$('table tr td:nth-child(2)').hide()" . PHP_EOL;
                }
            }
            break;
        default:
            return;
    endswitch;

    if ( ! isset( $id ) ) {
        return;
    }
    echo "<!-- QTS remove slug box -->" . PHP_EOL;
    echo "<script>" . PHP_EOL;
    echo "  jQuery(document).ready(function($){" . PHP_EOL;
    echo "      $(\"#" . $id . "\").parent().hide();" . PHP_EOL;
    echo "      $(\".form-field td #slug\").parent().parent().hide();" . PHP_EOL;
    if ( isset( $additional_jquery ) ) {
        echo $additional_jquery;
    }
    echo "  });" . PHP_EOL;
    echo "</script>" . PHP_EOL;
}

/**
 * Hide quickedit slug.
 */
function qtranxf_slugs_hide_quick_edit(): void {
    echo "<!-- QTS remove quick edit box -->" . PHP_EOL;
    echo "<style media=\"screen\">" . PHP_EOL;
    echo "  .inline-edit-row fieldset.inline-edit-col-left .inline-edit-col *:first-child + label { display: none !important }" . PHP_EOL;
    echo "</style>" . PHP_EOL;
}

function qtranxf_slugs_taxonomy_columns( array $columns ): array {
    unset( $columns['slug'] );
    $columns['qts-slug'] = __( 'Slug' );

    return $columns;
}

function qtranxf_slugs_taxonomy_custom_column( $str, string $column_name, int $term_id ): bool {
    global $q_config;

    if ( $column_name === 'qts-slug' ) {
        echo get_metadata( 'term', $term_id, QTX_SLUGS_META_PREFIX . $q_config['language'], true );
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
 * @param (array) $args
 */
function qtranxf_slugs_get_object_terms( array $terms, $obj_id, $taxonomy, array $args ): array {

    global $pagenow;
    global $q_config;

    // Although in post edit page the tags are translated,
    // but when saving/updating the post Wordpress considers
    // the translated tags as new tags. Due to this
    // issue I limit this 'hack' to the post manage
    // page only.
    if ( $pagenow == 'edit.php' ) {
        $meta = get_option( 'qtranslate_term_name' );

        if ( ! empty( $terms ) ) {
            foreach ( $terms as $term ) {
                if ( isset( $term->name ) && isset( $meta[ $term->name ][ $q_config['language'] ] ) ) {
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
function qtranxf_slugs_get_terms( array $terms, $taxonomy ): array {

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

function qtranxf_slugs_ma_module_updated(): void {
    if ( QTX_Module_Loader::is_module_active( 'slugs' ) ) {
        qtranxf_slugs_multi_activate();
    } else {
        qtranxf_slugs_deactivate();

    }
}
