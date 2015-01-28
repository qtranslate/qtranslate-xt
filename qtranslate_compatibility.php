<?php
if(!function_exists('qtrans_getLanguage')){
	function qtrans_getLanguage(){
		return qtranxf_getLanguage();
	}
}
if(!function_exists('qtrans_convertURL')){
	function qtrans_convertURL($url='', $lang='', $forceadmin = false, $showDefaultLanguage = false){
		return qtranxf_convertURL($url, $lang, $forceadmin, $showDefaultLanguage);
	}
}
if(!function_exists('qtrans_use')){
	function qtrans_use($lang, $text, $show_available=false){
		return qtranxf_use($lang, $text, $show_available);
	}
}
if (!function_exists('qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage')){
	function qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage($content){
		global $q_config;
	$translated=qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($content);
	return $translated;
	}
}
if(!function_exists('qtrans_useTermLib')){
	function qtrans_useTermLib($obj){ return qtranxf_useTermLib($obj); }
}
if(!function_exists('qtrans_getSortedLanguages')){
	function qtrans_getSortedLanguages($reverse = false){ return qtranxf_getSortedLanguages($reverse); }
}
?>
