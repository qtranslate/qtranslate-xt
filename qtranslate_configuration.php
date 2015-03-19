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

// Exit if accessed directly
if ( !defined( 'WP_ADMIN' ) ) exit;

require_once(dirname(__FILE__).'/admin/import_export.php');

function qtranxf_reset_config()
{
	if(!current_user_can('manage_options')) return;

	$next_thanks = get_option('qtranslate_next_thanks');
	if(!$next_thanks){
		$next_thanks = time() + rand(100,200)*24*60*60;
		update_option('qtranslate_next_thanks', $next_thanks);
	}

	if( !isset($_POST['qtranslate_reset']) || !isset($_POST['qtranslate_reset2']) ) return;
	// reset all settings
	delete_option('qtranslate_admin_notices');
	delete_option('qtranslate_language_names');
	delete_option('qtranslate_enabled_languages');
	delete_option('qtranslate_default_language');
	delete_option('qtranslate_flag_location');
	delete_option('qtranslate_flags');
	delete_option('qtranslate_locales');
	delete_option('qtranslate_na_messages');
	delete_option('qtranslate_date_formats');
	delete_option('qtranslate_time_formats');
	delete_option('qtranslate_use_strftime');
	delete_option('qtranslate_ignore_file_types');
	delete_option('qtranslate_url_mode');
	delete_option('qtranslate_detect_browser_language');
	delete_option('qtranslate_hide_untranslated');
	delete_option('qtranslate_show_displayed_language_prefix');
	delete_option('qtranslate_auto_update_mo');
	delete_option('qtranslate_next_update_mo');
	delete_option('qtranslate_next_thanks');
	delete_option('qtranslate_hide_default_language');
	delete_option('qtranslate_qtrans_compatibility');
	delete_option('qtranslate_editor_mode');
	delete_option('qtranslate_custom_fields');
	delete_option('qtranslate_widget_css'); // obsolete option
	delete_option('qtranslate_use_secure_cookie');
	delete_option('qtranslate_disable_client_cookies');
	delete_option('qtranslate_filter_options_mode');
	delete_option('qtranslate_filter_options');
	delete_option('qtranslate_header_css_on');
	delete_option('qtranslate_header_css');
	if(isset($_POST['qtranslate_reset3'])) {
		delete_option('qtranslate_term_name');
	}
	qtranxf_reloadConfig();
}
add_action('qtranslate_saveConfig','qtranxf_reset_config',20);

function qtranxf_init_admin()
{
	global $q_config;

	// update Gettext Databases if on back-end
	if($q_config['auto_update_mo']){
		require_once(dirname(__FILE__).'/admin/update-gettext-db.php');
		qtranxf_updateGettextDatabases();
	}

	// update definitions if necessary
	if(current_user_can('manage_categories')){
		//qtranxf_updateTermLibrary();
		qtranxf_updateTermLibraryJoin();
	}
}
add_action('qtranslate_init_begin','qtranxf_init_admin');

function qtranxf_update_option( $nm, $default_value=null ) {
	global $q_config;
	if( !isset($q_config[$nm]) || empty($q_config[$nm]) || (!is_null($default_value) && $default_value===$q_config[$nm]) ){
		delete_option('qtranslate_'.$nm);
	}else{
		update_option('qtranslate_'.$nm, $q_config[$nm]);
	}
}

function qtranxf_update_option_bool( $nm, $default_value=null ) {
	global $q_config;
	if( !isset($q_config[$nm]) || ($default_value !== null && $default_value === $q_config[$nm]) ){
		delete_option('qtranslate_'.$nm);
	}else{
		update_option('qtranslate_'.$nm, $q_config[$nm]?'1':'0');
	}
}

// saves entire configuration - it should be in admin only?
function qtranxf_saveConfig() {
	global $q_config;

	update_option('qtranslate_language_names', $q_config['language_name']);// language_names != language_name
	//update_option('qtranslate_enabled_languages', $q_config['enabled_languages']);

	qtranxf_update_option('enabled_languages');
	qtranxf_update_option('domains');

	update_option('qtranslate_default_language', $q_config['default_language']);
	qtranxf_update_option('flag_location',qtranxf_flag_location_default());
	update_option('qtranslate_flags', $q_config['flag']);
	update_option('qtranslate_locales', $q_config['locale']);
	update_option('qtranslate_na_messages', $q_config['not_available']);
	update_option('qtranslate_date_formats', $q_config['date_format']);
	update_option('qtranslate_time_formats', $q_config['time_format']);
	update_option('qtranslate_ignore_file_types', implode(',',$q_config['ignore_file_types']));
	update_option('qtranslate_url_mode', $q_config['url_mode']);
	update_option('qtranslate_term_name', $q_config['term_name']);
	update_option('qtranslate_use_strftime', $q_config['use_strftime']);

	qtranxf_update_option('editor_mode', QTX_EDITOR_MODE_LSB);

	qtranxf_update_option('custom_fields');
	qtranxf_update_option('custom_field_classes');
	qtranxf_update_option('text_field_filters');
	qtranxf_update_option('custom_pages');

	qtranxf_update_option('filter_options_mode',QTX_FILTER_OPTIONS_ALL);
	//if($q_config['filter_options_mode'] == QTX_FILTER_OPTIONS_LIST)
	qtranxf_update_option('filter_options',explode(' ',QTX_FILTER_OPTIONS_DEFAULT));

	qtranxf_update_option_bool('detect_browser_language');
	qtranxf_update_option_bool('hide_untranslated');
	qtranxf_update_option_bool('show_displayed_language_prefix');
	qtranxf_update_option_bool('auto_update_mo');
	qtranxf_update_option_bool('hide_default_language');
	qtranxf_update_option_bool('qtrans_compatibility');
	qtranxf_update_option_bool('use_secure_cookie');
	qtranxf_update_option_bool('disable_client_cookies');

	qtranxf_update_option_bool('header_css_on');
	qtranxf_update_option('header_css', qtranxf_front_header_css_default());

	do_action('qtranslate_saveConfig');
}

function qtranxf_get_custom_admin_js ($pages) {
	global $pagenow;
	//qtranxf_dbg_echo('qtranxf_get_custom_admin_js: $pagenow: ',$pagenow);
	//qtranxf_dbg_echo('qtranxf_get_custom_admin_js: $script_name=',$script_name);
	//if(!isset($_SERVER['REQUEST_URI'])) return false;
	//$uri=$_SERVER['REQUEST_URI'];
	$qs = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
	foreach($pages as $page_conf){
		//list($page,$path) = explode(':',$page_path);
		$page_conf_parts = explode(':',$page_conf);
		//qtranxf_dbg_echo('qtranxf_get_custom_admin_js: $page_conf_parts: ',$page_conf_parts);
		$uri = $page_conf_parts[0];
		$uri_parts = explode('?',$uri);
		//qtranxf_dbg_echo('qtranxf_get_custom_admin_js: $uri_parts: ',$uri_parts);
		$page = $uri_parts[0];
		//qtranxf_dbg_echo('qtranxf_get_custom_admin_js: $page: ',$page);
		if( $page !== $pagenow ) continue;
		if(isset($uri_parts[1]) && strpos($qs,$uri_parts[1])===FALSE) continue;
		return isset($page_conf_parts[1]) ? $page_conf_parts[1] : 'admin/js/edit-custom-page';
	}
	/*
		Filter allows to load custom script.
		Return path relative to the location of qTranslate-X plugin folder, when needed.
	*/
	$script=apply_filters('qtranslate_custom_admin_js',null);
	if($script) return $script;
	return false;
}

function qtranxf_select_admin_js ($enqueue_script=false) {
	global $pagenow;
	global $q_config;
	if(!isset($_SERVER['SCRIPT_NAME'])) return false;
	$script_name=$_SERVER['SCRIPT_NAME'];
	if(!preg_match('#/wp-admin/([^/]+)\.php#',$script_name,$matches)) return false;
	$fn=$matches[1];
	switch($fn){
		//case '/wp-admin/post-new.php':
		//case '/wp-admin/post.php':
		case 'post':
		case 'post-new':
			$script='admin/js/edit-post'; break;
		//case '/wp-admin/edit-tags.php':
		case 'edit-tags':
			if(isset($_SERVER['QUERY_STRING']) && strpos($_SERVER['QUERY_STRING'],'action=edit')!==FALSE ){
				$script='admin/js/edit-tag';
			}else{
				$script='admin/js/edit-tags';
			}
			break;
		//case '/wp-admin/nav-menus.php':
		case 'nav-menus':
			if(isset($_SERVER['QUERY_STRING'])){
				$qs=$_SERVER['QUERY_STRING'];
				//qtranxf_dbg_echo('$qs=',$qs);
				if(preg_match('/action=([^&#]+)/',$qs,$matches)){
					//qtranxf_dbg_echo('$matches[1]=',$matches[1]);
					if( $matches[1] != 'edit' ) return false;
				}
				//if(strpos($qs,'action=')!==FALSE && strpos($qs,'action=edit')===FALSE) return false;
			}
			$script='admin/js/edit-nav-menus';
			break;
		//case '/wp-admin/options-general.php':
		case 'options-general':
			if(isset($_SERVER['QUERY_STRING'])){
				$qs=$_SERVER['QUERY_STRING'];
				if(strpos($qs,'page=')!==FALSE) return false;
			}
			$script='admin/js/edit-options-general'; break;
		default:
			$script=qtranxf_get_custom_admin_js($q_config['custom_pages']);
			//qtranxf_dbg_echo('qtranxf_select_admin_js: $script: ',$script);
			if(!$script) return false;
			break;
	}
	$plugin_dir_path=plugin_dir_path(__FILE__);
	$script_path=$script.'.min.js'; $fn=$plugin_dir_path.$script_path;
	while(!file_exists($fn)){
		$script_path=$script.'.js'; $fn=$plugin_dir_path.$script_path;
		if(file_exists($fn)) break;
		$script_path=$script; $fn=$plugin_dir_path.$script_path;
		if(file_exists($fn)) break;
		return false;
	}
	if($enqueue_script){
		$script_url=plugins_url( $script_path, __FILE__ );
		//wp_register_script( 'qtranslate-admin-edit', $script_url, array('qtranslate-admin-common'), QTX_VERSION );
		wp_register_script( 'qtranslate-admin-edit', $script_url, array(), QTX_VERSION );
		wp_enqueue_script( 'qtranslate-admin-edit' );
	}
	//qtranxf_dbg_echo('qtranxf_select_admin_js: $fn: ',$fn);
	return $fn;
}

/**
 * load field configurations for the current admin page
 */
function qtranxf_load_admin_page_config() {
	global $pagenow;

	$page_config = array();

	$page_configs = array();//will be set to a default in the future

	$page_configs = apply_filters('qtranslate_load_admin_page_config',$page_configs);
	foreach($page_configs as $pgcfg){
		foreach($pgcfg['pages'] as $page => $query){
			//qtranxf_dbg_log('qtranxf_load_admin_page_config: $page='.$page.'; query=',$query);
			if( preg_match('!'.$page.'!',$pagenow) !== 1 ) continue;
			//qtranxf_dbg_log('qtranxf_load_admin_page_config: preg_match($page,$pagenow) ok. $_SERVER[QUERY_STRING]=',$_SERVER['QUERY_STRING']);
			if( !empty($query) && (isset($_SERVER['QUERY_STRING']) && preg_match('!'.$query.'!',$_SERVER['QUERY_STRING']) !== 1 ) ) continue;
			//qtranxf_dbg_log('qtranxf_load_admin_page_config: preg_match($query,$_SERVER[QUERY_STRING] ok');

			if( isset($pgcfg['anchors']) && !empty($pgcfg['anchors']) ){
				if( !isset($page_config['anchors']) ) $page_config['anchors'] = $pgcfg['anchors'];
				else $page_config['anchors'] = array_merge($page_config['anchors'],$pgcfg['anchors']);
			}

			if( isset($pgcfg['forms']) && !empty($pgcfg['forms']) ){
				if( !isset($page_config['forms']) ) $page_config['forms'] = $pgcfg['forms'];
				else $page_config['forms'] = array_merge($page_config['forms'],$pgcfg['forms']);
			}

			break;
		}
	}
	return $page_config;
}

function qtranxf_add_admin_footer_js ( $enqueue_script=false ) {
	global $q_config;
	if( $q_config['editor_mode'] == QTX_EDITOR_MODE_RAW) return;
	$script_file = qtranxf_select_admin_js($enqueue_script);
	$page_config = qtranxf_load_admin_page_config();
	if(!$script_file && empty($page_config))
		return;

	wp_dequeue_script('autosave');
	wp_deregister_script( 'autosave' );//autosave script saves the active language only and messes it up later in a hard way

	if( $enqueue_script ){
		//wp_register_script( 'qtranslate-admin-utils', plugins_url( '/admin/js/utils.min.js', __FILE__ ), array(), QTX_VERSION );
		//wp_enqueue_script( 'qtranslate-admin-utils' );
		$deps = array();
		if($script_file) $deps[] = 'qtranslate-admin-edit';
		if(isset($page_config['scripts'])){
			foreach($page_config['scripts'] as $js){
			}
		}
		wp_register_script( 'qtranslate-admin-common', plugins_url( '/admin/js/common.min.js', __FILE__ ), $deps, QTX_VERSION );
		wp_enqueue_script( 'qtranslate-admin-common' );
	}

	$config=array();
	$keys=array('enabled_languages','default_language','language','custom_fields','custom_field_classes','url_mode');//,'term_name'
	foreach($keys as $key){
		$config[$key]=$q_config[$key];
	}
	if($q_config['url_mode']==QTX_URL_DOMAINS){
		$config['domains']=$q_config['domains'];
	}
	$homeinfo=qtranxf_get_home_info();
	$config['url_info_home']=trailingslashit($homeinfo['path']);//$q_config['url_info']['home'];
	$config['flag_location']=qtranxf_flag_location();
	$config['js']=array();
	$config['flag']=array();
	$config['language_name']=array();
	foreach($q_config['enabled_languages'] as $lang)
	{
		$config['flag'][$lang]=$q_config['flag'][$lang];
		$config['language_name'][$lang]=$q_config['language_name'][$lang];
	}
	if(!empty($page_config)) $config['page_config'] = $page_config;
?>
<script type="text/javascript">
// <![CDATA[
<?php
	echo 'var qTranslateConfig='.json_encode($config).';'.PHP_EOL;
	if(!$enqueue_script){
		if($script_file) readfile($script_file);
		$plugin_dir_path=plugin_dir_path(__FILE__);
		readfile($plugin_dir_path.'admin/js/common.min.js');
		if(isset($page_config['scripts'])){
			foreach($page_config['scripts'] as $js){
			}
		}
	}
	if($q_config['qtrans_compatibility']){
		echo 'qtrans_use = function(lang, text) { var result = qtranxj_split(text); return result[lang]; }'.PHP_EOL;
	}
	do_action('qtranslate_add_admin_footer_js');
?>
//]]>
</script>
<?php
}

function qtranxf_add_admin_head_js ($enqueue_script=true) {
	global $q_config;
/*
	echo '<script type="text/javascript">'.PHP_EOL.'// <![CDATA['.PHP_EOL;
	if($enqueue_script){
		wp_register_script( 'qtranslate-admin-utils', plugins_url( '/admin/js/utils.min.js', __FILE__ ), array(), QTX_VERSION );
		wp_enqueue_script( 'qtranslate-admin-utils' );
	}else{
		$plugin_dir_path=plugin_dir_path(__FILE__);
		readfile($plugin_dir_path.'admin/js/utils.min.js');
	}
	//if($q_config['qtrans_compatibility']){
	//	echo 'qtrans_use = function(lang, text) { var result = qtranxj_split(text); return result[lang]; }'.PHP_EOL;
	//}
*/
	if(strpos($_SERVER['REQUEST_URI'],'page=qtranslate-x') !== FALSE) {
		echo '<script type="text/javascript">'.PHP_EOL.'// <![CDATA['.PHP_EOL;
?>
function qtranxj_getcookie(cname)
{
	var nm = cname + "=";
	var ca = document.cookie.split(';');
	for(var i=0; i<ca.length; i++) {
		var ce = ca[i];
		var p = ce.indexOf(nm);
		if (p >= 0) return ce.substring(p+nm.length,ce.length);
	}
	return '';
}
function qtranxj_delcookie(cname)
{
	var date = new Date();
	date.setTime(date.getTime()-(24*60*60*1000));
	document.cookie=cname+'=; expires='+date.toGMTString();
}
function qtranxj_readShowHideCookie(id) {
	var e=document.getElementById(id);
	if(!e) return;
	if(qtranxj_getcookie(id)){
		e.style.display='block';
	}else{
		e.style.display='none';
	}
}
function qtranxj_toggleShowHide(id) {
	var e = document.getElementById(id);
	if (e.style.display == 'block'){
		qtranxj_delcookie(id);
		e.style.display = 'none';
	}else{
		document.cookie=id+'=1';
		e.style.display='block';
	}
	return false;
}
<?php
		echo '//]]>'.PHP_EOL.'</script>'.PHP_EOL;
	}
}

function qtranxf_add_admin_lang_icons ()
{
	global $q_config;
	echo '<style type="text/css">'.PHP_EOL;
	echo "#wpadminbar #wp-admin-bar-language>div.ab-item{ background-size: 0;";
	echo "background-image: url(".qtranxf_flag_location().$q_config['flag'][$q_config['language']].");}\n";
	foreach($q_config['enabled_languages'] as $language) 
	{
		echo "#wpadminbar ul li#wp-admin-bar-".$language." {background-size: 0; background-image: url(".qtranxf_flag_location().$q_config['flag'][$language].");}\n";
	}
	echo '</style>'.PHP_EOL;
}

function qtranxf_add_admin_css () {
	wp_register_style( 'qtranslate-admin-style', plugins_url('qtranslate_configuration.css', __FILE__), array(), QTX_VERSION );
	wp_enqueue_style( 'qtranslate-admin-style' );
	qtranxf_add_admin_lang_icons();
	echo '<style type="text/css" media="screen">'.PHP_EOL;
/*
	echo ".qtranxs_title_input { border:0pt none; font-size:1.7em; outline-color:invert; outline-style:none; outline-width:medium; padding:0pt; width:100%; }\n";
	echo ".qtranxs_title_wrap { border-color:#CCCCCC; border-style:solid; border-width:1px; padding:2px 3px; }\n";
	echo "#qtranxs_textarea_content { padding:6px; border:0 none; line-height:150%; outline: none; margin:0pt; width:100%; -moz-box-sizing: border-box;";
	echo	"-webkit-box-sizing: border-box; -khtml-box-sizing: border-box; box-sizing: border-box; }\n";
	echo ".qtranxs_title { -moz-border-radius: 6px 6px 0 0;";
	echo	"-webkit-border-top-right-radius: 6px; -webkit-border-top-left-radius: 6px; -khtml-border-top-right-radius: 6px; -khtml-border-top-left-radius: 6px;";
	echo	"border-top-right-radius: 6px; border-top-left-radius: 6px; }\n";
	echo ".hide-if-no-js.wp-switch-editor.switch-tmce { margin-left:6px !important;}";
	echo "#postexcerpt textarea { height:4em; margin:0; width:98% }";
	echo ".qtranxs_lang_div { float:right; height:12px; width:18px; padding:6px 5px 8px 5px; cursor:pointer }";
	echo ".qtranxs_lang_div.active { background: #DFDFDF; border-left:1px solid #D0D0D0; border-right: 1px solid #F7F7F7; padding:6px 4px 8px 4px }";
*/
	//echo "#qtranxs_debug { width:100%; height:200px }";
	do_action('qtranslate_admin_css');
	do_action('qtranslate_css');//should not be used
	echo '</style>'.PHP_EOL;
}

function qtranxf_admin_head() {
	//qtranxf_add_css();//Since 3.2.5 no longer needed
	qtranxf_add_admin_css();
	qtranxf_add_admin_head_js();
	//Since 3.2.7 qtranxf_optionFilter('disable');//why this is here?
}
add_action('admin_head', 'qtranxf_admin_head');

function qtranxf_admin_footer() {
	$enqueue_script = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG);
	qtranxf_add_admin_footer_js( $enqueue_script );
}
add_action('admin_footer', 'qtranxf_admin_footer',999);

/* qTranslate-X Management Interface */
function qtranxf_adminMenu() {
	global $menu, $submenu, $q_config;
	// Configuration Page
	add_options_page(__('Language Management', 'qtranslate'), __('Languages', 'qtranslate'), 'manage_options', 'qtranslate-x', 'qtranxf_conf');
}

function qtranxf_language_form($lang = '', $language_code = '', $language_name = '', $language_locale = '', $language_date_format = '', $language_time_format = '', $language_flag ='', $language_na_message = '', $language_default = '', $original_lang='') {
	global $q_config;
?>
<input type="hidden" name="original_lang" value="<?php echo $original_lang; ?>" />
<div class="form-field">
	<label for="language_code"><?php _e('Language Code', 'qtranslate') ?></label>
	<input name="language_code" id="language_code" type="text" value="<?php echo $language_code; ?>" size="2" maxlength="2"/>
	<p><?php _e('2-Letter <a href="http://www.w3.org/WAI/ER/IG/ert/iso639.htm#2letter">ISO Language Code</a> for the Language you want to insert. (Example: en)', 'qtranslate'); ?></p>
</div>
<div class="form-field">
	<label for="language_flag"><?php _e('Flag', 'qtranslate') ?></label>
	<?php 
	$files = array();
	$flag_dir = trailingslashit(WP_CONTENT_DIR).$q_config['flag_location'];
	if($dir_handle = @opendir($flag_dir)) {
		while (false !== ($file = readdir($dir_handle))) {
			if(preg_match("/\.(jpeg|jpg|gif|png)$/i",$file)) {
				$files[] = $file;
			}
		}
		sort($files);
	}
	if(sizeof($files)>0){
	?>
	<select name="language_flag" id="language_flag" onchange="switch_flag(this.value);"  onclick="switch_flag(this.value);" onkeypress="switch_flag(this.value);">
	<?php
		foreach ($files as $file) {
	?>
		<option value="<?php echo $file; ?>" <?php echo ($language_flag==$file)?'selected="selected"':''?>><?php echo $file; ?></option>
	<?php
		}
	?>
	</select>
	<img src="" alt="<?php _e('Flag', 'qtranslate'); ?>" id="preview_flag" style="vertical-align:middle; display:none"/>
	<?php
	} else {
		_e('Incorrect Flag Image Path! Please correct it!', 'qtranslate');
	}
	?>
	<p><?php _e('Choose the corresponding country flag for language. (Example: gb.png)', 'qtranslate'); ?></p>
</div>
<script type="text/javascript">
//<![CDATA[
	function switch_flag(url) {
		document.getElementById('preview_flag').style.display = "inline";
		document.getElementById('preview_flag').src = "<?php echo qtranxf_flag_location();?>" + url;
	}
	switch_flag(document.getElementById('language_flag').value);
//]]>
</script>
<div class="form-field">
	<label for="language_name"><?php _e('Name', 'qtranslate') ?></label>
	<input name="language_name" id="language_name" type="text" value="<?php echo $language_name; ?>"/>
	<p><?php _e('The Name of the language, which will be displayed on the site. (Example: English)', 'qtranslate'); ?></p>
</div>
<div class="form-field">
	<label for="language_locale"><?php _e('Locale', 'qtranslate') ?></label>
	<input name="language_locale" id="language_locale" type="text" value="<?php echo $language_locale; ?>"  size="5" maxlength="5"/>
	<p>
	<?php _e('PHP and Wordpress Locale for the language. (Example: en_US)', 'qtranslate'); ?><br/>
	<?php _e('You will need to install the .mo file for this language.', 'qtranslate'); ?>
	</p>
</div>
<div class="form-field">
	<label for="language_date_format"><?php _e('Date Format', 'qtranslate') ?></label>
	<input name="language_date_format" id="language_date_format" type="text" value="<?php echo $language_date_format; ?>"/>
	<p><?php _e('Depending on your Date / Time Conversion Mode, you can either enter a <a href="http://www.php.net/manual/function.strftime.php">strftime</a> (use %q for day suffix (st,nd,rd,th)) or <a href="http://www.php.net/manual/function.date.php">date</a> format. This field is optional. (Example: %A %B %e%q, %Y)', 'qtranslate'); ?></p>
</div>
<div class="form-field">
	<label for="language_time_format"><?php _e('Time Format', 'qtranslate') ?></label>
	<input name="language_time_format" id="language_time_format" type="text" value="<?php echo $language_time_format; ?>"/>
	<p><?php _e('Depending on your Date / Time Conversion Mode, you can either enter a <a href="http://www.php.net/manual/function.strftime.php">strftime</a> or <a href="http://www.php.net/manual/function.date.php">date</a> format. This field is optional. (Example: %I:%M %p)', 'qtranslate'); ?></p>
</div>
<div class="form-field">
	<label for="language_na_message"><?php _e('Not Available Message', 'qtranslate') ?></label>
	<input name="language_na_message" id="language_na_message" type="text" value="<?php echo $language_na_message; ?>"/>
	<p>
	<?php _e('Message to display if post is not available in the requested language. (Example: Sorry, this entry is only available in %LANG:, : and %.)', 'qtranslate'); ?><br/>
	<?php _e('%LANG:&lt;normal_separator&gt;:&lt;last_separator&gt;% generates a list of languages separated by &lt;normal_separator&gt; except for the last one, where &lt;last_separator&gt; will be used instead.', 'qtranslate'); ?><br/>
	</p>
</div>
<?php
}

function qtranxf_updateSetting($var, $type = QTX_STRING, $def = null) {
	global $q_config;
	if(!isset($_POST['submit'])) return false;
	switch($type) {
		case QTX_URL:
		case QTX_LANGUAGE:
		case QTX_STRING:
			if(!isset($_POST[$var])) return false;
			$val=$_POST[$var];
			if($type == QTX_URL) $val = trailingslashit($val);
			else if($type == QTX_LANGUAGE && !qtranxf_isEnabled($val)) return false;
			//standardize multi-line string
			$lns = preg_split('/\r?\n\r?/',$val);
			$val = implode(PHP_EOL,$lns);
			if(isset($q_config[$var])){
				if($q_config[$var] === $val) return false;
			}elseif(!is_null($def)){
				if(empty($val) || $def === $val) return false;
			}
			if(empty($val) && $def) $val = $def;
			$q_config[$var] = $val;
			qtranxf_update_option($var, $def);
			return true;
		case QTX_ARRAY:
			if(!isset($_POST[$var])) return false;
			$val=preg_split('/[\s,]+/',$_POST[$var],null,PREG_SPLIT_NO_EMPTY);
			if( isset($q_config[$var]) && qtranxf_array_compare($q_config[$var],$val) ) return false;
			$q_config[$var] = $val;
			qtranxf_update_option($var, $def);
			return true;
/*
		case QTX_ARRAY_STRING:
			if(!isset($_POST[$var])) return false;
			$val=preg_split('/[\s,]+/',strtolower($_POST[$var]));
			$diff=array_diff($q_config[$var],$val);
			if(empty($diff)) return false;
			$q_config[$var] = $val;
			update_option('qtranslate_'.$var, implode(',',$val));
			return true;
*/
		case QTX_BOOLEAN:
			if(isset($_POST[$var])&&$_POST[$var]==1) {
				if($q_config[$var]) return false;
				$q_config[$var] = true;
				update_option('qtranslate_'.$var, '1');
			} else {
				if(!$q_config[$var]) return false;
				$q_config[$var] = false;
				update_option('qtranslate_'.$var, '0');
			}
			return true;
		case QTX_INTEGER:
			if(!isset($_POST[$var])) return false;
			$val = intval($_POST[$var]);
			if($q_config[$var] == $val) return false;
			$q_config[$var] = $val;
			update_option('qtranslate_'.$var, $q_config[$var]);
			return true;
	}
	return false;
}

function qtranxf_updateSettingFlagLocation($nm) {
	global $q_config;
	if(!isset($_POST['submit'])) return false;
	if(!isset($_POST[$nm])) return false;
	$flag_location=untrailingslashit($_POST[$nm]);
	if(empty($flag_location)) $flag_location = qtranxf_flag_location_default();
	$flag_location = trailingslashit($flag_location);
	if(!file_exists(trailingslashit(WP_CONTENT_DIR).$flag_location))
		return null;
	if($flag_location != $q_config[$nm]){
		$q_config[$nm]=$flag_location;
		if($flag_location == qtranxf_flag_location_default())
			delete_option('qtranslate_'.$nm);
		else
			update_option( 'qtranslate_'.$nm, $flag_location );
	}
	return true;
}

function qtranxf_updateSettingIgnoreFileTypes($nm) {
	global $q_config;
	if(!isset($_POST['submit'])) return false;
	if(!isset($_POST[$nm])) return false;
	$posted=preg_split('/[\s,]+/',strtolower($_POST[$nm]),null,PREG_SPLIT_NO_EMPTY);
	$val=explode(',',QTX_IGNORE_FILE_TYPES);
	if(is_array($posted)){
		foreach($posted as $v){
			if(empty($v)) continue;
			if(in_array($v,$val)) continue;
			$val[]=$v;
		}
	}
	if( qtranxf_array_compare($q_config[$nm],$val) ) return false;
	$q_config[$nm] = $val;
	update_option('qtranslate_'.$nm, implode(',',$val));
	return true;
}

function qtranxf_array_compare($a,$b) {
	if( !is_array($a) || !is_array($b) ) return false;
	if(count($a)!=count($b)) return false;
	//can be optimized
	$diff_a=array_diff($a,$b);
	$diff_b=array_diff($b,$a);
	return empty($diff_a) && empty($diff_b);
}

function qtranxf_admin_section_start($section, $nm) {
	echo '<h3>'.$section.'<span id="qtranxs-show-'.$nm.'"> ( <a name="qtranslate_'.$nm.'_settings" href="#" onclick="return qtranxj_toggleShowHide(\'qtranslate-admin-'.$nm.'\');">'.__('Show', 'qtranslate').' / '.__('Hide', 'qtranslate').'</a> )</span></h3>'.PHP_EOL;
	echo '<div id="qtranslate-admin-'.$nm.'" style="display: none">'.PHP_EOL;
}

function qtranxf_admin_section_end($nm) {
?>
<p class="submit">
	<input type="submit" name="submit" class="button-primary" value="<?php _e('Save Changes', 'qtranslate') ?>" />
</p>
</div>
<script type="text/javascript">
//<![CDATA[
	qtranxj_readShowHideCookie('qtranslate-admin-<?php echo $nm; ?>');
// ]]>
</script>
<?php
}

function qtranxf_conf() {
	global $q_config, $wpdb;
	//qtranxf_dbg_echo('qtranxf_conf: POST: ',$_POST);

	// do redirection for dashboard
	if(isset($_GET['godashboard'])) {
		echo '<h2>'.__('Switching Language', 'qtranslate').'</h2>'.sprintf(__('Switching language to %1$s... If the Dashboard isn\'t loading, use this <a href="%2$s" title="Dashboard">link</a>.','qtranslate'),$q_config['language_name'][qtranxf_getLanguage()],admin_url()).'<script type="text/javascript">document.location="'.admin_url().'";</script>';
		exit();
	}

	// init some needed variables
	$error = '';
	$original_lang = '';
	$language_code = '';
	$language_name = '';
	$language_locale = '';
	$language_date_format = '';
	$language_time_format = '';
	$language_na_message = '';
	$language_flag = '';
	$language_default = '';
	$altered_table = false;

	$message = apply_filters('qtranslate_configuration_pre',array());

	// check for action
	if(isset($_POST['qtranslate_reset']) && isset($_POST['qtranslate_reset2'])) {
		$message[] = __('qTranslate has been reset.', 'qtranslate');
	} elseif(isset($_POST['default_language'])) {
		// update settings
		qtranxf_updateSetting('default_language', QTX_LANGUAGE);

		//qtranxf_updateSetting('flag_location', QTX_URL);
		qtranxf_updateSettingFlagLocation('flag_location');

		//qtranxf_updateSetting('ignore_file_types', QTX_ARRAY_STRING);
		qtranxf_updateSettingIgnoreFileTypes('ignore_file_types');

		qtranxf_updateSetting('url_mode', QTX_INTEGER);
		switch($q_config['url_mode']){
			case QTX_URL_DOMAIN:
			case QTX_URL_DOMAINS: $q_config['disable_client_cookies'] = true; break;
			case QTX_URL_QUERY:
			case QTX_URL_PATH:
			default: qtranxf_updateSetting('disable_client_cookies', QTX_BOOLEAN); break;
		}

		$domains = isset($q_config['domains']) ? $q_config['domains'] : array();
		foreach($q_config['enabled_languages'] as $lang){
			$id='language_domain_'.$lang;
			if(!isset($_POST[$id])) continue;
			$domain = preg_replace('#^/*#','',untrailingslashit(trim($_POST[$id])));
			//qtranxf_dbg_echo('qtranxf_conf: domain['.$lang.']: ',$domain);
			$domains[$lang] = $domain;
		}
		if( !empty($domains) && (!isset($q_config['domains']) || !qtranxf_array_compare($q_config['domains'],$domains)) ){
			$q_config['domains'] = $domains;
			qtranxf_update_option('domains');
		}

		qtranxf_updateSetting('detect_browser_language', QTX_BOOLEAN);
		qtranxf_updateSetting('hide_untranslated', QTX_BOOLEAN);
		qtranxf_updateSetting('show_displayed_language_prefix', QTX_BOOLEAN);
		qtranxf_updateSetting('use_strftime', QTX_INTEGER);
		qtranxf_updateSetting('editor_mode', QTX_INTEGER);
		qtranxf_updateSetting('auto_update_mo', QTX_BOOLEAN);
		qtranxf_updateSetting('hide_default_language', QTX_BOOLEAN);
		qtranxf_updateSetting('qtrans_compatibility', QTX_BOOLEAN);
		qtranxf_updateSetting('custom_fields', QTX_ARRAY);
		qtranxf_updateSetting('custom_field_classes', QTX_ARRAY);
		qtranxf_updateSetting('text_field_filters', QTX_ARRAY);
		qtranxf_updateSetting('custom_pages', QTX_ARRAY);
		qtranxf_updateSetting('use_secure_cookie', QTX_BOOLEAN);
		qtranxf_updateSetting('filter_options_mode', QTX_INTEGER);
		qtranxf_updateSetting('filter_options', QTX_ARRAY);

		qtranxf_updateSetting('header_css_on', QTX_BOOLEAN);
		qtranxf_updateSetting('header_css', QTX_STRING, qtranxf_front_header_css_default());

		if(isset($_POST['update_mo_now']) && $_POST['update_mo_now']=='1' && qtranxf_updateGettextDatabases(true))
			$message[] = __('Gettext databases updated.', 'qtranslate');

		$import_migration = preg_grep( '/import/', $_POST );
		foreach($import_migration as $key => $value){
			$plugin = substr($key,0,-strlen('-migration'));
			$nm = '<span style="color:blue"><strong>'.qtranxf_get_plugin_name($plugin).'</strong></span>';
			$message[] = sprintf(__('Applicable options and taxonomy names from plugin %s have been imported. Note that the multilingual content of posts, pages and other objects has not been altered during this operation. There is no additional operation needed to import content, since its format is compatible with %s.', 'qtranslate'), $nm, 'qTranslate&#8209;X').' '.sprintf(__('It might be a good idea to review %smigration instructions%s, if you have not yet done so.', 'qtranslate'),'<a href="https://qtranslatexteam.wordpress.com/2015/02/24/migration-from-other-multilingual-plugins/" target="_blank">','</a>');
			$message[] = sprintf(__('%sImportant%s: Before you start making edits to post and pages, please, make sure that both, your front site and admin back-end, work under this configuration. It may help to review "%s" and see if any of conflicting plugins mentioned there are used here. While the current content, coming from %s, is compatible with this plugin, the newly modified posts and pages will be saved with a new square-bracket-only encoding, which has a number of advantages comparing to former %s encoding. However, the new encoding is not straightforwardly compatible with %s and you will need an additional step available under "%s" option if you ever decide to go back to %s. Even with this additional conversion step, the 3rd-party plugins custom-stored data will not be auto-converted, but manual editing will still work. That is why it is advisable to create a test-copy of your site before making any further changes. In case you encounter a problem, please give us a chance to improve %s, send the login information to the test-copy of your site to %s along with a detailed step-by-step description of what is not working, and continue using your main site with %s meanwhile. It would also help, if you share a success story as well, either on %sthe forum%s, or via the same e-mail as mentioned above. Thank you very much for trying %s.', 'qtranslate'), '<span style="color:red">', '</span>', '<a href="https://wordpress.org/plugins/qtranslate-x/other_notes/" target="_blank">'.'Known Issues'.'</a>', $nm, 'qTranslate', $nm, '<span style="color:magenta">'.__('Convert Database', 'qtranslate').'</span>', $nm, 'qTranslate&#8209;X', '<a href="mailto:qtranslateteam@gmail.com">qtranslateteam@gmail.com</a>', $nm, '<a href="https://wordpress.org/support/plugin/qtranslate-x">', '</a>', 'qTranslate&#8209;X').'<br><small>'.__('This is a one-time message, which you will not see again, unless the same import is repeated.', 'qtranslate').'</small>';
			if ($plugin == 'mqtranslate'){
				$message[] = sprintf(__('Option "%s" has also been turned on, as the most common case for importing configuration from %s. You may turn it off manually if your setup does not require it. Refer to %sFAQ%s for more information.', 'qtranslate'), '<span style="color:magenta">'.__('Compatibility Functions', 'qtranslate').'</span>', $nm, '<a href="https://wordpress.org/plugins/qtranslate-x/faq/" target="_blank">', '</a>');
			}
		}

		$export_migration = preg_grep( '/export/', $_POST );
		foreach($export_migration as $key => $value){
			$plugin = substr($key,0,-strlen('-migration'));
			$nm = '<span style="color:blue"><strong>'.qtranxf_get_plugin_name($plugin).'</strong></span>';
			$message[] = sprintf(__('Applicable options have been exported to plugin %s. If you have done some post or page updates after migrating from %s, then "%s" operation is also required to convert the content to "dual language tag" style in order for plugin %s to function.', 'qtranslate'), $nm, $nm, '<span style="color:magenta">'.__('Convert Database', 'qtranslate').'</span>', $nm);
		}

		if(isset($_POST['convert_database'])){
			$msg = qtranxf_convert_database($_POST['convert_database']);
			if($msg) $message[] = $msg;
		}
	}

	if(isset($_POST['original_lang'])) {
		// validate form input
		if($_POST['language_na_message']=='')		$error = __('The Language must have a Not-Available Message!', 'qtranslate');
		if(strlen($_POST['language_locale'])<2)		$error = __('The Language must have a Locale!', 'qtranslate');
		if($_POST['language_name']=='')				$error = __('The Language must have a name!', 'qtranslate');
		if(strlen($_POST['language_code'])!=2)		$error = __('Language Code has to be 2 characters long!', 'qtranslate');
		if($_POST['original_lang']==''&&$error=='') {
			// new language
			if(isset($q_config['language_name'][$_POST['language_code']])) {
				$error = __('There is already a language with the same Language Code!', 'qtranslate');
			} 
		} 
		if($_POST['original_lang']!=''&&$error=='') {
			// language update
			if($_POST['language_code']!=$_POST['original_lang']&&isset($q_config['language_name'][$_POST['language_code']])) {
				$error = __('There is already a language with the same Language Code!', 'qtranslate');
			} else {
				// remove old language
				unset($q_config['language_name'][$_POST['original_lang']]);
				unset($q_config['flag'][$_POST['original_lang']]);
				unset($q_config['locale'][$_POST['original_lang']]);
				unset($q_config['date_format'][$_POST['original_lang']]);
				unset($q_config['time_format'][$_POST['original_lang']]);
				unset($q_config['not_available'][$_POST['original_lang']]);
				if(in_array($_POST['original_lang'],$q_config['enabled_languages'])) {
					// was enabled, so set modified one to enabled too
					for($i = 0; $i < sizeof($q_config['enabled_languages']); $i++) {
						if($q_config['enabled_languages'][$i] == $_POST['original_lang']) {
							$q_config['enabled_languages'][$i] = $_POST['language_code'];
						}
					}
				}
				if($_POST['original_lang']==$q_config['default_language'])
					// was default, so set modified the default
					$q_config['default_language'] = $_POST['language_code'];
			}
		}
		if(get_magic_quotes_gpc()) {
			if(isset($_POST['language_date_format'])) $_POST['language_date_format'] = stripslashes($_POST['language_date_format']);
			if(isset($_POST['language_time_format'])) $_POST['language_time_format'] = stripslashes($_POST['language_time_format']);
		}
		if($error=='') {
			// everything is fine, insert language
			$q_config['language_name'][$_POST['language_code']] = $_POST['language_name'];
			$q_config['flag'][$_POST['language_code']] = $_POST['language_flag'];
			$q_config['locale'][$_POST['language_code']] = $_POST['language_locale'];
			$q_config['date_format'][$_POST['language_code']] = $_POST['language_date_format'];
			$q_config['time_format'][$_POST['language_code']] = $_POST['language_time_format'];
			$q_config['not_available'][$_POST['language_code']] = $_POST['language_na_message'];
		}
		if($error!=''||isset($_GET['edit'])) {
			// get old values in the form
			$original_lang = $_POST['original_lang'];
			$language_code = $_POST['language_code'];
			$language_name = $_POST['language_name'];
			$language_locale = $_POST['language_locale'];
			$language_date_format = $_POST['language_date_format'];
			$language_time_format = $_POST['language_time_format'];
			$language_na_message = $_POST['language_na_message'];
			$language_flag = $_POST['language_flag'];
			$language_default = $_POST['language_default'];
		}
	} elseif(isset($_GET['convert'])){
		// update language tags
		global $wpdb;
		$wpdb->show_errors();
		foreach($q_config['enabled_languages'] as $lang) {
			$wpdb->query('UPDATE '.$wpdb->posts.' set post_title = REPLACE(post_title, "[lang_'.$lang.']","<!--:'.$lang.'-->")');
			$wpdb->query('UPDATE '.$wpdb->posts.' set post_title = REPLACE(post_title, "[/lang_'.$lang.']","<!--:-->")');
			$wpdb->query('UPDATE '.$wpdb->posts.' set post_content = REPLACE(post_content, "[lang_'.$lang.']","<!--:'.$lang.'-->")');
			$wpdb->query('UPDATE '.$wpdb->posts.' set post_content = REPLACE(post_content, "[/lang_'.$lang.']","<!--:-->")');
		}
		$message[] = "Database Update successful!";
	} elseif(isset($_GET['markdefault'])){
		// update language tags
		global $wpdb;
		$wpdb->show_errors();
		$result = $wpdb->get_results('SELECT ID, post_title, post_content FROM '.$wpdb->posts.' WHERE NOT (post_content LIKE "%<!--:-->%" OR post_title LIKE "%<!--:-->%")');
		foreach($result as $post) {
			$title=qtranxf_mark_default($post->post_title);
			$content=qtranxf_mark_default($post->post_content);
			if( $title==$post->post_title && $content==$post->post_content ) continue;
			//qtranxf_dbg_echo("markdefault:<br>\ntitle old: '".$post->post_title."'<br>\ntitle new: '".$title."'<br>\ncontent old: '".$post->post_content."'<br>\ncontent new: '".$content."'"); continue;
			$wpdb->query('UPDATE '.$wpdb->posts.' set post_content = "'.mysql_real_escape_string($content).'", post_title = "'.mysql_real_escape_string($title).'" WHERE ID='.$post->ID);
		}
		$message[] = "All Posts marked as default language!";
	} elseif(isset($_GET['edit'])){
		$original_lang = $_GET['edit'];
		$language_code = $_GET['edit'];
		$language_name = $q_config['language_name'][$_GET['edit']];
		$language_locale = $q_config['locale'][$_GET['edit']];
		$language_date_format = $q_config['date_format'][$_GET['edit']];
		$language_time_format = $q_config['time_format'][$_GET['edit']];
		$language_na_message = $q_config['not_available'][$_GET['edit']];
		$language_flag = $q_config['flag'][$_GET['edit']];
	} elseif(isset($_GET['delete'])) {
		// validate delete (protect code)
		if($q_config['default_language']==$_GET['delete'])
			$error = 'Cannot delete Default Language!';
		if(!isset($q_config['language_name'][$_GET['delete']])||strtolower($_GET['delete'])=='code')
			$error = __('No such language!', 'qtranslate');
		if($error=='') {
			// everything seems fine, delete language
			qtranxf_disableLanguage($_GET['delete']);
			unset($q_config['language_name'][$_GET['delete']]);
			unset($q_config['flag'][$_GET['delete']]);
			unset($q_config['locale'][$_GET['delete']]);
			unset($q_config['date_format'][$_GET['delete']]);
			unset($q_config['time_format'][$_GET['delete']]);
			unset($q_config['not_available'][$_GET['delete']]);
		}
	} elseif(isset($_GET['enable'])) {
		// enable validate
		if(!qtranxf_enableLanguage($_GET['enable'])) {
			$error = __('Language is already enabled or invalid!', 'qtranslate');
		}
	} elseif(isset($_GET['disable'])) {
		// enable validate
		if($_GET['disable']==$q_config['default_language'])
			$error = __('Cannot disable Default Language!', 'qtranslate');
		if(!qtranxf_isEnabled($_GET['disable']))
			if(!isset($q_config['language_name'][$_GET['disable']]))
				$error = __('No such language!', 'qtranslate');
		// everything seems fine, disable language
		if($error=='' && !qtranxf_disableLanguage($_GET['disable'])) {
			$error = __('Language is already disabled!', 'qtranslate');
		}
	} elseif(isset($_GET['moveup'])) {
		$languages = qtranxf_getSortedLanguages();
		$msg = __('No such language!', 'qtranslate');
		foreach($languages as $key => $language) {
			if($language==$_GET['moveup']) {
				if($key==0) {
					$message[] = __('Language is already first!', 'qtranslate');
					break;
				}
				$languages[$key] = $languages[$key-1];
				$languages[$key-1] = $language;
				$q_config['enabled_languages'] = $languages;
				$msg = __('New order saved.', 'qtranslate');
				break;
			}
		}
		$message[] = $msg;
	} elseif(isset($_GET['movedown'])) {
		$languages = qtranxf_getSortedLanguages();
		$msg = __('No such language!', 'qtranslate');
		foreach($languages as $key => $language) {
			if($language==$_GET['movedown']) {
				if($key==sizeof($languages)-1) {
					$message[] = __('Language is already last!', 'qtranslate');
					break;
				}
				$languages[$key] = $languages[$key+1];
				$languages[$key+1] = $language;
				$q_config['enabled_languages'] = $languages;
				$msg = __('New order saved.', 'qtranslate');
				break;
			}
		}
		$message[] = $msg;
	}

	$everything_fine = ((isset($_POST['submit'])||isset($_GET['delete'])||isset($_GET['enable'])||isset($_GET['disable'])||isset($_GET['moveup'])||isset($_GET['movedown']))&&$error=='');
	if($everything_fine) {
		// settings might have changed, so save
		qtranxf_saveConfig();
		if(empty($message)) {
			$message[] = __('Options saved.', 'qtranslate');
		}
	}
	if($q_config['auto_update_mo']) {
		if(!is_dir(WP_LANG_DIR) || !$ll = @fopen(trailingslashit(WP_LANG_DIR).'qtranslate.test','a')) {
			$error = sprintf(__('Could not write to "%s", Gettext Databases could not be downloaded!', 'qtranslate'), WP_LANG_DIR);
		} else {
			@fclose($ll);
			@unlink(trailingslashit(WP_LANG_DIR).'qtranslate.test');
		}
	}
	// don't accidentally delete/enable/disable twice
	$clean_uri = preg_replace("/&(delete|enable|disable|convert|markdefault|moveup|movedown)=[^&#]*/i","",$_SERVER['REQUEST_URI']);
	$clean_uri = apply_filters('qtranslate_clean_uri', $clean_uri);

// Generate XHTML
	$plugindir = dirname(plugin_basename( __FILE__ ));
	$pluginurl=WP_PLUGIN_URL.'/'.$plugindir;
?>
<?php
	if (!empty($message)) :
		foreach($message as $msg){
?>
<div id="message" class="updated fade"><p><strong><?php echo $msg; ?></strong></p></div>
<?php } endif; ?>
<?php if ($error!='') : ?>
<div id="message" class="error fade"><p><strong><?php echo $error; ?></strong></p></div>
<?php endif; ?>

<?php if(isset($_GET['edit'])) { ?>
<div class="wrap">
<h2><?php _e('Edit Language', 'qtranslate'); ?></h2>
<form action="" method="post" id="qtranxs-edit-language">
<?php qtranxf_language_form($language_code, $language_code, $language_name, $language_locale, $language_date_format, $language_time_format, $language_flag, $language_na_message, $language_default, $original_lang); ?>
<p class="submit"><input type="submit" name="submit" value="<?php _e('Save Changes &raquo;', 'qtranslate'); ?>" /></p>
</form>
</div>
<?php } else { ?>
<div class="wrap">
<h2><?php _e('Language Management (qTranslate Configuration)', 'qtranslate'); ?></h2>
<small><?php printf(__('For help on how to configure qTranslate correctly, take a look at the <a href="%1$s">qTranslate FAQ</a> and the <a href="%2$s">Support Forum</a>.', 'qtranslate'), 'http://wordpress.org/plugins/qtranslate-x/faq/', 'https://wordpress.org/support/plugin/qtranslate-x'); ?></small>
	<form action="<?php echo $clean_uri;?>" method="post">
	<?php  qtranxf_admin_section_start(__('General Settings', 'qtranslate'),'general'); //id="qtranslate-admin-general" ?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e('Default Language / Order', 'qtranslate') ?></th>
				<td>
					<fieldset id="qtranxs-languages-menu"><legend class="hidden"><?php _e('Default Language', 'qtranslate') ?></legend>
				<?php
					$flag_location = qtranxf_flag_location();
					foreach ( qtranxf_getSortedLanguages() as $key => $language ) {
						echo '<label title="' . $q_config['language_name'][$language] . '"><input type="radio" name="default_language" value="' . $language . '"';
						checked($language,$q_config['default_language']);
						echo ' />';
						echo ' <a href="'.add_query_arg('moveup', $language, $clean_uri).'"><img src="'.$pluginurl.'/arrowup.png" alt="up" /></a>';
						echo ' <a href="'.add_query_arg('movedown', $language, $clean_uri).'"><img src="'.$pluginurl.'/arrowdown.png" alt="down" /></a>';
						echo ' <img src="' . $flag_location.$q_config['flag'][$language] . '" alt="' . $q_config['language_name'][$language] . '" /> ';
						echo ' '.$q_config['language_name'][$language] . '</label><br/>'.PHP_EOL;
					}
				?>
					<small><?php printf(__('Choose the default language of your blog. This is the language which will be shown on %s. You can also change the order the languages by clicking on the arrows above.', 'qtranslate'), get_bloginfo('url')); ?></small>
					</fieldset>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Hide Untranslated Content', 'qtranslate');?></th>
				<td>
					<label for="hide_untranslated"><input type="checkbox" name="hide_untranslated" id="hide_untranslated" value="1"<?php checked($q_config['hide_untranslated']); ?>/> <?php _e('Hide Content which is not available for the selected language.', 'qtranslate'); ?></label>
					<br/>
					<small><?php _e('When checked, posts will be hidden if the content is not available for the selected language. If unchecked, a message will appear showing all the languages the content is available in.', 'qtranslate'); ?>
					<?php printf(__('This function will not work correctly if you installed %s on a blog with existing entries. In this case you will need to take a look at option "%s" under "%s" section.', 'qtranslate'),'qTranslate',__('Convert Database','qtranslate'),__('Import','qtranslate').'/'.__('Export','qtranslate')); ?></small>
					<br/><br/>
					<label for="show_displayed_language_prefix"><input type="checkbox" name="show_displayed_language_prefix" id="show_displayed_language_prefix" value="1"<?php checked($q_config['show_displayed_language_prefix']); ?>/> <?php _e('Show displayed language prefix when content is not available for the selected language.', 'qtranslate'); ?></label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Detect Browser Language', 'qtranslate');?></th>
				<td>
					<label for="detect_browser_language"><input type="checkbox" name="detect_browser_language" id="detect_browser_language" value="1"<?php checked($q_config['detect_browser_language']); ?>/> <?php _e('Detect the language of the browser and redirect accordingly.', 'qtranslate'); ?></label>
					<br/>
					<small><?php _e('When the frontpage is visited via bookmark/external link/type-in, the visitor will be forwarded to the correct URL for the language specified by his browser.', 'qtranslate'); ?></small>
				</td>
			</tr>
		</table>
	<?php qtranxf_admin_section_end('general'); ?>
	<?php qtranxf_admin_section_start(__('Advanced Settings', 'qtranslate'),'advanced'); //id="qtranslate-admin-advanced"
		$permalink_is_query = qtranxf_is_permalink_structure_query();
		//qtranxf_dbg_echo('$permalink_is_query: ',$permalink_is_query);
		$url_mode = $q_config['url_mode'];
	?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e('URL Modification Mode', 'qtranslate') ?></th>
				<td>
					<fieldset><legend class="hidden"><?php _e('URL Modification Mode', 'qtranslate') ?></legend>
						<label title="Query Mode"><input type="radio" name="url_mode" value="<?php echo QTX_URL_QUERY; ?>" <?php checked($url_mode,QTX_URL_QUERY); ?> /> <?php echo __('Use Query Mode (?lang=en)', 'qtranslate').'. '.__('Most SEO unfriendly, not recommended.', 'qtranslate'); ?></label><br/>
					<?php /*
							if($permalink_is_query) {
								echo '<br/>'.PHP_EOL;
								printf(__('No other URL Modification Modes are available if permalink structure is set to "Default" on configuration page %sPermalink Setting%s. It is SEO advantageous to use some other permalink mode, which will enable more URL Modification Modes here as well.', 'qtranslate'),'<a href="'.admin_url('options-permalink.php').'">', '</a>');
								echo PHP_EOL.'<br/><br/>'.PHP_EOL;
							}else{ */ ?>
						<label title="Pre-Path Mode"><input type="radio" name="url_mode" value="<?php echo QTX_URL_PATH; ?>" <?php checked($url_mode,QTX_URL_PATH); disabled($permalink_is_query); ?> /> <?php echo __('Use Pre-Path Mode (Default, puts /en/ in front of URL)', 'qtranslate').'. '.__('SEO friendly.', 'qtranslate'); ?></label><br/>
						<label title="Pre-Domain Mode"><input type="radio" name="url_mode" value="<?php echo QTX_URL_DOMAIN; ?>" <?php checked($url_mode,QTX_URL_DOMAIN); ?> /> <?php echo __('Use Pre-Domain Mode (uses http://en.yoursite.com)', 'qtranslate').'. '.__('You will need to configure DNS sub-domains on your site.', 'qtranslate'); ?></label><br/>
					<?php /*
						<small><?php _e('Pre-Path and Pre-Domain mode will only work with mod_rewrite/pretty permalinks. Additional Configuration is needed for Pre-Domain mode or Per-Domain mode.', 'qtranslate'); ?></small><br/><br/>
							} */
					?>
						<label for="hide_default_language"><input type="checkbox" name="hide_default_language" id="hide_default_language" value="1"<?php checked($q_config['hide_default_language']); ?>/> <?php _e('Hide URL language information for default language.', 'qtranslate'); ?></label><br/>
						<small><?php _e('This is only applicable to Pre-Path and Pre-Domain mode.', 'qtranslate'); ?></small><br/><br/>
					<?php
						//if(!$permalink_is_query) {
							do_action('qtranslate_url_mode_choices',$permalink_is_query);
					?>
						<label title="Per-Domain Mode"><input type="radio" name="url_mode" value="<?php echo QTX_URL_DOMAINS; ?>" <?php checked($url_mode,QTX_URL_DOMAINS); ?> /> <?php echo __('Use Per-Domain mode: specify separate user-defined domain for each language.', 'qtranslate'); ?></label>
					<?php //} ?>
					</fieldset>
				</td>
			</tr>
	<?php
/*
			<tr valign="top">
				<td style="text-align: right"><?php echo __('Hide Default Language', 'qtranslate').':'; ?></td>
				<td>
				</td>
			</tr>
*/
		if($url_mode==QTX_URL_DOMAINS){
			$home_url=parse_url(get_option('home'),PHP_URL_HOST);
			foreach($q_config['enabled_languages'] as $lang){
				$id='language_domain_'.$lang;
				$domain = isset($q_config['domains'][$lang]) ? $q_config['domains'][$lang] : $lang.'.'.$home_url;
				echo '<tr><td style="text-align: right">'.__('Domain for', 'qtranslate').' <a href="'.$clean_uri.'&edit='.$lang.'">'.$q_config['language_name'][$lang].'</a>&nbsp;('.$lang.'):</td><td><input type="text" name="'.$id.'" id="'.$id.'" value="'.$domain.'" style="width:100%"/></td></tr>'.PHP_EOL;
			}
		}
	?>
			<tr valign="top">
				<th scope="row"><?php _e('Flag Image Path', 'qtranslate');?></th>
				<td>
					<?php echo trailingslashit(WP_CONTENT_URL); ?><input type="text" name="flag_location" id="flag_location" value="<?php echo $q_config['flag_location']; ?>" style="width:100%"/>
					<br/>
					<small><?php printf(__('Path to the flag images under wp-content, with trailing slash. (Default: %s, clear the value above to reset it to the default)', 'qtranslate'), qtranxf_flag_location_default()); ?></small>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Ignore Links', 'qtranslate');?></th>
				<td>
					<input type="text" name="ignore_file_types" id="ignore_file_types" value="<?php echo implode(',',array_diff($q_config['ignore_file_types'],explode(',',QTX_IGNORE_FILE_TYPES))); ?>" style="width:100%"/>
					<br/>
					<small><?php printf(__('Don\'t convert links to files of the given file types. (Always included: %s)', 'qtranslate'),implode(', ',explode(',',QTX_IGNORE_FILE_TYPES))); ?></small>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Head inline CSS', 'qtranslate'); ?></th>
				<td>
					<label for="header_css_on"><input type="checkbox" name="header_css_on" id="header_css_on" value="1"<?php checked($q_config['header_css_on']); ?> />&nbsp;<?php _e('CSS code added by plugin in the head of front-end pages:', 'qtranslate'); ?></label>
					<br />
					<textarea id="header_css" name="header_css" style="width:100%"><?php echo esc_attr(qtranxf_front_header_css()); ?></textarea>
					<br />
					<small><?php echo __('To reset to default, clear the text.', 'qtranslate').' '.__('To disable this inline CSS, clear the check box.', 'qtranslate'); ?></small>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Cookie Settings', 'qtranslate'); ?></th>
				<td>
					<label for="disable_client_cookies"><input type="checkbox" name="disable_client_cookies" id="disable_client_cookies" value="1"<?php checked($q_config['disable_client_cookies']); disabled( $url_mode==QTX_URL_DOMAIN || $url_mode==QTX_URL_DOMAINS); ?> /> <?php printf(__('Disable language client cookie "%s" (not recommended).', 'qtranslate'),QTX_COOKIE_NAME_FRONT); ?></label>
					<br />
					<small><?php echo sprintf(__('Language cookie is auto-disabled for "%s" "Pre-Domain" and "Per-Domain", as language is always unambiguously defined by a url in those modes.','qtranslate'), __('URL Modification Mode', 'qtranslate')).' '.sprintf(__('Otherwise, use this option with a caution, for simple enough sites only. If checked, the user choice of browsing language will not be saved between sessions and some AJAX calls may deliver unexpected language, as well as some undesired language switching during browsing may occur under certain themes (%sRead More%s).', 'qtranslate'),'<a href="https://qtranslatexteam.wordpress.com/2015/02/26/browser-redirection-based-on-language/" target="_blank">','</a>'); ?></small>
					<br /><br />
					<label for="use_secure_cookie"><input type="checkbox" name="use_secure_cookie" id="use_secure_cookie" value="1"<?php checked($q_config['use_secure_cookie']); ?> /><?php printf(__('Make %s cookies available only through HTTPS connections.', 'qtranslate'),'qTranslate&#8209;X'); ?></label>
					<br />
					<small><?php _e("Don't check this if you don't know what you're doing!", 'qtranslate') ?></small>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Update Gettext Databases', 'qtranslate');?></th>
				<td>
					<label for="auto_update_mo"><input type="checkbox" name="auto_update_mo" id="auto_update_mo" value="1"<?php checked($q_config['auto_update_mo']); ?>/> <?php _e('Automatically check for .mo-Database Updates of installed languages.', 'qtranslate'); ?></label>
					<br/>
					<label for="update_mo_now"><input type="checkbox" name="update_mo_now" id="update_mo_now" value="1" /> <?php _e('Update Gettext databases now.', 'qtranslate'); ?></label>
					<br/>
					<small><?php _e('qTranslate will query the Wordpress Localisation Repository every week and download the latest Gettext Databases (.mo Files).', 'qtranslate'); ?></small>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Date / Time Conversion', 'qtranslate');?></th>
				<td>
					<label><input type="radio" name="use_strftime" value="<?php echo QTX_DATE; ?>" <?php checked($q_config['use_strftime'],QTX_DATE); ?>/> <?php _e('Use emulated date function.', 'qtranslate'); ?></label><br/>
					<label><input type="radio" name="use_strftime" value="<?php echo QTX_DATE_OVERRIDE; ?>" <?php checked($q_config['use_strftime'],QTX_DATE_OVERRIDE); ?>/> <?php _e('Use emulated date function and replace formats with the predefined formats for each language.', 'qtranslate'); ?></label><br/>
					<label><input type="radio" name="use_strftime" value="<?php echo QTX_STRFTIME; ?>" <?php checked($q_config['use_strftime'],QTX_STRFTIME); ?>/> <?php _e('Use strftime instead of date.', 'qtranslate'); ?></label><br/>
					<label><input type="radio" name="use_strftime" value="<?php echo QTX_STRFTIME_OVERRIDE; ?>" <?php checked($q_config['use_strftime'],QTX_STRFTIME_OVERRIDE); ?>/> <?php _e('Use strftime instead of date and replace formats with the predefined formats for each language.', 'qtranslate'); ?></label><br/>
					<small><?php _e('Depending on the mode selected, additional customizations of the theme may be needed.', 'qtranslate'); ?></small>
					<?php /*
					<br/><br/>
					<label><?php _e('If one of the above options "... replace formats with the predefined formats for each language" is in use, then exclude the following formats from being overridden:', 'qtranslate'); ?></label><br/>
					<input type="text" name="ex_date_formats" id="qtranxs_ex_date_formats" value="<?php echo isset($q_config['ex_date_formats']) ? implode(' ',$q_config['ex_date_formats']) : QTX_EX_DATE_FORMATS_DEFAULT; ?>" style="width:100%"><br/>
					*/ ?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Translation of options', 'qtranslate'); ?></th>
				<td>
					<label for="filter_options_mode_all"><input type="radio" name="filter_options_mode" id="filter_options_mode_all" value=<?php echo '"'.QTX_FILTER_OPTIONS_ALL.'"'; checked($q_config['filter_options_mode'],QTX_FILTER_OPTIONS_ALL); ?> /> <?php _e('Filter all WordPress options for translation at front-end. It may hurt performance of the site, but ensures that all options are translated.', 'qtranslate'); ?> <?php _e('Starting from version 3.2.5, only options with multilingual content get filtered, which should help on performance issues.', 'qtranslate'); ?></label>
					<br />
					<label for="filter_options_mode_list"><input type="radio" name="filter_options_mode" id="filter_options_mode_list" value=<?php echo '"'.QTX_FILTER_OPTIONS_LIST.'"'; checked($q_config['filter_options_mode'],QTX_FILTER_OPTIONS_LIST); ?> /> <?php _e('Translate only options listed below (for experts only):', 'qtranslate'); ?> </label>
					<br />
					<input type="text" name="filter_options" id="qtranxs_filter_options" value="<?php echo isset($q_config['filter_options']) ? implode(' ',$q_config['filter_options']) : QTX_FILTER_OPTIONS_DEFAULT; ?>" style="width:100%"><br/>
					<small><?php printf(__('By default, all options are filtered to be translated at front-end for the sake of simplicity of configuration. However, for a developed site, this may cause a considerable performance degradation. Normally, there are very few options, which actually need a translation. You may simply list them above to minimize the performance impact, while still getting translations needed. Options names must match the field "%s" of table "%s" of WordPress database. A minimum common set of option, normally needed a translation, is already entered in the list above as a default example. Option names in the list may contain wildcard with symbol "%s".', 'qtranslate'), 'option_name', 'options', '%'); ?></small>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php echo __('Custom Fields', 'qtranslate');?></th>
				<td>
					<?php printf(__('Enter "%s" or "%s" attribute of text fields from your theme, which you wish to translate. This applies to post, page and media editors (%s). To lookup "%s" or "%s", right-click on the field in the post or the page editor and choose "%s". Look for an attribute of the field named "%s" or "%s". Enter it below, as many as you need, space- or comma-separated. After saving configuration, these fields will start responding to the language switching buttons, and you can enter different text for each language. The input fields of type %s will be parsed using %s syntax, while single line text fields will use %s syntax. If you need to override this behaviour, prepend prefix %s or %s to the name of the field to specify which syntax to use. For more information, read %sFAQ%s.', 'qtranslate'),'id','class','/wp-admin/post*','id','class',_x('Inspect Element','browser option','qtranslate'),'id','class','\'textarea\'',esc_html('<!--:-->'),'[:]','\'<\'','\'[\'','<a href="https://wordpress.org/plugins/qtranslate-x/faq/">','</a>'); ?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" style="text-align: right">id</th>
				<td>
					<input type="text" name="custom_fields" id="qtranxs_custom_fields" value="<?php echo implode(' ',$q_config['custom_fields']); ?>" style="width:100%"><br/>
					<small><?php _e('The value of "id" attribute is normally unique within one page, otherwise the first field found, having an id specified, is picked up.', 'qtranslate'); ?></small>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" style="text-align: right">class</th>
				<td>
					<input type="text" name="custom_field_classes" id="qtranxs_custom_field_classes" value="<?php echo implode(' ',$q_config['custom_field_classes']); ?>" style="width:100%"><br>
					<small><?php printf(__('All the fields of specified classes will respond to Language Switching Buttons. Be careful not to include a class, which would affect language-neutral fields. If you cannot uniquely identify a field needed neither by %s, nor by %s attribute, report the issue on %sSupport Forum%s', 'qtranslate'),'"id"', '"class"', '<a href="https://wordpress.org/support/plugin/qtranslate-x">','</a>'); ?></small>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php echo __('Custom Filters', 'qtranslate');?></th>
				<td>
					<input type="text" name="text_field_filters" id="qtranxs_text_field_filters" value="<?php echo implode(' ',$q_config['text_field_filters']); ?>" style="width:100%"><br>
					<small><?php printf(__('Names of filters (which are enabled on theme or other plugins via %s function) to add translation to. For more information, read %sFAQ%s.', 'qtranslate'),'apply_filters()','<a href="https://wordpress.org/plugins/qtranslate-x/faq/">','</a>'); ?></small>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php echo __('Custom Admin Pages', 'qtranslate');?></th>
				<td><input type="text" name="custom_pages" id="qtranxs_custom_pages" value="<?php echo implode(' ',$q_config['custom_pages']); ?>" style="width:100%"><br>
					<small><?php printf(__('List the custom admin page paths for which you wish Language Switching Buttons to show up. The Buttons will then control fields configured in "Custom Fields" section. You may only include part of the full URL after %s, including a distinctive query string if needed. As many as desired pages can be listed space/comma separated. For more information, read %sFAQ%s.', 'qtranslate'),'/wp-admin/','<a href="https://wordpress.org/plugins/qtranslate-x/faq/">','</a>'); ?></small>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Compatibility Functions', 'qtranslate');?></th>
				<td>
					<label for="qtranxs_qtrans_compatibility"><input type="checkbox" name="qtrans_compatibility" id="qtranxs_qtrans_compatibility" value="1"<?php checked($q_config['qtrans_compatibility']); ?>/>&nbsp;<?php printf(__('Enable function name compatibility (%s).', 'qtranslate'), 'qtrans_convertURL, qtrans_generateLanguageSelectCode, qtrans_getLanguage, qtrans_getLanguageName, qtrans_getSortedLanguages, qtrans_split, qtrans_use, qtrans_useCurrentLanguageIfNotFoundShowAvailable, qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage, qtrans_useDefaultLanguage, qtrans_useTermLib'); ?></label><br/>
					<small><?php printf(__('Some plugins and themes use direct calls to the functions listed, which are defined in former %s plugin and some of its forks. Turning this flag on will enable those function to exists, which will make the dependent plugins and themes to work. WordPress policy prohibits to define functions with the same names as in other plugins, since it generates user-unfriendly fatal errors, when two conflicting plugins are activated simultaneously. Before turning this option on, you have to make sure that there are no other plugins active, which define those functions.', 'qtranslate'), '<a href="https://wordpress.org/plugins/qtranslate/" target="_blank">qTranslate</a>'); ?></small>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Editor Mode', 'qtranslate'); ?></th>
				<td>
					<label for="qtranxs_editor_mode_lsb"><input type="radio" name="editor_mode" id="qtranxs_editor_mode_lsb" value="<?php echo QTX_EDITOR_MODE_LSB; ?>"<?php checked($q_config['editor_mode'], QTX_EDITOR_MODE_LSB); ?>/>&nbsp;<?php _e('Use Language Switching Buttons (LSB).', 'qtranslate'); ?></label><br/>
					<small><?php _e('This is the default mode.', 'qtranslate'); ?></small><br/>
					<label for="qtranxs_editor_mode_raw"><input type="radio" name="editor_mode" id="qtranxs_editor_mode_raw" value="<?php echo QTX_EDITOR_MODE_RAW; ?>"<?php checked($q_config['editor_mode'], QTX_EDITOR_MODE_RAW); ?>/>&nbsp;<?php _e('Editor Raw Mode', 'qtranslate'); ?>. <?php _e('Do not use Language Switching Buttons to edit multi-language text entries.', 'qtranslate'); ?></label><br/>
					<small><?php _e('Some people prefer to edit the raw entries containing all languages together separated by language defining tags, as they are stored in database.', 'qtranslate'); ?></small>
				</td>
			</tr>
<?php /*
			<tr>
				<th scope="row"><?php _e('Debugging Information', 'qtranslate');?></th>
				<td>
					<p><?php printf(__('If you encounter any problems and you are unable to solve them yourself, you can visit the <a href="%s">Support Forum</a>. Posting the following Content will help other detect any misconfigurations.', 'qtranslate'), 'https://wordpress.org/support/plugin/qtranslate-x'); ?></p>
					<textarea readonly="readonly" id="qtranxs_debug"><?php
						$q_config_copy = $q_config;
						// remove information to keep data anonymous and other not needed things
						unset($q_config_copy['url_info']);
						unset($q_config_copy['js']);
						unset($q_config_copy['windows_locale']);
						//unset($q_config_copy['pre_domain']);
						unset($q_config_copy['term_name']);
						echo htmlspecialchars(print_r($q_config_copy, true));
					?></textarea>
				</td>
			</tr>
*/ ?>
		</table>
	<?php qtranxf_admin_section_end('advanced'); ?>
<?php do_action('qtranslate_configuration', $clean_uri); ?>
	</form>

</div>
<div class="wrap">

<h2><?php _e('Languages', 'qtranslate') ?></h2>
<div id="col-container">

<div id="col-right">
<div class="col-wrap">

<table class="widefat">
	<thead>
	<tr>
<?php print_column_headers('language'); ?>
	</tr>
	</thead>

	<tfoot>
	<tr>
<?php print_column_headers('language', false); ?>
	</tr>
	</tfoot>

	<tbody id="the-list" class="qtranxs-language-list" class="list:cat">
<?php foreach($q_config['language_name'] as $lang => $language){ if($lang!='code') { ?>
	<tr>
		<td><img src="<?php echo qtranxf_flag_location().$q_config['flag'][$lang]; ?>" alt="<?php echo sprintf(__('%s Flag', 'qtranslate'), $language) ?>"></td>
		<td><?php echo $language; ?></td>
		<td><?php if(in_array($lang,$q_config['enabled_languages'])) { ?><a class="edit" href="<?php echo $clean_uri; ?>&disable=<?php echo $lang; ?>"><?php _e('Disable', 'qtranslate'); ?></a><?php  } else { ?><a class="edit" href="<?php echo $clean_uri; ?>&enable=<?php echo $lang; ?>"><?php _e('Enable', 'qtranslate'); ?></a><?php } ?></td>
		<td><a class="edit" href="<?php echo $clean_uri; ?>&edit=<?php echo $lang; ?>"><?php _e('Edit', 'qtranslate'); ?></a></td>
		<td><?php if($q_config['default_language']==$lang) { ?><?php _e('Default', 'qtranslate'); ?><?php  } else { ?><a class="delete" href="<?php echo $clean_uri; ?>&delete=<?php echo $lang; ?>"><?php _e('Delete', 'qtranslate'); ?></a><?php } ?></td>
	</tr>
<?php }} ?>
	</tbody>
</table>
<p><?php _e('Enabling a language will cause qTranslate to update the Gettext-Database for the language, which can take a while depending on your server\'s connection speed.', 'qtranslate');?></p>
</div>
</div><!-- /col-right -->

<div id="col-left">
<div class="col-wrap">
<div class="form-wrap">
<h3><?php _e('Add Language', 'qtranslate'); ?></h3>
<form name="addcat" id="addcat" method="post" class="add:the-list: validate">
<?php qtranxf_language_form($language_code, $language_code, $language_name, $language_locale, $language_date_format, $language_time_format, $language_flag, $language_default, $language_na_message); ?>
<p class="submit"><input type="submit" name="submit" value="<?php _e('Add Language &raquo;', 'qtranslate'); ?>" /></p>
</form></div>
</div>
</div><!-- /col-left -->

</div><!-- /col-container -->
<?php
}
}

/* Add a metabox in admin menu page */
function qtranxf_nav_menu_metabox( $object )
{
	global $nav_menu_selected_id;

	$elems = array( '#qtransLangSwLM#' => __('Language Menu', 'qtranslate') );

	class qtranxcLangSwItems {
		public $db_id = 0;
		public $object = 'qtranslangsw';
		public $object_id;
		public $menu_item_parent = 0;
		public $type = 'custom';
		public $title;
		public $url;
		public $target = '';
		public $attr_title = '';
		public $classes = array();
		public $xfn = '';
	}

	$elems_obj = array();
	foreach ( $elems as $value => $title ) {
		$elems_obj[$title] = new qtranxcLangSwItems();
		$elems_obj[$title]->object_id	= esc_attr( $value );
		$elems_obj[$title]->title		= esc_attr( $title );
		$elems_obj[$title]->url			= esc_attr( $value );
	}

	$walker = new Walker_Nav_Menu_Checklist();
/* Language menu items
.qtranxs-lang-menu
{
	//background-position: top left;
	background-position-y: 8px;
	padding-left: 22px;
}

.qtranxs-lang-menu-item
{
	background-position: center left;
	//background-position-x: 5px;
	//background-position-y:50%;
	padding-left: 22px;
}
*/
?>
<div id="qtranxs-langsw" class="qtranxslangswdiv">
	<div id="tabs-panel-qtranxs-langsw-all" class="tabs-panel tabs-panel-view-all tabs-panel-active">
		<ul id="qtranxs-langswchecklist" class="list:qtranxs-langsw categorychecklist form-no-clear">
			<?php echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $elems_obj ), 0, (object)array( 'walker' => $walker ) ); ?>
		</ul>
	</div>
	<span class="list-controls hide-if-no-js">
		<a href="javascript:void(0);" class="help" onclick="jQuery( '#help-login-links' ).toggle();"><?php _e( 'Help', 'qtranslate'); ?></a>
		<span class="hide-if-js" id="help-login-links"><p><a name="help-login-links"></a>
		<?php printf(__('Menu item added is replaced with a sub-menu of available languages when menu is rendered. Depending on how your theme renders menu you may need to override and customize css entries %s and %s, originally defined in %s. The field "URL" of inserted menu item allows additional configuration described in %sFAQ%s.', 'qtranslate' ), '.qtranxs-lang-menu', '.qtranxs-lang-menu-item', 'qtranslate.css', '<a href="https://wordpress.org/plugins/qtranslate-x/faq" target="blank">','</a>');?></p>
		</span>
	</span>
	<p class="button-controls">
		<span class="add-to-menu">
			<input type="submit"<?php disabled( $nav_menu_selected_id, 0 ); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e('Add to Menu', 'qtranslate'); ?>" name="add-qtranxs-langsw-menu-item" id="submit-qtranxs-langsw" />
			<span class="spinner"></span>
		</span>
	</p>
</div>
<?php
}

function qtranxf_add_nav_menu_metabox()
{
	add_meta_box( 'add-qtranxs-language-switcher', __( 'Language Switcher', 'qtranslate'), 'qtranxf_nav_menu_metabox', 'nav-menus', 'side', 'default' );
}

function qtranxf_add_language_menu( $wp_admin_bar )
{
	global $q_config;
	if ( !is_admin() || !is_admin_bar_showing() )
		return;

	$wp_admin_bar->add_menu( array(
			'id'   => 'language',
			'parent' => 'top-secondary',
			//'meta' => array('class'),
			'title' => $q_config['language_name'][$q_config['language']]
		)
	);

	foreach($q_config['enabled_languages'] as $language)
	{
		$wp_admin_bar->add_menu( 
			array
			(
				'id'	 => $language,
				'parent' => 'language',
				'title'  => $q_config['language_name'][$language],
				'href'   => add_query_arg('lang', $language)
			)
		);
	}
}

function qtranxf_links($links, $file){ // copied from Sociable Plugin
	//Static so we don't call plugin_basename on every plugin row.
	static $this_plugin;
	if (!$this_plugin) $this_plugin = plugin_basename(dirname(__FILE__).'/qtranslate.php');

	if ($file == $this_plugin){
		$settings_link = '<a href="options-general.php?page=qtranslate-x">' . __('Settings', 'qtranslate') . '</a>';
		array_unshift( $links, $settings_link ); // before other links
	}
	return $links;
}
add_filter('plugin_action_links', 'qtranxf_links', 10, 2);

add_action('admin_head-nav-menus.php', 'qtranxf_add_nav_menu_metabox');
add_action('admin_menu', 'qtranxf_adminMenu');
add_action('admin_bar_menu', 'qtranxf_add_language_menu', 999);
add_action('wp_before_admin_bar_render', 'qtranxf_fixAdminBar');
