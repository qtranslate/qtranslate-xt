<?php
if ( !defined( 'WP_ADMIN' ) ) exit;

{//updates
/**
 * @param (string) $name - urldecoded post name or other single slug (not a path with '/').
*/
function qtranxf_slug_update_translations( $name, &$qfields, $default_lang ) {
	global $q_config, $wpdb;

	if(empty($name)) return;
	//qtranxf_dbg_log('qtranxf_slug_update_translations: $name="'.$name.'", $qfields: ', $qfields);

	if(isset($qfields['qtranslate-original-value'])){
		if($qfields['qtranslate-original-value'] != $name){
			qtranxf_slug_del_translations(qtranxf_slug_encode($qfields['qtranslate-original-value']));
		}
		unset($qfields['qtranslate-original-value']);
	}

	unset($qfields[$default_lang]); //use $post->post_name instead since it may have been adjusted by WP

	$name = qtranxf_slug_encode($name);
	//qtranxf_dbg_log('qtranxf_slug_update_translations: encoded $name: ', $name);
	if(qtranxf_slug_translate_name($name,$default_lang)){
		qtranxf_slug_del_translation($default_lang,$name);
	}

	//$translations = qtranxf_slug_get_translations($name);
	foreach($qfields as $lang => $slug){
		$translation = qtranxf_slug_translate_name($name,$lang);
		$slug = qtranxf_slug_encode($slug);
		//qtranxf_dbg_log('qtranxf_slug_update_translations: origin $slug: ', $slug);
		if($name == $slug){
			if($translation) qtranxf_slug_del_translation($lang,$name);
			continue;
		}
		$slug = qtranxf_slug_unique($slug, $lang, $name);
		//qtranxf_dbg_log('qtranxf_slug_update_translations: unique $slug: ',$slug);
		if($translation){
			$sql = 'UPDATE '.$wpdb->prefix.'i18n_slugs SET slug = %s WHERE lang = %s AND name = %s';
		}else{
			$sql = 'INSERT INTO '.$wpdb->prefix.'i18n_slugs (slug, lang, name) VALUES (%s, %s, %s)';
		}
		$query = $wpdb->prepare( $sql, $slug, $lang, $name );
		$wpdb->query($query);
		if(!isset($q_config['slugs-cache']['names'][$name])) $q_config['slugs-cache']['names'][$name] = array();
		$q_config['slugs-cache']['names'][$name][$lang] = $slug;
	}

	//cleanup in case a language was disabled
	$slugs = qtranxf_slug_get_translations($name);
	foreach($slugs as $lang => $slug){
		if(qtranxf_isEnabled($lang)) continue;
		qtranxf_slug_del_translation($lang,$name);
	}
}

function qtranxf_slug_clean_request($nm){
	//qtranxf_dbg_log('qtranxf_slug_clean_request: $nm='.$nm.'; REQUEST[qtranslate-slugs]: ', $_REQUEST['qtranslate-slugs']);
	unset($_GET['qtranslate-slugs'][$nm]);
	unset($_POST['qtranslate-slugs'][$nm]);
	unset($_REQUEST['qtranslate-slugs'][$nm]);
	if(empty($_GET['qtranslate-slugs'])) unset($_GET['qtranslate-slugs']);
	if(empty($_POST['qtranslate-slugs'])) unset($_POST['qtranslate-slugs']);
	if(empty($_REQUEST['qtranslate-slugs'])) unset($_REQUEST['qtranslate-slugs']);
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
			$sql = 'SELECT ID FROM '.$wpdb->posts.' WHERE ID != %d AND post_name = %s';
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
}

{// filters
add_filter( 'editable_slug', 'qtranxf_slug_multilingual' );

function qtranxf_slug_admin_config($page_configs){
	//qtranxf_dbg_log('qtranxf_slug_admin_config: ');
	{// qtranslate-x configuration page
	if(!isset($page_configs['slugs-config'])) $page_configs['slugs-config'] = array();
	$page_config = &$page_configs['slugs-config'];

	if(!isset($page_configs['pages'])){
		$page_config['pages'] = array( 'options-general.php' => 'page=qtranslate-x' );
	}

	if(!isset($page_configs['anchors'])){
		$page_config['anchors'] = array( 'qtranxs_slug_lsb_top' => array('where'=>'first'), 'qtranxf_slug_lsb_bottom' => array('where'=>'first') );
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

	{// page edit-tags.php?action=edit
	if(!isset($page_configs['edit-tag'])) $page_configs['edit-tag'] = array();
	$page_config = &$page_configs['edit-tag'];
	if(!isset($page_config['pages'])) $page_config['pages'] = array( 'edit-tags.php' => 'action=edit' );
	if(!isset($page_config['forms'])) $page_config['forms'] = array();
	if(!isset($page_config['forms']['edittag'])) $page_config['forms']['edittag'] = array();
	if(!isset($page_config['forms']['edittag']['fields'])) $page_config['forms']['edittag']['fields'] = array();
	$fields = &$page_config['forms']['edittag']['fields']; // shorthand
	$fields['slug'] = array('encode' => 'slug');
	}

	{// page edit-tags.php?taxonomy=
	if(!isset($page_configs['edit-tags'])) $page_configs['edit-tags'] = array();
	$page_config = &$page_configs['edit-tags'];
	if(!isset($page_config['pages'])) $page_config['pages'] = array( 'edit-tags.php' => '^(?!.*action=edit).*$' );
	if(!isset($page_config['forms'])) $page_config['forms'] = array();
	if(!isset($page_config['forms']['the-list'])) $page_config['forms']['the-list'] = array();
	if(!isset($page_config['forms']['the-list']['fields'])) $page_config['forms']['the-list']['fields'] = array();
	$fields = &$page_config['forms']['the-list']['fields']; // shorthand
	$fields['slug'] = array('jquery' => 'td.column-slug', 'encode' => 'display');
	}

	return $page_configs;
}
add_filter('i18n_admin_config','qtranxf_slug_admin_config');

/*
 * Loads multilingual slug to be used in the post-editing form.
 * $value = apply_filters( "edit_{$field}", $value, $post_id ); // in /wp-includes/post.php
 */
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
add_filter( 'edit_post_name', 'qtranxf_slug_load_post_name', 20, 2 );

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
add_action('save_post', 'qtranxf_slug_action_save_post', 5, 3);

function qtranxf_slug_action_delete_post($post_ID){
	//qtranxf_dbg_log('qtranxf_slug_action_delete_post: $post_ID: ', $post_ID);
	$p = get_post($post_ID);
	if(empty($p->post_name)) return;
	$slug = $p->post_name;
	qtranxf_slug_del_translations($slug);
}
add_action( 'delete_post', 'qtranxf_slug_action_delete_post' );

function qtranxf_slug_update_translations_for( $key, &$qfields, $default_lang ) {
	if(isset($qfields['qtranslate-original-value'])){
		$name = $qfields[$default_lang];
		qtranxf_slug_update_translations( $name, $qfields, $default_lang );
	}else{
		foreach($qfields as $key => &$vals){
			qtranxf_slug_update_translations_for( $key, $vals, $default_lang ); // recursive call
		}
	}
}

function qtranxf_slug_edited_term($term_id, $tt_id, $taxonomy){
	global $q_config;
	if(!isset($_POST['qtranslate-slugs']['slug'])) return;
	$qfields = $_POST['qtranslate-slugs']['slug'];
	$default_lang = $q_config['default_language'];
	$term = get_term($term_id, $taxonomy);
	//qtranxf_dbg_log('qtranxf_slug_edited_term: $term: ', $term);
	//qtranxf_dbg_log('qtranxf_slug_edited_term: $qfields: ', $qfields);
	$name = $term->slug;
	qtranxf_slug_update_translations( $name, $qfields, $default_lang );
	qtranxf_slug_clean_request('slug');
}
add_action( 'created_term', 'qtranxf_slug_edited_term', 10, 3);
add_action( 'edited_term', 'qtranxf_slug_edited_term', 10, 3 );

function qtranxf_slug_delete_term($term_id, $tt_id, $taxonomy, $deleted_term){
	//qtranxf_dbg_log('qtranxf_slug_edited_term: $deleted_term: ', $deleted_term);
	$slug = $deleted_term->slug;
	qtranxf_slug_del_translations($slug);
}
add_action( 'delete_term', 'qtranxf_slug_delete_term', 10, 4 );

function qtranxf_slug_update_translations_left(){
	if(!isset($_REQUEST['qtranslate-slugs'])){
		//qtranxf_dbg_log('qtranxf_slug_update_translations_left: no $_REQUEST[qtranslate-slugs]');
		return;
	}
	//qtranxf_dbg_log('qtranxf_slug_update_translations_left: $_REQUEST[qtranslate-slugs]: ', $_REQUEST['qtranslate-slugs']);
	$default_lang = qtranxf_getLanguage();
	foreach($_REQUEST['qtranslate-slugs'] as $key => &$qfields){
		qtranxf_slug_update_translations_for( $key, $qfields, $default_lang );
		qtranxf_slug_clean_request($key);
	}
}
add_action('admin_head', 'qtranxf_slug_update_translations_left', 5);

function qtranxf_slug_options_update_php(){
	require_once(QTXSLUGS_DIR.'/admin/qtx_admin_slug_settings.php');
}
add_action('qtranslate_admin_options_update.php', 'qtranxf_slug_options_update_php');

function qtranxf_slug_rewrite_rules_array($rules_orig){
	//qtranxf_dbg_log('qtranxf_slug_rewrite_rules_array: rules org: ', $rules_orig);
	$srx = '#([\?\(\)\[\]\{\}\,\.\*\:\+]+)#';
	$rules = array();//need to preserve order of items
	foreach($rules_orig as $k => $v){
		$blocks = preg_split($srx, $k, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
		$rx = '';
		foreach($blocks as $b){
			if(!preg_match($srx,$b)){
				$slugs = qtranxf_slug_get_translations(qtranxf_slug_encode($b));
				if(!empty($slugs)){
					$b = '(?i:'.$b;
					foreach($slugs as $s){
						$b .= '|'.urldecode($s);
					}
					$b .= ')';
				}
			}
			$rx .= $b;
		}
		//qtranxf_dbg_log('qtranxf_slug_rewrite_rules_array: $k="'.$k.'"; $blocks: ', $blocks);
		$rules[$rx] = $v;
	}
	//qtranxf_dbg_log('qtranxf_slug_rewrite_rules_array: rules new: ', $rules);
	return $rules;
}
add_filter( 'rewrite_rules_array', 'qtranxf_slug_rewrite_rules_array', 999);

//function qtranxf_slug_rewrite_rules(&$rewrite)
//{
//}
//add_action( 'generate_rewrite_rules', 'qtranxf_slug_rewrite_rules');

function qtranxf_slug_admin_notices_renamed(){
	if(empty($q_config['slugs_opt']['mv']['terms'])) return;
	foreach($q_config['slugs_opt']['mv']['terms'] as $group => $group_info){
		$group_name = $group_info['group_name'];
		$lst = '<br/>'.PHP_EOL;
		foreach($group_info['values'] as $key => $info){
			$name_org = $info['value_org'];
			$name_new = $info['value_new'];
			$lst .= '"'.$name_org.'" -> "'.$name_new.'"<br/>'.PHP_EOL;
		}
		$msg = sprintf(__('The following default language slugs for custom %s have been renamed: %s Those custom terms are created by the theme or by some 3rd-party plugins, which have not yet been fully %sintegrated%s with %s. Thefore you have to change the default language slug of those types using their custom tools. Make sure to change them to the same value as shown above, otherwise you will need to re-enter the translations of those strings again on this configuration page.', 'qtranslate'), $group_name, $lst, '<a href="https://qtranslatexteam.wordpress.com/integration/" target="_blank">', '</a>', 'qTranslate&#8209;X');
		echo $msg;//todo
	}
/*
	if(!empty($q_config['slugs_opt']['mv']['terms'][$group]['values'])){
		$msg = '<br/>'.PHP_EOL;
		foreach($q_config['slugs_opt']['mv']['terms'][$group]['values'] as $key => $info){
			$name_org = $info['value_org'];
			$name_new = $info['value_new'];
			$msg .= '"'.$name_org.'" -> "'.$name_new.'"<br/>'.PHP_EOL;
		}
		qtranxf_add_warning(sprintf(__('The following default language slugs for custom %s have been renamed: %s Those custom types are created by the theme or by some 3rd-party plugins, which have not yet been fully %sintegrated%s with %s. Thefore you have to change the default language slug of those types using their custom tools. Make sure to change them to the same value as shown above, otherwise you will need to re-enter the translations of those strings again on this configuration page.', 'qtranslate'), $group_name, $msg, '<a href="https://qtranslatexteam.wordpress.com/integration/" target="_blank">', '</a>', 'qTranslate&#8209;X'));
	}
*/
}
add_action('admin_notices', 'qtranxf_slug_admin_notices_renamed');
}

{// utils
function qtranxf_slug_is_substitution($s){ return preg_match('/^%[a-z0-9_\-]%$/',$s); }

//function qtranxf_slug_decode($s){
//	$s = urldecode($s);
//	return $s;
//}

/**
 * @param (string) $name - rawurlencoded post name or other slug.
*/
function qtranxf_slug_del_translations( $name ) {
	global $q_config, $wpdb;
	$wpdb->show_errors();
	$sql = 'DELETE FROM '.$wpdb->prefix.'i18n_slugs WHERE name = %s';
	$query = $wpdb->prepare( $sql, $name );
	$wpdb->query($query);
	unset($q_config['slugs-cache']['names'][$name]);
}

/**
 * @param (string) $lang - two-letter language code.
 * @param (string) $name - rawurlencoded post name or other slug.
 */
function qtranxf_slug_del_translation( $lang, $name ) {
	global $q_config, $wpdb;
	$sql = 'DELETE FROM '.$wpdb->prefix.'i18n_slugs WHERE lang = %s AND name = %s';
	$query = $wpdb->prepare( $sql, $lang, $name );
	$wpdb->query($query);
	unset($q_config['slugs-cache']['names'][$name][$lang]);
}

/**
 * @param (string) $name - rawurlencoded post name or other slug (not permastruct).
 * @return string - multilingual urldecoded value for post name or other slug.
 */
function qtranxf_slug_multilingual( $name ) {
	global $q_config;
	$name = urldecode($name);
	$slugs = qtranxf_slug_get_translations(qtranxf_slug_encode($name));
	if(empty($slugs)) return $name;
	foreach($q_config['enabled_languages'] as $lang){
		if(isset($slugs[$lang])){
			//$slugs[$lang] = urldecode($slugs[$lang]);
		}else{
			$slugs[$lang] = $name;
		}
	}
	return urldecode(qtranxf_join_b($slugs));
}

/**
 * @param (string) $slug - urldecoded post name or other slug.
*/
function qtranxf_slug_multilingual_base( $slug ) {
	global $q_config;
	$is_permastruct = qtranxf_slug_is_permastruct($slug);
	if($is_permastruct){
		$info = qtranxf_slug_split_permastruct($slug);
		$val = '';
		foreach($info['blocks'] as $b){
			if(qtranxf_slug_is_substitution($b)){
				$val .= $b;
			}else{
				$val .= qtranxf_slug_multilingual($b);
			}
		}
		return $val;
	}else{
		return qtranxf_slug_multilingual($slug);
	}
}

/**
 * @param (string) $slug - rawurlencoded post name or other slug.
 * @param (string) $lang - two-letter language code.
 * @param (string) $name - rawurlencoded post name or other slug.
 */
function qtranxf_slug_unique( $slug, $lang, $name ) {
	global $wpdb;
	//$wpdb->show_errors(); @set_time_limit(0);
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
}

{//actions
function qtranxf_slug_admin_notices_plugin_conflicts(){
	qtranxf_admin_notice_plugin_conflict('Qtranslate Slug','qtranslate-slug/qtranslate-slug.php');
}
}
