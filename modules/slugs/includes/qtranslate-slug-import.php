<?php
/**
 * Legacy meta and options from QTS plugin.
 */
const QTX_SLUGS_LEGACY_QTS_META_PREFIX    = '_qts_slug_';
const QTX_SLUGS_LEGACY_QTS_OPTIONS_PREFIX = '_qts_';
const QTX_SLUGS_LEGACY_QTS_OPTIONS_NAME   = 'qts_options';

/**
 * Check if slugs meta should be imported from the legacy QTS postmeta and termmeta.
 *
 * @return string messages giving details, empty if new meta found or no legacy meta found.
 */
function qtranxf_slugs_check_import_qts() {
    global $wpdb;

    /**
     * Generic function that counts the slugs meta, legacy (QTS) or new (QTX).
     *
     * @param string $table name of the meta table (postmeta, termmeta)
     * @param string $prefix prefix for the meta key
     * @param string[] $msg array of messages, updated
     *
     * @return void
     */
    $count_slugs = function ( $table, $prefix, &$msg ) use ( $wpdb ) {
        $results = $wpdb->get_var( "SELECT count(*) FROM  $table WHERE meta_key like '$prefix%'" );
        if ( $results ) {
            $msg[] = sprintf( __( "Found %s slugs from $table.", 'qtranslate' ), $results );
        }
    };

    $msg = [];
    $count_slugs( $wpdb->postmeta, QTX_SLUGS_META_PREFIX, $msg );
    $count_slugs( $wpdb->termmeta, QTX_SLUGS_META_PREFIX, $msg );
    if ( ! empty( $msg ) ) {
        // Found some post/term meta with the new keys, no import to suggest (it can still be done manually).
        return '';
    }

    $msg = [];
    $count_slugs( $wpdb->postmeta, QTX_SLUGS_LEGACY_QTS_META_PREFIX, $msg );
    $count_slugs( $wpdb->termmeta, QTX_SLUGS_LEGACY_QTS_META_PREFIX, $msg );

    return empty ( $msg ) ? $msg : implode( '<br>', $msg );
}

/**
 * Import slugs meta by duplicating the legacy QTS postmeta and termmeta for QTX.
 *
 * @param bool $db_commit true to commit changes, false for dry-run mode.
 *
 * @return string messages giving details.
 */
function qtranxf_slugs_import_qts_meta( $db_commit ) {
    global $wpdb;

    $new_prefix = QTX_SLUGS_META_PREFIX;
    $old_prefix = QTX_SLUGS_LEGACY_QTS_META_PREFIX;

    /**
     * Generic function that imports old meta into new ones. All existing new meta are erased first.
     *
     * @param string $table name of the meta table (postmeta, termmeta)
     * @param string $colid column name giving the meta id (post_id, term_id)
     * @param string[] $msg array of messages, updated
     *
     * @return void
     */
    $import_meta = function ( $table, $colid, &$msg ) use ( $wpdb, $old_prefix, $new_prefix ) {
        $results = $wpdb->query( "DELETE FROM $table WHERE meta_key like '$new_prefix%'" );
        if ( $results ) {
            $msg[] = sprintf( __( "Deleted %s rows from $table (%s).", 'qtranslate' ), $results, $new_prefix );
        }
        $results = $wpdb->query( "INSERT INTO $table ($colid, meta_key, meta_value)
                              SELECT $colid, REPLACE(meta_key, '$old_prefix', '$new_prefix'), meta_value
                              FROM  $table
                              WHERE meta_key like '$old_prefix%'" );
        $msg[]   = sprintf( __( "Imported %s rows into $table (%s->%s).", 'qtranslate' ), $results ?: '0', $old_prefix, $new_prefix );
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
 * Import slugs legacy QTS options.
 *
 * @param bool $db_commit true to commit changes, false for dry-run mode.
 *
 * @return string messages giving details.
 */
function qtranxf_slugs_import_qts_options( $db_commit ) {
    $msg = [];

    $new_options = get_option( QTX_OPTIONS_MODULE_SLUGS );
    if ( $new_options ) {
        if ( $db_commit ) {
            delete_option( QTX_OPTIONS_MODULE_SLUGS );
        }
        $msg[] = sprintf( __( "Deleted %s types from options.", 'qtranslate' ), count( $new_options ) );
    }

    $options = get_option( QTX_SLUGS_LEGACY_QTS_OPTIONS_NAME );
    if ( $options ) {
        $new_options = [];
        // Drop the legacy prefix.
        foreach ( $options as $type => $slugs ) {
            $new_type                 = str_replace( QTX_SLUGS_LEGACY_QTS_OPTIONS_PREFIX, '', $type );
            $new_options[ $new_type ] = $slugs;
        }
        if ( $db_commit ) {
            update_option( QTX_OPTIONS_MODULE_SLUGS, $new_options, false );
        }
        $msg[] = sprintf( __( "Imported %s types from options.", 'qtranslate' ), count( $new_options ) );
    }

    return implode( '<br/>', $msg );
}

/**
 * Import slugs legacy QTS data (options and meta).
 *
 * @param bool $db_commit true to commit changes, false for dry-run mode.
 *
 * @return string messages giving details.
 */
function qtranxf_slugs_import_qts_data( $db_commit ) {
    $msg   = [];
    $msg[] = $db_commit ? __( 'Import slugs:', 'qtranslate' ) : __( "Dry-run mode:", 'qtranslate' );
    $msg[] = qtranxf_slugs_import_qts_meta( $db_commit );
    $msg[] = qtranxf_slugs_import_qts_options( $db_commit );

    if ( $db_commit ) {
        // Hide the admin notice.
        qtranxf_update_admin_notice( 'slugs-import', true );
    }

    return implode( '<br/>', $msg );
}
