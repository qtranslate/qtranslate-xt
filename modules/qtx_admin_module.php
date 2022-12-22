<?php

/**
 * Static definition of a built-in module.
 *
 * This provides only the basic structure, not the module logic or states.
 */
class QTX_Admin_Module {
    /**
     * @var string Internal id.
     */
    public $id;

    /**
     * @var string Name for display.
     */
    public $name;

    /**
     * Array of required plugin(s) defined in the WP format (directory/file.php).
     * If this list is not empty, the module requires at least one of the plugin(s) to be activated.
     *
     * @var string[]
     */
    public $plugins;

    /**
     * Incompatible plugin in the WP format, only one or zero supported.
     * If not empty, the module cannot be activated is this plugin is active.
     *
     * @var string|null
     */
    public $incompatible;

    /**
     * @var bool A module can have specific admin settings.
     */
    public $has_settings;

    /**
     * Constructor from fields array.
     *
     * @param array[] $fields
     *
     * @see QTX_Admin_Module
     */
    function __construct( $fields ) {
        $this->id           = $fields['id'];
        $this->name         = $fields['name'];
        $this->plugins      = isset( $fields['plugins'] ) ? $fields['plugins'] : array();
        $this->incompatible = isset( $fields['incompatible'] ) ? $fields['incompatible'] : null;
        $this->has_settings = isset( $fields['has_settings'] ) ? $fields['has_settings'] : false;
    }

    /**
     * Retrieve the default settings for the "admin enabled state" (checkbox).
     *
     * @return bool
     */
    function is_default_enabled() {
        return ! empty( $this->plugins );
    }

    /**
     * Retrieve the raw setup of the built-in modules.
     *
     * This structure is internal, but these fields must match the class members:
     * - id (required): key used to identify the module, also used in options
     * - name (required): for user display
     * - plugins (optional, array): WP identifier of plugin to be integrated, or array of plugin identifiers
     * - incompatible (optional): WP identifier of plugin incompatible with the module
     * - has_settings (optional, bool): for specific admin settings
     *
     * @return array[] ordered by module name
     */
    protected static function get_builtin_setup() {
        return [
            [
                'id'           => 'acf',
                'name'         => 'ACF',
                'plugins'      => [ 'advanced-custom-fields/acf.php', 'advanced-custom-fields-pro/acf.php' ],
                'incompatible' => 'acf-qtranslate/acf-qtranslate.php',
                'has_settings' => true,
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
                'plugins'      => [ 'events-made-easy/events-manager.php' ],
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
                'name'         => 'Yoast SEO (degraded)',
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

    /**
     * Retrieve the module definitions.
     *
     * @return QTX_Admin_Module[] ordered by name
     */
    public static function get_modules() {
        static $modules;
        if ( isset( $modules ) ) {
            return $modules;
        }
        $modules = [];
        foreach ( self::get_builtin_setup() as $setup ) {
            $modules[] = new QTX_Admin_Module( $setup );
        }

        return $modules;
    }
}
