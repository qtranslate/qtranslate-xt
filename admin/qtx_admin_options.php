<?php

function qtranxf_admin_set_default_options(&$ops)
{
	//options processed in a standardized way
	$ops['admin'] = array();

	$ops['admin']['int']=array(
		'editor_mode' => QTX_EDITOR_MODE_LSB,
		'highlight_mode' => QTX_HIGHLIGHT_MODE_LEFT_BORDER,
	);

	$ops['admin']['bool']=array(
		'auto_update_mo' => true,// automatically update .mo files
		//'plugin_js_composer_off' => false
	);

	//single line options
	$ops['admin']['str']=array(
		'lsb_style' => 'Simple_Buttons.css',
		'lsb_style_wrap_class' => 'qtranxf_default_lsb_style_wrap_class',
		'lsb_style_active_class' => 'qtranxf_default_lsb_style_active_class',
	);

	//multi-line options
	$ops['admin']['text']=array(
		'highlight_mode_custom_css' => null, // qtranxf_get_admin_highlight_css
	);

	$ops['admin']['array']=array(
		'custom_fields' => array(),
		'custom_field_classes' => array(),
		'custom_pages' => array(),
		'post_type_excluded' => array(),
	);

	//options processed in a special way
}

function qtranxf_admin_loadConfig()
{
	global $q_config, $qtranslate_options;
	qtranxf_admin_set_default_options($qtranslate_options);

	foreach($qtranslate_options['admin']['int'] as $nm => $def){
		qtranxf_load_option($nm, $def);
	}

	foreach($qtranslate_options['admin']['bool'] as $nm => $def){
		qtranxf_load_option_bool($nm,$def);
	}

	foreach($qtranslate_options['admin']['str'] as $nm => $def){
		qtranxf_load_option($nm, $def);
	}

	foreach($qtranslate_options['admin']['text'] as $nm => $def){
		qtranxf_load_option($nm, $def);
	}

	foreach($qtranslate_options['admin']['array'] as $nm => $def){
		qtranxf_load_option_array($nm,$def);
	}

	// Set Admin Sections Names
/*
	$admin_sections['general'] = array( 'name' => __('General Settings', 'qtranslate') );
	$admin_sections['advanced'] = array( 'name' => __('Advanced Settings', 'qtranslate') );
	$admin_sections['integration'] = array( 'name' => __('Custom Integration', 'qtranslate') );
	$admin_sections['import'] = array( 'name' => __('Import', 'qtranslate').'/'.__('Export', 'qtranslate') );
	$admin_sections['languages'] = array( 'name' => __('Languages', 'qtranslate') );
*/
	$q_config['admin_sections'] = array();
	$admin_sections = &$q_config['admin_sections'];
	$admin_sections['general'] = __('General Settings', 'qtranslate');
	$admin_sections['advanced'] = __('Advanced Settings', 'qtranslate');
	$admin_sections['integration'] = __('Custom Integration', 'qtranslate');
	$admin_sections['import'] = __('Import', 'qtranslate').'/'.__('Export', 'qtranslate');

	do_action('qtranslate_admin_loadConfig');

	$admin_sections['languages'] = __('Languages', 'qtranslate');//always last section

	qtranxf_add_admin_filters();
}
//add_action('qtranslate_loadConfig','qtranxf_admin_loadConfig');

function qtranxf_reset_config()
{
	global $qtranslate_options;

	if(!current_user_can('manage_options')) return;

	if(isset($_POST['qtranslate_reset_admin_notices'])){
		delete_option('qtranslate_admin_notices');
	}

	if( !isset($_POST['qtranslate_reset']) || !isset($_POST['qtranslate_reset2']) )
		return;

	// reset all settings
	foreach($qtranslate_options['front'] as $ops){ foreach($ops as $nm => $def){ delete_option('qtranslate_'.$nm); } }
	foreach($qtranslate_options['admin'] as $ops){ foreach($ops as $nm => $def){ delete_option('qtranslate_'.$nm); } }
	foreach($qtranslate_options['default_value'] as $nm => $def){ delete_option('qtranslate_'.$nm); }
	foreach($qtranslate_options['languages'] as $nm => $opn){ delete_option($opn); }

	//internal private options not loaded by default
	delete_option('qtranslate_next_update_mo');
	delete_option('qtranslate_next_thanks');

	// obsolete options
	delete_option('qtranslate_plugin_js_composer_off');
	delete_option('qtranslate_widget_css');
	delete_option('qtranslate_version');
	delete_option('qtranslate_disable_header_css');

	if(isset($_POST['qtranslate_reset3'])) {
		delete_option('qtranslate_term_name');
		if(isset($_POST['qtranslate_reset4'])){//not implemented yet
			delete_option('qtranslate_version_previous');
			//and delete translations in posts
		}
	}
	remove_filter('locale', 'qtranxf_localeForCurrentLanguage',99);
	qtranxf_reloadConfig();
	add_filter('locale', 'qtranxf_localeForCurrentLanguage',99);
}
add_action('qtranslate_saveConfig','qtranxf_reset_config',20);

function qtranxf_update_option( $nm, $default_value=null ) {
	global $q_config;
	if( !isset($q_config[$nm]) || $q_config[$nm] === '' ){
		delete_option('qtranslate_'.$nm);
		return;
	}
	if(!is_null($default_value)){
		if(is_string($default_value) && function_exists($default_value)){
			$default_value = call_user_func($default_value);
		}
		if( $default_value===$q_config[$nm] ){
			delete_option('qtranslate_'.$nm);
			return;
		}
	}
	update_option('qtranslate_'.$nm, $q_config[$nm]);
}

function qtranxf_update_option_bool( $nm, $default_value=null ) {
	global $q_config, $qtranslate_options;
	if( !isset($q_config[$nm]) ){
		delete_option('qtranslate_'.$nm);
		return;
	}
	if(is_null($default_value)){
		if(isset($qtranslate_options['default_value'][$nm])){
			$default_value = $qtranslate_options['default_value'][$nm];
		}elseif(isset($qtranslate_options['front']['bool'][$nm])){
			$default_value = $qtranslate_options['front']['bool'][$nm];
		}
	}
	if( !is_null($default_value) && $default_value === $q_config[$nm] ){
		delete_option('qtranslate_'.$nm);
	}else{
		update_option('qtranslate_'.$nm, $q_config[$nm]?'1':'0');
	}
}

// saves entire configuration - it should be in admin only?
function qtranxf_saveConfig() {
	global $q_config, $qtranslate_options;

	qtranxf_update_option('default_language');
	qtranxf_update_option('enabled_languages');

	foreach($qtranslate_options['front']['int'] as $nm => $def){
		qtranxf_update_option($nm,$def);
	}

	foreach($qtranslate_options['front']['bool'] as $nm => $def){
		qtranxf_update_option_bool($nm,$def);
	}
	qtranxf_update_option_bool('qtrans_compatibility');
	qtranxf_update_option_bool('disable_client_cookies');

	foreach($qtranslate_options['front']['str'] as $nm => $def){
		qtranxf_update_option($nm,$def);
	}

	foreach($qtranslate_options['front']['text'] as $nm => $def){
		qtranxf_update_option($nm,$def);
	}

	foreach($qtranslate_options['front']['array'] as $nm => $def){
		qtranxf_update_option($nm,$def);
	}
	qtranxf_update_option('domains');

	update_option('qtranslate_ignore_file_types', implode(',',$q_config['ignore_file_types']));

	qtranxf_update_option('flag_location',qtranxf_flag_location_default());

	//if($q_config['filter_options_mode'] == QTX_FILTER_OPTIONS_LIST)
	qtranxf_update_option('filter_options',explode(' ',QTX_FILTER_OPTIONS_DEFAULT));

	//$qtranslate_options['languages'] are updated in a special way: look for _GET['edit'], $_GET['delete'], $_GET['enable'], $_GET['disable']

	qtranxf_update_option('term_name');//uniquely special case


	//save admin options

	foreach($qtranslate_options['admin']['int'] as $nm => $def){
		qtranxf_update_option($nm,$def);
	}

	foreach($qtranslate_options['admin']['bool'] as $nm => $def){
		qtranxf_update_option_bool($nm,$def);
	}

	foreach($qtranslate_options['admin']['str'] as $nm => $def){
		qtranxf_update_option($nm,$def);
	}

	foreach($qtranslate_options['admin']['text'] as $nm => $def){
		qtranxf_update_option($nm,$def);
	}

	foreach($qtranslate_options['admin']['array'] as $nm => $def){
		qtranxf_update_option($nm,$def);
	}

	do_action('qtranslate_saveConfig');
}

function qtranxf_reloadConfig() {
	global $q_config;
	$url_info = isset($q_config['url_info']) ? $q_config['url_info'] : null;
	//qtranxf_dbg_log('qtranxf_reloadConfig: $url_info: ',$url_info);
	qtranxf_del_admin_filters();
	qtranxf_loadConfig();
	qtranxf_admin_loadConfig();
	if($url_info){
		$q_config['url_info'] = $url_info;
		if(isset($q_config['url_info']['language'])){
			$q_config['language'] = $q_config['url_info']['language'];
		}
		if(!qtranxf_isEnabled($q_config['language'])){
			$q_config['language'] = $q_config['default_language'];
		}
		//qtranxf_dbg_log('qtranxf_reloadConfig: $q_config[language]: ',$q_config['language']);
	}
	qtranxf_load_option_qtrans_compatibility();
}

function qtranxf_updateSetting($var, $type = QTX_STRING, $def = null) {
	global $q_config, $qtranslate_options;
	if(!isset($_POST['submit'])) return false;
	if(!isset($_POST[$var]) && $type != QTX_BOOLEAN) return false;

	if(is_null($def) && isset($qtranslate_options['default_value'][$var])){
		$def = $qtranslate_options['default_value'][$var];
	}
	if(is_string($def) && function_exists($def)){
		$def = call_user_func($def);
	}
	switch($type) {
		case QTX_URL:
		case QTX_LANGUAGE:
		case QTX_STRING:
			$val = sanitize_text_field($_POST[$var]);
			if($type == QTX_URL) $val = trailingslashit($val);
			else if($type == QTX_LANGUAGE && !qtranxf_isEnabled($val)) return false;
			if(isset($q_config[$var])){
				if($q_config[$var] === $val) return false;
			}elseif(!is_null($def)){
				if(empty($val) || $def === $val) return false;
			}
			if(empty($val) && $def) $val = $def;
			$q_config[$var] = $val;
			qtranxf_update_option($var, $def);
			return true;
		case QTX_TEXT:
			$val = $_POST[$var];
			//standardize multi-line string
			$lns = preg_split('/\r?\n\r?/',$val);
			foreach($lns as $key => $ln){
				$lns[$key] = sanitize_text_field($ln);
			}
			$val = implode(PHP_EOL,$lns);
			//qtranxf_dbg_log('qtranxf_updateSetting:QTX_TEXT: $_POST[$var]:'.PHP_EOL, $_POST[$var]);
			//qtranxf_dbg_log('qtranxf_updateSetting:QTX_TEXT: $val:'.PHP_EOL, $val);
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
			if(is_array($_POST[$var])){
				$val = $_POST[$var];
			}else{
				$val = sanitize_text_field($_POST[$var]);
				$val=preg_split('/[\s,]+/',$val,null,PREG_SPLIT_NO_EMPTY);
			}
			if( isset($q_config[$var]) && qtranxf_array_compare($q_config[$var],$val) ) return false;
			$q_config[$var] = $val;
			qtranxf_update_option($var, $def);
			return true;
		case QTX_BOOLEAN:
			if( isset($_POST[$var]) && $_POST[$var]==1 ) {
				if($q_config[$var]) return false;
				$q_config[$var] = true;
			} else {
				if(!$q_config[$var]) return false;
				$q_config[$var] = false;
			}
			qtranxf_update_option_bool($var, $def);
			return true;
		case QTX_INTEGER:
			$val = sanitize_text_field($_POST[$var]);
			$val = intval($val);
			if($q_config[$var] == $val) return false;
			$q_config[$var] = $val;
			qtranxf_update_option($var, $def);
			return true;
	}
	return false;
}

function qtranxf_updateSettingFlagLocation($nm) {
	global $q_config;
	if(!isset($_POST['submit'])) return false;
	if(!isset($_POST[$nm])) return false;
	$flag_location=untrailingslashit(sanitize_text_field($_POST[$nm]));
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
	$posted=preg_split('/[\s,]+/',strtolower(sanitize_text_field($_POST[$nm])),null,PREG_SPLIT_NO_EMPTY);
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

function qtranxf_parse_post_type_excluded() {
	global $q_config;
	if(!isset($_POST['submit'])) return false;
	$post_types = get_post_types();
	if(isset($_POST['post_types'])){
		//qtranxf_dbg_log('qtranxf_conf: $_POST[post_types]: ',$_POST['post_types']);
		if(!is_array($_POST['post_types'])) return false;
		$post_type_excluded = array();
		foreach ( $post_types as $post_type ) {
			if(!qtranxf_post_type_optional($post_type)) continue;
			if(isset($_POST['post_types'][$post_type])) continue;
			$post_type_excluded[] = $post_type;
		}
	}else{
		//qtranxf_dbg_log('qtranxf_conf: $_POST[post_types] is not set');
		$post_type_excluded = $post_types;
	}
	unset($_POST['post_types']);
	$_POST['post_type_excluded'] = $post_type_excluded;
	//qtranxf_dbg_log('qtranxf_conf: $_POST[post_type_excluded]: ',$_POST['post_type_excluded']);
}

function qtranxf_updateSettings()
{
	global $qtranslate_options, $q_config;
	// update front settings

	qtranxf_updateSetting('default_language', QTX_LANGUAGE);
	//enabled_languages are not changed at this place

	qtranxf_updateSettingFlagLocation('flag_location');
	qtranxf_updateSettingIgnoreFileTypes('ignore_file_types');

	foreach($qtranslate_options['front']['int'] as $nm => $def){
		qtranxf_updateSetting($nm, QTX_INTEGER, $def);
	}

	foreach($qtranslate_options['front']['bool'] as $nm => $def){
		qtranxf_updateSetting($nm, QTX_BOOLEAN, $def);
	}
	qtranxf_updateSetting('qtrans_compatibility', QTX_BOOLEAN);

	foreach($qtranslate_options['front']['str'] as $nm => $def){
		qtranxf_updateSetting($nm, QTX_STRING, $def);
	}

	foreach($qtranslate_options['front']['text'] as $nm => $def){
		qtranxf_updateSetting($nm, QTX_TEXT, $def);
	}

	foreach($qtranslate_options['front']['array'] as $nm => $def){
		qtranxf_updateSetting($nm, QTX_ARRAY, $def);
	}
	qtranxf_updateSetting('filter_options', QTX_ARRAY);

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

	// update admin settings

	//special cases handling
	if($_POST['highlight_mode'] != QTX_HIGHLIGHT_MODE_CUSTOM_CSS){
		$_POST['highlight_mode_custom_css'] = '';
	}
	if($_POST['lsb_style'] != $q_config['lsb_style']){
		$_POST['lsb_style_wrap_class'] = '';
		$_POST['lsb_style_active_class'] = '';
	}

	//if(!(isset($_POST['plugin_js_composer']) && $_POST['plugin_js_composer']=='1')){
	//	$_POST['plugin_js_composer_off'] = '1';
	//}

	qtranxf_parse_post_type_excluded();

	foreach($qtranslate_options['admin']['int'] as $nm => $def){
		qtranxf_updateSetting($nm, QTX_INTEGER, $def);
	}

	foreach($qtranslate_options['admin']['bool'] as $nm => $def){
		qtranxf_updateSetting($nm, QTX_BOOLEAN, $def);
	}

	foreach($qtranslate_options['admin']['str'] as $nm => $def){
		qtranxf_updateSetting($nm, QTX_STRING, $def);
	}

	foreach($qtranslate_options['admin']['text'] as $nm => $def){
		qtranxf_updateSetting($nm, QTX_TEXT, $def);
	}

	foreach($qtranslate_options['admin']['array'] as $nm => $def){
		qtranxf_updateSetting($nm, QTX_ARRAY, $def);
	}
}
