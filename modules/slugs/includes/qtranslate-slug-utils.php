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
 * @return string messages giving details, or empty if no meta found.
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
 * @param bool $db_commit true to commit changes, false for dry-run mode.
 *
 * @return string messages giving details.
 */
function qts_import_slugs_meta( $db_commit ) {
    global $wpdb;

    $new_prefix = QTX_SLUG_META_PREFIX;
    $old_prefix = '_qts_slug';

    $import_meta = function ( $dbmeta, $dbmetaid, &$msg ) use ( $wpdb, $old_prefix, $new_prefix ) {
        $results = $wpdb->query( "DELETE FROM $dbmeta WHERE meta_key like '$new_prefix%'" );
        if ( $results ) {
            $msg[] = sprintf( __( "Deleted %s rows from $dbmeta (%s).", 'qtranslate' ), $results, $new_prefix );
        }
        $results = $wpdb->query( "INSERT INTO $dbmeta ($dbmetaid, meta_key, meta_value)
                              SELECT $dbmetaid, REPLACE(meta_key, '$old_prefix', '$new_prefix'), meta_value
                              FROM  $dbmeta
                              WHERE meta_key like '$old_prefix%'" );
        $msg[]   = sprintf( __( "Imported %s rows into $dbmeta (%s->%s).", 'qtranslate' ), $results ?: '0', $old_prefix, $new_prefix );
    };

    $msg = [];
    $wpdb->query( "START TRANSACTION" );
    $import_meta( $wpdb->postmeta, 'post_id', $msg );
    $import_meta( $wpdb->termmeta, 'term_id', $msg );
    if ( $db_commit ) {
        $wpdb->query( "COMMIT" );
    } else {
        $wpdb->query( "ROLLBACK" );
    }

    return implode( '<br>', $msg );
}

/**
 * Import slugs legacy options.
 *
 * @param bool $db_commit true to commit changes, false for dry-run mode.
 *
 * @return string messages giving details.
 */
function qts_import_slugs_options( $db_commit ) {
    $msg = [];

    $new_options = get_option( QTX_OPTIONS_MODULE_SLUGS );
    if ( $new_options ) {
        $msg[] = sprintf( __( "Deleted %s types from options.", 'qtranslate' ), count( $new_options ) );
        if ( $db_commit ) {
            delete_option( QTX_OPTIONS_MODULE_SLUGS );
        }
    }

    $options = get_option( 'qts_options' );
    if ( $options ) {
        $new_options = [];
        // Drop the legacy prefix.
        foreach ( $options as $type => $slugs ) {
            $type                 = str_replace( '_qts_', '', $type );
            $new_options[ $type ] = $slugs;
        }
        $msg[] = sprintf( __( "Imported %s types from options.", 'qtranslate' ), count( $new_options ) );
        if ( $db_commit ) {
            update_option( QTX_OPTIONS_MODULE_SLUGS, $new_options, false );
        }
    }

    return implode( '<br/>', $msg );
}

/**
 * Import slugs legacy options.
 *
 * @param bool $db_commit true to commit changes, false for dry-run mode.
 *
 * @return string messages giving details.
 */
function qts_import_slugs( $db_commit ) {
    $msg   = [];
    $msg[] = $db_commit ? __( 'Import slugs:', 'qtranslate' ) : __( "Dry-run mode:", 'qtranslate' );
    $msg[] = qts_import_slugs_meta( $db_commit );
    $msg[] = qts_import_slugs_options( $db_commit );

    return implode( '<br/>', $msg );
}