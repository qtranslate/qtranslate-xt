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

/*
function qtranxf_test_next_posts_link() {
	//global $post;
	$npl = next_posts_link();
	qtranxf_tst_log('qtranxf_test_next_posts_link: ',$npl);
}
//qtranxf_test_next_posts_link();
*/

	//exit();

//add_filter( 'wp_head', 'qtranxf_test_meta_cache', 1 );
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
