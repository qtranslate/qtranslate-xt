<?php
if(!defined('ABSPATH'))exit;

function qtranxf_tst_log($msg,$var='novar',$bt=false,$exit=false){qtranxf_dbg_log($msg,$var,$bt,$exit);}

function qtranxf_check_test($result, $expected, $test_name){
	//qtranxf_tst_log('qtranxf_check_test: $result='.$result. PHP_EOL .'                 $expected=',$expected);
	if($result == $expected) return true;
	qtranxf_tst_log($test_name.': '.$result.' != ',$expected);
	return false;
}
	//qtranxf_tst_log('qtx-tests: SERVER: ',$_SERVER);
	require_once(dirname(__FILE__).'/qtx-test-convertURL.php');
	//require_once(dirname(__FILE__).'/qtx-test-date-time.php');
	//exit();
