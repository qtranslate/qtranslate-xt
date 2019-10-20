<?php

class acf_qtranslate_acf_5_wysiwyg extends acf_field_wysiwyg {

    /**
     * The plugin instance
     * @var acf_qtranslate_plugin
     */
    protected $plugin;

    /**
     * Constructor
     *
     * @param acf_qtranslate_plugin $plugin
     */
    function __construct( $plugin ) {
        $this->plugin = $plugin;

        if ( version_compare( $plugin->acf_version(), '5.6.0' ) < 0 ) {
            $this->initialize();
        }

        acf_field::__construct();
    }

    /**
     * Setup the field type data
     */
    function initialize() {
        $this->name     = 'qtranslate_wysiwyg';
        $this->label    = __( "Wysiwyg Editor (qTranslate)", 'acf' );
        $this->category = __( "qTranslate", 'acf' );
        $this->defaults = array(
            'tabs'          => 'all',
            'toolbar'       => 'full',
            'media_upload'  => 1,
            'default_value' => '',
            'delay'         => 0
        );

        if ( method_exists( $this, 'add_filters' ) ) {
            $this->add_filters();
        }
    }

    /**
     * Hook/override ACF render_field to create the HTML interface
     *
     * @param array $field
     */
    function render_field( $field ) {
        global $wp_version;

        acf_enqueue_uploader();

        $default_editor = 'html';
        $show_tabs      = true;

        // minimum height is 300
        $height = acf_get_user_setting( 'wysiwyg_height', 300 );
        $height = max( $height, 300 );

        // detect mode
        if ( ! user_can_richedit() ) {
            $show_tabs = false;
        } elseif ( $field['tabs'] == 'visual' ) {
            // visual tab only
            $default_editor = 'tinymce';
            $show_tabs      = false;
        } elseif ( $field['tabs'] == 'text' ) {
            // text tab only
            $show_tabs = false;
        } elseif ( wp_default_editor() == 'tinymce' ) {
            // both tabs
            $default_editor = 'tinymce';
        }

        // must be logged in tp upload
        if ( ! current_user_can( 'upload_files' ) ) {
            $field['media_upload'] = 0;
        }

        // set mode
        $switch_class = ( $default_editor === 'html' ) ? 'html-active' : 'tmce-active';

        // filter value for editor
        remove_filter( 'acf_the_editor_content', 'format_for_editor', 10 );
        remove_filter( 'acf_the_editor_content', 'wp_htmledit_pre', 10 );
        remove_filter( 'acf_the_editor_content', 'wp_richedit_pre', 10 );

        if ( version_compare( $wp_version, '4.3', '>=' ) ) {
            add_filter( 'acf_the_editor_content', 'format_for_editor', 10, 2 );
        } else {
            $function = ( $default_editor === 'html' ) ? 'wp_htmledit_pre' : 'wp_richedit_pre';
            add_filter( 'acf_the_editor_content', $function, 10, 1 );
        }

        global $q_config;

        $languages       = qtranxf_getSortedLanguages( true );
        $values          = $this->plugin->decode_language_values( $field['value'] );
        $currentLanguage = $this->plugin->get_active_language();

        echo '<div class="multi-language-field multi-language-field-wysiwyg">';

        foreach ( $languages as $language ) {
            $class = ( $language === $currentLanguage ) ? 'wp-switch-editor current-language' : 'wp-switch-editor';
            echo '<a class="' . $class . '" data-language="' . $language . '">' . $q_config['language_name'][ $language ] . '</a>';
        }

        $uid       = uniqid( 'acf-editor-' );
        foreach ( $languages as $language ):

            $id = $uid . "-$language";
            $name  = $field['name'] . "[$language]";
            $class = $switch_class;
            if ( $language === $currentLanguage ) {
                $class .= ' current-language';
            }

            if ( version_compare( $wp_version, '4.3', '>=' ) ) {
                $button = 'data-wp-editor-id="' . $id . '"';
            } else {
                $button = 'onclick="switchEditors.switchto(this);"';
            }

            $value = apply_filters( 'acf_the_editor_content', $values[ $language ], $default_editor );

            ?>
            <div id="wp-<?php echo $id; ?>-wrap" class="acf-editor-wrap wp-core-ui wp-editor-wrap <?php echo $class; ?>"
                 data-toolbar="<?php echo $field['toolbar']; ?>" data-upload="<?php echo $field['media_upload']; ?>"
                 data-language="<?php echo $language; ?>">
                <div id="wp-<?php echo $id; ?>-editor-tools" class="wp-editor-tools hide-if-no-js">
                    <?php if ( $field['media_upload'] ): ?>
                        <div id="wp-<?php echo $id; ?>-media-buttons" class="wp-media-buttons">
                            <?php do_action( 'media_buttons' ); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ( user_can_richedit() && $show_tabs ): ?>
                        <div class="wp-editor-tabs">
                            <button id="<?php echo $id; ?>-tmce"
                                    class="wp-switch-editor switch-tmce" <?php echo $button; ?>
                                    type="button"><?php echo __( 'Visual', 'acf' ); ?></button>
                            <button id="<?php echo $id; ?>-html"
                                    class="wp-switch-editor switch-html" <?php echo $button; ?>
                                    type="button"><?php echo _x( 'Text', 'Name for the Text editor tab (formerly HTML)', 'acf' ); ?></button>
                        </div>
                    <?php endif; ?>
                </div>
                <div id="wp-<?php echo $id; ?>-editor-container" class="wp-editor-container">
                    <textarea id="<?php echo $id; ?>" class="qtx-wp-editor-area" name="<?php echo $name; ?>"
                              <?php if ( $height ): ?>style="height:<?php echo $height; ?>px;"<?php endif; ?>><?php echo $value; ?></textarea>
                </div>
            </div>

        <?php endforeach;

        echo '</div>';
    }

    /**
     * Hook/override ACF update_value
     *
     * @param array $values - the values to save in database
     * @param int $post_id - the post_id of which the value will be saved
     * @param array $field - the field array holding all the field options
     *
     * @return    string - the modified value
     */
    function update_value( $values, $post_id, $field ) {
        return $this->plugin->encode_language_values( $values );
    }

    /**
     *  Hook/override ACF validation to handle the value formatted to a multi-lang array instead of string
     *
     * @param bool|string $valid
     * @param array $value containing values per language
     * @param string $field
     * @param string $input
     *
     * @return bool|string
     * @see acf_validation::acf_validate_value
     */
    function validate_value( $valid, $value, $field, $input ) {
        if ( is_array( $value ) ) {
            $valid = $this->plugin->validate_language_values( $this, $valid, $value, $field, $input );
        }

        return $valid;
    }
}
