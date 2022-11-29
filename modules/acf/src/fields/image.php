<?php

class QTX_Module_Acf_Field_Image extends acf_field_image {
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

        acf_field::__construct();
    }

    /**
     *  Setup the field type data
     */
    function initialize() {
        $this->name     = 'qtranslate_image';
        $this->label    = __( "Image", 'acf' ) . " (qTranslate-XT)";
        $this->category = "qTranslate-XT";
        $this->defaults = array(
            'return_format' => 'array',
            'preview_size'  => 'medium',
            'library'       => 'all',
            'min_width'     => 0,
            'min_height'    => 0,
            'min_size'      => 0,
            'max_width'     => 0,
            'max_height'    => 0,
            'max_size'      => 0,
            'mime_types'    => ''
        );
        $this->l10n     = array(
            'select'     => __( "Select Image", 'acf' ),
            'edit'       => __( "Edit Image", 'acf' ),
            'update'     => __( "Update Image", 'acf' ),
            'uploadedTo' => __( "Uploaded to this post", 'acf' ),
            'all'        => __( "All images", 'acf' ),
        );

        add_filter( 'get_media_item_args', array( $this, 'get_media_item_args' ) );
        // removed from ACF 5.8.3
        if ( method_exists( $this, 'wp_prepare_attachment_for_js' ) ) {
            add_filter( 'wp_prepare_attachment_for_js', array( $this, 'wp_prepare_attachment_for_js' ), 10, 3 );
        }
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

        $field_name = $field['name'];

        $uploader = acf_get_setting( 'uploader' );
        if ( $uploader === 'wp' ) {
            acf_enqueue_uploader();
        }

        $default_value     = '';
        $default_div_attrs = array(
            'class'             => 'acf-image-uploader qtranxs-translatable',
            'data-preview_size' => $field['preview_size'],
            'data-library'      => $field['library'],
            'data-mime_types'   => $field['mime_types'],
            'data-uploader'     => $uploader,
        );
        $default_img_attrs = array(
            'src'       => '',
            'alt'       => '',
            'data-name' => 'image',
        );

        echo '<div class="multi-language-field multi-language-field-image">';

        foreach ( $languages as $language ) {
            $class = 'wp-switch-editor';
            if ( $language === $currentLanguage ) {
                $class .= ' current-language';
            }
            echo '<a class="' . $class . '" data-language="' . $language . '">' . $q_config['language_name'][ $language ] . '</a>';
        }

        foreach ( $languages as $language ):
            $field['name']              = $field_name . '[' . $language . ']';
            $field['value']             = $values[ $language ];
            $value                      = $default_value;
            $div_attrs                  = $default_div_attrs;
            $img_attrs                  = $default_img_attrs;
            $div_attrs['data-language'] = $language;

            if ( $field['value'] && is_numeric( $field['value'] ) ) {
                $image = wp_get_attachment_image_src( $field['value'], $field['preview_size'] );
                if ( $image ) {
                    $value              = $field['value'];
                    $img_attrs['src']   = $image[0];
                    $img_attrs['alt']   = get_post_meta( $field['value'], '_wp_attachment_image_alt', true );
                    $div_attrs['class'] .= ' has-value';
                }
            }

            // Add "preview size" max width and height style.
            // Apply max-width to wrap, and max-height to img for max compatibility with field widths.
            $size               = acf_get_image_size( $field['preview_size'] );
            $size_w             = $size['width'] ? $size['width'] . 'px' : '100%';
            $size_h             = $size['height'] ? $size['height'] . 'px' : '100%';
            $img_attrs['style'] = sprintf( 'max-height: %s;', $size_h );

            if ( $language === $currentLanguage ) {
                $div_attrs['class'] .= ' current-language';
            }

            ?>
            <div <?php echo acf_esc_attrs( $div_attrs ); ?>>
                <?php
                acf_hidden_input(
                    array(
                        'name'  => $field['name'],
                        'value' => $value,
                    )
                );
                ?>
                <div class="show-if-value image-wrap" style="max-width: <?php echo esc_attr( $size_w ); ?>">
                    <img <?php echo acf_esc_attrs( $img_attrs ); ?> />
                    <div class="acf-actions -hover">
                        <?php if ( $uploader !== 'basic' ) : ?>
                            <a class="acf-icon -pencil dark" data-name="edit" href="#" title="<?php _e( 'Edit', 'acf' ); ?>"></a>
                        <?php endif; ?>
                        <a class="acf-icon -cancel dark" data-name="remove" href="#" title="<?php _e( 'Remove', 'acf' ); ?>"></a>
                    </div>
                </div>
                <div class="hide-if-value">
                    <?php if ( $uploader === 'basic' ) : ?>
                        <?php if ( $field['value'] && ! is_numeric( $field['value'] ) ) : ?>
                            <div class="acf-error-message"><p><?php echo acf_esc_html( $field['value'] ); ?></p></div>
                        <?php endif; ?>
                        <label class="acf-basic-uploader">
                            <?php
                            acf_file_input(
                                array(
                                    'name' => $field['name'],
                                    'id'   => $field['id'],
                                    'key'  => $field['key'],
                                )
                            );
                            ?>
                        </label>
                    <?php else : ?>
                        <p><?php _e( 'No image selected', 'acf' ); ?> <a data-name="add" class="acf-button button" href="#"><?php _e( 'Add Image', 'acf' ); ?></a></p>
                    <?php endif; ?>
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
     * @return string - the modified value
     */
    function update_value( $values, $post_id, $field ) {
        return acf_get_field_type( 'qtranslate_file' )->update_value( $values, $post_id, $field );
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
        return acf_get_field_type( 'qtranslate_file' )->validate_value( $valid, $value, $field, $input );
    }
}
