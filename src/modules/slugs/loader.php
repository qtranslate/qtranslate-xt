<?php
const QTX_SLUGS_META_PREFIX = 'qtranslate_slug_';   // The language code is appended.

require_once __DIR__ . '/slugs.php';
require_once __DIR__ . '/utils.php';

if ( is_admin() ) {
    require_once __DIR__ . '/admin.php';
}

add_filter( 'qtranslate_convert_url', 'qtranxf_slugs_convert_url', 10, 2 );

global $qtranslate_slugs;
$qtranslate_slugs = new QTX_Module_Slugs();
$qtranslate_slugs->init();
