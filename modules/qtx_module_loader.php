<?php

define( 'QTX_MODULE_STATE_UNDEFINED', 0 );
define( 'QTX_MODULE_STATE_ACTIVE', 1 );
define( 'QTX_MODULE_STATE_INACTIVE', 2 );
define( 'QTX_MODULE_STATE_BLOCKED', 3 );

require_once( QTRANSLATE_DIR . '/modules/qtx_module_setup.php' );

/**
 * Provide infos about the module states and the ability to load them. This never changes any state.
 */
class QTX_Module_Loader {
    /**
     * Get the modules previously activated in the options after validation for plugin integration on admin-side.
     * Note these should be loaded before "qtranslate_init_language" is triggered.
     *
     * @return QTX_Module[]
     * @see QTX_Admin_Modules::update_modules_state()
     */
    public static function get_active_modules() {
        $options_modules = get_option( 'qtranslate_modules_state', array() );
        if ( ! is_array( $options_modules ) ) {
            return array();
        }

        $active_modules = array();
        $modules        = QTX_Module_Setup::get_modules();
        foreach ( $modules as $module ) {
            if ( ! array_key_exists( $module->id, $options_modules ) ) {
                continue;
            }
            $state = $options_modules[ $module->id ];
            if ( $state === QTX_MODULE_STATE_ACTIVE ) {
                $active_modules[] = $module;
            }
        }

        return $active_modules;
    }

    /**
     * Check if a module is active, by reading the state from the options.
     *
     * @param string $module_id
     *
     * @bool true if module active.
     */
    public static function is_module_active( $module_id ) {
        $options_modules = get_option( 'qtranslate_modules_state', array() );

        return isset( $options_modules[ $module_id ] ) && $options_modules[ $module_id ] === QTX_MODULE_STATE_ACTIVE;
    }

    /**
     * Loads modules previously activated in the options after validation for plugin integration on admin-side.
     * Note these should be loaded before "qtranslate_init_language" is triggered.
     *
     * @see QTX_Admin_Modules::update_modules_state()
     */
    public static function load_active_modules() {
        $modules = self::get_active_modules();
        foreach ( $modules as $module ) {
            include_once( QTRANSLATE_DIR . '/modules/' . $module->id . '/' . $module->id . '.php' );
        }
    }
}
