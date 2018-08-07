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

{//action/filters
add_filter('qtranslate_option_config','qtranxf_slug_option_config');
function qtranxf_slug_option_config($ops){
	//$ops['front']['array']['slug'] = 'qtranxf_slug_option_default';
	$ops['front']['bool']['slugs'] = false;
	//$ops['front']['array']['slugs_post_type'] = array();
	//$ops['front']['array']['slugs_taxonomy'] = array();
	return $ops;
}

/* function qtranxf_slug_option_default()
{
	$ops = array();
	return $ops;
}
*/

function qtranxf_slug_load($url_info){
	global $q_config, $wpdb;

	$wpdb->i18n_slugs = $wpdb->prefix . 'i18n_slugs';

	if($url_info['doing_front_end']) {
		if(!$q_config['slugs']) return;
		require_once(QTXSLUGS_DIR.'/front/qtx_front_slug.php');
	}else{
		require_once(QTXSLUGS_DIR.'/admin/qtx_admin_slug_load.php');
	}
	if($q_config['slugs']){
		add_filter('qtranslate_url_set_language','qtranxf_slug_url_set_language', 5, 3);
	}
}
add_action('qtranslate_init_language','qtranxf_slug_load');

function qtranxf_slug_url_set_language($urlinfo, $lang, $url_mode){
	if(!empty($urlinfo['wp-path'])){
		$urlinfo['wp-path'] = qtranxf_slug_translate_path($urlinfo['wp-path'],$lang);
	}
	return $urlinfo;
}

}//action/filters

{// activation/deactivation hooks
if(is_admin()){

	add_action('qtranslate_activation_hook','qtranxf_slug_activation_hook');
	function qtranxf_slug_activation_hook(){
		require_once(QTXSLUGS_DIR.'/admin/qtx_admin_slug_activation.php');
		qtranxf_slug_activate();
	}

	add_action('qtranslate_deactivation_hook','qtranxf_slug_deactivation_hook');
	function qtranxf_slug_deactivation_hook(){
		require_once(QTXSLUGS_DIR.'/admin/qtx_admin_slug_activation.php');
		qtranxf_slug_deactivate();
	}
}
}// activation/deactivation hooks

{//utilities

function qtranxf_slug_get_name($slug) {
	global $wpdb;
	$sql = 'SELECT name FROM '.$wpdb->prefix.'i18n_slugs WHERE slug = %s';
	$row = $wpdb->get_row( $wpdb->prepare( $sql, $slug ) );
	//qtranxf_dbg_log('qtranxf_slug_get_name: '.$slug.' => ', $row);
	if($row) return $row->name;
	return null;
}

function qtranxf_slug_translate_name($lang, $name) {
	global $wpdb;
	$sql = 'SELECT slug FROM '.$wpdb->prefix.'i18n_slugs WHERE lang = %s AND name = %s';
	$slug = $wpdb->get_var( $wpdb->prepare( $sql, $lang, $name ) );
	//todo cache
	return $slug;
}

function qtranxf_slug_translate($slug, $lang) {
	$name = qtranxf_slug_get_name($slug);
	if(!$name) return $slug;
	$slug_lang = qtranxf_slug_translate_name($lang, $name);
	if($slug_lang) return $slug_lang;
	return $slug;
}

function qtranxf_slug_translate_path($path, $lang) {
	$path = rawurlencode(urldecode($path));
	$path = str_replace('%2F', '/', $path);
	$path = str_replace('%20', ' ', $path);
	$slugs = explode('/', $path);
	//qtranxf_dbg_log('qtranxf_slug_translate_path('.$path.', '.$lang.'): $slugs: ', $slugs);
	foreach($slugs as $k => $slug){
		if(empty($slug)) continue;
		$slugs[$k] = qtranxf_slug_translate($slug, $lang);
	}
	$path = implode('/', $slugs);
	//qtranxf_dbg_log('qtranxf_slug_translate_path: $path: ', $path);
	return $path;
}

/**
 * Helper that gets a base slug stored in options
 * 
 * @param string $name of extra permastruct
 * @return string base slug for 'post_type' and 'language' or false
 *
 * @since 1.0
 */
function qtranxf_slug_get_base($lang, $name) {
	global $q_config;

	if ( taxonomy_exists($name) ) {
		$type = 'taxonomy';
	} else if ( post_type_exists($name) ) {
		$type = 'post_type';
	} else {
		return false;
	}

	return isset($q_config['slugs_'.$type][$name][$lang]) ? $q_config['slugs_'.$type][$name][$lang] : false;
}

}//utilities
