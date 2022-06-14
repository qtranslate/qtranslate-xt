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
            if ( version_compare( acf()->settings['version'], '5.0.0' ) >= 0 ) {
                require_once __DIR__ . '/qtx_module_acf_register.php';
                new QTX_Module_Acf_Register();

                if ( is_admin() ) {
                    require_once __DIR__ . '/qtx_module_acf_admin.php';
                    new QTX_Module_Acf_Admin();
                }
            }
            $plugin_loaded = true;
        }
    }
}
