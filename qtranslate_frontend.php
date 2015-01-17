<?php
/*
	Copyright 2014  qTranslate Team  (email : qTranslateTeam@gmail.com )

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function qtranxf_add_lang_icons_css ()
{
	global $q_config;
	echo '<style type="text/css">'.PHP_EOL;
	foreach($q_config['enabled_languages'] as $lang) 
	{
		echo '.qtranxs_flag_'.$lang.' {background-image: url('.trailingslashit(WP_CONTENT_URL).$q_config['flag_location'].$q_config['flag'][$lang].'); background-repeat: no-repeat;}'.PHP_EOL;
	}
	do_action('qtranxf_head_add_css');
	echo '</style>'.PHP_EOL;
}
//add_filter('wp_head', 'qtranxf_add_lang_icons_css');

function qtranxf_head(){
	global $q_config;
	echo "\n<meta http-equiv=\"Content-Language\" content=\"".str_replace('_','-',$q_config['locale'][$q_config['language']])."\" />\n";
	qtranxf_add_lang_icons_css();
/*
	$css = "<style type=\"text/css\" media=\"screen\">\n";
	$css .=".qtranxs_flag span { display:none }\n";
	$css .=".qtranxs_flag { height:12px; width:18px; display:block }\n";
	$css .=".qtranxs_flag_and_text { padding-left:20px }\n";
	$baseurl = WP_CONTENT_URL;
	if(isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == '1' || $_SERVER['HTTPS'] == 'on')) {
		$baseurl = preg_replace('#^http://#','https://', $baseurl);
	}
	foreach($q_config['enabled_languages'] as $language) {
		$css .=".qtranxs_flag_".$language." { background:url(".$baseurl.'/'.$q_config['flag_location'].$q_config['flag'][$language].") no-repeat }\n";
	}
	//$css .= get_option('qtranslate_widget_css',QTX_WIDGET_CSS);
	$css .="</style>\n";
	echo apply_filters('qtranslate_header_css',$css);
*/
	// skip the rest if 404 //what if the menu is still shown through 404.php?
	//if(is_404()) return;
	// set links to translations of current page
	foreach($q_config['enabled_languages'] as $language) {
		if($language != qtranxf_getLanguage())
			echo '<link hreflang="'.$language.'" href="'.qtranxf_convertURL('',$language).'" rel="alternate" />'."\n";
	}
}
add_action('wp_head', 'qtranxf_head');

function qtranxf_wp_get_nav_menu_items( $items, $menu, $args )
{
	global $q_config;
	$language=$q_config['language'];
	$flag_location=trailingslashit(WP_CONTENT_URL).$q_config['flag_location'];
	$itemid=0;
	$menu_order=0;
  $qtransmenu=null;
	$altlang=null;
	$url='';//it will keep the same page
	//options
	$type='LM';//[LM|AL]
	$title='Language';//[none|Language|Current]
	$current=true;//[shown|hidden]
	$flags=true;//[none|all|items]
	$topflag=true;
	foreach($items as $key => $item)
	{
		//qtranxf_dbg_echo('item->title:'.$item->title);
		$qtransLangSw = isset( $item->url ) && stristr( $item->url, 'qtransLangSw' ) !== FALSE;
		if(!$qtransLangSw){
			$item_title=qtranxf_use($language, $item->title, false, true);
			if(empty($item_title)){
				unset($items[$key]);//remove menu item with empty title for this language
				continue;
			}
			$item->title=$item_title;
		}
		if($itemid<$item->ID) $itemid=$item->ID;
		if($menu_order<$item->menu_order) $menu_order=$item->menu_order;
		if(!$qtransLangSw) continue;
		$p=strpos($item->url,'?');
		if($p!==FALSE){
			$qs=substr($item->url,$p+1);
			$qs=str_replace('#','',$qs);
			$pars=array(); parse_str($qs,$pars);
			if(isset($pars['type']) && stripos($pars['type'],'AL')!==FALSE ) $type='AL';
			if(isset($pars['flags'])){
				$flags=(stripos($pars['flags'],'no')===FALSE);
				if($flags) $topflag=(stripos($pars['flags'],'items')===FALSE);
				else $topflag=false;
			}
			if(isset($pars['title'])){
				$title=$pars['title'];
				if(stripos($pars['title'],'no')!==FALSE) $title='';
				if(!$topflag && empty($title)) $title='Language';
			}
			if(isset($pars['current'])){
				$current=(stripos($pars['current'],'hid')===FALSE);
			}
		}
		if($type=='AL'){
			foreach($q_config['enabled_languages'] as $lang){
				if($lang==$language) continue;
				$toplang=$lang;
				$altlang=$lang;
				break;
			}
			$item->title=empty($title)?'':$q_config['language_name'][$toplang];
			$item->url=qtranxf_convertURL($url, $altlang, false, true);
		}else{
			$toplang=$language;
			if(empty($title)){
				$item->title='';
			}elseif(stripos($title,'Current')!==FALSE){
				$item->title=$q_config['language_name'][$toplang];
			}else{
				$item->title=__('Language','qtranslate');
			}
			$item->url=null;
		}
		if($topflag){
			if(!empty($item->title)) $item->title.=':&nbsp;';
			$item->title.='<img src="'.$flag_location.$q_config['flag'][$toplang].'">';
		}
		//$item->classes[] = 'qtranxs_flag_'.$language;
		$item->classes[] = 'qtranxs-lang-menu';
		$qtransmenu = $item;
	}
	if(!$qtransmenu) return $items;
	foreach($q_config['enabled_languages'] as $lang)
	{
		if($type=='AL'){
			if($lang==$language) continue;
			if($lang==$altlang ) continue;
		}elseif(!$current){
			if($lang==$language) continue;
		}
		$item=new WP_Post((object)array('ID' => ++$itemid));
		//$item->db_id=$item->ID;
		$item->menu_item_parent=$qtransmenu->ID;
		$item->menu_order=++$menu_order;
		$item->post_type='nav_menu_item';
		$item->object='custom';
		//$item->object_id=0;
		$item->type='custom';
		$item->type_label='Custom';
		$item->title=$q_config['language_name'][$lang];
		if($flags){
			$item->title='<img src="'.$flag_location.$q_config['flag'][$lang].'">&nbsp;'.$item->title;
		}
		$item->post_title=$item->title;
		$item->post_name='language-menuitem-'.$lang;
		if($lang!=$language)
			$item->url=qtranxf_convertURL($url, $lang, false, true);
		$item->classes=array();
		//$item->classes[] = 'qtranxs_flag_'.$lang;
		$item->classes[] = 'qtranxs-lang-menu-item';
		$items[]=$item;
		++$menu->count;
	}
	return $items;
}
add_filter( 'wp_get_nav_menu_items',  'qtranxf_wp_get_nav_menu_items', 0, 3 );

/*
function qtranxf_wp_setup_nav_menu_item($menu_item) {
	global $q_config;
	if($menu_item->title==='[:ru][:en]EN'){
		//echo "qtranxf_wp_setup_nav_menu_item: '$text'<br>\n";
		//qtranxf_dbg_echo('menu_item:',$menu_item,true);
		qtranxf_dbg_echo('menu_item->title:'.$menu_item->title);
		//$menu_item->title='test';//is in use
		//$menu_item->post_title='';//not in use in menu
		//$menu_item->title='';
		//unset($menu_item);
	}
	//return $menu_item;
	return qtranxf_use($q_config['language'], $menu_item, false, true);
	//return qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($menu_item);
}
add_filter('wp_setup_nav_menu_item', 'qtranxf_wp_setup_nav_menu_item');
*/

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

//function qtranxf_get_attachment_image_attributes($attr, $attachment, $size)
function qtranxf_get_attachment_image_attributes($attr)
{
	foreach( $attr as $name => $value ){
		if($name!=='alt') continue;
		$attr[$name]=qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($value);
	}
	return $attr;
}
add_filter('wp_get_attachment_image_attributes', 'qtranxf_get_attachment_image_attributes',0);
//add_filter('wp_get_attachment_image_attributes', 'qtranxf_get_attachment_image_attributes',0,3);

function qtranxf_excludeUntranslatedPosts($where) {
	global $q_config, $wpdb;
	if($q_config['hide_untranslated'] && !is_singular()) {
		$where .= " AND $wpdb->posts.post_content LIKE '%<!--:".qtranxf_getLanguage()."-->%'";
	}
	return $where;
}
// don't filter untranslated posts in admin
add_filter('posts_where_request', 'qtranxf_excludeUntranslatedPosts');

function qtranxf_home_url($url, $path, $orig_scheme, $blog_id)
{
	global $q_config;
	//qtranxf_dbg_log('qtranxf_home_url: url='.$url.'; path='.$path.'; orig_scheme='.$orig_scheme);
	$url=qtranxf_convertURL($url,'',false,!$q_config['hide_default_language']);
	//if((empty($path) && $q_config['url_mode'] == QT_URL_PATH) || $path == '/' || !empty($q_config['url_info']['explicit_default_language'])){
	//	$url=qtranxf_convertURL($url, '', false, $q_config['url_info']['explicit_default_language']);
	//}
	//qtranxf_dbg_log('qtranxf_home_url: new='.$url,wp_debug_backtrace_summary(null,0,false));
	return $url;
}
/*
$qtranxv_home=trailingslashit(get_home_url());
function qtranxf_home_url($url, $path, $orig_scheme, $blog_id)
{
	global $qtranxv_home;
	if ($href===$qtranxv_home){
		return qtranxf_convertURL($href);
	}else{
		return $href;
	}
}
*/
add_filter('home_url', 'qtranxf_home_url', 0, 4);

function qtranxf_esc_html($text) {
	//qtranxf_dbg_echo('qtranxf_esc_html:text='.$text,null,true);
	//never saw a case when this needs to be translated at all ...
	return qtranxf_useDefaultLanguage($text);//this does not make sense, does it?
	//return qtranxf_useCurrentLanguageIfNotFoundShowEmpty($text);
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


qtranxf_optionFilter();
add_filter('wp_head', 'qtranxf_add_css');

// Compability with Default Widgets
add_filter('widget_title', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
add_filter('widget_text', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);

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
