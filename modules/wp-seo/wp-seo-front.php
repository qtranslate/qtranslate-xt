<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter('wpseo_opengraph_title', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage');
