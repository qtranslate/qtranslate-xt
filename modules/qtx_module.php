<?php

/**
 * Definition of a module.
 *
 * This provides only the basic structure, not the module logic or states.
 */
class QTX_Module {
    public $id;
    public $name;
    public $plugin;
    public $incompatible;
    public $has_settings;

    /**
     * Constructor from field array.
     *
     * @see QTX_Module_Setup
     * @param array[] $fields
     */
    function __construct( $fields ) {
        foreach ( $fields as $key => $value ) {
            $this->{$key} = $value;
        }
    }

    /**
     * Check if the module specific settings.
     *
     * @return bool
     */
    function has_settings() {
        return isset( $this->has_settings ) ? $this->has_settings : false;
    }

    /**
     * Retrieve the default enabled state for the initial admin settings.
     *
     * @return bool
     */
    function is_default_enabled() {
        return ( $this->plugin !== true );
    }
}
