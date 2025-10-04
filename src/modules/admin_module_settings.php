<?php

require_once QTRANSLATE_DIR . '/src/modules/admin_module_manager.php';
require_once QTRANSLATE_DIR . '/src/modules/admin_module.php';
require_once QTRANSLATE_DIR . '/src/modules/module_state.php';

/**
 * Module admin settings, for display in the settings panels.
 */
class QTX_Admin_Module_Settings {
    public string $id;
    public string $name;

    /**
     * @var int Internal state.
     */
    protected int $state;

    /**
     * @var QTX_Admin_Module Underlying module definition.
     */
    protected QTX_Admin_Module $module;

    /**
     * Constructor.
     *
     * @param QTX_Admin_Module $module
     * @param integer $state
     */
    public function __construct( QTX_Admin_Module $module, int $state ) {
        $this->id     = $module->id;
        $this->name   = $module->name;
        $this->module = $module;
        $this->state  = $state;
    }

    /**
     * Retrieve display information related to the state.
     *
     * @return array
     */
    public function state_info(): array {
        $info = array();
        switch ( $this->state ) {
            case QTX_MODULE_STATE_ACTIVE:
                $info['label'] = _x( 'Active', 'Module settings', 'qtranslate' );
                $info['icon']  = 'dashicons-yes';
                $info['color'] = 'green';
                break;
            case QTX_MODULE_STATE_INACTIVE:
                $info['label'] = _x( 'Inactive', 'Module settings', 'qtranslate' );
                $info['icon']  = 'dashicons-no-alt';
                $info['color'] = '';
                break;
            case QTX_MODULE_STATE_BLOCKED:
                $info['label'] = _x( 'Blocked', 'Module settings', 'qtranslate' );
                $info['icon']  = 'dashicons-warning';
                $info['color'] = 'orange';
                break;
            case QTX_MODULE_STATE_UNDEFINED:
            default:
                $info['label'] = _x( 'Inactive', 'Module settings', 'qtranslate' );
                $info['icon']  = 'dashicons-editor-help';
                $info['color'] = 'orange';
                break;
        }

        return $info;
    }

    /**
     * Retrieve admin enabled checked settings.
     *
     * @return bool
     */
    public function is_checked(): bool {
        global $q_config;

        return ( isset( $q_config['admin_enabled_modules'][ $this->module->id ] ) && $q_config['admin_enabled_modules'][ $this->module->id ] ) || ( $this->state == QTX_MODULE_STATE_ACTIVE );
    }

    /**
     * Retrieve disabled settings.
     *
     * @return bool
     */
    public function is_disabled(): bool {
        return ( QTX_Admin_Module_Manager::can_module_be_activated( $this->module ) != QTX_MODULE_STATE_ACTIVE );
    }

    /**
     * Check if the module state is active.
     *
     * @return bool
     */
    public function is_active(): bool {
        return $this->state == QTX_MODULE_STATE_ACTIVE;
    }

    /**
     * Check if the module has settings.
     *
     * @return bool
     */
    public function has_settings(): bool {
        return $this->module->has_settings;
    }

    /**
     * Retrieve the label of the activation state for the required plugin, if set, "None" otherwise.
     *
     * @return string
     */
    public function plugin_state_label(): string {
        return empty( $this->module->plugins ) ? _x( 'None', 'Module settings', 'qtranslate' ) : ( QTX_Admin_Module_Manager::is_module_plugin_active( $this->module ) ? _x( 'Active', 'Module settings', 'qtranslate' ) : _x( 'Inactive', 'Module settings', 'qtranslate' ) );
    }

    /**
     * Retrieve settings for all modules (for display).
     * The status is retrieved from the modules option.
     *
     * @return QTX_Admin_Module_Settings[]
     */
    public static function get_settings_modules(): array {
        $states   = get_option( QTX_OPTIONS_MODULES_STATE, array() );
        $settings = array();
        foreach ( QTX_Admin_Module::get_modules() as $module ) {
            $state      = $states[ $module->id ] ?? QTX_MODULE_STATE_UNDEFINED;
            $settings[] = new QTX_Admin_Module_Settings( $module, $state );
        }

        return $settings;
    }
}
