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
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA02110-1301USA
*/

function mqtrans_import_settings_from_qtrans() {
	global $wpdb;
	
	$option_names = $wpdb->get_col("SELECT `option_name` FROM {$wpdb->options} WHERE `option_name` LIKE 'qtranslate\_%'");
	foreach ($option_names as $name)
	{		
		$opt = get_option($name);
		
		$nn = "m{$name}";
		if ( false !== get_option($nn) )
			update_option($nn, $opt);
		else
			add_option($nn, $opt);
	}
}

function mqtrans_export_setting_to_qtrans($updateOnly = false) {
	global $wpdb;
	
	$option_names = $wpdb->get_col("SELECT `option_name` FROM {$wpdb->options} WHERE `option_name` LIKE 'mqtranslate\_%'");
	foreach ($option_names as $name)
	{
		$opt = get_option($name);
	
		$nn = substr($name, 1);
		if ( false !== get_option($nn) )
			update_option($nn, $opt);
		else if (!$updateOnly)
			add_option($nn, $opt);
	}
}

function mqtrans_currentUserCanEdit($lang) {
	global $q_config;
	
	$cu = wp_get_current_user();
	if ($cu->has_cap('edit_users') || empty($q_config['ul_lang_protection']))
		return true;
	else
	{
		$user_meta = get_user_meta($cu->ID);
		if (empty($user_meta) || !is_array($user_meta) || !array_key_exists('mqtranslate_language_access', $user_meta))
			$user_langs = $q_config['enabled_languages'];
		else
			$user_langs = explode(',', get_user_meta($cu->ID, 'mqtranslate_language_access', true));
		return in_array($lang, $user_langs);
	}
}

function mqtrans_currentUserCanView($lang) {
	global $q_config;
	
	$cu = wp_get_current_user();
	if ($cu->has_cap('edit_users') || empty($q_config['ul_lang_protection']))
		return true;
	else
	{
		$master_lang = get_user_meta($cu->ID, 'mqtranslate_master_language', true);
		if (empty($master_lang))
			return ($lang === $q_config['default_language']);
		else
			return ($lang === $master_lang || $lang === $q_config['default_language']);	
	}
}

function mqtrans_userProfile($user) {
	global $q_config;

	if (empty($q_config['ul_lang_protection']))
		return;
	
	$cu = wp_get_current_user();
	$langs = qtrans_getSortedLanguages();
	
	echo '<h3>'.__('mqTranslate User Language Settings', 'mqtranslate') . "</h3>\n";
	echo "<table class=\"form-table\">\n<tbody>\n";
	
	// Editable languages
	$user_meta = get_user_meta($user->ID);
	if (empty($user_meta) || !is_array($user_meta) || !array_key_exists('mqtranslate_language_access', $user_meta))
		$user_langs = $q_config['enabled_languages'];
	else
		$user_langs = explode(',', get_user_meta($user->ID, 'mqtranslate_language_access', true));
	echo "<tr>\n";
	if ($cu->ID == $user->ID)
		echo '<th>'.__('You can edit posts in', 'mqtranslate') . "</th>\n";
	else
		echo '<th>'.__('This user can edit posts in', 'mqtranslate') . "</th>\n";
	echo "<td>";
	if ($user->has_cap('edit_users'))
	{
		if (empty($langs))
			_e('No language available', 'mqtranslate');
		else if ($cu->ID == $user->ID)
			_e('As an Administrator, you can edit posts in all languages.', 'mqtranslate');
		else
			_e('As an Administrator, this user can edit posts in all languages.', 'mqtranslate');
	}
	else if ($cu->has_cap('edit_users'))
	{
		if (empty($langs))
			_e('No language available', 'mqtranslate')."\n";
		else
		{
			$checkboxes = array();
			foreach ($langs as $l) {
				$name = "mqtrans_user_lang_{$l}";
				$checked = (in_array($l, $user_langs)) ? 'checked' : '';
				$checkboxes[] = "<label for=\"{$name}\"><input type=\"checkbox\" name=\"mqtrans_user_lang[]\" id=\"{$name}\" value=\"{$l}\" {$checked} /> {$q_config['language_name'][$l]}</label>\n";
			}
			echo implode("<br />\n", $checkboxes);
		}
	}
	else
	{
		$intersect = array_intersect($langs, $user_langs);
		if (empty($intersect))
			_e('No language selected', 'mqtranslate')."\n";
		else
		{
			$languages = array();
			foreach ($intersect as $l)
				$languages[] = $q_config['language_name'][$l];
			echo implode(', ', $languages);
		}
	}
	echo "</td>\n";
	echo "</tr>\n";
	
	// Master language
	$user_master_lang = get_user_meta($user->ID, 'mqtranslate_master_language', true);
	echo "<tr>\n";
	echo '<th>' . __('Master language', 'mqtranslate') . "</th>\n";
	echo "<td>\n";
	if ($user->has_cap('edit_users'))
		_e('Not applicable to Administrators', 'mqtranslate');
	else if ($cu->has_cap('edit_users'))
	{
		echo "<select name=\"mqtrans_master_lang\">\n";
		echo '<option value="">' . __('Default Language', 'mqtranslate') . "</option>\n";
		foreach ($langs as $l)
		{
			if ($l == $q_config['default_language'])
				continue;
			$selected = ($user_master_lang == $l) ? ' selected' : '';
			echo "<option value=\"{$l}\"{$selected}>{$q_config['language_name'][$l]}</option>\n";
		}
		echo "</select>\n";
		echo '<span class="description">' . __('Language from which texts should be translated by this user', 'mqtranslate') . "</span>\n";
	}
	else
	{
		if (empty($langs) || empty($user_master_lang) || !in_array($user_master_lang, $langs))
			_e('Default Language', 'mqtranslate');
		else
			echo $q_config['language_name'][$user_master_lang];
	}
	echo "</td>\n";
	echo "</tr>\n";
	
	echo "</tbody>\n</table>\n";
}

function mqtrans_userProfileUpdate($user_id) {
	global $q_config;
	$cu = wp_get_current_user();
	if ($cu->has_cap('edit_users') && !empty($q_config['ul_lang_protection'])) {
		// Editable languages
		$langs = (empty($_POST['mqtrans_user_lang'])) ? array() : $_POST['mqtrans_user_lang'];
		if (!is_array($langs))
			$langs = array();
		update_user_meta($user_id, 'mqtranslate_language_access', implode(',', $langs));
		
		// Master language
		if (empty($_POST['mqtrans_master_lang']))
			delete_user_meta($user_id, 'mqtranslate_master_language');
		else
			update_user_meta($user_id, 'mqtranslate_master_language', $_POST['mqtrans_master_lang']);
	}
}

function qtrans_isEmptyContent($value) {
	$str = trim(strip_tags($value, '<img>,<embed>,<object>'));
	return empty($str);
}

function mqtrans_postUpdated($post_ID, $after, $before) {
	global $wpdb, $q_config;

	// Don't handle custom post types
	if (!in_array($after->post_type, array( 'post', 'page' )) && !in_array($after->post_type, $q_config['allowed_custom_post_types']))
		return;
	
	$titleMap = array();
	$contentMap = array();
	
	$cu = wp_get_current_user();
	if ($cu->has_cap('edit_users') || empty($q_config['ul_lang_protection']))
	{
		$title = qtrans_split($after->post_title, true, $titleMap);
		foreach ($title as $k => $v) {
			if (qtrans_isEmptyContent($v))
				unset($title[$k]);
		}
		$content = qtrans_split($after->post_content, true, $contentMap);
		foreach ($content as $k => $v) {
			if (qtrans_isEmptyContent($v))
				unset($content[$k]);
		}
	}
	else
	{
		$titleBeforeMap = array();
		$titleBefore = qtrans_split($before->post_title, true, $titleBeforeMap);
		$titleAfter = qtrans_split($after->post_title, true, $titleMap);
		foreach ($titleAfter as $k => $v) {
			if (!mqtrans_currentUserCanEdit($k))
				unset($titleAfter[$k], $titleMap[$k]);
		}
		$title = array_merge($titleBefore, $titleAfter);
		$titleMap = array_merge($titleBeforeMap, $titleMap);

		$contentBeforeMap = array();
		$contentBefore = qtrans_split($before->post_content, true, $contentBeforeMap);
		$contentAfter = qtrans_split($after->post_content, true, $contentMap);
		foreach ($contentAfter as $k => $v) {
			if (qtrans_isEmptyContent($v) || !mqtrans_currentUserCanEdit($k))
				unset($contentAfter[$k], $contentMap[$k]);
		}
		$content = array_merge($contentBefore, $contentAfter);
		$contentMap = array_merge($contentBeforeMap, $contentMap);
	}
	
	$data = array('post_title' => qtrans_join($title, $titleMap), 'post_content' => qtrans_join($content, $contentMap));
	if (get_magic_quotes_gpc())
		$data = stripslashes_deep($data);
	$where = array('ID' => $post_ID);
	
	$wpdb->update($wpdb->posts, $data, $where);
}

function mqtrans_filterHomeURL($url, $path, $orig_scheme, $blog_id) {
	global $q_config;
	return ((empty($path) && $q_config['url_mode'] == QT_URL_PATH) || $path == '/' || !empty($q_config['url_info']['explicit_default_language'])) ? qtrans_convertURL($url, '', false, $q_config['url_info']['explicit_default_language']) : $url;
}

function mqtrans_filterPostMetaData($original_value, $object_id, $meta_key, $single) {
	if ($meta_key == '_menu_item_url')
	{
		$meta = wp_cache_get($object_id, 'post_meta');
		if (!empty($meta) && array_key_exists($meta_key, $meta) && !empty($meta[$meta_key]))
		{
			if ($single === false)
			{
				if (is_array($meta[$meta_key]))
					$meta = $meta[$meta_key];
				else
					$meta = array($meta[$meta_key]);
				$meta = array_map('qtrans_convertURL', $meta);
			}
			else
			{
				if (is_array($meta[$meta_key]))
					$meta = $meta[$meta_key][0];
				else
					$meta = $meta[$meta_key];
				$meta = qtrans_convertURL($meta);
			}
			return $meta;
		}
	}
	return null;
}

function mqtrans_team_options() {
	global $q_config;
?>
	<h3><?php _e('mqTranslate Team Settings', 'mqtranslate') ?><span id="mqtranslate-show-team"> (<a name="mqtranslate_team_settings" href="#" onclick="return showTeamSettings();"><?php _e('Show / Hide', 'mqtranslate'); ?></a>)</span></h3>
	<table class="form-table" id="mqtranslate-team" style="display: none">
			<tr>
				<th scope="row"><?php _e('User-level Language Protection', 'mqtranslate') ?></th>
				<td>
					<label for="ul_lang_protection"><input type="checkbox" name="ul_lang_protection" id="ul_lang_protection" value="1"<?php echo ($q_config['ul_lang_protection'])?' checked="checked"':''; ?>/> <?php _e('Enable user-level language protection', 'mqtranslate'); ?></label>
					<br />
					<small><?php _e('When enabled, this option allows you to select which language is editable on a user-level account basis.', 'mqtranslate') ?></small>
				</td>
			</tr>
	</table>
	<script type="text/javascript">
	// <![CDATA[
		function showTeamSettings() {
			var el = document.getElementById('mqtranslate-team');
			if (el.style.display == 'block')
				el.style.display = 'none';
			else
				el.style.display='block';
			return false;
		}
	// ]]>
	</script>
<?php
}

function mqtrans_load_team_options() {
	global $q_config;
	$opt = get_option('mqtranslate_ul_lang_protection');
	if ($opt === false)
		$q_config['ul_lang_protection'] = true;
	else
		$q_config['ul_lang_protection'] = ($opt == '1');
}

function mqtrans_save_team_options() {
	qtrans_checkSetting('ul_lang_protection', true, QT_BOOLEAN);
}

function mqtrans_editorExpand() {
	return false;
}

if (!defined('WP_ADMIN'))
{
	add_filter('home_url', 'mqtrans_filterHomeURL', 10, 4);
	add_filter('get_post_metadata', 'mqtrans_filterPostMetaData', 10, 4);
}
else
	add_filter('wp_editor_expand', 'mqtrans_editorExpand');

add_action('edit_user_profile', 			'mqtrans_userProfile');
add_action('show_user_profile',				'mqtrans_userProfile');
add_action('profile_update',				'mqtrans_userProfileUpdate');
add_action('post_updated',					'mqtrans_postUpdated', 10, 3);

add_action('mqtranslate_configuration', 	'mqtrans_team_options', 9);
add_action('mqtranslate_loadConfig',		'mqtrans_load_team_options');
add_action('mqtranslate_saveConfig',		'mqtrans_save_team_options');
?>