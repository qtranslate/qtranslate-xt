<?php
if ( !defined( 'WP_ADMIN' ) ) exit;

require_once(QTRANSLATE_DIR.'/admin/qtx_admin_options.php');
require_once(QTRANSLATE_DIR.'/admin/qtx_languages.php');
require_once(QTRANSLATE_DIR.'/admin/qtx_admin_class_translator.php');
require_once(QTRANSLATE_DIR.'/admin/qtx_user_options.php');

//if(file_exists(QTRANSLATE_DIR.'/admin/qtx_admin_slug.php'))
//	require_once(QTRANSLATE_DIR.'/admin/qtx_admin_slug.php');

//if(file_exists(QTRANSLATE_DIR.'/admin/qtx_admin_services.php'))
//	require_once(QTRANSLATE_DIR.'/admin/qtx_admin_services.php');

function qtranxf_collect_translations_deep( $qfields, $sep ) {
	$content = reset($qfields);
	//qtranxf_dbg_log('qtranxf_collect_translations_deep: $content: ',$content);
	if(is_string($content)) return qtranxf_join_texts($qfields,$sep);
	$result = array();
	foreach($content as $f => $r){
		$texts = array();
		foreach($qfields as $lang => &$vals){
			$texts[$lang] = $vals[$f];
		}
		$result[$f] = qtranxf_collect_translations_deep($texts,$sep); // recursive call
	}
	return $result;
}

function qtranxf_collect_translations( &$qfields, &$request, $edit_lang ) {
	if(isset($qfields['qtranslate-separator'])){
		$sep = $qfields['qtranslate-separator'];
		unset($qfields['qtranslate-separator']);
		$qfields[$edit_lang] = $request;
		$request = qtranxf_collect_translations_deep($qfields,$sep);
	}else{
		foreach($qfields as $nm => &$vals){
			qtranxf_collect_translations($vals,$request[$nm],$edit_lang); // recursive call
		}
	}
}

function qtranxf_collect_translations_posted() {
	//qtranxf_dbg_log('qtranxf_collect_translations_posted: REQUEST: ',$_REQUEST);
	if(!isset($_REQUEST['qtranslate-fields'])) return;
	//$edit_lang = isset($_COOKIE['qtrans_edit_language']) ? $_COOKIE['qtrans_edit_language'] : qtranxf_getLanguage();
	$edit_lang = qtranxf_getLanguageEdit();
	foreach($_REQUEST['qtranslate-fields'] as $nm => &$qfields){
		//qtranxf_dbg_log('qtranxf_collect_translations_posted: REQUEST[qtranslate-fields]['.$nm.']: ',$qfields);
		qtranxf_collect_translations($qfields,$_REQUEST[$nm],$edit_lang);
		//qtranxf_dbg_log('qtranxf_collect_translations_posted: collected REQUEST['.$nm.']: ',$_REQUEST[$nm]);
		if(isset($_POST[$nm])){
			//qtranxf_dbg_log('qtranxf_collect_translations_posted: POST['.$nm.']: ',$_POST[$nm]);
			$_POST[$nm] = $_REQUEST[$nm];
		}
		if(isset($_GET[$nm])){
			//qtranxf_dbg_log('qtranxf_collect_translations_posted: GET['.$nm.']: ',$_GET[$nm]);
			$_GET[$nm] = $_REQUEST[$nm];
		}
	}
	unset($_REQUEST['qtranslate-fields']);
	unset($_POST['qtranslate-fields']);
	unset($_GET['qtranslate-fields']);
}
add_action('plugins_loaded', 'qtranxf_collect_translations_posted', 5);

function qtranxf_admin_init()
{
	global $q_config, $post, $post_type;
	//qtranxf_dbg_log('qtranxf_admin_init: REQUEST_TIME_FLOAT: ', $_SERVER['REQUEST_TIME_FLOAT']);
	qtranxf_admin_loadConfig();

	$next_thanks = get_option('qtranslate_next_thanks');
	if($next_thanks !== false && $next_thanks < time()){
		$messages = get_option('qtranslate_admin_notices');
		if(isset($messages['next_thanks'])){
			unset($messages['next_thanks']);
			update_option('qtranslate_admin_notices',$messages);
		}
		$next_thanks = false;
	}
	if($next_thanks===false){
		$next_thanks = time() + rand(100,300)*24*60*60;
		update_option('qtranslate_next_thanks', $next_thanks);
	}

	if($q_config['auto_update_mo']){
		qtranxf_updateGettextDatabases();
	}

	// update definitions if necessary
	if(current_user_can('manage_categories')){
		//qtranxf_updateTermLibrary();
		qtranxf_updateTermLibraryJoin();
	}

	$post_type = qtranxf_post_type();
	$page_config = qtranxf_get_admin_page_config($post_type);
	if(!empty($page_config)){
		qtranxf_add_filters($page_config);
	}

	add_action('admin_notices', 'qtranxf_admin_notices_config');
}
//add_action('qtranslate_init_begin','qtranxf_admin_init');
add_action('admin_init','qtranxf_admin_init');

/**
 * load field configurations for the current admin page
 */
function qtranxf_get_admin_page_config($post_type) {
	static $page_config;
	if($page_config) return $page_config;

	global $q_config, $pagenow;
	$admin_config = $q_config['admin_config'];
	$admin_config = apply_filters('qtranslate_load_admin_page_config',$admin_config);//obsolete
	$url_query = isset($q_config['url_info']['query']) ? $q_config['url_info']['query'] : '';
	/**
	 * Customize the admin configuration for all pages.
	 * @param (array) $admin_config token 'admin-config' of the configuration.
	 */
	$admin_config = apply_filters('i18n_admin_config', $admin_config);
	//qtranxf_dbg_log('qtranxf_get_admin_page_config: $admin_config: ',qtranxf_json_encode($admin_config));

	$page_config = qtranxf_parse_page_config($admin_config, $pagenow, $url_query, $post_type);

	if(!empty($page_config)){
		$page_config['js'] = array();
		if( isset($page_config['js-conf']) ){
			foreach($page_config['js-conf'] as $k => $js){
				if(!isset($js['handle'])) $js['handle'] = $k;
				$page_config['js'][] = $js;
			}
			unset($page_config['js-conf']);
		}

		$page_config['js'][] = array( 'handle' => 'qtranslate-admin-common', 'src' => './admin/js/common.min.js');

		if( isset($page_config['js-exec']) ){
			foreach($page_config['js-exec'] as $k => $js){
				if(!isset($js['handle'])) $js['handle'] = $k;
				$page_config['js'][] = $js;
			}
			unset($page_config['js-exec']);
		}

		//make src to be relative to WP_CONTENT_DIR
		$bnm = 'plugins/'.qtranxf_qtranslate_basedirname();
		foreach($page_config['js'] as $k => $js){
			if(!isset($js['src'])) continue;
			$src = $js['src'];
			if( $src[0] != '.' || $src[1] != '/') continue;
			$page_config['js'][$k]['src'] = $bnm.substr($src,1);
		}

	}

	/**
	 * Customize the $page_config for this admin request.
	 * @param (array) $page_config 'admin_config', filtered for the current page.
	 * @param (string) $pagenow value of WordPress global variable $pagenow.
	 * @param (string) $url_query query part of URL without '?', sanitized version of $_SERVER['QUERY_STRING'].
	 * @param (string) $post_type type of post serving on the current page, or null if not applicable.
	 */
	$page_config = apply_filters('i18n_admin_page_config', $page_config, $pagenow, $url_query, $post_type);
	//qtranxf_dbg_log('qtranxf_get_admin_page_config: $pagenow='.$pagenow.'; $url_query='.$url_query.'; $post_type='.$post_type.'; $page_config: ',qtranxf_json_encode($page_config));
	return $page_config;
}

function qtranxf_get_admin_page_config_LSB($post_type) {
	global $q_config, $pagenow;
	static $page_config_lsb = null;
	if(!is_null($page_config_lsb)){
		//qtranxf_dbg_log('qtranxf_get_admin_page_config_LSB: cached: '.$pagenow.'; post_type: ', $post_type);
		return $page_config_lsb;
	}
	if( $q_config['editor_mode'] == QTX_EDITOR_MODE_RAW){
		//qtranxf_dbg_log('qtranxf_get_admin_page_config_LSB: QTX_EDITOR_MODE_RAW: '.$pagenow.'; post_type: ', $post_type);
		$page_config_lsb = array();
		return $page_config_lsb;
	}
	if(!empty($q_config['post_type_excluded'])){
		switch($pagenow){
			case 'post.php':
			case 'post-new.php':
				if(in_array($post_type,$q_config['post_type_excluded'])){
					//qtranxf_dbg_log('qtranxf_get_admin_page_config_LSB: post_type_excluded: pagenow: '.$pagenow.'; post_type: ', $post_type);
					return;
				}
			default: break;
		}
	}
	//qtranxf_dbg_log('qtranxf_get_admin_page_config_LSB: pagenow: '.$pagenow.'; post_type: ', $post_type);
	$page_config_lsb = qtranxf_get_admin_page_config($post_type);
	//qtranxf_dbg_log('qtranxf_get_admin_page_config_LSB: $page_config: ', $page_config_lsb);
	return $page_config_lsb;
}

function qtranxf_add_admin_footer_js ( $enqueue_script=false ) {
	global $q_config, $post_type;
	if(!$post_type) $post_type = qtranxf_post_type();
	$page_config = qtranxf_get_admin_page_config_LSB($post_type);
	if(empty($page_config)) return;

	wp_dequeue_script('autosave');
	wp_deregister_script( 'autosave' );//autosave script saves the active language only and messes it up later in a hard way

	$config=array();
	// since 3.2.9.9.0 'enabled_languages' is replaced with 'language_config' structure
	$keys=array('default_language', 'language', 'url_mode', 'lsb_style_wrap_class', 'lsb_style_active_class'); // ,'term_name'
	foreach($keys as $key){
		$config[$key]=$q_config[$key];
	}
	$config['custom_fields'] = apply_filters('qtranslate_custom_fields', $q_config['custom_fields']);
	$config['custom_field_classes'] = apply_filters('qtranslate_custom_field_classes', $q_config['custom_field_classes']);
	if($q_config['url_mode']==QTX_URL_DOMAINS){
		$config['domains']=$q_config['domains'];
	}
	$homeinfo=qtranxf_get_home_info();
	$config['url_info_home']=trailingslashit($homeinfo['path']);//$q_config['url_info']['home'];
	$config['flag_location']=qtranxf_flag_location();
	$config['js']=array();
	//$config['flag']=array();//deprecated since 3.2.9.9.0
	//$config['language_name']=array();//deprecated since 3.2.9.9.0
	$config['language_config']=array();
	foreach($q_config['enabled_languages'] as $lang)
	{
		//$config['flag'][$lang]=$q_config['flag'][$lang];
		//$config['language_name'][$lang]=$q_config['language_name'][$lang];
		$config['language_config'][$lang]=array();
		$config['language_config'][$lang]['flag'] = $q_config['flag'][$lang];
		$config['language_config'][$lang]['name'] = $q_config['language_name'][$lang];
		$config['language_config'][$lang]['locale'] = $q_config['locale'][$lang];
	}
	if(!empty($page_config)){
		$config['page_config'] = $page_config;
		//no need for javascript:
		unset($config['page_config']['js']);
		//unset($config['page_config']['js-conf']);
		//unset($config['page_config']['js-exec']);
	}

	$config['LSB'] = $q_config['editor_mode'] == QTX_EDITOR_MODE_LSB;
	/**
	 * Last chance to customize Java script variable qTranslateConfig.
	 */
	$config = apply_filters('qtranslate_admin_page_config', $config);
?>
<script type="text/javascript">
// <![CDATA[
<?php
	echo 'var qTranslateConfig='.json_encode($config).';'.PHP_EOL;
	qtranxf_loadfiles_js($page_config['js'], $enqueue_script);
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
	if(strpos($_SERVER['REQUEST_URI'],'page=qtranslate-x') === FALSE) return;
	if($enqueue_script){
		//wp_register_script( 'qtranslate-admin-options', plugins_url( 'js/options.min.js', __FILE__ ), array(), QTX_VERSION );
		wp_enqueue_script( 'qtranslate-admin-options', plugins_url( 'js/options.min.js', __FILE__ ), array(), QTX_VERSION );
	}else{
		echo '<script type="text/javascript">'.PHP_EOL .'// <![CDATA['.PHP_EOL;
		$plugin_dir_path=plugin_dir_path(__FILE__);
		readfile($plugin_dir_path.'js/options.min.js');
		echo '//]]>'.PHP_EOL .'</script>'.PHP_EOL;
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

/**
 * Add CSS code to highlight the translatable fields */
function qtranxf_add_admin_highlight_css() {
	global $q_config;
	if ( $q_config['highlight_mode'] == QTX_HIGHLIGHT_MODE_NONE || get_the_author_meta( 'qtranslate_highlight_disabled', get_current_user_id() )) {
		return;
	}
	echo '<style type="text/css">' . PHP_EOL;
	$highlight_mode = $q_config['highlight_mode'];
	switch ( $highlight_mode ) {
		case QTX_HIGHLIGHT_MODE_CUSTOM_CSS: echo $q_config['highlight_mode_custom_css']; break;
		default: echo qtranxf_get_admin_highlight_css($highlight_mode);
	}
	echo '</style>' . PHP_EOL;
}

function qtranxf_get_admin_highlight_css($highlight_mode) {
	global $q_config;
	$current_color_scheme = qtranxf_get_user_admin_color();
	$clr = $current_color_scheme[2];
	$css = '';
	switch ( $highlight_mode ) {
		case QTX_HIGHLIGHT_MODE_LEFT_BORDER:
			$css .= 'input.qtranxs-translatable, textarea.qtranxs-translatable, div.qtranxs-translatable {' . PHP_EOL;
			//$css .= 'box-shadow: -3px 0 ' . $clr . ' !important;' . PHP_EOL; // v1
			//$css .= 'box-shadow: inset 3px 0 ' . $clr . ' !important;' . PHP_EOL;// v2
			//$css .= 'padding-left: 5px' . PHP_EOL;// v2
			$css .= 'border-left: 3px solid ' . $clr .  ' !important;' . PHP_EOL;// v3
			$css .= '}' . PHP_EOL;
			break;
		case QTX_HIGHLIGHT_MODE_BORDER:
			$css .= 'input.qtranxs-translatable, textarea.qtranxs-translatable, div.qtranxs-translatable {' . PHP_EOL;
			//$css .= 'outline: 2px solid ' . $clr . ' !important;' . PHP_EOL;// v1
			$css .= 'border: 1px solid ' . $clr .  ' !important;' . PHP_EOL;// v2
			$css .= '}' . PHP_EOL;
			//$css .= 'div.qtranxs-translatable div.mce-panel {' . PHP_EOL;
			//$css .= 'margin-top: 2px' . PHP_EOL;
			//$css .= '}' . PHP_EOL;
			break;
	}
	return $css;
}

function qtranxf_add_admin_css () {
	global $q_config;
	wp_register_style( 'qtranslate-admin-style', plugins_url('css/qtranslate_configuration.css', __FILE__), array(), QTX_VERSION );
	wp_enqueue_style( 'qtranslate-admin-style' );
	qtranxf_add_admin_lang_icons();
	qtranxf_add_admin_highlight_css();
	echo '<style type="text/css" media="screen">'.PHP_EOL;
	$fn = QTRANSLATE_DIR.'/admin/css/opLSBStyle/'.$q_config['lsb_style'];
	if(file_exists($fn)) readfile($fn);
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
	//qtranxf_dbg_log('qtranxf_admin_head:');
	//wp_enqueue_script( 'jquery' );
	//qtranxf_add_css();//Since 3.2.5 no longer needed
	qtranxf_add_admin_css();
	qtranxf_add_admin_head_js();
	//Since 3.2.7 qtranxf_optionFilter('disable');//why this is here?
}
add_action('admin_head', 'qtranxf_admin_head');

function qtranxf_admin_footer() {
	//qtranxf_dbg_log('qtranxf_admin_footer:');
	$enqueue_script = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG);
	//$enqueue_script = false;
	qtranxf_add_admin_footer_js( $enqueue_script );
}
//add_action('admin_print_footer_scripts', 'qtranxf_admin_footer',999);
add_action('admin_footer', 'qtranxf_admin_footer',999);


/** @since 3.4 */
function qtranxf_settings_page() {
	require_once(QTRANSLATE_DIR.'/admin/qtx_configuration.php');
	qtranxf_conf();
}

/* qTranslate-X Management Interface */
function qtranxf_adminMenu() {
	global $menu, $submenu, $q_config;
	// Configuration Page
	add_options_page(__('Language Management', 'qtranslate'), __('Languages', 'qtranslate'), 'manage_options', 'qtranslate-x', 'qtranxf_settings_page');
}

/* Add a metabox in admin menu page */
function qtranxf_nav_menu_metabox( $object )
{
	global $nav_menu_selected_id; 
	$nm = __('Language Menu', 'qtranslate');
	$elems = array( '#qtransLangSwLM#' => $nm );

	class qtranxcLangSwItems {
		public $db_id = 0;
		public $object = 'qtranslangsw';
		public $object_id;
		public $menu_item_parent = 0;
		public $type = 'custom';
		public $title;// = 'Language';
		public $url;
		public $target = '';
		public $attr_title = '';
		public $classes = array();
		public $xfn = '';
	}

	$elems_obj = array();
	foreach ( $elems as $value => $title ) {
		$elems_obj[$title] = new qtranxcLangSwItems();
		$obj = &$elems_obj[$title];
		$obj->object_id = esc_attr( $value );
		if(empty($obj->title)) $obj->title = esc_attr( $title );
		$obj->label = esc_attr( $title );
		$obj->url = esc_attr( $value );
	}

	$walker = new Walker_Nav_Menu_Checklist();
/* Language menu items - not used anymore
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
			<?php echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $elems_obj ), 0, (object)array( 'walker' => $walker ) ) ?>
		</ul>
	</div>
	<span class="list-controls hide-if-no-js">
		<a href="javascript:void(0);" class="help" onclick="jQuery( '#qtranxs-langsw-help' ).toggle();"><?php _e( 'Help', 'qtranslate') ?></a>
		<span class="hide-if-js" id="qtranxs-langsw-help"><p><a name="qtranxs-langsw-help"></a>
		<?php 
		echo __('Menu item added is replaced with a drop-down menu of available languages, when menu is rendered.', 'qtranslate');
		echo ' ';
		printf(__('The rendered menu items have CSS classes %s and %s ("%s" is a language code), which can be defined in theme style, if desired. The label of language menu can also be customized via field "%s" in the menu configuration.', 'qtranslate'), '.qtranxs-lang-menu, .qtranxs-lang-menu-xx, .qtranxs-lang-menu-item', '.qtranxs-lang-menu-item-xx', 'xx', qtranxf_translate_wp('Navigation Label'));
		echo ' ';
		printf(__('The field "%s" of inserted menu item allows additional configuration described in %sFAQ%s.', 'qtranslate'), qtranxf_translate_wp('URL'), '<a href="https://qtranslatexteam.wordpress.com/faq/#LanguageSwitcherMenuConfig" target="blank">','</a>'); //https://wordpress.org/plugins/qtranslate-x/faq ?></p>
		</span>
	</span>
	<p class="button-controls">
		<span class="add-to-menu">
			<input type="submit"<?php disabled( $nav_menu_selected_id, 0 ) ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e('Add to Menu', 'qtranslate') ?>" name="add-qtranxs-langsw-menu-item" id="submit-qtranxs-langsw" />
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

	if(wp_is_mobile()){
		$title = '';
	}else{
		$title = $q_config['language_name'][$q_config['language']];
	}
	
	$wp_admin_bar->add_menu( array(
			'id'   => 'language',
			'parent' => 'top-secondary',
			//'href' => 'http://example.com',
			//'meta' => array('class'),
			'title' => $title
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
	if (!$this_plugin){
		$this_plugin = plugin_basename(QTRANSLATE_FILE);
	}
	if ($file == $this_plugin){
		$settings_link = '<a href="options-general.php?page=qtranslate-x">' . __('Settings', 'qtranslate') . '</a>';
		array_unshift( $links, $settings_link ); // before other links
	}
	return $links;
}
add_filter('plugin_action_links', 'qtranxf_links', 10, 2);

function qtranxf_admin_notices_config() {
	global $q_config;
	if(isset($q_config['errors']) && is_array($q_config['errors'])){
		foreach($q_config['errors'] as $key => $msg){
			echo '<div class="error fade" id="qtranxs_error_'.$key.'"><p><a href="'.admin_url('options-general.php?page=qtranslate-x').'" style="color:magenta">qTranslate&#8209;X</a>:&nbsp;<strong><span style="color: red;">'.qtranxf_translate_wp('Error').'</span>:&nbsp;'.$msg.'</strong></p></div>';
		}
		unset($q_config['errors']);
	}
}


add_action('admin_head-nav-menus.php', 'qtranxf_add_nav_menu_metabox');
add_action('admin_menu', 'qtranxf_adminMenu');
add_action('admin_bar_menu', 'qtranxf_add_language_menu', 999);
add_action('wp_before_admin_bar_render', 'qtranxf_fixAdminBar');
