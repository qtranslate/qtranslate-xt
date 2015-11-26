<?php
if(!defined('ABSPATH'))exit;

	$ds = array(
		'c' => '2015-02-25T23:38:07+00:00',
	);
	qtranxf_test_date($ds);

function qtranxf_test_date($ds){
	$cnt = 0;
	foreach($ds as $fmt => $date_expected){
		$d = get_the_date($fmt,1);
		qtranxf_tst_log('qtranxf_test_date: get_the_date('.$fmt.'): ', $d);
		if(!qtranxf_check_test($d,$date_expected,basename(__FILE__))) ++$cnt;
	}
	return $cnt;
}

function qtranxf_test_dt_format($cfg, $cfg_name='dtf', $format='F j, Y'){
	global $q_config;
	$q_config['locale'] = array_merge(qtranxf_default_locale(),$q_config['locale']);
	$enabled_languages = $q_config['enabled_languages'];
	require_once(QTRANSLATE_DIR.'/admin/qtx_admin_options_update.php');
	$t = strtotime('Nov 16, 2014 3:04pm');
	//qtranxf_dbg_log('qtranxf_test_dt_format: $t: ',$t);
	$m = PHP_EOL;
	foreach($cfg as $c => $f){
		if(!in_array($c,$q_config['enabled_languages'])) $q_config['enabled_languages'][] = $c;
		qtranxf_updateGettextDatabases(true,$c);
		$d = qtranxf_translate_dt_format($format, $c);
		//$d = qtranxf_convert_strftime2date($f);
		$sd = date($d,$t);
		$m .= '$'.$cfg_name.'[\''.$c.'\'] = \''.$d.'\';// "'.$sd.'"';
		if(false)
		{
			require_once(QTRANSLATE_DIR.'/inc/qtx_date_time.php');
			$ff = qtranxf_convertDateFormatToStrftimeFormat($f);
			$sf = qtranxf_strftime($ff,$t); $m .= ' // strftime("'.$f.'") "'.($sf?$sf:'invalid').'"';
		}
		$m .= PHP_EOL;
	}
	$q_config['enabled_languages'] = $enabled_languages;
	//qtranxf_dbg_log('qtranxf_test_dt_format("'.$cfg_name.'","'.$format.'"): $m:'.$m);
	return $m;
}

/* result of qtranxf_test_dt_format runs
qtranxf_test_dt_format("dtf","F j, Y"): $m:
$dtf['en'] = 'F j, Y';// "November 16, 2014"
$dtf['de'] = 'j. F Y';// "16. November 2014"
$dtf['zh'] = 'Y年n月j日';// "2014年11月16日"
$dtf['ru'] = 'd.m.Y';// "16.11.2014"
$dtf['fi'] = 'j.n.Y';// "16.11.2014"
$dtf['fr'] = 'j F Y';// "16 November 2014"
$dtf['nl'] = 'j F Y';// "16 November 2014"
$dtf['sv'] = 'j F, Y';// "16 November, 2014"
$dtf['it'] = 'j F Y';// "16 November 2014"
$dtf['ro'] = 'j F Y';// "16 November 2014"
$dtf['hu'] = 'Y-m-d';// "2014-11-16"
$dtf['ja'] = 'Y年n月j日';// "2014年11月16日"
$dtf['es'] = 'j F, Y';// "16 November, 2014"
$dtf['vi'] = 'j F, Y';// "16 November, 2014"
$dtf['ar'] = 'j F، Y';// "16 November، 2014"
$dtf['pt'] = 'j F, Y';// "16 November, 2014"
$dtf['pb'] = 'j \d\e F \d\e Y';// "16 de November de 2014"
$dtf['pl'] = 'j F Y';// "16 November 2014"
$dtf['gl'] = 'j F, Y';// "16 November, 2014"
$dtf['tr'] = 'j F Y';// "16 November 2014"
$dtf['et'] = 'j. M Y';// "16. Nov 2014"
$dtf['hr'] = 'j. F Y.';// "16. November 2014."
$dtf['eu'] = 'Y-m-d';// "2014-11-16"
$dtf['el'] = 'j F Y';// "16 November 2014"
$dtf['ua'] = 'F j, Y';// "November 16, 2014"
$dtf['cy'] = 'F j, Y';// "November 16, 2014"
$dtf['ca'] = 'j \d\e F \d\e Y';// "16 de November de 2014"
$dtf['sk'] = 'j. F Y';// "16. November 2014"
$dtf['sr'] = 'j. F Y.';// "16. November 2014."

qtranxf_test_dt_format("tmf","g:i a"): $m:
$tmf['en'] = 'g:i a';// "3:04 pm"
$tmf['de'] = 'G:i';// "15:04"
$tmf['zh'] = 'ag:i';// "pm3:04"
$tmf['ru'] = 'H:i';// "15:04"
$tmf['fi'] = 'H:i';// "15:04"
$tmf['fr'] = 'G \h i \m\i\n';// "15 h 04 min"
$tmf['nl'] = 'H:i';// "15:04"
$tmf['sv'] = 'H:i';// "15:04"
$tmf['it'] = 'G:i';// "15:04"
$tmf['ro'] = 'G:i';// "15:04"
$tmf['hu'] = 'H:i';// "15:04"
$tmf['ja'] = 'g:i A';// "3:04 PM"
$tmf['es'] = 'g:i a';// "3:04 pm"
$tmf['vi'] = 'g:i a';// "3:04 pm"
$tmf['ar'] = 'g:i a';// "3:04 pm"
$tmf['pt'] = 'G:i';// "15:04"
$tmf['pb'] = 'H:i';// "15:04"
$tmf['pl'] = 'H:i';// "15:04"
$tmf['gl'] = 'g:i a';// "3:04 pm"
$tmf['tr'] = 'H:i';// "15:04"
$tmf['et'] = 'h:i';// "03:04"
$tmf['hr'] = 'G:i';// "15:04"
$tmf['eu'] = 'G:i';// "15:04"
$tmf['el'] = 'H:i';// "15:04"
$tmf['ua'] = 'g:i a';// "3:04 pm"
$tmf['cy'] = 'g:i a';// "3:04 pm"
$tmf['ca'] = 'G:i';// "15:04"
$tmf['sk'] = 'G:i';// "15:04"
$tmf['sr'] = 'H:i ';// "15:04 "
*/