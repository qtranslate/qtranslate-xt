<?php

require_once ACF_QTRANSLATE_PLUGIN_DIR . 'src/acf_interface.php';

class acf_qtranslate_qtranslatex {

    /**
     * An ACF instance
     * @var acf_qtranslate_acf_interface
     */
    protected $acf;

    /**
     * The plugin instance
     * @var acf_qtranslate_plugin
     */
    protected $plugin;

    /**
     * Constructor
     *
     * @param acf_qtranslate_plugin $plugin
     * @param acf_qtranslate_acf_interface $acf
     */
    public function __construct( acf_qtranslate_plugin $plugin, acf_qtranslate_acf_interface $acf ) {
        $this->acf    = $acf;
        $this->plugin = $plugin;

        add_action( 'admin_head', array( $this, 'admin_head' ) );
    }

    /**
     * Add additional styles and scripts to head
     */
    public function admin_head() {
        // Hide the language tabs if they shouldn't be displayed
        $show_language_tabs = $this->plugin->get_plugin_setting( 'show_language_tabs' );
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
        $translate_standard_field_types = $this->plugin->get_plugin_setting( 'translate_standard_field_types' );
        if ( $translate_standard_field_types ) {
            ?>
            <script>
                var acf_qtranslate_translate_standard_field_types = <?= json_encode( $translate_standard_field_types ) ?>;
            </script>
            <?php
        }
    }
}
