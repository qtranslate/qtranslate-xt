/**
 * Shared core functionalities and setup
 */
'use strict';

import './qtranslatex';

const qTranslateConfig = window.qTranslateConfig;

const get_page_config_keys = () => {
    const pageConfig = qTranslateConfig['page_config'] || {};
    const configKeys = pageConfig['keys'] || [];
    return configKeys;
};

const $ = jQuery;

// With jQuery3 ready handlers fire asynchronously and may be fired after load.
// See: https://github.com/jquery/jquery/issues/3194
$(window).on('load', function () {
    // qtx may already be initialized (see 'wp_tiny_mce_init' for the Classic Editor)
    const qtx = qTranslateConfig.js.get_qtx();
    // Setup hooks for additional TinyMCE editors initialized dynamically
    qtx.loadAdditionalTinyMceHooks();

    const configKeys = get_page_config_keys();
    console.log('Loading page configs', configKeys);
    configKeys.forEach(key => {
        console.log('trigger qtranslate_load_admin', key);
        $('body').trigger('qtranslate_load_admin', [key, qtx]);
    });
});

export * from './dom';
export * from './qblocks';
