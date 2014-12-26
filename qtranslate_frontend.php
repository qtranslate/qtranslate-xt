<?php
add_filter( 'wp_get_nav_menu_items',  'qtranxf_get_nav_menu_items', 0, 3 );
function qtranxf_get_nav_menu_items( $items, $menu, $args )
{
	global $q_config;
	$language=$q_config['language'];
	$flag_location=$q_config['WP_CONTENT_URL'].$q_config['flag_location'];
	$itemid=0;
	$menu_order=0;
  $qtransmenu=null;
	foreach($items as $item)
	{
	  if($itemid<$item->ID) $itemid=$item->ID;
	  if($menu_order<$item->menu_order) $menu_order=$item->menu_order;
		if( !isset( $item->url ) || strstr( $item->url, '#qtransLangSw' ) === FALSE ) continue;
		$item->title=__('Language','qtranslate').':'.'&nbsp;<img src="'.$flag_location.$q_config['flag'][$language].'">';
		$item->url=null;
		//$item->classes[] = 'qtranxs-flag-'.$language;
		$item->classes[] = 'qtranxs-lang-menu';
		$qtransmenu = $item;
	}
	if(!$qtransmenu) return $items;
	foreach($q_config['enabled_languages'] as $lang)
	{
		$item=new WP_Post((object)array('ID' => ++$itemid));
		//$item->db_id=$item->ID;
		$item->menu_item_parent=$qtransmenu->ID;
		$item->menu_order=++$menu_order;
		$item->post_type='nav_menu_item';
		$item->object='custom';
		//$item->object_id=0;
		$item->type='custom';
		$item->type_label='Custom';
		$item->title='<img src="'.$flag_location.$q_config['flag'][$lang].'">&nbsp;'.$q_config['language_name'][$lang];
		$item->post_title=$item->title;
		$item->post_name='language-menuitem-'.$lang;
		if($lang!=$language)
			$item->url=qtranxf_convertURL($url, $lang, false, true);
		$item->classes=array();
		//$item->classes[] = 'qtranxs-flag-'.$lang;
		$item->classes[] = 'qtranxs-lang-menu-item';
		$items[]=$item;
		++$menu->count;
	}
	return $items;
}

function qtranxf_add_lang_icons ()
{
	global $q_config;
	echo "<style>\n";
	foreach($q_config['enabled_languages'] as $lang) 
	{
		echo '.qtranxs-flag-'.$lang.' {background-image: url('.trailingslashit(WP_CONTENT_URL).$q_config['flag_location'].$q_config['flag'][$lang]."); background-repeat: no-repeat;}\n";
	}
	echo "</style>\n";
}
add_filter('wp_head', 'qtranxf_add_lang_icons');

function qtranxf_postsFilter($posts) {
	if(is_array($posts)) {
		foreach($posts as $post) {
			$post->post_content = qtranxf_useCurrentLanguageIfNotFoundShowAvailable($post->post_content);
			$post = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($post);
		}
	}
	return $posts;
}
add_filter('the_posts', 'qtranxf_postsFilter');

function qtranxf_get_attachment_image_attributes($attr, $attachment, $size)
{
	foreach( $attr as $name => $value ){
		if($name!=='alt') continue;
		$attr[$name]=qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($value);
	}
	return $attr;
}
add_filter('wp_get_attachment_image_attributes', 'qtranxf_get_attachment_image_attributes',0,3);

function qtranxf_excludeUntranslatedPosts($where) {
	global $q_config, $wpdb;
	if($q_config['hide_untranslated'] && !is_singular()) {
		$where .= " AND $wpdb->posts.post_content LIKE '%<!--:".qtranxf_getLanguage()."-->%'";
	}
	return $where;
}
// don't filter untranslated posts in admin
add_filter('posts_where_request', 'qtranxf_excludeUntranslatedPosts');

$qtranxv_home=get_home_url().'/';
function qtranxf_home_url($href)
{
	global $qtranxv_home;
	if ($href===$qtranxv_home)
	{
		return qtranxf_convertURL($qtranxv_home);
	}
	else
	{
		return $href;
	}
}
add_filter('home_url', 'qtranxf_home_url');

function qtranxf_esc_html($text) {
	//echo "\nqtranxf_esc_html:text=$text\n";
	return qtranxf_useDefaultLanguage($text);
}
// filter options
add_filter('esc_html', 'qtranxf_esc_html', 0);

/*
function qtranxf_translate($text){
	$lang=qtranxf_getLanguage();
	echo "\nqtranxf_translate:lang=".$lang."\n";
	echo "text=$text\n";
	$split_regex = "#(<!--:[^-]+-->|\[:[a-z]{2}\])#ism";
	$skip=true;
	$quicktags=false;
	$blocks = preg_split($split_regex, $text, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
	foreach($blocks as $block) {
		echo "block=$block\n";
		if(preg_match("#^<!--:([a-z]{2})-->$#ism", $block, $matches)) {
			$skip=($lang != $matches[1]);
			continue;
		} elseif($quicktags && preg_match("#^\[:([a-z]{2})\]$#ism", $block, $matches)) {
			$skip=($lang != $matches[1]);
			continue;
		} elseif(preg_match("#^<!--:-->$#ism", $block, $matches)) {
			$skip=false;
			continue;
		// detect defective more tag
		//} elseif(preg_match("#^<!--more-->$#ism", $block, $matches)) {
		//	foreach($q_config['enabled_languages'] as $language) {
		//		$result[$language] .= $block;
		//	}
		//	continue;
		}
		if($skip) continue;
		$result .= $block;
	}
	return $result;
	//return qtranxf_useDefaultLanguage($text);
}
//add_filter('wpseo_title', 'qtranxf_translate', 0);
*/

// Compability with Default Widgets
qtranxf_optionFilter();
add_filter('widget_title', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
add_filter('widget_text', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);

add_filter('wp_head', 'qtranxf_add_css');
add_filter('wp_setup_nav_menu_item', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage');

add_filter('get_comment_author', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
add_filter('the_author', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
add_filter('tml_title', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);

// translate terms
add_filter('cat_row', 'qtranxf_useTermLib',0);
add_filter('cat_rows', 'qtranxf_useTermLib',0);
add_filter('wp_get_object_terms', 'qtranxf_useTermLib',0);
add_filter('single_tag_title', 'qtranxf_useTermLib',0);
add_filter('single_cat_title', 'qtranxf_useTermLib',0);
add_filter('the_category', 'qtranxf_useTermLib',0);
add_filter('get_term', 'qtranxf_useTermLib',0);
add_filter('get_terms', 'qtranxf_useTermLib',0);
add_filter('get_category', 'qtranxf_useTermLib',0);
?>
