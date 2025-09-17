/**
 * Main loader for core module.
 */
'use strict';
import {init, loadAdditionalTinyMceHooks} from './hooks';

const $ = jQuery;
const qTranslateConfig = window.qTranslateConfig;

const pageConfigKeys = function () {
    return qTranslateConfig['page_config']?.['keys'] ?? [];
};

export const isPageActive = function (page) {
    return (pageConfigKeys().indexOf(page) >= 0);
};

// With jQuery3 ready handlers fire asynchronously and may be fired after load.
// See: https://github.com/jquery/jquery/issues/3194
$(window).on('load', function () {
    // The hooks may already be initialized (see 'wp_tiny_mce_init' for the Classic Editor)
    // This `init` below is needed by pages not having such type of editor, for example the WP tags page.
    init();
    // Setup hooks for additional TinyMCE editors initialized dynamically, for example WISYWYG ACF.
    loadAdditionalTinyMceHooks();

    wp.hooks.doAction('qtranx.load');

    const configKeys = pageConfigKeys();
    configKeys.forEach(key => {
        $(document).trigger('qtxLoadAdmin:' + key, [qTranx.hooks, "Deprecated event 'qtxLoadAdmin', use wp.hooks.addAction('qtranx.load', namespace, callback) instead."]);
    });
});
