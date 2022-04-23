<?php

/**
 * Definition of a module.
 *
 * This provides only the basic structure, not the module logic or states.
 */
class QTX_Module {
    /**
     * Internal id.
     *
     * @var string
     */
    public $id;

    /**
     * Name for display.
     *
     * @var string
     */
    public $name;

    /**
     * Array of required plugin(s) defined in the WP format (directory/file.php).
     * If this list is not empty, the module requires at least one of the plugin(s) to be activated.
     *
     * @var string[]
     */
    public $plugins;

    /**
     * Incompatible plugin in the WP format, only one or zero supported.
     * If not empty, the module cannot be activated is this plugin is active.
     *
     * @var string|null
     */
    public $incompatible;

    /**
     * A module can have specific admin settings.
     *
     * @var bool
     */
    protected $has_settings;

    /**
     * Constructor from fields array.
     *
     * @param array[] $fields
     *
     * @see QTX_Module_Setup
     */
    function __construct( $fields ) {
        $this->id           = $fields['id'];
        $this->name         = $fields['name'];
        $this->plugins      = isset( $fields['plugins'] ) ? $fields['plugins'] : array();
        $this->incompatible = isset( $fields['incompatible'] ) ? $fields['incompatible'] : null;
        $this->has_settings = isset( $fields['has_settings'] ) ? $fields['has_settings'] : false;
    }

    /**
     * Check if the module has specific settings.
     *
     * @return bool
     */
    function has_settings() {
        return $this->has_settings;
    }

    /**
     * Retrieve the default settings for the "admin enabled state" (checkbox).
     *
     * @return bool
     */
    function is_default_enabled() {
        return ! empty( $this->plugins );
    }
}
