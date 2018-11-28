<?php
if ( !defined( 'WP_ADMIN' ) ) exit;

function qtranxf_regroup_translations( &$qfields, &$request, $edit_lang, $default_lang ) {
	if(isset($qfields['qtranslate-original-value'])){
		global $q_config;
		$original_value = $qfields['qtranslate-original-value'];
		if(qtranxf_isMultilingual($original_value)){
			$langs = qtranxf_split($original_value);
			$original_value = $langs[$default_lang];
			$qfields['qtranslate-original-value'] =  $original_value;
		}
		if(qtranxf_isMultilingual($request)){
			$qfields = qtranxf_split($request);
			$qfields['qtranslate-original-value'] =  $original_value;
		}else{
			$qfields[$edit_lang] = $request;
		}
		// make sure the default language value is provided
		qtranxf_ensure_language_set( $qfields, $default_lang, $original_value);
		$request = $qfields[$edit_lang];
		//$request = qtranxf_join_b($qfields);
	}else{
		foreach($qfields as $nm => &$vals){
			if(!isset($request[$nm])){
				unset($qfields[$nm]);
				continue;
			}
			qtranxf_regroup_translations($vals,$request[$nm],$edit_lang,$default_lang); // recursive call
		}
	}
}

function qtranxf_regroup_translations_for( $type, $edit_lang, $default_lang ) {
	if(!isset($_REQUEST[$type])) return;
	foreach($_REQUEST[$type] as $nm => &$qfields){
		if(!isset($_REQUEST[$nm])){
			unset($_REQUEST[$type][$nm]);
			continue;
		}
		qtranxf_regroup_translations($qfields,$_REQUEST[$nm],$edit_lang,$default_lang);
		if(isset($_POST[$nm])){
			$_POST[$nm] = $_REQUEST[$nm];
			$_POST[$type][$nm] = $_REQUEST[$type][$nm];
		}
		if(isset($_GET[$nm])){
			$_GET[$nm] = $_REQUEST[$nm];
			$_GET[$type][$nm] = $_REQUEST[$type][$nm];
		}
	}
}// */

function qtranxf_slug_collect_translations_posted() {
	if(isset($_REQUEST['qtranslate-slugs'])){
		//ensure REQUEST has the value of the default language
		//multilingual slug/term values will be processed later
		$edit_lang = qtranxf_getLanguageEdit();
		$default_lang = qtranxf_getLanguageDefault();
		qtranxf_regroup_translations_for('qtranslate-slugs', $edit_lang, $default_lang);
	}
}
add_action('plugins_loaded', 'qtranxf_slug_collect_translations_posted', 6);

function qtranxf_slug_update_translations( $name, &$qfields, $default_lang ) {
	global $wpdb;

	//$name = $qfields[$default_lang];

	unset($qfields['qtranslate-original-value']);
	unset($qfields[$default_lang]); //use $post->post_name instead since it may have been adjusted by WP

	if(qtranxf_slug_translate_name($default_lang,$name)){
		qtranxf_slug_del_translation($default_lang,$name);
	}

	foreach($qfields as $lang => $slug){
		$slug = sanitize_title($slug);
		//qtranxf_dbg_log('qtranxf_slug_update_translations: origin $slug: ', $slug);
		$slug = qtranxf_slug_unique($slug, $lang, $name);
		//qtranxf_dbg_log('qtranxf_slug_update_translations: unique $slug: ',$slug);
		$sql = null;
		if(qtranxf_slug_translate_name($lang,$name)){
			if($name != $slug)
				$sql = 'UPDATE '.$wpdb->prefix.'i18n_slugs SET slug = %s WHERE lang = %s AND name = %s';
			else
				$sql = 'DELETE FROM '.$wpdb->prefix.'i18n_slugs WHERE slug = %s AND lang = %s AND name = %s';
		}else{
			if($name != $slug)
				$sql = 'INSERT INTO '.$wpdb->prefix.'i18n_slugs (slug, lang, name) VALUES (%s, %s, %s)';
		}
		if($sql){
			$query = $wpdb->prepare( $sql, $slug, $lang, $name );
			$wpdb->query($query);
		}
	}
}

function qtranxf_slug_clean_request($nm){
	qtranxf_clean_request_of('qtranslate-slugs',$nm);
/*
	unset($_GET['qtranslate-slugs'][$nm]);
	unset($_POST['qtranslate-slugs'][$nm]);
	unset($_REQUEST['qtranslate-slugs'][$nm]);
	if(empty($_GET['qtranslate-slugs'])) unset($_GET['qtranslate-slugs']);
	if(empty($_POST['qtranslate-slugs'])) unset($_POST['qtranslate-slugs']);
	if(empty($_REQUEST['qtranslate-slugs'])) unset($_REQUEST['qtranslate-slugs']);
*/
}

function qtranxf_slug_has_post_name($post_type,$post_status){
	switch($post_type){
		case 'nav_menu_item': return false;
		case 'revision': if ( 'inherit' == $post_status ) return false; break;
		default: break;
	}
	return true;
}

function qtranxf_slug_save_post(&$qfields, $post_ID, $post){
	global $wpdb;
	$post_parent = $post->post_parent;
	$name = $post->post_name;
	$name_old = $qfields['qtranslate-original-value'];
	if($name != $name_old){
		$slugs_old = qtranxf_slug_get_translations($name_old);
		if(!empty($slugs_old)){
			$sql = 'SELECT ID FROM '.$wpdb->posts.' WHERE ID != %s AND post_name = %s';
			$query = $wpdb->prepare( $sql, $post_ID, $name_old );
			$id = $wpdb->get_var($query);
			if(empty($id)) qtranxf_slug_del_translations($name_old);
		}
	}

	$default_lang = qtranxf_getLanguageDefault();
	qtranxf_slug_update_translations($name, $qfields, $default_lang);
}

/* function qtranxf_slug_action_save_post($post_ID, $post, $update){
	//if( in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft' )) return;//already checked
	//if(empty($post_ID)) return;//already checked
	if(empty($post->post_name)) return;
	//if( $post->post_type == 'attachment' ) return;// does not come here for attachments
	if(empty($_POST['qtranslate-slugs']['post_name'])) return;
	$post_status = $post->post_status;
	$post_type = $post->post_type;
	//qtranxf_dbg_log('qtranxf_slug_save_post: $post_status='.$post_status.'; $post_type', $post_type);
	switch($post_type){
		case 'nav_menu_item': return;
		case 'revision': if ( 'inherit' == $post_status ) return; break;
		default: break;
	}
	//qtranxf_dbg_log('qtranxf_slug_save_post: $_POST[qtranslate-slugs][post_name]: ', $_POST['qtranslate-slugs']['post_name']);
	$post_parent = $post->post_parent;
	global $q_config;
	$default_lang = $q_config['default_lang'];
	//$_POST['qtranslate-slugs']['post_name'][$default_lang] = $post->post_name;
	unset($_POST['qtranslate-slugs']['post_name'][$default_lang]);
	//$default_slug = $post->post_name;//it is already unique within default language and we will not change that.
	update_metadata('post', $post_ID, '_qts_slug_'.$default_lang, $post->post_name);

	$post_type_hierarchical = is_post_type_hierarchical( $post_type );
	//qtranxf_dbg_log('qtranxf_slug_save_post: post_type_hierarchical('.$post_type.'): ', $post_type_hierarchical);

	global $wp_rewrite;
	$feeds = $wp_rewrite->feeds;
	if ( ! is_array( $feeds ) )
		$feeds = array();

	foreach($_POST['qtranslate-slugs']['post_name'] as $lang => $slug){
		$slug = sanitize_title($slug);
		$original_slug = $slug;
		//qtranxf_dbg_log('qtranxf_slug_save_post: $original_slug: ', $original_slug);
		$meta_key = '_qts_slug_'.$lang;
		if($post_type_hierarchical){
			$slug = qtranxf_slug_unique_post_slug_hierarchical($meta_key, $slug, $feeds, $post_ID, $post_type, $post_parent);
		}else{
			$slug = qtranxf_slug_unique_post_slug($meta_key, $slug, $feeds, $post_ID, $post_type);
		}
		$slug = apply_filters( 'qtranslate_unique_post_slug', $slug, $lang, $post_ID, $post_status, $post_type, $post_parent, $original_slug );
		//qtranxf_dbg_log('qtranxf_slug_save_post: unique $slug: ',$slug);
		update_metadata('post', $post_ID, $meta_key, $slug);
	}
	unset($_POST['qtranslate-slugs']['post_name']);
}// */

/* *
 * This is a copy of WP function wp_unique_post_slug from /wp-includes/post.php adjusted with $meta_key argument.
* /
function qtranxf_slug_unique_post_slug_hierarchical( $meta_key, $slug, $feeds, $post_ID, $post_type, $post_parent ) {
	global $wpdb, $wp_rewrite;
	//$wpdb->show_errors(); @set_time_limit(0);

	/ *
	 * Page slugs must be unique within their own trees. Pages are in a separate
	 * namespace than posts so page slugs are allowed to overlap post slugs.
	 * /
	//$check_sql = "SELECT post_name FROM $wpdb->posts WHERE post_name = %s AND post_type IN ( %s, 'attachment' ) AND ID != %d AND post_parent = %d LIMIT 1";
	$check_sql = "SELECT p.ID FROM $wpdb->postmeta as m INNER JOIN $wpdb->posts as p ON m.post_id = p.ID WHERE m.meta_key = %s AND m.meta_value = %s AND p.post_type IN ( %s, 'attachment' ) AND ID != %d AND post_parent = %d LIMIT 1";
	$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $meta_key, $slug, $post_type, $post_ID, $post_parent ) );

	/ **
	 * Filter whether the post slug would make a bad hierarchical post slug.
	 *
	 * @since 3.1.0
	 *
	 * @param bool   $bad_slug    Whether the post slug would be bad in a hierarchical post context.
	 * @param string $slug        The post slug.
	 * @param string $post_type   Post type.
	 * @param int    $post_parent Post parent ID.
	 * /
	if ( $post_name_check || in_array( $slug, $feeds ) || preg_match( "@^($wp_rewrite->pagination_base)?\d+$@", $slug )  || apply_filters( 'wp_unique_post_slug_is_bad_hierarchical_slug', false, $slug, $post_type, $post_parent ) ) {
		$suffix = 2;
		do {
			$alt_post_name = _truncate_post_slug( $slug, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
			$post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $meta_key, $alt_post_name, $post_type, $post_ID, $post_parent ) );
			$suffix++;
		} while ( $post_name_check );
		$slug = $alt_post_name;
	}
	return $slug;
}// */

/* *
 * This is a copy of WP function wp_unique_post_slug from /wp-includes/post.php adjusted with $meta_key argument.
* /
function qtranxf_slug_unique_post_slug( $meta_key, $slug, $feeds, $post_ID, $post_type, $post_parent ) {
	global $wpdb;
	//$wpdb->show_errors(); @set_time_limit(0);

	// Post slugs must be unique across all posts.
	//$check_sql = "SELECT post_name FROM $wpdb->posts WHERE post_name = %s AND post_type = %s AND ID != %d LIMIT 1";
	$check_sql = "SELECT p.ID FROM $wpdb->postmeta as m INNER JOIN $wpdb->posts as p ON m.post_id = p.ID WHERE m.meta_key = %s AND m.meta_value = %s AND p.post_type = %s AND p.ID != %d LIMIT 1";
	$sql = $wpdb->prepare( $check_sql, $meta_key, $slug, $post_type, $post_ID );
	$post_name_check = $wpdb->get_var( $sql );

	/ **
	 * Filter whether the post slug would be bad as a flat slug.
	 *
	 * @since 3.1.0
	 *
	 * @param bool   $bad_slug  Whether the post slug would be bad as a flat slug.
	 * @param string $slug      The post slug.
	 * @param string $post_type Post type.
	 * /
	if ( $post_name_check || in_array( $slug, $feeds ) || apply_filters( 'wp_unique_post_slug_is_bad_flat_slug', false, $slug, $post_type ) ) {
		$suffix = 2;
		do {
			$alt_post_name = _truncate_post_slug( $slug, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
			$sql = $wpdb->prepare( $check_sql, $meta_key, $alt_post_name, $post_type, $post_ID );
			$post_name_check = $wpdb->get_var( $sql );
			$suffix++;
		} while ( $post_name_check );
		$slug = $alt_post_name;
	}
	return $slug;
}// */

function qtranxf_slug_get_translations( $name ) {
	global $wpdb;
	$sql = 'SELECT lang, slug FROM '.$wpdb->prefix.'i18n_slugs WHERE name = %s';
	$query = $wpdb->prepare( $sql, $name );
	return $wpdb->get_results( $query );
}

function qtranxf_slug_del_translations( $name ) {
	global $wpdb;
	$wpdb->show_errors();
	$sql = 'DELETE FROM '.$wpdb->prefix.'i18n_slugs WHERE name = %s';
	$query = $wpdb->prepare( $sql, $name );
	$wpdb->query($query);
}

function qtranxf_slug_del_translation( $lang, $name ) {
	global $wpdb;
	$sql = 'DELETE FROM '.$wpdb->prefix.'i18n_slugs WHERE lang = %s AND name = %s';
	$query = $wpdb->prepare( $sql, $lang, $name );
	$wpdb->query($query);
}

function qtranxf_slug_multilingual( $name ) {
	global $q_config;
	$results = qtranxf_slug_get_translations($name);
	if(empty($results)) return $name;
	$slugs = array();
	foreach($results as $row){
		$slugs[$row->lang] = $row->slug;
	}
	foreach($q_config['enabled_languages'] as $lang){
		if(isset($slugs[$lang])) continue;
		$slugs[$lang] = $name;
	}
	return qtranxf_join_b($slugs);
}

function qtranxf_slug_unique( $slug, $lang, $name ) {
	global $wpdb;
	//$wpdb->show_errors(); @set_time_limit(0);
	$slug = sanitize_key($slug);
	$check_sql = 'SELECT name FROM '.$wpdb->prefix.'i18n_slugs WHERE slug = %s AND (lang != %s OR name != %s)';
	$query = $wpdb->prepare( $check_sql, $slug, $lang, $name );
	$post_name_check = $wpdb->get_var( $query );
	//qtranxf_dbg_log('qtranxf_slug_unique: $slug="'.$slug.'"; $lang="'.$lang.'"; $name='.$name.'; $post_name_check: ', $post_name_check);
	if ( !$post_name_check ) return $slug;

	$sfx = '-'.$lang;
	if( substr($slug, -3) != $sfx ){
		$alt_post_name = _truncate_post_slug( $slug, 200 - 3 ) . $sfx;
	//qtranxf_dbg_log('qtranxf_slug_unique: $slug="'.$alt_post_name.'"; $lang="'.$lang.'"; $name='.$name.'; $post_name_check: ', $post_name_check);
		$query = $wpdb->prepare( $check_sql, $alt_post_name, $lang, $name );
		$post_name_check = $wpdb->get_var( $query );
		if ( !$post_name_check ) return $alt_post_name;
	}

	$suffix = 2;
	do {
		$alt_post_name = _truncate_post_slug( $slug, 200 - ( strlen( $suffix ) + 1 ) ) . '-'.$suffix;
		$query = $wpdb->prepare( $check_sql, $alt_post_name, $lang, $name );
		$post_name_check = $wpdb->get_var( $query );
	//qtranxf_dbg_log('qtranxf_slug_unique: $slug="'.$alt_post_name.'"; $lang="'.$lang.'"; $name='.$name.'; $post_name_check: ', $post_name_check);
		$suffix++;
	} while ( $post_name_check );
	return $alt_post_name;
}

function qtranxf_slug_update_settings_pre(){
	global $q_config;
	if(empty($_POST['slugs'])){
		if($q_config['slugs']) qtranxf_slug_deactivation_hook();
		return;
	}
	if($q_config['slugs']){
		require_once(QTXSLUGS_DIR.'/admin/qtx_admin_slug_settings.php');
		qtranxf_slug_update_settings();
	}else{
		qtranxf_slug_activation_hook();
	}
}
add_action('qtranslate_update_settings_pre', 'qtranxf_slug_update_settings_pre');

/*
 * Loads multilingual slug to be used in the post-editing form.
 * $value = apply_filters( "edit_{$field}", $value, $post_id ); // in /wp-includes/post.php
 */
add_filter( 'edit_post_name', 'qtranxf_slug_load_post_name', 20, 2 );
function qtranxf_slug_load_post_name( $post_name, $post_id ){
	return qtranxf_slug_multilingual($post_name);
/*
	global $q_config;
	$meta_values = get_post_meta( $post_id );
	//qtranxf_dbg_log('qtranxf_slug_load_post_name: $post_name: ', $post_name);
	//qtranxf_dbg_log('qtranxf_slug_load_post_name: $meta_values: ', $meta_values);
	$slugs = array();
	foreach($q_config['enabled_languages'] as $lang){
		$meta_key = '_qts_slug_'.$lang;
		if(isset($meta_values[$meta_key])){
			$slugs[$lang] = $meta_values[$meta_key][0];
		}else{
			$slugs[$lang] = $post_name;
		}
	}
	return qtranxf_join_b($slugs);
*/
}

add_action('save_post', 'qtranxf_slug_action_save_post', 5, 3);
function qtranxf_slug_action_save_post($post_ID, $post, $update){
	//if( in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft' )) return;//already checked
	//if(empty($post_ID)) return;//already checked
	if(empty($post->post_name)) return;
	//if( $post->post_type == 'attachment' ) return;// does not come here for attachments
	if(empty($_POST['qtranslate-slugs']['post_name'])) return;
	$post_status = $post->post_status;
	$post_type = $post->post_type;
	//qtranxf_dbg_log('qtranxf_slug_action_save_post: $post_status='.$post_status.'; $post_type', $post_type);
	if(qtranxf_slug_has_post_name($post_type,$post_status))
		qtranxf_slug_save_post($_POST['qtranslate-slugs']['post_name'],$post_ID,$post);
	qtranxf_slug_clean_request('post_name');
}

function qtranxf_slug_update_translations_for( $name, &$qfields, $default_lang ) {
	if(isset($qfields['qtranslate-original-value'])){
		qtranxf_slug_update_translations( $name, $qfields, $default_lang );
	}else{
		foreach($qfields as $nm => &$vals){
			qtranxf_slug_update_translations_for( $nm, $vals, $default_lang ); // recursive call
		}
	}
}

function qtranxf_slug_update_translations_left(){
	if(!isset($_REQUEST['qtranslate-slugs'])) return;
	$default_lang = qtranxf_getLanguageDefault();
	foreach($_REQUEST['qtranslate-slugs'] as $name => &$qfields){
		qtranxf_slug_update_translations_for( $name, $qfields, $default_lang );
		qtranxf_slug_clean_request($name);
	}
}
add_action('admin_head', 'qtranxf_slug_update_translations_left', 5);

add_filter('i18n_admin_config','qtranxf_slug_admin_config');
function qtranxf_slug_admin_config($page_configs){

	{// qtranslate-x configuration page
	if(!isset($page_configs['slugs-config'])) $page_configs['slugs-config'] = array();
	$page_config = &$page_configs['slugs-config'];

	if(!isset($page_configs['pages'])){
		$page_config['pages'] = array( 'options-general.php' => 'page=qtranslate-x' );
	}

	if(!isset($page_configs['anchors'])){
		$page_config['anchors'] = array( 'qtranxf_slug_lsb_top' => array('where'=>'first'), 'qtranxf_slug_lsb_bottom' => array('where'=>'first') );
	}

	//qtranxf_config_add_form($page_config,'qtranxf_slug_config');
	//$fields = &$page_config['forms']['qtranxf_slug_config']['fields']; // shorthand
	//$fields['post_types'] = array( 'jquery' => '.qtranxs_slug_post_type' );
	}
/* // permalink configuration page
	if(!isset($page_configs['options-permalink'])) $page_configs['options-permalink'] = array();
	$page_config = &$page_configs['options-permalink'];

	if(!isset($page_configs['pages'])){
		$page_config['pages'] = array( 'options-permalink.php' => '' );
	}
	//$page_config['anchors'] = array( 'qtranxf_slug_config' => array('where'=>'before') );

	$page_config['forms']['main'] = array('fields' => array());
	$fields = &$page_config['forms']['main']['fields']; // shorthand

	$fields['category_base'] = array();
	$fields['tag_base'] = array();
*/

	{// page post*.php
	if(!isset($page_configs['post'])) $page_configs['post'] = array();
	$page_config = &$page_configs['post'];

	if(!isset($page_configs['pages'])){
		$page_config['pages'] = array( 'post.php' => '', 'post-new.php' => '' );
	}

	//if(!isset($page_config['post_type'])) $page_config['post_type'] = array();
	//$page_config['post_type']['exclude'] = 'attachment';

	qtranxf_config_add_form($page_config,'post');
	$fields = &$page_config['forms']['post']['fields']; // shorthand
	$fields['post_name'] = array('encode' => 'slug', 'post-type-excluded' => 'attachment');
	//$fields['new-post-slug'] = array();//no need

	$page_config['js-exec']['post-exec-slug'] = array( 'src' => './slugs/admin/js/post-exec.min.js' );
	}

	return $page_configs;
}

add_action('qtranslate_admin_notices_plugin_conflicts','qtranxf_slug_admin_notices_plugin_conflicts');
function qtranxf_slug_admin_notices_plugin_conflicts(){
	qtranxf_admin_notice_plugin_conflict('Qtranslate Slug','qtranslate-slug/qtranslate-slug.php');
}

add_action('qtranslate_add_row_migrate','qtranxf_slug_add_row_migrate');
function qtranxf_slug_add_row_migrate(){
	qtranxf_add_row_migrate('Qtranslate Slug', 'qtranslate-slug', array('note' => __('These options get auto-migrated on activation of plugin if applicable. Migration utilities are provided here for the sake of completeness.','qtranslate')));
}

add_action('qtranslate_admin_options_update.php', 'qtranxf_slug_options_update_php');
function qtranxf_slug_options_update_php(){
	require_once(QTXSLUGS_DIR.'/admin/qtx_admin_slug_settings.php');
}
