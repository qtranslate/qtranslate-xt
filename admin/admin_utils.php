<?php

function qtranxf_detect_admin_language($url_info) {
	global $q_config;
	$cs=null;
	$lang=null;
	if(isset($_COOKIE[QTX_COOKIE_NAME_ADMIN])){
		$lang=qtranxf_resolveLangCase($_COOKIE[QTX_COOKIE_NAME_ADMIN],$cs);
		$url_info['lang_cookie_admin'] = $lang;
	}
	if(!$lang){
		$locale = get_locale();
		$url_info['locale'] = $locale;
		$lang = qtranxf_resolveLangCase(substr($locale,0,2),$cs);
		$url_info['lang_locale'] = $lang;
		if(!$lang) $lang = $q_config['default_language'];
	}
	$url_info['doing_front_end'] = false;
	$url_info['lang_admin'] = $lang;
	return $url_info;
}
add_filter('qtranslate_detect_admin_language','qtranxf_detect_admin_language');

function qtranxf_convert_to_b($text) {
	$blocks = qtranxf_get_language_blocks($text);
	if( count($blocks) > 1 ){
		foreach($blocks as $key => $b){
			if(empty($b)) unset($blocks[$key]);
		}
	}
	if( count($blocks) <= 1 )
		return $text;

	$text='';
	$lang = false;
	$lang_closed = true;
	foreach($blocks as $block) {
		if(preg_match("#^<!--:([a-z]{2})-->$#ism", $block, $matches)) {
			$lang_closed = false;
			$lang = $matches[1];
			$text .= '[:'.$lang.']';
			continue;
		} elseif(preg_match("#^\[:([a-z]{2})\]$#ism", $block, $matches)) {
			$lang_closed = false;
			$lang = $matches[1];
			$text .= '[:'.$lang.']';
			continue;
		}
		switch($block){
			case '[:]':
			case '<!--:-->':
				$lang = false;
				break;
			default:
				if( !$lang && !$lang_closed ){
					$text .= '[:]';
					$lang_closed = true;
				}
				$text .= $block;
				break;
		}
	}
	$text .= '[:]';
	return $text;
}

function qtranxf_convert_to_b_no_closing($text) {
	$blocks = qtranxf_get_language_blocks($text);
	if( count($blocks) > 1 ){
		foreach($blocks as $key => $b){
			if(empty($b)) unset($blocks[$key]);
		}
	}
	if( count($blocks) > 1 ){
		$texts = qtranxf_split_blocks($blocks);
		$text = qtranxf_join_b_no_closing($texts);
	}
	return $text;
}

function qtranxf_convert_to_c($text) {
	$blocks = qtranxf_get_language_blocks($text);
	if( count($blocks) > 1 ){
		foreach($blocks as $key => $b){
			if(empty($b)) unset($blocks[$key]);
		}
	}
	if( count($blocks) > 1 ){
		$texts = qtranxf_split_blocks($blocks);
		$text = qtranxf_join_c($texts);
	}
	return $text;
}

function qtranxf_convert_to_b_deep($text) {
	if(is_array($text)) {
		foreach($text as $key => $t) {
			$text[$key] = qtranxf_convert_to_b_deep($t);
		}
		return $text;
	}

	if( is_object($text) || $text instanceof __PHP_Incomplete_Class ) {
		foreach(get_object_vars($text) as $key => $t) {
			$text->$key = qtranxf_convert_to_b_deep($t);
		}
		return $text;
	}

	if(!is_string($text) || empty($text))
		return $text;

	return qtranxf_convert_to_b($text);
}

function qtranxf_convert_to_b_no_closing_deep($text) {
	if(is_array($text)) {
		foreach($text as $key => $t) {
			$text[$key] = qtranxf_convert_to_b_no_closing_deep($t);
		}
		return $text;
	}

	if( is_object($text) || $text instanceof __PHP_Incomplete_Class ) {
		foreach(get_object_vars($text) as $key => $t) {
			$text->$key = qtranxf_convert_to_b_no_closing_deep($t);
		}
		return $text;
	}

	if(!is_string($text) || empty($text))
		return $text;

	return qtranxf_convert_to_b_no_closing($text);
}

function qtranxf_convert_database($action){
	global $wpdb;
	$wpdb->show_errors();
	qtranxf_convert_database_options($action);
	qtranxf_convert_database_posts($action);
	qtranxf_convert_database_postmeta($action);
	switch($action){
		case 'b_only':
			return __('Database has been converted to square bracket format.', 'qtranslate').'<br/>'.__('Note: custom entries are not touched.', 'qtranslate');
		case 'c_dual':
			return __('Database has been converted to legacy dual-tag format.', 'qtranslate').'<br/>'.__('Note: custom entries are not touched.', 'qtranslate');
		default: return '';
	}
}

function qtranxf_convert_database_options($action){
	global $wpdb;
	$result = $wpdb->get_results('SELECT option_id, option_value FROM '.$wpdb->options);
	if(!$result) return;
	switch($action){
		case 'b_only':
			foreach($result as $row) {
				if(!qtranxf_isMultilingual($row->option_value)) continue;
				//if(!preg_match('/(<!--:[a-z]{2}-->|\[:[a-z]{2}\])/im',$row->option_value)) continue;
				$value = maybe_unserialize($row->option_value);
				$value_converted=qtranxf_convert_to_b_deep($value);
				$value_serialized = maybe_serialize($value_converted);
				if($value_serialized === $row->option_value) continue;
				//Since 3.2-b3: Replaced mysql_real_escape_string with $wpdb->prepare
				$wpdb->query($wpdb->prepare('UPDATE '.$wpdb->options.' set option_value = %s WHERE option_id = %d', $value_serialized, $row->option_id));
				//Old Line:
				//$wpdb->query('UPDATE '.$wpdb->options.' set option_value = "'.mysql_real_escape_string($value_serialized).'" WHERE option_id='.$row->option_id);
				//End Changes
			}
			break;
		case 'c_dual':
			foreach($result as $row) {
				if(!qtranxf_isMultilingual($row->option_value)) continue;
				//if(!preg_match('/(<!--:[a-z]{2}-->|\[:[a-z]{2}\])/im',$row->option_value)) continue;
				$value = maybe_unserialize($row->option_value);
				$value_converted=qtranxf_convert_to_b_no_closing_deep($value);
				$value_serialized = maybe_serialize($value_converted);
				if($value_serialized === $row->option_value) continue;
				//Since 3.2-b3: Replaced mysql_real_escape_string with $wpdb->prepare
				$wpdb->query($wpdb->prepare('UPDATE '.$wpdb->options.' set option_value = %s WHERE option_id = %d', $value_serialized, $row->option_id));
				//Old Line:
				//$wpdb->query('UPDATE '.$wpdb->options.' set option_value = "'.mysql_real_escape_string($value_serialized).'" WHERE option_id='.$row->option_id);
				//End Changes
			}
			break;
		default: break;
	}
}

function qtranxf_convert_database_posts($action){
	global $wpdb;
	$result = $wpdb->get_results('SELECT ID, post_title, post_content, post_excerpt FROM '.$wpdb->posts);
	if(!$result) return;
	switch($action){
		case 'b_only':
			foreach($result as $row) {
				$title=qtranxf_convert_to_b($row->post_title);
				$content=qtranxf_convert_to_b($row->post_content);
				$excerpt=qtranxf_convert_to_b($row->post_excerpt);
				if( $title==$row->post_title && $content==$row->post_content && $excerpt==$row->post_excerpt ) continue;
				//Since 3.2-b3: Replaced mysql_real_escape_string with $wpdb->prepare
				$wpdb->query($wpdb->prepare('UPDATE '.$wpdb->posts.' set post_content = %s, post_title = %s, post_excerpt = %s WHERE ID = %d',$content, $title, $excerpt, $row->ID));
				//$wpdb->query('UPDATE '.$wpdb->posts.' set post_content = "'.mysql_real_escape_string($content).'", post_title = "'.mysql_real_escape_string($title).'", post_excerpt = "'.mysql_real_escape_string($excerpt).'" WHERE ID='.$row->ID);
			}
			break;
		case 'c_dual':
			foreach($result as $row) {
				$title=qtranxf_convert_to_c($row->post_title);
				$content=qtranxf_convert_to_c($row->post_content);
				$excerpt=qtranxf_convert_to_c($row->post_excerpt);
				if( $title==$row->post_title && $content==$row->post_content && $excerpt==$row->post_excerpt ) continue;
				//Since 3.2-b3: Replaced mysql_real_escape_string with $wpdb->prepare
				$wpdb->query($wpdb->prepare('UPDATE '.$wpdb->posts.' set post_content = %s, post_title = %s, post_excerpt = %s WHERE ID = %d',$content, $title, $excerpt, $row->ID));
				//$wpdb->query('UPDATE '.$wpdb->posts.' set post_content = "'.mysql_real_escape_string($content).'", post_title = "'.mysql_real_escape_string($title).'", post_excerpt = "'.mysql_real_escape_string($excerpt).'" WHERE ID='.$row->ID);
			}
			break;
		default: break;
	}
}

function qtranxf_convert_database_postmeta($action){
	global $wpdb;
	$result = $wpdb->get_results('SELECT meta_id, meta_value FROM '.$wpdb->postmeta);
	if(!$result) return;
	switch($action){
		case 'b_only':
			foreach($result as $row) {
				if(!qtranxf_isMultilingual($row->meta_value)) continue;
				//if(!preg_match('/(<!--:[a-z]{2}-->|\[:[a-z]{2}\])/im',$row->meta_value)) continue;
				$value = maybe_unserialize($row->meta_value);
				$value_converted=qtranxf_convert_to_b_deep($value);
				$value_serialized = maybe_serialize($value_converted);
				if($value_serialized === $row->meta_value) continue;
				$wpdb->query($wpdb->prepare('UPDATE '.$wpdb->postmeta.' set meta_value = %s WHERE meta_id = %d', $value_serialized, $row->meta_id));
				//$wpdb->query('UPDATE '.$wpdb->postmeta.' set meta_value = "'.mysql_real_escape_string($value_serialized).'" WHERE meta_id='.$row->meta_id);
			}
			break;
		case 'c_dual':
			foreach($result as $row) {
				if(!qtranxf_isMultilingual($row->meta_value)) continue;
				//if(!preg_match('/(<!--:[a-z]{2}-->|\[:[a-z]{2}\])/im',$row->meta_value)) continue;
				$value = maybe_unserialize($row->meta_value);
				$value_converted=qtranxf_convert_to_b_no_closing_deep($value);
				$value_serialized = maybe_serialize($value_converted);
				if($value_serialized === $row->meta_value) continue;
				$wpdb->query($wpdb->prepare('UPDATE '.$wpdb->postmeta.' set meta_value = %s WHERE meta_id = %d', $value_serialized, $row->meta_id));
				//$wpdb->query('UPDATE '.$wpdb->postmeta.' set meta_value = "'.mysql_real_escape_string($value_serialized).'" WHERE meta_id='.$row->meta_id);
			}
			break;
		default: break;
	}
}

function qtranxf_mark_default($text) {
	global $q_config;
	$blocks = qtranxf_get_language_blocks($text);
	if( count($blocks) > 1 ) return $text;//already have other languages.
	$content=array();
	foreach($q_config['enabled_languages'] as $language) {
		if($language == $q_config['default_language']) {
			$content[$language] = $text;
		}else{
			$content[$language] = '';
		}
	}
	return qtranxf_join_b($content);
}

function qtranxf_term_name_encoded($name) {
	global $q_config;
	if(isset($q_config['term_name'][$name])) {
		$name = qtranxf_join_b($q_config['term_name'][$name]);
	}
	return $name;
}

function qtranxf_get_term_joined($obj,$taxonomy=null) {
	global $q_config;
	if(is_object($obj)) {
		// object conversion
		if(isset($q_config['term_name'][$obj->name])) {
			//'[:'.$q_config['language'].']'.$obj->name
			$obj->name = qtranxf_join_b($q_config['term_name'][$obj->name]);
			//qtranxf_dbg_log('qtranxf_get_term_joined: object:',$obj);
		} 
	}elseif(isset($q_config['term_name'][$obj])) {
		$obj = qtranxf_join_b($q_config['term_name'][$obj]);
		//'[:'.$q_config['language'].']'.$obj.
		//qtranxf_dbg_echo('qtranxf_get_term_joined: string:',$obj,true);//never fired, we probably do not need it
	}
	return $obj;
}

function qtranxf_get_terms_joined($terms, $taxonomies=null, $args=null) {
	global $q_config;
	if(is_array($terms)){
		// handle arrays recursively
		foreach($terms as $key => $term) {
			$terms[$key] = qtranxf_get_terms_joined($term);
		}
	}else{
		$terms = qtranxf_get_term_joined($terms);
	}
	return $terms;
}

function qtranxf_useAdminTermLibJoin($obj, $taxonomies=null, $args=null) {
	global $pagenow;
	//qtranxf_dbg_echo('qtranxf_useAdminTermLibJoin: $pagenow='.$pagenow);
	//qtranxf_dbg_echo('qtranxf_useAdminTermLibJoin: $obj:',$obj);
	//qtranxf_dbg_echo('qtranxf_useAdminTermLibJoin: $taxonomies:',$taxonomies);
	//qtranxf_dbg_echo('qtranxf_useAdminTermLibJoin: $args:',$args);
	switch($pagenow){
		case 'nav-menus.php':
		case 'edit-tags.php':
		case 'edit.php':
			return qtranxf_get_terms_joined($obj);
		default: return qtranxf_useTermLib($obj);
	}
}
add_filter('get_term', 'qtranxf_useAdminTermLibJoin', 5, 2);
add_filter('get_terms', 'qtranxf_useAdminTermLibJoin', 5, 3);

//does someone use it?
function qtranxf_useAdminTermLib($obj) {
	//qtranxf_dbg_echo('qtranxf_useAdminTermLib: $obj: ',$obj,true);
	if ($script_name==='/wp-admin/edit-tags.php' &&
		strstr($_SERVER['QUERY_STRING'], 'action=edit' )!==FALSE)
	{
		return $obj;
	}
	else
	{
		return qtranxf_useTermLib($obj);
	}
}
//add_filter('get_term', 'qtranxf_useAdminTermLib',0);
//add_filter('get_terms', 'qtranxf_useAdminTermLib',0);


function qtranxf_updateTermLibrary() {
	global $q_config;
	if(!isset($_POST['action'])) return;
	switch($_POST['action']) {
		case 'editedtag':
		case 'addtag':
		case 'editedcat':
		case 'addcat':
		case 'add-cat':
		case 'add-tag':
		case 'add-link-cat':
			if(isset($_POST['qtrans_term_'.$q_config['default_language']]) && $_POST['qtrans_term_'.$q_config['default_language']]!='') {
				$default = htmlspecialchars(qtranxf_stripSlashesIfNecessary($_POST['qtrans_term_'.$q_config['default_language']]), ENT_NOQUOTES);
				if(!isset($q_config['term_name'][$default]) || !is_array($q_config['term_name'][$default])) $q_config['term_name'][$default] = array();
				foreach($q_config['enabled_languages'] as $lang) {
					$_POST['qtrans_term_'.$lang] = qtranxf_stripSlashesIfNecessary($_POST['qtrans_term_'.$lang]);
					if($_POST['qtrans_term_'.$lang]!='') {
						$q_config['term_name'][$default][$lang] = htmlspecialchars($_POST['qtrans_term_'.$lang], ENT_NOQUOTES);
					} else {
						$q_config['term_name'][$default][$lang] = $default;
					}
				}
				update_option('qtranslate_term_name',$q_config['term_name']);
			}
		break;
	}
}

function qtranxf_updateTermLibraryJoin() {
	global $q_config;
	if(!isset($_POST['action'])) return;
	$action=$_POST['action'];
	if(!isset($_POST['qtrans_term_field_name'])) return;
	$field=$_POST['qtrans_term_field_name'];
	$default_name_original=$_POST['qtrans_term_field_default_name'];
	//qtranxf_dbg_log('$_POST:',$_POST);
	$field_value = qtranxf_stripSlashesIfNecessary($_POST[$field]);
	//qtranxf_dbg_log('$field_value='.$field_value);
	$names=qtranxf_split($field_value);
	//qtranxf_dbg_log('names=',$names);
	$default_name=htmlspecialchars($names[$q_config['default_language']], ENT_NOQUOTES);
	$_POST[$field]=$default_name;
	if(empty($default_name))
		return;//will generate error later from WP
	foreach($names as $lang => $name){
		$q_config['term_name'][$default_name_original][$lang] = htmlspecialchars($name, ENT_NOQUOTES);
	}
	if($default_name_original != $default_name){
		$q_config['term_name'][$default_name]=$q_config['term_name'][$default_name_original];
		unset($q_config['term_name'][$default_name_original]);
	}
	update_option('qtranslate_term_name',$q_config['term_name']);
}

/*
function qtranxf_edit_terms($term_id, $taxonomy){
	//qtranxf_dbg_log('qtranxf_edit_terms: $name='.$name);
}
add_action('edit_terms','qtranxf_edit_terms');
*/

function qtranxf_language_columns($columns) {
	return array(
		'flag' => __('Flag', 'qtranslate'),
		'name' => __('Name', 'qtranslate'),
		'status' => __('Action', 'qtranslate'),
		'status2' => '',
		'status3' => ''
		);
}

function qtranxf_languageColumnHeader($columns){
	$new_columns = array();
	if(isset($columns['cb'])) $new_columns['cb'] = '';
	if(isset($columns['title'])) $new_columns['title'] = '';
	if(isset($columns['author'])) $new_columns['author'] = '';
	if(isset($columns['categories'])) $new_columns['categories'] = '';
	if(isset($columns['tags'])) $new_columns['tags'] = '';
	$new_columns['language'] = __('Languages', 'qtranslate');
	return array_merge($new_columns, $columns);
}

function qtranxf_languageColumn($column) {
	global $q_config, $post;
	if ($column == 'language') {
		$available_languages = qtranxf_getAvailableLanguages($post->post_content);
		$missing_languages = array_diff($q_config['enabled_languages'], $available_languages);
		$available_languages_name = array();
		foreach($available_languages as $language) {
			$available_languages_name[] = $q_config['language_name'][$language];
		}
		$available_languages_names = join(", ", $available_languages_name);
		
		echo apply_filters('qtranslate_available_languages_names',$available_languages_names);
		do_action('qtranslate_languageColumn', $available_languages, $missing_languages);
	}
	return $column;
}

function qtranxf_admin_list_cats($text) {
	global $pagenow;
	//qtranxf_dbg_echo('qtranxf_admin_list_cats: $text',$text);
	switch($pagenow){
		case 'edit-tags.php':
			//replace [:] with <:>
			$blocks = qtranxf_get_language_blocks($text);
			if(count($blocks)<=1) return $text;
			$texts = qtranxf_split_blocks($blocks);
			//$text = qtranxf_join_c($texts);
			$text = qtranxf_join_b($texts);//with closing tag
			return $text;
		default: return qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($text);
	}
}
add_filter('list_cats', 'qtranxf_admin_list_cats',0);

function qtranxf_admin_dropdown_cats($text) {
	global $pagenow;
	//qtranxf_dbg_echo('qtranxf_admin_list_cats: $text',$text);
	switch($pagenow){
		case 'edit-tags.php':
			return $text;
		default: return qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($text);
	}
}
add_filter('wp_dropdown_cats', 'qtranxf_admin_dropdown_cats',0);

function qtranxf_admin_category_description($text) {
	global $pagenow;
	switch($pagenow){
		case 'edit-tags.php':
			return $text;
		default: return qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($text);
	}
}
add_filter('category_description', 'qtranxf_admin_category_description',0);

function qtranxf_admin_the_title($title) {
	global $pagenow;
	switch($pagenow){
		//case 'edit-tags.php':
		case 'nav-menus.php':
			return $title;
		default: return qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($title);
	}
}
add_filter('the_title', 'qtranxf_admin_the_title', 0);//WP: fires for display purposes only

//filter added in qtranslate_hooks.php
function qtranxf_trim_words( $text, $num_words, $more, $original_text ) {
	global $q_config;
	//qtranxf_dbg_log('qtranxf_trim_words: $text: ',$text);
	//qtranxf_dbg_log('qtranxf_trim_words: $original_text: ',$original_text);
	$blocks = qtranxf_get_language_blocks($original_text);
	//qtranxf_dbg_log('qtranxf_trim_words: $blocks: ',$blocks);
	if ( count($blocks) <= 1 )
		return $text;
	$lang = $q_config['language'];
	$texts = qtranxf_split_blocks($blocks);
	foreach($texts as $key => $txt){
		$texts[$key] = wp_trim_words($txt, $num_words, $more);
	}
	return qtranxf_join_b($texts);//has to be 'b', because 'c' gets stripped in /wp-admin/includes/nav-menu.php:182: esc_html( $item->description )
}

/**
 * The same as core wp_htmledit_pre in /wp-includes/formatting.php,
 * but with last argument of htmlspecialchars $double_encode off,
 * which makes it to survive multiple applications from other plugins,
 * for example, "PS Disable Auto Formatting" (https://wordpress.org/plugins/ps-disable-auto-formatting/)
 * cited on support thread https://wordpress.org/support/topic/incompatibility-with-ps-disable-auto-formatting.
 * @since 2.9.8.9
*/
if(!function_exists('qtranxf_htmledit_pre')){
function qtranxf_htmledit_pre($output) {
	if ( !empty($output) )
		$output = htmlspecialchars($output, ENT_NOQUOTES, get_option( 'blog_charset' ), false ); // convert only < > &
	return apply_filters( 'htmledit_pre', $output );
}
}

function qtranxf_the_editor($editor_div)
{
	// remove wpautop, which causes unmatched <p> on combined language strings
	if('html' != wp_default_editor()) {
		remove_filter('the_editor_content', 'wp_richedit_pre');
		add_filter('the_editor_content', 'qtranxf_htmledit_pre', 99);
	}
	return $editor_div;
}

function qtranxf_filter_options_general($value)
{
	global $q_config;
	global $pagenow;
	switch($pagenow){
		case 'options-general.php':
		case 'customize.php'://there is more work to do for this case
			return $value;
		default: break;
	}
	$lang = $q_config['language'];
	return qtranxf_use_language($lang,$value,false,false);
}
add_filter('option_blogname', 'qtranxf_filter_options_general');
add_filter('option_blogdescription', 'qtranxf_filter_options_general');

/* this did not work, need more investigation
function qtranxf_enable_blog_title_filters($name)
{
	add_filter('option_blogname', 'qtranxf_filter_options_general');
	add_filter('option_blogdescription', 'qtranxf_filter_options_general');
}
add_action( 'get_header', 'qtranxf_enable_blog_title_filters' );

function qtranxf_disable_blog_title_filters($name)
{
	remove_filter('option_blogname', 'qtranxf_filter_options_general');
	remove_filter('option_blogdescription', 'qtranxf_filter_options_general');
}
add_action( 'wp_head', 'qtranxf_disable_blog_title_filters' );
*/

function qtranxf_add_admin_filters(){
	global $q_config;
	switch($q_config['editor_mode']){
		case QTX_EDITOR_MODE_RAW:
		break;
		case QTX_EDITOR_MODE_LSB:
		default:
			//applied in /wp-includes/class-wp-editor.php
			add_filter('the_editor', 'qtranxf_the_editor');
		break;
	}
}
qtranxf_add_admin_filters();

add_filter('manage_language_columns', 'qtranxf_language_columns');
add_filter('manage_posts_columns', 'qtranxf_languageColumnHeader');
add_filter('manage_posts_custom_column', 'qtranxf_languageColumn');
add_filter('manage_pages_columns', 'qtranxf_languageColumnHeader');
add_filter('manage_pages_custom_column', 'qtranxf_languageColumn');
