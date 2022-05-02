<?php

require_once( QTRANSLATE_DIR . '/modules/qtx_admin_module_manager.php' );
require_once( QTRANSLATE_DIR . '/modules/qtx_admin_module.php' );
require_once( QTRANSLATE_DIR . '/modules/qtx_module_state.php' );

/**
 * Module admin settings, for display in the settings panels.
 */
class QTX_Admin_Module_Settings {
    public $id;
    public $name;
    public $plugin_state_label;
    public $module_state_label;
    public $icon;
    public $color;

    /**
     * @var int Internal state.
     */
    protected $state;

    /**
     * @var QTX_Admin_Module Underlying module definition.
     */
    protected $module;

    /**
     * Constructor.
     *
     * @param QTX_Admin_Module $module
     * @param integer $state
     */
    public function __construct( $module, $state ) {
        $this->id     = $module->id;
        $this->name   = $module->name;
        $this->module = $module;
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
                $this->color              = 'orange';
                break;
        }
        $this->plugin_state_label = empty( $module->plugins ) ? _x( 'None', 'Module settings', 'qtranslate' ) : ( QTX_Admin_Module_Manager::is_module_plugin_active( $module ) ? _x( 'Active', 'Module settings', 'qtranslate' ) : _x( 'Inactive', 'Module settings', 'qtranslate' ) );
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
        return ( QTX_Admin_Module_Manager::can_module_be_activated( $this->module ) != QTX_MODULE_STATE_ACTIVE );
    }

    /**
     * Check if the module state is active.
     *
     * @return bool
     */
    public function is_active() {
        return $this->state == QTX_MODULE_STATE_ACTIVE;
    }

    /**
     * Check if the module has settings.
     *
     * @return bool
     */
    public function has_settings() {
        return $this->module->has_settings;
    }

    /**
     * Retrieve settings for all modules (for display).
     * The status is retrieved from the modules option.
     *
     * @return QTX_Admin_Module_Settings[]
     */
    public static function get_settings_modules() {
        $states   = get_option( QTX_OPTIONS_MODULES_STATE, array() );
        $settings = array();
        foreach ( QTX_Admin_Module::get_modules() as $module ) {
            $state      = isset( $states[ $module->id ] ) ? $states[ $module->id ] : QTX_MODULE_STATE_UNDEFINED;
            $settings[] = new QTX_Admin_Module_Settings( $module, $state );
        }

        return $settings;
    }
}
