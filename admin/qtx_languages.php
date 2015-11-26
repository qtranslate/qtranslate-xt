<?php
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Load array of preset language properties.
 * @since 3.4.8
 */
function qtranxf_langs_preset(){
	global $q_config, $qtranslate_options;
	$langs = array();
	foreach($qtranslate_options['languages'] as $nm => $opn){
		$defs = call_user_func('qtranxf_default_'.$nm);
		foreach($defs as $lang => $v){
			$langs[$lang][$nm] = $v;
		}
	}
	if($q_config['use_strftime'] == QTX_DATE_WP){
		foreach($langs as $lang => $props){
			if(isset($props['date_format'])) $langs[$lang]['date_format'] = qtranxf_convert_strftime2date($props['date_format']);
			if(isset($props['time_format'])) $langs[$lang]['time_format'] = qtranxf_convert_strftime2date($props['time_format']);
		}
	}
	//qtranxf_dbg_log('qtranxf_langs_preset: $langs: ',$langs);
	return $langs;
}

/**
 * Load array of stored in options language properties, which differs from their preset default.
 * @since 3.3
 */
function qtranxf_langs_stored(&$defs = null){
	global $qtranslate_options;
	if(!$defs) $defs = qtranxf_langs_preset();
	$langs = array();
	foreach($qtranslate_options['languages'] as $nm => $opn){
		$vals = get_option($opn,array());
		$vals_changed = !is_array($vals);
		if(!$vals_changed){
			foreach($vals as $lang => $v){
				if(!empty($defs[$lang][$nm]) && $defs[$lang][$nm] == $v){
					unset($vals[$lang]);
					$vals_changed = true;
				}else{
					$langs[$lang][$nm] = $v;
				}
			}
		}
		if($vals_changed){
			if(empty($vals)) delete_option($opn);
			else update_option($opn,$vals);
		}
	}
	//qtranxf_dbg_log('qtranxf_langs_stored: $langs: ',$langs);
	return $langs;
}

/**
 * @since 3.3
 * @return array merged array of stored and pre-defined language properties
 */
function qtranxf_langs_config($langs_preset=null,$langs_stored=null){
	if(!$langs_preset) $langs_preset = qtranxf_langs_preset();
	if(!$langs_stored) $langs_stored = qtranxf_langs_stored($langs_preset);
	$langs = $langs_preset;
	foreach($langs_stored as $lang => $props){
		foreach($props as $nm => $v){
			$langs[$lang][$nm] = $v;
		}
		$langs[$lang]['stored'] = true;
	}
	//qtranxf_dbg_log('qtranxf_langs_config: $langs: ',$langs);
	return $langs;
}

/**
 * Save language properties to database
 * @since 3.3
 */
function qtranxf_save_languages($langs_stored, $qtranslate_options=null){
	if(!$qtranslate_options) {
		global $qtranslate_options;
		if(!$qtranslate_options) qtranxf_set_default_options($qtranslate_options);
	}
	$cfg = array();
	foreach($langs_stored as $lang => $props){
		foreach($props as $nm => $v){
			$cfg[$nm][$lang] = $v;
		}
	}
	foreach($qtranslate_options['languages'] as $nm => $opn){
		if(empty($cfg[$nm])) delete_option($opn);
		else update_option($opn,$cfg[$nm]);
	}
	global $q_config;
	if(isset($q_config['date_i18n'])) update_option('qtranslate_date_i18n',$q_config['date_i18n']);
	//qtranxf_dbg_log('qtranxf_save_languages: $cfg: ',$cfg);
}

/**
 * Remove language $lang properties from hash $langs.
 * @since 3.3
 */
function qtranxf_unsetLanguage(&$langs, $lang){
	unset($langs['language_name'][$lang]);
	unset($langs['flag'][$lang]);
	unset($langs['locale'][$lang]);
	unset($langs['locale_html'][$lang]);
	unset($langs['date_format'][$lang]);
	unset($langs['time_format'][$lang]);
	unset($langs['not_available'][$lang]);
	if(isset($langs['date_i18n']))
	foreach($langs['date_i18n'] as $f => $v){
		if(is_array($v) && isset($v[$lang]))
			unset($langs['date_i18n'][$f][$lang]);
	}
	//unset($langs['languages'][$lang]);
}

/** 
 * @since 3.4.2
 */
function qtranxf_setLanguageAdmin($lang){
	global $q_config;
	$q_config['language'] = $lang;
	qtranxf_set_language_cookie($lang);
}

function qtranxf_update_config_header_css(){
	global $q_config;
	$header_css = get_option('qtranslate_header_css');
	if($header_css === false){
		$q_config['header_css'] = qtranxf_front_header_css_default();
	}
	if(!$q_config['header_css_on'] || !empty($header_css)){
		qtranxf_add_warning(sprintf(__('A manual update to option "%s" or to the theme custom CSS may be needed, after some languages are changed.', 'qtranslate'), __('Head inline CSS', 'qtranslate')).' '.__('If you do not wish to customize this option, then reset it to the default by emptying its value.', 'qtranslate'));
	}
}

function qtranxf_disableLanguage($lang){
	global $q_config;
	if(!qtranxf_isEnabled($lang))
		return false;
	foreach($q_config['enabled_languages'] as $k => $l){
		if($l != $lang) continue;
		unset($q_config['enabled_languages'][$k]);
		break;
	}
	qtranxf_unsetLanguage($q_config,$lang);
	if($q_config['language'] == $lang){
		qtranxf_setLanguageAdmin($q_config['default_language']);
	}
	qtranxf_update_config_header_css();
	return true;
}

function qtranxf_enableLanguage($lang){
	global $q_config;
	if(qtranxf_isEnabled($lang))// || !isset($q_config['language_name'][$lang]))
		return false;
	$q_config['enabled_languages'][] = $lang;

	qtranxf_load_languages_enabled();
	qtranxf_update_config_header_css();

	// force update of .mo files
	if ($q_config['auto_update_mo']) qtranxf_updateGettextDatabases(true, $lang);

	return true;
}

/**
 * Remove language $lang from the database.
 * @since 3.3
 */
function qtranxf_deleteLanguage($lang){
	global $q_config;
	$langs_preset = qtranxf_langs_preset();
	if(isset($langs_preset[$lang])){
		//action "Reset"
		global $qtranslate_options;
		foreach($qtranslate_options['languages'] as $nm => $opn){
			if(empty($langs_preset[$lang][$nm])) unset($q_config[$nm][$lang]);
			else $q_config[$nm][$lang] = $langs_preset[$lang][$nm];
		}
		qtranxf_set_date_i18n_formats($q_config,$lang);
	}else{
		//action "Delete"
		if( $q_config['default_language'] == $lang ){
			//if(!isset($q_config['language_name'][$lang])||strtolower($lang)=='code') $error = __('No such language!', 'qtranslate');
			return __('Cannot delete Default Language!', 'qtranslate');
		}
		qtranxf_disableLanguage($lang);
	}
	$langs_stored = qtranxf_langs_stored($langs_preset);
	unset($langs_stored[$lang]);
	qtranxf_save_languages($langs_stored);
	return '';
}
