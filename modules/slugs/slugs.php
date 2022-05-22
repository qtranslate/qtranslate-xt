<?php
const QTX_SLUGS_META_PREFIX = 'qtranslate_slug_';   // The language code is appended.

include_once( dirname( __FILE__ ) . '/includes/class-qtranslate-slug.php' );
include_once( dirname( __FILE__ ) . '/includes/qtranslate-slug-utils.php' );

global $qtranslate_slug;
$qtranslate_slug = new QtranslateSlug();

if ( is_admin() ) {
    include_once( dirname( __FILE__ ) . '/includes/qtranslate-slug-admin.php' );
}

add_action( 'plugins_loaded', array( $qtranslate_slug, 'init' ) );
add_filter( 'qtranslate_convert_url', 'qts_convert_url', 10, 2 );
