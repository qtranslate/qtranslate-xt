<?php

class QTX_Module_Acf {
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'plugins_loaded', array( $this, 'init' ), 3 );
        add_action( 'after_setup_theme', array( $this, 'init' ), -10 );
    }

    /**
     * Setup plugin if Advanced Custom Fields is enabled
     *
     * @return void
     */
    public function init() {
        static $plugin_loaded = false;
        if ( ! $plugin_loaded && function_exists( 'acf' ) ) {
            if ( version_compare( $this->acf_version(), '5.0.0' ) >= 0 ) {
                require_once __DIR__ . '/qtx_module_acf_fields.php';
                new QTX_Module_Acf_Fields( $this );

                if (is_admin()) {
                    require_once __DIR__ . '/qtx_module_acf_admin.php';
                    new QTX_Module_Acf_Admin();
                }
            }
            $plugin_loaded = true;
        }
    }

    /**
     * Return the version of Advanced Custom Fields plugin
     *
     * @return string
     */
    public function acf_version() {
        return acf()->settings['version'];
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