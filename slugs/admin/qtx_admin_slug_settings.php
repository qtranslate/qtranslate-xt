<?php
if ( !defined( 'WP_ADMIN' ) ) exit;

require_once(QTXSLUGS_DIR.'/admin/qtx_admin_slug.php');

{// configuration

add_filter('qtranslate_admin_sections','qtranxf_slug_admin_sections');
function qtranxf_slug_admin_sections($sections){
	global $q_config;
	$sections['slugs'] = __('Slugs', 'qtranslate');
	if(isset($q_config['slugs']) && $q_config['slugs']){
		//todo add_action('qtranslate_url_mode_choices','qtranxf_slug_url_mode_choices', 10, 2);
	}
	return $sections;
}

function qtranxf_slug_url_mode_choices($permalink_is_query,$url_mode){
?>
<label title="Slug Mode"><input type="radio" name="url_mode" value="<?php echo QTX_URL_SLUG; ?>" <?php checked($url_mode,QTX_URL_SLUG); disabled($permalink_is_query) ?> /> <?php echo __('Use Slug Mode.', 'qtranslate').' '.__('SEO friendly.', 'qtranslate') ?></label>
<p class="qtranxs_notes"><?php _e('Whenever it is possible, the language is determined by a localized URL slug, otherwise Pre-Path method is used.', 'qtranslate') ?></p><br/>
<?php
}

add_action('qtranslate_configuration', 'qtranxf_slug_config');
function qtranxf_slug_config($request_uri) {
	global $q_config;
	$nopermalinks = qtranxf_is_permalink_structure_query();
	qtranxf_admin_section_start('slugs');
?>
<table class="form-table" id="qtranxf_slug_config">
<?php if($nopermalinks){ ?>
	<tr>
		<th scope="row" colspan="2"><?php printf(__('You have to use pretty %spermalinks%s in order to take advantage of multilingual slugs.', 'qtranslate'), '<a href="'.admin_url('options-permalink.php').'">', '</a>') ?></th>
	</tr>
<?php }else{ ?>
	<tr>
		<th scope="row"><?php _e('Translate Slugs', 'qtranslate') ?></th>
		<td><input type="checkbox" name="slugs" id="qtranxs_slugs"<?php checked(!empty($q_config['slugs'])) ?>  value="1"><label for="qtranxs_slugs" class="qtranxs_explanation"><?php printf(__('Enable multilingual URL slugs for posts, pages, categories, post types, tags, etc.', 'qtranslate')) ?></label>
		<p class="qtranxs_notes"><?php printf(__('Make sure to deactivate all other 3rd-party slug services. You may need to %simport slug data%s from other slug-plugins upon activation this service.', 'qtranslate'), '<a href="'.$request_uri.'#import">', '</a>').' '.printf(__('It is important to read %sLocalized Slugs Guide%s to prevent other possible confusions.', 'qtranslate'), '<a href="https://qtranslatexteam.wordpress.com/slugs/" target="_blank">', '</a>') ?></p>
		</td>
	</tr>
<?php
	if(!empty($q_config['slugs'])){
?>
	<tr>
		<td colspan="2"><p class="qtranxs_explanation"><?php echo __('If you change the default language value in one of the fields below, you may need to go back to the appropriate 3rd-party configuration page and make the same change there. It may also not be possible to make the same change using their tool, since additional restrictions may apply. That is why, it is advisable to tune all the default language values first, using corresponding native tools, before entering their translations to other languages on this page.', 'qtranslate').' '.__('Be aware that not all browsers support non-latin alphabets in the address field, and therefore such slugs may appear url-encoded, which is probably not what you would wish. Please, test the appearance of non-latin slugs before going too far with them, you might change your mind.', 'qtranslate').' '.sprintf(__('It is important to read %sLocalized Slugs Guide%s to prevent other possible confusions.', 'qtranslate'), '<a href="https://qtranslatexteam.wordpress.com/slugs/" target="_blank">', '</a>') ?></p>
		</td>
	</tr>
	<tr><td colspan="2" id="qtranxs_slug_lsb_top"></td></tr>
<?php
	$objects = get_taxonomies( array( 'public' => true, 'show_ui' => true, '_builtin' => true ), 'object' ); 
	$objects = qtranxf_slug_admin_filter_types($objects);
	if(!empty($objects)){
?>
	<tr>
		<th scope="row" class="qtranxs_option_group"><?php _e('Built-in Taxonomies', 'qtranslate') ?></th>
		<td><p class="qtranxs_explanation"><?php printf(__('Multilingual slug base for WordPress built-in taxonomies. The default language value is defined by %sWordPress%s on admin page %s.', 'qtranslate'), '<a href="https://codex.wordpress.org/Taxonomies" target="_blank">', '</a>', '<a href="'.admin_url('options-permalink.php').'" target="_blank">'.qtranxf_translate_wp('Settings').'/'.qtranxf_translate_wp('Permalinks').'</a>') ?></p>
		</td>
	</tr>
<?php
		//qtranxf_slug_admin_field('taxonomy', qtranxf_translate_wp('Category base'), 'taxonomy_category');
		//qtranxf_slug_admin_field('taxonomy', qtranxf_translate_wp('Tag base'), 'taxonomy_post_tag');
		qtranxf_slug_admin_fields($objects,'taxonomy_builtin');
	}

	//global $wp_taxonomies; $objects = $wp_taxonomies;
	$objects = get_taxonomies( array( 'public' => true, 'show_ui' => true, '_builtin' => false ), 'object' );
	$objects = qtranxf_slug_admin_filter_types($objects);
	if(!empty($objects)){
?>
	<tr>
		<th scope="row" class="qtranxs_option_group"><?php _e('Custom Taxonomies', 'qtranslate') ?></th>
		<td><p class="qtranxs_explanation"><?php echo __('Multilingual slug base for custom taxonomies.', 'qtranslate').' '; printf(__('The default language value is as provided in the argument %s of a call to function %s made by a 3rd-party, which created the term.', 'qtranslate'), '<code>$args[\'rewrite\'][\'slug\']</code>', '<a href="http://codex.wordpress.org/Function_Reference/register_taxonomy" target="_blank"><code>register_taxonomy</code></a>') ?></p>
		</td>
	</tr>
<?php
		qtranxf_slug_admin_fields($objects,'taxonomy');
	}

	//global $wp_post_types; $objects = $wp_post_types;
	$objects = get_post_types( array('_builtin' => true, 'public' => true ), 'objects');
	$objects = qtranxf_slug_admin_filter_types($objects);
	if(!empty($objects)){
?>
	<tr>
		<th scope="row" class="qtranxs_option_group"><?php _e('Built-in Post Types', 'qtranslate') ?></th>
		<td><p class="qtranxs_explanation"><?php printf(__('Multilingual slug base for WordPress built-in post types. The default value is defined by %sWordPress%s.', 'qtranslate'), '<a href="https://codex.wordpress.org/Post_Types" target="_blank">', '</a>') ?></p>
		</td>
	</tr>
<?php
		qtranxf_slug_admin_fields($objects,'post_type_builtin');
	}

	//$objects = get_post_types( array('publicly_queryable' => true) );
	$objects = get_post_types( array('_builtin' => false, 'public' => true ), 'objects');
	$objects = qtranxf_slug_admin_filter_types($objects);
	if(!empty($objects)){
?>
	<tr>
		<th scope="row" class="qtranxs_option_group"><?php _e('Custom Post Types', 'qtranslate') ?></th>
		<td><p class="qtranxs_explanation"><?php echo __('Multilingual slug base for custom post types.', 'qtranslate').' '; printf(__('The default language value is as provided in the argument %s of a call to function %s made by a 3rd-party, which created the term.', 'qtranslate'), '<code>$args[\'rewrite\'][\'slug\']</code>', '<a href="http://codex.wordpress.org/Function_Reference/register_post_type" target="_blank"><code>register_post_type</code></a>') ?></p>
		</td>
	</tr>
<?php
		qtranxf_slug_admin_fields($objects,'post_type');
	}
?>
	<tr><td colspan="2" id="qtranxf_slug_lsb_bottom"></td></tr>
<?php
	}//if(!empty($q_config['slugs']))
	}//if(pretty permalinks)
?>
</table>
<?php 
	qtranxf_admin_section_end('slugs', $nopermalinks?null:'');
}

function qtranxf_slug_admin_filter_types($objects){
	foreach($objects as $k => $o){
		if(isset($o->rewrite['slug'])) continue;
		unset($objects[$k]);
	}
	return $objects;
}

function qtranxf_slug_admin_fields($objects, $type){
	$group = 'slugs_'.$type;
	foreach($objects as $key => $o){
		//qtranxf_dbg_log('qtranxf_slug_admin_fields: term '.$group.'['.$key.']: ',$o);
		$label = $o->labels->singular_name;
		//$label = apply_filters('translate_text', $label);
		//$key = $o->name;//the same
		//if(!isset($o->rewrite['slug'])) continue;//already fitered
		$slug = $o->rewrite['slug'];
		qtranxf_dbg_log('qtranxf_slug_admin_fields: term: '.$group.'['.$key.']: slug: ',$slug);
		if(!empty($q_config['slugs_opt']['mv']['terms'][$group]['values'][$key])){
			$value_org = $q_config['slugs_opt']['mv']['terms'][$group]['values'][$key]['value_org'];
			$value_new = $q_config['slugs_opt']['mv']['terms'][$group]['values'][$key]['value_new'];
			if($slug == $value_new){
				unset($q_config['slugs_opt']['mv']['terms'][$group]['values'][$key]);
				if(empty($q_config['slugs_opt']['mv']['terms'][$group]['values'])) unset($q_config['slugs_opt']['mv']['terms'][$group]);
				if(empty($q_config['slugs_opt']['mv']['terms'])) unset($q_config['slugs_opt']['mv']['terms']);
				if(empty($q_config['slugs_opt']['mv'])) unset($q_config['slugs_opt']['mv']);
				if(empty($q_config['slugs_opt'])) delete_option('qtranslate_slugs_opt');
				else update_option('qtranslate_slugs_opt', $q_config['slugs_opt']);
			}else{
				if($slug != $value_org){
					$q_config['slugs_opt']['mv']['terms'][$group]['values'][$key]['value_org'] = $slug;
					update_option('qtranslate_slugs_opt',$q_config['slugs_opt']);
				}
				$slug = $value_new;
			}
		}
		qtranxf_slug_admin_field($type, $label, $key, $slug);
	}
}

function qtranxf_slug_admin_field($type, $label, $key, $slug){
	global $q_config;
	$name = 'slugs_'.$type.'['.$key.']';
	$val = qtranxf_slug_multilingual_base($slug);
	$id = 'qtranxs_'.$type.'_'.$key;
?>
	<tr>
		<th scope="row" style="text-align: right"><label for="<?php echo $id ?>" class="i18n-multilingual-display"><?php echo $label ?></label></th>
		<td><input type="text" name="<?php echo $name ?>" id="<?php echo $id ?>" value="<?php echo $val ?>" class="i18n-multilingual-slug qtranxs-slug-<?php echo $type ?> widefat"></td>
	</tr>
<?php
}
}// configuration

{// options update
//function qtranxf_slug_sanitize_base($s){ return preg_replace('#/+#', '/', '/' . str_replace( '#', '', $s ) ); }

//function qtranxf_slug_is_permastruct($s){ return preg_match('#[^a-z0-9_\-]#',$s); }
function qtranxf_slug_is_permastruct($s){ return strpos($s,'/') !== false; }

function qtranxf_slug_sanitize($s, &$is_permastruct){
	$is_permastruct = qtranxf_slug_is_permastruct($s);
	$s = str_replace( '#', '', $s );
	if($is_permastruct){
		if($s[0] != '/') $s = '/' . $s;
		$s = preg_replace('#/{2,}#', '/', $s );
	}
	return remove_accents($s);
}

/**
 * @param (string) $s - decoded string
 * return array - ('blocks' => [all blocks between '/'], 'slugs' => [decoded slugs])
 */
function qtranxf_slug_split_permastruct($s){
	$blocks = explode('/',$s);
	$info = array('blocks' => $blocks, 'slugs' => array());
	$slugs = &$info['slugs'];
	if(count($blocks) == 1){
		$slugs[] = $blocks[0];
	}else{
		foreach($blocks as $k => $v){
			if(empty($v)) continue;
			if(qtranxf_slug_is_substitution($v)) continue;
			$slugs[] = $v;
		}
	}
	return $info;
}

function qtranxf_slug_del_translations_permastruct($s){
	$info = qtranxf_slug_split_permastruct($s);
	//qtranxf_dbg_log('qtranxf_slug_del_translations_permastruct: $info: ', $info);
	foreach($info['slugs'] as $name){
		qtranxf_slug_del_translations($name);
	}
}

function qtranxf_slug_update_translations_of($group, $group_name, $default_lang, &$wp_group){
	global $q_config;
	foreach($_POST['qtranslate-slugs'][$group] as $key => &$qfields){
		if(!isset($wp_group[$key])){
			qtranxf_add_error(sprintf(__('Translations of "%s" cannot be updated for unknown term "%s".', 'qtranslate'), $group_name, $key));
			continue;
		}
		$is_permastruct;
		$value_new = qtranxf_slug_sanitize($qfields[$default_lang],$is_permastruct);
		//qtranxf_dbg_log('qtranxf_slug_update_translations_of: $value_new: ', $value_new);
		if(isset($qfields['qtranslate-original-value'])){
			$value_org = $qfields['qtranslate-original-value'];
			if($value_org != $value_new){
				//qtranxf_dbg_log('qtranxf_slug_update_translations_of: $value_org: ', $value_org);
				if(!isset($q_config['slugs_opt']['mv'])) $q_config['slugs_opt']['mv'] = array();
				if(!isset($q_config['slugs_opt']['mv']['terms'])) $q_config['slugs_opt']['mv']['terms'] = array();
				if(!isset($q_config['slugs_opt']['mv']['terms'][$group])) $q_config['slugs_opt']['mv']['terms'][$group] = array( 'group_name' => $group_name, 'values' => array());
				if(!isset($q_config['slugs_opt']['mv']['terms'][$group]['values'][$key])){
					$q_config['slugs_opt']['mv']['terms'][$group]['values'][$key] = array();
					$q_config['slugs_opt']['mv']['terms'][$group]['values'][$key]['value_org'] = $value_org;
				}
				$q_config['slugs_opt']['mv']['terms'][$group]['values'][$key]['value_new'] = $value_new;
			}
			qtranxf_slug_del_translations_permastruct($value_org);
			$qfields = qtranxf_slug_replace($value_org, $value_new, $qfields);
		}
		$info_def = qtranxf_slug_split_permastruct($value_new);
		//qtranxf_dbg_log('qtranxf_slug_update_translations_of: $info_def: ', $info_def);
		$cnt = count($info_def['slugs']);
		//qtranxf_dbg_log('qtranxf_slug_update_translations_of: $cnt: ', $cnt);
		if($is_permastruct){
			$names = array();
			foreach($info_def['slugs'] as $name) $names[$name] = array();
			foreach($qfields as $lng => $val){
				if(empty($val)) continue; //default will be used
				$info_lng = qtranxf_slug_split_permastruct($val);
				if(count($info_lng['slugs']) != $cnt){
					qtranxf_add_error(sprintf(__('Translation of term "%s" for language code "%s", "%s" -> "%s", is inconsistent. Translation has to have matching structire. Please, correct it and try saving the configuration again.', 'qtranslate'), $group_name, $lng, $value_new, $val));
					continue;
				}
				foreach($info_lng['slugs'] as $k => $s){
					$name = $info_def['slugs'][$k];
					$names[$name][$lng] = $s;
				}
			}
			//qtranxf_dbg_log('qtranxf_slug_update_translations_of: $names: ', $names);
			foreach($names as $name => $slugs){
				qtranxf_slug_update_translations($name, $slugs, $default_lang);
			}
		}else{
			//qtranxf_dbg_log('qtranxf_slug_update_translations_of: ok $value_new: ', $value_new);
			qtranxf_slug_update_translations($value_new, $qfields, $default_lang);
		}
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
	//qtranxf_add_warning(sprintf(__('The slug for %s "%s" for default language cannot be changed to "%s" on this page, because it is not known here which tool created it and for what purpose. Please, update this slug on the page where it is originated from. It may be required then to come back here to update the translations, unless the other plugin or theme is %sintegrated%s with %s.', 'qtranslate'), $group_name, $name_old, $name, '<a href="https://qtranslatexteam.wordpress.com/integration/" target="_blank">', '</a>', 'qTranslate&#8209;X'));
*/
	qtranxf_slug_clean_request($group);
}

function qtranxf_slug_replace($value_org, $value_new, $slugs){
	foreach($slugs as $k => $v){
		if($v == $value_org) $slugs[$k] = $value_new;
	}
	qtranxf_slug_replace_rewrite_structs($value_org, $value_new);
	return $slugs;
}

function qtranxf_slug_default_base_value($key, $default_name, &$qpost, $blog_prefix, $default_lang){
	// mimic code from /wp-admin/options-permalink.php
	//preg_replace('#/+#', '/', '/' . str_replace( '#', '', $s ) );
	$value_new = untrailingslashit($qpost[$key][$default_lang]);
	$value_new = preg_replace('#/{2,}#', '/', str_replace( '#', '', $value_new ) );
	$value_org = $qpost[$key]['qtranslate-original-value'];
	if($value_org != $value_new){
		qtranxf_slug_del_translations_permastruct($value_org);
		$qpost[$key] = qtranxf_slug_replace($value_org, $value_new, $qpost[$key]);
		global $wp_taxonomies;
		if(isset($wp_taxonomies[$key]) && isset($wp_taxonomies[$key]->rewrite['slug'])){
			//$wp_taxonomies[$key]->rewrite['slug'] = qtranxf_slug_encode($value_new);
			$wp_taxonomies[$key]->rewrite['slug'] = $value_new;
		}
	}
	unset($qpost[$key]['qtranslate-original-value']);
	if($value_new == $default_name) return '';
	if($value_new[0] != '/') $value_new = '/' . $value_new;
	return $blog_prefix . $value_new;
}

function qtranxf_slug_update_settings(){
	global $wp_rewrite;
	//qtranxf_dbg_log('4.qtranxf_slug_update_settings: $_POST[qtranslate-slugs]: ', $_POST['qtranslate-slugs']);
	if(!isset($_POST['qtranslate-slugs'])) return;
	global $wp_taxonomies, $wp_post_types;
	$default_lang = qtranxf_getLanguageDefault();
	if(isset($_POST['slugs_taxonomy_builtin'])){
		// mimic code from /wp-admin/options-permalink.php
		$blog_prefix = is_multisite() && !is_subdomain_install() && is_main_site() ? '/blog' : '';// /wp-admin/options-permalink.php:78
		if(isset($_POST['slugs_taxonomy_builtin']['category'])){
			$slug_base = qtranxf_slug_default_base_value('category', 'category', $_POST['qtranslate-slugs']['slugs_taxonomy_builtin'], $blog_prefix, $default_lang);
			$wp_rewrite->set_category_base( $slug_base );
		}
		if(isset($_POST['slugs_taxonomy_builtin']['post_tag'])){
			$slug_base = qtranxf_slug_default_base_value('post_tag', 'tag', $_POST['qtranslate-slugs']['slugs_taxonomy_builtin'], $blog_prefix, $default_lang);
			$wp_rewrite->set_tag_base( $slug_base );
		}
		qtranxf_slug_update_translations_of('slugs_taxonomy_builtin', '<code>taxonomy</code>', $default_lang, $wp_taxonomies);
		//qtranxf_dbg_log('qtranxf_slug_update_settings: $wp_rewrite: ', $wp_rewrite);
	}
	if(isset($_POST['slugs_taxonomy'])){
		qtranxf_slug_update_translations_of('slugs_taxonomy', '<code>taxonomy</code>', $default_lang, $wp_taxonomies);
	}
	if(isset($_POST['slugs_post_type'])){
		qtranxf_slug_update_translations_of('slugs_post_type', '<code>post_type</code>', $default_lang, $wp_post_types);
	}
	$wp_rewrite->flush_rules(false);
}

function qtranxf_slug_replace_rewrite_structs($slugs_org, $slugs_new){
	global $wp_rewrite;
	if(empty($wp_rewrite->extra_permastructs)) return;
	if(!is_array($slugs_org)){
		$info_org = qtranxf_slug_split_permastruct($slugs_org);
		$info_new = qtranxf_slug_split_permastruct($slugs_new);
		qtranxf_slug_replace_rewrite_structs($info_org['slugs'], $info_new['slugs']);//recursive call
		return;
	}
	if(empty($slugs_org)) return;
	if(count($slugs_org) != count($slugs_new)) return;
	foreach($wp_rewrite->extra_permastructs as $k => $v){
		if(empty($v['struct'])) continue;
		reset($slugs_new);
		foreach($slugs_org as $value_org){
			$value_new = next($slugs_new);
			$wp_rewrite->extra_permastructs[$k]['struct'] = preg_replace('#(?:[^a-z0-9_\-%]|^)'.$value_org.'(?:[^a-z0-9_\-%]|$)#',$value_new,$v['struct']);
		}
	}
}
}// options update

{// Import/Export
/* add_filter('qtranslate_get_plugin_name', 'qtranxf_slug_get_plugin_name', 10, 2);
function qtranxf_slug_get_plugin_name($nm, $slug){
	switch($slug){
		case 'qtranslate-slug': return 'QTranslate Slug';
		default: return $nm;
	}
} */

add_action('qtranslate_add_row_migrate','qtranxf_slug_add_row_migrate');
function qtranxf_slug_add_row_migrate(){
	qtranxf_add_row_migrate('Qtranslate Slug', 'qtranslate-slug', array('no_export' => true, 'note' => __('These options get auto-migrated on activation of plugin if applicable. Migration utilities are provided here for the sake of completeness.','qtranslate')));
}

function qtranxf_migrate_import_qtranslate_slug($default_language){
	//$q_config is not yet available
	global $wpdb;
	//qtranxf_dbg_log('qtranxf_migrate_import_qtranslate_slug: ');

	$nm = '<a href="https://wordpress.org/plugins/qtranslate-slug/" target="_blank"><span style="color:blue"><strong>QTranslate Slug</strong></span></a>';

	$wpdb->show_errors(); @set_time_limit(0);

	$qts_options = get_option('qts_options');
	if(!empty($qts_options)){
		qtranxf_dbg_log('qtranxf_migrate_import_qtranslate_slug: $qts_options: ', $qts_options);
		if(is_array($qts_options)){
			foreach($qts_options as $k => $v){
			}
		}else{
			qtranxf_error_log(sprintf(__('Failed to import options from plugin %s.', 'qtranslate'), $nm));
		}
	}

	$sql = 'SELECT ID, post_name, post_type, post_parent, meta_key, meta_value FROM '.$wpdb->postmeta.' as m INNER JOIN '.$wpdb->posts.' as p ON p.ID = m.post_id WHERE p.post_status = "publish" AND p.post_name != "" AND m.meta_key LIKE "_qts_slug___" AND m.meta_value IS NOT NULL';
	$sql .= ' AND NOT EXISTS (SELECT * FROM '.$wpdb->prefix.'i18n_slugs WHERE name = p.post_name AND lang = MID(m.meta_key,11))';

	$result = $wpdb->get_results($sql);
	//qtranxf_dbg_log('qtranxf_migrate_import_qtranslate_slug: $result: ', $result);
	//qtranxf_dbg_log('qtranxf_migrate_import_qtranslate_slug: count($result): ', count($result));

	if(!is_array($result)){
		qtranxf_error_log(sprintf(__('Failed to import data from plugin %s.', 'qtranslate'), $nm).' '.sprintf(__('It might be a good idea to review %smigration instructions%s, if you have not yet done so.', 'qtranslate'), '<a href="https://qtranslatexteam.wordpress.com/migration/" target="_blank">', '</a>'));
		return;
	}
	foreach($result as $row) {
		$name = $row->post_name;
		if(empty($name)) continue;
		$slug = $row->meta_value;
		if(empty($slug)) continue;
		$lang = substr($row->meta_key,-2);
		if($lang != $default_language) continue;
		if($slug == $name) continue;
		qtranxf_dbg_log('qtranxf_migrate_import_qtranslate_slug: default language $slug('.$slug.') != $name('.$name.')');
		$slug = wp_unique_post_slug( $slug, $row->ID, 'publish', $row->post_type, $row->post_parent );
		$wpdb->query($wpdb->prepare('UPDATE '.$wpdb->posts.' SET post_name = %s WHERE ID = %d', $slug, $row->ID));
		$row->post_name = $row->meta_value = $slug;
	}
	$cnt = 0;
	$cnt_renamed = 0;
	$lst = '';
	$sql = 'INSERT INTO '.$wpdb->prefix.'i18n_slugs (slug, lang, name) VALUES (%s, %s, %s)';
	foreach($result as $row) {
		$lang = substr($row->meta_key,-2);
		if($lang == $default_language) continue;
		$name = $row->post_name;
		if(empty($name)) continue;
		$slug = $row->meta_value;
		if(empty($slug)) continue;
		if(!qtranxf_isEnabled($lang)) continue;
		$slug = qtranxf_slug_unique($row->meta_value,$lang,$name);
		if($slug != $row->meta_value){
			$lst .= $row->ID.'\t'.$name.'\t'.$lang.'\t'.$row->meta_value.' => '.$slug.PHP_EOL;
			++$cnt_renamed;
		}
		//qtranxf_dbg_log('qtranxf_migrate_import_qtranslate_slug: $lang='.$lang.'; row: ', $row);
		$query = $wpdb->prepare($sql, $slug, $lang, $row->post_name );
		$wpdb->query($query);
		++$cnt;
	}
	$msg = '';
	if($cnt != 0){
		$msg .= sprintf(__('Applicable options and slug data from plugin %s have been imported.', 'qtranslate'), $nm);
	}
	//if(!empty($lst))){
		$upload_dir = wp_upload_dir();
		qtranxf_dbg_log('qtranxf_migrate_import_qtranslate_slug: upload_dir: ',$upload_dir);
		$fnm = '/qts-qtx-report-of-renamed.log';
		$fn = $upload_dir['basedir'].$fnm;
		//[01-Nov-2015 02:21:57 UTC]
		error_log(date('[d-M-Y H:i:s T]').': Slugs renamed during import:'.PHP_EOL ."Post_ID\tpost_name\tLANG\tQtranslate Slug => qTranslate-X Slug".PHP_EOL .$lst.PHP_EOL,3,$fn);
		$url = $upload_dir['baseurl'].$fnm;
		$msg .= ' '.sprintf(__('A number of post slugs have had to be renamed in order to provide uniqueness. The report of renamed slugs is saved in the file "%s".', 'qtranslate'), '<a href="'.$url.' target="_blank">'.$url.'</a>');
	//}
	if(!empty($msg)){
		$msg .= ' '.sprintf(__('It might be a good idea to review %smigration instructions%s, if you have not yet done so.', 'qtranslate'),'<a href="https://qtranslatexteam.wordpress.com/migration/" target="_blank">','</a>');
		qtranxf_add_message($msg);
	}
}

/*
function qtranxf_migrate_export_qtranslate_slug(){
	//qtranxf_dbg_log('qtranxf_migrate_export_qtranslate_slug: ');
	$nm = '<a href="https://wordpress.org/plugins/qtranslate-slug/" target="_blank"><span style="color:blue"><strong>QTranslate Slug</strong></span></a>';
	qtranxf_add_message(sprintf(__('Applicable options and slug data have been exported to plugin %s.', 'qtranslate'), $nm));
}
*/
}// Import/Export
