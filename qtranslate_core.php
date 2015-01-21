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

/* qTranslate Functions */

function qtranxf_init_language() {
	global $q_config;
	// check if it isn't already initialized
	if(defined('QTRANX_INIT')){ echo "qtranxf_init_language second time"; return; }
	define('QTRANX_INIT',true);

	qtranxf_loadConfig();

	$cookie_name = defined('WP_ADMIN') ? QTX_COOKIE_NAME_ADMIN : QTX_COOKIE_NAME_FRONT;
	$q_config['cookie_enabled']=isset($_COOKIE[$cookie_name]);

	$host=$_SERVER['HTTP_HOST'];
	if(isset($_SERVER['SERVER_PORT']) && !empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT']!='80'){
		$host.=':'.$_SERVER['SERVER_PORT'];
	}
	//qtranxf_dbg_log('qtranxf_init_language: IP='.$_SERVER['REMOTE_ADDR']);
	//qtranxf_dbg_log('qtranxf_init_language: URL='.$host.$_SERVER['REQUEST_URI']);
	//qtranxf_dbg_log('qtranxf_init_language: POST: ',$_POST);
	$q_config['url_info'] = qtranxf_detect_language($_SERVER['REQUEST_URI'], $host, isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
	//qtranxf_dbg_log('qtranxf_init_language: url_info: ',$q_config['url_info']);

	$q_config['language'] = $q_config['url_info']['language'];
	$q_config['language'] = apply_filters('qtranslate_language', $q_config['language']);

	// Filter all options for language tags
	if(!defined('WP_ADMIN')) {
		$alloptions = wp_load_alloptions();
		foreach($alloptions as $option => $value) {
			add_filter('option_'.$option, 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
		}
	}

	// remove traces of language (or better not?)
	//unset($_GET['lang']);
	//qtranxf_dbg_log('qtranxf_init_language: REQUEST_URI='.$_SERVER['REQUEST_URI']);
	//$_SERVER['REQUEST_URI'] = $q_config['url_info']['url'];
	//$_SERVER['HTTP_HOST'] = $q_config['url_info']['host'];
	//qtranxf_dbg_log('qtranxf_init_language: REQUEST_URI='.$_SERVER['REQUEST_URI']);

	// fix url to prevent xss
	$q_config['url_info']['url'] = qtranxf_convertURL(add_query_arg('lang',$q_config['default_language'],$q_config['url_info']['url']));

	if($q_config['qtrans_compatibility']){
		require_once(dirname(__FILE__).'/qtranslate_compatibility.php');
	}

	//allow other plugins to initialize whatever they need for language
	do_action('qtranslate_init_language');

//qtranxf_dbg_log('qtranxf_init_language: url_info.url='.$q_config['url_info']['url']);
//qtranxf_dbg_log('qtranxf_init_language: language='.$q_config['language']);
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

function qtranxf_get_language_cookie()
{
	if(defined('WP_ADMIN')){
		if(isset($_COOKIE[QTX_COOKIE_NAME_ADMIN])) return $_COOKIE[QTX_COOKIE_NAME_ADMIN];
	}else{
		if(isset($_COOKIE[QTX_COOKIE_NAME_FRONT])) return $_COOKIE[QTX_COOKIE_NAME_FRONT];
	}
	return false;
}

function qtranxf_set_language_cookie($lang,$cookie_path)
{
	if(defined('WP_ADMIN')){
		$cookie_name = QTX_COOKIE_NAME_ADMIN;
		$cookie_path = trailingslashit($cookie_path).'wp-admin';
	}else{
		$cookie_name = QTX_COOKIE_NAME_FRONT;
		if(strlen($cookie_path)>1)
			$cookie_path=untrailingslashit($cookie_path);
	}
	//qtranxf_dbg_log('qtranxf_set_language_cookie: COOKIEPATH='.COOKIEPATH.'; cookie_path='.$cookie_path.'; lang='.$lang);
	setcookie($cookie_name, $lang, time()+31536000, $cookie_path);//one year
	//two weeks 1209600
}

// returns cleaned string and language information
//former qtranxf_extractURL
function qtranxf_detect_language($url, $host, $referer) {
	global $q_config;
	//$home = parse_url(get_option('home'));//qtranxf_dbg_log("home:",$home);
	//$home['path'] = isset($home['path'])?trailingslashit($home['path']):'/';
	//$home['host'] = isset($home['host'])?$home['host'];
	$home = qtranxf_parseURL(get_option('home'));
	$home['path'] = isset($home['path'])?trailingslashit($home['path']):'/';
	$referer = qtranxf_parseURL($referer);

	$result = array();
	//$result['language'] = $q_config['default_language'];
	$result['url'] = $url;
	$result['original_url'] = $url;
	$result['host'] = $host;
	$result['redirect'] = false;
	$result['internal_referer'] = false;
	$result['home'] = $home['path'];

	$site = qtranxf_parseURL(get_option('siteurl'));
	$site['path'] = isset($site['path'])?trailingslashit($site['path']):'/';
	$language_neutral_path=$site['path']!=$home['path'] && qtranxf_startsWith($url,$site['path']);
	if($language_neutral_path){
		$url = substr($url,strlen($site['path']));
	}else{
		$url = substr($url,strlen($home['path']));
	}
	//$result['site_path']=$site['path'];

	$doredirect=false;
	$lang_url=null;
	switch($q_config['url_mode']) {
		case QTX_URL_PATH:
			// pre url
			if(!$language_neutral_path && $url) {
				// might have language information
				if(preg_match("#^([a-z]{2})(/.*)?$#i",$url,$match)) {
					$lang_url = qtranxf_resolveLangCase($match[1],$doredirect);
					if($lang_url){
						// found language information
						//$result['language'] = $lang_url;
						$result['url'] = $home['path'].substr($url, 3);
					}
				}
			}
			break;
		case QTX_URL_DOMAIN:
			// pre domain
			if($host) {
				if(preg_match("#^([a-z]{2})\.#i",$host,$match)) {
					$lang_url = qtranxf_resolveLangCase($match[1],$doredirect);
					if($lang_url){
						// found language information
						//$result['language'] = $lang_url;
						$result['host'] = substr($host, 3);
					}
				}
			}
		//case QTX_URL_DOMAIN_SET:
		default: break;
	}
	//qtranxf_dbg_log('qtranxf_detect_language: url='.$url);
	//qtranxf_dbg_log('qtranxf_detect_language: lang_url='.$lang_url);

	// check if referrer is internal
	if( $referer['host']==$result['host'] && qtranxf_startsWith($referer['path'], $home['path']) ) {
		// user coming from internal link
		$result['internal_referer'] = true;
	}

	$lang=null;
	if(isset($_GET['lang'])){// language override given
		$lang = qtranxf_resolveLangCase($_GET['lang'],$doredirect);
		if($lang){
			$result['url'] = preg_replace("#(&|\?)lang=".$lang."&?#i","$1",$result['url']);
			$result['url'] = preg_replace("#[\?\&]+$#i","",$result['url']);
		} elseif($home['host'] == $result['host'] && $home['path'] == $result['url']) {
			if(empty($referer['host'])||!$q_config['hide_default_language']) {
				$result['redirect'] = true;
			}else{
				// check if activating language detection is possible
				if(preg_match("#^([a-z]{2}).#i",$referer['host'],$match)) {
					$cs=false;
					$lang = qtranxf_resolveLangCase($match[1],$cs);
					if($lang) {
						// found language information
						$referer['host'] = substr($referer['host'], 3);
					}
				}
				if(!$result['internal_referer']) {
					// user coming from external link
					$result['redirect'] = true;
				}
			}
		}
	}

	if($lang){
		//qtranxf_dbg_log('qtranxf_detect_language: GET[lang]='.$lang);
		if($lang_url && $lang !== $lang_url) $doredirect=true;
	}else if($lang_url){
		$lang = $lang_url;
		if($q_config['hide_default_language'] && $lang_url == $q_config['default_language'])
			$doredirect=true;
	}else{
		$lang=qtranxf_get_language_cookie();
		if($lang){
			$cs=false;
			$lang=qtranxf_resolveLangCase($lang,$cs);
			//qtranxf_dbg_log('qtranxf_detect_language: cookie: lang='.$lang);
		}

		if(!$lang && $q_config['detect_browser_language']){
			$lang=qtranxf_http_negotiate_language();
			//qtranxf_dbg_log('qtranxf_detect_language: browser: lang='.$lang);
		}

		if(!$lang)
			$lang = $q_config['default_language'];

		if(!defined('WP_ADMIN') && !defined('DOING_CRON')
			&& (!$q_config['hide_default_language'] || $lang != $q_config['default_language'])
		){
			//qtranxf_dbg_log('qtranxf_detect_language: language_neutral_path=',$language_neutral_path);
			if(!$language_neutral_path){
				$url_parsed=parse_url($url);//$url now does not have language information 
				//qtranxf_dbg_log('qtranxf_detect_language: url_parsed:',$url_parsed);
				if(isset($url_parsed['path'])){
					$path=$url_parsed['path'];//$path does not have / in front
					$language_neutral_path=qtranxf_language_neutral_path($path);
				}
			}
			//qtranxf_dbg_log('qtranxf_detect_language: language_neutral_path=',$language_neutral_path);
			if(!$language_neutral_path){
				//qtranxf_dbg_log('qtranxf_detect_language: ignored_file_type=false; path='.$path);
				$doredirect=true;
			}
		}
	}

	//qtranxf_dbg_log('doredirect=',$doredirect);
	if($doredirect){
		$urlto=$result['host'].$result['url'];
		if(isset($_SERVER['HTTPS']))
			$urlto='https://'.$urlto;
		else
			$urlto='http://'.$urlto;
		//if(isset($_SERVER['QUERY_STRING'])) $url=
		$target=qtranxf_convertURL($urlto,$lang,false,!$q_config['hide_default_language']);
		$target = apply_filters('qtranslate_language_detect_redirect', $target, $result);
		if($target!==false){
			//qtranxf_dbg_log('qtranxf_detect_language:doredirect: url='.$url);
			//qtranxf_dbg_log('qtranxf_detect_language:doredirect: target='.$target);
			//qtranxf_dbg_log('qtranxf_detect_language:doredirect: POST: ',$_POST);
			$url_parsed=parse_url($url);
			$tgt_parsed=parse_url($target);
			$url_parsed_path=isset($url_parsed['path'])?rtrim($url_parsed['path'],'/?#'):'';
			$tgt_parsed_path=isset($tgt_parsed['path'])?rtrim($tgt_parsed['path'],'/?#'):'';
			if($url_parsed_path!=$tgt_parsed_path){
				qtranxf_set_language_cookie($lang,$result['home']);
				wp_redirect($target);
				//header('Location: '.$target);
				exit();
			}
		}
	}
	qtranxf_set_language_cookie($lang,$result['home']);
	$result['language'] = $lang;
	return $result;
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
}

function qtranxf_http_negotiate_language(){
	if(function_exists('http_negotiate_language')){
		$supported=array();
		$supported[]=str_replace('_','-',$q_config['locale'][$q_config['default_language']]);
		foreach($q_config['available_languages'] as $lang){
			$supported[]=str_replace('_','-',$q_config['locale'][$lang]);
		}
		$lang = http_negotiate_language($supported);
		//qtranxf_dbg_log('qtranxf_http_negotiate_language:http_negotiate_language: lang='.$lang);
	}else{
		$lang = qtranxf_get_browser_language();
		//qtranxf_dbg_log('qtranxf_http_negotiate_language:qtranxf_get_browser_language: lang='.$lang);
	}
	return $lang;
}

function qtranxf_init() {
	global $q_config;

	do_action('qtranslate_init_begin');

/*
	// Check for WP Secret Key Mismatch
	global $wp_default_secret_key;
	if(strpos($q_config['url_info']['url'],'wp-login.php')!==false && defined('AUTH_KEY') && isset($wp_default_secret_key) && $wp_default_secret_key != AUTH_KEY) {
		global $error;
		$error = __('Your $wp_default_secret_key is mismatchting with your AUTH_KEY. This might cause you not to be able to login anymore.', 'qtranslate');
	}
*/

	// load plugin translations
	load_plugin_textdomain('qtranslate', false, dirname(plugin_basename( __FILE__ )).'/lang');

	foreach($q_config['text_field_filters'] as $nm){
		add_filter($nm, 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
	}
/*
	//"WordPress SEO" plugin support - not yet
	//if(is_plugin_active( 'wordpress-seo/wp-seo.php' )){//in admin only
	if ( defined( 'WPSEO_FILE' ) ) {
		//add_filter('wpseo_replacements', 'qtranxf_wpseo_replacements', 0);
		add_filter('wpseo_replacements', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 0);
	}
*/
	//allow other plugins to initialize whatever they need for qTranslate
	do_action('qtranslate_init');
}

function qtranxf_validateBool($var, $default) {
	if($var==='0') return false; elseif($var==='1') return true; else return $default;
}

function qtranxf_load_option($nm) {
	global $q_config;
	$val = get_option('qtranslate_'.$nm);
	if($val===FALSE) return;
	$q_config[$nm]=$val;
}

function qtranxf_load_option_array($nm) {
	global $q_config;
	$vals = get_option('qtranslate_'.$nm);
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

function qtranxf_load_option_bool($nm) {
	global $q_config;
	$val = get_option('qtranslate_'.$nm);
	if($val==='0') $q_config[$nm] = false;
	elseif($val==='1') $q_config[$nm] = true;
}

// loads config via get_option and defaults to values set on top
function qtranxf_loadConfig() {
	global $q_config;

	//for other plugins and jave scripts to be able to fork by version
	//$q_config['version'] = get_plugin_data( plugins_url( '/qtranslate.php', __FILE__ ), false, false )['Version'];

	// Load everything
	$language_names = get_option('qtranslate_language_names');
	$enabled_languages = get_option('qtranslate_enabled_languages');
	$default_language = get_option('qtranslate_default_language');
	$flag_location = get_option('qtranslate_flag_location');
	$flags = get_option('qtranslate_flags');
	$locales = get_option('qtranslate_locales');
	$na_messages = get_option('qtranslate_na_messages');
	$date_formats = get_option('qtranslate_date_formats');
	$time_formats = get_option('qtranslate_time_formats');
	$use_strftime = get_option('qtranslate_use_strftime');
	$ignore_file_types = get_option('qtranslate_ignore_file_types');
	$url_mode = get_option('qtranslate_url_mode');
	$term_name = get_option('qtranslate_term_name');

	qtranxf_load_option_bool('editor_mode');//will be integer later

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
	if(!is_array($enabled_languages)) $enabled_languages = $q_config['enabled_languages'];
	if(!is_array($term_name)) $term_name = $q_config['term_name'];
	if(empty($default_language)) $default_language = $q_config['default_language'];
	if(empty($use_strftime)) $use_strftime = $q_config['use_strftime'];
	if(empty($url_mode)) $url_mode = $q_config['url_mode'];
	if(!is_string($flag_location) || $flag_location==='') $flag_location = $q_config['flag_location'];

	qtranxf_load_option_bool('detect_browser_language');
	qtranxf_load_option_bool('hide_untranslated');
	qtranxf_load_option_bool('show_displayed_language_prefix');
	qtranxf_load_option_bool('auto_update_mo');
	qtranxf_load_option_bool('hide_default_language');
	qtranxf_load_option_bool('qtrans_compatibility');

	// url fix for upgrading users
	$flag_location = trailingslashit(preg_replace('#^wp-content/#','',$flag_location));

	// check for invalid permalink/url mode combinations
	$permalink_structure = get_option('permalink_structure');
	if($permalink_structure===""||strpos($permalink_structure,'?')!==false||strpos($permalink_structure,'index.php')!==false) $url_mode = QTX_URL_QUERY;

	// overwrite default values with loaded values
	$q_config['date_format'] = $date_formats;
	$q_config['time_format'] = $time_formats;
	$q_config['not_available'] = $na_messages;
	$q_config['locale'] = $locales;
	$q_config['flag'] = $flags;
	$q_config['language_name'] = $language_names;
	$q_config['enabled_languages'] = $enabled_languages;
	$q_config['default_language'] = $default_language;
	$q_config['flag_location'] = $flag_location;
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

function qtranxf_dateFromPostForCurrentLanguage($old_date, $format ='') {
	global $post;
	return qtranxf_strftime(qtranxf_convertDateFormat($format), mysql2date('U',$post->post_date), $old_date);
}

function qtranxf_dateModifiedFromPostForCurrentLanguage($old_date, $format ='') {
	global $post;
	return qtranxf_strftime(qtranxf_convertDateFormat($format), mysql2date('U',$post->post_modified), $old_date);
}

function qtranxf_timeFromPostForCurrentLanguage($old_date, $format = '', $post = null, $gmt = false) {
	$post = get_post($post);
	
	$post_date = $gmt? $post->post_date_gmt : $post->post_date;
	return qtranxf_strftime(qtranxf_convertTimeFormat($format), mysql2date('U',$post_date), $old_date);
}

function qtranxf_timeModifiedFromPostForCurrentLanguage($old_date, $format = '', $gmt = false) {
	global $post;
	$post_date = $gmt? $post->post_modified_gmt : $post->post_modified;
	return qtranxf_strftime(qtranxf_convertTimeFormat($format), mysql2date('U',$post_date), $old_date);
}

function qtranxf_dateFromCommentForCurrentLanguage($old_date, $format ='') {
	global $comment;
	return qtranxf_strftime(qtranxf_convertDateFormat($format), mysql2date('U',$comment->comment_date), $old_date);
}

function qtranxf_timeFromCommentForCurrentLanguage($old_date, $format = '', $gmt = false, $translate = true) {
	if(!$translate) return $old_date;
	global $comment;
	$comment_date = $gmt? $comment->comment_date_gmt : $comment->comment_date;
	return qtranxf_strftime(qtranxf_convertTimeFormat($format), mysql2date('U',$comment_date), $old_date);
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

function qtranxf_convertBlogInfoURL($url, $what) {
	if($what=='stylesheet_url') return $url;
	if($what=='template_url') return $url;
	if($what=='template_directory') return $url;
	if($what=='stylesheet_directory') return $url;
	return qtranxf_convertURL($url);
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
//qtranxf_dbg_echo('qtranxf_language_neutral_path: path='.$path);
//if(preg_match("#^(wp-comments-post.php|wp-login.php|wp-signup.php|wp-register.php|wp-cron.php|wp-admin/)#", $path)) return true;
	if(preg_match("#^(wp-.*\.php|wp-admin/)#", $path)) return true;
	if(qtranxf_ignored_file_type($path)) return true;
	return false;
}

if (!function_exists('qtranxf_convertURL')){
function qtranxf_convertURL($url='', $lang='', $forceadmin = false, $showDefaultLanguage = false) {
	global $q_config;

	if($lang=='') $lang = $q_config['language'];
	if(empty($url)){
		if( !defined('WP_ADMIN') && defined('QTS_VERSION') ){
			//quick workaround, but need a permanent solution
			$url = qts_get_url($lang);
			//qtranxf_dbg_echo('qtranxf_convertURL: url=',$url);
			if(!empty($url)){
				if($q_config['hide_default_language'] && $lang==$q_config['default_language'] && $showDefaultLanguage)
					$url=qtranxf_convertURL($url,$lang,$forceadmin,true);
				return $url;
			}
		}
		$url = esc_url($q_config['url_info']['url']);
	}
	if(defined('WP_ADMIN')&&!$forceadmin) return $url;
	if(!qtranxf_isEnabled($lang)) return '';

	// & workaround
	$url = str_replace('&amp;','&',$url);
	$url = str_replace('&#038;','&',$url);

	// check for trailing slash
	$nottrailing = (strpos($url,'?')===false && strpos($url,'#')===false && substr($url,-1,1)!='/');

	// check if it's an external link
	$urlinfo = qtranxf_parseURL($url);
	$home = rtrim(get_option('home'),'/');
	if($urlinfo['host']!='') {
		// check for already existing pre-domain language information
		if($q_config['url_mode'] == QTX_URL_DOMAIN && preg_match("#^([a-z]{2}).#i",$urlinfo['host'],$match)) {
			if(qtranxf_isEnabled($match[1])) {
				// found language information, remove it
				$url = preg_replace("/".$match[1]."\./i","",$url, 1);
				// reparse url
				$urlinfo = qtranxf_parseURL($url);
			}
		}
		if(substr($url,0,strlen($home))!=$home) {
			return $url;
		}
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
	if(preg_match("#(&|\?)lang=([^&\#]+)#i",$url,$match) && qtranxf_isEnabled($match[2])) {
		$url = preg_replace("#(&|\?)lang=".$match[2]."&?#i","$1",$url);
	}

	// remove any slashes out front
	$url = ltrim($url,"/");

	// remove any useless trailing characters
	$url = rtrim($url,"?&");
	
	// re-parse url without home path
	$urlinfo = qtranxf_parseURL($url);

	// check if its a link to an ignored file type
	//$ignore_file_types = preg_split('/\s*,\s*/', strtolower($q_config['ignore_file_types']));
	//$pathinfo = pathinfo($urlinfo['path']);
	//if(isset($pathinfo['extension']) && in_array(strtolower($pathinfo['extension']), $ignore_file_types)) {
	//if(qtranxf_ignored_file_type($urlinfo['path'])){
	//	return $home."/".$url;
	//}
	
	// ignore wp internal links
	//if(preg_match("#^(wp-login.php|wp-signup.php|wp-register.php|wp-admin/)#", $url)) {
	if(qtranxf_language_neutral_path($url)) {
		return $home."/".$url;
	}

	switch($q_config['url_mode']) {
		case QTX_URL_PATH:	// pre url
			// might already have language information
			//qtranxf_dbg_echo('qtranxf_convertURL:url='.$url);
			if(preg_match("#^([a-z]{2})(/|$)#i",$url,$match)) {
				if(qtranxf_isEnabled($match[1])) {
					// found language information, remove it
					$url = substr($url, 3);
				}
			}
			if(!$q_config['hide_default_language'] || $lang!=$q_config['default_language'] || $showDefaultLanguage) $url = $lang."/".$url;
			break;
		case QTX_URL_DOMAIN:	// pre domain
			if(!$q_config['hide_default_language']||$lang!=$q_config['default_language']) $home = preg_replace("#//#","//".$lang.".",$home,1);
			break;
		default: // query
			if(!$q_config['hide_default_language'] || $lang!=$q_config['default_language'] || $showDefaultLanguage){
				if(strpos($url,'?')===false) {
					$url .= '?';
				} else {
					$url .= '&';
				}
				$url .= "lang=".$lang;
			}
	}

	// see if cookies are activated
	if($q_config['hide_default_language'] && !$showDefaultLanguage && !$q_config['cookie_enabled'] && !$q_config['url_info']['internal_referer'] && $urlinfo['path'] == '' && $lang == $q_config['default_language'] && $q_config['language'] != $q_config['default_language']) {
		// :( now we have to make unpretty URLs
		$url = preg_replace("#(&|\?)lang=".$match[2]."&?#i","$1",$url);
		if(strpos($url,'?')===false) {
			$url .= '?';
		} else {
			$url .= '&';
		}
		$url .= "lang=".$lang;
	}

	// &amp; workaround
	$complete = str_replace('&','&amp;',$home."/".$url);

	// remove trailing slash if there wasn't one to begin with
	if($nottrailing && strpos($complete,'?')===false && strpos($complete,'#')===false && substr($complete,-1,1)=='/')
		$complete = substr($complete,0,-1);

	$complete = apply_filters('qtranslate_convertURL',$complete,$lang);
	return $complete;
}
}

//if (!function_exists('qtranxf_get_split_blocks')){
// split text at all language comments and quick tags
function qtranxf_get_language_blocks($text) {
	$split_regex = "#(<!--:[a-z]{2}-->|<!--:-->|\[:[a-z]{2}\])#ism";
	return preg_split($split_regex, $text, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
}
//}

if (!function_exists('qtranxf_split')){
function qtranxf_split($text, $quicktags = true) {
	$blocks = qtranxf_get_language_blocks($text);
	return qtranxf_split_blocks($blocks,$quicktags);
}
}

if (!function_exists('qtranxf_split_blocks')){
function qtranxf_split_blocks($blocks, $quicktags = true) {
	global $q_config;
	$result = array();
	foreach($q_config['enabled_languages'] as $language) {
		$result[$language] = '';
	}
	$current_language = false;
	foreach($blocks as $block) {
		# detect language tags
		if(preg_match("#^<!--:([a-z]{2})-->$#ism", $block, $matches)) {
			$current_language = $matches[1];
			//if(!qtranxf_isEnabled($current_language)) $current_language = false;//still need it
			continue;
		// detect quicktags
		} elseif($quicktags && preg_match("#^\[:([a-z]{2})\]$#ism", $block, $matches)) {
			$current_language = $matches[1];
			//if(!qtranxf_isEnabled($current_language)) $current_language = false;//still need it
			continue;
		// detect ending tags
		} elseif(preg_match("#^<!--:-->$#ism", $block, $matches)) {
			$current_language = false;
			continue;
		}
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

function qtranxf_join($texts) {
	if(!is_array($texts)) $texts = qtranxf_split($texts, false);
	qtranxf_join_c($texts);
}

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

function qtranxf_join_b($texts) {
	$text = '';
	foreach($texts as $lang => $lang_text) {
		if(empty($lang_text)) continue;
		$text .= '[:'.$lang.']'.$lang_text;
	}
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
	global $q_config;
	// return full string if language is not enabled
	//if(!qtranxf_isEnabled($lang)) return $text;//why?
	if(is_array($text)) {
		// handle arrays recursively
		foreach($text as $key => $t) {
			$text[$key] = qtranxf_use($lang,$text[$key],$show_available,$show_empty);
		}
		return $text;
	}

	if(is_object($text)||@get_class($text) == '__PHP_Incomplete_Class') {
		foreach(get_object_vars($text) as $key => $t) {
			$text->$key = qtranxf_use($lang,$text->$key,$show_available,$show_empty);
		}
		return $text;
	}

	// prevent filtering weird data types and save some resources
	if(!is_string($text) || empty($text))//|| $text) == ''
		return $text;

	// get content
	$blocks = qtranxf_get_language_blocks($text);
	//if($text==='[:ru][:en]EN'){
	//qtranxf_dbg_echo('qtranxf_use:blocks:',$blocks,true);
	//}
	if(count($blocks)<=1)//no language is encoded in the $text, the most frequent case
		return $text;
	$content = qtranxf_split_blocks($blocks);
	//$content = qtranxf_split($text);

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
		$normal_seperator = $match[1];
		$end_seperator = $match[2];
		// build available languages string backward
		$i = 0;
		foreach($available_languages as $language) {
			if($i==1) $language_list  = $end_seperator.$language_list;
			if($i>1) $language_list  = $normal_seperator.$language_list;
			$language_list = "<a href=\"".qtranxf_convertURL('', $language, false, true)."\">".$q_config['language_name'][$language]."</a>".$language_list;
			$i++;
		}
	}
	return "<p>".preg_replace('/%LANG:([^:]*):([^%]*)%/', $language_list, $q_config['not_available'][$lang])."</p>";
}
}

function qtranxf_showAllSeperated($text) {
	if(empty($text)) return $text;
	global $q_config;
	$result = '';
	foreach(qtranxf_getSortedLanguages() as $language) {
		$result .= $q_config['language_name'][$language].':'.PHP_EOL.qtranxf_use($language, $text).PHP_EOL.PHP_EOL;
	}
	return $result;
}

function qtranxf_add_css ()
{
	wp_register_style( 'qtranslate-style', plugins_url('qtranslate.css', __FILE__) );
	wp_enqueue_style( 'qtranslate-style' );
}

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
?>
