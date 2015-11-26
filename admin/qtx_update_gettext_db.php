<?php
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * return 'true', if no update needed,
 * or 'false', if update is impossible to do,
 * or 0, if all languages were updated successfully,
 * or positive integer number of errors occurred on languages update.
 */
function qtranxf_updateGettextDatabasesEx($force = false, $only_for_language = '') {
	if(empty($only_for_language)){
		if(!$force){
			$next_update = get_option('qtranslate_next_update_mo');
			if(time() < $next_update) return true;
		}
		update_option('qtranslate_next_update_mo', time() + 7*24*60*60);
	}

	if(!is_dir(WP_LANG_DIR)) {
		if(!@mkdir(WP_LANG_DIR))
			return false;
	}

	require_once ABSPATH . 'wp-admin/includes/translation-install.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	//include( ABSPATH . WPINC . '/version.php' ); // include an unmodified $wp_version
	//$result = translations_api( 'core', array( 'version' => $wp_version ));
	//if ( is_wp_error( $result ) ){
	//	qtranxf_add_warning(__('Gettext databases <strong>not</strong> updated:', 'qtranslate') . ' ' . $result->get_error_message());
	//	return false;
	//}
	//$translations = $result['translations'];
	$translations = wp_get_available_translations();
	if ( empty( $translations ) ){
		qtranxf_add_warning(__( 'Gettext databases <strong>not</strong> updated:', 'qtranslate') . ' ' . __('Failed to fetch the list of available translations.', 'qtranslate'));
		return false;
	}

	set_time_limit(300);

	if(empty($only_for_language)){
		global $q_config;
		$langs =  $q_config['enabled_languages'];
		$locales = $q_config['locale'];
	}else{
		$langs = array($only_for_language);
		$locales = qtranxf_language_configured('locale');
	}
	$errcnt = 0;
/*
	//qtranxf_dbg_log('qtranxf_updateGettextDatabasesEx: count($translations): ',count($translations));
	//qtranxf_dbg_log('qtranxf_updateGettextDatabasesEx: $translations: ',$translations);
	foreach ( $translations as $loc => $translation ) {
		$locale = $translation['language'];
		$lang = null;
		foreach($langs as $lng) {
			if(!isset($locales[$lng])){
				$locales = qtranxf_language_configured('locale');
				if(!isset($locales[$lng])) continue;
			}
			if($locales[$lng] != $locale) continue;
			$lang = $lng;
			break;
		}
		if(!$lang) continue;
*/
	foreach($langs as $lang) {
		$loc = $locales[$lang];
		if(!isset($translations[$loc])) continue;
		$mo = WP_LANG_DIR.'/'.$loc.'.mo';
		$mo_ok = file_exists($mo);
		qtranxf_dbg_log('qtranxf_updateGettextDatabasesEx: $mo_ok for '.$mo.': ',$mo_ok);
		if($mo_ok) continue;//WP now takes care of translations updates on its own
		$translation = (object) $translations[$loc];
		$skin              = new Automatic_Upgrader_Skin;
		$upgrader          = new Language_Pack_Upgrader( $skin );
		$translation->type = 'core';
		$result            = $upgrader->upgrade( $translation, array( 'clear_update_cache' => false ));

		if ( is_wp_error( $result ) ){
			qtranxf_add_warning(sprintf(__( 'Failed to update gettext database for "%s": %s', 'qtranslate' ), $lang, $result->get_error_message()));
			++$errcnt;
		}
	}
	return $errcnt;
}
