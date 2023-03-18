<?php
/**
 * Module: Advanced Custom Fields (ACF)
 *
 * Converted from: ACF qTranslate (https://github.com/funkjedi/acf-qtranslate)
 */

/**
 * Setup module if Advanced Custom Fields is enabled and version >= 5.0.0
 *
 * @return void
 */
function qtranxf_acf_init() {
    static $acf_loaded = false;

    if ( ! $acf_loaded && function_exists( 'acf' ) ) {
        if ( version_compare( acf()->settings['version'], '5.0.0' ) >= 0 ) {
            require_once __DIR__ . '/register.php';
            new QTX_Module_Acf_Register();

            if ( is_admin() ) {
                require_once __DIR__ . '/admin.php';
                new QTX_Module_Acf_Admin();
            }
        }
        $acf_loaded = true;
    }
}

add_action( 'after_setup_theme', 'qtranxf_acf_init', -10 );  // ACF can be delivered by a theme rather than plugin.

qtranxf_acf_init();
