<?php

/**
 * Handle the admin sections and settings.
 */
class QTX_Module_Acf_Admin {
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'acf/input/admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        add_filter( 'acf/get_field_types', array( $this, 'get_field_types' ) );
        add_action( 'admin_head', array( $this, 'admin_head' ) );
        add_action( 'admin_init', array( $this, 'admin_init' ) );

        add_filter( 'qtranslate_admin_config', array( $this, 'filter_qtranslate_admin_config' ) );
        add_action( 'qtranslate_configuration', array( $this, 'display_settings' ) );
        add_action( 'qtranslate_update_settings', array( $this, 'update_settings' ) );
    }

    /**
     * Return the standard ACF fields (built-in) supported for content translation e.g. post, with their default settings.
     * @return array string ACF ID => default setting
     */
    public static function standard_fields_default(): array {
        return [
            'text'     => true,
            'textarea' => true,
            'wysiwyg'  => true,
        ];
    }

    /**
     * Return the sub-fields (built-in) supported in group settings, with their default settings.
     * @return array string ACF ID => default setting
     */
    public static function group_sub_fields_default(): array {
        return [
            'label'         => true,
            'instructions'  => true,
            'default_value' => true,
        ];
    }

    /**
     * Return the sub-fields (built-in) supported in group settings, with their labels.
     * @return array string ACF ID => string label
     */
    public static function group_sub_fields_labels(): array {
        return [
            'label'         => __( 'Label', 'acf' ),
            'instructions'  => __( 'Instructions', 'acf' ),
            'default_value' => __( 'Default Value', 'acf' ),
        ];
    }

    /**
     * Return the qtranslate fields e.g. post or page, with their default settings.
     * @return array string ACF ID => default setting
     */
    public static function qtranslate_fields_default(): array {
        return [
            'qtranslate_file'        => false,
            'qtranslate_image'       => false,
            'qtranslate_post_object' => false,
            'qtranslate_text'        => false,
            'qtranslate_textarea'    => false,
            'qtranslate_url'         => false,
            'qtranslate_wysiwyg'     => false,
        ];
    }

    /**
     * Filter the field types to exclude the extended fields that have been deactivated in settings.
     *
     * @param array $groups
     *
     * @return array
     */
    public function get_field_types( array $groups ): array {
        if ( ! isset ( $groups[ QTX_Module_Acf_Extended::ACF_CATEGORY_QTX ] ) ) {
            return $groups;
        }
        $default   = self::qtranslate_fields_default();
        $settings  = self::get_module_setting( 'qtranslate_fields', $default );
        $group_qtx = &$groups[ QTX_Module_Acf_Extended::ACF_CATEGORY_QTX ];
        foreach ( $group_qtx as $field_id => $field_object ) {
            assert( isset( $settings[ $field_id ] ) );
            if ( ! $settings[ $field_id ] ) {
                unset( $group_qtx[ $field_id ] );
            }
        }
        if ( empty( $group_qtx ) ) {
            unset( $groups[ QTX_Module_Acf_Extended::ACF_CATEGORY_QTX ] );
        }
        return $groups;
    }

    /**
     * Load javascript and stylesheets on admin pages
     */
    public function admin_enqueue_scripts(): void {
        wp_enqueue_style( 'qtranslate-acf', plugins_url( 'css/modules/acf.css', QTRANSLATE_FILE ),
            array( 'acf-input' ), QTX_VERSION );

        wp_enqueue_script( 'qtranslate-acf', plugins_url( 'dist/modules/acf.js', QTRANSLATE_FILE ), array(
            'acf-input',
            'underscore',
            'qtranslate-admin-main'
        ), QTX_VERSION );

        wp_localize_script( 'qtranslate-acf', 'qTranslateModuleAcf', get_option( QTX_OPTIONS_MODULE_ACF ) );
    }

    /**
     * Add additional styles and scripts to head
     */
    public function admin_head(): void {
        // Hide the language tabs if they shouldn't be displayed
        $show_language_tabs = self::get_module_setting( 'show_language_tabs' );
        if ( ! $show_language_tabs ) {
            ?>
            <style>
                .multi-language-field {
                    margin-top: 0 !important;
                }

                .multi-language-field .wp-switch-editor[data-language] {
                    display: none !important;
                }
            </style>
            <?php
        }
    }

    /**
     * Enable the LSB and display hooks for ACF.
     *
     * @param array $config
     *
     * @return array
     */
    public function filter_qtranslate_admin_config( array $config ): array {
        // Display for posts with ACF fields.
        $config['acf-post'] = [
            'pages' => [
                'post.php'     => '',
                'post-new.php' => '',
            ],
            'forms' => [
                // classic LSB (above #post) and blocks SLM (no #post)
                'wpbody-content' => [
                    'fields' => [
                        'acf-field-postbox' => [
                            'jquery' => '.acf-postbox .postbox-header h2',
                            'encode' => 'display',
                        ],
                        'acf-field-label'   => [
                            'jquery' => '.acf-label > label, .acf-label > p.description',
                            'encode' => 'display',
                        ],
                    ],
                ],
            ],
        ];
        // Display for ACF options: edit ACF field group.
        $config['acf-field-group'] = [
            'pages'     => [ 'post.php' => '' ],
            'post_type' => 'acf-field-group',
            'forms'     => [
                'post' => [
                    'fields' => [
                        'acf-field-group-object-label' => [
                            'jquery' => '.li-field-label .edit-field',
                            'encode' => 'display',
                        ],
                    ]
                ],
            ],
        ];
        // Display for generic forms with ACF fields.
        $config['acf-forms'] = [
            'pages'   => [
                // TODO: see how to handle this more dynamically with current_screen.
                'admin.php'     => '',  // custom admin options (ACF Pro).
                'comment.php'   => '',
                'nav-menus.php' => '',
                'profile.php'   => '',
                'user-edit.php' => '',
                'user-new.php'  => '',
                'widgets.php'   => '',
            ],
            'anchors' => [
                'acf-form-data' => [ 'where' => 'after' ],
            ],
            'forms'   => [
                'wpbody-content' => [
                    'fields' => [
                        'acf-form-data-title'   => [
                            'jquery' => '#acf-form-data ~ h2, .acf-postbox h3, .acf-menu-settings h2',
                            'encode' => 'display'
                        ],
                        'acf-field-label'       => [
                            'jquery' => '.acf-label > label, .acf-label > p.description',
                            'encode' => 'display',
                        ],
                        'acf-admin-field-title' => [
                            'jquery' => '.acf-postbox .postbox-header h2',  // admin.php not set in main i18n.
                            'encode' => 'display',
                        ],
                    ]
                ]
            ]
        ];
        // Display for taxonomy with ACF fields.
        $config['acf-taxonomy'] = [
            'pages'   => [
                'edit-tags.php' => 'taxonomy=',
                'term.php'      => 'taxonomy=',
            ],
            'anchors' => [
                'acf-form-data' => [ 'where' => 'after' ],
                'edittag'       => [ 'where' => 'before' ], // To enforce the top LSB (usually wrap).
            ],
            'forms'   => [
                'wpbody-content' => [
                    'fields' => [
                        'acf-form-data-title'         => [
                            'jquery' => '#acf-form-data ~ h2',
                            'encode' => 'display'
                        ],
                        'acf-field-input-description' => [
                            'jquery' => '.acf-label > label, .acf-input > p.description',
                            'encode' => 'display',
                        ],
                    ]
                ]
            ]
        ];

        return $config;
    }

    /**
     * Retrieve the value of a setting for the ACF module.
     *
     * @param string $name Name of the WordPress field setting (one entry per `add_settings_field`).
     * @param mixed $default
     *
     * @return mixed
     */
    protected static function get_module_setting( string $name, $default = null ) {
        $options  = get_option( QTX_OPTIONS_MODULE_ACF ); // Global key for all ACF settings, ignore default.
        $settings = $options[ $name ] ?? $default;
        // If new sub-keys are added to array settings, ensure the default values complete missing entries in storage.
        return is_array( $default ) ? array_merge( $default, $settings ) : $settings;
    }

    /**
     * Register settings and validation hooks.
     */
    public function admin_init(): void {
        register_setting( 'settings-qtranslate-acf', QTX_OPTIONS_MODULE_ACF );

        // Standard fields (ACF builtin fields)
        add_settings_section(
            'section-acf-standard',
            __( 'Standard fields', 'qtranslate' ),
            '',
            'settings-qtranslate-acf'
        );
        add_settings_field(
            'translate_standard_fields',
            __( 'Translate standard fields', 'qtranslate' ),
            array( $this, 'render_setting_standard_fields' ),
            'settings-qtranslate-acf',
            'section-acf-standard'
        );
        add_settings_field(
            'translate_group_sub_fields',
            __( 'Translate group sub-fields', 'qtranslate' ),
            array( $this, 'render_setting_group_sub_fields' ),
            'settings-qtranslate-acf',
            'section-acf-standard'
        );

        // Extended fields (overridden "qTranslate-XT" fields)
        add_settings_section(
            'section-acf-extended',
            __( 'Extended qTranslate fields', 'qtranslate' ),
            function () {
                _e( 'Additional types specific to qTranslate. Prefer the standard ACF fields.', 'qtranslate' );
            },
            'settings-qtranslate-acf'
        );
        add_settings_field(
            'select_qtranslate_fields',
            __( 'Types enabled for selection', 'qtranslate' ),
            array( $this, 'render_setting_qtranslate_fields' ),
            'settings-qtranslate-acf',
            'section-acf-extended'
        );
        add_settings_field(
            'show_language_tabs',
            'Display language tabs',
            array( $this, 'render_setting_show_language_tabs' ),
            'settings-qtranslate-acf',
            'section-acf-extended'
        );

        // Enable multi-language validation, typically the max length must be checked for each language and not the raw string.
        $default  = self::standard_fields_default();
        $settings = self::get_module_setting( 'standard_fields', $default );
        $fields   = array_keys( $settings );
        foreach ( $fields as $tag ) {
            $acf_type = acf_get_field_type( $tag );
            if ( ! isset( $acf_type ) || ! isset( $settings[ $tag ] ) ) {
                continue;
            }
            add_filter( 'acf/validate_value/type=' . $tag, array( $this, 'validate_raw_value_standard' ), 5, 4 );
        }
    }

    /**
     * Validates the form multilingual raw values for a given standard field.
     *
     * @param bool|string $valid
     * @param string $value raw value
     * @param array $field info
     * @param string $input
     *
     * @return bool|string
     * @see acf_validation::acf_validate_value
     */
    public static function validate_raw_value_standard( $valid, $value, array $field, $input ) {
        if ( is_string( $value ) && qtranxf_isMultilingual( $value ) ) {
            // Remove the standard validation that is likely to fail for the raw value.
            $instance = acf_get_field_type( $field['type'] );
            remove_filter( 'acf/validate_value/type=' . $field['type'], array( $instance, 'validate_value' ) );
            // Validate all the values for each language.
            $ml_values = qtranxf_split( $value );
            $valid     = self::validate_language_values_standard( $instance, $valid, $ml_values, $field, $input );
        }
        return $valid;
    }

    /**
     * Validate language values for a standard field.
     *
     * Validates all the form values for a given field, by iterating over the multi-lang array values as strings:
     *  - check the required property
     *  - call the original ACF method for every language string.
     *
     * @param object $field_object corresponding to the qtranslate ACF field item
     * @param bool|string $valid
     * @param array $values holding a string value per language
     * @param array $field info
     * @param string $input
     *
     * @return    bool|string
     * @see acf_validation::acf_validate_value
     */
    protected static function validate_language_values_standard( $field_object, $valid, array $values, array $field, $input ) {
        global $q_config;

        // Validate every language value as string.
        foreach ( $values as $key_language => $value_language ) {
            // validate properly the required value (see: acf_validate_value in acf_validation)
            if ( $field['required'] && empty( $value_language ) ) {
                // TODO: retrieve the label for the language being edited.
                $label = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $field['label'] );
                return '(' . $q_config['language_name'][ $key_language ] . ') ' . sprintf( __( '%s value is required', 'acf' ), $label );
            }
            // Validate with original ACF method.
            if ( method_exists( $field_object, 'validate_value' ) ) {
                $valid_language = $field_object->validate_value(
                    $valid,
                    $value_language,
                    $field,
                    $input
                );
                if ( ! empty( $valid_language ) && is_string( $valid_language ) ) {
                    return '(' . $q_config['language_name'][ $key_language ] . ') ' . $valid_language;
                }
            }
        }

        return $valid;
    }

    public function display_settings(): void {
        QTX_Admin_Settings::open_section( 'acf' );
        wp_nonce_field( 'acf', 'nonce_acf', false );
        do_settings_sections( 'settings-qtranslate-acf' );
        QTX_Admin_Settings::close_section( 'acf' );
    }

    public function update_settings(): void {
        // The nonce allows to validate the settings tab was displayed but also to have checkbox fields only.
        if ( ! isset( $_POST['nonce_acf'] ) || ! wp_verify_nonce( $_POST['nonce_acf'], 'acf' ) ) {
            return;
        }
        $post_acf = $_POST[ QTX_OPTIONS_MODULE_ACF ] ?? [];
        // Unchecked boxes are not part of the POST data. We want to store explicit values for two reasons:
        // 1) the settings falls back to default when all values are unchecked (not set in POST)
        // 2) if new options come in later, we have no way to tell it's undefined or unchecked after user update.
        // Ensure we set values for all keys though the default values are ignored.
        $options  = [
            'standard_fields'    => self::standard_fields_default(),
            'group_sub_fields'   => self::group_sub_fields_default(),
            'qtranslate_fields'  => self::qtranslate_fields_default(),
            'show_language_tabs' => isset( $post_acf['show_language_tabs'] ) && $post_acf['show_language_tabs'],
        ];
        // If checked, convert '1' strings to `true`, otherwise `false` (ignore the current value = default).
        $set_bool = function ( &$item, $key, $post_fields ): void {
            $item = isset( $post_fields[ $key ] ) && $post_fields[ $key ];
        };
        array_walk( $options['standard_fields'], $set_bool, $post_acf['standard_fields'] ?? [] );
        array_walk( $options['group_sub_fields'], $set_bool, $post_acf['group_sub_fields'] ?? [] );
        array_walk( $options['qtranslate_fields'], $set_bool, $post_acf['qtranslate_fields'] ?? [] );
        update_option( QTX_OPTIONS_MODULE_ACF, $options, false );
    }

    /**
     * Render setting
     */
    public function render_setting_standard_fields(): void {
        $default  = self::standard_fields_default();
        $settings = self::get_module_setting( 'standard_fields', $default );
        $fields   = array_keys( $default );
        foreach ( $fields as $id ):
            $acf_type = acf()->fields->get_field_type( $id );
            if ( ! isset( $acf_type ) ) {
                continue;
            } ?>
            <label>
                <input type="checkbox"
                       name="<?php echo QTX_OPTIONS_MODULE_ACF ?>[standard_fields][<?php echo $id ?>]" <?php checked( $settings[ $id ] ); ?>
                       value="1"/><?php echo $acf_type->label ?>
            </label>
            <br/>
        <?php endforeach;
    }

    /**
     * Render setting
     */
    public function render_setting_group_sub_fields(): void {
        $default  = self::group_sub_fields_default();
        $settings = self::get_module_setting( 'group_sub_fields', $default );
        $labels   = self::group_sub_fields_labels();
        $fields   = array_keys( $default );
        foreach ( $fields as $id ): ?>
            <label>
                <input type="checkbox"
                       name="<?php echo QTX_OPTIONS_MODULE_ACF ?>[group_sub_fields][<?php echo $id ?>]" <?php checked( $settings[ $id ] ); ?>
                       value="1"/><?php echo $labels[ $id ] ?>
            </label>
            <br/>
        <?php endforeach;
    }

    /**
     * Render setting
     */
    public function render_setting_qtranslate_fields(): void {
        $default  = self::qtranslate_fields_default();
        $settings = self::get_module_setting( 'qtranslate_fields', $default );
        $fields   = array_keys( $default );
        foreach ( $fields as $id ):
            $acf_type = acf()->fields->get_field_type( $id );
            if ( ! isset( $acf_type ) ) {
                continue;
            }
            ?>
            <label>
                <input type="checkbox"
                       name="<?php echo QTX_OPTIONS_MODULE_ACF ?>[qtranslate_fields][<?php echo $id ?>]" <?php checked( $settings[ $id ] ); ?>
                       value="1"/><?php echo $acf_type->label ?>
            </label>
            <br/>
        <?php endforeach;
    }

    /**
     * Render setting
     */
    public function render_setting_show_language_tabs(): void {
        ?>
        <input type="checkbox"
               name="<?php echo QTX_OPTIONS_MODULE_ACF ?>[show_language_tabs]" <?php checked( self::get_module_setting( 'show_language_tabs', false ) ); ?>
               value="1">
        <?php
    }

    /**
     * Get the visible ACF fields
     *
     * @param null $widget_id
     *
     * @return array
     */
    // TODO: restore or remove dead code, used from `qtranslate_load_admin_page_config` and `admin_enqueue_scripts` but it
    //       was removed by https://github.com/funkjedi/acf-qtranslate/commit/c152248ff1771fd33643bc39dd286cd3e4cb3e57
    /*
    public function get_visible_acf_fields( $widget_id = null ) {
        global $wp_registered_widgets;

        $visible_fields = array();

        // build field group filters required for current screen
        $filter = $this->get_acf_field_group_filters();
        if ( count( $filter ) === 0 ) {
            return $visible_fields;
        }

        // widgets need some special handling since they
        // require multiple acf_get_field_group_visibility()
        // calls in order to return all the visible fields
        if ( acf_is_screen( 'widgets' ) || acf_is_screen( 'customize' ) ) {
            if ( $widget_id ) {
                $filter['widget'] = _get_widget_id_base( $widget_id );
            } else {
                // process each widget form individually for any visible fields
                foreach ( $wp_registered_widgets as $widget ) {
                    $visible_fields += $this->get_visible_acf_fields( $widget['id'] );
                }

                return $visible_fields;
            }
        }

        $supported_field_types = array(
            'email',
            'text',
            'textarea',
            'repeater',
            'flexible_content',
            'qtranslate_file',
            'qtranslate_image',
            'qtranslate_post_object',
            'qtranslate_text',
            'qtranslate_textarea',
            'qtranslate_url',
            'qtranslate_wysiwyg',
        );

        foreach ( acf_get_field_groups( $filter ) as $field_group ) {
            $fields = acf_get_fields( $field_group );
            foreach ( $fields as $field ) {
                if ( in_array( $field['type'], $supported_field_types ) ) {
                    $visible_fields[] = array( 'id' => 'acf-' . $field['key'] );
                }
            }
        }

        return $visible_fields;
    }
    */

    /**
     * Get field group filters based on active screen.
     */
    // TODO: restore or remove dead code, only called by `get_visible_acf_fields`.
    /*
    public function get_acf_field_group_filters() {
        global $post, $pagenow, $typenow, $plugin_page;

        $filter = array();
        if ( $pagenow === 'post.php' || $pagenow === 'post-new.php' ) {
            if ( $typenow !== 'acf' ) {
                $filter['post_id']   = $post->ID;
                $filter['post_type'] = $typenow;
            }
        } elseif ( $pagenow === 'admin.php' && isset( $plugin_page ) ) {
            if ( acf_get_options_page( $plugin_page ) ) {
                $filter['post_id'] = acf_get_valid_post_id( 'options' );
            }
        } elseif ( $pagenow === 'edit-tags.php' && isset( $_GET['taxonomy'] ) ) {
            $filter['taxonomy'] = filter_var( $_GET['taxonomy'], FILTER_SANITIZE_STRING );
        } elseif ( $pagenow === 'profile.php' ) {
            $filter['user_id']   = get_current_user_id();
            $filter['user_form'] = 'edit';
        } elseif ( $pagenow === 'user-edit.php' && isset( $_GET['user_id'] ) ) {
            $filter['user_id']   = filter_var( $_GET['user_id'], FILTER_SANITIZE_NUMBER_INT );
            $filter['user_form'] = 'edit';
        } elseif ( $pagenow === 'user-new.php' ) {
            $filter['user_id']   = 'new';
            $filter['user_form'] = 'edit';
        } elseif ( $pagenow === 'media.php' || $pagenow === 'upload.php' ) {
            $filter['attachment'] = 'All';
        } elseif ( acf_is_screen( 'widgets' ) || acf_is_screen( 'customize' ) ) {
            $filter['widget'] = 'all';
        }

        return $filter;
    }
    */
}
