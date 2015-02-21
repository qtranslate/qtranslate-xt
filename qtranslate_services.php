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
if ( !defined( 'ABSPATH' ) ) exit;

/* qTranslate Services */

define('QTS_FAST_TIMEOUT', 10);
define('QTS_VERIFY', 'verify');
define('QTS_GET_SERVICES', 'get_services');
define('QTS_INIT_TRANSLATION', 'init_translation');
define('QTS_RETRIEVE_TRANSLATION', 'retrieve_translation');
define('QTS_QUOTE', 'quote');
define('QTS_STATE_OPEN', 'open');
define('QTS_STATE_ERROR', 'error');
define('QTS_STATE_CLOSED', 'closed');
define('QTS_ERROR_INVALID_LANGUAGE', 'QTS_ERROR_INVALID_LANGUAGE');
define('QTS_ERROR_NOT_SUPPORTED_LANGUAGE', 'QTS_ERROR_NOT_SUPPORTED_LANGUAGE');
define('QTS_ERROR_INVALID_SERVICE', 'QTS_ERROR_INVALID_SERVICE');
define('QTS_ERROR_INVALID_ORDER', 'QTS_ERROR_INVALID_ORDER');
define('QTS_ERROR_SERVICE_GENERIC', 'QTS_ERROR_SERVICE_GENERIC');
define('QTS_ERROR_SERVICE_UNKNOWN', 'QTS_ERROR_SERVICE_UNKNOWN');
define('QTS_DEBUG','QTS_DEBUG');

/** runs once on file load */
function qts_initialize()
{
	// generate public key
	global $qts_public_key;
	$qts_public_key = '-----BEGIN PUBLIC KEY-----|MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDNccmB4Up9V9+vD5kWWiE6zpRV|m7y1sdFihreycdpmu3aPjKooG5LWUbTTyc993nTxV71SKuuYdkPzu5JxniAsI2N0|7DsySZ/bQ2/BEANNwJD3pmz4NmIHgIeNaUze/tvTZq6m+FTVHSvEqAaXJIsQbO19|HeegbfEpmCj1d/CgOwIDAQAB|-----END PUBLIC KEY-----|';

	// OpenSSL functions used
	global $qts_openssl_functions_used;
	$qts_openssl_functions_used = array(
		'openssl_pkey_new',
		'openssl_pkey_export',
		'openssl_pkey_get_details',
		'openssl_seal',
		'openssl_open',
		'openssl_free_key'
		);

	// error messages
	global $qts_error_messages;
	$qts_error_messages[QTS_ERROR_INVALID_LANGUAGE] = __('The language/s do not have a valid ISO 639-1 representation.','qtranslate');
	$qts_error_messages[QTS_ERROR_NOT_SUPPORTED_LANGUAGE] = __('The language/s you used are not supported by the service.','qtranslate');
	$qts_error_messages[QTS_ERROR_INVALID_SERVICE] = __('There is no such service.','qtranslate');
	$qts_error_messages[QTS_ERROR_INVALID_ORDER] = __('The system could not process your order.','qtranslate');
	$qts_error_messages[QTS_ERROR_SERVICE_GENERIC] = __('There has been an error with the selected service.','qtranslate');
	$qts_error_messages[QTS_ERROR_SERVICE_UNKNOWN] = __('An unknown error occured with the selected service.','qtranslate');
	$qts_error_messages[QTS_DEBUG] = __('The server returned a debugging message.','qtranslate');

	// check schedule
	if (!wp_next_scheduled('qts_cron_hook')) {
		wp_schedule_event( time(), 'hourly', 'qts_cron_hook' );
	}
	qts_load();
}
qts_initialize();

// hooks
add_action('admin_menu', 'qts_init');
//add_action('qtranslate_init', 'qts_init');
add_action('qtranslate_admin_css', 'qts_css');
add_action('qts_cron_hook', 'qts_cron');
add_action('qtranslate_configuration', 'qts_config_hook', 10);
add_action('qtranslate_loadConfig', 'qts_load');
add_action('qtranslate_saveConfig', 'qts_save');
add_action('qtranslate_clean_uri', 'qts_clean_uri');
add_action('wp_ajax_qts_quote', 'qts_quote');

add_filter('manage_order_columns', 'qts_order_columns');
add_filter('qtranslate_configuration_pre', 'qts_config_pre_hook');

// serializing/deserializing functions
function qts_base64_serialize($var) {
	if(is_array($var)) {
		foreach($var as $key => $value) {
			$var[$key] = qts_base64_serialize($value);
		}
	}
	$var = serialize($var);
	$var = strtr(base64_encode($var), '-_,', '+/=');
	return $var;
}

function qts_base64_unserialize($var) {
	$var = base64_decode(strtr($var, '-_,', '+/='));
	$var = unserialize($var);
	if(is_array($var)) {
		foreach($var as $key => $value) {
			$var[$key] = qts_base64_unserialize($value);
		}
	}
	return $var;
}

// sends a encrypted message to qTranslate Services and decrypts the received data
function qts_queryQS($action, $data='', $fast = false) {
	global $qts_public_key;
	// generate new private key
	$key = openssl_pkey_new();
	openssl_pkey_export($key, $private_key);
	$public_key=openssl_pkey_get_details($key);
	$public_key=$public_key["key"];
	$message = qts_base64_serialize(array('key'=>$public_key, 'data'=>$data));
	openssl_seal($message, $message, $server_key, array($qts_public_key));
	$message = qts_base64_serialize(array('key'=>$server_key[0], 'data'=>$message));
	$data = "message=".$message;
	
	// connect to qts
	if($fast) {
		$fp = fsockopen('www.qianqin.de', 80, $errno, $errstr, QTS_FAST_TIMEOUT);
		stream_set_timeout($fp, QTS_FAST_TIMEOUT);
	} else {
		$fp = fsockopen('www.qianqin.de', 80);
	}
	if(!$fp) return false;
	
	fputs($fp, "POST /qtranslate/services/$action HTTP/1.1\r\n");
	fputs($fp, "Host: www.qianqin.de\r\n");
	fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
	fputs($fp, "Content-length: ". strlen($data) ."\r\n");
	fputs($fp, "Connection: close\r\n\r\n");
	fputs($fp, $data);
	$res = '';
	while(!feof($fp)) {
		$res .= fgets($fp, 128);
	}
	// check for timeout
	$info = stream_get_meta_data($fp);
	if($info['timed_out']) return false;
	fclose($fp);
	
	preg_match("#^Content-Length:\s*([0-9]+)\s*$#ism",$res, $match);
	if(isset($match[1])) {
		$content_length = $match[1];
		$content = substr($res, -$content_length, $content_length);
	} else {
		$content = $res;
	}
	$debug = $content;
	$content = qts_base64_unserialize($content);
	openssl_open($content['data'], $content, $content['key'], $private_key);
	if($content===false) {
		echo "<pre>DEBUG:\n";
		echo $debug;
		echo "</pre>";
	}
	openssl_free_key($key);
	return qts_cleanup(qts_base64_unserialize($content), $action);
}

function qts_clean_uri($clean_uri) {
	return preg_replace("/&(qts_delete|qts_cron)=[^&#]*/i","",$clean_uri);
}

function qts_translateButtons($available_languages, $missing_languages) {
	global $q_config, $post;
	if(sizeof($missing_languages)==0) return;
	$missing_languages_name = array();
	foreach($missing_languages as $language) {
		$missing_languages_name[] = '<a href="edit.php?page=qtranslate_services&post='.$post->ID.'&target_language='.$language.'">'.$q_config['language_name'][$language].'</a>';
	}
	$missing_languages_names = join(', ', $missing_languages_name);
	printf(__('<div>Translate to %s</div>', 'qtranslate') ,$missing_languages_names);
}

function qts_css() {
echo '
p.error {background-color:#ffebe8;border-color:#c00;border-width:1px;border-style:solid;padding:0 .6em;margin:5px 15px 2px;-moz-border-radius:3px;-khtml-border-radius:3px;-webkit-border-radius:3px;border-radius:3px;}
p.error a{color:#c00;}
#qts_boxes { margin-right:300px }
#qts_boxes .postbox h3.hndle, #submitboxcontainer .postbox h3.hndle {cursor:auto}
#qts_boxes div.inside {margin: 6px 6px 8px;}
#submitboxcontainer { float:right; width:280px }
#qts_content_preview { width:100%; height:200px }
.service_description { margin-left:20px; margin-top:0 }
#qtranslate-services h4 { margin-top:0 }
#qtranslate-services h5 { margin-bottom:0 }
#qtranslate-services .description { font-size:11px }
#qtrans_select_translate { margin-right:11px }
.qts_status { border:0 }
.qts_no-bottom-border { border-bottom:0 !important }
#submitboxcontainer p { margin:6px 6px ; }
.qts_submit { text-align:right; padding:6px }
';
}

function qts_load() {
	global $q_config, $qts_public_key;
	// qTranslate Services
	//$q_config['qtranslate_services'] = false;
	qtranxf_load_option_bool('qtranslate_services',false);
	//$qtranslate_services = get_option('qtranslate_qtranslate_services');
	//$qtranslate_services = qtranxf_validateBool($qtranslate_services, $q_config['qtranslate_services']);
	if(!$q_config['qtranslate_services']) return;
	if(!qts_isOpenSSLAvailable()){
		$q_config['qtranslate_services'] = false;
		return;
	}
	if(is_string($qts_public_key)) {
		$qts_public_key = openssl_get_publickey(join("\n",explode("|",$qts_public_key)));
	}
}

function qts_isOpenSSLAvailable() {
	global $qts_openssl_functions_used;
	foreach($qts_openssl_functions_used as $function) {
		if(!function_exists($function)) return false;
	}
	return true;
}

function qts_init() {
	global $q_config;
	if(!$q_config['qtranslate_services']) return;
	/* disabled for meta box
		add_filter('qtranslate_toolbar', 'qts_toobar');
		add_filter('qtranslate_modify_editor_js', 'qts_editor_js');
	*/
	add_meta_box('translatediv', __('Translate to','qtranslate'), 'qts_translate_box', 'post', 'side', 'core');
	add_meta_box('translatediv', __('Translate to','qtranslate'), 'qts_translate_box', 'page', 'side', 'core');
	
	add_action('qtranslate_languageColumn', 'qts_translateButtons', 10, 2);
	
	// add plugin page without menu link for users with permission
	if(current_user_can('edit_published_posts')) {
		//add_posts_page(__('Translate','qtranslate'), __('Translate','qtranslate'), 'edit_published_posts', 'qtranslate_services', 'qts_service');
		global $_registered_pages;
		$hookname = get_plugin_page_hookname('qtranslate_services', 'edit.php');
		add_action($hookname, 'qts_service');
		$_registered_pages[$hookname] = true;
	}
}

function qts_save() {
	global $q_config;
	if($q_config['qtranslate_services'])
		update_option('qtranslate_qtranslate_services', '1');
	else
		update_option('qtranslate_qtranslate_services', '0');
}

function qts_cleanup($var, $action) {
	if( !is_array($var) ) $var = array();
	switch($action) {
		case QTS_GET_SERVICES:
			foreach($var as $service_id => $service) {
				// make array out of serialized field
				$fields = array();
				$required_fields = explode('|',$service['service_required_fields']);
				foreach($required_fields as $required_field) {
					if(strpos($required_field, " ")!==false) {
						list($fieldname, $title) = explode(' ', $required_field, 2);
						if($fieldname!='') {
							$fields[] = array('name' => $fieldname, 'value' => '', 'title' => $title);
						}
					}
				}
				$var[$service_id]['service_required_fields'] = $fields;
			}
		break;
	}
	if(isset($var['error']) && $var['error'] == QTS_DEBUG) {
		echo "<pre>Debug message received from Server: \n";
		var_dump($var['message']);
		echo "</pre>";
	}
	return $var;
}

function qts_config_pre_hook($message) {
	global $q_config;
	if(isset($_POST['default_language'])) {
		qtranxf_updateSetting('qtranslate_services', QTX_BOOLEAN);
		qts_load();
		if($q_config['qtranslate_services']) {
			$services = qts_queryQS(QTS_GET_SERVICES);
			$service_settings = get_option('qts_service_settings');
			if(!is_array($service_settings)) $service_settings = array();

			foreach($services as $service_id => $service) {
				// check if there are already settings for the field
				if(!isset($service_settings[$service_id])||!is_array($service_settings[$service_id])) $service_settings[$service_id] = array();

				// update fields
				foreach($service['service_required_fields'] as $field) {
					if(isset($_POST['qts_'.$service_id.'_'.$field['name']])) {
						// skip empty passwords to keep the old value
						if($_POST['qts_'.$service_id.'_'.$field['name']]=='' && $field['name']=='password') continue;
						$service_settings[$service_id][$field['name']] = $_POST['qts_'.$service_id.'_'.$field['name']];
					}
				}
			}
			update_option('qts_service_settings', $service_settings);
		}
	}
	if(isset($_GET['qts_delete'])) {
		$_GET['qts_delete'] = intval($_GET['qts_delete']);
		$orders = get_option('qts_orders');
		if(is_array($orders)) {
			foreach($orders as $key => $order) {
				if($orders[$key]['order']['order_id'] == $_GET['qts_delete']) {
					unset($orders[$key]);
					update_option('qts_orders',$orders);
				}
			}
		}
		$message[] = __('Order deleted.','qtranslate');
	}
	if(isset($_GET['qts_cron'])) {
		qts_cron();
		$message[] = __('Status updated for all open orders.','qtranslate');
	}
	return $message;
}

function qts_translate_box($post) {
	global $q_config;
	$languages = qtranxf_getSortedLanguages();
?>
	<ul>
<?php
	$from = $q_config['default_language'];
	$to = '';
	foreach($languages as $language) {
		if($language!=$from) $to = $language;
		if(isset($_REQUEST['post'])) {
?>
			<li><img src="<?php echo qtranxf_flag_location().$q_config['flag'][$language]; ?>" alt="<?php echo $q_config['language_name'][$language]; ?>"> <a href="edit.php?page=qtranslate_services&post=<?php echo intval($_REQUEST['post']); ?>&target_language=<?php echo $language; ?>"><?php echo $q_config['language_name'][$language]; ?> <span class="qsprice"></span></a></li>
<?php
		} else {
			echo '<li>'.__('Please save your post first.','qtranslate').'</li>';
			break;
		}
	}
?>
	</ul>
	<script type="text/javascript">
	// <![CDATA[
	jQuery(document).ready(function() {
		jQuery.post(ajaxurl, {
			action: 'qts_quote',
			mode: 'price_only',
			translate_from: '<?php echo $from; ?>',
			translate_to: '<?php echo $to; ?>',
			service_id: 5, 
			post_id: '<?php echo intval($_REQUEST['post']); ?>'}, 
			function(response) {
				eval(response);
		})
	});
	// ]]>
	</script>

<?php
}

function qts_order_columns($columns) {
	return array(
				'title' => __('Post Title', 'qtranslate'),
				'service' => __('Service', 'qtranslate'),
				'source_language' => __('Source Language', 'qtranslate'),
				'target_language' => __('Target Language', 'qtranslate'),
				'action' => __('Action', 'qtranslate')
				);
}

function qts_config_hook($request_uri) {
	global $q_config;
	qtranxf_admin_section_start(__('qTranslate Services Settings', 'qtranslate'),'service');
	// id="qtranslate-admin-service" style="display: none"
?>
<table class="form-table">
	<tr>
		<th scope="row"><?php _e('qTranslate Services', 'qtranslate') ?></th>
		<td>
			<?php if($q_config['qtranslate_services'] && !qts_isOpenSSLAvailable()) { printf(__('<div id="message" class="error fade"><p>qTranslate Services could not load <a href="%s">OpenSSL</a>!</p></div>'), 'http://www.php.net/manual/book.openssl.php'); } ?>
			<label for="qtranslate_services"><input type="checkbox" name="qtranslate_services" id="qtranslate_services" value="1"<?php checked($q_config['qtranslate_services']); ?>/> <?php _e('Enable qTranslate Services', 'qtranslate'); ?></label>
			<br/>
			<small><?php _e('With qTranslate Services, you will be able to use professional human translation services with a few clicks.', 'qtranslate'); ?></small>
		</td>
	</tr>
<?php 
	if($q_config['qtranslate_services']) { 
		$service_settings = get_option('qts_service_settings');
		$services = qts_queryQS(QTS_GET_SERVICES);
		$orders = get_option('qts_orders');
?>
	<tr valign="top">
		<th scope="row"><h4><?php _e('Open Orders', 'qtranslate'); ?></h4></th>
		<td>
<?php if(is_array($orders) && sizeof($orders)>0) { ?>
			<table class="widefat">
				<thead>
				<tr>
<?php print_column_headers('order'); ?>
				</tr>
				</thead>

				<tfoot>
				<tr>
<?php print_column_headers('order', false); ?>
				</tr>
				</tfoot>
<?php
		foreach($orders as $order) { 
			$p = get_post($order['post_id']); $post = &$p;
			if(!$post) continue;
			$post->post_title = esc_html(qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($post->post_title));
?>
				<tr>
					<td class="qts_no-bottom-border"><a href="post.php?action=edit&post=<?php echo $order['post_id']; ?>" title="<?php printf(__('Edit %s', 'qtranslate'),$post->post_title); ?>"><?php echo $post->post_title; ?></a></td>
					<td class="qts_no-bottom-border"><?php if(isset($services[$order['service_id']])): ?><a href="<?php echo $services[$order['service_id']]['service_url']; ?>" title="<?php _e('Website', 'qtranslate'); ?>"><?php echo $services[$order['service_id']]['service_name']; ?></a><?php endif; ?></td>
					<td class="qts_no-bottom-border"><?php echo $q_config['language_name'][$order['source_language']]; ?></td>
					<td class="qts_no-bottom-border"><?php echo $q_config['language_name'][$order['target_language']]; ?></td>
					<td class="qts_no-bottom-border"><a class="delete" href="<?php echo add_query_arg('qts_delete', $order['order']['order_id'], $request_uri); ?>#qtranslate_service_settings">Delete</a></td>
				</tr>
<?php 
			if(isset($order['status'])) {
?>
				<tr class="qts_status">
					<td colspan="5">
						<?php printf(__('Current Status: %s','qtranslate'), $order['status']); ?>
					</td>
				</tr>
<?php
			}
		}
?>
			</table>
			<p><?php printf(__('qTranslate Services will automatically check every hour whether the translations are finished and update your posts accordingly. You can always <a href="%s">check manually</a>.','qtranslate'),'options-general.php?page=qtranslate-x&qts_cron=true#qtranslate_service_settings'); ?></p>
			<p><?php _e('Deleting an open order doesn\'t cancel it. You will have to logon to the service homepage and cancel it there.','qtranslate'); ?></p>
<?php } else { ?>
			<p><?php _e('No open orders.','qtranslate'); ?></p>
<?php } ?>
		</td>
	</tr>
<?php
			if(!empty($services)){
				$showservices=false;
				foreach($services as $service) {
					if(sizeof($service['service_required_fields'])==0) continue;
					$showservices=true;
					break;
				}
			if($showservices){
?>
	<tr valign="top">
		<th scope="row" colspan="2">
			<h4><?php _e('Service Configuration', 'qtranslate');?></h4>
			<p class="description"><?php _e('Below, you will find configuration settings for qTranslate Service Providers, which are required for them to operate.', 'qtranslate'); ?></p>
		</th>
	</tr>
<?php
		foreach($services as $service) {
			if(sizeof($service['service_required_fields'])>0) {
?>
	<tr valign="top">
		<th scope="row" colspan="2">
			<h5><?php _e($service['service_name']);?> ( <a name="qts_service_<?php echo $service['service_id']; ?>" href="<?php echo $service['service_url']; ?>"><?php _e('Website', 'qtranslate'); ?></a> )</h5>
			<p class="description"><?php _e($service['service_description']); ?></p>
		</th>
	</tr>
<?php
				foreach($service['service_required_fields'] as $field) {
?>
	<tr valign="top">
		<th scope="row"><?php echo $field['title']; ?></th>
		<td>
			<input type="<?php echo ($field['name']=='password')?'password':'text';?>" name="<?php echo 'qts_'.$service['service_id']."_".$field['name']; ?>" value="<?php echo (isset($service_settings[$service['service_id']][$field['name']])&&$field['name']!='password')?$service_settings[$service['service_id']][$field['name']]:''; ?>" style="width:100%"/>
		</td>
	</tr>
<?php
				}
			}
		}}}
	}
?>
</table>
<?php
	qtranxf_admin_section_end('service');
}

function qts_cron() {
	global $wpdb;
	// poll translations
	$orders = get_option('qts_orders');
	if(!is_array($orders)) return;
	foreach($orders as $key => $order) {
		qts_UpdateOrder($order['order']['order_id']);
	}
}

function qts_UpdateOrder($order_id) {
	global $wpdb;
	$orders = get_option('qts_orders');
	if(!is_array($orders)) return false;
	foreach($orders as $key => $order) {
		// search for wanted order
		if($order['order']['order_id']!=$order_id) continue;
		
		// query server for updates
		$order['order']['order_url'] = get_option('home');
		$result = qts_queryQS(QTS_RETRIEVE_TRANSLATION, $order['order']);
		if(isset($result['order_comment'])) $orders[$key]['status'] = $result['order_comment'];
		// update db if post is updated
		if(isset($result['order_status']) && $result['order_status']==QTS_STATE_CLOSED) {
			$order['post_id'] = intval($order['post_id']);
			$p=get_post($order['post_id']); $post = &$p;
			$title = qtranxf_split($post->post_title);
			$content = qtranxf_split($post->post_content);
			$title[$order['target_language']] = $result['order_translated_title'];
			$content[$order['target_language']] = $result['order_translated_text'];
			$post->post_title = qtranxf_join_b($title);
			$post->post_content = qtranxf_join_b($content);
			$wpdb->show_errors();
			$wpdb->query('UPDATE '.$wpdb->posts.' SET post_title="'.mysql_real_escape_string($post->post_title).'", post_content = "'.mysql_real_escape_string($post->post_content).'" WHERE ID = "'.$post->ID.'"');
			wp_cache_add($post->ID, $post, 'posts');
			unset($orders[$key]);
		}
		update_option('qts_orders',$orders);
		return true;
	}
	return false;
}

function qts_service() {
	global $q_config, $qts_public_key, $qts_error_messages;
	if(!isset($_REQUEST['post'])) {
		echo '<script type="text/javascript">document.location="edit.php";</script>';
		printf(__('To translate a post, please go to the <a href="%s">edit posts overview</a>.','qtranslate'), 'edit.php');
		exit();
	}
	$post_id = intval($_REQUEST['post']);
	$confirm = isset($_GET['confirm'])?true:false;
	$translate_from  = '';
	$translate_to = '';
	$translate_from_name  = '';
	$translate_to_name = '';
	if(isset($_REQUEST['source_language'])&&qtranxf_isEnabled($_REQUEST['source_language']))
		$translate_from = $_REQUEST['source_language'];
	if(isset($_REQUEST['target_language'])&&qtranxf_isEnabled($_REQUEST['target_language']))
		$translate_to = $_REQUEST['target_language'];
	if($translate_to == $translate_from) $translate_to = '';
	$p = get_post($post_id); $post = &$p;
	if(!$post) {
		printf(__('Post with id "%s" not found!','qtranslate'), $post_id);
		return;
	}
	$default_service = intval(get_option('qts_default_service'),5);
	$service_settings = get_option('qts_service_settings');
	// Detect available Languages and possible target languages
	$available_languages = qtranxf_getAvailableLanguages($post->post_content);
	if(sizeof($available_languages)==0) {
		$error = __('The requested Post has no content, no Translation possible.', 'qtranslate');
	}

	// try to guess source and target language
	if(!in_array($translate_from, $available_languages)) $translate_from = '';
	$missing_languages = array_diff($q_config['enabled_languages'], $available_languages);
	if(empty($translate_from) && in_array($q_config['default_language'], $available_languages) && $translate_to!=$q_config['default_language']) $translate_from = $q_config['default_language'];
	if(empty($translate_to) && sizeof($missing_languages)==1) $translate_to = $missing_languages[0];
	if(in_array($translate_to, $available_languages)) {
		$message = __('The Post already has content for the selected target language. If a translation request is send, the current text for the target language will be overwritten.','qtranslate');
	}
	if(sizeof($available_languages)==1) {
		if($available_languages[0] == $translate_to) {
			unset($translate_to);
		}
		$translate_from = $available_languages[0];
	} elseif($translate_from == '' && sizeof($available_languages) > 1) {
		$languages = qtranxf_getSortedLanguages();
		foreach($languages as $language) {
			if($language != $translate_to && in_array($language, $available_languages)) {
				$translate_from = $language;
				break;
			}
		}
	}
	
	// link to current page with get variables
	$url_link = add_query_arg('post', $post_id);
	if(!empty($translate_to)) $url_link = add_query_arg('target_language', $translate_to, $url_link);
	if(!empty($translate_from)) $url_link = add_query_arg('source_language', $translate_from, $url_link);
	
	// get correct title and content
	$post_title = qtranxf_use($translate_from,$post->post_title);
	$post_content = qtranxf_use($translate_from,$post->post_content);
	$post_excerpt = qtranxf_use($translate_from,$post->post_excerpt);
	if(!empty($translate_from)) $translate_from_name  = $q_config['language_name'][$translate_from];
	if(!empty($translate_to)) $translate_to_name = $q_config['language_name'][$translate_to];

	$post_title_html = htmlspecialchars($post_title);
	$permalink = get_permalink($post_id);
	if($permalink){
		if($translate_from_name) $permalink = qtranxf_convertURL($translate_from_name,$permalink);
		$post_title_html = '<a href="'.$permalink.'" target="_blank">'.$post_title_html.'</a>';
	}
	if(!empty($translate_from) && !empty($translate_to)) {
		$title = sprintf('Translate &quot;%1$s&quot; from %2$s to %3$s', $post_title_html, $translate_from_name, $translate_to_name);
	} elseif(!empty($translate_from)) {
		$title = sprintf('Translate &quot;%1$s&quot; from %2$s', $post_title_html, $translate_from_name);
	} else {
		$title = sprintf('Translate &quot;%1$s&quot;', $post_title_html);
	}
	
	// Check data
	if(isset($_POST['service_id'])) {
		$service_id = intval($_POST['service_id']);
		$default_service = $service_id;
		update_option('qts_default_service', $service_id);
		$order_key = substr(md5(time().AUTH_KEY),0,20);
		$request = array(
				'order_service_id' => $service_id,
				'order_url' => get_option('home'),
				'order_key' => $order_key,
				'order_title' => $post_title,
				'order_text' => $post_content,
				'order_excerpt' => $post_excerpt,
				'order_source_language' => $translate_from,
				'order_source_locale' => $q_config['locale'][$translate_from],
				'order_target_language' => $translate_to,
				'order_target_locale' => $q_config['locale'][$translate_to]
			);
		// check for additional fields
		if(isset($service_settings[$service_id]) && is_array($service_settings[$service_id])) {
			$request['order_required_field'] = array();
			foreach($service_settings[$service_id] as $setting => $value) {
				$request['order_required_field'][$setting] = $value;
			}
		}
		if(isset($_POST['token'])) $request['order_token'] = $_POST['token'];
		$answer = qts_queryQS(QTS_INIT_TRANSLATION, $request);
		if(isset($answer['error'])) {
			$error = sprintf(__('An error occured: %s', 'qtranslate'), $qts_error_messages[$answer['error']]);
			if($answer['message']!='') {
				$error.='<br/>'.sprintf(__('Additional information: %s', 'qtranslate'), qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($answer['message']));
			}
		}
		if(isset($answer['order_id'])) {
			$orders = get_option('qts_orders');
			if(!is_array($orders)) $orders = array();
			$orders[] = array('post_id'=>$post_id, 'service_id' => $service_id, 'source_language'=>$translate_from, 'target_language'=>$translate_to, 'order' => array('order_key' => $order_key, 'order_id' => $answer['order_id']));
			update_option('qts_orders', $orders);
			if(empty($answer['message'])) {
				$order_completed_message = '';
			} else {
				$order_completed_message = htmlspecialchars($answer['message']);
			}
			qts_UpdateOrder($answer['order_id']);
		}
	}
	if(isset($error)) {
?>
<div class="wrap">
<h2><?php _e('qTranslate Services', 'qtranslate'); ?></h2>
<div id="message" class="error fade"><p><?php echo $error; ?></p></div>
<p><?php printf(__('An serious error occured and qTranslate Services cannot proceed. For help, please visit the <a href="%s">Support Forum</a>','qtranslate'), 'http://www.qianqin.de/qtranslate/forum/');?></p>
</div>
<?php
	return;
	}
	if(isset($order_completed_message)) {
?>
<div class="wrap">
<h2><?php _e('qTranslate Services', 'qtranslate'); ?></h2>
<div id="message" class="updated fade"><p><?php _e('Order successfully sent.', 'qtranslate'); ?></p></div>
<p><?php _e('Your translation order has been successfully transfered to the selected service.','qtranslate'); ?></p>
<?php
		if(!empty($order_completed_message)) {
?>
<p><?php printf(__('The service returned this message: %s','qtranslate'), $order_completed_message);?></p>
<?php
		}
?>
<p><?php _e('Feel free to choose an action:', 'qtranslate'); ?></p>
<ul>
	<li><a href="<?php echo add_query_arg('target_language', null, $url_link); ?>"><?php _e('Translate this post to another language.', 'qtranslate'); ?></a></li>
	<li><a href="edit.php"><?php _e('Translate a different post.', 'qtranslate'); ?></a></li>
	<li><a href="options-general.php?page=qtranslate-x#qtranslate_service_settings"><?php _e('View all open orders.', 'qtranslate'); ?></a></li>
	<li><a href="options-general.php?page=qtranslate-x&qts_cron=true#qtranslate_service_settings"><?php _e('Let qTranslate Services check if any open orders are finished.', 'qtranslate'); ?></a></li>
	<li><a href="<?php echo get_permalink($post_id); ?> "><?php _e('View this post.', 'qtranslate'); ?></a></li>
</ul>
</div>
<?php
		return;
	}
?>
<div class="wrap">
<h2><?php _e('qTranslate Services', 'qtranslate'); ?></h2>
<?php
if(!empty($message)) {
?>
<div id="message" class="updated fade"><p><?php echo $message; ?></p></div>
<?php
}
?>
<h3><?php echo $title;?></h3>
<form action="edit.php?page=qtranslate_services" method="post" id="qtranslate-services-translate">
<p><?php
	if(sizeof($available_languages)>1) {
		$available_languages_name = array();
		foreach(array_diff($available_languages,array($translate_from)) as $language) {
			$available_languages_name[] = '<a href="'.add_query_arg('source_language',$language, $url_link).'">'.$q_config['language_name'][$language].'</a>';
		}
		$available_languages_names = join(", ", $available_languages_name);
		printf(__('Your article is available in multiple languages. If you do not want to translate from %1$s, you can switch to one of the following languages: %2$s', 'qtranslate'),$q_config['language_name'][$translate_from],$available_languages_names);
	}
?></p>
<input type="hidden" name="post" value="<?php echo $post_id; ?>"/>
<input type="hidden" name="source_language" value="<?php echo $translate_from; ?>"/>
<?php
	if(empty($translate_to)) {
?>
<p><?php _e('Please choose the language you want to translate to:', 'qtranslate');?></p>
<ul>
<?php 
		foreach($q_config['enabled_languages'] as $language) {
			if($translate_from == $language) continue;
?>
	<li><label><input type="radio" name="target_language" value="<?php echo $language;?>" /> <?php echo $q_config['language_name'][$language]; ?></li>
<?php
		}
?>
</ul>
	<p class="submit">
		<input type="submit" name="submit" class="button-primary" value="<?php _e('Continue', 'qtranslate') ?>" />
	</p>
<?php
	} else {
?>
<input type="hidden" name="target_language" value="<?php echo $translate_to; ?>"/>
<?php if($confirm): ?>
	<input type="hidden" name="service_id" value="<?php echo $_REQUEST['service_id']; ?>"/>
	<input type="hidden" name="token" value="<?php echo $_REQUEST['token']; ?>"/>
	<div id="submitboxcontainer" class="metabox-holder">
		<div id="submitdiv" class="postbox">
			<h3 class="hndle"><?php _e('Confirm Order', 'qtranslate'); ?></h3>
			<div class="inside">
				<p><?php _e('Please confirm your order.', 'qtranslate'); ?></p>
				<div class="qts_submit"><a class="button-primary" onclick="sendorder();"><?php _e('Confirm Order', 'qtranslate'); ?></a></div>
			</div>
		</div>
	</div>
<?php else: ?>
	<div id="submitboxcontainer" class="metabox-holder">
		<div id="submitdiv" class="postbox">
			<h3 class="hndle"><?php _e('Request Translation', 'qtranslate'); ?></h3>
			<div class="inside request">
				<noscript><?php _e('Javascript is required for qTranslate Services', 'qtranslate'); ?></noscript>
				<p><?php _e('Please choose a service first', 'qtranslate'); ?></p>
			</div>
		</div>
	</div>
<?php endif; ?>
<?php
		$timestamp = time();
		if($timestamp != qts_queryQS(QTS_VERIFY, $timestamp)) {
?>
<p class="error"><?php _e('ERROR: Could not connect to qTranslate Services. Please try again later.', 'qtranslate');?></p>
<?php
			return;
		}
	
?>
<div id="qts_boxes" class="metabox-holder">
	<div class="postbox">
		<h3 class="hndle"><?php _e('Translation Service', 'qtranslate'); ?></h3>
		<div class="inside">

<ul>
<?php
		if($services = qts_queryQS(QTS_GET_SERVICES)) {
			$default_service_ok = false;
			foreach($services as $service_id => $service) {
				if($service_id != $default_service) continue;
				$default_service_ok = true;
				break;
			}
			if(!$default_service_ok){
				foreach($services as $service_id => $service) {
					$default_service = $service_id;
					break;
				}
			}
			foreach($services as $service_id => $service) {
				// check if we have data for all required fields
				//if($service_id==1) continue;//qTranslate Services Test
				$requirements_matched = true;
				foreach($service['service_required_fields'] as $field) {
					if(!isset($service_settings[$service_id][$field['name']]) || $service_settings[$service_id][$field['name']] == '') $requirements_matched = false;
				}
				if(!$requirements_matched) {
?>
<li>
	<label><input type="radio" name="service_id" disabled="disabled" /> <b><?php echo qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($service['service_name']); ?></b> ( <a href="<?php echo $service['service_url']; ?>" target="_blank"><?php _e('Website', 'qtranslate'); ?></a> )</label>
	<p class="error"><?php printf(__('Cannot use this service, not all <a href="%s">required fields</a> filled in for this service.','qtranslate'), 'options-general.php?page=qtranslate-x#qts_service_'.$service_id); ?></p>
	<p class="service_description"><?php echo qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($service['service_description']); ?></p>
</li>
<?php
				} else {
?>
<li><label><input type="radio" id="qts_service_<?php echo $service['service_id'];?>" onclick="chooseservice(this.value)" value="<?php echo $service['service_id']; ?>"<?php checked($service['service_id'],$default_service); ?> <?php echo $confirm?'disabled="disabled"':'name="service_id"'; ?> /> <b><?php echo qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($service['service_name']); ?></b> ( <a href="<?php echo $service['service_url']; ?>" target="_blank"><?php _e('Website', 'qtranslate'); ?></a> )</label><p class="service_description"><?php echo qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($service['service_description']); ?></p></li>
<?php
				}
			}
?>
</ul>
<script type="text/javascript">
	function chooseservice(id) {
		if(id == '0') return;
		jQuery('#qts_service_'+id).attr('checked','checked');
		jQuery('#submitdiv .request').html('<?php _e('<p><img src="images/wpspin_light.gif"> Getting Quote...</p>', 'qtranslate'); ?>');
		jQuery.post(ajaxurl, {
			action: 'qts_quote',
			translate_from: '<?php echo $translate_from; ?>',
			translate_to: '<?php echo $translate_to; ?>',
			service_id: id, 
			post_id: '<?php echo $post_id; ?>'}, 
			function(response) {
				eval(response);
		});
	}

	function sendorder() {
		jQuery("#qtranslate-services-translate").submit();
	}

	chooseservice('<?php echo isset($_REQUEST['service_id'])?$_REQUEST['service_id']:$default_service; ?>');
</script>
		</div>
	</div>
	<div class="postbox closed">
		<div class="handlediv" title="<?php _e('Click to toggle'); ?>" onclick="jQuery(this).parent().removeClass('closed');jQuery(this).hide();"><br></div>
		<h3 class="hndle"><?php _e('Review Article', 'qtranslate'); ?></h3>
		<div class="inside">
			<textarea name="qts_content_preview" id="qts_content_preview" readonly="readonly"><?php echo $post_content; ?></textarea>
		</div>
	</div>

<p><?php _e('Your article will be SSL encrypted and securly sent to qTranslate Services, which will forward your text to the chosen Translation Service. Once qTranslate Services receives the translated text, it will automatically appear on your blog.', 'qtranslate'); ?></p>
<?php
		}
?>
	</div>
<?php
	}
?>
</form>
</div>
<?php
}

function qts_quote() {
	global $q_config;
	$mode = 'full';
	if(isset($_POST['mode'])) $mode = $_POST['mode'];
	if($mode!='price_only') $mode = 'full';
	$service_id = $_POST['service_id'];
	$translate_from = $_POST['translate_from'];
	$translate_to = $_POST['translate_to'];
	$p = get_post($_POST['post_id']); $post = &$p;
	$post = qtranxf_use($translate_from, $post);
	$post_title = $post->post_title;
	$post_content = $post->post_content;
	$post_excerpt = $post->post_excerpt;
	$request = array(
			'order_service_id' => $service_id,
			'order_title' => $post_title,
			'order_text' => $post_content,
			'order_excerpt' => $post_excerpt,
			'order_source_language' => $translate_from,
			'order_source_locale' => $q_config['locale'][$translate_from],
			'order_target_language' => $translate_to,
			'order_target_locale' => $q_config['locale'][$translate_to],
			'order_confirm_url' => get_admin_url(null, 'edit.php?page=qtranslate_services&confirm=1&post='.$_POST['post_id'].'&source_language='.$translate_from.'&target_language='.$translate_to.'&service_id='.$service_id),
			'order_failure_url' => get_admin_url(null, 'edit.php?page=qtranslate_services&post='.$_POST['post_id'].'&source_language='.$translate_from.'&target_language='.$translate_to.'&service_id='.$service_id)
		);
	$answer = qts_queryQS(QTS_QUOTE, $request);
	$price = __('unavailable', 'qtranslate');
	$currency = '';
	$short = '';
	if(isset($answer['price'])) {
		if($answer['price'] == 0) {
			$price = __('free', 'qtranslate');
		} else if($answer['price'] < 0) {
			$price = __('unavailable', 'qtranslate');
		} else {
			$price = number_format_i18n($answer['price'],2);
			$currency = $answer['currency'];
		}
		$content = sprintf(__('<p>Price: %1$s %2$s</p>','qtranslate'), $currency, $price);
		$short = sprintf(__('~ %1$s %2$s','qtranslate'), $currency, $price);
		if(!empty($answer['paypalurl'])) {
			$content .= '<div class="qts_submit"><a href="'.$answer['paypalurl'].'"><img src="https://fpdbs.paypal.com/dynamicimageweb?cmd=_dynamic-image&locale='.$q_config['locale'][$q_config['language']].'"></a></div>';
		} else {
			$content .= '<div class="qts_submit"><a class="button-primary" onclick="sendorder();">'.__('Request Translation', 'qtranslate').'</a></div>';
		}
	} else {
		$content = '<p>'.__('An error occured!', 'qtranslate');
		if(isset($answer['error'])) $content .= '<br>'.$answer['message'];
		$content .= '</p>';
	}
	if($mode == 'full') {
		echo "jQuery('#submitdiv .request').html('";
		echo $content;
		echo "');";
	} else if($mode == 'price_only') {
		echo "jQuery('.qsprice').html('";
		echo $short;
		echo "');";
	}
	die();
}

function qts_toobar($content) {
	// Create Translate Button 
	$content .= qtranxf_createEditorToolbarButton('translate', 'translate', 'init_qs', __('Translate'));
	return $content;
}

function qts_editor_js($content) {
	$content .= "
		init_qs = function(action, id) {
			document.location.href = 'edit.php?page=qtranslate_services&post=".intval($_REQUEST['post'])."';
		}
		";
	return $content;
}
