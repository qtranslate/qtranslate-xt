<?php

// page settings sections & fields as well as the contextual help text.
include_once( 'qtranslate-slug-settings-options.php' );

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
            echo "<p>" . __( 'For example, the post_type <kbd>books</kbd>, in Spanish would be displayed as <code>https://example.org/es/libros/post-type-name/</code>. If you leave this blank will use the default option when you <a href="https://developer.wordpress.org/reference/functions/register_post_type/">registered</a> the post_type.', 'qts' ) . "</p>";
            break;

        case 'taxonomies':
            echo "<p>" . __( 'For example, the taxonomy <kbd>category</kbd>, in Spanish would be displayed as <code>https://example.org/es/categoria/taxonomy-name/</code>. If you leave this blank will use the default option when you <a href="https://developer.wordpress.org/reference/functions/register_taxonomy/">registered</a> the taxonomy (if you previously setup a base permastruct for <u>categories</u> or <u>tags</u> in <a href="options-permalink.php">permalinks</a> page, these bases will be overwritten by the translated ones).', 'qts' ) . "</p>";
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

    $type    = $args['type'];
    $id      = $args['id'];
    $desc    = $args['desc'];
    $std     = $args['std'];
    $choices = $args['choices'];
    $class   = $args['class'];

    $options = $qtranslate_slug->get_options();

    // pass the standard value if the option is not yet set in the database
    if ( ! isset( $options[ $id ] ) && $type != 'checkbox' ) {
        $options[ $id ] = $std;
    }

    // additional field class. output only if the class is defined in the create_setting arguments
    $field_class = ( $class != '' ) ? ' ' . $class : '';

    echo '<table class="form-table qtranxs-form-table" id="qtranxs_slugs_config"><tbody><tr">';
    echo '<th scope="row">' . $args['title'] . '</th>';
    echo '<td><div class="form-field">';

    // switch html display based on the setting type.
    switch ( $type ) {
        case 'text':
            $options[ $id ] = stripslashes( $options[ $id ] );
            $options[ $id ] = esc_attr( $options[ $id ] );
            echo "<input class='regular-text$field_class' type='text' id='$id' name='" . QTS_OPTIONS_NAME . "[$id]' value='$options[$id]' />";
            echo ( $desc != '' ) ? "<br /><p class='qtranxs-notes'>$desc</p>" : "";
            break;

        case "multi-text":
            foreach ( $choices as $item ) {
                $item    = explode( "|", $item ); // cat_name|cat_slug
                $item[0] = esc_html__( $item[0], 'qts' );

                if ( ! empty( $options[ $id ] ) ) {
                    foreach ( $options[ $id ] as $option_key => $option_val ) {
                        if ( $item[1] == $option_key ) {
                            $value = $option_val;
                        }
                    }
                } else {
                    $value = '';
                }
                echo "<label for=" . QTS_OPTIONS_NAME . "[$id|${item[1]}]>" . $item[0] . "</label> " .
                     "<input class='$field_class' type='text' id='$id|${item[1]}' name='" . QTS_OPTIONS_NAME . "[$id|${item[1]}]' value='" . urldecode( $value ) . "' /><br/>";
                //echo "<span>$item[0]:</span> <input class='$field_class' type='text' id='$id|$item[1]' name='" . QTS_OPTIONS_NAME . "[$id|$item[1]]' value='$value' /><br/>";
            }
            echo ( $desc != '' ) ? "<p class='qtranxs-notes'>$desc</p>" : "";
            break;

        case 'textarea':
            $options[ $id ] = stripslashes( $options[ $id ] );
            $options[ $id ] = esc_html( $options[ $id ] );
            echo "<textarea class='textarea$field_class' type='text' id='$id' name='" . QTS_OPTIONS_NAME . "[$id]' rows='5' cols='30'>$options[$id]</textarea>";
            echo ( $desc != '' ) ? "<br /><p class='qtranxs-notes'>$desc</p>" : "";
            break;

        case 'select':
            echo "<select id='$id' class='select$field_class' name='" . QTS_OPTIONS_NAME . "[$id]'>";
            foreach ( $choices as $item ) {
                $value = esc_attr( $item, 'qts' );
                $item  = esc_html( $item, 'qts' );

                $selected = ( $options[ $id ] == $value ) ? 'selected="selected"' : '';
                echo "<option value='$value' $selected>$item</option>";
            }
            echo "</select>";
            echo ( $desc != '' ) ? "<br /><p class='qtranxs-notes'>$desc</p>" : "";
            break;

        case 'select2':
            echo "<select id='$id' class='select$field_class' name='" . QTS_OPTIONS_NAME . "[$id]'>";
            foreach ( $choices as $item ) {

                $item    = explode( "|", $item );
                $item[0] = esc_html( $item[0], 'qts' );

                $selected = ( $options[ $id ] == $item[1] ) ? 'selected="selected"' : '';
                echo "<option value='$item[1]' $selected>$item[0]</option>";
            }
            echo "</select>";
            echo ( $desc != '' ) ? "<br /><p class='qtranxs-notes'>$desc</p>" : "";
            break;

        case 'checkbox':
            echo "<input class='checkbox$field_class' type='checkbox' id='$id' name='" . QTS_OPTIONS_NAME . "[$id]' value='1' " . checked( $options[ $id ], 1, false ) . " />";
            echo ( $desc != '' ) ? "<br /><p class='qtranxs-notes'>$desc</p>" : "";
            break;

        case "multi-checkbox":
            foreach ( $choices as $item ) {

                $item    = explode( "|", $item );
                $item[0] = esc_html( $item[0], 'qts' );

                $checked = '';

                if ( isset( $options[ $id ][ $item[1] ] ) ) {
                    if ( $options[ $id ][ $item[1] ] == 'true' ) {
                        $checked = 'checked="checked"';
                    }
                }

                echo "<input class='checkbox$field_class' type='checkbox' id='$id|$item[1]' name='" . QTS_OPTIONS_NAME . "[$id|$item[1]]' value='1' $checked /> $item[0] <br/>";
            }
            echo ( $desc != '' ) ? "<br /><p class='qtranxs-notes'>$desc</p>" : "";
            break;

        case "multi-radio":
            foreach ( $choices as $index => $item ) {

                $item       = explode( "|", $item );
                $item_key   = ( count( $item ) > 1 ) ? esc_html( $item[0], 'qts' ) : esc_html( end( $item ), 'qts' );
                $item_value = ( count( $item ) > 1 ) ? esc_html( $item[1], 'qts' ) : esc_html( end( $item ), 'qts' );

                $checked = '';

                if ( isset( $options[ $id ] ) && $options[ $id ] === $item_value ) {
                    $checked = 'checked="checked"';
                }

                echo "<label for='$id|$item_value'><input class='radio$field_class' type='radio' id='$id|$item_value' name='" . QTS_OPTIONS_NAME . "[$id]' value='$item_value' $checked /> <strong>$item_key</strong>";
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
    <p class="heading"><?php _e( 'If you activated previously the <a href="options-permalink.php">pretty permalinks</a>, in this section you can translate the <abbr title="en inglés, Universal Resource Locator">URLs</abbr> <strong>bases</strong> for <a href="https://developer.wordpress.org/reference/functions/register_post_type/#parameters">public</a> post_types, categories, tags and taxonomies.', 'qts' ); ?> </p>

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
    flush_rewrite_rules();
}

add_action( 'qtranslate_configuration', 'qts_show_settings_page' );

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
    // TODO: errors are not displayed, earlier hook to be evaluated
    $errors      = &$q_config['url_info']['errors'];
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
                        // register error
                        if ( is_numeric( $input[ $option['id'] ] ) == false ) {
                            $errors[] = $option['id'] . ': ' . __( 'Expecting a Numeric value! Please fix.', 'qts' );
                        }
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
                foreach ( $option['choices'] as $k => $v ) {
                    $pieces        = explode( "|", $v );
                    $text_values[] = $pieces[1];
                }

                foreach ( $text_values as $v ) {
                    // Check that the option isn't empty
                    if ( ! empty( $input[ $option['id'] . '|' . $v ] ) ) {
                        // If it's not null, make sure it's sanitized, add it to an array
                        switch ( $option['class'] ) {
                            // different sanitation actions based on the class create you own cases as you need them
                            case 'numeric':
                                // accept the input only if is numberic!
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
                foreach ( $option['choices'] as $k => $v ) {
                    $pieces         = explode( "|", $v );
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
            default:
                $errors[] = $option['id'] . ': ' . __( 'Unknown field type.', 'qts' );
        endswitch;
    endforeach;

    return $valid_input;
}

function qts_update_settings() {
    global $qtranslate_slug;
    $qts_settings = false;
    if ( isset( $_POST[ QTS_OPTIONS_NAME ] ) ) {
        $qts_settings = qts_validate_options( $_POST[ QTS_OPTIONS_NAME ] );
    }
    $qtranslate_slug->save_options( $qts_settings );
}

add_action( 'qtranslate_update_settings', 'qts_update_settings' );
