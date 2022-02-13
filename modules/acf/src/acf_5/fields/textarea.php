<?php

class acf_qtranslate_acf_5_textarea extends acf_field_textarea {

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
        $this->name     = 'qtranslate_textarea';
        $this->label    = __( "Textarea", 'acf' ) . " (qTranslate-XT)";
        $this->category = "qTranslate-XT";
        $this->defaults = array(
            'default_value' => '',
            'new_lines'     => '',
            'maxlength'     => '',
            'placeholder'   => '',
            'rows'          => ''
        );
    }

    /**
     * Hook/override ACF render_field to create the HTML interface
     *
     * @param array $field
     */
    function render_field( $field ) {
        global $q_config;
        $languages       = qtranxf_getSortedLanguages( true );
        $values          = $this->plugin->decode_language_values( $field['value'] );
        $currentLanguage = $this->plugin->get_active_language();

        if ( empty( $field['rows'] ) ) {
            $field['rows'] = 8;
        }

        $atts = array();

        $keys = array( 'id', 'class', 'name', 'placeholder', 'rows' );
        if ( $field['maxlength'] !== '' ) {
            $keys[] = 'maxlength';
        }
        foreach ( $keys as $k ) {
            $atts[ $k ] = $field[ $k ];
        }

        $special_keys = array( 'readonly', 'disabled' );
        foreach ( $special_keys as $k ) {
            if ( isset( $field[ $k ] ) && $field[ $k ] ) {
                $atts[ $k ] = $k;
            }
        }

        echo '<div class="acf-input-wrap multi-language-field">';

        foreach ( $languages as $language ) {
            $class = ( $language === $currentLanguage ) ? 'wp-switch-editor current-language' : 'wp-switch-editor';
            echo '<a class="' . $class . '" data-language="' . $language . '">' . $q_config['language_name'][ $language ] . '</a>';
        }

        foreach ( $languages as $language ) {
            $atts['class'] = $field['class'];
            if ( $language === $currentLanguage ) {
                $atts['class'] .= ' current-language';
            }
            $atts['name']          = $field['name'] . "[$language]";
            $atts['data-language'] = $language;
            echo '<textarea ' . acf_esc_attrs( $atts ) . ' >';
            echo esc_textarea( $values[ $language ] );
            echo '</textarea>';
        }

        echo '</div>';
    }

    /**
     * Hook/override ACF update_value
     *
     * @param array $values - the values to save in database
     * @param int $post_id - the post_id of which the value will be saved
     * @param array $field - the field array holding all the field options
     *
     * @return string - the modified value
     * @see acf_field_textarea::render_field
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
