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
if(defined('WP_DEBUG')&&WP_DEBUG){
	if(!function_exists('qtranxf_dbg_log')){
		function qtranxf_dbg_log($msg,$var='novar',$bt=false){
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
		}
	}
	if(!function_exists('qtranxf_dbg_echo')){
		function qtranxf_dbg_echo($msg,$var='novar',$bt=false){
			if( $var !== 'novar' )
				$msg .= var_export($var,true);
			echo $msg."<br>\n";
			if($bt){
				debug_print_backtrace();
			}
		}
	}
	if(!function_exists('qtranxf_dbg_log_if')){
		function qtranxf_dbg_log_if($condition,$msg,$var='novar',$bt=false){
			if($condition) qtranxf_dbg_log($msg,$var,$bt);
		}
	}
	assert_options(ASSERT_BAIL,true);
}else{
	if(!function_exists('qtranxf_dbg_log')){ function qtranxf_dbg_log($msg,$var=null,$bt=false){} }
	if(!function_exists('qtranxf_dbg_echo')){ function qtranxf_dbg_echo($msg,$var=null,$bt=false){} }
	if(!function_exists('qtranxf_dbg_log_if')){ function qtranxf_dbg_log_if($condition,$msg,$var=null,$bt=false){} }
	//assert_options(ASSERT_ACTIVE,false);
	//assert_options(ASSERT_WARNING,false);
	//assert_options(ASSERT_QUIET_EVAL,true);
}// */

function qtranxf_parseURL($url) {
	//this is not the same as native parse_url and so it is in use
	//it should also work quicker than native parse_url, so we should keep it?
	$r  = '!(?:(\w+)://)?(?:(\w+)\:(\w+)@)?([^/:]+)?';
	$r .= '(?:\:(\d*))?([^#?]+)?(?:\?([^#]+))?(?:#(.+$))?!i';
	preg_match ( $r, $url, $out );
	//qtranxf_dbg_log('qtranxf_parseURL('.$url.'): out:',$out);
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
/*
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

function qtranxf_startsWith($s, $n) {
	if(strlen($n)>strlen($s)) return false;
	if($n == substr($s,0,strlen($n))) return true;
	return false;
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
	// if timestamp is requested, don't replace it 
	if('U'==$format) return qtranxf_convertDateFormatToStrftimeFormat($format); 
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
