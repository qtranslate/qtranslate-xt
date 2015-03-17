<?php // encoding: utf-8
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

function qtranxf_get_plugin_name($slug)
{
	//there must be a better way to take it from file?
	switch($slug) {
		case 'qtranslate': return 'qTranslate';
		case 'mqtranslate': return 'mqTranslate';
		case 'qtranslate-xp': return 'qTranslate Plus';
		case 'ztranslate': return 'zTranslate';
		case 'sitepress-multilingual-cms': return 'WPML';
		default: return '';
	}
}

function qtranxf_migrate_options_update($nm_to,$nm_from)
{
	global $wpdb;
	$option_names = $wpdb->get_col("SELECT `option_name` FROM {$wpdb->options} WHERE `option_name` LIKE '$nm_to\_%'");
	foreach ($option_names as $name)
	{
		if(strpos($name,'_flag_location')>0) continue;
		$nm = str_replace($nm_to,$nm_from,$name);
		$value=get_option($nm);
		if($value===FALSE) continue;
		update_option($name,$value);
	}
}

function qtranxf_migrate_options_copy($nm_to,$nm_from)
{
	global $wpdb;
	$options = $wpdb->get_results("SELECT option_name, option_value FROM {$wpdb->options} WHERE `option_name` LIKE '$nm_from\_%'");
	foreach ($options as $option)
	{
		$name = $option->option_name;
		//skip new qTranslate-X specific options
		switch($name){
			case 'qtranslate_flag_location':
			case 'qtranslate_admin_notices':
			case 'qtranslate_domains':
			case 'qtranslate_editor_mode':
			case 'qtranslate_custom_fields':
			case 'qtranslate_custom_field_classes':
			case 'qtranslate_text_field_filters':
			case 'qtranslate_qtrans_compatibility':
			case 'qtranslate_header_css_on':
			case 'qtranslate_header_css':
			case 'qtranslate_filter_options_mode':
			case 'qtranslate_filter_options':
				continue;
			default: break;
		}
		//if(strpos($name,'_flag_location')>0) continue;
		$value = maybe_unserialize($option->option_value);
		if(strpos($name,'_flag_location')>0) continue;
		$nm = str_replace($nm_from,$nm_to,$name);
		update_option($nm,$value);
	}
}

function qtranxf_migrate_import_mqtranslate(){
	qtranxf_migrate_options_update('qtranslate','mqtranslate');
	update_option('qtranslate_qtrans_compatibility', '1');//since 3.1
}
function qtranxf_migrate_export_mqtranslate(){ qtranxf_migrate_options_copy('mqtranslate','qtranslate'); }

function qtranxf_migrate_import_qtranslate_xp(){ qtranxf_migrate_options_update('qtranslate','ppqtranslate'); }
function qtranxf_migrate_export_qtranslate_xp(){ qtranxf_migrate_options_copy('ppqtranslate','qtranslate'); }

//function qtranxf_migrate_import_ztranslate(){ qtranxf_migrate_options_update('qtranslate','ztranslate'); }
//function qtranxf_migrate_export_ztranslate(){ qtranxf_migrate_options_copy('ztranslate','qtranslate'); }

function qtranxf_migrate_plugin($plugin){
	$var=$plugin.'-migration';
	if(!isset($_POST[$var])) return;
	$action = $_POST[$var];
	if($action=='none') return;
	$f='qtranxf_migrate_'.$_POST[$var].'_'.str_replace('-','_',$plugin);
	$f();
	if( $action == 'export' ) return;
	//if( $plugin == 'mqtranslate' )//since 3.2-b2: moved to qtranxf_migrate_import_mqtranslate
	//	update_option('qtranslate_qtrans_compatibility', '1');
	qtranxf_reloadConfig();
}

function qtranxf_migrate_plugins()
{
	if(!current_user_can('manage_options')) return;
	qtranxf_migrate_plugin('mqtranslate');
	qtranxf_migrate_plugin('qtranslate-xp');
	//qtranxf_migrate_plugin('ztranslate');//ok same db
}
add_action('qtranslate_saveConfig','qtranxf_migrate_plugins',30);
//add_action('qtranslate_init_begin','qtranxf_migrate_plugins',11);

function qtranxf_add_row_migrate($nm,$plugin) {
	$plugin_file = WP_CONTENT_DIR.'/plugins/'.$plugin;
	if(!file_exists($plugin_file)) return;
	//$pd = get_plugin_data( $plugin_file.'/mqtranslate.php', false, true );
	//qtranxf_dbg_log('qtranxf_add_row_migrate: $pd:',$pd);
	switch($plugin){
		case 'sitepress-multilingual-cms': $href='https://wpml.org'; break;
		default: $href='https://wordpress.org/plugins/'.$plugin; break;
	}
?>
<tr valign="top" id="qtranslate-<?php echo $plugin; ?>">
	<th scope="row"><?php _e('Plugin');?> <a href="<?php echo $href; ?>/" target="_blank"><?php echo $nm; ?></a></th>
	<td>
<?php
	switch($plugin){
		case 'qtranslate':
		case 'ztranslate':
			_e('There is no need to migrate any setting, the database schema is compatible with this plugin.', 'qtranslate');
		break;
		case 'sitepress-multilingual-cms':
			printf(__('Use plugin %s to import data.', 'qtranslate'),'<a href="https://wordpress.org/plugins/w2q-wpml-to-qtranslate/" target="_blank">W2Q: WPML to qTranslate</a>');
		break;
	default:
?>
		<label for="<?php echo $plugin; ?>_no_migration"><input type="radio" name="<?php echo $plugin; ?>-migration" id="<?php echo $plugin; ?>_no_migration" value="none" checked /> <?php _e('Do not migrate any setting', 'qtranslate'); ?></label>
		<br/>
		<label for="<?php echo $plugin; ?>_import_migration"><input type="radio" name="<?php echo $plugin; ?>-migration" id="<?php echo $plugin; ?>_import_migration" value="import" /> <?php echo __('Import settings from ', 'qtranslate').$nm; ?></label>
		<br/>
		<label for="<?php echo $plugin; ?>_export_migration"><input type="radio" name="<?php echo $plugin; ?>-migration" id="<?php echo $plugin; ?>_export_migration" value="export" /> <?php echo __('Export settings to ', 'qtranslate').$nm; ?></label>
<?php break; } ?>
	</td>
</tr>
<?php
}

function qtranxf_admin_section_import_export($request_uri)
{
	qtranxf_admin_section_start(__('Import', 'qtranslate').'/'.__('Export', 'qtranslate'),'import');
	//id="qtranslate-admin-import" style="display: none"
?>
	<table class="form-table">
		<tr valign="top" id="qtranslate-convert-database">
			<th scope="row"><?php _e('Convert Database', 'qtranslate');?></th>
			<td>
				<?php printf(__('If you are updating from qTranslate 1.x or Polyglot, <a href="%s">click here</a> to convert posts to the new language tag format.', 'qtranslate'), $request_uri.'&convert=true'); ?>
				<?php printf(__('If you have installed qTranslate for the first time on a Wordpress with existing posts, you can either go through all your posts manually and save them in the correct language or <a href="%s">click here</a> to mark all existing posts as written in the default language.', 'qtranslate'), $request_uri.'&markdefault=true'); ?>
				<?php _e('Both processes are <b>irreversible</b>! Be sure to make a full database backup before clicking one of the links.', 'qtranslate'); ?><br/><br/>
				<label for="qtranxs_convert_database_none"><input type="radio" name="convert_database" id="qtranxs_convert_database_none" value="none" checked />&nbsp;<?php _e('Do not convert database', 'qtranslate'); ?></label><br/><br/>
				<label for="qtranxs_convert_database_to_b_only"><input type="radio" name="convert_database" id="qtranxs_convert_database_to_b_only" value="b_only" />&nbsp;<?php echo __('Convert database to the "square bracket only" style.', 'qtranslate'); ?></label><br/>
				<small><?php printf(__('The square bracket language tag %s only will be used as opposite to dual-tag (%s and %s) %s legacy database format. All string options and standard post and page fields will be uniformly encoded like %s.','qtranslate'),'[:]',esc_html('<!--:-->'),'[:]','qTranslate','"[:en]English[:de]Deutsch[:]"'); ?></small><br/><br/>
				<label for="qtranxs_convert_database_to_c_dual"><input type="radio" name="convert_database" id="qtranxs_convert_database_to_c_dual" value="c_dual" />&nbsp;<?php echo __('Convert database back to the legacy "dual language tag" style.', 'qtranslate'); ?></label><br/>
				<small><?php _e('Note, that only string options and standard post and page fields are affected.','qtranslate'); ?></small>
			</td>
		</tr>
		<?php qtranxf_add_row_migrate('qTranslate','qtranslate'); ?>
		<?php qtranxf_add_row_migrate('mqTranslate','mqtranslate'); ?>
		<?php qtranxf_add_row_migrate('qTranslate Plus','qtranslate-xp'); ?>
		<?php qtranxf_add_row_migrate('zTranslate','ztranslate'); ?>
		<?php qtranxf_add_row_migrate('WPML Multilingual CMS','sitepress-multilingual-cms'); ?>
		<tr valign="top">
			<th scope="row"><?php _e('Reset qTranslate', 'qtranslate');?></th>
			<td>
				<label for="qtranslate_reset"><input type="checkbox" name="qtranslate_reset" id="qtranslate_reset" value="1"/> <?php _e('Check this box and click Save Changes to reset all qTranslate settings.', 'qtranslate'); ?></label>
				<br/>
				<label for="qtranslate_reset2"><input type="checkbox" name="qtranslate_reset2" id="qtranslate_reset2" value="1"/> <?php _e('Yes, I really want to reset qTranslate.', 'qtranslate'); ?></label>
				<br/>
				<label for="qtranslate_reset3"><input type="checkbox" name="qtranslate_reset3" id="qtranslate_reset3" value="1"/> <?php _e('Also delete Translations for Categories/Tags/Link Categories.', 'qtranslate'); ?></label>
				<br/>
				<small><?php _e('If something isn\'t working correctly, you can always try to reset all qTranslate settings. A Reset won\'t delete any posts but will remove all settings (including all languages added).', 'qtranslate'); ?></small>
			</td>
		</tr>
	</table>
<?php
	qtranxf_admin_section_end('import');
}
add_action('qtranslate_configuration', 'qtranxf_admin_section_import_export', 9);
