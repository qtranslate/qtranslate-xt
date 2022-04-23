<?php

define( 'QTX_MODULE_STATE_UNDEFINED', 0 );
define( 'QTX_MODULE_STATE_ACTIVE', 1 );
define( 'QTX_MODULE_STATE_INACTIVE', 2 );
define( 'QTX_MODULE_STATE_BLOCKED', 3 );


/**
 * Provide infos about the module states and the ability to load them. This never changes any state.
 *
 * @see QTX_Admin_Modules::update_modules_state()
 */
class QTX_Module_Loader {
    /**
     * Check if a module is active, by reading the state from the options.
     *
     * @param string $module_id
     *
     * @bool true if module active.
     */
    public static function is_module_active( $module_id ) {
        $modules_state = get_option( 'qtranslate_modules_state', array() );

        return isset( $modules_state[ $module_id ] ) && $modules_state[ $module_id ] === QTX_MODULE_STATE_ACTIVE;
    }

    /**
     * Loads modules previously activated in the options.
     *
     * Attention! This assumes the current states stored in the options are valid.
     * This doesn't perform any check, neither on the plugin conditions nor the folder structure.
     * In the worst case the state can be refreshed by reactivating the plugin.
     *
     * Note also the modules should be loaded before "qtranslate_init_language" is triggered.
     */
    public static function load_active_modules() {
        $modules_state = get_option( 'qtranslate_modules_state', array() );

        foreach ( $modules_state as $module_id => $state ) {
            if ( $state === QTX_MODULE_STATE_ACTIVE ) {
                include_once( QTRANSLATE_DIR . '/modules/' . $module_id . '/' . $module_id . '.php' );
            }
        }
    }
}
