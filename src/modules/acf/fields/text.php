<?php

class QTX_Module_Acf_Field_Text extends acf_field_text {
    /**
     * The register instance
     * @var QTX_Module_Acf_Register
     */
    protected $register;

    /**
     * Constructor
     *
     * @param QTX_Module_Acf_Register $register
     * @param bool $do_initialize true if initialize() must be called explicitly
     */
    function __construct( $register, $do_initialize ) {
        $this->register = $register;
        if ( $do_initialize ) {
            $this->initialize();
        }
        parent::__construct();
    }

    /**
     *  Setup the field type data
     */
    function initialize() {
        parent::initialize();
        $this->name     = 'qtranslate_text';
        $this->category = QTX_Module_Acf_Register::ACF_CATEGORY_QTX;
        $this->label    .= ' [' . $this->category . ']' . ' - ' . __( 'Deprecated', 'qtranslate' );
    }

    /**
     * Hook/override ACF render_field to create the HTML interface
     *
     * @param array $field
     */
    function render_field( $field ) {
        global $q_config;
        $languages       = qtranxf_getSortedLanguages( true );
        $values          = $this->register->decode_language_values( $field['value'] );
        $currentLanguage = qtranxf_getLanguage();

        $atts = array();

        $keys = array( 'type', 'id', 'class', 'name', 'value', 'placeholder' );
        if ( $field['maxlength'] !== "" ) {
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
            $atts['class'] = $field['class'] . ' qtranxs-translatable';
            if ( $language === $currentLanguage ) {
                $atts['class'] .= ' current-language';
            }
            $atts['type']          = 'text';
            $atts['name']          = $field['name'] . "[$language]";
            $atts['value']         = $values[ $language ];
            $atts['data-language'] = $language;
            echo '<input ' . acf_esc_attrs( $atts ) . ' />';
        }

        echo '</div>';
    }

    /**
     * Hook/override for ACF render_field_settings
     *
     * @param array $field
     */
    function render_field_settings( $field ) {
        // default_value
        acf_render_field_setting( $field, array(
            'label'        => __( 'Default Value', 'acf' ),
            'instructions' => __( 'Appears when creating a new post', 'acf' ),
            'type'         => 'text',
            'name'         => 'default_value',
        ) );

        // placeholder
        acf_render_field_setting( $field, array(
            'label'        => __( 'Placeholder Text', 'acf' ),
            'instructions' => __( 'Appears within the input', 'acf' ),
            'type'         => 'text',
            'name'         => 'placeholder',
        ) );

        // maxlength
        acf_render_field_setting( $field, array(
            'label'        => __( 'Character Limit', 'acf' ),
            'instructions' => __( 'Leave blank for no limit', 'acf' ),
            'type'         => 'number',
            'name'         => 'maxlength',
        ) );
    }

    /**
     * Hook/override ACF update_value
     *
     * @param array $values - the values to save in database
     * @param int $post_id - the post_id of which the value will be saved
     * @param array $field - the field array holding all the field options
     *
     * @return string - the modified value
     * @see acf_field_text::update_value
     */
    function update_value( $values, $post_id, $field ) {
        return $this->register->encode_language_values( $values );
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
            $valid = $this->register->validate_language_values( $this, $valid, $value, $field, $input );
        }

        return $valid;
    }
}
