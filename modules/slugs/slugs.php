<?php

if ( ! defined( "QTS_PREFIX" ) ) {
    define( "QTS_PREFIX", '_qts_' );
}
if ( ! defined( "QTS_META_PREFIX" ) ) {
    define( "QTS_META_PREFIX", QTS_PREFIX . 'slug_' );
}

// TODO: remove temporary rename of legacy option for master, rely on plugin activation in next release.
require_once( QTRANSLATE_DIR . '/admin/qtx_admin_options.php' );
qtranxf_migrate_legacy_option( 'qts_options', QTX_OPTIONS_MODULE_SLUGS, false );

// Init the module

include_once( dirname( __FILE__ ) . '/includes/class-qtranslate-slug.php' );
include_once( dirname( __FILE__ ) . '/includes/qtranslate-slug-utils.php' );

global $qtranslate_slug;
$qtranslate_slug = new QtranslateSlug();


if ( is_admin() ) {
    include_once( dirname( __FILE__ ) . '/includes/qtranslate-slug-admin.php' );
}

// plugin init
add_action( 'plugins_loaded', array( $qtranslate_slug, 'init' ) );
add_filter( 'qtranslate_convert_url', 'qts_convert_url', 10, 2 );
