<?php
if ( !defined( 'WP_ADMIN' ) ) exit;

require_once(QTXSLUGS_DIR.'/admin/qtx_admin_slug.php');
require_once(QTXSLUGS_DIR.'/admin/qtx_admin_slug_settings.php');

/**
 * Activation of slugs service if applicable.
 */
function qtranxf_slug_activate_plugin(){
	global $q_config;
	$slugs = get_option('qtranslate_slugs');
	//qtranxf_dbg_log('qtranxf_slug_activate_plugin: $slugs=',$slugs);
	if(!$slugs) return;
	qtranxf_slug_activate();
}

/**
 * Unconditional activation of slugs service.
 */
function qtranxf_slug_activate(){
	global $q_config;
	//qtranxf_dbg_log('qtranxf_slug_activate:');
	qtranxf_slug_add_tables();
	$qts_active = is_plugin_active( 'qtranslate-slug/qtranslate-slug.php' );
	if($qts_active || file_exists(WP_PLUGIN_DIR.'/qtranslate-slug/qtranslate-slug.php')){
		qtranxf_migrate_import_qtranslate_slug();
	}
	if ( $qts_active ){
		qtranxf_admin_notice_deactivate_plugin('Qtranslate Slug', 'qtranslate-slug/qtranslate-slug.php');
	}
	// regenerate rewrite rules in db
	//add_action( 'generate_rewrite_rules', 'qtranxf_slug_modify_rewrite_rules');
	add_filter( 'rewrite_rules_array', 'qtranxf_slug_rewrite_rules_array', 999);
	flush_rewrite_rules();
}

/**
 * Deactivation of slugs service if applicable.
 */
function qtranxf_slug_deactivate_plugin(){
	global $q_config;
	$slugs = $q_config['slugs'];
	//qtranxf_dbg_log('qtranxf_slug_deactivate_plugin: $slugs=',$slugs);
	if(!$slugs) return;
	qtranxf_slug_deactivate();
}

/**
 * Unconditional deactivation of slugs service.
 */
function qtranxf_slug_deactivate() {
	//qtranxf_dbg_log('qtranxf_slug_deactivate: ');
	// regenerate rewrite rules in db
	//remove_action( 'generate_rewrite_rules', 'qtranxf_slug_modify_rewrite_rules');
	remove_filter( 'rewrite_rules_array', 'qtranxf_slug_rewrite_rules_array', 999);
	flush_rewrite_rules();
}

/**
 * return true if tables were added and they did not exist before
 */
function qtranxf_slug_add_tables() {
	global $wpdb;
	//qtranxf_dbg_log('qtranxf_slug_add_tables: $wpdb: ', $wpdb);
	$tbl = $wpdb->prefix.'i18n_slugs';
	if(!empty($wpdb->get_var( 'SHOW TABLES LIKE \''.$tbl.'\';')))
		return false;

	$collate = '';
	$mb4 = false;
	if($wpdb->has_cap( 'collation' )) {
		if(!empty($wpdb->charset)){
			$collate = ' DEFAULT CHARACTER SET '.$wpdb->charset;
			if(strpos($wpdb->charset,'mb4')) $mb4 = true;
		}
		if(!empty($wpdb->collate)){
			$collate .= ' COLLATE '.$wpdb->collate;
			if(strpos($wpdb->collate,'mb4')) $mb4 = true;
		}
	}
	$show_errors = $wpdb->hide_errors();
	//$err_level = error_reporting(0);//still in error_log
	//$err_handler = set_error_handler(null);//still in error_log
	//qtranxf_dbg_log('qtranxf_slug_add_tables: $err_handler: ', $err_handler);
	$result = false;
	if(!$mb4){
		$sql = 'CREATE TABLE IF NOT EXISTS `'.$wpdb->prefix.'i18n_slugs` (
		`slug` varchar(200) NOT NULL PRIMARY KEY,
		`lang` varchar(2) NOT NULL,
		`name` varchar(200) NOT NULL,
		CONSTRAINT `name_lang` UNIQUE (`name`,`lang`))' . $collate . ';';
		$result = @$wpdb->query($sql);
		//qtranxf_dbg_log('qtranxf_slug_add_tables: $result(200): ', $result);
	}
	if(!$result){
		$sql = 'CREATE TABLE IF NOT EXISTS `'.$wpdb->prefix.'i18n_slugs` (
		`slug` varchar(200) NOT NULL,
		`lang` varchar(2) NOT NULL,
		`name` varchar(200) NOT NULL,
		PRIMARY KEY (`slug`(150)),
		CONSTRAINT `name_lang` UNIQUE (`name`(150),`lang`))' . $collate . ';';
		$result = $wpdb->query($sql);
		//qtranxf_dbg_log('qtranxf_slug_add_tables: $result(150): ', $result);
	}
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
	if($show_errors) $wpdb->show_errors();
	return true;
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
}//

/**
 * Helper that gets a base slug stored in options
 * 
 * @param string $name of extra permastruct
 * @return string base slug for 'post_type' and 'language' or false
 *
 * @since 1.0
 * /
function qtranxf_slug_get_base($lang, $name) {
	global $q_config;

	if ( taxonomy_exists($name) ) {
		$type = 'taxonomy';
	} else if ( post_type_exists($name) ) {
		$type = 'post_type';
	} else {
		return false;
	}

	return isset($q_config['slugs_'.$type][$name][$lang]) ? $q_config['slugs_'.$type][$name][$lang] : false;
}
*/
