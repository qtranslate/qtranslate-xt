<?php
if ( !defined( 'WP_ADMIN' ) ) exit;

require_once(QTXSLUGS_DIR.'/admin/qtx_admin_slug.php');
require_once(QTXSLUGS_DIR.'/admin/qtx_admin_slug_settings.php');

function qtranxf_slug_activate(){
	//qtranxf_dbg_log('qtranxf_slug_activate: REQUEST_TIME_FLOAT: ', $_SERVER['REQUEST_TIME_FLOAT']);

	qtranxf_slug_add_tables();

	if ( get_option('qtranslate_slugs') === false
		&& file_exists(WP_PLUGIN_DIR.'/qtranslate-slug/qtranslate-slug.php')){
		qtranxf_migrate_import_qtranslate_slug();
	}

	if ( is_plugin_active( 'qtranslate-slug/qtranslate-slug.php' ) ){
		qtranxf_admin_notice_deactivate_plugin('Qtranslate Slug', 'qtranslate-slug/qtranslate-slug.php');
	}

	// regenerate rewrite rules in db
	//add_action( 'generate_rewrite_rules', 'qtranxf_slug_modify_rewrite_rules');
	//flush_rewrite_rules();
}

function qtranxf_slug_deactivate() {
	//qtranxf_dbg_log('qtranxf_slug_deactivate: REQUEST_TIME_FLOAT: ', $_SERVER['REQUEST_TIME_FLOAT']);
	// regenerate rewrite rules in db
	//remove_action( 'generate_rewrite_rules', 'qtranxf_slug_modify_rewrite_rules');
	//flush_rewrite_rules();
}

/*
function qtranxf_slug_modify_rewrite_rules() {
	//qtranxf_dbg_log('qtranxf_slug_modify_rewrite_rules: REQUEST_TIME_FLOAT: ', $_SERVER['REQUEST_TIME_FLOAT']);
	return;//todo

	// post types rules
	$post_types = get_post_types( array('_builtin' => false ), 'objects');
	foreach ( $post_types as $post_type ) {
		qtranxf_slug_generate_extra_rules( $post_type->name );
	}

	// taxonomies rules
	//$taxonomies = $this->get_public_taxonomies();
	$builtin = get_taxonomies( array( 'public' => true, 'show_ui' => true, '_builtin' => true ), 'object'); 
	$taxonomies = get_taxonomies( array( 'public' => true, 'show_ui' => true, '_builtin' => false ), 'object' ); 
	$taxonomies = array_merge( $builtin, $taxonomies );
	foreach ( $taxonomies as $taxonomy ) {
		qtranxf_slug_generate_extra_rules( $taxonomy->name );
	}
}

function qtranxf_slug_generate_extra_rules( $name = false ) {
	global $q_config, $wp_rewrite;

	foreach($q_config['enabled_languages'] as $lang){
		//if ( $base = $this->get_base_slug( $name, $lang) ){
		$base = qtranxf_slug_get_base( $lang, $name );
		if(!$base) continue;

		$struct = $wp_rewrite->extra_permastructs[$name];

		if ( is_array( $struct ) ) {
			if ( count( $struct ) == 2 )
				$rules = $wp_rewrite->generate_rewrite_rules( "/$base/%$name%", $struct[1] );
			else
				$rules = $wp_rewrite->generate_rewrite_rules( "/$base/%$name%", $struct['ep_mask'], $struct['paged'], $struct['feed'], $struct['forcomments'], $struct['walk_dirs'], $struct['endpoints'] );
		} else {
			$rules = $wp_rewrite->generate_rewrite_rules( "/$base/%$name%" );
		}

		$wp_rewrite->rules = array_merge($rules, $wp_rewrite->rules);
	}
}// */

function qtranxf_slug_add_tables() {
	global $wpdb;

	$collate = '';
	if($wpdb->has_cap( 'collation' )) {
		if(!empty($wpdb->charset)) $collate = ' DEFAULT CHARACTER SET '.$wpdb->charset;
		if(!empty($wpdb->collate)) $collate .= ' COLLATE '.$wpdb->collate;
	}

	$sql = 'CREATE TABLE IF NOT EXISTS `'.$wpdb->prefix.'i18n_slugs` (
	`slug` varchar(200) NOT NULL PRIMARY KEY,
	`lang` varchar(2) NOT NULL,
	`name` varchar(200) NOT NULL,
	CONSTRAINT `name_lang` UNIQUE (`name`,`lang`))' . $collate . ';';

	$wpdb->query($sql);
/*
	$sql = 'CREATE TABLE IF NOT EXISTS `'.$wpdb->prefix.'terms_i18n` (
	`term_id` bigint(20) NOT NULL,
	`lang` varchar(2) NOT NULL,
	`slug` varchar(200) NULL,
	CONSTRAINT `term_id_lang` PRIMARY KEY (`term_id`,`lang`))' . $collate . ';';

	$wpdb->query($sql);

	$sql = 'CREATE TABLE IF NOT EXISTS `'. $wpdb->prefix . 'posts_i18n` (
	`ID` bigint(20) NOT NULL,
	`lang` varchar(2) NOT NULL,
	`slug` varchar(200) NULL,
	CONSTRAINT `ID_lang` PRIMARY KEY (`ID`,`lang`))'.$collate.';';

	$wpdb->query($sql);
*/
}
