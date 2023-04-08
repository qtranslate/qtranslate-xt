<?php

require_once QTRANSLATE_DIR . '/src/modules/admin_module.php';
require_once QTRANSLATE_DIR . '/src/modules/module_state.php';

/**
 * Module admin management, taking care of the state updates and notices.
 */
class QTX_Admin_Module_Manager {
    /**
     * Register hooks for modules and related plugins
     */
    public static function register_hooks(): void {
        add_action( 'admin_notices', 'QTX_Admin_Module_Manager::admin_notices' );
        add_action( 'activated_plugin', 'QTX_Admin_Module_Manager::register_plugin_activated' );
        add_action( 'deactivated_plugin', 'QTX_Admin_Module_Manager::register_plugin_deactivated' );
    }

    /**
     * Update the state of all modules for plugin integration.
     *
     * Each module is activated:
     * - if the conditions with integration and incompatible plugins (optional) are met
     * AND
     * - if the `admin_enabled_modules` admin option is checked for that module.
     *
     * Update the QTX_OPTIONS_MODULES_STATE option, telling which module should be loaded.
     * Note each module can enable hooks both for admin and front requests.
     *
     * @param callable $func_is_active callback to evaluate if a plugin is active
     */
    public static function update_modules_state( $func_is_active = 'is_plugin_active' ): void {
        global $q_config;

        $option_modules = array();
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        foreach ( QTX_Admin_Module::get_modules() as $module ) {
            $state = self::can_module_be_activated( $module, $func_is_active );
            if ( $state == QTX_MODULE_STATE_ACTIVE ) {
                // The admin options matter only if the module can be activated, otherwise the hard conditions prevail.
                if ( isset ( $q_config['admin_enabled_modules'][ $module->id ] ) && ! $q_config['admin_enabled_modules'][ $module->id ] ) {
                    $state = QTX_MODULE_STATE_INACTIVE;
                }
            }
            $option_modules[ $module->id ] = $state;
        }

        $old_option_modules = get_option( QTX_OPTIONS_MODULES_STATE );
        update_option( QTX_OPTIONS_MODULES_STATE, $option_modules );

        // Trigger info notices and potential loading only if changed.
        if ( $old_option_modules != $option_modules ) {
            set_transient( 'qtranslate_notice_modules', true, 5 );
            QTX_Module_Loader::load_active_modules();
        }
    }

    /**
     * Check if the module has a related plugin active, if any.
     *
     * @param QTX_Admin_Module $module
     * @param callable $func_is_active
     *
     * @return bool true if the integration plugin is active OR if the module does not have any.
     */
    public static function is_module_plugin_active( QTX_Admin_Module $module, $func_is_active = 'is_plugin_active' ): bool {
        if ( empty( $module->plugins ) ) {
            return true; // Attention: should not be interpreted as "having a plugin".
        }

        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        // TODO the call_user_func should be replaced by direct calls from PHP7
        foreach ( $module->plugins as $plugin ) {
            if ( call_user_func( $func_is_active, $plugin ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an integration module can be activated:
     * - if the linked plugin to be integrated is active (or at least one in case of multiple plugins)
     * - if no incompatible plugin (legacy) prevents it. In that case, an admin notice is displayed.
     *
     * ATTENTION: the admin checkboxes are ignored in this check! This evaluates the "potential" state.
     *
     * @param QTX_Admin_Module $module
     * @param callable $func_is_active callback to evaluate if a plugin is active
     *
     * @return integer module state
     */
    public static function can_module_be_activated( QTX_Admin_Module $module, $func_is_active = 'is_plugin_active' ): int {
        $state = QTX_MODULE_STATE_INACTIVE;

        if ( self::is_module_plugin_active( $module, $func_is_active ) ) {
            if ( isset( $module->incompatible ) && call_user_func( $func_is_active, $module->incompatible ) ) {
                $state = QTX_MODULE_STATE_BLOCKED;
            } else {
                $state = QTX_MODULE_STATE_ACTIVE;
            }
        }

        return $state;
    }

    /**
     * Hook called after a plugin is activated.
     *
     * @param string $activated_plugin name of the plugin being activated.
     */
    public static function register_plugin_activated( string $activated_plugin ): void {
        // We could use "is_plugin_active" because the "active_plugins" option is updated BEFORE the action is called.
        // However, this is not the case for the deactivation so for consistency we use the counterpart check.
        self::update_modules_state( function ( $check_plugin ) use ( $activated_plugin ) {
            return ( $check_plugin === $activated_plugin ) || is_plugin_active( $check_plugin );
        } );
    }

    /**
     * Hook called after a plugin is deactivated.
     *
     * @param string $deactivated_plugin name of the plugin being deactivated.
     */
    public static function register_plugin_deactivated( string $deactivated_plugin ): void {
        // We can't use "is_plugin_active" because the "active_plugins" option is updated AFTER the action is called!
        // This is a problem of WP Core, but we pass a custom function as a workaround.
        self::update_modules_state( function ( $check_plugin ) use ( $deactivated_plugin ) {
            return ( $check_plugin !== $deactivated_plugin ) && is_plugin_active( $check_plugin );
        } );
    }

    public static function admin_notices(): void {
        $options_modules = get_option( QTX_OPTIONS_MODULES_STATE, array() );
        if ( empty( $options_modules ) ) {
            $msg   = '<p>' . sprintf( __( 'Modules state undefined in %s. Please deactivate it and reactivate it again from the plugins page.', 'qtranslate' ), 'qTranslate&#8209;XT' ) . '</p>';
            $nonce = wp_create_nonce( 'deactivate-plugin_qtranslate-xt/qtranslate.php' );
            $msg   .= '<p><a class="button" href="' . admin_url( 'plugins.php?action=deactivate&plugin=' . urlencode( 'qtranslate-xt/qtranslate.php' ) . '&plugin_status=all&paged=1&s&_wpnonce=' . $nonce ) . '"><strong>' . sprintf( __( 'Deactivate %s', 'qtranslate' ), 'qTranslate&#8209;XT' ) . '</strong></a></p>';
            echo '<div class="notice notice-warning is-dismissible">' . $msg . '</div>';

            return;
        }

        $active_modules = array();
        $modules        = QTX_Admin_Module::get_modules();
        foreach ( $modules as $module ) {
            if ( ! array_key_exists( $module->id, $options_modules ) ) {
                continue;
            }

            switch ( $options_modules[ $module->id ] ) {
                case QTX_MODULE_STATE_BLOCKED:
                    $incompatible_plugin = $module->incompatible;
                    $plugin_data         = get_plugin_data( WP_PLUGIN_DIR . '/' . $incompatible_plugin, false, true );
                    $plugin_name         = $plugin_data['Name'];
                    $url_deactivate      = esc_url( wp_nonce_url( admin_url( 'plugins.php?action=deactivate&plugin=' . urlencode( $incompatible_plugin ) ), 'deactivate-plugin_' . $incompatible_plugin ) );
                    $msg                 = '<p>' . sprintf( __( 'The plugin "%s" is incompatible with the module "%s" of %s. Please disable it.', 'qtranslate' ), $plugin_name, $module->name, 'qTranslate&#8209;XT' ) . '</p>';
                    $msg                 .= '<p><a class="button" href="' . $url_deactivate . '"><strong>' . sprintf( __( 'Deactivate plugin %s', 'qtranslate' ), $plugin_name ) . '</strong></a>';
                    echo '<div class="notice notice-warning is-dismissible">' . $msg . '</div>';
                    break;
                case QTX_MODULE_STATE_ACTIVE:
                    $active_modules[] = $module->name;
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
}
