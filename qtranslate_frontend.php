<?php
add_filter( 'wp_get_nav_menu_items',  'qtranxf_get_nav_menu_items', 0, 3 );
function qtranxf_get_nav_menu_items( $items, $menu, $args )
{
	global $q_config;
	$language=$q_config['language'];
	$itemid=0;
	$menu_order=0;
  $qtransmenu=null;
	foreach($items as $item)
	{
	  if($itemid<$item->ID) $itemid=$item->ID;
	  if($menu_order<$item->menu_order) $menu_order=$item->menu_order;
		if( !isset( $item->url ) || strstr( $item->url, '#qtransLangSw' ) === FALSE ) continue;
		$item->title=__('Language','qtranslate').':';
		$item->url=null;
		$item->classes[] = 'qtranxs-flag-'.$language;
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
		$item->post_title=$q_config['language_name'][$lang];
		$item->title=$item->post_title;
		$item->post_name='language-menuitem-'.$lang;
		if($lang!=$language)
			$item->url=qtranxf_convertURL($url, $lang, false, true);
		$item->classes=array();
		$item->classes[] = 'qtranxs-flag-'.$lang;
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
?>