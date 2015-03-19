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

/* qTranslate-X Utilities */

// /*
if(WP_DEBUG){
	if(!function_exists('qtranxf_dbg_log')){
		function qtranxf_dbg_log($msg,$var='novar',$bt=false,$exit=false){
			//$d=ABSPATH.'/wp-logs';
			//if(!file_exists($d)) mkdir($d);
			//$f=$d.'/qtranslate.log';
			$f=WP_CONTENT_DIR.'/debug-qtranslate.log';
			if( $var !== 'novar' )
				$msg .= var_export($var,true);
			if($bt){
				$msg .= PHP_EOL.'backtrace:'.PHP_EOL.var_export(debug_backtrace(),true);
			}
			error_log($msg.PHP_EOL,3,$f);
			if($exit) exit();
		}
	}
	if(!function_exists('qtranxf_dbg_echo')){
		function qtranxf_dbg_echo($msg,$var='novar',$bt=false,$exit=false){
			if( $var !== 'novar' )
				$msg .= var_export($var,true);
			echo $msg."<br>\n";
			if($bt){
				debug_print_backtrace();
			}
			if($exit) exit();
		}
	}
	if(!function_exists('qtranxf_dbg_log_if')){
		function qtranxf_dbg_log_if($condition,$msg,$var='novar',$bt=false,$exit=false){
			if($condition)qtranxf_dbg_log($msg,$var,$bt,$exit);
		}
	}
	if(!function_exists('qtranxf_dbg_echo_if')){
		function qtranxf_dbg_echo_if($condition,$msg,$var='novar',$bt=false,$exit=false){
			if($condition)qtranxf_dbg_echo($msg,$var,$bt,$exit);
		}
	}
	assert_options(ASSERT_BAIL,true);

	function qtranxf_do_tests(){
		if(file_exists(dirname(__FILE__).'/dev/qtx-tests.php'))
			require_once(dirname(__FILE__).'/dev/qtx-tests.php');
	}
	//add_action('qtranslate_init_language','qtranxf_do_tests');

}else{
	if(!function_exists('qtranxf_dbg_log')){ function qtranxf_dbg_log($msg,$var=null,$bt=false,$exit=false){} }
	if(!function_exists('qtranxf_dbg_echo')){ function qtranxf_dbg_echo($msg,$var=null,$bt=false,$exit=false){} }
	if(!function_exists('qtranxf_dbg_log_if')){ function qtranxf_dbg_log_if($condition,$msg,$var=null,$bt=false,$exit=false){} }
	if(!function_exists('qtranxf_dbg_echo_if')){ function qtranxf_dbg_echo_if($condition,$msg,$var=null,$bt=false,$exit=false){} }
	//assert_options(ASSERT_ACTIVE,false);
	//assert_options(ASSERT_WARNING,false);
	//assert_options(ASSERT_QUIET_EVAL,true);
}// */

function qtranxf_parseURL($url) {
	//this is not the same as native parse_url and so it is in use
	//it should also work quicker than native parse_url, so we should keep it?
	//preg_match('!(?:(\w+)://)?(?:(\w+)\:(\w+)@)?([^/:]+)?(?:\:(\d*))?([^#?]+)?(?:\?([^#]+))?(?:#(.+$))?!',$url,$out);
	preg_match('!(?:(\w+)://)?(?:(\w+)\:(\w+)@)?([^/:?#]+)?(?:\:(\d*))?([^#?]+)?(?:\?([^#]+))?(?:#(.+$))?!',$url,$out);
	//qtranxf_dbg_log('qtranxf_parseURL('.$url.'): out:',$out);
	//new code since 3.2.8 - performance improvement
	$result = array();
	if(!empty($out[1])) $result['scheme'] = $out[1];
	if(!empty($out[2])) $result['user'] = $out[2];
	if(!empty($out[3])) $result['pass'] = $out[3];
	if(!empty($out[4])) $result['host'] = $out[4];
	if(!empty($out[6])) $result['path'] = $out[6];
	if(!empty($out[7])) $result['query'] = $out[7];
	if(!empty($out[8])) $result['fragment'] = $out[8];
	/*
	//new code since 3.2-b2, older version produces warnings in the debugger
	$result = @array(
		'scheme' => isset($out[1]) ? $out[1] : '',
		'user' => isset($out[2]) ? $out[2] : '',
		'pass' => isset($out[3]) ? $out[3] : '',
		'host' => isset($out[4]) ? $out[4] : '',
		'path' => isset($out[6]) ? $out[6] : '',
		'query' => isset($out[7]) ? $out[7] : '',
		'fragment' => isset($out[8]) ? $out[8] : ''
		);
	*/
	if(!empty($out[5])) $result['host'] .= ':'.$out[5];
/*
	//this older version produce warnings in the debugger
	$result = @array(
		"scheme" => $out[1],
		"host" => $out[4].(($out[5]=='')?'':':'.$out[5]),
		"user" => $out[2],
		"pass" => $out[3],
		"path" => $out[6],
		"query" => $out[7],
		"fragment" => $out[8]
		);
*/
/* not the same as above for relative url without host like 'path/1/2/3'
	$result = parse_url($url) + array(
		'scheme' => '',
		'host' => '',
		'user' => '',
		'pass' => '',
		'path' => '',
		'query' => '',
		'fragment' => ''
	);
	isset($result['port']) and $result['host'] .= ':'. $result['port'];
*/
	return $result;
}

/**
 * @since 3.2.8
 */
function qtranxf_buildURL($urlinfo,$homeinfo) {
	//qtranxf_dbg_log('qtranxf_buildURL: $urlinfo:',$urlinfo);
	//qtranxf_dbg_log('qtranxf_buildURL: $homeinfo:',$homeinfo);
	$url = (empty($urlinfo['scheme']) ? $homeinfo['scheme'] : $urlinfo['scheme']).'://';
	if(!empty($urlinfo['user'])){
		$url .= $urlinfo['user'];
		if(!empty($urlinfo['pass'])) $url .= ':'.$urlinfo['pass'];
		$url .= '@';
	}elseif(!empty($homeinfo['user'])){
		$url .= $homeinfo['user'];
		if(!empty($homeinfo['pass'])) $url .= ':'.$homeinfo['pass'];
		$url .= '@';
	}
	$url .= empty($urlinfo['host']) ? $homeinfo['host'] : $urlinfo['host'];
	if(!empty($urlinfo['path-base'])) $url .= $urlinfo['path-base'];
	if(!empty($urlinfo['wp-path'])) $url .= $urlinfo['wp-path'];
	if(!empty($urlinfo['query'])) $url .= '?'.$urlinfo['query'];
	if(!empty($urlinfo['fragment'])) $url .= '#'.$urlinfo['fragment'];
	//qtranxf_dbg_log('qtranxf_buildURL: $url:',$url);
	return $url;
}

/**
 * @since 3.2.8 Copies the data needed for qtranxf_buildURL and qtranxf_url_set_language
 */
function qtranxf_copy_url_info($urlinfo) {
	$r = array();
	if(isset($urlinfo['scheme'])) $r['scheme'] = $urlinfo['scheme'];
	if(isset($urlinfo['user'])) $r['user'] = $urlinfo['user'];
	if(isset($urlinfo['pass'])) $r['pass'] = $urlinfo['pass'];
	if(isset($urlinfo['host'])) $r['host'] = $urlinfo['host'];
	if(isset($urlinfo['path-base'])) $r['path-base'] = $urlinfo['path-base'];
	if(isset($urlinfo['wp-path'])) $r['wp-path'] = $urlinfo['wp-path'];
	if(isset($urlinfo['query'])) $r['query'] = $urlinfo['query'];
	if(isset($urlinfo['fragment'])) $r['fragment'] = $urlinfo['fragment'];
	if(isset($urlinfo['query_amp'])) $r['query_amp'] = $urlinfo['query_amp'];
	return $r;
}

function qtranxf_get_address_info($option_name) {
	$info = qtranxf_parseURL( get_option($option_name) );
	if(isset($info['path'])){
		$info['path-length'] = strlen($info['path']);
	}else{
		$info['path'] = '';
		$info['path-length'] = 0;
	}
	return $info;
}

function qtranxf_get_home_info() {
	static $home_info;
	if(!$home_info) $home_info = qtranxf_get_address_info('home');
	return $home_info;
}

function qtranxf_get_site_info() {
	static $site_info;
	if(!$site_info) $site_info = qtranxf_get_address_info('siteurl');
	return $site_info;
}

function qtranxf_get_url_info($url){
	$urlinfo = qtranxf_parseURL($url);
	qtranxf_complete_url_info($urlinfo);
	qtranxf_complete_url_info_path($urlinfo);
	return $urlinfo;
}

function qtranxf_complete_url_info(&$urlinfo){
	if(!isset($urlinfo['path'])) $urlinfo['path'] = '';
	$path = &$urlinfo['path'];
	$home_info = qtranxf_get_home_info();
	$site_info = qtranxf_get_site_info();
	$home_path = $home_info['path'];
	$site_path = $site_info['path'];
	$home_path_len = $home_info['path-length'];
	$site_path_len = $site_info['path-length'];
	if($home_path_len > $site_path_len){
		if(qtranxf_startsWith($path,$home_path)){
			$urlinfo['path-base'] = $home_path;
			$urlinfo['path-base-length'] = $home_path_len;
			$urlinfo['doing_front_end'] = true;
		}elseif(qtranxf_startsWith($path,$site_path)){
			$urlinfo['path-base'] = $site_path;
			$urlinfo['path-base-length'] = $site_path_len;
			$urlinfo['doing_front_end'] = false;
		}
	}elseif($home_path_len < $site_path_len){
		if(qtranxf_startsWith($path,$site_path)){
			$urlinfo['path-base'] = $site_path;
			$urlinfo['path-base-length'] = $site_path_len;
			$urlinfo['doing_front_end'] = false;
		}elseif(qtranxf_startsWith($path,$home_path)){
			$urlinfo['path-base'] = $home_path;
			$urlinfo['path-base-length'] = $home_path_len;
			$urlinfo['doing_front_end'] = true;
		}
	}elseif($home_path != $site_path){
		if(qtranxf_startsWith($path,$home_path)){
			$urlinfo['path-base'] = $home_path;
			$urlinfo['path-base-length'] = $home_path_len;
			$urlinfo['doing_front_end'] = true;
		}elseif(qtranxf_startsWith($path,$site_path)){
			$urlinfo['path-base'] = $site_path;
			$urlinfo['path-base-length'] = $site_path_len;
			$urlinfo['doing_front_end'] = false;
		}
	}else{//$home_path == $site_path
		if(qtranxf_startsWith($path,$home_path)){
			$urlinfo['path-base'] = $home_path;
			$urlinfo['path-base-length'] = $home_path_len;
		}
	}
}

/**
 * @since 3.2.8
 */
function qtranxf_complete_url_info_path(&$urlinfo){
	if(isset($urlinfo['path-base'])){
		if( empty($urlinfo['path-base']) ){
			$urlinfo['wp-path'] = $urlinfo['path'];
		}elseif( !empty($urlinfo['path']) && qtranxf_startsWith($urlinfo['path'],$urlinfo['path-base']) ){
			//qtranxf_dbg_log('qtranxf_complete_url_info_path: urlinfo: ',$urlinfo);
			if(isset($urlinfo['path'][$urlinfo['path-base-length']])){
				if($urlinfo['path'][$urlinfo['path-base-length']] == '/'){
					$urlinfo['wp-path'] = substr($urlinfo['path'],$urlinfo['path-base-length']);
				}
			}else{
				$urlinfo['wp-path'] = '';
			}
		}
	}
	//$urlinfo['wp-path'] is not set, means url does not belong to this WP installation
}

/**
 * Simplified version of WP's add_query_arg
 * @since 3.2.8
 */
function qtranxf_add_query_arg(&$query, $key_value){
	if(empty($query)) $query = $key_value;
	else $query .= '&'.$key_value;
}

/**
 * Simplified version of WP's remove_query_arg
 * @since 3.2.8
 */
function qtranxf_del_query_arg(&$query, $key){
	//$key_value;
	$match;
	while(preg_match('/(&|&amp;|&#038;|^)('.$key.'=[^&]+)(&|&amp;|&#038;|$)/i',$query,$match)){
		//$key_value = $match[2];
		$p = strpos($query,$match[2]);
		$n = strlen($match[2]);
		if(!empty($match[1])) { $l = strlen($match[1]); $p -= $l; $n += $l; }
		elseif(!empty($match[3])) { $l = strlen($match[3]); $n += $l; }
		//qtranxf_dbg_log('qtranxf_del_query_arg: query: '.$query.'; p='.$p.'; n=',$n);
		$query = substr_replace($query,'',$p,$n);
		//qtranxf_dbg_log('qtranxf_del_query_arg: query: ',$query);
	}
	//return $key_value;
}

/*
 * @since 2.3.8 simplified version of esc_url
*/
function qtranxf_sanitize_url($url)
{
	$url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\\x80-\\xff]|i', '', $url);
	$strip = array('%0d', '%0a', '%0D', '%0A');
	$count;
	do{ $url = str_replace( $strip, '', $url, $count ); } while($count);
	return $url;
}

function qtranxf_stripSlashesIfNecessary($str) {
	if(1==get_magic_quotes_gpc()) {
		$str = stripslashes($str);
	}
	return $str;
}

function qtranxf_insertDropDownElement($language, $url, $id){
	global $q_config;
	$html ="
		var sb = document.getElementById('qtranxs_select_".$id."');
		var o = document.createElement('option');
		var l = document.createTextNode('".$q_config['language_name'][$language]."');
		";
	if($q_config['language']==$language)
		$html .= "o.selected = 'selected';";
		$html .= "
		o.value = '".addslashes(htmlspecialchars_decode($url, ENT_NOQUOTES))."';
		o.appendChild(l);
		sb.appendChild(o);
		";
	return $html;
}

function qtranxf_get_domain_language($host){
	global $q_config;
	//todo should have hash host->lang
	//foreach($q_config['enabled_languages'] as $lang){
	//	if(!isset($q_config['domains'][$lang])) continue;
	//	if($q_config['domains'][$lang] != $host) continue;
	//	return $lang;
	//}
	foreach($q_config['domains'] as $lang => $h){
		if($h == $host) return $lang;
	}
}

function qtranxf_external_host_ex($host,$homeinfo){
	global $q_config;
	//$homehost = qtranxf_get_home_info()['host'];
	switch($q_config['url_mode']){
		case QTX_URL_QUERY: return $homeinfo['host'] != $host;
		case QTX_URL_PATH:
		case QTX_URL_DOMAIN: return !qtranxf_endsWith($host,$homeinfo['host']);
		case QTX_URL_DOMAINS:
			foreach($q_config['domains'] as $lang => $h){
				if($h == $host) return false;
			}
			if($homeinfo['host'] == $host) return false;
		default: return true;
	}
}

function qtranxf_external_host($host){
	$homeinfo=qtranxf_get_home_info();
	return qtranxf_external_host_ex($host,$homeinfo);
}

function qtranxf_isMultilingual($str){
	return preg_match('/(<!--:[a-z]{2}-->|\[:[a-z]{2}\])/im',$str);
}

if (!function_exists('qtranxf_getLanguage')){
function qtranxf_getLanguage() {
	global $q_config;
	return $q_config['language'];
}
}

function qtranxf_getLanguageName($lang = '') {
    global $q_config;
		if($lang=='' || !qtranxf_isEnabled($lang)) $lang = $q_config['language'];
    return $q_config['language_name'][$lang];
}

function qtranxf_isEnabled($lang) {
	global $q_config;
	return in_array($lang, $q_config['enabled_languages']);
}

/**
 * @since 3.2.8 - change code to improve performance
 */
function qtranxf_startsWith($s, $n) {
	$l = strlen($n);
	if($l>strlen($s)) return false;
	for($i=0;$i<$l;++$i){
		if($s[$i] != $n[$i])
			return false;
	}
	//if($n == substr($s,0,strlen($n))) return true;
	return true;
}

/**
 * @since 3.2.8
 */
function qtranxf_endsWith($s, $n) {
	$l = strlen($n);
	$b = strlen($s) - $l;
	if($b < 0) return false;
	for($i=0;$i<$l;++$i){
		if($s[$b+$i] != $n[$i])
			return false;
	}
	return true;
}

function qtranxf_getAvailableLanguages($text) {
	global $q_config;
	$result = array();
	$content = qtranxf_split($text);
	foreach($content as $language => $lang_text) {
		$lang_text = trim($lang_text);
		if(!empty($lang_text)) $result[] = $language;
	}
	if(sizeof($result)==0) {
		// add default language to keep default URL
		$result[] = $q_config['language'];
	}
	return $result;
}

function qtranxf_isAvailableIn($post_id, $language='') {
	global $q_config;
	if($language == '') $language = $q_config['default_language'];
	$p = get_post($post_id); $post = &$p;
	$languages = qtranxf_getAvailableLanguages($post->post_content);
	return in_array($language,$languages);
}

function qtranxf_convertDateFormatToStrftimeFormat($format) {
	$mappings = array(
		'd' => '%d',
		'D' => '%a',
		'j' => '%E',
		'l' => '%A',
		'N' => '%u',
		'S' => '%q',
		'w' => '%f',
		'z' => '%F',
		'W' => '%V',
		'F' => '%B',
		'm' => '%m',
		'M' => '%b',
		'n' => '%i',
		't' => '%J',
		'L' => '%k',
		'o' => '%G',
		'Y' => '%Y',
		'y' => '%y',
		'a' => '%P',
		'A' => '%p',
		'B' => '%K',
		'g' => '%l',
		'G' => '%L',
		'h' => '%I',
		'H' => '%H',
		'i' => '%M',
		's' => '%S',
		'u' => '%N',
		'e' => '%Q',
		'I' => '%o',
		'O' => '%O',
		'P' => '%s',
		'T' => '%v',
		'Z' => '%1',
		'c' => '%2',
		'r' => '%3',
		'U' => '%4'
	);
	
	$date_parameters = array();
	$strftime_parameters = array();
	$date_parameters[] = '#%#'; 			$strftime_parameters[] = '%';
	foreach($mappings as $df => $sf) {
		$date_parameters[] = '#(([^%\\\\])'.$df.'|^'.$df.')#';	$strftime_parameters[] = '${2}'.$sf;
	}
	// convert everything
	$format = preg_replace($date_parameters, $strftime_parameters, $format);
	// remove single backslashes from dates
	$format = preg_replace('#\\\\([^\\\\]{1})#','${1}',$format);
	// remove double backslashes from dates
	$format = preg_replace('#\\\\\\\\#','\\\\',$format);
	return $format;
}

function qtranxf_convertFormat($format, $default_format) {
	global $q_config;
	// if one of special language-neutral formats are requested, don't replace it
	switch($format){
		case 'Z':
		case 'c':
		case 'r':
		case 'U':
			return qtranxf_convertDateFormatToStrftimeFormat($format); 
		default: break;
	}
	// check for multilang formats
	$format = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($format);
	$default_format = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($default_format);
	switch($q_config['use_strftime']) {
		case QTX_DATE:
			if($format=='') $format = $default_format;
			return qtranxf_convertDateFormatToStrftimeFormat($format);
		case QTX_DATE_OVERRIDE:
			return qtranxf_convertDateFormatToStrftimeFormat($default_format);
		case QTX_STRFTIME:
			return $format;
		case QTX_STRFTIME_OVERRIDE:
		default:
			return $default_format;
	}
}

function qtranxf_convertDateFormat($format) {
	global $q_config;
	if(isset($q_config['date_format'][$q_config['language']])) {
		$default_format = $q_config['date_format'][$q_config['language']];
	} elseif(isset($q_config['date_format'][$q_config['default_language']])) {
		$default_format = $q_config['date_format'][$q_config['default_language']];
	} else {
		$default_format = '';
	}
	return qtranxf_convertFormat($format, $default_format);
}

function qtranxf_convertTimeFormat($format) {
	global $q_config;
	if(isset($q_config['time_format'][$q_config['language']])) {
		$default_format = $q_config['time_format'][$q_config['language']];
	} elseif(isset($q_config['time_format'][$q_config['default_language']])) {
		$default_format = $q_config['time_format'][$q_config['default_language']];
	} else {
		$default_format = '';
	}
	return qtranxf_convertFormat($format, $default_format);
}

function qtranxf_formatCommentDateTime($format) {
	global $comment;
	return qtranxf_strftime(qtranxf_convertFormat($format, $format), mysql2date('U',$comment->comment_date), '');
}

function qtranxf_formatPostDateTime($format) {
	global $post;
	return qtranxf_strftime(qtranxf_convertFormat($format, $format), mysql2date('U',$post->post_date), '');
}

function qtranxf_formatPostModifiedDateTime($format) {
	global $post;
	return qtranxf_strftime(qtranxf_convertFormat($format, $format), mysql2date('U',$post->post_modified), '');
}

//not in use
//function qtranxf_realURL($url = '') {
//	global $q_config;
//	return $q_config['url_info']['original_url'];
//}

function qtranxf_getSortedLanguages($reverse = false) {
	global $q_config;
	$languages = $q_config['enabled_languages'];
	ksort($languages);
	// fix broken order
	$clean_languages = array();
	foreach($languages as $lang) {
		$clean_languages[] = $lang;
	}
	if($reverse) krsort($clean_languages);
	return $clean_languages;
}

function qtranxf_can_redirect() {
	return !defined('WP_ADMIN') && !defined('DOING_AJAX') && !defined('WP_CLI') && !defined('DOING_CRON') && empty($_POST);
}
