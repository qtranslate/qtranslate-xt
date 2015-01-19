<?php

function qtranxf_updateGettextDatabaseFile($lcr,$mo){
	$tmpfile=$mo.'.filepart';
	$ll = fopen($tmpfile,'w');
	if(!$ll) return false;
	while(!feof($lcr)) {
		// try to get some more time
		@set_time_limit(30);
		$lc = fread($lcr, 8192);
		if(!$lc){
			fclose($lcr);
			fclose($ll);
			unlink($tmpfile);
			return false;
		}
		fwrite($ll,$lc);
	}
	fclose($lcr);
	fclose($ll);
	// only use completely download .mo files
	rename($tmpfile,$mo);
	return true;
}

function qtranxf_updateGettextDatabaseFiles($lcr,$locale,$dstdir,$srcdir){
	if($lcr){
		$mo=$dstdir.$locale.'.mo';
		qtranxf_updateGettextDatabaseFile($lcr,$mo);
	}
	if(!$srcdir) return;
}

function qtranxf_updateGettextDatabase($locale,$repository){
	//wp_download_language_pack() - todo: use this function
	$dstdir=trailingslashit(WP_LANG_DIR);
	$tmpfile=$dstdir.$locale.'.mo.filepart';
	if(!$ll = @fopen($tmpfile,'a'))
		return false; // cannot access .mo file
	fclose($ll);
	$m='';
	$wp_version = $GLOBALS['wp_version'];
	// try to find a .mo file
	if(!($locale == 'en_US' && $lcr=@fopen('http://www.qianqin.de/wp-content/languages/'.$locale.'.mo','r')))
	if(!$lcr=@fopen(($m=$repository.$locale.'/tags/'.$wp_version.'/messages/').$locale.'.mo','r'))
	if(!$lcr=@fopen(($m=$repository.substr($locale,0,2).'/tags/'.$wp_version.'/messages/').$locale.'.mo','r'))
	if(!$lcr=@fopen(($m=$repository.$locale.'/branches/'.$wp_version.'/messages/').$locale.'.mo','r'))
	if(!$lcr=@fopen(($m=$repository.substr($locale,0,2).'/branches/'.$wp_version.'/messages/').$locale.'.mo','r'))
	if(!$lcr=@fopen($repository.$locale.'/branches/'.$wp_version.'/'.$locale.'.mo','r'))
	if(!$lcr=@fopen($repository.substr($locale,0,2).'/branches/'.$wp_version.'/'.$locale.'.mo','r'))
	if(!$lcr=@fopen($repository.$locale.'/trunk/messages/'.$locale.'.mo','r')) 
	if(!$lcr=@fopen($repository.substr($locale,0,2).'/trunk/messages/'.$locale.'.mo','r'))
	{
		$tagsfile=file($repository.$locale.'/tags/');
		$tags=array();
		foreach( $tagsfile as $ln ){
			if(!preg_match('/href="(\d.*)"/',$ln,$match)) continue;
			$tag=$match[1];
			$tags[]=$tag;
		}
		$tags=array_reverse($tags);
		foreach( $tags as $tag ){
			$m=$repository.$locale.'/tags/'.$tag.'messages/';
			$mo=$m.$locale.'.mo';
			//if(file_exists())
			if(!$lcr=@fopen($mo,'r')) continue;
			break;
		}
		if(!$lcr){// couldn't find a .mo file
			if(filesize($tmpfile)==0) unlink($tmpfile);
			return false;
		}
	}
	// found a .mo file, update local .mo
	qtranxf_updateGettextDatabaseFiles($lcr,$locale,$dstdir,$m);
	return true;
}

function qtranxf_updateGettextDatabases($force = false, $only_for_language = '') {
	global $q_config;
	if($only_for_language && !qtranxf_isEnabled($only_for_language)) return false;
	if(!is_dir(WP_LANG_DIR)) {
		if(!@mkdir(WP_LANG_DIR))
			return false;
	}
	$next_update = get_option('qtranslate_next_update_mo');
	if(time() < $next_update && !$force) return true;
	update_option('qtranslate_next_update_mo', time() + 7*24*60*60);
	//$repository='http://i18n.svn.wordpress.org/';
	$repository='http://svn.automattic.com/wordpress-i18n/';
	foreach($q_config['locale'] as $lang => $locale) {
		if($only_for_language && $lang != $only_for_language) continue;
		if(!qtranxf_isEnabled($lang)) continue;
		qtranxf_updateGettextDatabase($locale,$repository);
	}
	return true;
}

?>
