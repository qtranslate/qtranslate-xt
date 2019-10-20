<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function qtranxf_test_date( $ds ) {
    $cnt = 0;
    foreach ( $ds as $fmt => $date_expected ) {
        $post = get_post( 1 );
        $d    = get_the_date( $fmt, $post );
        get_post_modified_time( $fmt, false, $post, true );
        qtranxf_tst_log( 'qtranxf_test_date: get_the_date(' . $fmt . '): ', $d );
        if ( ! qtranxf_check_test( $d, $date_expected, basename( __FILE__ ) ) ) {
            ++ $cnt;
        }
    }

    return $cnt;
}

$ds = array(
    'c' => '2015-02-25T23:38:07+00:00',
);
qtranxf_test_date( $ds );
