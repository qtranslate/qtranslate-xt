<?php
if(!defined('ABSPATH'))exit;

function qtranxf_tst_log($msg,$var='novar',$bt=false,$exit=false){qtranxf_dbg_log($msg,$var,$bt,$exit);}

function qtranxf_check_test($result, $expected, $test_name){
	//qtranxf_tst_log('qtranxf_check_test: $result='.$result. PHP_EOL .'                 $expected=',$expected);
	if($result == $expected) return true;
	qtranxf_tst_log($test_name.': '.$result.' != ',$expected);
	return false;
}
	//qtranxf_tst_log('qtx-tests: SERVER: ',$_SERVER);
	//require_once(dirname(__FILE__).'/qtx-test-convertURL.php');
	//require_once(dirname(__FILE__).'/qtx-test-date-time.php');
	//exit();

add_filter( 'wp_head', 'qtranxf_test_meta_cache', 1 );
function qtranxf_test_meta_cache() {
	global $post;
	if( !is_singular() || !$post || 'post' != $post->post_type ){
		qtranxf_tst_log('qtranxf_test_meta_cache: return');
		return;
	}
	$views = get_post_meta( $post->ID, 'views', true );
	$views = $views ? $views : 0;
	$views++;
	update_post_meta( $post->ID, 'views', $views );
	$views_fetched = get_post_meta( $post->ID, 'views', true );
	if(qtranxf_check_test($views_fetched,$views,'qtranxf_test_meta_cache')){
		qtranxf_tst_log('qtranxf_test_meta_cache: ok');
	}
	//qtranxf_tst_log('qtranxf_test_meta_cache: views_expected='.$views.'; $views_fetched=',$views_fetched);
}

/*
	$cfg_json=file_get_contents(dirname(QTRANSLATE_FILE).'/qTranslateX.json');
	//$cfg_json=php_strip_whitespace(dirname(QTRANSLATE_FILE).'/qTranslateX.json');
	if($cfg_json){
		//qtranxf_tst_log('qtranxf_load_admin_page_config: cfg_json:',$cfg_json);
		$cfg=json_decode($cfg_json);
		//qtranxf_tst_log('qtranxf_load_admin_page_config: cfg:',$cfg);
		if($cfg){
			qtranxf_tst_log('qtranxf_load_admin_page_config: cfg: ',json_encode($cfg,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
		}else{
			$msg=json_last_error();
			qtranxf_tst_log('qtranxf_load_admin_page_config: json_last_error: ',$msg);
		}
	}
*/
