<?php
if ( !defined( 'WP_ADMIN' ) ) exit;

//function qtranxf_slug_admin_load(){}
//qtranxf_slug_admin_load();

add_action('qtranslate_admin_loadConfig', 'qtranxf_slug_admin_loadConfig');
function qtranxf_slug_admin_loadConfig() {
	global $q_config;
	//qtranxf_dbg_log('3.qtranxf_slug_admin_loadConfig: $q_config[slugs]: ', $q_config['slugs']);
	if($q_config['slugs']){
		require_once(QTXSLUGS_DIR.'/admin/qtx_admin_slug.php');
	}
}

add_action('qtranslate_configuration_pre', 'qtranxf_slug_settings');
function qtranxf_slug_settings(){
	require_once(QTXSLUGS_DIR.'/admin/qtx_admin_slug_settings.php');
}

//add_action('qtranslate_update_settings_pre', 'qtranxf_slug_load_admin');
//function qtranxf_slug_load_admin(){
//	if(!empty($_POST['slugs'])){
//		require_once(QTXSLUGS_DIR.'/admin/qtx_admin_slug.php');
//		qtranxf_slug_update_settings_pre();
//	}
//}

add_action('qtranslate_update_settings_pre', 'qtranxf_slug_update_settings_pre');
function qtranxf_slug_update_settings_pre(){
	global $q_config;
	if(empty($_POST['slugs'])){
		remove_action('admin_head', 'qtranxf_slug_update_translations_left', 5);
		//qtranxf_dbg_log('qtranxf_slug_update_settings_pre: empty $_POST[slugs]: $q_config[slugs]: ',$q_config['slugs']);
		if(!empty($q_config['slugs'])){
			require_once(QTXSLUGS_DIR.'/admin/qtx_admin_slug_activation.php');
			qtranxf_slug_deactivate();
		}
	}else{
		add_action('qtranslate_admin_notices_plugin_conflicts','qtranxf_slug_admin_notices_plugin_conflicts');
		if(empty($q_config['slugs'])){
			//qtranxf_dbg_log('qtranxf_slug_update_settings_pre: qtranxf_slug_activate()');
			require_once(QTXSLUGS_DIR.'/admin/qtx_admin_slug_activation.php');
			qtranxf_slug_activate();
		}else{
			//qtranxf_dbg_log('qtranxf_slug_update_settings_pre: qtranxf_slug_update_settings()');
			require_once(QTXSLUGS_DIR.'/admin/qtx_admin_slug_settings.php');
			qtranxf_slug_update_settings();
		}
	}
}
