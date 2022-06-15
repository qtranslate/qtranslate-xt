<?php

/**
 * Allows the integration with ACF by setting up the derived fields.
 */
class QTX_Module_Acf_Register {
    /**
     * Constructor
     */
    public function __construct() {
        // a higher priority is needed for custom admin options (ACF PRO)
        add_filter( 'acf/format_value', array( $this, 'format_value' ), 5 );
        add_action( 'acf/include_fields', array( $this, 'include_fields' ), 5 );
    }

    /**
     * Register the fields in the ACF plugin.
     */
    public function include_fields() {
        require_once __DIR__ . '/fields/file.php';
        require_once __DIR__ . '/fields/image.php';
        require_once __DIR__ . '/fields/post_object.php';
        require_once __DIR__ . '/fields/text.php';
        require_once __DIR__ . '/fields/textarea.php';
        require_once __DIR__ . '/fields/url.php';
        require_once __DIR__ . '/fields/wysiwyg.php';

        // Before ACF 5.6.0 initialize() must be called explicitly in the constructors of the acf_field derived classes.
        $do_initialize = version_compare( acf()->settings['version'], '5.6.0' ) < 0;

        acf()->fields->register_field_type( new QTX_Module_Acf_Field_File( $this, $do_initialize ) );
        acf()->fields->register_field_type( new QTX_Module_Acf_Field_Image( $this, $do_initialize ) );
        acf()->fields->register_field_type( new QTX_Module_Acf_Field_Post_Object( $this, $do_initialize ) );
        acf()->fields->register_field_type( new QTX_Module_Acf_Field_Text( $this, $do_initialize ) );
        acf()->fields->register_field_type( new QTX_Module_Acf_Field_Textarea( $this, $do_initialize ) );
        acf()->fields->register_field_type( new QTX_Module_Acf_Field_Url( $this, $do_initialize ) );
        acf()->fields->register_field_type( new QTX_Module_Acf_Field_Wysiwyg( $this, $do_initialize ) );
    }

    /**
     * Hook/override ACF format_value
     *
     * This filter is applied to the $value after it is loaded from the db and
     * before it is returned to the template via functions such as get_field().
     *
     * @param $value
     *
     * @return array|mixed|string|void
     */
    public function format_value( $value ) {
        if ( is_string( $value ) ) {
            $value = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $value );
            $value = maybe_unserialize( $value );
        }

        return $value;
    }

    /**
     * Encode a multi-language array into a string with bracket tags
     *
     * @param array $values
     *
     * @return string
     */
    public function encode_language_values( $values ) {
        assert( is_array( $values ) );

        return qtranxf_join_b( $values );
    }

    /**
     * Decode a multi-language string to an array
     *
     * @param string $values
     *
     * @return array
     */
    public function decode_language_values( $values ) {
        return qtranxf_split( $values );
    }

    /**
     * Validate language values
     *
     * Validates all the form values for a given field, by iterating over the multi-lang array values as strings:
     *  - check the required property
     *  - call the original ACF method for every language string.
     *
     * @param object $field_object corresponding to the qtranslate ACF field item
     * @param bool|string $valid
     * @param array $values holding a string value per language
     * @param string $field
     * @param string $input
     *
     * @return    bool|string
     * @see acf_validation::acf_validate_value
     */
    public function validate_language_values( $field_object, $valid, $values, $field, $input ) {
        global $q_config;

        // retrieve the original ACF validation method for that field (cumbersome, but we can't change acf_field base class)
        $parent_class = get_parent_class( $field_object );
        if ( method_exists( $parent_class, 'validate_value' ) ) {
            try {
                $validation_method = new ReflectionMethod( $parent_class, 'validate_value' );
            } catch ( ReflectionException $exception ) {
                return $exception->getMessage();
            }
        }

        // validate every language value as string
        foreach ( $values as $key_language => $value_language ) {
            // validate properly the required value (see: acf_validate_value in acf_validation)
            if ( $field['required'] && empty( $value_language ) ) {
                return '(' . $q_config['language_name'][ $key_language ] . ') ' . sprintf( __( '%s value is required', 'acf' ), $field['label'] );
            }
            // validate with original ACF method
            if ( isset( $validation_method ) ) {
                $valid_language = $validation_method->invokeArgs( $field_object, array(
                    $valid,
                    $value_language,
                    $field,
                    $input
                ) );
                if ( ! empty( $valid_language ) && is_string( $valid_language ) ) {
                    return '(' . $q_config['language_name'][ $key_language ] . ') ' . $valid_language;
                }
            }
        }

        return $valid;
    }
}
