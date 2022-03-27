<?php

define( 'QTX_MODULE_STATUS_UNDEFINED', 0 );
define( 'QTX_MODULE_STATUS_ACTIVE', 1 );
define( 'QTX_MODULE_STATUS_INACTIVE', 2 );
define( 'QTX_MODULE_STATUS_BLOCKED', 3 );

class QTX_Modules_Handler {
    /**
     * Loads modules previously enabled in the options after validation for plugin integration on admin-side.
     * Note these should be loaded before "qtranslate_init_language" is triggered.
     *
     * @see QTX_Admin_Modules::update_modules_status()
     */
    public static function load_modules_enabled() {
        $def_modules     = self::get_modules_defs();
        $options_modules = get_option( 'qtranslate_modules', array() );
        if ( ! is_array( $options_modules ) ) {
            return null;
        }

        foreach ( $def_modules as $def_module ) {
            if ( ! array_key_exists( $def_module['id'], $options_modules ) ) {
                continue;
            }
            $module_status = $options_modules[ $def_module['id'] ];
            if ( $module_status === QTX_MODULE_STATUS_ACTIVE ) {
                include_once( QTRANSLATE_DIR . '/modules/' . $def_module['id'] . '/' . $def_module['id'] . '.php' );
            }
        }
    }

    public static function update_manual_enabled_modules() {
        global $q_config;
        $options_modules = get_option( 'qtranslate_modules', array() );
        $changed         = false;
        foreach ( $q_config['ma_module_enabled'] as $module_id => $module_enabled ) {
            if ( $module_enabled && $options_modules[ $module_id ] != QTX_MODULE_STATUS_ACTIVE ) {
                $options_modules[ $module_id ] = QTX_MODULE_STATUS_ACTIVE;
                $changed                       = true;
            } else if ( ! $module_enabled && $options_modules[ $module_id ] == QTX_MODULE_STATUS_ACTIVE ) {
                $options_modules[ $module_id ] = QTX_MODULE_STATUS_INACTIVE;
                $changed                       = true;
            }
        }

        if ( $changed ) {
            update_option( 'qtranslate_modules', $options_modules );
            self::load_modules_enabled();
            do_action( 'qtx_ma_modules_updated' );
        }
    }

    /**
     * Retrieve the definitions of the built-in integration modules.
     * Each module is defined by:
     * - id: key used to identify the module, also used in options
     * - name: for user display
     * - plugin (mixed): WP identifier of plugin to be integrated, or array of plugin identifiers
     * - incompatible: WP identifier of plugin incompatible with the module
     *
     * @return array ordered by name
     */
    public static function get_modules_defs() {
        return array(
            array(
                'id'                => 'acf',
                'name'              => 'ACF',
                'plugin'            => array( 'advanced-custom-fields/acf.php', 'advanced-custom-fields-pro/acf.php' ),
                'incompatible'      => 'acf-qtranslate/acf-qtranslate.php',
                'manual_activation' => false
            ),
            array(
                'id'                => 'all-in-one-seo-pack',
                'name'              => 'All in One SEO Pack',
                'plugin'            => array(
                    'all-in-one-seo-pack/all_in_one_seo_pack.php',
                    'all-in-one-seo-pack-pro/all_in_one_seo_pack.php'
                ),
                'incompatible'      => 'all-in-one-seo-pack-qtranslate-x/qaioseop.php',
                'manual_activation' => false
            ),
            array(
                'id'                => 'events-made-easy',
                'name'              => 'Events Made Easy',
                'plugin'            => 'events-made-easy/events-manager.php',
                'incompatible'      => 'events-made-easy-qtranslate-x/events-made-easy-qtranslate-x.php',
                'manual_activation' => false
            ),
            array(
                'id'                => 'jetpack',
                'name'              => 'Jetpack',
                'plugin'            => 'jetpack/jetpack.php',
                'manual_activation' => false
            ),
            array(
                'id'                => 'google-site-kit',
                'name'              => 'Google Site Kit',
                'plugin'            => 'google-site-kit/google-site-kit.php',
                'manual_activation' => false
            ),
            array(
                'id'                => 'gravity-forms',
                'name'              => 'Gravity Forms',
                'plugin'            => 'gravityforms/gravityforms.php',
                'incompatible'      => 'qtranslate-support-for-gravityforms/qtranslate-support-for-gravityforms.php',
                'manual_activation' => false
            ),
            array(
                'id'                => 'woo-commerce',
                'name'              => 'WooCommerce',
                'plugin'            => 'woocommerce/woocommerce.php',
                'incompatible'      => 'woocommerce-qtranslate-x/woocommerce-qtranslate-x.php',
                'manual_activation' => false
            ),
            array(
                'id'                => 'wp-seo',
                'name'              => 'Yoast SEO',
                'plugin'            => 'wordpress-seo/wp-seo.php',
                'incompatible'      => 'wp-seo-qtranslate-x/wordpress-seo-qtranslate-x.php',
                'manual_activation' => false
            ),
            array(
                'id'                => 'slugs',
                'name'              => __( 'Slugs translation', 'qtranslate' ) . sprintf( ' (%s)', __( 'experimental' ) ),
                'plugin'            => true,
                'incompatible'      => 'qtranslate-slug/qtranslate-slug.php',
                'manual_activation' => true,
                'ma_checkbox_text'  => __( 'Enable slugs translation.', 'qtranslate' ),
                'ma_checkbox_notes' => __( 'This activates the slug translation module. ', 'qtranslate' ) . __( 'Attention! This module is still experimental. It is subject to bugs and limitations.', 'qtranslate' )
            )
        );
    }

    public static function ma_modules_default_options() {
        $module_defs = self::get_modules_defs();
        $response    = array();
        foreach ( $module_defs as $module ) {
            if ( $module['manual_activation'] == true ) {
                $response[ $module['id'] ] = false;
            }
        }

        return $response;
    }

    public static function get_module_def_by_id( $module_id ) {
        $module_defs = self::get_modules_defs();
        $response    = array();
        foreach ( $module_defs as $module ) {
            if ( $module['id'] === $module_id ) {
                $response = $module;
                break;
            }
        }

        return $response;
    }
}
