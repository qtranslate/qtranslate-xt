<?php
/**
 * Built-in module for Google Site Kit
 */

add_filter( 'googlesitekit_canonical_home_url', function ( string $url ): string {
    // bypass qtranxf_home_url and provide a fixed home URL to avoid disconnections
    return get_option( 'home' );
} );
