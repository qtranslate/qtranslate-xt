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
        add_action( 'admin_head', array( $this, 'admin_head' ) );
        add_action( 'admin_init', array( $this, 'admin_init' ) );

        add_filter( 'qtranslate_admin_config', array( $this, 'filter_qtranslate_admin_config' ) );
        add_action( 'qtranslate_configuration', array( $this, 'display_settings' ) );
        add_action( 'qtranslate_update_settings', array( $this, 'update_settings' ) );
    }

    /**
     * Return a map of id->label for the standard content fields e.g. post or page.
     * @return array
     */
    public static function content_fields() {
        return [
            'text'     => __( 'Text', 'acf' ),
            'textarea' => __( 'Text Area', 'acf' ),
            'wysiwyg'  => __( 'Wysiwyg Editor', 'acf' ),
        ];
    }

    /**
     * Return a map of id->label for the meta fields supported in group settings.
     * @return array
     */
    public static function group_meta_fields() {
        return [
            'label'         => __( 'Label', 'acf' ),
            'instructions'  => __( 'Instructions', 'acf' ),
            'default_value' => __( 'Default Value', 'acf' ),
        ];
    }

    /**
     * Load javascript and stylesheets on admin pages
     */
    public function admin_enqueue_scripts() {
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
    public function admin_head() {
        // Hide the language tabs if they shouldn't be displayed
        $show_language_tabs = $this->get_module_setting( 'show_language_tabs' );
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
    public function filter_qtranslate_admin_config( $config ) {
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
     * The default is set in register_setting so this works only for admin.
     *
     * @param string $name
     *
     * @return mixed
     */
    function get_module_setting( $name ) {
        $options = get_option( QTX_OPTIONS_MODULE_ACF );
        if ( isset( $options[ $name ] ) ) {
            return $options[ $name ];
        }

        return null;
    }

    /**
     * Register settings and default fields
     */
    function admin_init() {
        // The default is set in a "default_option_{$option_name}" filter.
        // Remap the labels to boolean values.
        // Attention: once saved by admin, they are stored as "1" strings if checked, nothing if unchecked.
        $default = [
            'content_fields'     => array_map( function () {
                return true;
            }, $this->content_fields() ),
            'group_meta_fields'  => array_map( function () {
                return true;
            }, $this->group_meta_fields() ),
            'show_language_tabs' => false,
        ];
        register_setting( 'settings-qtranslate-acf', QTX_OPTIONS_MODULE_ACF, [ 'default' => $default ] );

        // Standard fields (ACF builtin fields)
        add_settings_section(
            'section-acf-standard',
            __( 'Standard fields', 'qtranslate' ),
            '',
            'settings-qtranslate-acf'
        );
        add_settings_field(
            'translate_content_fields',
            __( 'Translate content fields', 'qtranslate' ),
            array( $this, 'render_setting_content_fields' ),
            'settings-qtranslate-acf',
            'section-acf-standard'
        );
        add_settings_field(
            'translate_group_meta_fields',
            __( 'Translate group meta fields', 'qtranslate' ),
            array( $this, 'render_setting_group_meta_fields' ),
            'settings-qtranslate-acf',
            'section-acf-standard'
        );

        // Extended fields (ACF-QTX overridden fields)
        add_settings_section(
            'section-acf-extended',
            __( 'Extended fields', 'qtranslate' ),
            '',
            'settings-qtranslate-acf'
        );
        add_settings_field(
            'show_language_tabs',
            'Display language tabs',
            array( $this, 'render_setting_show_language_tabs' ),
            'settings-qtranslate-acf',
            'section-acf-extended'
        );
    }

    function display_settings() {
        QTX_Admin_Settings::open_section( 'acf' );
        wp_nonce_field( 'acf', 'nonce_acf', false );
        do_settings_sections( 'settings-qtranslate-acf' );
        QTX_Admin_Settings::close_section( 'acf' );
    }

    function update_settings() {
        // The nonce allows to validate the settings tab was displayed but also to have checkbox fields only.
        if ( ! isset( $_POST['nonce_acf'] ) || ! wp_verify_nonce( $_POST['nonce_acf'], 'acf' ) ) {
            return;
        }
        $options = isset( $_POST[ QTX_OPTIONS_MODULE_ACF ] ) ? $_POST[ QTX_OPTIONS_MODULE_ACF ] : null;
        update_option( QTX_OPTIONS_MODULE_ACF, $options, false );
    }

    /**
     * Render setting
     */
    function render_setting_content_fields() {
        $settings = $this->get_module_setting( 'content_fields' );
        $fields   = $this->content_fields();
        foreach ( $fields as $f_id => $f_label ): ?>
            <label>
                <input type="checkbox"
                       name="<?php echo QTX_OPTIONS_MODULE_ACF ?>[content_fields][<?php echo $f_id ?>]" <?php checked( isset( $settings[ $f_id ] ) && $settings[ $f_id ] ); ?>
                       value="1"/><?php echo $f_label ?>
            </label>
            <br/>
        <?php endforeach;
    }

    /**
     * Render setting
     */
    function render_setting_group_meta_fields() {
        $settings = $this->get_module_setting( 'group_meta_fields' );
        $fields   = $this->group_meta_fields();
        foreach ( $fields as $f_id => $f_label ): ?>
            <label>
                <input type="checkbox"
                       name="<?php echo QTX_OPTIONS_MODULE_ACF ?>[group_meta_fields][<?php echo $f_id ?>]" <?php checked( isset( $settings[ $f_id ] ) && $settings[ $f_id ] ); ?>
                       value="1"/><?php echo $f_label ?>
            </label>
            <br/>
        <?php endforeach;
    }

    /**
     * Render setting
     */
    function render_setting_show_language_tabs() {
        ?>
        <input type="checkbox"
               name="<?php echo QTX_OPTIONS_MODULE_ACF ?>[show_language_tabs]" <?php checked( $this->get_module_setting( 'show_language_tabs' ), 1 ); ?>
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
