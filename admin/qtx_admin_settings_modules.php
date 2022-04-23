<?php

/**
 * Module admin settings, for display in the settings panels.
 */
class QTX_Admin_Settings_Module {
    public $id;
    public $name;
    public $state;
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
    public function __construct( $module, $options_modules ) {
        $this->module             = $module;
        $this->id                 = $module->id;
        $this->name               = $module->name;
        $this->state              = isset( $options_modules[ $module->id ] ) ? $options_modules[ $module->id ] : QTX_MODULE_STATE_UNDEFINED;
        $this->plugin_state_label = empty( $module->plugins ) ? _x( 'None', 'Module admin', 'qtranslate' ) : ( QTX_Admin_Modules::is_module_plugin_active( $module ) ? _x( 'Active', 'Module admin', 'qtranslate' ) : _x( 'Inactive', 'Module admin', 'qtranslate' ) );
        switch ( $this->state ) {
            case QTX_MODULE_STATE_ACTIVE:
                $this->module_state_label = _x( 'Active', 'Module admin', 'qtranslate' );
                $this->icon               = 'dashicons-yes';
                $this->color              = 'green';
                break;
            case QTX_MODULE_STATE_INACTIVE:
                $this->module_state_label = _x( 'Inactive', 'Module admin', 'qtranslate' );
                $this->icon               = 'dashicons-no-alt';
                $this->color              = '';
                break;
            case QTX_MODULE_STATE_BLOCKED:
                $this->module_state_label = _x( 'Blocked', 'Module admin', 'qtranslate' );
                $this->icon               = 'dashicons-warning';
                $this->color              = 'orange';
                break;
            case QTX_MODULE_STATE_UNDEFINED:
            default:
                $this->module_state_label = __( 'Inactive', 'qtranslate' );
                $this->icon               = 'dashicons-editor-help';
                $this->color              = '';
                break;
        }
    }

    /**
     * Retrieve checked settings.
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

    /**
     * Retrieve settings for all modules (for display).
     * The status is retrieved from the modules option.
     *
     * @return QTX_Admin_Settings_Module[]
     */
    public static function get_modules_settings() {
        $modules         = QTX_Module_Loader::get_modules_defs();
        $options_modules = get_option( 'qtranslate_modules_state', array() );
        $settings        = array();
        foreach ( $modules as $module ) {
            $settings[] = new QTX_Admin_Settings_Module( $module, $options_modules );
        }

        return $settings;
    }
}
