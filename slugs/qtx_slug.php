<?php
/*
	Copyright 2014  qTranslate Team  (email : qTranslateTeam@gmail.com )

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/
if ( !defined( 'ABSPATH' ) ) exit;

define( 'QTXSLUGS_FILE', __FILE__ );
define( 'QTXSLUGS_DIR', dirname(__FILE__) );

define('QTX_URL_SLUG', 10);

{//action/filters
add_filter('qtranslate_option_config','qtranxf_slug_option_config');
function qtranxf_slug_option_config($ops){
	//qtranxf_dbg_log('1.qtranxf_slug_option_config: ');
	//$ops['front']['array']['slug'] = 'qtranxf_slug_option_default';
	$ops['front']['bool']['slugs'] = false;
	$ops['front']['array']['slugs_opt'] = array();
	//$ops['front']['array']['slugs_post_type'] = array();
	//$ops['front']['array']['slugs_taxonomy'] = array();
	return $ops;
}

add_action('qtranslate_loadConfig', 'qtranxf_slug_loadConfig');
function qtranxf_slug_loadConfig()
{
	global $q_config;
	if($q_config['slugs'] && $q_config['url_mode'] != QTX_URL_QUERY){
		add_filter('qtranslate_url_del_language','qtranxf_slug_url_del_language', 5, 2);
		add_filter('qtranslate_url_set_language_pre','qtranxf_slug_url_set_language', 5, 3);
	}
	$q_config['slugs-cache'] = array('names' => array(), 'slugs' => array());
}

function qtranxf_slug_detect_language($lang, &$urlinfo){
	global $q_config;
	//qtranxf_dbg_log('qtranxf_slug_detect_language: $urlinfo: ', $urlinfo);
	if( empty($urlinfo['wp-path'])
		|| !empty($urlinfo['language_neutral_path'])
		|| defined('WP_ADMIN')
	) return $lang;
	//qtranxf_dbg_log('qtranxf_slug_detect_language: $urlinfo: ', $urlinfo);
	$lang_slug;
	$modified_def = qtranxf_slug_path_del_language($urlinfo,$lang_slug);
	if($modified_def){
		if($q_config['url_mode'] == QTX_URL_SLUG && !empty($lang_slug)){
			$lang = $lang_slug;
			$urlinfo['lang_slug'] = $lang;
		}
		//$_SERVER['REQUEST_URI'] = $urlinfo['path-base'].$urlinfo['wp-paths'][$q_config['default_language']];
		//if(!empty($urlinfo['query'])) $_SERVER['REQUEST_URI'] .= '?'.$urlinfo['query'];
	}
	qtranxf_slug_path_set_language($urlinfo, $lang);
	if($urlinfo['wp-path'] != $urlinfo['wp-paths'][$lang]){
		if(empty($urlinfo['doredirect'])) $urlinfo['doredirect'] = 'slug';
	}
	return $lang;
}
add_filter('i18n_detect_language','qtranxf_slug_detect_language', 2, 2);

function qtranxf_slug_load($url_info){
	global $q_config, $wpdb;
	//qtranxf_dbg_log('2.qtranxf_slug_load: ');
	//qtranxf_dbg_log('2.qtranxf_slug_load: $url_info: ',$url_info);

	$wpdb->i18n_slugs = $wpdb->prefix . 'i18n_slugs';

	if($url_info['doing_front_end']) {
		if(!$q_config['slugs']) return;
		require_once(QTXSLUGS_DIR.'/front/qtx_front_slug.php');
	}else{
		require_once(QTXSLUGS_DIR.'/admin/qtx_admin_slug_load.php');
	}
}
add_action('qtranslate_load_front_admin','qtranxf_slug_load');

function qtranxf_slug_path_del_language(&$urlinfo, &$lang=null){
	global $q_config;
	$slugs_org = qtranxf_slug_split_path($urlinfo['wp-path']);
	$urlinfo['wp-path-slugs'] = array();//slugs of the default language
	$slugs = &$urlinfo['wp-path-slugs'];
	$modified = false;
	foreach($slugs_org as $k => $slug_org){
		if(empty($slug_org)){
			$slugs[] = '';
			continue;
		}
		$info = qtranxf_slug_translation($slug_org);
		if(empty($info['name'])){
			$slugs[] = $slug_org;
			continue;
		}
		$lang = $info['lang'];
		$slugs[] = $info['name'];
		$modified = true;
	}
	$urlinfo['wp-paths'] = array();
	$urlinfo['wp-paths'][$q_config['default_language']] = implode('/', $slugs);
	//qtranxf_dbg_log('qtranxf_slug_path_del_language: '.($modified?'modified':'unmodified').': $urlinfo: ', $urlinfo);
	return $modified;
}


function qtranxf_slug_path_set_language(&$urlinfo, $lang){
	global $q_config;
	if($lang == $q_config['default_language']) return false;
	//if(!isset($urlinfo['wp-path-slugs'])){
	//	//qtranxf_dbg_log('qtranxf_slug_path_set_language('.$lang.'): no wp-path-slugs: $urlinfo: ', $urlinfo);
	//}
	$slugs = array();
	$modified = false;
	foreach($urlinfo['wp-path-slugs'] as $k => $slug_def){
		if(empty($slug_def)){
			$slugs[] = '';
			continue;
		}
		$slug_lng = qtranxf_slug_translate_name($slug_def, $lang);
		if(empty($slug_lng)){
			$slugs[] = $slug_def;
		}else{
			$slugs[] = $slug_lng;
			$modified = true;
		}
	}
	$urlinfo['wp-paths'][$lang] = implode('/', $slugs);
	//qtranxf_dbg_log('qtranxf_slug_path_set_language('.$lang.'): '.($modified?'modified':'unmodified').': $urlinfo: ', $urlinfo);
	return $modified;
}

function qtranxf_slug_url_del_language($urlinfo, $url_mode=null){
	if(!empty($urlinfo['wp-path']))
		qtranxf_slug_path_del_language($urlinfo);
	return $urlinfo;
}

function qtranxf_slug_url_set_language($urlinfo, $lang, $url_mode=null){
	if(!empty($urlinfo['wp-path'])){
		//if(!isset($urlinfo['wp-paths']) || !isset($urlinfo['wp-path-slugs'])){
		//	//qtranxf_dbg_log('qtranxf_slug_url_set_language('.$lang.'): no wp-paths: $urlinfo: ', $urlinfo);
		//}
		if(!isset($urlinfo['wp-paths'][$lang])){
			qtranxf_slug_path_set_language($urlinfo,$lang);
		}
		$urlinfo['wp-path'] = $urlinfo['wp-paths'][$lang];
		//qtranxf_dbg_log('qtranxf_slug_url_set_language('.$lang.'): $urlinfo: ', $urlinfo);
	}
	return $urlinfo;
}

}//action/filters

{// activation/deactivation hooks
if(is_admin()){

	add_action('qtranslate_activation_hook','qtranxf_slug_activation_hook');
	function qtranxf_slug_activation_hook(){
		require_once(QTXSLUGS_DIR.'/admin/qtx_admin_slug_activation.php');
		qtranxf_slug_activate_plugin();
	}

	add_action('qtranslate_deactivation_hook','qtranxf_slug_deactivation_hook');
	function qtranxf_slug_deactivation_hook(){
		require_once(QTXSLUGS_DIR.'/admin/qtx_admin_slug_activation.php');
		qtranxf_slug_deactivate_plugin();
	}
}
}// activation/deactivation hooks

{//utilities

function qtranxf_slug_get_translations($name) {
	global $q_config, $wpdb;
	if(isset($q_config['slugs-cache']['names'][$name])) return $q_config['slugs-cache']['names'][$name];
	$sql = 'SELECT slug, lang FROM '.$wpdb->prefix.'i18n_slugs WHERE name = %s';
	$rows = $wpdb->get_results( $wpdb->prepare( $sql, $name ) );
	$q_config['slugs-cache']['names'][$name] = array();
	$names = &$q_config['slugs-cache']['names'][$name];
	$slugs = &$q_config['slugs-cache']['slugs'];
	foreach($rows as $row){
		$names[$row->lang] = $row->slug;
		$slugs[$row->slug] = array('lang' => $row->lang, 'name' => $name);
	}
	return $names;
}

function qtranxf_slug_translation($slug) {
	global $q_config, $wpdb;
	if(isset($q_config['slugs-cache']['slugs'][$slug])) return $q_config['slugs-cache']['slugs'][$slug];
	$sql = 'SELECT lang, name FROM '.$wpdb->prefix.'i18n_slugs WHERE slug = %s';
	$row = $wpdb->get_row( $wpdb->prepare( $sql, $slug ), ARRAY_A );
	//qtranxf_dbg_log('qtranxf_slug_get_name: '.$slug.' => ', $row);
	return $q_config['slugs-cache']['slugs'][$slug] = is_array($row) ? $row : array();
}

function qtranxf_slug_get_name($slug) {
	$info = qtranxf_slug_translation($slug);
	if(isset($info['name'])) return $info['name'];
	//qtranxf_dbg_log('qtranxf_slug_get_name: '.$slug.' => ', $info);
	return null;
}

function qtranxf_slug_translate_name($name,$lang) {
	$slugs = qtranxf_slug_get_translations($name);
	//qtranxf_dbg_log('qtranxf_slug_translate_name('.$name.', '.$lang.'): slugs: ', $slugs);
	if(isset($slugs[$lang])) return $slugs[$lang];
	return null;
}

function qtranxf_slug_translate($slug, $lang) {
	$name = qtranxf_slug_get_name($slug);
	if(!$name) return $slug;
	$lang_slug = qtranxf_slug_translate_name($name,$lang);
	if($lang_slug) return $lang_slug;
	return $slug;
}

function qtranxf_slug_split_path($path) {
	$path = rawurlencode(urldecode($path));
	$path = str_replace('%2F', '/', $path);
	$path = str_replace('%20', ' ', $path);
	$slugs = explode('/', $path);
	//foreach($slugs as $k => $v){
	//	if(empty($v)) unset($slugs[$k]);
	//}
	return $slugs;
}

function qtranxf_slug_translate_path($path, $lang) {
	$slugs = qtranxf_slug_split_path($path);
	//qtranxf_dbg_log('qtranxf_slug_translate_path('.$path.', '.$lang.'): $slugs: ', $slugs);
	foreach($slugs as $k => $slug){
		if(empty($slug)) continue;
		$slugs[$k] = qtranxf_slug_translate($slug, $lang);
	}
	$path = implode('/', $slugs);
	//qtranxf_dbg_log('qtranxf_slug_translate_path: $path: ', $path);
	return $path;
}

}//utilities
