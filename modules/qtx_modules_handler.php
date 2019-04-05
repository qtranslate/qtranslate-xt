<?php

class QTX_Modules_Handler {
	/**
	 * Retrieve the definitions of the integration modules.
	 */
	public static function get_modules_defs() {
		return array(
			array(
				'id'           => 'all-in-one-seo-pack',
				'name'         => 'All in One SEO Pack',
				'plugin'       => 'all-in-one-seo-pack/all_in_one_seo_pack.php',
				'incompatible' => 'all-in-one-seo-pack-qtranslate-x/qaioseop.php'
			),
			array(
				'id'           => 'events-made-easy',
				'name'         => 'Events Made Easy',
				'plugin'       => 'events-made-easy/events-manager.php',
				'incompatible' => 'events-made-easy-qtranslate-x/events-made-easy-qtranslate-x.php'
			),
			array(
				'id'           => 'gravity-forms',
				'name'         => 'Gravity Forms',
				'plugin'       => 'gravityforms/gravityforms.php',
				'incompatible' => 'qtranslate-support-for-gravityforms/qtranslate-support-for-gravityforms.php'
			),
			array(
				'id'           => 'woo-commerce',
				'name'         => 'WooCommerce',
				'plugin'       => 'woocommerce/woocommerce.php',
				'incompatible' => 'woocommerce-qtranslate-x/woocommerce-qtranslate-x.php'
			),
//          TODO obsolete Yoast SEO module - needs to be reviewed before re-activation, especially the JS part
//			array(
//				'id'           => 'wp-seo',
//				'name'         => 'Yoast SEO',
//				'plugin'       => 'wordpress-seo/wp-seo.php',
//				'incompatible' => 'wp-seo-qtranslate-x/wordpress-seo-qtranslate-x.php'
//			)
		);
	}

	/**
	 * Loads modules previously enabled in the options after validation for plugin integration on admin-side.
	 * Note these should be loaded before "qtranslate_init_language" is triggered.
	 *
	 * @see QTX_Admin_Modules::update_modules_option()
	 */
	public static function load_modules_enabled() {
		$def_modules  = self::get_modules_defs();
		$options_modules = get_option( 'qtranslate_modules', array());
		foreach ($def_modules as $def_module) {
			if (! array_key_exists($def_module['id'], $options_modules))
				continue;
			$options_module = $options_modules[$def_module['id']];
			if ($options_module) {
				require_once( QTRANSLATE_DIR . '/modules/' . $def_module['id'] . '/' . $def_module['id'] . '.php' );
			}
		}
	}
}
