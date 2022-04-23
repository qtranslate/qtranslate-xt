<?php

/**
 * Definition of a module.
 *
 * This provides only the basic structure, not the module logic or states.
 */
class QTX_Module {
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @type array
     */
    public $plugins;

    /**
     * @var string|null
     */
    public $incompatible;

    /**
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
     * Check if the module specific settings.
     *
     * @return bool
     */
    function has_settings() {
        return $this->has_settings;
    }

    /**
     * Retrieve the default enabled state for the initial admin settings.
     *
     * @return bool
     */
    function is_default_enabled() {
        return ! empty( $this->plugins );
    }
}
