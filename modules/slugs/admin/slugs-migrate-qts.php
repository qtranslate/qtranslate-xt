<?php
/**
 * Legacy meta and options from QTS plugin.
 */
const QTX_SLUGS_LEGACY_QTS_META_PREFIX    = '_qts_slug_';
const QTX_SLUGS_LEGACY_QTS_OPTIONS_PREFIX = '_qts_';
const QTX_SLUGS_LEGACY_QTS_OPTIONS_NAME   = 'qts_options';

/**
 * Check if slugs meta should be migrated from the legacy QTS postmeta and termmeta.
 *
 * @return string messages giving details, empty if new meta found or no legacy meta found.
 */
function qtranxf_slugs_check_migrate_qts() {
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
        $esc_prefix = str_replace( '_', '\_', $prefix );  // Escape '_' against LIKE wildcards.
        $results    = $wpdb->get_var( "SELECT count(*) FROM  $table WHERE meta_key LIKE '$esc_prefix%'" );
        if ( $results ) {
            $msg[] = sprintf( __( "Found %d slugs from $table.", 'qtranslate' ), $results );
        }
    };

    $msg = [];
    $count_slugs( $wpdb->postmeta, QTX_SLUGS_LEGACY_QTS_META_PREFIX, $msg );
    $count_slugs( $wpdb->termmeta, QTX_SLUGS_LEGACY_QTS_META_PREFIX, $msg );

    return empty ( $msg ) ? '' : implode( '<br>', $msg );
}

/**
 * Migrate slugs meta by migrating the legacy QTS postmeta and termmeta to QTX.
 * Attention: current slugs meta are deleted if QTS slugs are found.
 *
 * @param bool $db_commit true to commit changes, false for dry-run mode.
 *
 * @return string messages giving details.
 */
function qtranxf_slugs_migrate_qts_meta( $db_commit ) {
    global $wpdb;

    $new_prefix = QTX_SLUGS_META_PREFIX;
    $old_prefix = QTX_SLUGS_LEGACY_QTS_META_PREFIX;

    /**
     * Generic function that migrates QTS meta to QTX meta.
     *
     * @param string $table name of the meta table (postmeta, termmeta)
     * @param string $colid column name of the parent id (post_id, term_id)
     * @param string[] $msg array of messages, updated
     *
     * @return void
     */
    $migrate_meta = function ( $table, $colid, $db_commit, &$msg ) use ( $wpdb, $old_prefix, $new_prefix ) {
        // Escape '_' against LIKE wildcards.
        $old_esc = str_replace( '_', '\_', $old_prefix );
        $new_esc = str_replace( '_', '\_', $new_prefix );

        $count_qts = $wpdb->get_var( "SELECT count(*) FROM  $table WHERE meta_key LIKE '$old_esc%'" );
        if ( ! $count_qts ) {
            $msg[] = sprintf( __( "No slugs to migrate from %s.", 'qtranslate' ), $table );

            return;
        }
        // Find the related post_id/term_id to delete (not meta_id), to ensure the migrated slugs replace the whole existing groups.
        $id_to_delete = "SELECT DISTINCT($colid) FROM $table WHERE meta_key LIKE '$old_esc%'";
        if ( $db_commit ) {
            $results = $wpdb->query( "DELETE FROM $table WHERE meta_key LIKE '$new_esc%' AND $colid in ( SELECT * FROM ( $id_to_delete ) as M )" );
            $msg[]   = sprintf( __( "Deleted %d slugs from %s (%s).", 'qtranslate' ), $results ?: 0, $table, $new_prefix );
            // Rename meta keys.
            $results = $wpdb->query( "UPDATE $table SET meta_key = REPLACE(meta_key, '$old_prefix', '$new_prefix') WHERE meta_key LIKE '$old_esc%'" );
            $msg[]   = sprintf( __( "Migrated %d slugs from %s (%s).", 'qtranslate' ), $results ?: 0, $table, $old_prefix );
        } else {
            // Dry-run mode: show how many slugs are to be deleted and migrated, no change in DB.
            $results = $wpdb->get_var( "SELECT count(*) FROM  $table WHERE meta_key LIKE '$new_esc%' AND $colid in ($id_to_delete)" );
            $msg[]   = sprintf( __( "Deleted %d slugs from %s (%s).", 'qtranslate' ), $results ?: 0, $table, $new_prefix );
            $msg[]   = sprintf( __( "Migrated %d slugs from %s (%s).", 'qtranslate' ), $count_qts, $table, $old_prefix );
        }
    };

    $msg = [];
    $migrate_meta( $wpdb->postmeta, 'post_id', $db_commit, $msg );
    $migrate_meta( $wpdb->termmeta, 'term_id', $db_commit, $msg );

    return implode( '<br>', $msg );
}

/**
 * Migrate legacy QTS options to QTX.
 * Attention: current slugs options are deleted if QTS options are found.
 *
 * @param bool $db_commit true to commit changes, false for dry-run mode.
 *
 * @return string messages giving details.
 */
function qtranxf_slugs_migrate_qts_options( $db_commit ) {
    $msg = [];

    $qts_options = get_option( QTX_SLUGS_LEGACY_QTS_OPTIONS_NAME );
    if ( ! $qts_options ) {
        return __( "No options to migrate.", 'qtranslate' );
    }

    $old_options = get_option( QTX_OPTIONS_MODULE_SLUGS );
    if ( $old_options ) {
        if ( $db_commit ) {
            delete_option( QTX_OPTIONS_MODULE_SLUGS );
        }
        $msg[] = sprintf( __( "Deleted %d types from options.", 'qtranslate' ), count( $old_options ) );
    }

    $new_options = [];
    // Drop the legacy prefix.
    foreach ( $qts_options as $type => $slugs ) {
        $type                 = str_replace( QTX_SLUGS_LEGACY_QTS_OPTIONS_PREFIX, '', $type );
        $new_options[ $type ] = $slugs;
    }
    if ( $db_commit ) {
        update_option( QTX_OPTIONS_MODULE_SLUGS, $new_options, false );
        delete_option( QTX_SLUGS_LEGACY_QTS_OPTIONS_NAME );

        global $qtranslate_slugs;
        if ( $qtranslate_slugs->options_buffer != $new_options ) {
            $qtranslate_slugs->options_buffer = $new_options;
            flush_rewrite_rules();
        }
    }
    $msg[] = sprintf( __( "Migrated %d types from options.", 'qtranslate' ), count( $new_options ) );

    return implode( '<br/>', $msg );
}

/**
 * Migrate slugs legacy QTS data (meta and options).
 * Attention: current slugs data are deleted if QTS data are found.
 *
 * @param bool $db_commit true to commit changes, false for dry-run mode.
 *
 * @return string messages giving details.
 */
function qtranxf_slugs_migrate_qts_data( $db_commit ) {
    $msg   = [];
    $msg[] = $db_commit ? __( 'Migrate slugs:', 'qtranslate' ) : __( "Dry-run mode:", 'qtranslate' );
    $msg[] = qtranxf_slugs_migrate_qts_meta( $db_commit );
    $msg[] = qtranxf_slugs_migrate_qts_options( $db_commit );

    if ( $db_commit ) {
        qtranxf_update_admin_notice( 'slugs-migrate', true );  // Hide the automatic admin notice.
    }

    return implode( '<br/>', $msg );
}
