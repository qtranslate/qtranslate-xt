<?php

require_once( QTRANSLATE_DIR . '/admin/qtx_admin_modules.php' );
require_once( QTRANSLATE_DIR . '/modules/qtx_module_setup.php' );

/**
 * Module admin settings, for display in the settings panels.
 */
class QTX_Admin_Settings_Module {
    public $id;
    public $name;
    protected $state;
    public $plugin_state_label;
    public $module_state_label;
    public $icon;
    public $color;
    protected $module;

    /**
     * Constructor.
     *
     * @param QTX_Module $module
     */
    public function __construct( $module, $state ) {
        $this->module = $module;
        $this->id     = $module->id;
        $this->name   = $module->name;
        $this->state  = $state;
        switch ( $this->state ) {
            case QTX_MODULE_STATE_ACTIVE:
                $this->module_state_label = _x( 'Active', 'Module settings', 'qtranslate' );
                $this->icon               = 'dashicons-yes';
                $this->color              = 'green';
                break;
            case QTX_MODULE_STATE_INACTIVE:
                $this->module_state_label = _x( 'Inactive', 'Module settings', 'qtranslate' );
                $this->icon               = 'dashicons-no-alt';
                $this->color              = '';
                break;
            case QTX_MODULE_STATE_BLOCKED:
                $this->module_state_label = _x( 'Blocked', 'Module settings', 'qtranslate' );
                $this->icon               = 'dashicons-warning';
                $this->color              = 'orange';
                break;
            case QTX_MODULE_STATE_UNDEFINED:
            default:
                $this->module_state_label = _x( 'Inactive', 'Module settings', 'qtranslate' );
                $this->icon               = 'dashicons-editor-help';
                $this->color              = '';
                break;
        }
        $this->plugin_state_label = empty( $module->plugins ) ? _x( 'None', 'Module settings', 'qtranslate' ) : ( QTX_Admin_Modules::is_module_plugin_active( $module ) ? _x( 'Active', 'Module settings', 'qtranslate' ) : _x( 'Inactive', 'Module settings', 'qtranslate' ) );
    }

    /**
     * Retrieve admin enabled checked settings.
     *
     * @return bool
     */
    public function is_checked() {
        global $q_config;

        return ( isset( $q_config['admin_enabled_modules'][ $this->module->id ] ) && $q_config['admin_enabled_modules'][ $this->module->id ] ) || ( $this->state == QTX_MODULE_STATE_ACTIVE );
    }

    /**
     * Retrieve disabled settings.
     *
     * @return bool
     */
    public function is_disabled() {
        return ( QTX_Admin_Modules::can_module_be_activated( $this->module ) != QTX_MODULE_STATE_ACTIVE );
    }

    public function is_active() {
        return $this->state == QTX_MODULE_STATE_ACTIVE;
    }

    /**
     * Retrieve disabled settings.
     *
     * @return bool
     */
    public function has_settings() {
        return $this->module->has_settings();
    }

    /**
     * Retrieve settings for all modules (for display).
     * The status is retrieved from the modules option.
     *
     * @return QTX_Admin_Settings_Module[]
     */
    public static function get_settings_modules() {
        $states   = get_option( 'qtranslate_modules_state', array() );
        $settings = array();
        foreach ( QTX_Module_Setup::get_modules() as $module ) {
            $state      = isset( $states[ $module->id ] ) ? $states[ $module->id ] : QTX_MODULE_STATE_UNDEFINED;
            $settings[] = new QTX_Admin_Settings_Module( $module, $state );
        }

        return $settings;
    }
}
