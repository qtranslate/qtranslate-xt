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
	$flag_location=qtranxf_flag_location();
	echo '<style type="text/css">'.PHP_EOL;
	foreach($q_config['enabled_languages'] as $lang) 
	{
		echo '.qtranxs_flag_'.$lang.' {background-image: url('.$flag_location.$q_config['flag'][$lang].'); background-repeat: no-repeat;}'.PHP_EOL;
	}
	do_action('qtranslate_head_add_css');
	echo '</style>'.PHP_EOL;
}
//add_filter('wp_head', 'qtranxf_add_lang_icons_css');

function qtranxf_head(){
	global $q_config;
	$lang=$q_config['language'];
	echo "\n<meta http-equiv=\"Content-Language\" content=\"".str_replace('_','-',$q_config['locale'][$lang])."\" />\n";
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
			echo '<link hreflang="'.$language.'" href="'.qtranxf_convertURL('',$language,false,true).'" rel="alternate" />'."\n";
	}
	qtranxf_add_css();
}
add_action('wp_head', 'qtranxf_head');

/*
function qtranxf_remove_detached_children( $items )
{
	$keys=array();
	foreach($items as $key => $item){
		$keys[$item->ID]=$key;
	}
	do{
		$more=false;
		foreach($items as $key => $item){
			//qtranxf_dbg_echo('item['.$key.']: '.$item->title.'; ID='.$item->ID.'; p='.$item->menu_item_parent);
			if($item->menu_item_parent==0) continue;
			if(!isset($keys[$item->menu_item_parent])) continue;
			//qtranxf_dbg_echo('parent key='.$keys[$item->menu_item_parent]);
			if(isset($items[$keys[$item->menu_item_parent]])) continue;
			//qtranxf_dbg_echo('unset: item: '.$item->title.'; key='.$keys[$item->menu_item_parent]);
			unset($items[$key]);
			$more=true;
		}
	}while($more);
}
*/

function qtranxf_wp_get_nav_menu_items( $items, $menu, $args )
{
	global $q_config;
	$language=$q_config['language'];
	$flag_location=qtranxf_flag_location();
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
	$itemsmodified=false;
	foreach($items as $key => $item)
	{
		//qtranxf_dbg_echo('qtranxf_wp_get_nav_menu_items: $item: ',$item);
		//qtranxf_dbg_echo('qtranxf_wp_get_nav_menu_items: item: '.$item->title.'; p='.$item->menu_item_parent.'; ID='.$item->ID);
		$qtransLangSw = isset( $item->url ) && stristr( $item->url, 'qtransLangSw' ) !== FALSE;
		if(!$qtransLangSw){
			$item_title=qtranxf_use_language($language, $item->title, false, true);
			if(empty($item_title)){
				//qtranxf_dbg_echo('removed item: '.$item->title.'; p='.$item->menu_item_parent);
				unset($items[$key]);//remove menu item with empty title for this language
				$itemsmodified=true;
				continue;
			}
			$item->title = $item_title;
			if($item->object == 'custom' && !empty($item->url)){
				if(strpos($item->url,'setlang=no')===FALSE){
					$item->url = qtranxf_convertURL($item->url,$language);
				}else{
					$item->url = remove_query_arg('setlang',$item->url);
				}
			}
		}
		//qtranxf_dbg_echo('passed item: '.$item->title.'; p='.$item->menu_item_parent);

		$item->post_content=qtranxf_use_language($language, $item->post_content, false, true);
		$item->post_title=qtranxf_use_language($language, $item->post_title, false, true);
		$item->post_excerpt=qtranxf_use_language($language, $item->post_excerpt, false, true);
		$item->description=qtranxf_use_language($language, $item->description, false, true);

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
	//if(	$itemsmodified ) qtranxf_remove_detached_children($items);
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
		$item->object_id=$qtransmenu->object_id;
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
		//qtranxf_dbg_echo('qtranxf_wp_get_nav_menu_items: $item',$item);
	}
	return $items;
}
add_filter( 'wp_get_nav_menu_items',  'qtranxf_wp_get_nav_menu_items', 20, 3 );

/*
function qtranxf_wp_setup_nav_menu_item($menu_item) {
	global $q_config;
	if($menu_item->title==='test'){
		//echo "qtranxf_wp_setup_nav_menu_item: '$text'<br>\n";
		//qtranxf_dbg_echo('menu_item:',$menu_item,true);
		//qtranxf_dbg_echo('menu_item->title:'.$menu_item->title);
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

function qtranxf_postsFilter($posts,&$query) {//WP_Query
	global $q_config;
	switch($query->query_vars['post_type']){
		case 'nav_menu_item': return $posts;//will translate later in qtranxf_wp_get_nav_menu_items: to be able to filter empty labels.
		default: break;
	}
	//qtranxf_dbg_echo('qtranxf_postsFilter: $post_type: ',$query->query_vars['post_type']);
	//if(empty($query->query_vars['post_type'])){
	//	qtranxf_dbg_echo('qtranxf_postsFilter: $query: ',$query,true);
	//}
	//if(empty($posts)){
	//	qtranxf_dbg_echo('qtranxf_postsFilter: $posts: ',$posts,true);
	//}
	if(is_array($posts)) {
		$lang = $q_config['language'];
		foreach($posts as $post) {//post is an object derived from WP_Post
			//$post->post_content = qtranxf_useCurrentLanguageIfNotFoundShowAvailable($post->post_content);
			//$post->post_content = qtranxf_use_language($lang, $post->post_content, true);
			//qtranxf_dbg_log('post_content:',$post->post_content);
			//qtranxf_dbg_log('$post-before:',$post);
			//$post = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($post);
			//qtranxf_dbg_log('$post-after:',$post);
			foreach(get_object_vars($post) as $key => $txt) {
				switch($key){//the quickest way to proceed
					//known to skip
					case 'ID'://int
					case 'post_author':
					case 'post_date':
					case 'post_date_gmt':
					case 'post_status':
					case 'comment_status':
					case 'ping_status':
					case 'post_password':
					case 'post_name': //slug!
					case 'to_ping':
					case 'pinged':
					case 'post_modified':
					case 'post_modified_gmt':
					case 'post_parent': //int
					case 'guid':
					case 'menu_order': //int
					case 'post_type':
					case 'post_mime_type':
					case 'comment_count':
					case 'filter':
						continue;
					//known to translate
					case 'post_content': $post->$key = qtranxf_use_language($lang, $txt, true); break;
					case 'post_title':
					case 'post_excerpt':
					case 'post_content_filtered'://not sure how this is in use
						$post->$key = qtranxf_use_language($lang, $txt, false);
						break;
					//other maybe, if it is a string, actually most likely it never comes here
					default:
						//qtranxf_dbg_echo('qtranxf_postsFilter: other: $post->'.$key.': ',$txt);
						$post->$key = qtranxf_use($lang, $txt, false);
						//if(!is_string($txt)){
						//	qtranxf_dbg_echo('not string: $post->'.$key.': ',$txt);
						//	continue;
						//}
						//qtranxf_dbg_echo('string: $post->'.$key.': ',$txt);
						//$post->$key = qtranxf_use_language($lang, $txt, false);
				}
			}
		}
	}
	return $posts;
}
add_filter('the_posts', 'qtranxf_postsFilter', 5, 2);

/** allow all filters within WP_Query - many other add_filters may not be needed now? */
function qtranxf_pre_get_posts( &$query ) {//WP_Query
	//qtranxf_dbg_echo('qtranxf_pre_get_posts: $query: ',$query);
	//'post_type'
	$query->query_vars['suppress_filters'] = false;
}
add_action( 'pre_get_posts', 'qtranxf_pre_get_posts', 99 );

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

function qtranxf_excludeUntranslatedPosts($where,&$query) {//WP_Query
	global $wpdb;
	//qtranxf_dbg_echo('qtranxf_excludeUntranslatedPosts: post_type: ',$query->query_vars['post_type']);
	switch($query->query_vars['post_type']){
		case 'page':
		case 'post': break;
		//case '': qtranxf_dbg_echo('qtranxf_excludeUntranslatedPosts: post_type is empty: $query: ',$query, true);
		default: return $where;
	}
	//qtranxf_dbg_echo('qtranxf_excludeUntranslatedPosts: $where: ',$where);
	//qtranxf_dbg_echo('qtranxf_excludeUntranslatedPosts: is_singular(): ',is_singular());
	$single_post_query=is_singular();
	if($single_post_query){
		$single_post_query = preg_match('/ID\s*=\s*[\'"]*(\d+)[\'"]*/i',$where,$matches)==1;
		//qtranxf_dbg_echo('qtranxf_excludeUntranslatedPosts: $single_post_query: ',$single_post_query);
		//if($single_post_query){
		//	//qtranxf_dbg_echo('qtranxf_excludeUntranslatedPosts: $matches[1]:',$matches[1]);
		//}
	}
	if(!$single_post_query){
		$where .= " AND $wpdb->posts.post_content LIKE '%<!--:".qtranxf_getLanguage()."-->%'";
	}
	return $where;
}

function qtranxf_excludeUntranslatedPostComments($clauses, &$q/*WP_Comment_Query*/) {
	global $wpdb;
	//qtranxf_dbg_echo('qtranxf_excludeUntranslatedPostComments: $clauses: ',$clauses);
	$single_post_query=is_singular();
	if($single_post_query){
		$single_post_query = preg_match('/comment_post_ID\s*=\s*[\'"]*(\d+)[\'"]*/i',$clauses['where'])==1;
	}
	if(!$single_post_query){
		$clauses['where'] .= " AND $wpdb->posts.post_content LIKE '%<!--:".qtranxf_getLanguage()."-->%'";
	}
	return $clauses;
}

function qtranxf_home_url($url, $path, $orig_scheme, $blog_id)
{
	global $q_config;
	$lang = $q_config['language'];
	//qtranxf_dbg_log('qtranxf_home_url: url='.$url.'; path='.$path.'; orig_scheme='.$orig_scheme);
	$url = qtranxf_get_url_for_language($url, $lang, !$q_config['hide_default_language'] || $lang != $q_config['default_language']);
	//qtranxf_dbg_log('qtranxf_home_url: url='.$url.'; lang='.$lang);
	//$url=qtranxf_convertURL($url,$lang,false,!$q_config['hide_default_language']);
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

//filter added in qtranslate_hooks.php
function qtranxf_trim_words( $text, $num_words, $more, $original_text ) {
	global $q_config;
	$blocks = qtranxf_get_language_blocks($original_text);
	if ( count($blocks) <= 1 )
		return $text;
	$lang = $q_config['language'];
	$text = qtranxf_use_block($lang, $blocks, true, false);
	return wp_trim_words($text, $num_words, $more);
}

/*
function qtranxf_img_caption_shortcode($output, $attr, $content) {
	qtranxf_dbg_echo('qtranxf_img_caption_shortcode: attr:',$attr);
	qtranxf_dbg_echo('qtranxf_img_caption_shortcode: content:',$content);
	if(is_array($attr)){
		foreach($attr as $key => $a){
			$attr[$key] = qtranxf_useCurrentLanguageIfNotFoundShowEmpty($a);
		}
	}
	return $output;
}
add_filter( 'img_caption_shortcode', 'qtranxf_img_caption_shortcode',10,3);
*/

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

//add_filter('wp_head', 'qtranxf_add_css');

// Compability with Default Widgets
add_filter('widget_title', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
add_filter('widget_text', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
add_filter('the_title', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 0);//WP: fires for display purposes only
add_filter('category_description', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
add_filter('list_cats', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
add_filter('wp_dropdown_cats', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
add_filter('term_name', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
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
