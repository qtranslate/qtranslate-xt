<?php // encoding: utf-8
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

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function qtranxf_init_language() {
	global $q_config, $pagenow;
	//if(defined('QTRANS_INIT')){
	//	//qtranxf_dbg_log('qtranxf_init_language: QTRANS_INIT: url_info: ',$q_config['url_info']);
	//	return;
	//}
	//define('QTRANS_INIT',true);

	qtranxf_loadConfig();

	$q_config['cookie_enabled'] = isset($_COOKIE[QTX_COOKIE_NAME_FRONT]) || isset($_COOKIE[QTX_COOKIE_NAME_ADMIN]);

	$q_config['url_info'] = array(); //'url_info' hash is not for external use, it is subject to change at any time.
	$url_info = &$q_config['url_info'];

	if(WP_DEBUG){
		$url_info['pagenow'] = $pagenow;
		if(defined('WP_ADMIN')&&WP_ADMIN) $url_info['WP_ADMIN'] = true;
		if(defined('DOING_AJAX')) $url_info['DOING_AJAX_POST'] = $_POST;
		if(defined('DOING_CRON')) $url_info['DOING_CRON_POST'] = $_POST;
	}

	//fill $url_info the same way as _parseURL does
	$url_info['scheme'] = is_ssl() ? 'https' : 'http';
	$url_info['host'] = $_SERVER['HTTP_HOST'];

	if(empty($_SERVER['REQUEST_URI'])){
		$url_info['path'] = '';
	}else{
		$url_info['path'] = $_SERVER['REQUEST_URI'];
		$p = strpos($url_info['path'],'?');
		if($p){
			$url_info['path'] = substr($url_info['path'],0,$p);
			if(empty($_SERVER['QUERY_STRING'])){
				$url_info['query'] = substr($url_info['path'],$p+1);
			}else{
				$url_info['query'] = $_SERVER['QUERY_STRING'];
			}
			$url_info['query'] = qtranxf_sanitize_url($url_info['query']); // to prevent xss
		}
	}

	$url_info['original_url'] = $_SERVER['REQUEST_URI'];//is in use in -slug, here is for debugging purpose only

	$url_info['language'] = qtranxf_detect_language($url_info);
	//qtranxf_dbg_log('qtranxf_init_language: REQUEST_TIME_FLOAT: ',$_SERVER['REQUEST_TIME_FLOAT']);
	//qtranxf_dbg_log('qtranxf_init_language: detected: url_info: ',$url_info);

	$q_config['language'] = apply_filters('qtranslate_language', $url_info['language'], $url_info);

	if( $q_config['url_info']['doing_front_end'] && qtranxf_can_redirect() ){
		$lang = $q_config['language'];
		$url_orig = $url_info['scheme'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$url_lang = qtranxf_convertURL('',$lang);//uses $q_config['url_info'] and caches information, which will be needed later anyway.
		if(!isset($url_info['doredirect']) && $url_orig != $url_lang){
			$url_info['doredirect'] = '$url_orig != $url_lang';
		}
		if(isset($url_info['doredirect'])){
			$target = apply_filters('qtranslate_language_detect_redirect', $url_lang, $url_orig, $url_info);
			//qtranxf_dbg_log('qtranxf_init_language: doredirect to '.$lang.PHP_EOL.'urlorg:'.$url_orig.PHP_EOL.'target:'.$target);
			if($target!==false && $target != $url_orig){
				wp_redirect($target);
				//header('Location: '.$target);
				exit();
			}else{
				$url_info['doredirect'] .= ' - cancelled, because it goes to the same target.';
			}
			//qtranxf_dbg_log('qtranxf_init_language: doredirect: ',$url_info['doredirect']);
		}
	}elseif(isset($url_info['doredirect'])){
		$url_info['doredirect'] .= ' - cancelled by can_redirect';
	}
	//qtranxf_dbg_log('qtranxf_init_language: done url_info: ',$url_info);

	// fix url to prevent xss - how does this prevents xss?
	//$q_config['url_info']['url'] = qtranxf_convertURL(add_query_arg('lang',$q_config['default_language'],$q_config['url_info']['url']));

	// load plugin translations
	// since 3.2-b3 moved it here as https://codex.wordpress.org/Function_Reference/load_plugin_textdomain seem to recommend to run load_plugin_textdomain in 'plugins_loaded' action, which is this function respond to
	load_plugin_textdomain('qtranslate', false, dirname(plugin_basename( __FILE__ )).'/lang');

	if($q_config['url_info']['doing_front_end']) {
		require_once(dirname(__FILE__)."/qtranslate_frontend.php");
	}else{
		require_once(dirname(__FILE__).'/qtranslate_configuration.php');
		require_once(dirname(__FILE__).'/admin/admin_utils.php');

		// load qTranslate Services if available // disabled since 3.1
		//if(file_exists(dirname(__FILE__).'/qtranslate_services.php'))
		//	require_once(dirname(__FILE__).'/qtranslate_services.php');
	}

	qtranxf_load_option_qtrans_compatibility();

	//allow other plugins to initialize whatever they need for language
	do_action('qtranslate_init_language',$url_info);
	//qtranxf_dbg_log('done: qtranxf_init_language: url_info: ',$url_info);
}

function qtranxf_detect_language(&$url_info) {
	global $q_config;

	//$url_info['home'] = $homeinfo['path'];//not in use any more

	if(defined('WP_ADMIN')){
		$siteinfo = qtranxf_get_site_info();
		$url_info['path-base'] = $siteinfo['path'];
		$url_info['path-base-length'] = $siteinfo['path-length'];
	}else{
		$homeinfo = qtranxf_get_home_info();
		$url_info['path-base'] = $homeinfo['path'];
		$url_info['path-base-length'] = $homeinfo['path-length'];
		$url_info['doing_front_end'] = true;
	}

	$lang = qtranxf_parse_language_info($url_info);

	if( (!$lang || !isset($url_info['doing_front_end']))
		&& (defined('DOING_AJAX') || !$q_config['cookie_enabled'])
		&& isset($_SERVER['HTTP_REFERER'])
	){
		//get language from HTTP_REFERER, if needed, and detect front- vs back-end
		$http_referer = $_SERVER['HTTP_REFERER'];
		$url_info['http_referer'] = $http_referer;
		if(strpos($http_referer,'/wp-admin')!==FALSE){
			$url_info['referer_admin'] = true;
			if(!isset($url_info['doing_front_end'])) $url_info['doing_front_end'] = false;
		}else{
			$ref_info = qtranxf_parseURL($http_referer);
			if( !qtranxf_external_host($ref_info['host']) ) {
				//determine $ref_info['path-base']
				qtranxf_complete_url_info($ref_info);
				if(isset($ref_info['path-base'])){
					$url_info['internal_referer'] = true;
					//$ref_info['url'] = $ref_info['path'];
					if(!$lang || !(isset($url_info['doing_front_end']) || isset($ref_info['doing_front_end']))){
						$lang = qtranxf_parse_language_info($ref_info,true);
						if($lang === false) unset($url_info['internal_referer']);
					}
					$url_info['referer_language'] = $lang;
					if(!isset($url_info['doing_front_end']) && isset($ref_info['doing_front_end'])){
						$url_info['doing_front_end'] = $ref_info['doing_front_end'];
					}
				}
			}
			if(!$lang && $q_config['hide_default_language']
				&& isset($url_info['doing_front_end']) && $url_info['doing_front_end'] )
			{
				$lang = $q_config['default_language'];
			}
		}
	}

	//$url_info['doing_front_end'] defines if we are in front- or back-end. Why WP does not have that, or does it?
	if(!isset($url_info['doing_front_end'])) $url_info['doing_front_end'] = !defined('WP_ADMIN');

	if(!$lang) {
		if( $url_info['doing_front_end'] ){
			$lang = qtranxf_detect_language_front($url_info);
		}else{
			$lang = qtranxf_detect_language_admin($url_info);
		}
	}

	$url_info['language'] = $lang;
	$url_info['set_cookie'] = !defined('DOING_AJAX');

	/**
	 * Hook for possible other methods
	 * Set $url_info['language'] with the result
	*/
	$url_info = apply_filters('qtranslate_detect_language', $url_info);
	$lang = $url_info['language'];
	if($url_info['set_cookie']) qtranxf_set_language_cookie($lang);
	return $lang;
}

/**
 * Expects to be set before call:
 * - $url_info['host']
 * - $url_info['path']
 * - $url_info['query']
 * - $url_info['path-base']
 * - $url_info['path-base-length']
*/
function qtranxf_parse_language_info(&$url_info, $link=false) {
	global $q_config;

	qtranxf_complete_url_info_path($url_info);
	if(!isset($url_info['wp-path']))
		return false;// url is not from this WP installation

	$doredirect=false;

	if( !defined('WP_ADMIN') || $link ){
		$lang = null;
		$url_mode = $q_config['url_mode'];
		switch($url_mode) {
			case QTX_URL_PATH: // pre path
				if( !empty($url_info['wp-path']) && preg_match('!^/([a-z]{2})(/|$)!i',$url_info['wp-path'],$match)) {
					$lang = qtranxf_resolveLangCase($match[1],$doredirect);
					if($lang){
						$url_info['lang_url'] = $lang;
						$url_info['wp-path'] = substr($url_info['wp-path'],3);
						$url_info['doing_front_end'] = true;
						if(WP_DEBUG){
							$url_info['url_mode'] = 'pre-path';
						}
					}
				}
				//}
				break;
			case QTX_URL_DOMAIN: // pre domain
				if(!empty($url_info['host'])) {
					if(preg_match('#^([a-z]{2})\.#i',$url_info['host'],$match)) {
						$lang = qtranxf_resolveLangCase($match[1],$doredirect);
						if($lang){
							$url_info['lang_url'] = $lang;
							$url_info['host'] = substr($url_info['host'],3);
							$url_info['doing_front_end'] = true;
							if(WP_DEBUG){
								$url_info['url_mode'] = 'pre-domain';
							}
						}
					}
				}
				break;
			case QTX_URL_DOMAINS: // domain per language
				if(!empty($url_info['host'])){
					foreach($q_config['enabled_languages'] as $lang){//todo should have hash host->lang
						if(!isset($q_config['domains'][$lang])) continue;
						if($q_config['domains'][$lang] != $url_info['host']) continue;
						$url_info['lang_url'] = $lang;
						if($lang != $q_config['default_language'] || strpos(get_option('siteurl'),$url_info['host']) === FALSE){
							$url_info['doing_front_end'] = true;
						}
						if(WP_DEBUG){
							$url_info['url_mode'] = 'per-domain';
						}
						break;
					}
				}
				break;
			default:
				/**
				 * Hook for possible other methods
				 * Set, if applicable:
				 * $url_info['lang_url']
				 * $url_info['doing_front_end']
				 * $url_info['path'] - convert to language neutral or default
				*/
				$url_info = apply_filters('qtranslate_parse_language_info_mode', $url_info, $q_config['url_mode']);
				if(isset($url_info['lang_url'])) $lang = $url_info['lang_url'];
				break;
		}
	}

	$lang = null;
	if(!$link){
		if(isset($_GET['lang'])){
			$lang = qtranxf_resolveLangCase($_GET['lang'],$doredirect);
			if($lang) $url_info['lang_query_get'] = $lang;
		}else if(isset($_POST['lang'])){
			$lang = qtranxf_resolveLangCase($_POST['lang'],$doredirect);
			if($lang) $url_info['lang_query_post'] = $lang;//todo excessive?
		}
	}elseif( !empty($url_info['query']) && preg_match('/(&|&amp;|&#038;|\?)lang=([a-z]{2})/i',$url_info['query'],$match) ) {
		$lang = qtranxf_resolveLangCase($match[2],$doredirect);
		if($lang) $url_info['lang_query_link'] = $lang;//todo excessive?
	}

	if($lang){
		$url_info['lang_query'] = $lang;
		qtranxf_del_query_arg($url_info['query'],'lang');
		if(isset($url_info['lang_url'])){
			if($lang !== $lang_url) $doredirect=true;
		}elseif(!defined('WP_ADMIN')){
			if( $q_config['url_mode'] != QTX_URL_QUERY ) $doredirect=true;
		}
	}else if(isset($url_info['lang_url'])){
		$lang = $url_info['lang_url'];
		if($q_config['hide_default_language'] && $lang == $q_config['default_language']) $doredirect=true;
	}

	if($lang) $url_info['language'] = $lang;

	if($doredirect){
		$url_info['doredirect'] = 'detected in parse_language_info';
	}

	if(!isset($url_info['doing_front_end'])){
		$language_neutral_path = qtranxf_language_neutral_path($url_info['wp-path']);
		$url_info['language_neutral_path'] = $language_neutral_path;
		if(!$language_neutral_path){
			$url_info['doing_front_end'] = true;
		}
	}

	/**
	 * Hook for possible other methods
	 * Set $url_info['language'] with the result
	*/
	$url_info = apply_filters('qtranslate_parse_language_info', $url_info, $link);//slug?

	if(isset($url_info['language'])) $lang = $url_info['language'];
	return $lang;
}

function qtranxf_detect_language_admin(&$url_info) {
	require_once(dirname(__FILE__).'/admin/admin_utils.php');
	$url_info = apply_filters('qtranslate_detect_admin_language',$url_info);
	return $url_info['lang_admin'];
}

function qtranxf_detect_language_front(&$url_info) {
	global $q_config;
	//assert($url_info['doing_front_end']);
	while(true){
		if( isset($_COOKIE[QTX_COOKIE_NAME_FRONT]) ){
			$cs=null;
			$lang=qtranxf_resolveLangCase($_COOKIE[QTX_COOKIE_NAME_FRONT],$cs);
			$url_info['lang_cookie_front'] = $lang;
			if($lang) break;
		}

		if( $q_config['detect_browser_language']
			&& ( !isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'],$url_info['host'])===FALSE )//external referrer or no referrer
			&& ( empty($url_info['wp-path']) || $url_info['wp-path'] == '/' )// home page is requested
		){
			$lang=qtranxf_http_negotiate_language();
			$url_info['lang_browser'] = $lang;
			if($lang) break;
		}

		$lang = $q_config['default_language'];
		break;
	}
	if( !isset($url_info['doredirect'])
		&& (!$q_config['hide_default_language'] || $lang != $q_config['default_language'])
		//&& !$url_info['language_neutral_path']//already so
	){
		$url_info['doredirect']='language needs to be shown in url';
	}
	return $lang;
}

function qtranxf_setcookie_language($lang, $cookie_name, $cookie_path, $cookie_domain = NULL, $secure = false)
{
	//qtranxf_dbg_log('qtranxf_setcookie_language: lang='.$lang.'; cookie_name='.$cookie_name.'; cookie_path='.$cookie_path);
	setcookie($cookie_name, $lang, time()+31536000, $cookie_path, $cookie_domain, $secure);//one year
	//two weeks 1209600
}

function qtranxf_set_language_cookie($lang)
{
	global $q_config;
	if(defined('WP_ADMIN')){
		qtranxf_setcookie_language( $lang, QTX_COOKIE_NAME_ADMIN, ADMIN_COOKIE_PATH );
	}elseif(!$q_config['disable_client_cookies']){
		qtranxf_setcookie_language( $lang, QTX_COOKIE_NAME_FRONT, COOKIEPATH, NULL, $q_config['use_secure_cookie'] );
	}
}

function qtranxf_get_browser_language(){
	//qtranxf_dbg_log('qtranxf_get_browser_language: HTTP_ACCEPT_LANGUAGE:',$_SERVER["HTTP_ACCEPT_LANGUAGE"]);
	if(!isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) return null;
	if(!preg_match_all("#([^;,]+)(;[^,0-9]*([0-9\.]+)[^,]*)?#i",$_SERVER["HTTP_ACCEPT_LANGUAGE"], $matches, PREG_SET_ORDER)) return null;
	$prefered_languages = array();
	$priority = 1.0;
	foreach($matches as $match) {
		if(!isset($match[3])) {
			$pr = $priority;
			$priority -= 0.001;
		} else {
			$pr = floatval($match[3]);
		}
		$prefered_languages[$match[1]] = $pr;
	}
	arsort($prefered_languages, SORT_NUMERIC);
	//qtranxf_dbg_echo('qtranxf_get_browser_language: prefered_languages:',$prefered_languages);
	foreach($prefered_languages as $language => $priority) {
		if(strlen($language)>2) $language = substr($language,0,2);
		if(qtranxf_isEnabled($language))
			return $language;
	}
	return null;
}

function qtranxf_http_negotiate_language(){
	global $q_config;
	if(function_exists('http_negotiate_language')){
		$supported=array();
		$supported[]=str_replace('_','-',$q_config['locale'][$q_config['default_language']]);//needs to be the first
		foreach($q_config['enabled_languages'] as $lang){
			if($lang == $q_config['default_language']) continue;
			$supported[]=str_replace('_','-',$q_config['locale'][$lang]);
		}
		$locale_negotiated = http_negotiate_language($supported);
		//since 3.2-b3 added search, since locale may be different from two-letter code and not even started with language code.
		foreach($q_config['enabled_languages'] as $lang){
			$locale = str_replace('_','-',$q_config['locale'][$lang]);
			if($locale == $locale_negotiated)
				return $lang;
		}
	}else{
		return qtranxf_get_browser_language();
	}
	return null;
}

function qtranxf_resolveLangCase($lang,&$caseredirect)
{
	if(qtranxf_isEnabled($lang)) return $lang;
	$lng=strtolower($lang);
	if(qtranxf_isEnabled($lng)){
		$caseredirect=true;
		return $lng;
	}
	$lng=strtoupper($lang);
	if(qtranxf_isEnabled($lng)){
		$caseredirect=true;
		return $lng;
	}
	return FALSE;
}

function qtranxf_load_option_qtrans_compatibility(){
	global $q_config;
	qtranxf_load_option_bool('qtrans_compatibility');
	$q_config['qtrans_compatibility'] = apply_filters('qtranslate_compatibility', $q_config['qtrans_compatibility']);
	if( !isset($q_config['qtrans_compatibility']) || !$q_config['qtrans_compatibility'] ) return;
	require_once(dirname(__FILE__).'/qtranslate_compatibility.php');
}

function qtranxf_init() {
	global $q_config;

	do_action('qtranslate_init_begin');

/*
	// Check for WP Secret Key Mismatch
	global $wp_default_secret_key;
	if(strpos($q_config['url_info']['url'],'wp-login.php')!==false && defined('AUTH_KEY') && isset($wp_default_secret_key) && $wp_default_secret_key != AUTH_KEY) {
		global $error;
		$error = __('Your $wp_default_secret_key is mismatching with your AUTH_KEY. This might cause you not to be able to login anymore.', 'qtranslate');
	}
*/

	// load plugin translations
	// since 3.2-b3 moved to qtranxf_init_language
	//load_plugin_textdomain('qtranslate', false, dirname(plugin_basename( __FILE__ )).'/lang');

	if($q_config['url_info']['doing_front_end']){
		// don't filter untranslated posts in admin
		if($q_config['hide_untranslated']){
			add_filter('wp_list_pages_excludes', 'qtranxf_excludePages');//moved here from _hooks.php since 3.2.8
			add_filter('posts_where_request', 'qtranxf_excludeUntranslatedPosts',10,2);
			add_filter('comments_clauses','qtranxf_excludeUntranslatedPostComments',10,2);
		}
		foreach($q_config['text_field_filters'] as $nm){
			add_filter($nm, 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage');
		}
	}

	//allow other plugins to initialize whatever they need for qTranslate
	do_action('qtranslate_init');
}

function qtranxf_front_header_css() {
	global $q_config;
	if( isset($q_config['header_css']) && !empty($q_config['header_css']) )
		return $q_config['header_css'];
	return qtranxf_front_header_css_default();
}

function qtranxf_front_header_css_default()
{
	global $q_config;
	$flag_location=qtranxf_flag_location();
	$css = '';
	foreach($q_config['enabled_languages'] as $lang) 
	{
		$css .= '.qtranxs_flag_'.$lang.' {background-image: url('.$flag_location.$q_config['flag'][$lang].'); background-repeat: no-repeat;}'.PHP_EOL;
	}
	return $css;
}

function qtranxf_flag_location() {
	global $q_config;
	return trailingslashit(WP_CONTENT_URL).$q_config['flag_location'];
}

function qtranxf_flag_location_default() {
	//$q_config['flag_location'] = 'plugins/qtranslate-x/flags/';
	$plugindir = dirname(plugin_basename( __FILE__ ));
	return 'plugins/'.$plugindir.'/flags/';
}

function qtranxf_load_option_flag_location($nm) {
	global $q_config;
	$default_value = qtranxf_flag_location_default();
	$option_value = get_option('qtranslate_'.$nm);
	if($option_value===FALSE){
		$q_config[$nm] = $default_value;
	}else{
		// url fix for upgrading users
		$flag_location = trailingslashit(preg_replace('#^wp-content/#','',$option_value));
		if(file_exists(trailingslashit(WP_CONTENT_DIR).$flag_location) && $default_value != $flag_location){
			$q_config[$nm] = $flag_location;
		}else{
			$q_config[$nm] = $default_value;
			delete_option('qtranslate_'.$nm);
		}
	}
}

function qtranxf_validateBool($var, $default_value) {
	if($var==='0') return false; elseif($var==='1') return true; else return $default_value;
}

function qtranxf_load_option($nm, $default_value=null) {
	global $q_config;
	$val = get_option('qtranslate_'.$nm);
	if($val===FALSE){
		if(is_null($default_value)) return;
		$val = $default_value;
	}
	$q_config[$nm]=$val;
}

function qtranxf_load_option_array($nm, $default_value=null) {
	global $q_config;
	$vals = get_option('qtranslate_'.$nm);
	if($vals === FALSE){
		if(is_null($default_value)) return;
		if(is_string($default_value)) $vals = preg_split('/[\s,]+/',$default_value,null,PREG_SPLIT_NO_EMPTY);
		else if(is_array($default_value)) $vals = $default_value;
	}
	if(!is_array($vals)) return;

	//clean up array due to previous configuration imperfections
	foreach($vals as $key => $val){
		if(!empty($val)) continue;
		unset($vals[$key]);
		if(!empty($vals)) continue;
		delete_option('qtranslate_'.$nm);
		break;
	}
	$q_config[$nm]=$vals;
}

function qtranxf_load_option_bool( $nm, $default_value=null ) {
	global $q_config;
	$val = get_option('qtranslate_'.$nm);
	if($val===FALSE){ if(!is_null($default_value)) $q_config[$nm] = $default_value; }
	elseif($val==='0') $q_config[$nm] = false;
	elseif($val==='1') $q_config[$nm] = true;
}

function qtranxf_is_permalink_structure_query(){
	$permalink_structure = get_option('permalink_structure');
	return empty($permalink_structure)||strpos($permalink_structure, '?')!==false||strpos($permalink_structure, 'index.php')!==false;
}

// loads config via get_option and defaults to values set on top
function qtranxf_loadConfig() {
	global $q_config;

	//for other plugins and jave scripts to be able to fork by version
	//$q_config['version'] = get_plugin_data( plugins_url( '/qtranslate.php', __FILE__ ), false, false )['Version'];

	// Load everything
	$language_names = get_option('qtranslate_language_names');
	//$enabled_languages = get_option('qtranslate_enabled_languages');
	qtranxf_load_option_array('enabled_languages');
	$default_language = get_option('qtranslate_default_language');
	//$flag_location = get_option('qtranslate_flag_location');
	$flags = get_option('qtranslate_flags');
	$locales = get_option('qtranslate_locales');
	$na_messages = get_option('qtranslate_na_messages');
	$date_formats = get_option('qtranslate_date_formats');
	$time_formats = get_option('qtranslate_time_formats');
	$use_strftime = get_option('qtranslate_use_strftime');
	$ignore_file_types = get_option('qtranslate_ignore_file_types');
	$url_mode = get_option('qtranslate_url_mode');
	$term_name = get_option('qtranslate_term_name');

	qtranxf_load_option_flag_location('flag_location');

	qtranxf_load_option('editor_mode');

	qtranxf_load_option_array('custom_fields');
	qtranxf_load_option_array('custom_field_classes');
	qtranxf_load_option_array('text_field_filters');
	qtranxf_load_option_array('custom_pages');

	// default if not set
	if(!is_array($date_formats)) $date_formats = $q_config['date_format'];
	if(!is_array($time_formats)) $time_formats = $q_config['time_format'];
	if(!is_array($na_messages)) $na_messages = $q_config['not_available'];
	if(!is_array($locales)) $locales = $q_config['locale'];
	if(!is_array($flags)) $flags = $q_config['flag'];
	if(!is_array($language_names)) $language_names = $q_config['language_name'];
	//if(!is_array($enabled_languages)) $enabled_languages = $q_config['enabled_languages'];
	if(!is_array($term_name)) $term_name = $q_config['term_name'];
	if(empty($default_language)) $default_language = $q_config['default_language'];
	if(empty($use_strftime)) $use_strftime = $q_config['use_strftime'];
	if(empty($url_mode)) $url_mode = $q_config['url_mode'];
	//if(!is_string($flag_location) || $flag_location==='') $flag_location = $q_config['flag_location'];

	qtranxf_load_option_bool('detect_browser_language');
	qtranxf_load_option_bool('hide_untranslated');
	qtranxf_load_option_bool('show_displayed_language_prefix');
	qtranxf_load_option_bool('auto_update_mo');
	qtranxf_load_option_bool('hide_default_language');
	qtranxf_load_option_bool('header_css_on');
	qtranxf_load_option_bool('use_secure_cookie');

	qtranxf_load_option('header_css');

	qtranxf_load_option('filter_options_mode',QTX_FILTER_OPTIONS_ALL);
	if($q_config['filter_options_mode'] == QTX_FILTER_OPTIONS_LIST){
		qtranxf_load_option_array('filter_options',QTX_FILTER_OPTIONS_DEFAULT);
	}

	// check for invalid permalink/url mode combinations
	if(qtranxf_is_permalink_structure_query()){
		switch($url_mode){
			case QTX_URL_QUERY:
			case QTX_URL_DOMAIN:
			case QTX_URL_DOMAINS: break;
			default: $url_mode = QTX_URL_QUERY; break;
		}
	}

	switch($url_mode){
		case QTX_URL_DOMAINS:
			$q_config['domains']=array();
			qtranxf_load_option_array('domains');
			$q_config['disable_client_cookies'] = true;
			$q_config['hide_default_language'] = false;
			break;
		case QTX_URL_QUERY:
		case QTX_URL_PATH:
			$q_config['disable_client_cookies'] = false;
			qtranxf_load_option_bool('disable_client_cookies');
			break;
		case QTX_URL_DOMAIN:
		default:
			$q_config['disable_client_cookies'] = true;
			break;
	}

	// overwrite default values with loaded values
	$q_config['date_format'] = $date_formats;
	$q_config['time_format'] = $time_formats;
	$q_config['not_available'] = $na_messages;
	$q_config['locale'] = $locales;
	$q_config['flag'] = $flags;
	$q_config['language_name'] = $language_names;
	//$q_config['enabled_languages'] = $enabled_languages;
	$q_config['default_language'] = $default_language;
	//$q_config['flag_location'] = $flag_location;
	$q_config['use_strftime'] = $use_strftime;

	//$q_config['ignore_file_types'] = $ignore_file_types;
	$val=explode(',',QTX_IGNORE_FILE_TYPES);
	if(!empty($ignore_file_types)){
		$vals=preg_split('/[\s,]+/', strtolower($ignore_file_types), null, PREG_SPLIT_NO_EMPTY);
		foreach($vals as $v){
			if(empty($v)) continue;
			if(in_array($v,$val)) continue;
			$val[]=$v;
		}
	}
	$q_config['ignore_file_types'] = $val;

	$q_config['url_mode'] = $url_mode;
	$q_config['term_name'] = $term_name;

	do_action('qtranslate_loadConfig');
}

function qtranxf_reloadConfig() {
	global $q_config;
	qtranxf_set_config_default();
	qtranxf_loadConfig();
	if(isset($q_config['url_info']['language']))
		$q_config['language'] = $q_config['url_info']['language'];
	qtranxf_load_option_qtrans_compatibility();
	//qtranxf_dbg_echo('qtranxf_reloadConfig: $q_config[url_info]: ',$q_config['url_info']);
}

/* BEGIN DATE TIME FUNCTIONS */

function qtranxf_strftime($format, $date, $default = '', $before = '', $after = '') {
	// don't do anything if format is not given
	if($format=='') return $default;
	// add date suffix ability (%q) to strftime
	$day = intval(ltrim(strftime("%d",$date),'0'));
	$search = array();
	$replace = array();
	
	// date S
	$search[] = '/(([^%])%q|^%q)/';
	if($day==1||$day==21||$day==31) { 
		$replace[] = '$2st';
	} elseif($day==2||$day==22) {
		$replace[] = '$2nd';
	} elseif($day==3||$day==23) {
		$replace[] = '$2rd';
	} else {
		$replace[] = '$2th';
	}
	
	$search[] = '/(([^%])%E|^%E)/'; $replace[] = '${2}'.$day; // date j
	$search[] = '/(([^%])%f|^%f)/'; $replace[] = '${2}'.date('w',$date); // date w
	$search[] = '/(([^%])%F|^%F)/'; $replace[] = '${2}'.date('z',$date); // date z
	$search[] = '/(([^%])%i|^%i)/'; $replace[] = '${2}'.date('n',$date); // date i
	$search[] = '/(([^%])%J|^%J)/'; $replace[] = '${2}'.date('t',$date); // date t
	$search[] = '/(([^%])%k|^%k)/'; $replace[] = '${2}'.date('L',$date); // date L
	$search[] = '/(([^%])%K|^%K)/'; $replace[] = '${2}'.date('B',$date); // date B
	$search[] = '/(([^%])%l|^%l)/'; $replace[] = '${2}'.date('g',$date); // date g
	$search[] = '/(([^%])%L|^%L)/'; $replace[] = '${2}'.date('G',$date); // date G
	$search[] = '/(([^%])%N|^%N)/'; $replace[] = '${2}'.date('u',$date); // date u
	$search[] = '/(([^%])%Q|^%Q)/'; $replace[] = '${2}'.date('e',$date); // date e
	$search[] = '/(([^%])%o|^%o)/'; $replace[] = '${2}'.date('I',$date); // date I
	$search[] = '/(([^%])%O|^%O)/'; $replace[] = '${2}'.date('O',$date); // date O
	$search[] = '/(([^%])%s|^%s)/'; $replace[] = '${2}'.date('P',$date); // date P
	$search[] = '/(([^%])%v|^%v)/'; $replace[] = '${2}'.date('T',$date); // date T
	$search[] = '/(([^%])%1|^%1)/'; $replace[] = '${2}'.date('Z',$date); // date Z
	$search[] = '/(([^%])%2|^%2)/'; $replace[] = '${2}'.date('c',$date); // date c
	$search[] = '/(([^%])%3|^%3)/'; $replace[] = '${2}'.date('r',$date); // date r
	$search[] = '/(([^%])%4|^%4)/'; $replace[] = '${2}'.$date; // date U
	$format = preg_replace($search,$replace,$format);
	return $before.strftime($format, $date).$after;
}

/**
 * @since 3.2.8 time functions adjusted
 */
function qtranxf_format_date($format, $mysq_time, $default, $before = '', $after = '') {
	global $q_config;
	$ts = mysql2date('U', $mysq_time);
	if($format == 'U') return $ts;
	$format = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($format);
	if (!empty($format) && $q_config['use_strftime'] == QTX_STRFTIME)
		$format = qtranxf_convertDateFormatToStrftimeFormat($format);
	return qtranxf_strftime(qtranxf_convertDateFormat($format), $ts, $default, $before, $after);
}

function qtranxf_format_time($format, $mysq_time, $default, $before = '', $after = '') {
	global $q_config;
	$ts = mysql2date('U', $mysq_time);
	if($format == 'U') return $ts;
	$format = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($format);
	if (!empty($format) && $q_config['use_strftime'] == QTX_STRFTIME)
		$format = qtranxf_convertDateFormatToStrftimeFormat($format);
	return qtranxf_strftime(qtranxf_convertTimeFormat($format), $ts, $default, $before, $after);
}

function qtranxf_dateFromPostForCurrentLanguage($old_date, $format = '', $post = null) {
	$post = get_post($post); if(!$post) return $old_date;
	return qtranxf_format_date($format, $post->post_date, $old_date);
}

function qtranxf_dateModifiedFromPostForCurrentLanguage($old_date, $format = '') {
	global $post; if(!$post) return $old_date;
	return qtranxf_format_date($format, $post->post_modified, $old_date);
}

function qtranxf_timeFromPostForCurrentLanguage($old_date, $format = '', $post = null, $gmt = false) {
	$post = get_post($post); if(!$post) return $old_date;
	$post_date = $gmt? $post->post_date_gmt : $post->post_date;
	return qtranxf_format_time($format, $post_date, $old_date);
	//return qtranxf_strftime(qtranxf_convertTimeFormat($format), mysql2date('U',$post_date), $old_date);
}

function qtranxf_timeModifiedFromPostForCurrentLanguage($old_date, $format = '', $gmt = false) {
	global $post; if(!$post) return $old_date;
	$post_date = $gmt? $post->post_modified_gmt : $post->post_modified;
	return qtranxf_format_time($format, $post_date, $old_date);
	//return qtranxf_strftime(qtranxf_convertTimeFormat($format), mysql2date('U',$post_date), $old_date);
}

function qtranxf_dateFromCommentForCurrentLanguage($old_date, $format, $comment = null) {
	if(!$comment){ global $comment; }//compatibility with older WP
	if(!$comment) return $old_date;
	return qtranxf_format_date($format, $comment->comment_date, $old_date);
	//return qtranxf_strftime(qtranxf_convertDateFormat($format), mysql2date('U',$comment->comment_date), $old_date);
}

function qtranxf_timeFromCommentForCurrentLanguage($old_date, $format = '', $gmt = false, $translate = true, $comment = null) {
	if(!$translate) return $old_date;
	if(!$comment){ global $comment; }//compatibility with older WP
	if(!$comment) return $old_date;
	$comment_date = $gmt? $comment->comment_date_gmt : $comment->comment_date;
	return qtranxf_format_time($format, $comment_date, $old_date);
	//return qtranxf_strftime(qtranxf_convertTimeFormat($format), mysql2date('U',$comment_date), $old_date);
}

/* END DATE TIME FUNCTIONS */

if (!function_exists('qtranxf_useTermLib')){
function qtranxf_useTermLib($obj) {
	global $q_config;
	if(is_array($obj)) {
		// handle arrays recursively
		foreach($obj as $key => $t) {
			$obj[$key] = qtranxf_useTermLib($obj[$key]);
		}
		return $obj;
	}
	if(is_object($obj)) {
		// object conversion
		if(isset($q_config['term_name'][$obj->name][$q_config['language']])) {
			//qtranxf_dbg_echo('qtranxf_useTermLib: object: ',$obj,true);
			$obj->name = $q_config['term_name'][$obj->name][$q_config['language']];
		} 
	} elseif(isset($q_config['term_name'][$obj][$q_config['language']])) {
		//qtranxf_dbg_echo('qtranxf_useTermLib: string: ',$obj,true);
		$obj = $q_config['term_name'][$obj][$q_config['language']];
	}
	return $obj;
}
}

// check if it is a link to an ignored file type
function qtranxf_ignored_file_type($path) {
	global $q_config;
	//$pathinfo = pathinfo($path);//too much overhead
	//qtranxf_dbg_echo('qtranxf_ignored_file_type: pathinfo:',$pathinfo);
	//if(!isset($pathinfo['extension'])) return false;
	//$ext=strtolower($pathinfo['extension']);
	$i=strpos($path,'?');
	if($i!==FALSE){
		$path=substr($path,0,$i);
	}
	$i=strpos($path,'#');
	if($i!==FALSE){
		$path=substr($path,0,$i);
	}
	$i=strrpos($path,'.');
	//qtranxf_dbg_echo('qtranxf_ignored_file_type: path='.$path.'; i='.$i);
	if($i===FALSE) return false;
	$ext=substr($path,$i+1);
	//qtranxf_dbg_echo('qtranxf_ignored_file_type: ext='.$ext);
	return in_array($ext, $q_config['ignore_file_types']);
}

function qtranxf_language_neutral_path($path) {
	//qtranxf_dbg_log('qtranxf_language_neutral_path: path='.$path);
	//if(preg_match("#^(wp-comments-post.php|wp-login.php|wp-signup.php|wp-register.php|wp-cron.php|wp-admin/)#", $path)) return true;
	if( empty($path) ) return false;
	static $language_neutral_path_cache;
	if(isset($language_neutral_path_cache[$path])){
		//qtranxf_dbg_log('qtranxf_language_neutral_path: cached='.$language_neutral_path_cache[$path].': path='.$path);
		return $language_neutral_path_cache[$path];
	}
	if(preg_match('#^/(wp-.*\.php|wp-admin/|xmlrpc.php|.*sitemap.*|robots.txt|oauth/)#', $path)){
		$language_neutral_path_cache[$path] = true;
		//qtranxf_dbg_log('qtranxf_language_neutral_path: preg_match: path='.$path);
		return true;
	}
	if(qtranxf_ignored_file_type($path)){
		$language_neutral_path_cache[$path] = true;
		//qtranxf_dbg_log('qtranxf_language_neutral_path: file_type: path='.$path);
		return true;
	}
	$language_neutral_path_cache[$path] = false;
	return false;
}
/**
 * @since 
 */
function qtranxf_url_del_language(&$urlinfo) {
	global $q_config;
	//$urlinfo = apply_filters('qtranslate_url_del_language_pre', $urlinfo);

	if(!empty($urlinfo['query'])){
		$query = &$urlinfo['query'];
		// &amp; workaround
		if(strpos($query,'&amp;')!==false){
			$query = str_replace('&amp;','&',$query);
			$urlinfo['query_amp']=true;
		}
		if(strpos($urlinfo['query'],'&#038;')!==false){
			$query = str_replace('&#038;','&',$query);
			$urlinfo['query_amp']=true;
		}
		qtranxf_del_query_arg($query,'lang');
	}

	$url_mode = $q_config['url_mode'];
	switch($url_mode) {
		case QTX_URL_PATH: // pre-path
			// might already have language information
			if(!empty($urlinfo['wp-path']) && preg_match('!^/([a-z]{2})(/|$)!i',$urlinfo['wp-path'],$match)) {
				// found language information, remove it
				$urlinfo['wp-path'] = substr($urlinfo['wp-path'], 3);
			}
			break;
		case QTX_URL_DOMAIN: // pre-domain
			// remove language information
			$homeinfo=qtranxf_get_home_info();
			$urlinfo['host'] = $homeinfo['host'];
			break;
		case QTX_URL_DOMAINS: // per-domain
			$urlinfo['host'] = $q_config['domains'][$q_config['default_language']];
			break;
		case QTX_URL_QUERY: // query
			break;
		default:
			$urlinfo = apply_filters('qtranslate_url_del_language',$urlinfo,$url_mode);
			break;
	}
	//qtranxf_dbg_log('qtranxf_url_del_language: $urlinfo:',$urlinfo);
}

function qtranxf_url_set_language($urlinfo,$lang,$showLanguage) {
	global $q_config;
	$urlinfo = qtranxf_copy_url_info($urlinfo);
	//qtranxf_dbg_log('qtranxf_url_set_language: $urlinfo:',$urlinfo);
	if($showLanguage){
		$url_mode = $q_config['url_mode'];
		switch($url_mode) {
			case QTX_URL_PATH: // pre-path
				$urlinfo['wp-path'] = '/'.$lang.$urlinfo['wp-path'];
				break;
			case QTX_URL_DOMAIN: // pre-domain
				$urlinfo['host'] = $lang.'.'.$urlinfo['host'];
				//qtranxf_dbg_log('qtranxf_url_set_language: QTX_URL_DOMAIN: urlinfo:',$urlinfo);
				break;
			case QTX_URL_DOMAINS: // per-domain
				$urlinfo['host'] = $q_config['domains'][$lang];
				break;
			case QTX_URL_QUERY: // query
				qtranxf_add_query_arg($urlinfo['query'],'lang='.$lang);
				break;
			default:
				$urlinfo = apply_filters('qtranslate_url_set_language',$urlinfo,$lang,$url_mode);
				break;
		}
	}

	// see if cookies are activated
	if( !$showLanguage//there still no language information in the converted URL
		&& !$q_config['cookie_enabled']// there will be no way to take language from the cookie
		//&& empty($urlinfo['path']) //why this was here?
		//&& !isset($q_config['url_info']['internal_referer'])//three below replace this one?
		&& $q_config['language'] != $q_config['default_language']//we need to be able to get language other than default
		&& empty($q_config['url_info']['lang_url'])//we will not be able to get language from referrer path
		&& empty($q_config['url_info']['lang_query_get'])//we will not be able to get language from referrer query
		) {
		// :( now we have to make unpretty URLs
		qtranxf_add_query_arg($urlinfo['query'],'lang='.$lang);
	}

	// &amp; workaround
	if(isset($urlinfo['query_amp'])){
		$urlinfo['query'] = str_replace('&','&amp;',$urlinfo['query']);
	}
	return $urlinfo;
}

//if (!function_exists('qtranxf_get_url_for_language')){
function qtranxf_get_url_for_language($url, $lang, $showLanguage) {
	global $q_config;
	static $url_cache=array();
	//qtranxf_dbg_log('called: qtranxf_get_url_for_language('.$lang.($showLanguage?', true':', false').'): url=',$url,false);
	//qtranxf_dbg_log('qtranxf_get_url_for_language: $url_cache:',$url_cache);
	if(!isset($url_cache[$url])) $url_cache[$url] = array();
	$urlinfo = &$url_cache[$url];
	//$urlinfo = apply_filters('qtranslate_url_for_language_pre', $urlinfo, $url, $lang, $showLanguage);

	if($showLanguage){
		if(isset($urlinfo[$lang])){
			//qtranxf_dbg_log('qtranxf_get_url_for_language: cached: lang='.$lang.': ',$urlinfo);
			return $urlinfo[$lang];
		}
	}else{
		if(isset($urlinfo['bare'])){
			//qtranxf_dbg_log('qtranxf_get_url_for_language: cached: bare: ',$urlinfo);
			return $urlinfo['bare'];
		}
	}

	if(isset($urlinfo['language_neutral'])){
		//qtranxf_dbg_log('qtranxf_get_url_for_language: cached: language_neutral: ',$urlinfo);
		return $urlinfo['language_neutral'];
	}

	$homeinfo = qtranxf_get_home_info();
	if(!isset($urlinfo['url_parsed'])){
		if(empty($url)){
			$urlinfo = qtranxf_copy_url_info($q_config['url_info']);

			if(qtranxf_language_neutral_path($urlinfo['wp-path'])){
				//qtranxf_dbg_log('qtranxf_get_url_for_language: language_neutral: wp-path: url='.$url.':',$urlinfo);
				$complete = qtranxf_buildURL($urlinfo,$homeinfo);
				if(!isset($url_cache[$complete])) $url_cache[$complete] = $urlinfo;
				$urlinfo['language_neutral'] = $complete;
				return $complete;
			}

		}else{
			$urlinfo = qtranxf_get_url_info($url);

			// check if it's an external link
			if(!isset($urlinfo['wp-path'])){
				$urlinfo['language_neutral'] = $url;
				//qtranxf_dbg_log('qtranxf_get_url_for_language: language_neutral: external path: ',$urlinfo);
				return $url;
			}

			if(!empty($urlinfo['host']) && qtranxf_external_host_ex($urlinfo['host'],$homeinfo)){
				$urlinfo['language_neutral'] = $url;
				//qtranxf_dbg_log('qtranxf_get_url_for_language: language_neutral: external host: ',$urlinfo);
				return $url;
			}

			if(qtranxf_language_neutral_path($urlinfo['wp-path'])){
				//qtranxf_dbg_log('qtranxf_get_url_for_language: language_neutral: wp-path: url='.$url.': ',$urlinfo);
				$urlinfo['language_neutral'] = $url;
				return $url;
			}

			qtranxf_url_del_language($urlinfo);
		}
		$urlinfo['url_parsed'] = $url;
	}

	$urlinfo_lang = qtranxf_url_set_language($urlinfo,$lang,$showLanguage);
	$complete = qtranxf_buildURL($urlinfo_lang,$homeinfo);
	//$complete = apply_filters('qtranslate_url_for_language',$complete,$lang,$urlinfo_lang,$homeinfo);
	if($showLanguage){
		$urlinfo[$lang] = $complete;
	}else{
		$urlinfo['bare'] = $complete;
	}
	if(!isset($url_cache[$complete])) $url_cache[$complete] = $urlinfo;
	//qtranxf_dbg_log('done: qtranxf_get_url_for_language('.$lang.($showLanguage?', true':', false').'): $urlinfo=',$urlinfo,false);
	return $complete;
}
/*
function qtranxf_get_url_for_language($url, $lang, $showLanguage) {
	global $q_config;

	// check for trailing slash
	$nottrailing = (strpos($url,'?')===false && strpos($url,'#')===false && substr($url,-1,1)!='/');

	// check if it's an external link
	$home = rtrim(get_option('home'),'/');
	if(!empty($urlinfo['host'])){
		// check for already existing pre-domain language information
		if($q_config['url_mode'] == QTX_URL_DOMAIN && preg_match('#^([a-z]{2})\.#i',$urlinfo['host'],$match)) {
			if(qtranxf_isEnabled($match[1])) {
				// found language information, remove it
				$url = preg_replace('/'.$match[1].'\./i','',$url, 1);
				// reparse url
				$urlinfo = qtranxf_parseURL($url);
			}
		}
		if(substr($url,0,strlen($home))!=$home) return $url;
		$site = rtrim(get_option('siteurl'),'/');
		if($site != $home && qtranxf_startsWith($url,$site)) return $url;
		// strip home path
		$url = substr($url,strlen($home));
	} else {
		// relative url, strip home path
		$homeinfo = qtranxf_parseURL($home);
		if($homeinfo['path']==substr($url,0,strlen($homeinfo['path']))) {
			$url = substr($url,strlen($homeinfo['path']));
		}
	}

	// check for query language information and remove if found
	if(preg_match('/(&|\?)(lang=[^&#]+)(&|#|)/i',$url,$match)){
		$p = strpos($url,$match[2]);
		$n = strlen($match[2]) + 1;
		if($match[1] == '&' || $match[3] != '&') --$p;
		//qtranxf_dbg_log('qtranxf_convertURL('.$url.') p='.$p.'; n=',$n);
		$url = substr_replace($url,'',$p,$n);
		//qtranxf_dbg_log('qtranxf_convertURL: $url:',$url);
	}

	// remove any slashes out front
	$url = ltrim($url,'/');

	// remove any useless trailing characters
	$url = rtrim($url,'?&');

	// re-parse url without home path
	//$urlinfo = qtranxf_parseURL($url);
	// check if its a link to an ignored file type
	//$ignore_file_types = preg_split('/\s*,\s*/ /*', strtolower($q_config['ignore_file_types']));
	//$pathinfo = pathinfo($urlinfo['path']);
	//if(isset($pathinfo['extension']) && in_array(strtolower($pathinfo['extension']), $ignore_file_types)) {
	//if(qtranxf_ignored_file_type($urlinfo['path'])){
	//	return $home.'/'.$url;
	//}

	// ignore wp internal links
	//if(preg_match("#^(wp-login.php|wp-signup.php|wp-register.php|wp-admin/)#", $url)) {
	if(qtranxf_language_neutral_path($url)) {
		return $home.'/'.$url;
	}

	$url_mode = $q_config['url_mode'];
	switch($url_mode) {
		case QTX_URL_PATH: // pre path
			// might already have language information
			//qtranxf_dbg_echo('qtranxf_convertURL:url='.$url);
			if(preg_match('!^([a-z]{2})([/\?#&]|$)!i',$url,$match)) {
				if(qtranxf_isEnabled($match[1])) {
					// found language information, remove it
					$url = ltrim(substr($url, 2),'/');
				}
			}
			if($showLanguage) $url = $lang.'/'.$url;
			break;
		case QTX_URL_DOMAIN: // pre domain
			if($showLanguage) $home = preg_replace("#//#","//".$lang.".",$home,1);
			break;
		case QTX_URL_DOMAINS: // domain per language
			return $q_config['domains'][$lang].'/'.$url;
		case QTX_URL_QUERY: // query
			if($showLanguage){
				//$url=add_query_arg('lang',$lang,$url);//it is doing much more than needed here
				if(strpos($url,'?')===false) {
					$url .= '?';
				} else {
					$url .= '&';
				}
				$url .= 'lang='.$lang;
			}
			break;
		default:
			$url = apply_filters('qtranslate_get_url_for_language_url_mode',$url,$lang,$showLanguage,$url_mode,$urlinfo);
			break;
	}

	// see if cookies are activated
	if( !$showLanguage//there still no language information in the converted URL
		&& !$q_config['cookie_enabled']// there will be no way to take language from the cookie
		//&& empty($urlinfo['path']) //why this was here?
		//&& !isset($q_config['url_info']['internal_referer'])//three below replace this one?
		&& $q_config['language'] != $q_config['default_language']//we need to be able to get language other than default
		&& empty($q_config['url_info']['lang_url'])//we will not be able to get language from referrer path
		&& empty($q_config['url_info']['lang_query_get'])//we will not be able to get language from referrer query
		) {
		// :( now we have to make unpretty URLs
		//$url=add_query_arg('lang',$lang,$url);//it is doing much more than needed here
		//$url = preg_replace("#(&|\?)lang=".$match[2]."&?#i","$1",$url);//already removed above
		if(strpos($url,'?')===false) {
			$url .= '?';
		} else {
			$url .= '&';
		}
		$url .= "lang=".$lang;
	}

	$complete = $home.'/'.$url;

	// remove trailing slash if there wasn't one to begin with
	if($nottrailing && strpos($complete,'?')===false && strpos($complete,'#')===false && substr($complete,-1,1)=='/')
		$complete = substr($complete,0,-1);

	$complete = apply_filters('qtranslate_get_url_for_language',$complete,$lang);

	return $complete;
}
*/
//}

//if (!function_exists('qtranxf_convertURL')){
function qtranxf_convertURL($url='', $lang='', $forceadmin = false, $showDefaultLanguage = false) {
	global $q_config;

	if($lang=='') $lang = $q_config['language'];
	if(empty($url)){
		if( $q_config['url_info']['doing_front_end'] && defined('QTS_VERSION') && $q_config['url_mode'] != QTX_URL_QUERY){
			//quick workaround, but need a permanent solution
			$url = qts_get_url($lang);
			//qtranxf_dbg_echo('qtranxf_convertURL: url=',$url);
			if(!empty($url)){
				if($q_config['hide_default_language'] && $showDefaultLanguage && $lang==$q_config['default_language'])
					$url=qtranxf_convertURL($url,$lang,$forceadmin,true);
				return $url;
			}
		}
		//$url = esc_url($q_config['url_info']['url']);
	}
	if( !$q_config['url_info']['doing_front_end'] && !$forceadmin ) return $url;
	if(!qtranxf_isEnabled($lang)) return '';

	if(!$showDefaultLanguage) $showDefaultLanguage = !$q_config['hide_default_language'];
	$showLanguage = $showDefaultLanguage || $lang != $q_config['default_language'];
	//qtranxf_dbg_log('qtranxf_convertURL('.$url.','.$lang.'): showLanguage=',$showLanguage);
	$complete = qtranxf_get_url_for_language($url, $lang, $showLanguage);
	//qtranxf_dbg_log('qtranxf_convertURL: complete: ',$complete);
	return $complete;
}
//}

function qtranxf_convertURLs($url, $lang='', $forceadmin = false, $showDefaultLanguage = false) {
	global $q_config;
	if(empty($lang)) $lang = $q_config['language'];
	if(is_array($url)){
		foreach($url as $k => $v){
			$url[$k] = qtranxf_convertURLs($v, $lang, $forceadmin, $showDefaultLanguage);
		}
		return $url;
	}else if(is_string($url) && !empty($url)){
		return qtranxf_convertURL($url, $lang, $forceadmin, $showDefaultLanguage);
	}
	return $url;
}

//if (!function_exists('qtranxf_get_split_blocks')){
// split text at all language comments and quick tags
function qtranxf_get_language_blocks($text) {
	$split_regex = "#(<!--:[a-z]{2}-->|<!--:-->|\[:[a-z]{2}\]|\[:\])#ism";
	return preg_split($split_regex, $text, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
}
//}

if (!function_exists('qtranxf_split')){
function qtranxf_split($text) {
	$blocks = qtranxf_get_language_blocks($text);
	return qtranxf_split_blocks($blocks);
}
}

if (!function_exists('qtranxf_split_blocks')){
function qtranxf_split_blocks($blocks) {
	global $q_config;
	$result = array();
	foreach($q_config['enabled_languages'] as $language) {
		$result[$language] = '';
	}
	$current_language = false;
	foreach($blocks as $block) {
		// detect c-tags
		if(preg_match("#^<!--:([a-z]{2})-->$#ism", $block, $matches)) {
			$current_language = $matches[1];
			continue;
		// detect b-tags
		}elseif(preg_match("#^\[:([a-z]{2})\]$#ism", $block, $matches)) {
			$current_language = $matches[1];
			continue;
		}
		switch($block){
			case '[:]':
			case '<!--:-->':
				$current_language = false;
				break;
			default:
				// correctly categorize text block
				if($current_language){
					if(!isset($result[$current_language])) $result[$current_language]='';
					$result[$current_language] .= $block;
					$current_language = false;
				}else{
					foreach($q_config['enabled_languages'] as $language) {
						$result[$language] .= $block;
					}
				}
			break;
		}
	}
	//it gets trimmed later in qtranxf_use() anyway, better to do it here
	foreach($result as $lang => $text){
		$result[$lang]=trim($text);
	}
	return $result;
}
/*
function qtranxf_split($text, $quicktags = true) {
	global $q_config;
	//init vars
	$split_regex = "#(<!--[^-]*-->|\[:[a-z]{2}\])#ism";
	//$split_regex = "#(<!--:[[a-z]{2}]?-->|\[:[a-z]{2}\])#ism";
	$current_language = "";
	$result = array();
	foreach($q_config['enabled_languages'] as $language) {
		$result[$language] = "";
	}

	// split text at all xml comments
	$blocks = preg_split($split_regex, $text, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
	foreach($blocks as $block) {
		# detect language tags
		if(preg_match("#^<!--:([a-z]{2})-->$#ism", $block, $matches)) {
			if(qtranxf_isEnabled($matches[1])) {
				$current_language = $matches[1];
			} else {
				$current_language = "invalid";
			}
			continue;
		// detect quicktags
		} elseif($quicktags && preg_match("#^\[:([a-z]{2})\]$#ism", $block, $matches)) {
			if(qtranxf_isEnabled($matches[1])) {
				$current_language = $matches[1];
			} else {
				$current_language = "invalid";
			}
			continue;
		// detect ending tags
		} elseif(preg_match("#^<!--:-->$#ism", $block, $matches)) {
			$current_language = "";
			continue;
		// detect defective more tag
		//} elseif(preg_match("#^<!--more-->$#ism", $block, $matches)) {
		//	foreach($q_config['enabled_languages'] as $language) {
		//		$result[$language] .= $block;
		//	}
		//	continue;
		}
		// correctly categorize text block
		if($current_language == "") {
			// general block, add to all languages
			foreach($q_config['enabled_languages'] as $language) {
				$result[$language] .= $block;
			}
		} elseif($current_language != "invalid") {
			// specific block, only add to active language
			$result[$current_language] .= $block;
		}
	}
	//foreach($result as $lang => $lang_content) {
	//	$result[$lang] = preg_replace("#(<!--more-->|<!--nextpage-->)+$#ism","",$lang_content);
	//}
	return $result;
}// */
}

// not in use?
//function qtranxf_join($texts) {
//	if(!is_array($texts)) $texts = qtranxf_split($texts);
//	qtranxf_join_c($texts);
//}

function qtranxf_join_c($texts) {
	$text = '';
	foreach($texts as $lang => $lang_text) {
		if(empty($lang_text)) continue;
		$text .= '<!--:'.$lang.'-->'.$lang_text.'<!--:-->';
	}
/*
	//should join all available, not only enabled?
	global $q_config;
	$split_regex = "#<!--more-->#ism";
	$max = 0;
	foreach($q_config['enabled_languages'] as $language) {
		$texts[$language] = preg_split($split_regex, $texts[$language]);
		if(sizeof($texts[$language]) > $max) $max = sizeof($texts[$language]);
	}
	for($i=0;$i<$max;$i++) {
		if($i>=1) {
			$text .= '<!--more-->';
		}
		foreach($q_config['enabled_languages'] as $language) {
			if(isset($texts[$language][$i]) && $texts[$language][$i] !== '') {
				$text .= '<!--:'.$language.'-->'.$texts[$language][$i].'<!--:-->';
			}
		}
	}
*/
	return $text;
}

function qtranxf_join_b_no_closing($texts) {
	$text = '';
	foreach($texts as $lang => $lang_text) {
		if(empty($lang_text)) continue;
		$text .= '[:'.$lang.']'.$lang_text;
	}
	return $text;
}

function qtranxf_join_b($texts) {
	$text = qtranxf_join_b_no_closing($texts);
	if(!empty($text)) $text .= '[:]';
	return $text;
}

function qtranxf_disableLanguage($lang) {
	global $q_config;
	if(qtranxf_isEnabled($lang)) {
		$new_enabled = array();
		for($i = 0; $i < sizeof($q_config['enabled_languages']); $i++) {
			if($q_config['enabled_languages'][$i] != $lang) {
				$new_enabled[] = $q_config['enabled_languages'][$i];
			}
		}
		$q_config['enabled_languages'] = $new_enabled;
		return true;
	}
	return false;
}

function qtranxf_enableLanguage($lang) {
	global $q_config;
	if(qtranxf_isEnabled($lang) || !isset($q_config['language_name'][$lang])) {
		return false;
	}
	$q_config['enabled_languages'][] = $lang;
	// force update of .mo files
	if ($q_config['auto_update_mo']) qtranxf_updateGettextDatabases(true, $lang);
	return true;
}

if (!function_exists('qtranxf_use')){
function qtranxf_use($lang, $text, $show_available=false, $show_empty=false) {
	//global $q_config;
	// return full string if language is not enabled
	//if(!qtranxf_isEnabled($lang)) return $text;//why?
	if(is_array($text)) {
		// handle arrays recursively
		foreach($text as $key => $t) {
			$text[$key] = qtranxf_use($lang,$text[$key],$show_available,$show_empty);
		}
		return $text;
	}

	if( is_object($text) || $text instanceof __PHP_Incomplete_Class ) {//since 3.2-b1 instead of @get_class($text) == '__PHP_Incomplete_Class'
		foreach(get_object_vars($text) as $key => $t) {
			$text->$key = qtranxf_use($lang,$text->$key,$show_available,$show_empty);
		}
		return $text;
	}

	// prevent filtering weird data types and save some resources
	if(!is_string($text) || empty($text))//|| $text) == ''
		return $text;

	return qtranxf_use_language($lang, $text, $show_available, $show_empty);
}
}

if (!function_exists('qtranxf_use_language')){
/** when $text is already known to be string */
function qtranxf_use_language($lang, $text, $show_available=false, $show_empty=false) {
	$blocks = qtranxf_get_language_blocks($text);
	if(count($blocks)<=1)//no language is encoded in the $text, the most frequent case
		return $text;
	return qtranxf_use_block($lang, $blocks, $show_available, $show_empty);
}
}

if (!function_exists('qtranxf_use_block')){
function qtranxf_use_block($lang, $blocks, $show_available=false, $show_empty=false) {
	global $q_config;
	$content = qtranxf_split_blocks($blocks);

	// if content is available show the content in the requested language
	if(!empty($content[$lang])) {
		return $content[$lang];
	}elseif($show_empty){
		return '';
	}

	// content not available in requested language (bad!!) what now?

	// find available languages
	$available_languages = array();
	foreach($content as $language => $lang_text) {
		//$lang_text = trim($lang_text);//do we need to trim? not really ... but better trim in qtranxf_split_blocks then
		//$content[$language]=$lang_text;
		if(!empty($lang_text)) $available_languages[] = $language;
	}

	//// if no languages available show full text
	//if(sizeof($available_languages)==0) return $text;//not is is not empty, since we called qtranxf_get_language_blocks
	//// if content is available show the content in the requested language
	//if(!empty($content[$lang])) {//already done above now
	//	return $content[$lang];
	//}
	//// content not available in requested language (bad!!) what now?

	if(!$show_available){
		// check if content is available in default language, if not return first language found. (prevent empty result)
		if($lang!=$q_config['default_language']){
			$language = $q_config['default_language'];
			//$lang_text = qtranxf_use($language, $text, $show_available);
			$lang_text = $content[$language];
			$lang_text = trim($lang_text);
			if(!empty($lang_text)){
				if ($q_config['show_displayed_language_prefix'])
					return "(".$q_config['language_name'][$language].") ".$lang_text;
				else
					return $lang_text;
			}
		}
		foreach($content as $language => $lang_text) {
			$lang_text = trim($lang_text);
			if(empty($lang_text)) continue;
			if ($q_config['show_displayed_language_prefix'])
				return "(".$q_config['language_name'][$language].") ".$lang_text;
			else
				return $lang_text;
		}
	}
	// display selection for available languages
	$available_languages = array_unique($available_languages);
	$language_list = "";
	if(preg_match('/%LANG:([^:]*):([^%]*)%/',$q_config['not_available'][$lang],$match)) {
		$normal_separator = $match[1];
		$end_separator = $match[2];
		// build available languages string backward
		$i = 0;
		foreach($available_languages as $language) {
			if($i==1) $language_list  = $end_separator.$language_list;
			if($i>1) $language_list  = $normal_separator.$language_list;
			$language_list = '<a href="'.qtranxf_convertURL('', $language, false, true).'">'.$q_config['language_name'][$language].'</a>'.$language_list;
			$i++;
		}
	}
	//qtranxf_dbg_echo('$language_list=',$language_list,true);
	//if(isset($post)){
	//	//qtranxf_dbg_echo('$post='.$post);
	//}
	return "<p>".preg_replace('/%LANG:([^:]*):([^%]*)%/', $language_list, $q_config['not_available'][$lang])."</p>";
}
}

function qtranxf_showAllSeparated($text) {
	if(empty($text)) return $text;
	global $q_config;
	$result = '';
	foreach(qtranxf_getSortedLanguages() as $language) {
		$result .= $q_config['language_name'][$language].':'.PHP_EOL.qtranxf_use($language, $text).PHP_EOL.PHP_EOL;
	}
	return $result;
}

/* @since 3.2.5 qtranslate.css is not actually needed
function qtranxf_add_css ()
{
	global $q_config;
	if (is_admin() || $q_config['header_css_on'])
	{
		wp_register_style( 'qtranslate-style', plugins_url('qtranslate.css', __FILE__), array(), QTX_VERSION );
		wp_enqueue_style( 'qtranslate-style' );
	}
}
*/
/* since 3.2.7 - removed
function qtranxf_optionFilter($do='enable') {//do we need it?
	//qtranxf_dbg_echo('qtranxf_optionFilter: do='.$do);
	$options = array(	'option_widget_pages',
						'option_widget_archives',
						'option_widget_meta',
						'option_widget_calendar',
						'option_widget_text',
						'option_widget_categories',
						'option_widget_recent_entries',
						'option_widget_recent_comments',
						'option_widget_rss',
						'option_widget_tag_cloud'
					);
	if($do!='disable'){//this needs to be optimized if this function needed at all.
		foreach($options as $option)
			add_filter($option, 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
	}else{
		foreach($options as $option)
			remove_filter($option, 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
	}
}
*/
