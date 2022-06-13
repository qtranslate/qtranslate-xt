<?php

class QTX_Module_Acf_Admin {
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'acf/input/admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        add_action( 'admin_footer', array( $this, 'admin_footer' ), -10 );
        add_action( 'admin_head', array( $this, 'admin_head' ) );
        add_action( 'admin_init', array( $this, 'admin_init' ) );

        add_filter( 'qtranslate_admin_config', array( $this, 'filter_qtranslate_admin_config' ) );
        add_action( 'qtranslate_configuration', array( $this, 'display_settings' ) );
        add_action( 'qtranslate_update_settings', array( $this, 'update_settings' ) );
    }

    /**
     * Load javascript and stylesheets on admin pages
     */
    public function admin_enqueue_scripts() {
        wp_enqueue_style( 'qtranslate-acf', plugins_url( 'modules/acf/assets/acf.css', QTRANSLATE_FILE ),
            array( 'acf-input' ), QTX_VERSION );

        wp_enqueue_script( 'qtranslate-acf', plugins_url( 'dist/modules/acf.js', QTRANSLATE_FILE ), array(
            'acf-input',
            'underscore',
            'qtranslate-admin-main'
        ), QTX_VERSION );
    }

    /**
     * Output a hidden block that can be used to force qTranslate-X to include the LSB
     */
    public function admin_footer() {
        ?>
        <script>
            (function ($) {
                var anchors = {
                    '#post-body-content': 'prepend',
                    '#widgets-right': 'before',
                    '#posts-filter': 'prepend',
                    '#wpbody-content h1': 'after',
                    '#wpbody-content': 'prepend'
                };
                $.each(anchors, function (anchor, fn) {
                    var $anchor = $(anchor);
                    if ($anchor.length) {
                        $anchor[fn]('<span id="acf-qtranslate-lsb-shim" style="display:none">[:en]LSB[:]</span>');
                        return false;
                    }
                });
            })(jQuery);
        </script>
        <?php
    }

    /**
     * Add additional styles and scripts to head
     */
    public function admin_head() {
        // Hide the language tabs if they shouldn't be displayed
        $show_language_tabs = $this->get_plugin_setting( 'show_language_tabs' );
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

        // Enable translation of standard field types
        $translate_standard_field_types = $this->get_plugin_setting( 'translate_standard_field_types' );
        if ( $translate_standard_field_types ) {
            ?>
            <script>
                var acf_qtranslate_translate_standard_field_types = <?= json_encode( $translate_standard_field_types ) ?>;
            </script>
            <?php
        }
    }

    /**
     * Enable the display of the LSB on ACF Options pages
     *
     * @param array $config
     *
     * @return array
     */
    public function filter_qtranslate_admin_config( $config ) {
        $pages = array(
            'admin.php' => 'page=',
        );

        foreach ( explode( "\n", $this->get_plugin_setting( 'show_on_pages' ) ) as $page ) {
            $page = trim( $page );
            if ( $page ) {
                $pages[ $page ] = '';
            }
        }

        $config['acf-display-nodes'] = array(
            'pages'   => $pages,
            'anchors' => array(
                'acf-qtranslate-lsb-shim' => array( 'where' => 'after' ),
            ),
            'forms'   => array(
                'wpwrap' => array(
                    'fields' => array(
                        'lsb-shim'                => array(
                            'jquery' => '#acf-qtranslate-lsb-shim',
                            'encode' => 'display',
                        ),
                        'acf5-field-group-handle' => array(
                            'jquery' => '.acf-postbox h2 span,.acf-postbox h3 span',
                            'encode' => 'display',
                        ),
                        'acf5-field-label'        => array(
                            'jquery' => '.acf-field .acf-label label',
                            'encode' => 'display',
                        ),
                        'acf5-field-description'  => array(
                            'jquery' => '.acf-field .acf-label p.description',
                            'encode' => 'display',
                        ),
                    )
                ),
            ),
        );

        $config['acf-field-group'] = array(
            'pages'     => array( 'post.php' => '' ),
            'post_type' => 'acf-field-group',
            'forms'     => array(
                'post' => array(
                    'fields' => array(
                        'field-group-object-label' => array(
                            'jquery' => '.li-field-label .edit-field',
                            'encode' => 'display',
                        ),
                    )
                ),
            ),
        );

        return $config;
    }

    /**
     * Retrieve the value of a plugin setting
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    function get_plugin_setting( $name, $default = null ) {
        $options = get_option( QTX_OPTIONS_MODULE_ACF );
        if ( isset( $options[ $name ] ) ) {
            return $options[ $name ];
        }

        return $default;
    }

    /**
     * Register settings and default fields
     */
    function admin_init() {
        register_setting( 'settings-qtranslate-acf', QTX_OPTIONS_MODULE_ACF );

        // Define a placeholder for the fields without title or content.
        add_settings_section(
            'section-acf',
            '',
            '__return_false',
            'settings-qtranslate-acf'
        );

        add_settings_field(
            'translate_standard_field_types',
            'Enable translation for Standard Field Types',
            array( $this, 'render_setting_translate_standard_field_types' ),
            'settings-qtranslate-acf',
            'section-acf'
        );

        add_settings_field(
            'show_language_tabs',
            'Display language tabs',
            array( $this, 'render_setting_show_language_tabs' ),
            'settings-qtranslate-acf',
            'section-acf'
        );

        add_settings_field(
            'show_on_pages',
            'Display the LSB on the following pages',
            array( $this, 'render_setting_show_on_pages' ),
            'settings-qtranslate-acf',
            'section-acf'
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
    function render_setting_translate_standard_field_types() {
        ?>
        <input type="checkbox"
               name="<?php echo QTX_OPTIONS_MODULE_ACF ?>[translate_standard_field_types]" <?php checked( $this->get_plugin_setting( 'translate_standard_field_types' ), 1 ); ?>
               value="1">
        <?php
    }

    /**
     * Render setting
     */
    function render_setting_show_language_tabs() {
        ?>
        <input type="checkbox"
               name="<?php echo QTX_OPTIONS_MODULE_ACF ?>[show_language_tabs]" <?php checked( $this->get_plugin_setting( 'show_language_tabs' ), 1 ); ?>
               value="1">
        <?php
    }

    /**
     * Render setting
     */
    function render_setting_show_on_pages() {
        ?>
        <textarea name="<?php echo QTX_OPTIONS_MODULE_ACF ?>[show_on_pages]"
                  style="max-width:500px;width:100%;height:200px;padding-top:6px"
                  placeholder="post.php"><?= esc_html( $this->get_plugin_setting( 'show_on_pages' ) ) ?></textarea><br>
        <small>Enter each page on it's own line</small>
        <?php
    }


}
