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

function qtranxf_admin_notice_deactivate_plugin($nm,$plugin)
{
	deactivate_plugins($plugin,true);
	$d=dirname($plugin);
	$link='<a href="https://wordpress.org/plugins/'.$d.'/" target="_blank">'.$nm.'</a>';
	$qtxnm='qTranslate&#8209;X';
	$qtxlink='<a href="https://wordpress.org/plugins/qtranslate-x/" target="_blank">'.$qtxnm.'</a>';
	$f='qtranxf_migrate_import_'.str_replace('-','_',dirname($plugin));
	$imported=false;
	if(function_exists($f)){
		global $wpdb;
		$options = $wpdb->get_col("SELECT `option_name` FROM {$wpdb->options} WHERE `option_name` LIKE 'qtranslate_%'");
		if(empty($options)){
			$f();
			$imported=true;
		}
	}
	$msg=sprintf(__('Activation of plugin %s deactivated plugin %s since they cannot run simultaneously.','qtranslate'),$qtxlink,$link).' ';
	if($imported){
		$msg.=sprintf(__('The compatible settings from %s have been imported to %s. Further tuning, import, export and reset of options can be done at Settings/Languages configuration page, once %s is running.%sContinue%s','qtranslate'),$nm,$qtxnm,$qtxnm,'</p><p><a  class="button" href="">','</a>');
	}else{
		$msg.=sprintf(__('You may import/export compatible settings from %s to %s on Settings/Languages configuration page, once %s is running.%sContinue%s','qtranslate'),$nm,$qtxnm,$qtxnm,'</p><p><a  class="button" href="">','</a>');
	}
	//$nonce=wp_create_nonce('deactivate-plugin_'.$plugin);
	//$msg=sprintf(__('Plugin %s cannot run concurrently with %s, please %sdeactivate %s%s. You may import compatible settings from %s to %s on Settings/Languages configuration page, once %s is running.','qtranslate'),$qtxlink,$link,'<a href="'.admin_url('plugins.php?action=deactivate&plugin='.encode($plugin).'&plugin_status=all&paged=1&s&_wpnonce='.$nonce.'">',$nm,'</a>',$nm,$qtxnm,$qtxnm);
	//$msg=sprintf(__('Activation of plugin %s deactivated plugin %s since they cannot run simultaneously. You may import compatible settings from %s to %s on Settings/%sLanguages%s configuration page, once %s is running.%sContinue%s','qtranslate'),$qtxlink,$link,$nm,$qtxnm,'<a href="'.admin_url('/options-general.php?page=qtranslate-x').'">','</a>',$qtxnm,'</p><p><a  class="button" href="">','</a>');
	wp_die('<p>'.$msg.'</p>');
}

function qtranxf_activation_hook()
{
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
}

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
	$me='<a href="https://wordpress.org/plugins/qtranslate-x/" style="color:blue" target="_blank">qTranslate&#8209;X</a>';
	$link='<a href="https://wordpress.org/plugins/'.dirname($plugin).'/" style="color:magenta" target="_blank">'.$title.'</a>';
	echo '<div class="error"><p style="font-size: larger">';
	echo '<span style="color:red"><strong>'.__('Error').':</strong></span> '.sprintf(__('plugin %s cannot run concurrently with plugin %s. You may import and export compatible settings between %s and %s on Settings/<a href="%s">Languages</a> configuration page. Then you have to deactivate one of the plugins to continue.','qtranslate'),$me,$link,'qTranslate&#8209;X',$title,admin_url('options-general.php?page=qtranslate-x'));
	$nonce=wp_create_nonce('deactivate-plugin_'.$plugin);
	echo '</p><p> &nbsp; &nbsp; &nbsp; &nbsp;<a class="button" href="'.admin_url('plugins.php?action=deactivate&plugin='.urlencode($plugin).'&plugin_status=all&paged=1&s&_wpnonce='.$nonce).'"><strong>'.__('Deactivate ').'<span style="color:magenta">'.$title.'</span></strong></a>';
	$nonce=wp_create_nonce('deactivate-plugin_qtranslate-x/qtranslate.php');
	echo ' &nbsp; &nbsp; &nbsp; &nbsp;<a class="button" href="'.admin_url('plugins.php?action=deactivate&plugin='.urlencode('qtranslate-x/qtranslate.php').'&plugin_status=all&paged=1&s&_wpnonce='.$nonce).'"><strong>'.__('Deactivate ').'<span style="color:blue">qTranslate&#8209;X</span></strong></a>';
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

function qtranxf_admin_notices_survey_request()
{
	$messages = get_option('qtranslate_admin_notices');
	if(isset($messages['survey-translation-service'])) return;
?>
<script type="text/javascript">
	function qtranxj_dismiss_admin_notice(id) {
		jQuery('#qtranxs_'+id).css('display','none');
		jQuery.post(ajaxurl, { action: 'qtranslate_admin_notice', notice_id: id }
		//,function(response) { eval(response); }
		);
	}
</script>
<?php
	echo '<div class="updated" id="qtranxs_survey-translation-service"><p style="font-size: larger;">';// text-align: center;
	printf(__('Thank you for using %s plugin!','qtranslate'), '<a href="https://wordpress.org/plugins/qtranslate-x/" style="color:blue" target="_blank">qTranslate&#8209;X</a>');
	echo '<br>';
	printf(__('Please, help us to make decision on "%s" feature, press the button below.','qtranslate'),__('Translation Service', 'qtranslate'));
	echo '</p><p><a class="button" href="http://www.marius-siroen.com/qTranslate-X/TranslateServices/" target="_blank">';
	printf(__('Survey on "%s" feature','qtranslate'),__('Translation Service', 'qtranslate'));
	echo '</a>&nbsp;&nbsp;&nbsp;<a class="button" href="javascript:qtranxj_dismiss_admin_notice(\'survey-translation-service\');">'.__('I have already done it, dismiss this message.', 'qtranslate');
	echo '</a></p></div>';
}
add_action('admin_notices', 'qtranxf_admin_notices_survey_request');

function qtranxf_ajax_qtranslate_admin_notice()
{
	if(!isset($_POST['notice_id'])) return;
	$id = $_POST['notice_id'];
	$messages = get_option('qtranslate_admin_notices',array());
	$messages[$id] = time();
	update_option('qtranslate_admin_notices',$messages);
	//echo "jQuery('#qtranxs_+$id').css('display','none');"; die();
}
add_action('wp_ajax_qtranslate_admin_notice', 'qtranxf_ajax_qtranslate_admin_notice');
?>
