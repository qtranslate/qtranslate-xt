/**
 * Main loader for hooks module.
 */
'use strict';
import {config} from '../core/config'
import {init, loadAdditionalTinyMceHooks} from './handlers';

const $ = jQuery;

// With jQuery3 ready handlers fire asynchronously and may be fired after load.
// See: https://github.com/jquery/jquery/issues/3194
$(window).on('load', function () {
    // The hooks may already be initialized (see 'wp_tiny_mce_init' for the Classic Editor)
    // This `init` below is needed by pages not having such type of editor, for example the WP tags page.
    init();
    // Setup hooks for additional TinyMCE editors initialized dynamically, for example WISYWYG ACF.
    loadAdditionalTinyMceHooks();

    wp.hooks.doAction('qtranx.load');

    config.i18n.setup.keys.forEach(key => {
        $(document).trigger('qtxLoadAdmin:' + key, [qTranx.hooks, "Deprecated event 'qtxLoadAdmin', use wp.hooks.addAction('qtranx.load', namespace, callback) instead."]);
    });
});
