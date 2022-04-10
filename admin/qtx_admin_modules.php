<?php

class QTX_Admin_Modules {

    /**
     * Register hooks for modules and related plugins
     */
    public static function register_hooks() {
        add_action( 'admin_notices', 'QTX_Admin_Modules::admin_notices' );
        add_action( 'activated_plugin', 'QTX_Admin_Modules::register_plugin_activated' );
        add_action( 'deactivated_plugin', 'QTX_Admin_Modules::register_plugin_deactivated' );
    }

    /**
     * Update the modules status for plugin integration.
     * The valid modules are stored in the 'qtranslate_modules' option, telling which module should be loaded.
     * Note each module can enable hooks both for admin and front requests.
     *
     * @param callable $func_is_active callback to evaluate if a plugin is active
     */
    public static function update_modules_status( $func_is_active = 'is_plugin_active' ) {
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        $module_defs    = QTX_Modules_Handler::get_modules_defs();
        $option_modules = array();
        foreach ( $module_defs as $module_def ) {
            $status = self::check_module( $module_def, $func_is_active );
            // inactive modules are skipped, keep only active or incompatible
            if ( isset( $status ) ) {
                $option_modules[ $module_def['id'] ] = $status;
            }
        }

        $old_option_modules = get_option( 'qtranslate_modules' );
        update_option( 'qtranslate_modules', $option_modules );

        // trigger info notices only if changed
        if ( $old_option_modules != $option_modules ) {
            set_transient( 'qtranslate_notice_modules', true, 5 );
        }
    }

    /**
     * Check if an integration module can be activated:
     * - if the linked plugin to be integrated is active (or at least one in case of multiple plugins)
     * - if no incompatible plugin (legacy) prevents it. In that case, an admin notice is displayed.
     *
     * @param array $module_def
     * @param callable $func_is_active callback to evaluate if a plugin is active
     *
     * @return integer module status
     */
    public static function check_module( $module_def, $func_is_active = 'is_plugin_active' ) {
        $module_status = QTX_MODULE_STATUS_INACTIVE;

        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        // TODO the call_user_func should be replaced by direct calls from PHP7
        $integration_plugin = $module_def['plugin'];
        if ( is_array( $integration_plugin ) ) {
            $active = false;
            foreach ( $integration_plugin as $item_plugin ) {
                if ( call_user_func( $func_is_active, $item_plugin ) ) {
                    $active = true;
                    break;
                }
            }
        } else if ( is_bool( $integration_plugin ) ) {
            $active = $integration_plugin;
        } else {
            $active = call_user_func( $func_is_active, $integration_plugin );
        }

        if ( $active ) {
            if ( isset( $module_def['incompatible'] ) && call_user_func( $func_is_active, $module_def['incompatible'] ) ) {
                $module_status = QTX_MODULE_STATUS_BLOCKED;
            } else {
                $module_status = QTX_MODULE_STATUS_ACTIVE;
            }
        }

        return $module_status;
    }

    /**
     * Hook called after a plugin is activated
     *
     * @param string $updated_plugin name of the plugin
     */
    public static function register_plugin_activated( $updated_plugin ) {
        // we could use "is_plugin_active" because the "active_plugins" option is updated BEFORE the action is called
        // however this is not the case for the deactivation so for consistency we use the counterpart check
        self::update_modules_status( function ( $test_plugin ) use ( $updated_plugin ) {
            return ( $test_plugin === $updated_plugin ) ? true : is_plugin_active( $test_plugin );
        } );
    }

    /**
     * Hook called after a plugin is deactivated
     *
     * @param string $updated_plugin name of the plugin
     */
    public static function register_plugin_deactivated( $updated_plugin ) {
        // we can't use "is_plugin_active" because the "active_plugins" option is updated AFTER the action is called!
        // this is a problem of WP Core, but we pass a custom function as a workaround
        self::update_modules_status( function ( $test_plugin ) use ( $updated_plugin ) {
            return ( $test_plugin === $updated_plugin ) ? false : is_plugin_active( $test_plugin );
        } );
    }

    public static function update_manual_enabled_modules() {
        global $q_config;
        $options_modules = get_option( 'qtranslate_modules', array() );
        foreach ( $q_config['ma_module_enabled'] as $module_id => $module_enabled ) {
            $check_status = self::check_module( QTX_Modules_Handler::get_module_def_by_id( $module_id ) );
            if ( $check_status == QTX_MODULE_STATUS_ACTIVE ) {
                // The state of the checked module can be changed by the admin.
                if ( $module_enabled && $options_modules[ $module_id ] != QTX_MODULE_STATUS_ACTIVE ) {
                    $options_modules[ $module_id ] = QTX_MODULE_STATUS_ACTIVE;
                } else if ( ! $module_enabled && $options_modules[ $module_id ] == QTX_MODULE_STATUS_ACTIVE ) {
                    $options_modules[ $module_id ] = QTX_MODULE_STATUS_INACTIVE;
                }
            } else {
                // TODO fix update when module plugin state changed
                $q_config['ma_module_enabled'][ $module_id ] = false;
                if ( $options_modules[ $module_id ] != $check_status ) {
                    $options_modules[ $module_id ] = $check_status;
                }
            }
        }

        update_option( 'qtranslate_modules', $options_modules );
        QTX_Modules_Handler::load_active_modules();
    }

    public static function admin_notices() {
        $options_modules = get_option( 'qtranslate_modules', array() );
        if ( empty( $options_modules ) ) {
            $msg   = '<p>' . sprintf( __( 'Modules status undefined in %s. Please deactivate it and reactivate it again from the plugins page.', 'qtranslate' ), 'qTranslate&#8209;XT' ) . '</p>';
            $nonce = wp_create_nonce( 'deactivate-plugin_qtranslate-xt/qtranslate.php' );
            $msg   .= '<p><a class="button" href="' . admin_url( 'plugins.php?action=deactivate&plugin=' . urlencode( 'qtranslate-xt/qtranslate.php' ) . '&plugin_status=all&paged=1&s&_wpnonce=' . $nonce ) . '"><strong>' . sprintf( __( 'Deactivate %s', 'qtranslate' ), 'qTranslate&#8209;XT' ) . '</strong></a></p>';
            echo '<div class="notice notice-warning is-dismissible">' . $msg . '</div>';

            return;
        }

        $module_defs    = QTX_Modules_Handler::get_modules_defs();
        $active_modules = array();
        foreach ( $module_defs as $module_def ) {
            if ( ! array_key_exists( $module_def['id'], $options_modules ) ) {
                continue;
            }

            switch ( $options_modules[ $module_def['id'] ] ) {
                case QTX_MODULE_STATUS_BLOCKED:
                    $incompatible_plugin = $module_def['incompatible'];
                    $plugin_data         = get_plugin_data( WP_PLUGIN_DIR . '/' . $incompatible_plugin, false, true );
                    $plugin_name         = $plugin_data['Name'];
                    $url_deactivate      = esc_url( wp_nonce_url( admin_url( 'plugins.php?action=deactivate&plugin=' . urlencode( $incompatible_plugin ) ), 'deactivate-plugin_' . $incompatible_plugin ) );
                    $msg                 = '<p>' . sprintf( __( 'The plugin "%s" is incompatible with the module "%s" of %s. Please disable it.', 'qtranslate' ), $plugin_name, $module_def['name'], 'qTranslate&#8209;XT' ) . '</p>';
                    $msg                 .= '<p><a class="button" href="' . $url_deactivate . '"><strong>' . sprintf( __( 'Deactivate plugin %s', 'qtranslate' ), $plugin_name ) . '</strong></a>';
                    echo '<div class="notice notice-warning is-dismissible">' . $msg . '</div>';
                    break;
                case QTX_MODULE_STATUS_ACTIVE:
                    $active_modules[] = $module_def['name'];
                    break;
            }
        }
        if ( ! empty( $active_modules ) && get_transient( 'qtranslate_notice_modules' ) ) {
            $msg_modules = '<ul><li>' . implode( '</li><li>', $active_modules ) . '</li></ul>';
            $msg_infos   = '<p>' . sprintf( __( 'The following modules are now active in %s:', 'qtranslate' ), 'qTranslate&#8209;XT' ) . $msg_modules . '</p>';
            echo '<div class="notice notice-info is-dismissible">' . $msg_infos . '</div>';
            delete_transient( 'qtranslate_notice_modules' );
        }
    }

    /**
     * Retrieve infos for all modules (for display).
     * The status is retrieved from the modules option.
     */
    public static function get_modules_infos() {
        $module_defs     = QTX_Modules_Handler::get_modules_defs();
        $options_modules = get_option( 'qtranslate_modules', array() );
        $infos           = array();
        foreach ( $module_defs as $module_def ) {
            $info         = array();
            $info['id']   = $module_def['id'];
            $info['name'] = $module_def['name'];
            $status       = array_key_exists( $module_def['id'], $options_modules ) ? $options_modules[ $module_def['id'] ] : QTX_MODULE_STATUS_UNDEFINED;
            switch ( $status ) {
                case QTX_MODULE_STATUS_ACTIVE:
                    $info['plugin'] = $module_def['plugin'] === true ? '-' : __( 'Active', 'qtranslate' );
                    $info['module'] = __( 'Active', 'qtranslate' );
                    $info['icon']   = 'dashicons-yes';
                    $info['color']  = 'green';
                    break;
                case QTX_MODULE_STATUS_INACTIVE:
                    // TODO: fix plugin state may not be inactive
                    $info['plugin'] = $module_def['plugin'] === true ? '-' : __( 'Inactive', 'qtranslate' );
                    $info['module'] = __( 'Inactive', 'qtranslate' );
                    $info['icon']   = 'dashicons-no-alt';
                    $info['color']  = '';
                    break;
                case QTX_MODULE_STATUS_BLOCKED:
                    $info['plugin'] = $module_def['plugin'] === true ? '-' : __( 'Active', 'qtranslate' );
                    $info['module'] = __( 'Blocked', 'qtranslate' );
                    $info['icon']   = 'dashicons-warning';
                    $info['color']  = 'orange';
                    break;
                case QTX_MODULE_STATUS_UNDEFINED:
                default:
                    $info['plugin'] = $module_def['plugin'] === true ? '-' : __( 'Undefined', 'qtranslate' );
                    $info['module'] = __( 'Inactive', 'qtranslate' );
                    $info['icon']   = 'dashicons-editor-help';
                    $info['color']  = '';
                    break;
            }

            array_push( $infos, $info );
        }

        return $infos;
    }
}
