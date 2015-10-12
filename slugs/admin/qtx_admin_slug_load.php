<?php
if ( !defined( 'WP_ADMIN' ) ) exit;

add_action('qtranslate_admin_loadConfig', 'qtranxf_slug_admin_loadConfig');
function qtranxf_slug_admin_loadConfig() {
	global $q_config;
	$q_config['admin_sections']['slugs'] = __('Slugs', 'qtranslate');
	if($q_config['slugs']){
		require_once(QTXSLUGS_DIR.'/admin/qtx_admin_slug.php');
	}
}

add_action('qtranslate_configuration', 'qtranxf_slug_configuration');
function qtranxf_slug_configuration($request_uri){
	require_once(QTXSLUGS_DIR.'/admin/qtx_admin_slug_settings.php');
	qtranxf_slug_config($request_uri);
}
