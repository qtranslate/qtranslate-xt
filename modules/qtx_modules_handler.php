<?php

define( 'QTX_MODULE_STATUS_UNDEFINED', 0 );
define( 'QTX_MODULE_STATUS_ACTIVE', 1 );
define( 'QTX_MODULE_STATUS_INACTIVE', 2 );
define( 'QTX_MODULE_STATUS_BLOCKED', 3 );
define( 'QTRANSLATE_MODULES_DIR', QTRANSLATE_DIR . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR  );
define( 'QTRANSLATE_MODULES_CONFIG', QTRANSLATE_MODULES_DIR . '%s' . DIRECTORY_SEPARATOR . 'module-config.php' );
define( 'QTRANSLATE_MODULES_FILE', QTRANSLATE_MODULES_DIR . '%s' . DIRECTORY_SEPARATOR . '%s.php' );

class QTX_Modules_Handler {
    /**
     * Loads modules previously enabled in the options after validation for plugin integration on admin-side.
     * Note these should be loaded before "qtranslate_init_language" is triggered.
     *
     * @see QTX_Admin_Modules::update_modules_status()
     */
    public static function load_modules_enabled() {
        $def_modules     = self::get_modules_defs();
        $options_modules = get_option( 'qtranslate_modules', array() );
        if ( ! is_array( $options_modules ) ) {
            return null;
        }

        foreach ( $def_modules as $def_module ) {
            if ( ! array_key_exists( $def_module['id'], $options_modules ) ) {
                continue;
            }
            $module_status = $options_modules[ $def_module['id'] ];
            if ( $module_status === QTX_MODULE_STATUS_ACTIVE ) {
                include_once( sprintf( QTRANSLATE_MODULES_FILE, $def_module['id'], $def_module['id'] ) );
            }
        }
    }

    public static function update_manual_enabled_modules() {
        global $q_config;
        $options_modules = get_option( 'qtranslate_modules', array() );
        $changed         = false;
        foreach ( $q_config['ma_module_enabled'] as $module_id => $module_enabled ) {
            if ( $module_enabled && $options_modules[ $module_id ] != QTX_MODULE_STATUS_ACTIVE ) {
                $options_modules[ $module_id ] = QTX_MODULE_STATUS_ACTIVE;
                $changed                       = true;
            } else if ( ! $module_enabled && $options_modules[ $module_id ] == QTX_MODULE_STATUS_ACTIVE ) {
                $options_modules[ $module_id ] = QTX_MODULE_STATUS_INACTIVE;
                $changed                       = true;
            }
        }

        if ( $changed ) {
            update_option( 'qtranslate_modules', $options_modules );
            self::load_modules_enabled();
            do_action( 'qtx_ma_modules_updated' );
        }
    }

    /**
     * Retrieve the definitions of the built-in integration modules.
     * Each module is defined by:
     * - id: key used to identify the module, also used in options
     * - name: for user display
     * - plugin (mixed): WP identifier of plugin to be integrated, or array of plugin identifiers
     * - incompatible: WP identifier of plugin incompatible with the module
     *
     * @return array ordered by name
     */
    public static function get_modules_defs() {
        $defaults=array(
                'id'                => '',
                'name'              => '',
                'plugin'            => true,
                'incompatible'      => '',
                'manual_activation' => false,
                );
        $cdir = scandir( QTRANSLATE_MODULES_DIR );
        $modules_defs=array();
        foreach ($cdir as $key => $value)
        {
            if ( !in_array( $value, array( ".", ".." ) ) &&
                    is_dir( QTRANSLATE_MODULES_DIR . $value ) &&
                    file_exists( sprintf( QTRANSLATE_MODULES_CONFIG, $value ) ) ) {
                $module_config=array();
                include( sprintf( QTRANSLATE_MODULES_CONFIG, $value ) );
                $result=array_merge($defaults,$module_config);
                if ($result['id']==$value && file_exists( sprintf( QTRANSLATE_MODULES_FILE, $value, $value ) ) ){
                    $modules_defs[]=$result;
                }
           }
        }

        return $modules_defs;
    }

    public static function ma_modules_default_options() {
        $module_defs = self::get_modules_defs();
        $response    = array();
        foreach ( $module_defs as $module ) {
            if ( isset( $module['manual_activation'] ) && $module['manual_activation'] == true ) {
                $response[ $module['id'] ] = false;
            }
        }

        return $response;
    }

    public static function get_module_def_by_id( $module_id ) {
        $module_defs = self::get_modules_defs();
        $response    = array();
        foreach ( $module_defs as $module ) {
            if ( $module['id'] === $module_id ) {
                $response = $module;
                break;
            }
        }

        return $response;
    }
}
