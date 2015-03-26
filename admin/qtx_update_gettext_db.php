<?php

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

	require_once ABSPATH . 'wp-admin/includes/translation-install.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	include( ABSPATH . WPINC . '/version.php' ); // include an unmodified $wp_version
	$result = translations_api( 'core', array( 'version' => $wp_version ) );

	foreach ( $result['translations'] as $translation ) {
		$locale = substr($translation['language'], 0, 2);
		if (
			isset( $q_config['locale'][$locale] )
			&& $q_config['locale'][$locale] == $translation['language']
			&& qtranxf_isEnabled($locale)
		) {
			$translation = (object) $translation;
			$skin              = new Automatic_Upgrader_Skin;
			$upgrader          = new Language_Pack_Upgrader( $skin );
			$translation->type = 'core';
			$result            = $upgrader->upgrade( $translation, array( 'clear_update_cache' => false ) );
		}
	}
	return true;
}
