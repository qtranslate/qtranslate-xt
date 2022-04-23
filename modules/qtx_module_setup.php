<?php

/**
 * Static setup data for the built-in modules.
 *
 * This is internal, not meant to be accessed directly.
 */
class QTX_Module_Setup {
    /**
     * Retrieve the setup of the built-in modules.
     *
     * Each module is defined by:
     * - id: key used to identify the module, also used in options
     * - name: for user display
     * - plugin (mixed): WP identifier of plugin to be integrated, or array of plugin identifiers
     * - incompatible: WP identifier of plugin incompatible with the module
     *
     * @see QTX_Module these fields must match the class members.
     * @return array[] ordered by module name
     */
    protected static function get_module_setup() {
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
