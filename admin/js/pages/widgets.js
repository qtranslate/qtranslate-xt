/* executed for
 /wp-admin/widgets.php
*/
'use strict';
const $ = jQuery;

$(document).on('qtxLoadAdmin:widgets', (event, qtx) => {
    if (!window.wpWidgets)
        return;

    console.log('QTX widgets');

    $(document).on('wp-before-tinymce-init', (event, editor) => {
        console.log('wp-before-tinymce-init');

        // Normally the title is not dependent on TinyMCE
        // But the elements are created dynamically by WP when the area is shown

        // TODO add hooks only for that editor, not all widgets
        const widget = jQuery('#widgets-right');
        widget.find('span.in-widget-title').each(function (i, e) {
            qtx.addDisplayHook(e);
        });
        widget.find(".text-widget-fields input[id$='_title']").each(function (i, e) {
            console.log('found title', e)
            const ret = qtx.addContentHookById(e.id, '[', 'title');
            console.log('addContentHook', ret)
            // qtx.refreshContentHook(e);
        });

        widget.find(".text-widget-fields textarea[id$='_text']").each(function (i, e) {
            console.log('found text', e)
            const ret = qtx.addContentHook(e, '[', 'text');
            console.log('addContentHook', ret)
            // qtx.refreshContentHook(e);
        });

    });

    jQuery(document).on('tinymce-editor-init', () => {
        qtx.loadAdditionalTinyMceHooks();
    });

    // TODO hook elements of basic widgets without TinyMCE such as CustomHTM

    const onWidgetUpdate = function (evt, widget) {
        console.log('onWidgetUpdate', widget);
        widget.find('span.in-widget-title').each(function (i, e) {
            qtx.addDisplayHook(e);
        });
        widget.find("input.qtranxs-translatable").each(function (i, e) {
            qtx.refreshContentHook(e);
        });
        widget.find("textarea.qtranxs-translatable").each(function (i, e) {
            qtx.refreshContentHook(e);
        });
    };

    $(document).on('widget-added', onWidgetUpdate);
    $(document).on('widget-updated', onWidgetUpdate);

    const onLanguageSwitchAfter = function () {
        console.log('onLanguageSwitchAfter');
        $('#widgets-right .widget').each(function () {
            wpWidgets.appendTitle(this);
        });
    };

    qtx.addLanguageSwitchAfterListener(onLanguageSwitchAfter);
});
