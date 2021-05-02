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

    // TODO hook elements of basic widgets without TinyMCE such as CustomHTML
    const onWidgetAdd = function (evt, widget) {
        // const $widget = $(widget);
        const widget_base = widget.find('.id_base').val();
        console.log('onWidgetAdd', widget, widget_base);
        switch(widget_base) {
            case 'custom_html':
                widget.find(".custom-html-widget-fields input[id$='_title']").each(function (i, e) {
                    console.log('found title', e)
                    const ret = qtx.addContentHookById(e.id, '[', 'title');
                    console.log('addContentHook', ret)
                    // qtx.refreshContentHook(e);
                });
                widget.find(".custom-html-widget-fields textarea[id$='_content']").each(function (i, e) {
                    console.log('found content', e)
                    const ret = qtx.addContentHookById(e.id, '[', 'content');
                    console.log('addContentHook', ret)
                    // qtx.refreshContentHook(e);
                });
                widget.find(".custom-html-widget-fields .CodeMirror-wrap").addClass('qtranxs-translatable');
                break;
            case 'text_widget':
                // Maybe initialize stuff here?
                break;
        }
    }

    const onWidgetUpdate = function (evt, widget) {
        // const widget = $(widget);
        const widget_base = widget.find('.id_base').val();
        console.log('onWidgetUpdate', widget, widget_base);
        switch(widget_base) {
            case 'custom_html':
                // TODO
                break;
            case 'text_widget':
                widget.find('span.in-widget-title').each(function (i, e) {
                    qtx.refreshContentHook(e);
                });
                widget.find("input.qtranxs-translatable").each(function (i, e) {
                    qtx.refreshContentHook(e);
                });
                widget.find("textarea.qtranxs-translatable").each(function (i, e) {
                    qtx.refreshContentHook(e);
                });
                break;
        }
    };

    $(document).on('widget-added', onWidgetAdd);
    $(document).on('widget-updated', onWidgetUpdate);

    const onLanguageSwitchAfter = function () {
        $('#widgets-right .widget').each(function () {
            console.log('onLanguageSwitchAfter', this);
            wpWidgets.appendTitle(this);
        });
    };

    qtx.addLanguageSwitchAfterListener(onLanguageSwitchAfter);
});
