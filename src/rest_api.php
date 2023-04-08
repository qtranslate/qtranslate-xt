<?php

/**
 * Add specific rewrites to handle API REST for the default language, when hidden in QTX_URL_PATH mode.
 * Most of the requests don't need this, as they are handled through custom home_url with the language.
 * Note: to make it work you have to flush your rewrite rules by saving the permalink structures from the admin page!
 *
 * Example with 'en' as default language and hidden option enabled:
 *   /wp-json/wp/...    -> home_url = '/' (default, hidden)
 *   /fr/wp-json/wp/... -> home_url = '/fr'
 *   /en/wp-json/wp/... -> home_url = '/' (default, hidden but requested) -> fails with standard rewrites (404)
 * This function allows to handle specifically this last case.
 *
 * @see rest_api_register_rewrites in wp_includes/rest-api.php
 */
function qtranxf_rest_api_register_rewrites(): void {
    global $q_config;
    if ( ! $q_config['hide_default_language'] || $q_config['url_mode'] !== QTX_URL_PATH ) {
        return;
    }

    global $wp_rewrite;
    $default_lang = $q_config['default_language'];
    add_rewrite_rule( '^' . $default_lang . '/' . rest_get_url_prefix() . '/?$', 'index.php?rest_route=/', 'top' );
    add_rewrite_rule( '^' . $default_lang . '/' . rest_get_url_prefix() . '/(.*)?', 'index.php?rest_route=/$matches[1]', 'top' );
    add_rewrite_rule( '^' . $default_lang . '/' . $wp_rewrite->index . '/' . rest_get_url_prefix() . '/?$', 'index.php?rest_route=/', 'top' );
    add_rewrite_rule( '^' . $default_lang . '/' . $wp_rewrite->index . '/' . rest_get_url_prefix() . '/(.*)?', 'index.php?rest_route=/$matches[1]', 'top' );
}
