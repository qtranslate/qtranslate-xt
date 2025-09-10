/**
 * Main loader for core module.
 */
'use strict';

import {hooks} from './hooks';

const qTranslateConfig = window.qTranslateConfig;

const pageConfigKeys = () => {
    const pageConfig = qTranslateConfig['page_config'] || {};
    const configKeys = pageConfig['keys'] || [];
    return configKeys;
};

const $ = jQuery;

// With jQuery3 ready handlers fire asynchronously and may be fired after load.
// See: https://github.com/jquery/jquery/issues/3194
$(window).on('load', function () {
    // The hooks may already be initialized (see 'wp_tiny_mce_init' for the Classic Editor)
    // This `init` below is needed by pages not having such type of editor, for example the WP tags page.
    hooks.init();

    // Setup hooks for additional TinyMCE editors initialized dynamically, for example WISYWYG ACF.
    hooks.loadAdditionalTinyMceHooks();

    const configKeys = pageConfigKeys();
    configKeys.forEach(key => {
        $(document).trigger('qtxLoadAdmin:' + key, [hooks]);
    });
});
