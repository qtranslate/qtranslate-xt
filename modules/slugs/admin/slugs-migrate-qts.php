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
        $results = $wpdb->get_var( "SELECT count(*) FROM  $table WHERE meta_key like '$prefix%'" );
        if ( $results ) {
            $msg[] = sprintf( __( "Found %s slugs from $table.", 'qtranslate' ), $results );
        }
    };

    $msg = [];
    $count_slugs( $wpdb->postmeta, QTX_SLUGS_META_PREFIX, $msg );
    $count_slugs( $wpdb->termmeta, QTX_SLUGS_META_PREFIX, $msg );
    if ( ! empty( $msg ) ) {
        // Found some post/term meta with the new keys, no migrate to suggest (it can still be done manually).
        return '';
    }

    $msg = [];
    $count_slugs( $wpdb->postmeta, QTX_SLUGS_LEGACY_QTS_META_PREFIX, $msg );
    $count_slugs( $wpdb->termmeta, QTX_SLUGS_LEGACY_QTS_META_PREFIX, $msg );

    return empty ( $msg ) ? $msg : implode( '<br>', $msg );
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
     * @param string[] $msg array of messages, updated
     *
     * @return void
     */
    $migrate_meta = function ( $table, &$msg ) use ( $wpdb, $old_prefix, $new_prefix ) {
        $max_results = $wpdb->get_var( "SELECT count(*) FROM  $table WHERE meta_key like '$old_prefix%'" );
        if ( ! $max_results ) {
            $msg[] = sprintf( __( "No slugs to migrate from %s.", 'qtranslate' ), $table );
        } else {
            $results = $wpdb->query( "DELETE FROM $table WHERE meta_key like '$new_prefix%'" );
            $msg[]   = sprintf( __( "Deleted %s slugs from $table (%s).", 'qtranslate' ), $results ?: '0', $new_prefix );
            // Rename meta keys.
            $results = $wpdb->query( "UPDATE $table SET meta_key = REPLACE(meta_key, '$old_prefix', '$new_prefix') WHERE meta_key LIKE '$old_prefix%'" );
            $msg[]   = sprintf( __( "Migrated %s slugs from $table (%s).", 'qtranslate' ), $results ?: '0', $old_prefix );
        }
    };

    $msg = [];
    $wpdb->query( "START TRANSACTION" );
    $migrate_meta( $wpdb->postmeta, $msg );
    $migrate_meta( $wpdb->termmeta, $msg );
    if ( $db_commit ) {
        $wpdb->query( "COMMIT" );
    } else {
        $wpdb->query( "ROLLBACK" );
    }

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
        $msg[] = sprintf( __( "Deleted %s types from options.", 'qtranslate' ), count( $old_options ) );
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
    $msg[] = sprintf( __( "Migrated %s types from options.", 'qtranslate' ), count( $new_options ) );

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
