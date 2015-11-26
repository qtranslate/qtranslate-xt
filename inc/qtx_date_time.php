<?php
if ( !defined( 'ABSPATH' ) ) exit;

{// BEGIN DATE TIME FUNCTIONS

/**
 * @param $date - unix time stamp
 */
function qtranxf_strftime($format, $date, $default = '', $before = '', $after = '') {
	// don't do anything if format is not given
	if($format=='') return $default;
	$format_req = $format;
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
	$win = strtoupper(substr(PHP_OS,0,3)) === 'WIN'; //qtranxf_windows_os();
	if($win){
		$format = preg_replace('#(?<!%)((?:%%)*)%e#', '\1%#d', $format);
	}
	$s = strftime($format, $date);
/*
	if($s && $win){
		//qtranxf_dbg_log('qtranxf_strftime: iconv_get_encoding: ', iconv_get_encoding());
		$lc = setlocale(LC_TIME,'0');
		//qtranxf_dbg_log('qtranxf_strftime: $lc: ', $lc);
		$p = strrpos($lc, '.');
		$n = substr($lc,$p+1);
		//$s = iconv('Windows-'.$n, 'UTF-8', $s);
		$s = iconv('', 'UTF-8', $s);
	}
*/
	if(!$s){
		error_log('qTranslate-X: failed to format the date with a call to function qtranxf_strftime("'.$format_req.'", '.$date.'). The default value "'.$default.'" is returned.');
		$s = $default;
	}
	//qtranxf_dbg_log('qtranxf_strftime: $format_req="'.$format_req.'"; $format="'.$format.'"; $date='.$date.'; $s: ', $s);
	return $before.$s.$after;
}

/**
 * @since 3.4.7 time functions re-organized
 * @since 3.2.8 time functions adjusted
 */
function qtranxf_format_date($format, $mysq_time, $default, $before = '', $after = '') {
	global $q_config;
	if(empty($format)) return $default;
	$ts = mysql2date('U', $mysq_time);
	//qtranxf_dbg_log('qtranxf_format_date: $format="'.$format.'"; $mysq_time: ', $mysq_time);
	//qtranxf_dbg_log('qtranxf_format_date: $ts: ', $ts);
	if($format == 'U') return $ts;
	$format = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($format);
	switch($q_config['use_strftime']){
		case QTX_STRFTIME_OVERRIDE:
		case QTX_STRFTIME: $format = qtranxf_convertDateFormatToStrftimeFormat($format); break;
	}
	$format = qtranxf_convertDateFormat($format);
	//qtranxf_dbg_log('qtranxf_format_date: $format: ', $format);
	return qtranxf_strftime($format, $ts, $default, $before, $after);
}

function qtranxf_format_time($format, $mysq_time, $default, $before = '', $after = '') {
	global $q_config;
	if(empty($format)) return $default;
	$ts = mysql2date('U', $mysq_time);
	if($format == 'U') return $ts;
	$format = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($format);
	switch($q_config['use_strftime']){
		case QTX_STRFTIME_OVERRIDE:
		case QTX_STRFTIME: $format = qtranxf_convertDateFormatToStrftimeFormat($format); break;
	}
	$format = qtranxf_convertTimeFormat($format);
	return qtranxf_strftime($format, $ts, $default, $before, $after);
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
}

function qtranxf_timeModifiedFromPostForCurrentLanguage($old_date, $format = '', $gmt = false) {
	global $post; if(!$post) return $old_date;
	$post_date = $gmt? $post->post_modified_gmt : $post->post_modified;
	return qtranxf_format_time($format, $post_date, $old_date);
}

function qtranxf_dateFromCommentForCurrentLanguage($old_date, $format, $comment = null) {
	if(!$comment){ global $comment; }//compatibility with older WP
	if(!$comment) return $old_date;
	return qtranxf_format_date($format, $comment->comment_date, $old_date);
}

function qtranxf_timeFromCommentForCurrentLanguage($old_date, $format = '', $gmt = false, $translate = true, $comment = null) {
	if(!$translate) return $old_date;
	if(!$comment){ global $comment; }//compatibility with older WP
	if(!$comment) return $old_date;
	$comment_date = $gmt? $comment->comment_date_gmt : $comment->comment_date;
	return qtranxf_format_time($format, $comment_date, $old_date);
}
}

{//utils
function qtranxf_convertDateFormatToStrftimeFormat($format) {
	$mappings = qtranxf_date_strftime_mapping();
	$date_parameters = array();
	$strftime_parameters = array();
	$date_parameters[] = '#%#'; 			$strftime_parameters[] = '%';
	foreach($mappings as $df => $sf) {
		$date_parameters[] = '#(([^%\\\\])'.$df.'|^'.$df.')#';	$strftime_parameters[] = '${2}'.$sf;
	}
	// convert everything
	$format = preg_replace($date_parameters, $strftime_parameters, $format);
	// remove single backslashes from dates
	//$format = preg_replace('#\\\\([^\\\\]{1})#','${1}',$format);
	$format = preg_replace('#\\\\([^\\\\]{1})#','${1}',$format);
	// remove double backslashes from dates
	$format = preg_replace('#\\\\\\\\#','\\\\',$format);
	//$format = preg_replace('#\\\\#','\\',$format);
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
}

{//filters
add_filter('get_comment_date', 'qtranxf_dateFromCommentForCurrentLanguage',0,3);
add_filter('get_comment_time', 'qtranxf_timeFromCommentForCurrentLanguage',0,5);
add_filter('get_post_modified_time', 'qtranxf_timeModifiedFromPostForCurrentLanguage',0,3);
add_filter('get_the_time', 'qtranxf_timeFromPostForCurrentLanguage',0,3);
add_filter('get_the_date', 'qtranxf_dateFromPostForCurrentLanguage',0,3);
add_filter('get_the_modified_date', 'qtranxf_dateModifiedFromPostForCurrentLanguage',0,2);
}