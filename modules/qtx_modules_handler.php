<?php

define( 'QTX_MODULE_STATE_UNDEFINED', 0 );
define( 'QTX_MODULE_STATE_ACTIVE', 1 );
define( 'QTX_MODULE_STATE_INACTIVE', 2 );
define( 'QTX_MODULE_STATE_BLOCKED', 3 );

class QTX_Modules_Handler {
    /**
     * Get the modules previously activated in the options after validation for plugin integration on admin-side.
     * Note these should be loaded before "qtranslate_init_language" is triggered.
     *
     * @see QTX_Admin_Modules::update_modules_state()
     * @array Module defs.
     */
    public static function get_active_modules() {
        $options_modules = get_option( 'qtranslate_modules_state', array() );
        if ( ! is_array( $options_modules ) ) {
            return array();
        }

        $active_modules = array();
        $def_modules    = self::get_modules_defs();
        foreach ( $def_modules as $def_module ) {
            if ( ! array_key_exists( $def_module['id'], $options_modules ) ) {
                continue;
            }
            $state = $options_modules[ $def_module['id'] ];
            if ( $state === QTX_MODULE_STATE_ACTIVE ) {
                array_push( $active_modules, $def_module );
            }
        }

        return $active_modules;
    }

    /**
     * Check if a module is active.
     *
     * @param string $module_id
     *
     * @bool true if module active.
     */
    public static function is_module_active( $module_id ) {
        $options_modules = get_option( 'qtranslate_modules_state', array() );

        return isset( $options_modules[ $module_id ] ) && $options_modules[ $module_id ] === QTX_MODULE_STATE_ACTIVE;
    }

    /**
     * Loads modules previously activated in the options after validation for plugin integration on admin-side.
     * Note these should be loaded before "qtranslate_init_language" is triggered.
     *
     * @see QTX_Admin_Modules::update_modules_state()
     */
    public static function load_active_modules() {
        $def_modules = self::get_active_modules();
        foreach ( $def_modules as $def_module ) {
            include_once( QTRANSLATE_DIR . '/modules/' . $def_module['id'] . '/' . $def_module['id'] . '.php' );
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
                'id'           => 'acf',
                'name'         => 'ACF',
                'plugin'       => array( 'advanced-custom-fields/acf.php', 'advanced-custom-fields-pro/acf.php' ),
                'incompatible' => 'acf-qtranslate/acf-qtranslate.php',
            ),
            array(
                'id'           => 'all-in-one-seo-pack',
                'name'         => 'All in One SEO Pack',
                'plugin'       => array(
                    'all-in-one-seo-pack/all_in_one_seo_pack.php',
                    'all-in-one-seo-pack-pro/all_in_one_seo_pack.php'
                ),
                'incompatible' => 'all-in-one-seo-pack-qtranslate-x/qaioseop.php',
            ),
            array(
                'id'           => 'events-made-easy',
                'name'         => 'Events Made Easy',
                'plugin'       => 'events-made-easy/events-manager.php',
                'incompatible' => 'events-made-easy-qtranslate-x/events-made-easy-qtranslate-x.php',
            ),
            array(
                'id'     => 'jetpack',
                'name'   => 'Jetpack',
                'plugin' => 'jetpack/jetpack.php',
            ),
            array(
                'id'     => 'google-site-kit',
                'name'   => 'Google Site Kit',
                'plugin' => 'google-site-kit/google-site-kit.php',
            ),
            array(
                'id'           => 'gravity-forms',
                'name'         => 'Gravity Forms',
                'plugin'       => 'gravityforms/gravityforms.php',
                'incompatible' => 'qtranslate-support-for-gravityforms/qtranslate-support-for-gravityforms.php',
            ),
            array(
                'id'           => 'woo-commerce',
                'name'         => 'WooCommerce',
                'plugin'       => 'woocommerce/woocommerce.php',
                'incompatible' => 'woocommerce-qtranslate-x/woocommerce-qtranslate-x.php',
            ),
            array(
                'id'           => 'wp-seo',
                'name'         => 'Yoast SEO',
                'plugin'       => 'wordpress-seo/wp-seo.php',
                'incompatible' => 'wp-seo-qtranslate-x/wordpress-seo-qtranslate-x.php',
            ),
            array(
                'id'           => 'slugs',
                'name'         => __( 'Slugs translation', 'qtranslate' ) . sprintf( ' (%s)', __( 'experimental' ) ),
                'plugin'       => true,
                'incompatible' => 'qtranslate-slug/qtranslate-slug.php',
                'has_settings' => true,
            )
        );
    }
}
