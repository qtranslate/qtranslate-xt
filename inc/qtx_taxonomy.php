<?php
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * @since 3.4.6.9
 * @param WP_Term $term 
 */
function qtranxf_term_set_i18n_config($term){
	$term->i18n_config = array();
	if(isset($term->name)){
		global $q_config;
		$default_language = $q_config['default_language'];
		if(isset($q_config['term_name'][$term->name])){
			$ts = $q_config['term_name'][$term->name];
			$ts[$default_language] = $term->name;
			//$ml = qtranxf_join_b($q_config['term_name'][$term->name]);
			//$term->i18n_config['name'] = array( 'ts' => $ts, 'ml' => $ml );
		}else{
			$ts = array( $default_language => $term->name );
		}
		$term->i18n_config['name'] = array('ts' => $ts);
	}
	if(!empty($term->description) && qtranxf_isMultilingual($term->description)){
		$ts = qtranxf_split($term->description);
		$term->i18n_config['description'] = array( 'ts' => $ts, 'ml' => $term->description );
	}
}

/**
 * @since 3.4
 */
function qtranxf_term_use($lang, $obj, $taxonomy) {
	global $q_config;
	if(is_array($obj)) {
		// handle arrays recursively
		foreach($obj as $key => $t) {
			$obj[$key] = qtranxf_term_use($lang, $obj[$key], $taxonomy);
		}
		return $obj;
	}
	if(is_object($obj)) {
		// object conversion
		if(!isset($obj->i18n_config)){
			qtranxf_term_set_i18n_config($obj);
			if(isset($obj->i18n_config['name']['ts'][$lang]))
				$obj->name = $obj->i18n_config['name']['ts'][$lang];
			if(isset($obj->i18n_config['description']['ts'][$lang]))
				$obj->description = $obj->i18n_config['description']['ts'][$lang];
		}
	} elseif(isset($q_config['term_name'][$obj][$lang])) {
		//qtranxf_dbg_echo('qtranxf_translate_term: string: ',$obj,true);
		$obj = $q_config['term_name'][$obj][$lang];
	}
	return $obj;
}

function qtranxf_useTermLib($obj) {
	global $q_config;
	return qtranxf_term_use($q_config['language'], $obj, null);
}

/**
 * @since 3.4.6.9
 * @param WP_Term $term
 * @return string term name in default language.
 */
function qtranxf_term_name_in( $lang, $term ) {
	if(isset($term->i18n_config['name']['ts'][$lang])){
		return $term->i18n_config['name']['ts'][$lang];
	}
	return $term->name;
}
