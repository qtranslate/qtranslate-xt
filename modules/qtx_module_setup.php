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
     * - id (required): key used to identify the module, also used in options
     * - name (required): for user display
     * - plugins (optional, array): WP identifier of plugin to be integrated, or array of plugin identifiers
     * - incompatible (optional): WP identifier of plugin incompatible with the module
     * - has_settings (optional, bool): for specific admin settings
     *
     * @return array[] ordered by module name
     * @see QTX_Module these fields must match the class members.
     */
    protected static function get_module_setup() {
        return [
            [
                'id'           => 'acf',
                'name'         => 'ACF',
                'plugins'      => [ 'advanced-custom-fields/acf.php', 'advanced-custom-fields-pro/acf.php' ],
                'incompatible' => 'acf-qtranslate/acf-qtranslate.php',
            ],
            [
                'id'           => 'all-in-one-seo-pack',
                'name'         => 'All in One SEO Pack',
                'plugins'      => [
                    'all-in-one-seo-pack/all_in_one_seo_pack.php',
                    'all-in-one-seo-pack-pro/all_in_one_seo_pack.php'
                ],
                'incompatible' => 'all-in-one-seo-pack-qtranslate-x/qaioseop.php',
            ],
            [
                'id'           => 'events-made-easy',
                'name'         => 'Events Made Easy',
                'plugin'       => [ 'events-made-easy/events-manager.php' ],
                'incompatible' => 'events-made-easy-qtranslate-x/events-made-easy-qtranslate-x.php',
            ],
            [
                'id'      => 'jetpack',
                'name'    => 'Jetpack',
                'plugins' => [ 'jetpack/jetpack.php' ],
            ],
            [
                'id'      => 'google-site-kit',
                'name'    => 'Google Site Kit',
                'plugins' => [ 'google-site-kit/google-site-kit.php' ],
            ],
            [
                'id'           => 'gravity-forms',
                'name'         => 'Gravity Forms',
                'plugins'      => [ 'gravityforms/gravityforms.php' ],
                'incompatible' => 'qtranslate-support-for-gravityforms/qtranslate-support-for-gravityforms.php',
            ],
            [
                'id'           => 'woo-commerce',
                'name'         => 'WooCommerce',
                'plugins'      => [ 'woocommerce/woocommerce.php' ],
                'incompatible' => 'woocommerce-qtranslate-x/woocommerce-qtranslate-x.php',
            ],
            [
                'id'           => 'wp-seo',
                'name'         => 'Yoast SEO',
                'plugins'      => [ 'wordpress-seo/wp-seo.php' ],
                'incompatible' => 'wp-seo-qtranslate-x/wordpress-seo-qtranslate-x.php',
            ],
            [
                'id'           => 'slugs',
                'name'         => __( 'Slugs translation', 'qtranslate' ) . sprintf( ' (%s)', __( 'experimental' ) ),
                'incompatible' => 'qtranslate-slug/qtranslate-slug.php',
                'has_settings' => true,
            ]
        ];
    }
}
