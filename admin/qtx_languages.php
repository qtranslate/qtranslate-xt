<?php

/**
 * Load array of stored in options language properties
 * @since 3.3
 */
function qtranxf_load_languages(&$cfg)
{
	global $qtranslate_options;
	//$cfg = array();
	foreach($qtranslate_options['languages'] as $nm => $opn){
		$cfg[$nm] = get_option($opn,array());
	}
	return $cfg;
}

/**
 * Save language properties from configuration $cfg to database
 * @since 3.3
 */
function qtranxf_save_languages($cfg)
{
	global $qtranslate_options;
	foreach($qtranslate_options['languages'] as $nm => $opn){
		if(empty($cfg[$nm])) delete_option($opn);
		else update_option($opn,$cfg[$nm]);
	}
	return $cfg;
}

/**
 * Remove language $lang properties from hash $langs.
 * @since 3.3
 */
function qtranxf_unsetLanguage(&$langs, $lang) {
	unset($langs['language_name'][$lang]);
	unset($langs['flag'][$lang]);
	unset($langs['locale'][$lang]);
	unset($langs['date_format'][$lang]);
	unset($langs['time_format'][$lang]);
	unset($langs['not_available'][$lang]);
}

/**
 * Remove language $lang properties from hash $langs.
 * @since 3.3
 */
function qtranxf_copyLanguage(&$langs, $cfg, $lang) {
	$langs['language_name'][$lang] = $cfg['language_name'][$lang];
	$langs['flag'][$lang] = $cfg['flag'][$lang];
	$langs['locale'][$lang] = $cfg['locale'][$lang];
	$langs['date_format'][$lang] = $cfg['date_format'][$lang];
	$langs['time_format'][$lang] = $cfg['time_format'][$lang];
	$langs['not_available'][$lang] = $cfg['not_available'][$lang];
}

function qtranxf_disableLanguage($lang) {
	global $q_config;
	if(qtranxf_isEnabled($lang)) {
		$new_enabled = array();
		for($i = 0; $i < sizeof($q_config['enabled_languages']); $i++) {
			if($q_config['enabled_languages'][$i] != $lang) {
				$new_enabled[] = $q_config['enabled_languages'][$i];
			}else{
				qtranxf_unsetLanguage($q_config,$lang);
			}
		}
		$q_config['enabled_languages'] = $new_enabled;
		return true;
	}
	return false;
}

function qtranxf_enableLanguage($lang) {
	global $q_config;
	if(qtranxf_isEnabled($lang))// || !isset($q_config['language_name'][$lang]))
		return false;
	$q_config['enabled_languages'][] = $lang;
	// force update of .mo files
	if ($q_config['auto_update_mo']) qtranxf_updateGettextDatabases(true, $lang);
	qtranxf_load_languages_enabled();
	return true;
}

/**
 * Remove language $lang from the database.
 * @since 3.3
 */
function qtranxf_deleteLanguage($lang) {
	global $q_config;
	if( !qtranxf_language_predefined($lang) ){
		if( $q_config['default_language'] == $lang ){
			//if($q_config['default_language']==$lang) $error = ;
			//if(!isset($q_config['language_name'][$lang])||strtolower($lang)=='code') $error = __('No such language!', 'qtranslate');
			return __('Cannot delete Default Language!', 'qtranslate');
		}
		qtranxf_disableLanguage($lang);
	}
	$langs=array(); qtranxf_load_languages($langs);
	qtranxf_unsetLanguage($langs,$lang);
	qtranxf_save_languages($langs);
	return '';
}
