<?php

add_action( 'qtranslate_update_settings', 'qts_update_settings' );
add_action( 'qtranslate_configuration', 'qts_show_settings_page' );

/**
 * Helper function for defining variables for the current page.
 *
 * @return array
 */
function qts_get_settings() {
    $output                      = array();
    $output['qts_page_sections'] = qts_options_page_sections();
    $output['qts_page_fields']   = qts_options_page_fields();

    return $output;
}

/**
 * Section HTML, displayed before the first option.
 *
 * @return void echoes output
 */
function qts_section_fn( $section_id = '' ) {
    switch ( $section_id ) {
        case 'post_types':
            echo "<p>" . __( 'For example, the post_type <kbd>books</kbd>, in Spanish would be displayed as <code>https://example.org/es/libros/post-type-name/</code>. If you leave this blank will use the default option when you <a href="https://developer.wordpress.org/reference/functions/register_post_type/">registered</a> the post_type.', 'qtranslate' ) . "</p>";
            break;

        case 'taxonomies':
            echo "<p>" . __( 'For example, the taxonomy <kbd>category</kbd>, in Spanish would be displayed as <code>https://example.org/es/categoria/taxonomy-name/</code>. If you leave this blank will use the default option when you <a href="https://developer.wordpress.org/reference/functions/register_taxonomy/">registered</a> the taxonomy (if you previously setup a base permastruct for <u>categories</u> or <u>tags</u> in <a href="options-permalink.php">permalinks</a> page, these bases will be overwritten by the translated ones).', 'qtranslate' ) . "</p>";
            break;
    }
}

/**
 * Form Fields HTML: all form field types share the same function.
 *
 * @return void echoes output
 */
function qts_show_form_field( $args = array() ) {
    global $qtranslate_slug;
    global $q_config;

    $type    = $args['type'];
    $id      = $args['id'];
    $desc    = $args['desc'];
    $std     = $args['std'];
    $choices = $args['choices'];
    $class   = $args['class'];

    $options = $qtranslate_slug->options_buffer ?: get_option( QTX_OPTIONS_MODULE_SLUGS, array() );

    // pass the standard value if the option is not yet set in the database
    if ( ! isset( $options[ $id ] ) && $type != 'checkbox' ) {
        $options[ $id ] = $std;
    }

    // additional field class. output only if the class is defined in the create_setting arguments
    $field_class = ( $class != '' ) ? ' ' . $class : '';

    echo '<table class="form-table qtranxs-form-table" id="' . $id . '"><tbody><tr">';
    echo '<th scope="row">' . $args['title'] . '</th>';
    echo '<td><div class="form-field">';

    // switch html display based on the setting type.
    switch ( $type ) {
        case 'text':
            $options[ $id ] = stripslashes( $options[ $id ] );
            $options[ $id ] = esc_attr( $options[ $id ] );
            echo "<input class='regular-text$field_class' type='text' id='$id' name='" . QTX_OPTIONS_MODULE_SLUGS . "[$id]' value='$options[$id]' />";
            echo ( $desc != '' ) ? "<br /><p class='qtranxs-notes'>$desc</p>" : "";
            break;

        case "multi-text":
            $flag_location = qtranxf_flag_location();
            echo "<ul class='qtranxs-slugs-list'>";
            foreach ( $choices as $item ) {
                $item    = explode( "|", $item ); // label|slug
                $item[0] = esc_html( $item[0] );

                $value = '';
                if ( ! empty( $options[ $id ] ) ) {
                    foreach ( $options[ $id ] as $option_key => $option_val ) {
                        if ( $item[1] == $option_key ) {
                            $value = $option_val;
                        }
                    }
                }
                // Assume the slug is a language (to be clarified in the given choices).
                $lang    = $item[1];
                $flag    = $q_config['flag'][ $lang ];
                $name    = $q_config['language_name'][ $lang ];
                $item_id = "$id|${item[1]}";
                echo "<li><img class='qtranxs-lang-flag' src='${flag_location}${flag}' alt='$name' title='$name' />" . PHP_EOL;
                echo "<input class='$field_class' type='text' id='$item_id' name='" . QTX_OPTIONS_MODULE_SLUGS . "[$item_id]' value='" . urldecode( $value ) . "' title='{$item[0]}' /></li>" . PHP_EOL;
            }
            echo "</ul>";
            echo ( $desc != '' ) ? "<p class='qtranxs-notes'>$desc</p>" : "";
            break;

        case 'textarea':
            $options[ $id ] = stripslashes( $options[ $id ] );
            $options[ $id ] = esc_html( $options[ $id ] );
            echo "<textarea class='textarea$field_class' type='text' id='$id' name='" . QTX_OPTIONS_MODULE_SLUGS . "[$id]' rows='5' cols='30'>$options[$id]</textarea>";
            echo ( $desc != '' ) ? "<br /><p class='qtranxs-notes'>$desc</p>" : "";
            break;

        case 'select':
            echo "<select id='$id' class='select$field_class' name='" . QTX_OPTIONS_MODULE_SLUGS . "[$id]'>";
            foreach ( $choices as $item ) {
                $value = esc_attr( $item );
                $item  = esc_html( $item );

                $selected = ( $options[ $id ] == $value ) ? 'selected="selected"' : '';
                echo "<option value='$value' $selected>$item</option>";
            }
            echo "</select>";
            echo ( $desc != '' ) ? "<br /><p class='qtranxs-notes'>$desc</p>" : "";
            break;

        case 'select2':
            echo "<select id='$id' class='select$field_class' name='" . QTX_OPTIONS_MODULE_SLUGS . "[$id]'>";
            foreach ( $choices as $item ) {

                $item    = explode( "|", $item );
                $item[0] = esc_html( $item[0] );

                $selected = ( $options[ $id ] == $item[1] ) ? 'selected="selected"' : '';
                echo "<option value='$item[1]' $selected>$item[0]</option>";
            }
            echo "</select>";
            echo ( $desc != '' ) ? "<br /><p class='qtranxs-notes'>$desc</p>" : "";
            break;

        case 'checkbox':
            echo "<input class='checkbox$field_class' type='checkbox' id='$id' name='" . QTX_OPTIONS_MODULE_SLUGS . "[$id]' value='1' " . checked( $options[ $id ], 1, false ) . " />";
            echo ( $desc != '' ) ? "<br /><p class='qtranxs-notes'>$desc</p>" : "";
            break;

        case "multi-checkbox":
            foreach ( $choices as $item ) {

                $item    = explode( "|", $item );
                $item[0] = esc_html( $item[0] );

                $checked = '';

                if ( isset( $options[ $id ][ $item[1] ] ) ) {
                    if ( $options[ $id ][ $item[1] ] == 'true' ) {
                        $checked = 'checked="checked"';
                    }
                }

                echo "<input class='checkbox$field_class' type='checkbox' id='$id|$item[1]' name='" . QTX_OPTIONS_MODULE_SLUGS . "[$id|$item[1]]' value='1' $checked /> $item[0] <br/>";
            }
            echo ( $desc != '' ) ? "<br /><p class='qtranxs-notes'>$desc</p>" : "";
            break;

        case "multi-radio":
            foreach ( $choices as $index => $item ) {

                $item       = explode( "|", $item );
                $item_key   = ( count( $item ) > 1 ) ? esc_html( $item[0] ) : esc_html( end( $item ) );
                $item_value = ( count( $item ) > 1 ) ? esc_html( $item[1] ) : esc_html( end( $item ) );

                $checked = '';

                if ( isset( $options[ $id ] ) && $options[ $id ] === $item_value ) {
                    $checked = 'checked="checked"';
                }

                echo "<label for='$id|$item_value'><input class='radio$field_class' type='radio' id='$id|$item_value' name='" . QTX_OPTIONS_MODULE_SLUGS . "[$id]' value='$item_value' $checked /> <strong>$item_key</strong>";
                if ( isset( $desc[ $index ] ) && ! empty( $desc[ $index ] ) ) {
                    echo ": " . $desc[ $index ];
                }
                echo "</label>";
            }
            echo ( ! is_array( $desc ) && $desc != '' ) ? "<br /><p class='qtranxs-notes'>$desc</p>" : "";
            break;
    }

    echo '</div></td></tr></tbody></table>';

}

/**
 * Admin Settings Page HTML.
 *
 * @return void echoes output
 */
function qts_show_settings_page() {
    $settings_output = qts_get_settings();

    if ( empty( $settings_output['qts_page_sections'] ) ) {
        return;
    }
    QTX_Admin_Settings::open_section( 'slugs' );
    ?>
    <p class="heading"><?php _e( 'If you activated previously the <a href="options-permalink.php">pretty permalinks</a>, in this section you can translate the <abbr title="en inglés, Universal Resource Locator">URLs</abbr> <strong>bases</strong> for <a href="https://developer.wordpress.org/reference/functions/register_post_type/#parameters">public</a> post_types, categories, tags and taxonomies.', 'qtranslate' ); ?> </p>

    <?php foreach ( $settings_output['qts_page_sections'] as $id => $title ) { ?>
        <h2><?php echo $title; ?></h2>
        <?php qts_section_fn( $id );
        // call the "add_settings_field" for each!
        foreach ( $settings_output['qts_page_fields'] as $option ) {
            if ( $option['section'] == $id ) {
                qts_show_form_field( $option );
            }
        }
    }
    QTX_Admin_Settings::close_section( 'slugs' );
}

/**
 * Validate input.
 *
 * @return array
 */
function qts_validate_options( $input ) {
    global $q_config;
    // Initialize lookup array to be used to make sure slug for a specific language is unique
    $slugs_lookup_array = array();
    foreach ( $q_config['enabled_languages'] as $lang ) {
        $slugs_lookup_array[ $lang ] = array();
    }

    $valid_input = array();

    // collect only the values we expect and fill the new $valid_input array
    // i.e. whitelist our option IDs

    // get the settings sections array
    $settings_output = qts_get_settings();
    $options         = $settings_output['qts_page_fields'];

    // run a foreach and switch on option type
    foreach ( $options as $option ):
        switch ( $option['type'] ):
            case 'text':
                // switch validation based on the class!
                switch ( $option['class'] ) {
                    case 'numeric':
                        // accept the input only when numeric!
                        $input[ $option['id'] ]       = trim( $input[ $option['id'] ] ); // trim whitespace
                        $valid_input[ $option['id'] ] = ( is_numeric( $input[ $option['id'] ] ) ) ? $input[ $option['id'] ] : 'Expecting a Numeric value!';
                        break;
                    default:
                        // accept the input only after stripping out all html, extra white space etc!
                        $input[ $option['id'] ]       = sanitize_text_field( $input[ $option['id'] ] ); // need to add slashes still before sending to the database
                        $valid_input[ $option['id'] ] = addslashes( $input[ $option['id'] ] );
                        break;
                }
                break;

            case "multi-text":
                // this will hold the text values as an array of 'key' => 'value'
                unset( $textarray );

                $text_values = array();
                foreach ( $option['choices'] as $value ) {
                    $pieces        = explode( "|", $value );
                    $text_values[] = $pieces[1];
                }

                foreach ( $text_values as $v ) {
                    // Check that the option isn't empty
                    if ( ! empty( $input[ $option['id'] . '|' . $v ] ) ) {
                        // If it's not null, make sure it's sanitized, add it to an array
                        switch ( $option['class'] ) {
                            // different sanitation actions based on the class create you own cases as you need them
                            case 'numeric':
                                // accept the input only if is numeric!
                                $input[ $option['id'] . '|' . $v ] = trim( $input[ $option['id'] . '|' . $v ] ); // trim whitespace
                                $input[ $option['id'] . '|' . $v ] = ( is_numeric( $input[ $option['id'] . '|' . $v ] ) ) ? $input[ $option['id'] . '|' . $v ] : '';
                                break;
                            case 'qts-slug':
                                // strip all html tags and white-space.
                                // allows slug1/slug2 structure
                                $exploded_slugs = explode( '/', $input[ $option['id'] . '|' . $v ] );
                                $clean_slugs    = array();
                                foreach ( $exploded_slugs as $exploded_slug ) {
                                    $clean_slug = sanitize_title( sanitize_text_field( $exploded_slug ) );
                                    if ( ! empty( $clean_slug ) ) {
                                        $clean_slugs[] = $clean_slug;
                                    }
                                }
                                $new_slug = addslashes( implode( "/", $clean_slugs ) );
                                // avoid duplicate slugs per language
                                while ( in_array( $new_slug, $slugs_lookup_array[ $v ] ) ) {
                                    $new_slug = "$new_slug-2";
                                }
                                if ( ! empty( $new_slug ) ) {
                                    $slugs_lookup_array[ $v ][] = $new_slug;
                                }
                                $input[ $option['id'] . '|' . $v ] = $new_slug;
                                break;
                            default:
                                // strip all html tags and white-space.
                                $input[ $option['id'] . '|' . $v ] = sanitize_text_field( $input[ $option['id'] . '|' . $v ] ); // need to add slashes still before sending to the database
                                $input[ $option['id'] . '|' . $v ] = addslashes( $input[ $option['id'] . '|' . $v ] );
                                break;
                        }
                        // pass the sanitized user input to our $textarray array
                        $textarray[ $v ] = $input[ $option['id'] . '|' . $v ];

                    } else {
                        $textarray[ $v ] = '';
                    }
                }
                // pass the non-empty $textarray to our $valid_input array
                if ( ! empty( $textarray ) ) {
                    $valid_input[ $option['id'] ] = $textarray;
                }
                break;
            case 'select':
                // check to see if the selected value is in our approved array of values!
                $valid_input[ $option['id'] ] = ( in_array( $input[ $option['id'] ], $option['choices'] ) ? $input[ $option['id'] ] : '' );
                break;
            case 'checkbox':
                // if it's not set, default to null!
                if ( ! isset( $input[ $option['id'] ] ) ) {
                    $input[ $option['id'] ] = null;
                }
                // Our checkbox value is either 0 or 1
                $valid_input[ $option['id'] ] = ( $input[ $option['id'] ] == 1 ? 1 : 0 );
                break;
            case 'multi-checkbox':
                unset( $checkboxarray );
                $check_values = array();
                foreach ( $option['choices'] as $value ) {
                    $pieces         = explode( "|", $value );
                    $check_values[] = $pieces[1];
                }
                foreach ( $check_values as $v ) {
                    // Check that the option isn't null
                    if ( ! empty( $input[ $option['id'] . '|' . $v ] ) ) {
                        // If it's not null, make sure it's true, add it to an array
                        $checkboxarray[ $v ] = 'true';
                    } else {
                        $checkboxarray[ $v ] = 'false';
                    }
                }
                // Take all the items that were checked, and set them as the main option
                if ( ! empty( $checkboxarray ) ) {
                    $valid_input[ $option['id'] ] = $checkboxarray;
                }
                break;
        endswitch;
    endforeach;

    return $valid_input;
}

function qts_update_settings() {
    global $qtranslate_slug;

    $qts_settings = isset( $_POST[ QTX_OPTIONS_MODULE_SLUGS ] ) ? qts_validate_options( $_POST[ QTX_OPTIONS_MODULE_SLUGS ] ) : array();
    if ( empty( $qts_settings ) ) {
        return;
    }
    if ( $qtranslate_slug->options_buffer == $qts_settings ) {
        return;
    }
    update_option( QTX_OPTIONS_MODULE_SLUGS, $qts_settings, false );
    $qtranslate_slug->options_buffer = $qts_settings;
    flush_rewrite_rules();
}

/**
 * Define our settings sections.
 *
 * @return array key=$id, array value=$title in: add_settings_section( $id, $title, $callback, $page );
 */
function qts_options_page_sections() {
    $sections               = array();
    $sections['post_types'] = __( 'Post types', 'qtranslate' );
    $sections['taxonomies'] = __( 'Taxonomies', 'qtranslate' );

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
        $label     = sprintf( __( 'Slug' ) . ' (%s)', $q_config['language_name'][ $lang ] );
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
    global $qtranslate_slug;
    $options = array();

    $post_types = $qtranslate_slug->get_public_post_types();
    foreach ( $post_types as $post_type ) {
        $options[] = qts_options_page_build_slug_fields( $post_type, "post_types", "post_type_" );
    }

    $taxonomies = $qtranslate_slug->get_public_taxonomies();
    foreach ( $taxonomies as $taxonomy ) {
        $options[] = qts_options_page_build_slug_fields( $taxonomy, "taxonomies", "taxonomy_" );
    }

    return array_filter( $options );
}

function qts_options_page_build_slug_fields( $object, $target_section, $id_prefix ) {
    if ( is_array( $object->rewrite ) && array_key_exists( 'slug', $object->rewrite ) ) {
        $slug = ltrim( $object->rewrite['slug'], "/" );
    } else {
        $slug = $object->name;
    }

    return array(
        "section" => $target_section,
        "id"      => QTS_PREFIX . $id_prefix . $object->name,
        "title"   => qtranxf_use( qtranxf_getLanguage(), $object->label ),
        "desc"    => sprintf( '<code>https://example.org/<u>%s</u>/some-%s/</code>', $slug, $object->name ),
        'class'   => 'qts-slug', // used in qts_validate_options. TODO: cleaner way to be considered...
        "type"    => "multi-text",
        "choices" => get_multi_txt_choices(),
        "std"     => ""
    );
}