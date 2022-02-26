<?php

class acf_qtranslate_plugin {

    /**
     * An ACF instance
     * @var acf_qtranslate_acf_interface
     */
    protected $acf;

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'plugins_loaded', array( $this, 'init' ), 3 );
        add_action( 'after_setup_theme', array( $this, 'init' ), -10 );
        add_action( 'acf/input/admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        add_action( 'admin_footer', array( $this, 'admin_footer' ), -10 );
        add_action( 'admin_head', array( $this, 'admin_head' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'admin_init' ) );

        add_filter( 'qtranslate_admin_config', array( $this, 'filter_qtranslate_admin_config' ) );
        add_filter( 'plugin_action_links_' . plugin_basename( ACF_QTRANSLATE_PLUGIN ), array(
            $this,
            'plugin_action_links'
        ) );
    }

    /**
     * Setup plugin if Advanced Custom Fields is enabled
     *
     * @return void
     */
    public function init() {
        static $plugin_loaded = false;
        if ( ! $plugin_loaded && $this->acf_enabled() ) {
            if ( $this->acf_major_version() === 5 ) {
                require_once ACF_QTRANSLATE_PLUGIN_DIR . 'src/acf_5/acf.php';
                $this->acf = new acf_qtranslate_acf_5( $this );
            }
            $plugin_loaded = true;
        }
    }

    /**
     * Check if Advanced Custom Fields is enabled
     *
     * @return boolean
     */
    public function acf_enabled() {
        if ( function_exists( 'acf' ) ) {
            return $this->acf_major_version() === 5;
        }

        return false;
    }

    /**
     * Return the major version number for Advanced Custom Fields
     *
     * @return int
     */
    public function acf_version() {
        return acf()->settings['version'];
    }

    /**
     * Return the major version number for Advanced Custom Fields
     *
     * @return int
     */
    public function acf_major_version() {
        return (int) $this->acf_version();
    }

    /**
     * Get the active language
     *
     * IMPORTANT!
     * The active language should only be used for a default selection but the rendering should be "non-restrictive".
     * In LSB mode, we should not assume which language the client is going to select eventually.
     * Though we are likely to select the "wrong" language, the correct selection will be adjusted client-side.
     * In single mode, the rendering should be the correct one though, as the language remains the same.
     */
    public function get_active_language() {
        return qtranxf_getLanguage();
    }

    /**
     * Load javascript and stylesheets on admin pages
     */
    public function admin_enqueue_scripts() {
        wp_enqueue_style( 'qtranslate-acf', plugins_url( 'assets/acf.css', ACF_QTRANSLATE_PLUGIN ),
            array( 'acf-input' ), QTX_VERSION );

        wp_enqueue_script( 'qtranslate-acf', plugins_url( 'dist/modules/acf.js', QTRANSLATE_FILE ), array(
            'acf-input',
            'underscore',
            'qtranslate-admin-main'
        ), QTX_VERSION );
    }

    /**
     * Output a hidden block that can be use to force qTranslate-X to include the LSB
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
     * Add settings link on plugin page
     *
     * @param array $links
     *
     * @return array
     */
    public function plugin_action_links( $links ) {
        array_unshift( $links, '<a href="options-general.php?page=acf-qtranslate">Settings</a>' );

        return $links;
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
     * @param $default
     *
     * @return |null
     */
    function get_plugin_setting( $name, $default = null ) {
        $options = get_option( 'acf_qtranslate' );
        if ( isset( $options[ $name ] ) === true ) {
            return $options[ $name ];
        }

        return $default;
    }

    /**
     * Register the options page with the Wordpress menu
     */
    function admin_menu() {
        add_options_page( 'ACF qTranslate', 'ACF qTranslate', 'manage_options', 'acf-qtranslate', array(
            $this,
            'options_page'
        ) );
    }

    /**
     * Register settings and default fields
     */
    function admin_init() {
        register_setting( 'acf_qtranslate', 'acf_qtranslate' );

        add_settings_section(
            'qtranslatex_section',
            'qTranslate-X',
            array( $this, 'render_section_qtranslatex' ),
            'acf_qtranslate'
        );

        add_settings_field(
            'translate_standard_field_types',
            'Enable translation for Standard Field Types',
            array( $this, 'render_setting_translate_standard_field_types' ),
            'acf_qtranslate',
            'qtranslatex_section'
        );

        add_settings_field(
            'show_language_tabs',
            'Display language tabs',
            array( $this, 'render_setting_show_language_tabs' ),
            'acf_qtranslate',
            'qtranslatex_section'
        );

        add_settings_field(
            'show_on_pages',
            'Display the LSB on the following pages',
            array( $this, 'render_setting_show_on_pages' ),
            'acf_qtranslate',
            'qtranslatex_section'
        );
    }

    /**
     * Render the options page
     */
    function options_page() {
        ?>
        <form action="options.php" method="post">
            <h2>ACF qTranslate Settings</h2>
            <br>
            <?php

            settings_fields( 'acf_qtranslate' );
            do_settings_sections( 'acf_qtranslate' );
            submit_button();

            ?>
        </form>
        <?php
    }

    /**
     * Render the qTranslate-XT section
     */
    function render_section_qtranslatex() {
        ?>
        The following options represent additional functionality that is available when
        using qTranslate-XT. This functionality is off by default and must be enabled below.
        <?php
    }

    /**
     * Render setting
     */
    function render_setting_translate_standard_field_types() {
        ?>
        <input type="checkbox"
               name="acf_qtranslate[translate_standard_field_types]" <?php checked( $this->get_plugin_setting( 'translate_standard_field_types' ), 1 ); ?>
               value="1">
        <?php
    }

    /**
     * Render setting
     */
    function render_setting_show_language_tabs() {
        ?>
        <input type="checkbox"
               name="acf_qtranslate[show_language_tabs]" <?php checked( $this->get_plugin_setting( 'show_language_tabs' ), 1 ); ?>
               value="1">
        <?php
    }

    /**
     * Render setting
     */
    function render_setting_show_on_pages() {
        ?>
        <textarea name="acf_qtranslate[show_on_pages]" style="max-width:500px;width:100%;height:200px;padding-top:6px"
                  placeholder="post.php"><?= esc_html( $this->get_plugin_setting( 'show_on_pages' ) ) ?></textarea><br>
        <small>Enter each page on it's own line</small>
        <?php
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
