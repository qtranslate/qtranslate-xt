<?php
//if(is_admin() && !wp_doing_ajax()){
//	require_once __DIR__ . '/qwpseo-activation.php';
//	$file = wp_normalize_path(__FILE__);
//	register_activation_hook($file, 'qwpseo_activation_hook');
//}

//if(wp_doing_ajax()){
//	require_once __DIR__ . '/qwpseo-activation.php';
//	add_action('wp_ajax_qwpseo_meta_fix', 'qwpseo_ajax_meta_fix');
//}else{
//	$n = get_option('qwpseo_meta_fix');
//	if(!is_numeric($n) || $n > 0){
//		require_once __DIR__ . '/qwpseo-activation.php';
//		qwpseo_meta_check();
//	}

/*
function qwpseo_set_encoding_s($value){
    $lang_code = QTX_LANG_CODE_FORMAT;

	if(is_string($value)){
		$value = preg_replace('/<!--:($lang_code)-->/ism', '{:$1}', $value);
		$value = preg_replace('/\\[:($lang_code)\\]/ism', '{:$1}', $value);
		$value = preg_replace('/\\[:\\]|<!--:-->/ism', '{:}', $value);
	}elseif(is_array($value)){
		foreach($value as $k => $v){
			$value[$k] = qwpseo_set_encoding_s($v);//recursive call
		}
	}elseif(is_object($value) || $value instanceof __PHP_Incomplete_Class){
		foreach(get_object_vars($value) as $k => $v){
			if(!isset($value->$k)) continue;
			$value->$k = qwpseo_set_encoding_s($v);//recursive call
		}
	}
	return $value;
}

function qwpseo_meta_fix($result){
	global $wpdb;
	$wpdb->show_errors(true); @set_time_limit(0);
	$query = "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_id = %d";
	foreach($result as $row){
		$value = $row->meta_value;
		if(is_serialized($value)){
			$value = unserialize($value);
			$value = qwpseo_set_encoding_s($value);
			$value = serialize($value);
		}else{//if(is_string($value)){
			$value = qwpseo_set_encoding_s($value);
		}
		if($row->meta_value === $value)
			continue;
		$q = $wpdb->prepare($query, $value, $row->meta_id);
		$wpdb->query($q);
	}
}
*/

function qwpseo_meta_fix( $result ) {
    global $wpdb;
    $wpdb->show_errors( true );
    @set_time_limit( 0 );
    $query = "UPDATE $wpdb->postmeta SET meta_value = %s WHERE meta_id = %d";
    //$query_i = "INSERT $wpdb->postmeta SET meta_value = %s WHERE meta_id = %d";
    foreach ( $result as $row ) {
        $value = $row->meta_value;
        if ( is_serialized( $value ) ) {
            $value = unserialize( $value );
            $value = qwpseo_set_encoding_s( $value );
            $value = serialize( $value );
        } else {//if(is_string($value)){
            $value = qwpseo_set_encoding_s( $value );
        }
        if ( $row->meta_value === $value ) {
            continue;
        }
        $q = $wpdb->prepare( $query, $value, $row->meta_id );
        $wpdb->query( $q );
    }
}

function qwpseo_meta_check() {
    global $wpdb;
    //$query = "SELECT * FROM $wpdb->postmeta WHERE meta_key like '_yoast_wpseo%' AND (meta_value like '%[:__]%' OR meta_value like '%[:]%' OR meta_value like '%<--:__-->%' OR meta_value like '%<--:-->%')";
    $query  = "SELECT * FROM $wpdb->postmeta as a WHERE a.meta_key = '_yoast_wpseo_focuskw' AND (NOT EXISTS (SELECT * FROM $wpdb->postmeta as b WHERE b.post_id = a.post_id AND b.meta_key = '_yoast_wpseo_focuskw_text_input' ))";
    $result = $wpdb->get_results( $query );
    if ( is_null( $result ) || ! is_array( $result ) ) {
        delete_option( 'qwpseo_meta_fix' );
    } else {
        $n = count( $result );
        update_option( 'qwpseo_meta_fix', $n );
        if ( $n > 0 ) {
            add_action( 'admin_notices', 'qwpseo_admin_notices' );
        }
    }

    return $result;
}

function qwpseo_ajax_meta_fix() {
    for ( $i = 10; --$i > 0; ) {
        $result = qwpseo_meta_check();
        if ( is_null( $result ) ) {
            die( qtranxf_translate( 'An error occurred during the database update.' ) );
        } elseif ( count( $result ) == 0 ) {
            die( qtranxf_translate( 'The database update has finished successfully.' ) );
        }
        qwpseo_meta_fix( $result );
    }
    die( qtranxf_translate( 'The database update has not finished. Please, refresh this page and run the update again.' ) );
}

function qwpseo_activation_hook() {
    qwpseo_meta_check();
}

function qwpseo_admin_notices() {
    $n = get_option( 'qwpseo_meta_fix' );
    if ( ! is_numeric( $n ) ) {
        return;
    }
    if ( $n == 0 ) {
        return;
    }
    ?>
    <script>
        function qwpseo_hide_notice() {
            jQuery('#qwpseo_notice_meta_fix').addClass('hidden');
        }

        function qwpseo_run_meta_fix() {
            jQuery('#qwpseo_notice_meta_fix_spinner').addClass('is-active');
            jQuery.post(ajaxurl, {action: 'qwpseo_meta_fix'}, function (r) {
                jQuery('#qwpseo_notice_meta_fix_spinner').removeClass('is-active');
                jQuery('#qwpseo_notice_meta_fix').addClass('is-dismissible');
                jQuery('#qwpseo_notice_meta_fix_button_span').html(r);
                jQuery('#qwpseo_notice_meta_fix').append('<button type="button" class="notice-dismiss" onclick="javascript:qwpseo_hide_notice();"></button>');
            });
        }
    </script>
    <?php
    echo '<div class="error notice" id="qwpseo_notice_meta_fix"><p>';
    printf( qtranxf_translate( 'Plugin %s needs to perform an update of the database in order to function correctly.' ), '<span style="color:red;"><a href="https://wordpress.org/plugins/wp-seo-qtranslate-x/" target="_blank"><strong>qTranslate-XT (Integration: Yoast SEO)</strong></a></span>' );
    echo '&nbsp;';
    printf( qtranxf_translate( 'The number of database entries to be updated is equal to %d.' ), $n );
    echo '&nbsp;';
    echo qtranxf_translate( 'Make sure to have a recent enough backup of the database, just in case something goes wrong during the update. When ready, please press the button below to start the update.' );
    echo '<br/><br/>&nbsp;<span class="spinner" style="float: left" id="qwpseo_notice_meta_fix_spinner"></span><span id="qwpseo_notice_meta_fix_button_span" style="color: blue"><a class="button qwpseo_notice_button" href="javascript:qwpseo_run_meta_fix();">';
    echo qtranxf_translate( 'Run the Database Update Now' );
    echo '</a></span>';
    echo '</p></div>' . PHP_EOL;
}
