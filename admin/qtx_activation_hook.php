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

function qtranxf_version_int() {
	$ver = str_replace('.','',QTX_VERSION);
	while(strlen($ver) < 5) $ver.='0';
	return intval($ver);
}

/**
 * since 3.2.9.2
 */
function qtranxf_default_enabled_languages()
{
	//$locale = defined('WPLANG') ? WPLANG : get_option('WPLANG','en_US');
	$locale = get_locale();
	if(!$locale) $locale = 'en_US';
	$lang = null;
	$locales = qtranxf_default_locale();
	foreach($locales as $ln => $lo){
		if($lo != $locale) continue;
		$lang = $ln;
		break;
	}
	if(!$lang) $lang = substr($locale,0,2);
	if(!qtranxf_language_predefined($lang)){
		$langs = array();
		$langs['language_name'][$lang] = 'Unknown';
		$langs['flag'][$lang] = 'us.png';
		$langs['locale'][$lang] = $locale;
		$langs['date_format'][$lang] = '%A %B %e%q, %Y';
		$langs['time_format'][$lang] = '%I:%M %p';
		$langs['not_available'][$lang] = 'Sorry, this entry is only available in %LANG:, : and %.';
		qtranxf_save_languages($langs);
	}
	//qtranxf_dbg_log('qtranxf_default_enabled_languages: $lang='.$lang.' $locale:',$locale);
	return array($lang, $lang != 'en' ? 'en' : 'de');
	//return array( 'de', 'en', 'zh' );
}

/**
 * since 3.2.9.2
 */
function qtranxf_default_default_language()
{
	$enabled_languages = qtranxf_default_enabled_languages();
	$default_language = $enabled_languages[0];
	update_option('qtranslate_enabled_languages',$enabled_languages);
	update_option('qtranslate_default_language',$default_language);
	return $default_language;
}

function qtranxf_admin_notice_first_install(){

}

function qtranxf_admin_notice_deactivate_plugin($nm,$plugin)
{
	deactivate_plugins($plugin,true);
	$d=dirname($plugin);
	$link='<a href="https://wordpress.org/plugins/'.$d.'/" target="_blank">'.$nm.'</a>';
	$qtxnm='qTranslate&#8209;X';
	$qtxlink='<a href="https://wordpress.org/plugins/qtranslate-x/" target="_blank">'.$qtxnm.'</a>';
	$imported=false;
	//if($first_install){
	$f='qtranxf_migrate_import_'.str_replace('-','_',dirname($plugin));
	if(function_exists($f)){
		global $wpdb;
		$options = $wpdb->get_col("SELECT `option_name` FROM {$wpdb->options} WHERE `option_name` LIKE 'qtranslate_%'");
		if(empty($options)){
			$f();
			$imported=true;
		}
	}
	//}
	$s = '</p><p>'.sprintf(__('It might be a good idea to review %smigration instructions%s, if you have not yet done so.', 'qtranslate'),'<a href="https://qtranslatexteam.wordpress.com/2015/02/24/migration-from-other-multilingual-plugins/" target="_blank">','</a>').'</p><p><a class="button" href="">';
	$msg=sprintf(__('Activation of plugin %s deactivated plugin %s since they cannot run simultaneously.', 'qtranslate'), $qtxlink, $link).' ';
	if($imported){
		$msg.=sprintf(__('The compatible settings from %s have been imported to %s. Further tuning, import, export and reset of options can be done at Settings/Languages configuration page, once %s is running.%sContinue%s', 'qtranslate'), $nm, $qtxnm, $qtxnm, $s, '</a>');
	}else{
		$msg.=sprintf(__('You may import/export compatible settings from %s to %s on Settings/Languages configuration page, once %s is running.%sContinue%s', 'qtranslate'), $nm, $qtxnm, $qtxnm, $s, '</a>');
	}
	//$nonce=wp_create_nonce('deactivate-plugin_'.$plugin);
	//$msg=sprintf(__('Plugin %s cannot run concurrently with %s, please %sdeactivate %s%s. You may import compatible settings from %s to %s on Settings/Languages configuration page, once %s is running.','qtranslate'),$qtxlink,$link,'<a href="'.admin_url('plugins.php?action=deactivate&plugin='.encode($plugin).'&plugin_status=all&paged=1&s&_wpnonce='.$nonce.'">',$nm,'</a>',$nm,$qtxnm,$qtxnm);
	//$msg=sprintf(__('Activation of plugin %s deactivated plugin %s since they cannot run simultaneously. You may import compatible settings from %s to %s on Settings/%sLanguages%s configuration page, once %s is running.%sContinue%s','qtranslate'),$qtxlink,$link,$nm,$qtxnm,'<a href="'.admin_url('/options-general.php?page=qtranslate-x').'">','</a>',$qtxnm,'</p><p><a  class="button" href="">','</a>');
	wp_die('<p>'.$msg.'</p>');
}

function qtranxf_activation_hook()
{
	//global $q_config;

	// Check if other qTranslate forks are activated.
	if ( is_plugin_active( 'mqtranslate/mqtranslate.php' ) )
		qtranxf_admin_notice_deactivate_plugin('mqTranslate','mqtranslate/mqtranslate.php');

	if ( is_plugin_active( 'qtranslate/qtranslate.php' ) )
		qtranxf_admin_notice_deactivate_plugin('qTranslate','qtranslate/qtranslate.php');

	if ( is_plugin_active( 'qtranslate-xp/ppqtranslate.php' ) )
		qtranxf_admin_notice_deactivate_plugin('qTranslate Plus','qtranslate-xp/ppqtranslate.php');

	if ( is_plugin_active( 'ztranslate/ztranslate.php' ) )
		qtranxf_admin_notice_deactivate_plugin('zTranslate','ztranslate/ztranslate.php');

	//deactivate_plugins(plugin_basename(__FILE__)); // Deactivate ourself

	$default_language = get_option('qtranslate_default_language');
	$first_install = $default_language===false;
	if($first_install){
		qtranxf_default_default_language();
		$ver = qtranxf_version_int();
		update_option('qtranslate_version_previous', $ver);
		add_action('admin_notices', 'qtranxf_admin_notice_first_install');
	}else{
		$ver = get_option('qtranslate_version_previous');
		if(!$ver) update_option('qtranslate_version_previous', 29000);
	}

	$f=WP_CONTENT_DIR.'/debug-qtranslate.log';
	if(file_exists($f)) unlink($f);
}

function qtranxf_admin_notices_version()
{
	$ver_cur = qtranxf_version_int();
	$ver_prv = get_option('qtranslate_version_previous',$ver_cur);
	if($ver_cur == $ver_prv) return;
	if($ver_prv < 33000 && $ver_cur >= 32980) qtranxf_admin_notices_new_options(array('Highlight Style','LSB Style'),'3.3','https://qtranslatexteam.wordpress.com/2015/03/30/release-notes-3-3');
}
add_action('admin_notices', 'qtranxf_admin_notices_version');

/*
function qtranxf_admin_notice_deactivated($plugin)
{
	$plugin_file=WP_CONTENT_DIR.'/plugins/'.$plugin;
	$plugin_data=get_plugin_data( plugin_file, false, true );
echo "qtranxf_admin_notice_deactivated: $plugin";
var_dump($plugin_data);
	if(!$plugin_data) return;
	$nm='<a href="https://wordpress.org/plugins/'.dirname($plugin).'/">'.$plugin_data['Name'].'</a>';
	echo printf(__('Plugin qTranslate&#8209;X deactivated plugin %s since they cannot run simultaneously. You may import compatible settings from %s to qTranslate&#8209;X on Settings/"<a href="%s">Languages</a>" configuration page.','qtranslate'),$nm,$nm,admin_url('options-general.php?page=qtranslate-x'));
}

function qtranxf_admin_notices($nm)
{
	//if($_SERVER['REQUEST_METHOD']!='GET') return;
	if(isset($_REQUEST['qtx_dismiss'])){
		update_option('qtranslate_admin_notices',array());
		return;
	}
	$admin_notices=get_option('qtranslate_admin_notices',array());
	if(empty($admin_notices)) return;
	//echo '<div class="updated">';
	echo '<div class="update-nag">';
	echo '<div style="float: right"><a href="?qtx_dismiss"><small>dismiss</small></a></div>';
	foreach($admin_notices as $key=>$notice){
		echo '<p>';
		switch($key){
			case 'mqtranslate/mqtranslate.php':
			case 'qtranslate/qtranslate.php':
			case 'qtranslate-xp/ppqtranslate.php':
			case 'ztranslate/ztranslate.php':
				qtranxf_admin_notice_deactivated($key);
				break;
			default: echo $notice; break;
		}
		echo '</p>';
	}
	echo '</div>';
}

function qtranxf_check_qtranslate_other()
{
	// Check if other qTranslate forks are active.
	$plugins=array();
	if(is_plugin_active('mqtranslate/mqtranslate.php')) $plugins[]='qtranslate-xp/ppqtranslate.php';
	if(is_plugin_active('qtranslate/qtranslate.php')) $plugins[]='qtranslate/qtranslate.php';
	if(is_plugin_active('qtranslate-xp/ppqtranslate.php')) $plugins[]='qtranslate-xp/ppqtranslate.php';
	if(is_plugin_active('ztranslate/ztranslate.php')) $plugins[]='ztranslate/ztranslate.php';
	if(empty($plugins)) return;
	$admin_notices=get_option('qtranslate_admin_notices',array());
	$t=time();
	foreach($plugins as $plugin){
		$admin_notices[$plugin]=$t;
	}
	deactivate_plugins($plugins,true);
	add_action('admin_notices', 'qtranxf_admin_notices');
}
//muplugins_loaded plugins_loaded
//add_action('admin_init', 'qtranxf_check_qtranslate_other', 0);
*/

function qtranxf_admin_notice_plugin_conflict($title,$plugin)
{
	if(!is_plugin_active($plugin)) return;
	$me=qtranxf_get_plugin_link();
	$link='<a href="https://wordpress.org/plugins/'.dirname($plugin).'/" style="color:magenta" target="_blank">'.$title.'</a>';
	echo '<div class="error"><p style="font-size: larger">';
	printf(__('%sError:%s plugin %s cannot run concurrently with plugin %s. You may import and export compatible settings between %s and %s on Settings/<a href="%s">Languages</a> configuration page. Then you have to deactivate one of the plugins to continue.','qtranslate'),'<span style="color:red"><strong>','</strong></span>',$me,$link,'qTranslate&#8209;X',$title,admin_url('options-general.php?page=qtranslate-x'), 'qtranslate');
	echo ' ';
	printf(__('It might be a good idea to review %smigration instructions%s, if you have not yet done so.', 'qtranslate'),'<a href="https://qtranslatexteam.wordpress.com/2015/02/24/migration-from-other-multilingual-plugins/" target="_blank">','</a>');

	$nonce=wp_create_nonce('deactivate-plugin_'.$plugin);
	echo '</p><p> &nbsp; &nbsp; &nbsp; &nbsp;<a class="button" href="'.admin_url('plugins.php?action=deactivate&plugin='.urlencode($plugin).'&plugin_status=all&paged=1&s&_wpnonce='.$nonce).'"><strong>'.sprintf(__('Deactivate %s', 'qtranslate'), '<span style="color:magenta">'.$title.'</span>').'</strong></a>';
	$nonce=wp_create_nonce('deactivate-plugin_qtranslate-x/qtranslate.php');
	echo ' &nbsp; &nbsp; &nbsp; &nbsp;<a class="button" href="'.admin_url('plugins.php?action=deactivate&plugin='.urlencode('qtranslate-x/qtranslate.php').'&plugin_status=all&paged=1&s&_wpnonce='.$nonce).'"><strong>'.sprintf(__('Deactivate %s', 'qtranslate'), '<span style="color:blue">qTranslate&#8209;X</span>').'</strong></a>';
	echo '</p></div>';
}

function qtranxf_admin_notices_plugin_conflicts()
{
	qtranxf_admin_notice_plugin_conflict('qTranslate','qtranslate/qtranslate.php');
	qtranxf_admin_notice_plugin_conflict('mqTranslate','mqtranslate/mqtranslate.php');
	qtranxf_admin_notice_plugin_conflict('qTranslate Plus','qtranslate-xp/ppqtranslate.php');
	qtranxf_admin_notice_plugin_conflict('zTranslate','ztranslate/ztranslate.php');
}
add_action('admin_notices', 'qtranxf_admin_notices_plugin_conflicts');

function qtranxf_get_plugin_link()
{
	return '<a href="https://wordpress.org/plugins/qtranslate-x/" style="color:blue" target="_blank">qTranslate&#8209;X</a>';
}

function qtranxf_admin_notice_plugin_integration($plugin,$integr_title,$integr_plugin)
{
	if(!is_plugin_active($plugin)) return 0;
	if(is_plugin_active($integr_plugin)) return 0;

	$integr_slug = dirname($integr_plugin);
	$messages = get_option('qtranslate_admin_notices');
	if(isset($messages['integration-'.$integr_slug])) return 0;

	//$plugin_file = WP_PLUGIN_DIR.'/'.$plugin;
	$plugin_file = WP_CONTENT_DIR.'/plugins/'.$plugin;
	if(!file_exists($plugin_file)) return 0;
	$pd = get_plugin_data( $plugin_file, false, true );
	$pluginName = $pd['Name'];
	$pluginURI = $pd['PluginURI'];

	$me=qtranxf_get_plugin_link();
	$plugin_link='<a href="'.$pluginURI.'/" style="color:blue" target="_blank">'.$pluginName.'</a>';
	$integr_link='<a href="https://wordpress.org/plugins/'.$integr_slug.'/" style="color:magenta" target="_blank">'.$integr_title.'</a>';

	echo '<div class="update-nag" id="qtranxs-integration-'.$integr_slug.'"><p style="font-size: larger">';
	printf(__('Plugin %s may be integrated with multilingual plugin %s with a help of plugin %s.','qtranslate'),$plugin_link,$me,$integr_link);
	echo ' ';
	echo __('Please, press an appropriate button below.','qtranslate');

	$integr_file = WP_CONTENT_DIR.'/plugins/'.$integr_plugin;
	if(file_exists($integr_file)){
		echo '</p><p> &nbsp; &nbsp; &nbsp; &nbsp;<a class="button" href="'.esc_url( wp_nonce_url( admin_url('plugins.php?action=activate&plugin='.urlencode($integr_plugin)), 'activate-plugin_'.$integr_plugin)).'"><strong>'.sprintf(__('Activate plugin %s', 'qtranslate'), '<span style="color:magenta">'.$integr_title.'</span>').'</strong></a>';
	}else{
		echo '</p><p> &nbsp; &nbsp; &nbsp; &nbsp;<a class="button" href="'.esc_url( wp_nonce_url( admin_url('update.php?action=install-plugin&plugin='.urlencode($integr_slug)), 'install-plugin_'.$integr_slug)).'"><strong>'.sprintf(__('Install plugin %s', 'qtranslate'), '<span style="color:magenta">'.$integr_title.'</span>').'</strong></a>';
	}
	echo '&nbsp;&nbsp;&nbsp;<a class="button" href="javascript:qtranxj_dismiss_admin_notice(\'integration-'.$integr_slug.'\');">'.__('I am aware of that, dismiss this message.', 'qtranslate');
	echo '</a></p></div>';
	return 1;
}

function qtranxf_admin_notice_dismiss_script()
{
?>
<script type="text/javascript">
	function qtranxj_dismiss_admin_notice(id) {
		jQuery('#qtranxs-'+id).css('display','none');
		jQuery.post(ajaxurl, { action: 'qtranslate_admin_notice', notice_id: id }
		//,function(response) { eval(response); }
		);
	}
</script>
<?php
}

function qtranxf_admin_notices_plugin_integration()
{
	global $pagenow;
	if($pagenow == 'update.php') return;
	$cnt = 0;

	$cnt += qtranxf_admin_notice_plugin_integration('advanced-custom-fields/acf.php', 'ACF qTranslate', 'acf-qtranslate/acf-qtranslate.php');

	$cnt += qtranxf_admin_notice_plugin_integration('all-in-one-seo-pack/all_in_one_seo_pack.php', 'All in One SEO Pack & qTranslate&#8209;X', 'all-in-one-seo-pack-qtranslate-x/qaioseop.php');

	$cnt += qtranxf_admin_notice_plugin_integration('events-made-easy/events-manager.php', 'Events Made Easy & qTranslate&#8209;X', 'events-made-easy-qtranslate-x/events-made-easy-qtranslate-x.php');

	$cnt += qtranxf_admin_notice_plugin_integration('gravity-forms-addons/gravity-forms-addons.php', 'qTranslate support for GravityForms', 'qtranslate-support-for-gravityforms/qtranslate-support-for-gravityforms.php');

	$cnt += qtranxf_admin_notice_plugin_integration('woocommerce/woocommerce.php', 'WooCommerce & qTranslate&#8209;X', 'woocommerce-qtranslate-x/woocommerce-qtranslate-x.php');

	$cnt += qtranxf_admin_notice_plugin_integration('wordpress-seo/wp-seo.php', 'Wordpress SEO & qTranslate&#8209;X', 'wp-seo-qtranslate-x/wordpress-seo-qtranslate-x.php');

	if($cnt>0){
		qtranxf_admin_notice_dismiss_script();
	}
}
add_action('admin_notices', 'qtranxf_admin_notices_plugin_integration');


function qtranxf_admin_notices_survey_request()
{
	$messages = get_option('qtranslate_admin_notices');
	if(isset($messages['survey-translation-service'])) return;
	qtranxf_admin_notice_dismiss_script();
	echo '<div class="updated" id="qtranxs-survey-translation-service"><p style="font-size: larger;">';// text-align: center;
	printf(__('Thank you for using %s plugin!', 'qtranslate'), '<a href="https://wordpress.org/plugins/qtranslate-x/" style="color:blue" target="_blank">qTranslate&#8209;X</a>');
	echo '<br>';
	printf(__('Please, help us to make a decision on "%s" feature, press the button below.', 'qtranslate'), __('Translation Service', 'qtranslate'));
	echo '</p><p><a class="button" href="http://www.marius-siroen.com/qTranslate-X/TranslateServices/" target="_blank">';
	printf(__('Survey on "%s" feature', 'qtranslate'), __('Translation Service', 'qtranslate'));
	echo '</a>&nbsp;&nbsp;&nbsp;<a class="button" href="javascript:qtranxj_dismiss_admin_notice(\'survey-translation-service\');">'.__('I have already done it, dismiss this message.', 'qtranslate');
	echo '</a></p></div>';
}
add_action('admin_notices', 'qtranxf_admin_notices_survey_request');


function qtranxf_ajax_qtranslate_admin_notice()
{
	if(!isset($_POST['notice_id'])) return;
	$id = $_POST['notice_id'];
	$messages = get_option('qtranslate_admin_notices',array());
	if(!is_array($messages)) $messages = array();
	$messages[$id] = time();
	update_option('qtranslate_admin_notices',$messages);
	//echo "jQuery('#qtranxs_+$id').css('display','none');"; die();
}
add_action('wp_ajax_qtranslate_admin_notice', 'qtranxf_ajax_qtranslate_admin_notice');

function qtranxf_admin_notices_new_options($nms,$ver,$url)
{
	$messages = get_option('qtranslate_admin_notices');
	$id='new-options-ver-'.str_replace('.','',$ver);
	if(isset($messages[$id])) return;
	$me=qtranxf_get_plugin_link();
	qtranxf_admin_notice_dismiss_script();
	echo '<div class="update-nag" id="qtranxs-'.$id.'">';// style="font-size: larger"
	//echo __('One time message:', 'qtranslate'); echo ' ';
	if(!empty($nms)){
		$opns = '';
		for($i = 0; $i < sizeof($nms); ++$i){
			$nm = $nms[$i];
			if($i) $opns .= ', ';
			$opns .= '"'.__($nm, 'qtranslate').'"';
		}
		echo '<p>';
		printf(__('The latest version of plugin %s has a number of new options, for example, %s, which may change the look of some pages. Please, review the help text of new options on %sconfiguration page%s.','qtranslate'), $me, $opns, '<a href="'.admin_url('options-general.php?page=qtranslate-x').'">','</a>');
		echo '</p>';
	}
	if(!empty($url)){
		echo '<p>';
		printf(__('It is recommended to review %sRelease Notes%s for this new version of %s before making any further changes.','qtranslate'), '<a href="'.$url.'" target="_blank">','</a>',$me);
		echo '</p>';
	}
	echo '<p>&nbsp;&nbsp;&nbsp;<a class="button" href="javascript:qtranxj_dismiss_admin_notice(\''.$id.'\');">'.__('I have already done it, dismiss this message.', 'qtranslate');
	echo '</a></p></div>';
}
