<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function qtranxf_admin_notice_dismiss_script(): void {
    static $admin_notice_dismiss_script;
    if ( $admin_notice_dismiss_script ) {
        return;
    }
    $admin_notice_dismiss_script = true;
    wp_register_script( 'qtx_admin_notices', plugins_url( 'dist/notices.js', QTRANSLATE_FILE ), array( 'jquery' ), QTX_VERSION );
    wp_enqueue_script( 'qtx_admin_notices' );
}

function qtranxf_check_admin_notice( string $id ) {
    $messages = get_option( 'qtranslate_admin_notices' );
    if ( isset( $messages[ $id ] ) ) {
        return $messages[ $id ];
    }

    return false;
}

function qtranxf_update_option_admin_notices( $messages, string $id, bool $set = true ) {
    if ( ! is_array( $messages ) ) {
        $messages = array();
    }
    if ( $set ) {
        $messages[ $id ] = time();
    } else {
        unset( $messages[ $id ] );
    }
    update_option( 'qtranslate_admin_notices', $messages );

    return $messages;
}

/**
 * Update an admin notice to be set (hidden) / unset (shown).
 *
 * @param string $id
 * @param bool $set true to set the message as seen (hide), false to unset (show)
 *
 * @return array|mixed
 */
function qtranxf_update_admin_notice( string $id, bool $set ) {
    $messages = get_option( 'qtranslate_admin_notices', array() );

    return qtranxf_update_option_admin_notices( $messages, $id, $set );
}

/**
 * Unset all deprecated notices in the option so that they are shown again (making it "annoying" on purpose).
 * @return void
 */
function qtranxf_unset_admin_notices_deprecated(): void {
    $messages = get_option( 'qtranslate_admin_notices', array() );
    foreach ( $messages as $id => $message ) {
        if ( str_starts_with( $id, "deprecated" ) ) {
            unset( $messages[ $id ] );
        }
    }
    update_option( 'qtranslate_admin_notices', $messages );
}

function qtranxf_admin_notice_config_files_changed(): void {
    if ( ! qtranxf_check_admin_notice( 'config-files-changed' ) ) {
        return;
    }
    qtranxf_admin_notice_dismiss_script();
    $url = admin_url( 'options-general.php?page=qtranslate-xt#integration' );
    echo '<div class="notice notice-success qtranxs-notice-ajax is-dismissible" id="qtranxs-config-files-changed" action="unset"><p>';
    printf( __( 'Option "%s" for plugin %s has been auto-adjusted after recent changes in the site configuration. It might be a good idea to %sreview the changes%s in the list of configuration files.', 'qtranslate' ), '<a href="' . $url . '">' . __( 'Configuration Files', 'qtranslate' ) . '</a>', qtranxf_get_plugin_link(), '<a href="' . $url . '">', '</a>' );
    echo '<br/></p><p>';
    echo '<a class="button" href="' . $url . '">';
    printf( __( 'Review Option "%s"', 'qtranslate' ), __( 'Configuration Files', 'qtranslate' ) );
    echo '</a>&nbsp;&nbsp;&nbsp;<a class="button" href="https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide/" target="_blank">';
    echo __( 'Read Integration Guide', 'qtranslate' );
    echo '</a>&nbsp;&nbsp;&nbsp;<a class="button qtranxs-notice-dismiss" href="javascript:void(0);">' . __( 'I have already done it, dismiss this message.', 'qtranslate' );
    echo '</a></p></div>';
}

add_action( 'admin_notices', 'qtranxf_admin_notice_config_files_changed' );

function qtranxf_admin_notice_first_install(): void {
    if ( qtranxf_check_admin_notice( 'initial-install' ) ) {
        return;
    }
    qtranxf_admin_notice_dismiss_script();
    echo '<div class="notice notice-info qtranxs-notice-ajax notice is-dismissible" id="qtranxs-initial-install"><p>';
    printf( __( 'Are you new to plugin %s?', 'qtranslate' ), qtranxf_get_plugin_link() );
    echo '<br/>';
    echo '</p><p><a class="button" href="https://github.com/qtranslate/qtranslate-xt/wiki/Startup-Guide/" target="_blank">';
    echo __( 'Read Startup Guide', 'qtranslate' );
    echo '</a>&nbsp;&nbsp;&nbsp;<a class="button qtranxs-notice-dismiss" href="javascript:void(0);">' . __( 'I have already done it, dismiss this message.', 'qtranslate' );
    echo '</a></p></div>';
}

add_action( 'admin_notices', 'qtranxf_admin_notice_first_install' );

/**
 * Show admin notice for block editor (Gutenberg)
 */
function qtranxf_admin_notices_block_editor(): void {
    if ( qtranxf_check_admin_notice( 'gutenberg-support' ) ) {
        return;
    }
    qtranxf_admin_notice_dismiss_script();
    ?>
    <div class="notice notice-warning qtranxs-notice-ajax is-dismissible" id="qtranxs-gutenberg-support">
        <p><?php printf( __( '<b>Caution!</b> The block editor (Gutenberg) is supported only recently in %s with some limitations. Use at your own discretion!', 'qtranslate' ), 'qTranslate&#8209;XT' ); ?></p>
        <p><?php printf( __( 'Currently only the single language edit mode is supported. For more details, please read carefully our <a href="%s">Gutenberg FAQ</a>.', 'qtranslate' ), 'https://github.com/qtranslate/qtranslate-xt/wiki/FAQ#gutenberg' ); ?></p>
        <?php if ( ! qtranxf_is_classic_editor_supported() ):
            $link_classic = admin_url( 'plugin-install.php?tab=plugin-information&plugin=classic-editor' );
            $link_plugins = admin_url( 'plugins.php' ); ?>
            <p><?php printf( __( 'It is recommended to install the <a href="%s">%s</a> in your <a href="%s">plugins</a>.', 'qtranslate' ), $link_classic, 'Classic Editor', $link_plugins ); ?></p>
        <?php endif; ?>
        <p>
            <a class="button qtranxs-notice-dismiss"
               href="javascript:void(0);"><?php _e( 'I have already done it, dismiss this message.', 'qtranslate' ); ?></a>
        </p>
    </div>
    <?php
}

add_action( 'admin_notices', 'qtranxf_admin_notices_block_editor' );

function qtranxf_admin_notices_slugs_migrate(): void {
    if ( qtranxf_check_admin_notice( 'slugs-migrate' ) || ! QTX_Module_Loader::is_module_active( 'slugs' ) ) {
        return;
    }
    $old_value = get_option( 'qts_options' );  // Very quick check to avoid loading more code.
    if ( ! $old_value ) {
        return;
    }
    require_once QTRANSLATE_DIR . '/src/modules/slugs/admin_migrate_qts.php';
    $msg = qtranxf_slugs_check_migrate_qts();  // More advanced checks with QTS meta.
    if ( empty( $msg ) ) {
        return;
    }
    qtranxf_admin_notice_dismiss_script();
    echo '<div class="notice notice-warning qtranxs-notice-ajax is-dismissible" id="qtranxs-slugs-migrate"><p>';
    $options_link = admin_url( 'options-general.php?page=qtranslate-xt#import' );
    echo '<p>' . sprintf( __( '%s : found slugs meta that can be migrated. Go to the <a href="%s">import settings</a> to migrate.', 'qtranslate' ), qtranxf_get_plugin_link(), $options_link ) . '</p>';
    echo '<p>' . $msg . '</p>';
    echo '</p><p><a class="button qtranxs-notice-dismiss" href="javascript:void(0);">' . __( 'I have already done it, dismiss this message.', 'qtranslate' );
    echo '</a></p></div>';
}

add_action( 'admin_notices', 'qtranxf_admin_notices_slugs_migrate' );

function qtranxf_admin_notice_deactivate_plugin( $name, $plugin ): void {
    deactivate_plugins( $plugin, true );
    $d        = dirname( $plugin );
    $link     = '<a href="https://wordpress.org/plugins/' . $d . '/" target="_blank">' . $name . '</a>';
    $qtxnm    = 'qTranslate&#8209;XT';
    $qtxlink  = qtranxf_get_plugin_link();
    $imported = false;
    $func     = 'qtranxf_migrate_import_' . str_replace( '-', '_', dirname( $plugin ) );
    if ( function_exists( $func ) ) {
        global $wpdb;
        $options = $wpdb->get_col( "SELECT `option_name` FROM {$wpdb->options} WHERE `option_name` LIKE 'qtranslate_%'" );
        if ( empty( $options ) ) {
            $func();
            $imported = true;
        }
    }
    $s   = '</p><p>' . sprintf( __( 'It might be a good idea to review %smigration instructions%s, if you have not yet done so.', 'qtranslate' ), '<a href="https://github.com/qtranslate/qtranslate-xt/wiki/Migration-Guide/" target="_blank">', '</a>' ) . '</p><p><a class="button" href="">';
    $msg = sprintf( __( 'Activation of plugin %s deactivated plugin %s since they cannot run simultaneously.', 'qtranslate' ), $qtxlink, $link ) . ' ';
    if ( $imported ) {
        $msg .= sprintf( __( 'The compatible settings from %s have been imported to %s. Further tuning, import, export and reset of options can be done at Settings/Languages configuration page, once %s is running.%sContinue%s', 'qtranslate' ), $name, $qtxnm, $qtxnm, $s, '</a>' );
    } else {
        $msg .= sprintf( __( 'You may import/export compatible settings from %s to %s on Settings/Languages configuration page, once %s is running.%sContinue%s', 'qtranslate' ), $name, $qtxnm, $qtxnm, $s, '</a>' );
    }
    wp_die( '<p>' . $msg . '</p>' );
}

function qtranxf_admin_notice_plugin_conflict( $title, $plugin ): void {
    if ( ! is_plugin_active( $plugin ) ) {
        return;
    }
    $me   = qtranxf_get_plugin_link();
    $link = '<a href="https://wordpress.org/plugins/' . dirname( $plugin ) . '/" target="_blank">' . $title . '</a>';
    echo '<div class="notice notice-error is-dismissible"><p>';
    printf( __( '%sError:%s plugin %s cannot run concurrently with plugin %s. You may import and export compatible settings between %s and %s on Settings/<a href="%s">Languages</a> configuration page. Then you have to deactivate one of the plugins to continue.', 'qtranslate' ), '<strong>', '</strong>', $me, $link, 'qTranslate&#8209;XT', $title, admin_url( 'options-general.php?page=qtranslate-xt' ), 'qtranslate' );
    echo ' ';
    printf( __( 'It might be a good idea to review %smigration instructions%s, if you have not yet done so.', 'qtranslate' ), '<a href="https://github.com/qtranslate/qtranslate-xt/wiki/Migration-Guide/" target="_blank">', '</a>' );

    $nonce = wp_create_nonce( 'deactivate-plugin_' . $plugin );
    echo '</p><p> &nbsp; &nbsp; &nbsp; &nbsp;<a class="button" href="' . admin_url( 'plugins.php?action=deactivate&plugin=' . urlencode( $plugin ) . '&plugin_status=all&paged=1&s&_wpnonce=' . $nonce ) . '"><strong>' . sprintf( __( 'Deactivate %s', 'qtranslate' ), $title ) . '</strong></a>';
    $nonce = wp_create_nonce( 'deactivate-plugin_qtranslate-xt/qtranslate.php' );
    echo ' &nbsp; &nbsp; &nbsp; &nbsp;<a class="button" href="' . admin_url( 'plugins.php?action=deactivate&plugin=' . urlencode( 'qtranslate-xt/qtranslate.php' ) . '&plugin_status=all&paged=1&s&_wpnonce=' . $nonce ) . '"><strong>' . sprintf( __( 'Deactivate %s', 'qtranslate' ), 'qTranslate&#8209;XT' ) . '</strong></a>';
    echo '</p></div>';
}

function qtranxf_admin_notices_plugin_conflicts(): void {
    qtranxf_admin_notice_plugin_conflict( 'qTranslate', 'qtranslate/qtranslate.php' );
    qtranxf_admin_notice_plugin_conflict( 'mqTranslate', 'mqtranslate/mqtranslate.php' );
    qtranxf_admin_notice_plugin_conflict( 'qTranslate Plus', 'qtranslate-xp/ppqtranslate.php' );
    qtranxf_admin_notice_plugin_conflict( 'zTranslate', 'ztranslate/ztranslate.php' );
    do_action( 'qtranslate_admin_notices_plugin_conflicts' );
}

add_action( 'admin_notices', 'qtranxf_admin_notices_plugin_conflicts' );

function qtranxf_admin_notices_errors(): void {
    $msgs = get_option( 'qtranslate_config_errors' );
    if ( ! is_array( $msgs ) ) {
        return;
    }
    foreach ( $msgs as $key => $msg ) {
        echo '<div class="notice notice-error is-dismissible" id="qtranxs_config_error_' . $key . '"><p><a href="' . admin_url( 'options-general.php?page=qtranslate-xt' ) . '">qTranslate&#8209;XT</a>:&nbsp;';
        // translators: Colon after a title. Template reused from language menu item.
        echo sprintf( __( '%s:', 'qtranslate' ), '<strong>' . __( 'Error', 'qtranslate' ) . '</strong>' );
        echo '&nbsp;' . $msg . '</p></div>';
    }
}

add_action( 'admin_notices', 'qtranxf_admin_notices_errors' );

/**
 * Return meta info for deprecated settings.
 *
 * @return array[] Attributes to handle notices of deprecated settings.
 */
function qtranx_admin_deprecated_settings(): array {
    global $q_config;

    return [
        'custom_i18n_config'             => [
            'check'   => ! empty( $q_config['custom_i18n_config'] ),
            'name'    => __( 'Custom Configuration', 'qtranslate' ),
            'section' => 'integration',
            'hint'    => '<a href="https://github.com/qtranslate/qtranslate-xt/issues/1012">github #1024</a>',
        ],
        'qtrans_compatibility'           => [
            'check'   => $q_config['qtrans_compatibility'],
            'name'    => __( 'Compatibility Functions', 'qtranslate' ),
            'section' => 'integration',
            'hint'    => '<a href="https://github.com/qtranslate/qtranslate-xt/issues/1461">github #1461</a>',
        ],
        'use_strftime_date_override'     => [
            'check'   => $q_config['use_strftime'] == QTX_DATE_OVERRIDE,
            'name'    => __( 'Date / Time Conversion', 'qtranslate' ),
            'section' => 'advanced',
            'hint'    => '<a href="https://github.com/qtranslate/qtranslate-xt/issues/1234">github #1234</a>',
        ],
        'use_strftime_strftime_override' => [
            'check'   => $q_config['use_strftime'] == QTX_STRFTIME_OVERRIDE,
            'name'    => __( 'Date / Time Conversion', 'qtranslate' ),
            'section' => 'advanced',
            'hint'    => '<a href="https://github.com/qtranslate/qtranslate-xt/issues/1234">github #1234</a>',
        ],
    ];
}

/**
 * Check if deprecated settings are set and display a dismissible warning for each case.
 *
 * IDs are used as "deprecated-<id>" keys in the admin notice option.
 * @return void
 */
function qtranxf_admin_notices_deprecated_settings(): void {
    $options_url = admin_url( 'options-general.php?page=qtranslate-xt' );
    $title       = '<a href="' . $options_url . '">qTranslate-XT</a>&nbsp;';
    foreach ( qtranx_admin_deprecated_settings() as $setting_key => $setting_data ) {
        $setting_id = "deprecated-" . $setting_key;
        if ( qtranxf_check_admin_notice( $setting_id ) ) {
            continue;
        }
        if ( $setting_data['check'] ) {
            qtranxf_admin_notice_dismiss_script();
            $message     = $title . '&nbsp;';
            $message     .= sprintf( __( '%s:', 'qtranslate' ), '<strong>' . __( 'Warning', 'qtranslate' ) . '</strong>' ) . '&nbsp;';
            $section_url = $options_url . '#' . $setting_data['section'];
            switch ( $setting_data['section'] ) {
                case 'advanced':
                    $section_name = __( 'Advanced', 'qtranslate' );
                    break;
                case 'integration':
                    $section_name = __( 'Integration', 'qtranslate' );
                    break;
                default:
                    $section_name = '?';
                    break;
            }
            $message .= sprintf( __( 'The value set for option "%s" is deprecated, it will not be supported in the future. Go to "%s" settings to change it.', 'qtranslate' ),
                '<strong>' . $setting_data['name'] . '</strong>', sprintf( '<a href="%s">%s</a>', $section_url, $section_name ) );
            $message .= '&nbsp;' . sprintf( __( 'For more information, see %s.', 'qtranslate' ), $setting_data['hint'] );

            echo '<div class="notice notice-warning qtranxs-notice-ajax is-dismissible" id="qtranxs-' . $setting_id . '"><p>' . $message . '</p></div>';
        }
    }
}

function qtranxf_ajax_qtranslate_admin_notice(): void {
    if ( ! isset( $_POST['notice_id'] ) ) {
        return;
    }
    $id  = sanitize_text_field( $_POST['notice_id'] );
    $set = empty( $_POST['notice_action'] );
    qtranxf_update_admin_notice( $id, $set );
}

add_action( 'wp_ajax_qtranslate_admin_notice', 'qtranxf_ajax_qtranslate_admin_notice' );
