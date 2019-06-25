<?php
if ( !defined( 'WP_ADMIN' ) ) exit;

require_once(QTXSLUGS_DIR.'/admin/qtx_admin_slug.php');

{// configuration

function qtranxf_slug_config($request_uri) {
	global $q_config;
	qtranxf_admin_section_start('slugs');
?>
<table class="form-table qtranxs-form-table" id="qtranxs_slug_config">
	<tr>
		<th scope="row"><?php _e('Translate Slugs', 'qtranslate') ?></th>
		<td><input type="checkbox" name="slugs" id="qtranxs_slugs"<?php checked(!empty($q_config['slugs'])) ?>  value="1" /><label for="qtranxs_slugs" class="qtranxs_explanation"><?php printf(__('Enable multilingual URL slugs for posts, pages, categories, post types, tags, etc.', 'qtranslate')) ?></label>
		<p class="qtranxs-notes"><?php printf(__('Make sure to deactivate all other 3rd-party slug services. You may need to %simport slug data%s from other slug-plugins upon activation this service. Please, read %sMigration Guide%s for more information.', 'qtranslate'), '<a href="'.$request_uri.'#import">', '</a>', '<a href="https://github.com/qtranslate/qtranslate-xt/wiki/Migration-Guide" target="_blank">', '</a>') ?></p>
		</td>
	</tr>
	<tr><td colspan="2" id="qtranxf_slug_lsb_top"></td></tr>
<?php
	if(!empty($q_config['slugs'])){

	$objects = get_taxonomies( array( 'public' => true, 'show_ui' => true, '_builtin' => true ), 'object' ); 
	if(!empty($objects)){
?>
	<tr>
		<th scope="row" class="qtranxs-option-group"><?php _e('Built-in Taxonomies', 'qtranslate') ?></th>
		<td><p class="qtranxs_explanation"><?php printf(__('Multilingual slug for WordPress built-in taxonomies. The default value is defined by %sWordPress%s.', 'qtranslate'), '<a href="https://codex.wordpress.org/Taxonomies" target="_blank">', '</a>') ?></p>
		</td>
	</tr>
<?php
		//qtranxf_slug_admin_field('taxonomy', qtranxf_translate_wp('Category base'), 'taxonomy_category');
		//qtranxf_slug_admin_field('taxonomy', qtranxf_translate_wp('Tag base'), 'taxonomy_post_tag');
		qtranxf_slug_admin_fields($objects,'taxonomy');
	}

	$objects = get_taxonomies( array( 'public' => true, 'show_ui' => true, '_builtin' => false ), 'object' );
	if(!empty($objects)){
?>
	<tr>
		<th scope="row" class="qtranxs-option-group"><?php _e('Custom Taxonomies', 'qtranslate') ?></th>
		<td><p class="qtranxs_explanation"><?php echo __('Multilingual slug for custom taxonomies.', 'qtranslate').' '; printf(__('The default is value of argument %s as provided in the call of function %s.', 'qtranslate'), '<code>$taxonomy</code>', '<a href="http://codex.wordpress.org/Function_Reference/register_taxonomy" target="_blank"><code>register_taxonomy</code></a>') ?></p>
		</td>
	</tr>
<?php
		qtranxf_slug_admin_fields($objects,'taxonomy');
	}

	//$objects = get_post_types( array('publicly_queryable' => true) );
	$objects = get_post_types( array('_builtin' => false, 'public' => true ), 'objects');
	if(!empty($objects)){
?>
	<tr>
		<th scope="row" class="qtranxs-option-group"><?php _e('Custom Post Types', 'qtranslate') ?></th>
		<td><p class="qtranxs_explanation"><?php echo __('Multilingual slug for post types.', 'qtranslate').' '; printf(__('The default is value of argument %s as provided in the call of function %s.', 'qtranslate'), '<code>$post_type</code>', '<a href="http://codex.wordpress.org/Function_Reference/register_post_type" target="_blank"><code>register_post_type</code></a>') ?></p>
		</td>
	</tr>
<?php
		qtranxf_slug_admin_fields($objects,'post_type');
	}
?>
	<tr><td colspan="2" id="qtranxf_slug_lsb_bottom"></td></tr>
<?php }//if(!empty($q_config['slugs'])) ?>
</table>
<?php 
	qtranxf_admin_section_end('slugs');
}

function qtranxf_slug_admin_fields($objects, $type){
	foreach ($objects as $o){
		$label = $o->labels->singular_name;
		//$label = apply_filters('translate_text', $label);
		$slug = $o->name;
		qtranxf_slug_admin_field($type, $label, $slug);
	}
}

function qtranxf_slug_admin_field($type, $label, $slug){
	$opnm = 'slugs_'.$type;
	$name = $opnm.'['.$slug.']';
	$val = qtranxf_slug_multilingual($slug);
	$id = 'qtranxs_'.$type.'_'.$slug;
?>
	<tr>
		<th scope="row" style="text-align: right"><label for="<?php echo $id ?>" class="i18n-multilingual-display"><?php echo $label ?></label></th>
		<td><input type="text" name="<?php echo $name ?>" id="<?php echo $id ?>" value="<?php echo $val ?>" class="i18n-multilingual-slug qtranxs-slug-<?php echo $type ?> widefat"></td>
	</tr>
<?php
}
}// configuration

{// options update

function qtranxf_slug_update_translations_of($group, $group_name, $default_lang, &$wp_group){
	foreach($_POST['qtranslate-slugs'][$group] as $name_old => &$qfields){
		//$name_old = $qfields['qtranslate-original-value'];
		$name = sanitize_key($qfields[$default_lang]);
		//qtranxf_dbg_log('qtranxf_slug_update_translations_of: new: $wp_group['.$name.']: ', $wp_group[$name]);
		//qtranxf_dbg_log('qtranxf_slug_update_translations_of: old: $wp_group['.$name_old.']: ', $wp_group[$name_old]);
		if($name != $name_old){
			if(isset($wp_group[$name_old])){
				qtranxf_add_warning(sprintf(__('The slug for %s "%s" for default language cannot be changed to "%s" on this page, because it is not known here which tool created it and for what purpose. Please, update this slug on the page where it is originated from. It may be required then to come back here to update the translations, unless the other plugin or theme is %sintegrated%s with %s.', 'qtranslate'), $group_name, $name_old, $name, '<a href="https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide" target="_blank">', '</a>', 'qTranslate&#8209;XT'));
				$qfields[$default_lang] = $name = $name_old;
			}else{
				$slugs_old = qtranxf_slug_get_translations($name_old);
				//qtranxf_dbg_log('qtranxf_slug_update_translations_of: $slugs_old: ', $slugs_old);
				if(!empty($slugs_old)) qtranxf_slug_del_translations($name_old);
			}
		}
		if(isset($wp_group[$name])){
			qtranxf_slug_update_translations($name, $qfields, $default_lang );
		}else{
			qtranxf_add_error(sprintf(__('Translations cannot be updated for unknown %s "%s".', 'qtranslate'), $group_name, $name));
		}
	}
	qtranxf_slug_clean_request($group);
}

function qtranxf_slug_update_settings(){
	if(!isset($_POST['qtranslate-slugs'])) return;
	$default_lang = qtranxf_getLanguageDefault();
	if(isset($_POST['qtranslate-slugs']['slugs_post_type'])){
		global $wp_post_types;
		qtranxf_slug_update_translations_of('slugs_post_type', '<code>post_type</code>', $default_lang, $wp_post_types);
	}
	if(isset($_POST['slugs_taxonomy'])){
		global $wp_taxonomies;
		qtranxf_slug_update_translations_of('slugs_taxonomy', '<code>taxonomy</code>', $default_lang, $wp_taxonomies);
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

function qtranxf_migrate_import_qtranslate_slug(){
	global $q_config, $wpdb;
	//qtranxf_dbg_log('qtranxf_migrate_import_qtranslate_slug: REQUEST_TIME_FLOAT: ', $_SERVER['REQUEST_TIME_FLOAT']);

	$nm = '<a href="https://wordpress.org/plugins/qtranslate-slug/" target="_blank"><span style="color:blue"><strong>QTranslate Slug</strong></span></a>';

	$wpdb->show_errors(); @set_time_limit(0);

	$sql = 'SELECT post_name, meta_key, meta_value FROM '.$wpdb->postmeta.' as m INNER JOIN '.$wpdb->posts.' as p ON p.ID = m.post_id WHERE m.meta_key LIKE "_qts_slug___" AND m.meta_value IS NOT NULL';
	//$sql .= ' AND NOT EXISTS (SELECT * FROM '.$wpdb->prefix.'posts_i18n WHERE ID = post_id AND lang = MID(meta_key,11))';
	$sql .= ' AND NOT EXISTS (SELECT * FROM '.$wpdb->prefix.'i18n_slugs WHERE name = p.post_name AND lang = MID(m.meta_key,11))';

	$result = $wpdb->get_results($sql);
	//qtranxf_dbg_log('qtranxf_migrate_import_qtranslate_slug: $result: ', $result);
	//qtranxf_dbg_log('qtranxf_migrate_import_qtranslate_slug: count($result): ', count($result));

	if(is_array($result)){
		$default_language = $q_config['default_language'];
		//$sql = 'INSERT INTO '.$wpdb->prefix.'posts_i18n (ID, lang, slug) VALUES (%s, %s, %s)';
		$sql = 'INSERT INTO '.$wpdb->prefix.'i18n_slugs (slug, lang, name) VALUES (%s, %s, %s)';
		foreach($result as $row) {
			// todo
			// $slug = $row->meta_value;
			$lang = substr( $row->meta_key,-2);
			$name = $row->post_name;
//			if($lang == $default_language){
//				if($slug != $name){
//	                //qtranxf_dbg_log('qtranxf_migrate_import_qtranslate_slug: $slug('.$slug.') != $name('.$name.')');
//				}
//			}
			if($lang != $default_language){
				$slug = qtranxf_slug_unique($row->meta_value,$lang,$name);
				// todo report
//				if($slug != $row->meta_value){
//				}
	            //qtranxf_dbg_log('qtranxf_migrate_import_qtranslate_slug: $lang='.$lang.'; row: ', $row);
				//$query = $wpdb->prepare($sql, $row->post_id, $lang, $row->meta_value);
				$query = $wpdb->prepare($sql, $slug, $lang, $row->post_name );
				$wpdb->query($query);
			}
		}
		qtranxf_add_message(sprintf(__('Applicable options and slug data from plugin %s have been imported.', 'qtranslate'), $nm).' '.sprintf(__('It might be a good idea to review %smigration instructions%s, if you have not yet done so.', 'qtranslate'),'<a href="https://github.com/qtranslate/qtranslate-xt/wiki/Migration-Guide" target="_blank">','</a>'));
	}else{
		qtranxf_error_log(sprintf(__('Failed to import data from plugin %s.', 'qtranslate'), $nm).' '.sprintf(__('It might be a good idea to review %smigration instructions%s, if you have not yet done so.', 'qtranslate'), '<a href="https://github.com/qtranslate/qtranslate-xt/wiki/Migration-Guide" target="_blank">', '</a>'));
	}
}

function qtranxf_migrate_export_qtranslate_slug(){
	//qtranxf_dbg_log('qtranxf_migrate_export_qtranslate_slug: REQUEST_TIME_FLOAT: ', $_SERVER['REQUEST_TIME_FLOAT']);
	$nm = '<a href="https://wordpress.org/plugins/qtranslate-slug/" target="_blank"><span style="color:blue"><strong>QTranslate Slug</strong></span></a>';
	qtranxf_add_message(sprintf(__('Applicable options and slug data have been exported to plugin %s.', 'qtranslate'), $nm));
}
}// Import/Export
