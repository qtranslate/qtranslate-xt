<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

//TOTO: check if this function needs to be retained as documented or not
function qts_get_url( $lang = false ) {
    global $qtranslate_slug;

    return $qtranslate_slug->get_current_url( $lang );
}

function qts_convert_url( $url, $lang ) {
    if ( empty( $url ) ) {
        return qts_get_url( $lang );
    }

    return $url;
}

/**
 * Check if slugs meta can be imported from the legacy postmeta and termmeta.
 *
 * @return string messages giving details, or empty if failed.
 */
function qts_check_import_slugs() {
    global $wpdb;

    $count_slugs = function ( $dbmeta, &$msg ) use ( $wpdb ) {
        $results = $wpdb->get_var( "SELECT count(*) FROM  $dbmeta WHERE meta_key like '_qts_slug%'" );
        if ( $results ) {
            $msg[] = sprintf( __( "Found %s slugs from $dbmeta.", 'qtranslate' ), $results );
        }
    };

    $count_slugs( $wpdb->postmeta, $msg );
    $count_slugs( $wpdb->termmeta, $msg );

    return empty ( $msg ) ? $msg : implode( '<br>', $msg );
}

/**
 * Import slugs meta by duplicating the legacy postmeta and termmeta.
 *
 * @return string messages giving details, or empty if failed.
 */
function qts_import_slugs() {
    global $wpdb;

    $meta_prefix = 'QTX_SLUGS'; // TOOD decide target prefix.
    $old_prefix  = '_qts_slug';

    $import_meta = function ( $dbmeta, $dbmetaid, &$msg ) use ( $wpdb, $old_prefix, $meta_prefix ) {
        $results = $wpdb->query( "DELETE FROM $dbmeta WHERE meta_key like '$meta_prefix%'" );
        if ( $results ) {
            $msg[] = sprintf( __( "Deleted %s slugs from $dbmeta.", 'qtranslate' ), $results );
        }
        $results = $wpdb->query( "INSERT INTO $dbmeta ($dbmetaid, meta_key, meta_value)
                              SELECT $dbmetaid, REPLACE(meta_key, '$old_prefix', '$meta_prefix'), meta_value
                              FROM  $dbmeta
                              WHERE meta_key like '$old_prefix%'" );
        if ( $results ) {
            $msg[] = sprintf( __( "Imported %s slugs into $dbmeta.", 'qtranslate' ), $results );
        }
    };

    $msg = [];
    $import_meta( $wpdb->postmeta, 'post_id', $msg );
    $import_meta( $wpdb->termmeta, 'term_id', $msg );

    return empty( $msg ) ? __( "No slug meta was imported.", 'qtranslate' ) : implode( '<br>', $msg );
}
