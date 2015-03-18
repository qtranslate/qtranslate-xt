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

function qtranxf_head(){
	global $q_config;

	if( $q_config['header_css_on'] ){
		$header_css = qtranxf_front_header_css();
		echo '<style type="text/css">'.PHP_EOL;
		echo $header_css;
		echo '</style>'.PHP_EOL;
	}
	do_action('qtranslate_head_add_css');//not really needed?

	// skip the rest if 404
	if(is_404()) return;

	// set links to translations of current page
	//$lang=$q_config['language'];
	//echo "\n<meta http-equiv=\"Content-Language\" content=\"".str_replace('_','-',$q_config['locale'][$lang])."\" />\n"; //obsolete way 
	foreach($q_config['enabled_languages'] as $language) {
		//if($language != qtranxf_getLanguage())//standard requires them all
		echo '<link hreflang="'.$language.'" href="'.qtranxf_convertURL('',$language,false,true).'" rel="alternate" />'.PHP_EOL;
	}
	//https://support.google.com/webmasters/answer/189077
	echo '<link hreflang="x-default" href="'.qtranxf_convertURL('',$q_config['default_language']).'" rel="alternate" />'.PHP_EOL;

	//qtranxf_add_css();// since 3.2.5 no longer needed
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
	$url = '';
	//options
	$type='LM';//[LM|AL]
	$title='Language';//[none|Language|Current]
	$current=true;//[shown|hidden]
	$flags=true;//[none|all|items]
	$topflag=true;
	$itemsmodified=false;
	foreach($items as $key => $item)
	{
		//qtranxf_dbg_echo('qtranxf_wp_get_nav_menu_items: $item->url: ',$item->url);
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
		if(isset($item->attr_title)) $item->attr_title=qtranxf_use_language($language, $item->attr_title, false, true);
		//qtranxf_dbg_echo('qtranxf_wp_get_nav_menu_items: $item: ',$item);

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
			$item->title.='<img src="'.$flag_location.$q_config['flag'][$toplang].'" alt="'.$q_config['language_name'][$toplang].' '.__('Flag', 'qtranslate').'" />';
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
			$item->title='<img src="'.$flag_location.$q_config['flag'][$lang].'" alt="'.$q_config['language_name'][$lang].' '.__('Flag', 'qtranslate').'" />&nbsp;'.$item->title;
		}
		$item->post_title=$item->title;
		$item->post_name='language-menuitem-'.$lang;
		if($lang!=$language){
			$item->url=qtranxf_convertURL($url, $lang, false, true);
			$item->url=esc_url($item->url);//not sure if this is needed
		}
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

/**
 * Filter all options for language tags
 */
function qtranxf_filter_options(){
	global $q_config, $wpdb;
	$where;
	switch($q_config['filter_options_mode']){
		case QTX_FILTER_OPTIONS_ALL:
			$where=' WHERE autoload=\'yes\' AND (option_value LIKE \'%![:__!]%\' ESCAPE \'!\' OR option_value LIKE \'%<!--:__-->%\')';
			//$alloptions = wp_load_alloptions();
			//foreach($alloptions as $option => $value) {
			//	add_filter('option_'.$option, 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
			//} return;
			break;
		case QTX_FILTER_OPTIONS_LIST:
			if(empty($q_config['filter_options'])) return;
			$where = ' WHERE FALSE';
			foreach($q_config['filter_options'] as $nm){
				$where .= ' OR option_name LIKE "'.$nm.'"';
			}
			break;
		default: return;
	}
	$result = $wpdb->get_results('SELECT option_name FROM '.$wpdb->options.$where);
	if(!$result) return;
	foreach($result as $row) {
		//qtranxf_dbg_log('add_filter: option_'.$row->option_name);
		add_filter('option_'.$row->option_name, 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
	}
}
qtranxf_filter_options();

function qtranxf_postsFilter($posts,&$query) {//WP_Query
	global $q_config;
	//$post->post_content = qtranxf_useCurrentLanguageIfNotFoundShowAvailable($post->post_content);
	//$posts = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($posts);
	if(!is_array($posts)) return $posts;
	//qtranxf_dbg_echo('qtranxf_postsFilter: $post_type: ',$query->query_vars['post_type']);
	switch($query->query_vars['post_type']){
		case 'nav_menu_item': return $posts;//will translate later in qtranxf_wp_get_nav_menu_items: to be able to filter empty labels.
		default: break;
	}
	$lang = $q_config['language'];
	foreach($posts as $post) {//post is an object derived from WP_Post
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
				//other maybe, if it is a string, most likely it never comes here
				default:
					//qtranxf_dbg_echo('qtranxf_postsFilter: other: $post->'.$key.': ',$txt);
					$post->$key = qtranxf_use($lang, $txt, false);
					//if(!is_string($txt)){
					//	//qtranxf_dbg_echo('not string: $post->'.$key.': ',$txt);
					//	continue;
					//}
					//qtranxf_dbg_echo('string: $post->'.$key.': ',$txt);
					//$post->$key = qtranxf_use_language($lang, $txt, false);
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

/**
 * since 3.1-b3 new query to pass empty content and content without closing tags (sliders, galleries and other special kind of posts that never get translated)
 */
function qtranxf_where_clause_translated_posts($lang) {
	global $wpdb;
	return  "($wpdb->posts.post_content='' OR $wpdb->posts.post_content LIKE '%![:".$lang."!]%' ESCAPE '!' OR $wpdb->posts.post_content LIKE '%<!--:".$lang."-->%' OR ($wpdb->posts.post_content NOT LIKE '%![:!]%' ESCAPE '!' AND $wpdb->posts.post_content NOT LIKE '%<!--:-->%'))";
}

function qtranxf_excludePages($pages) {
	global $wpdb;
	static $exclude = 0;
	if(!is_array($exclude)){
		$lang = qtranxf_getLanguage();
		$where = qtranxf_where_clause_translated_posts($lang);
		//$query = "SELECT ID FROM $wpdb->posts WHERE post_type = 'page' AND post_status = 'publish' AND NOT ($wpdb->posts.post_content LIKE '%<!--:".$lang."-->%')";
		$query = "SELECT ID FROM $wpdb->posts WHERE post_type = 'page' AND post_status = 'publish' AND NOT ".$where;
		$hide_pages = $wpdb->get_results($query);
		$exclude = array();
		foreach($hide_pages as $page) {
			$exclude[] = $page->ID;
		}
	}
	return array_merge($exclude, $pages);
}

function qtranxf_excludeUntranslatedPosts($where,&$query) {//WP_Query
	global $wpdb;
	//qtranxf_dbg_echo('qtranxf_excludeUntranslatedPosts: post_type: ',$query->query_vars['post_type']);
	switch($query->query_vars['post_type']){
		//known not to filter
		case 'nav_menu_item':
			return $where;
		//known to filter
		case '':
		case 'any':
		case 'page':
		case 'post':
		default: break;
	}
	//qtranxf_dbg_echo('qtranxf_excludeUntranslatedPosts: post_type is empty: $query: ',$query, true);
	//qtranxf_dbg_echo('qtranxf_excludeUntranslatedPosts: $where: ',$where);
	//qtranxf_dbg_echo('qtranxf_excludeUntranslatedPosts: is_singular(): ',is_singular());
	$single_post_query=$query->is_singular();//since 3.1 instead of top is_singular()
	if($single_post_query){
		$single_post_query = preg_match('/ID\s*=\s*[\'"]*(\d+)[\'"]*/i',$where,$matches)==1;
		//qtranxf_dbg_echo('qtranxf_excludeUntranslatedPosts: $single_post_query: ',$single_post_query);
		//if($single_post_query){
		//	//qtranxf_dbg_echo('qtranxf_excludeUntranslatedPosts: $matches[1]:',$matches[1]);
		//}
	}
	if(!$single_post_query){
		$lang = qtranxf_getLanguage();
		$where .= ' AND '.qtranxf_where_clause_translated_posts($lang);
		//$where .= " AND ($wpdb->posts.post_content='' OR $wpdb->posts.post_content LIKE '%![:".$lang."!]%' ESCAPE '!' OR $wpdb->posts.post_content LIKE '%<!--:".$lang."-->%' OR ($wpdb->posts.post_content NOT LIKE '%![:!]%' ESCAPE '!' AND $wpdb->posts.post_content NOT LIKE '%<!--:-->%'))";
		//$where .= " AND ($wpdb->posts.post_content LIKE '%<!--:".qtranxf_getLanguage()."-->%')";
	}
	return $where;
}

function qtranxf_excludeUntranslatedPostComments($clauses, &$q/*WP_Comment_Query*/) {
	global $wpdb;

	//qtranxf_dbg_log('qtranxf_excludeUntranslatedPostComments: $clauses: ',$clauses);
	//if(!isset($clauses['join'])) return $clauses;
	//if(strpos($clauses['join'],$wpdb->posts) === FALSE) return $clauses;

	if( !isset($clauses['join']) || empty($clauses['join']) ){
		$clauses['join'] = "JOIN $wpdb->posts ON $wpdb->posts.ID = $wpdb->comments.comment_post_ID";
	}elseif(strpos($clauses['join'],$wpdb->posts) === FALSE){
		//do not break some more complex JOIN if it ever happens
		return $clauses;
	}
	//qtranxf_dbg_log('qtranxf_excludeUntranslatedPostComments: $wpdb->posts found');

	$single_post_query=is_singular();
	if($single_post_query){
		$single_post_query = preg_match('/comment_post_ID\s*=\s*[\'"]*(\d+)[\'"]*/i',$clauses['where'])==1;
	}
	if(!$single_post_query){
		$lang = qtranxf_getLanguage();
		$clauses['where'] .= ' AND '.qtranxf_where_clause_translated_posts($lang);
		//$clauses['where'] .= " AND ($wpdb->posts.post_content='' OR $wpdb->posts.post_content LIKE '%![:".$lang."!]%' ESCAPE '!' OR $wpdb->posts.post_content LIKE '%<!--:".$lang."-->%' OR ($wpdb->posts.post_content NOT LIKE '%![:!]%' ESCAPE '!' AND $wpdb->posts.post_content NOT LIKE '%<!--:-->%'))";
	}
	return $clauses;
}

/*
//todo in response to https://github.com/qTranslate-Team/qtranslate-x/issues/17
function qtranxf_add_query_language(&$query) //WP_Comment_Query &$this
{
	global $q_config;
	//$query->query_vars[QTX_COOKIE_NAME_FRONT] = qtranxf_getLanguage();//this does not help, since they cut additional query_vars before generating cache key
	$lang = $q_config['language'];//qtranxf_getLanguage();
	//$query->query_vars['meta_query'] = array( 'key' => 'qtranxf_language_' . $lang, 'compare' => 'NOT EXISTS' );//this cannot be right?
}
if($q_config['hide_untranslated']){
	add_action( 'pre_get_comments', 'qtranxf_add_query_language' );
}
*/

//function qtranxf_get_attachment_image_attributes($attr, $attachment, $size)
function qtranxf_get_attachment_image_attributes($attr, $attachment=null, $size=null)
{
	global $q_config;
	$lang = $q_config['language'];
	//qtranxf_dbg_echo('qtranxf_get_attachment_image_attributes: $attachment:',$attachment);
	if(isset($attr['alt'])){
		$attr['alt']=qtranxf_use_language($lang,$attr['alt'],false,false);
	}
	//foreach( $attr as $name => $value ){
		//qtranxf_dbg_echo('qtranxf_get_attachment_image_attributes: $name='.$name.'; value='.$value);
		//if($name!=='alt') continue;
		//$attr[$name]=qtranxf_use_language($lang,$value,false,false);
		////$attr[$name]=qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($value);
	//}
	return $attr;
}
add_filter('wp_get_attachment_image_attributes', 'qtranxf_get_attachment_image_attributes',5,3);

/*
function qtranxf_get_attachment_link( $link, $id=null, $size=null, $permalink=null, $icon=null, $text=null )
{
	global $q_config;
	$lang = $q_config['language'];
	return qtranxf_use_language($lang,$link,false,true);
}
add_filter( 'wp_get_attachment_link', 'qtranxf_get_attachment_link', 5, 6);
*/

function qtranxf_home_url($url, $path, $orig_scheme, $blog_id)
{
	global $q_config;
	$lang = $q_config['language'];
	//qtranxf_dbg_log('qtranxf_home_url: url='.$url.'; path='.$path.'; orig_scheme='.$orig_scheme);
	$url = qtranxf_get_url_for_language($url, $lang, !$q_config['hide_default_language'] || $lang != $q_config['default_language']);
	//qtranxf_dbg_log('qtranxf_home_url: url='.$url.'; lang='.$lang);
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
	//qtranxf_dbg_echo('qtranxf_esc_html:text=',$text,true);
	//return qtranxf_useDefaultLanguage($text);//this does not make sense, does it? - original code
	//return qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($text);
	/**
	 * since 3.1-b1
	 * used to return qtranxf_useDefaultLanguage($text)
	*/
	return qtranxf_useCurrentLanguageIfNotFoundShowEmpty($text);
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

/**
 * @since 3.2.3 translation of postmeta
 */
function qtranxf_filter_postmeta($original_value, $object_id, $meta_key = '', $single = false){
	global $q_config;
	if(!isset($q_config['url_info'])){
		//qtranxf_dbg_log('qtranxf_filter_postmeta: too early: $object_id='.$object_id.'; $meta_key',$meta_key,true);
		return $original_value;
	}

	$meta_type = 'post';
	$lang = $q_config['language'];
	$cache_key = $meta_type . '_meta';
	$cache_key_lang = $cache_key . $lang;
	$meta_cache = wp_cache_get( $object_id, $cache_key_lang );
	if( !$meta_cache ){
		$meta_cache = wp_cache_get($object_id, $cache_key);
		if ( !$meta_cache ) {
			$meta_cache = update_meta_cache( $meta_type, array( $object_id ) );
			$meta_cache = $meta_cache[$object_id];
		}

		//qtranxf_dbg_log('qtranxf_filter_postmeta: $object_id='.$object_id.'; $meta_cache before:',$meta_cache);
		foreach($meta_cache as $mkey => $mval){
			if(strpos($mkey,'_url') !== false){
				$val = array_map('maybe_unserialize', $mval);
				switch($mkey){
					case '_menu_item_url': break; // function qtranxf_wp_get_nav_menu_items takes care of this later
					default:
						//qtranxf_dbg_log('qtranxf_filter_postmeta: $object_id='.$object_id.'; $meta_cache['.$mkey.'] url before:',$val);
						$val = qtranxf_convertURLs($val,$lang);
						//qtranxf_dbg_log('qtranxf_filter_postmeta: $object_id='.$object_id.'; $meta_cache['.$mkey.'] url  after:',$val);
					break;
				}
			}else{
				$val = array();
				foreach($mval as $k => $v){
					$ml = qtranxf_isMultilingual($v);
					$v = maybe_unserialize($v);
					if($ml) $v = qtranxf_use($lang, $v, false, false);
					$val[$k] = $v;
				}
			}
			$meta_cache[$mkey] = $val;
		}
		//qtranxf_dbg_log('qtranxf_filter_postmeta: $object_id='.$object_id.'; $meta_cache  after:',$meta_cache);

		wp_cache_add( $object_id, $meta_cache, $cache_key_lang );
	}

	if(!$meta_key)
		return $meta_cache;

	if(isset($meta_cache[$meta_key]))
		return $meta_cache[$meta_key];

	if ($single)
		return '';
	else
		return array();
}
add_filter('get_post_metadata', 'qtranxf_filter_postmeta', 5, 4);

function qtranxf_checkCanonical($redirect_url, $requested_url) {
	global $q_config;
	//if(!qtranxf_can_redirect()) return $redirect_url;// WP already check this
	$lang = $q_config['language'];
	// fix canonical conflicts with language urls
	$redirect_url_lang = qtranxf_convertURL($redirect_url,$lang);
	//$requested_url_lang = qtranxf_convertURL($requested_url,$lang);
	//qtranxf_dbg_log('qtranxf_checkCanonical: requested vs redirect vs redirect_lang:' . PHP_EOL . $requested_url . PHP_EOL . $redirect_url. PHP_EOL . $redirect_url_lang);
	//qtranxf_dbg_log('qtranxf_checkCanonical: redirect vs requested:' . PHP_EOL . $redirect_url . PHP_EOL . $requested_url. PHP_EOL . 'redirect_lang vs requested_lang:' . PHP_EOL . $redirect_url_lang . PHP_EOL . $requested_url_lang, 'novar');//. PHP_EOL . '$q_config[url_info]: ', $q_config['url_info']);
	//if(qtranxf_convertURL($redirect_url)==qtranxf_convertURL($requested_url))
	//if($redirect_url_lang==$requested_url_lang) return false; //WP calls this only if $redirect_url != $requested_url, we only need to make sure to return language encoded url
	return $redirect_url_lang;
}
add_filter('redirect_canonical', 'qtranxf_checkCanonical', 10, 2);

/**
 * @since 3.2.8 moved here from _hooks.php
 */
function qtranxf_convertBlogInfoURL($url, $what) {
	switch($what){
		case 'stylesheet_url':
		case 'template_url':
		case 'template_directory':
		case 'stylesheet_directory':
			return $url;
		default: return qtranxf_convertURL($url);
	}
}
add_filter('bloginfo_url', 'qtranxf_convertBlogInfoURL',10,2);

//qtranxf_optionFilter();
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

/**
 * @since 3.2
 * wp-includes\category-template.php:1230 calls:
 * $description = get_term_field( 'description', $term, $taxonomy );
 *
 * which calls wp-includes\taxonomy.php:1503
 * return sanitize_term_field($field, $term->$field, $term->term_id, $taxonomy, $context);
 *
 * which calls wp-includes\taxonomy.php:2276:
 * apply_filters( "term_{$field}", $value, $term_id, $taxonomy, $context );
*/
add_filter('term_description', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
