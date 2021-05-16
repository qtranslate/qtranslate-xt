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
        console.log('wp-before-tinymce-init', editor);
        const widget = $(editor.selector).parents('.widget');

        // Normally the title is not dependent on TinyMCE
        // But the elements are created dynamically by WP when the area is shown
        widget.find(".text-widget-fields input[id$='_title']").each(function (i, e) {
            // qtx.addContentHookById(e.id, '[', 'title');
            // $(e).addClass('qtranxs-translatable');
            const fieldId = 'widget-' + getWidgetId(e) + '-title';
            const hook = qtx.hasContentHook(fieldId);
            hook.contentField = e;
            $(e).addClass('qtranxs-translatable');
        });

        // widget.find('span.in-widget-title').each(function (i, e) {
        //     qtx.addDisplayHook(e);
        // });
        // widget.find(".text-widget-fields input[id$='_title']").each(function (i, e) {
        //     qtx.addContentHookById(e.id, '[', 'title');
        // });
        // widget.find(".text-widget-fields textarea[id$='_text']").each(function (i, e) {
        //     const ret = qtx.addContentHook(e, '[', 'text');
        //     console.log('addContentHook', ret)
        // });
    });

    const getWidgetId = function (field) {
        const widgetInside = $(field).parents('.widget-inside');
        const widgetId = widgetInside.find('.widget-id').val();
        return widgetId;
    };

    jQuery(document).on('tinymce-editor-init', (event, editor) => {
        // qtx.loadAdditionalTinyMceHooks();
        const textArea = document.getElementById(editor.id);
        const fieldId = 'widget-' + getWidgetId(textArea) + '-text';
        const hook = qtx.attachEditorHook(editor, fieldId);
        hook.contentField = document.getElementById(editor.id);
        hook.wpautop = true;
        console.log('tinymce-editor-init', editor.id, hook);
        // const editor = tinyMCE.get(editor_id);
        // $(editor.getContainer()).addClass('qtranxs-translatable');
        // $(editor.getElement()).addClass('qtranxs-translatable');
    });

    // // TODO hook elements of basic widgets without TinyMCE such as CustomHTML
    // const onWidgetAdd = function (evt, widget) {
    //     const widgetBase = widget.find('.id_base').val();
    //     console.log('onWidgetAdd', widget, widgetBase);
    //     switch(widgetBase) {
    //         case 'custom_html':
    //             widget.find(".custom-html-widget-fields input[id$='_title']").each(function (i, e) {
    //                 console.log('found title', e)
    //                 const ret = qtx.addContentHookById(e.id, '[', 'title');
    //                 console.log('addContentHook', ret)
    //                 // qtx.refreshContentHook(e);
    //             });
    //             widget.find(".custom-html-widget-fields textarea[id$='_content']").each(function (i, e) {
    //                 console.log('found content', e)
    //                 const ret = qtx.addContentHookById(e.id, '[', 'content');
    //                 console.log('addContentHook', ret)
    //                 // qtx.refreshContentHook(e);
    //             });
    //             widget.find(".custom-html-widget-fields .CodeMirror-wrap").addClass('qtranxs-translatable');
    //             break;
    //
    //         case 'text':
    //             break;
    //     }
    // }

    const onWidgetUpdate = function (evt, widget) {
        const widgetBase = widget.find('.id_base').val();
        switch(widgetBase) {
            case 'custom_html':
                // TODO
                break;
            case 'text':
                const widgetId = widget.find('.widget-id').val();
                // widget.find('span.in-widget-title').each(function (i, e) {
                //     qtx.refreshContentHook(e);
                // });
                //const editorId = editors['widget-' + widgetId + '-text'];
                //const editor = window.tinyMCE.get(editorId);
                const fieldTitle = widget.find(".text-widget-fields input[id$='_title']")[0];
                console.log('fieldTitle', fieldTitle);
                widget.find(".widget-content input[id^='widget-text-'][id$='-title']").each(function (i, e) {
                    const fieldSyncTitle = document.getElementById('widget-' + widgetId + '-title');
                    const hook = qtx.refreshContentHook(e);
                    hook.contentField = fieldTitle;
                    // Here the title field has not been synced after translation yet
                    //fieldTitle.val(fieldSyncTitle.val());
                    //fieldTitle.trigger('change');
                });
                const fieldText = widget.find(".text-widget-fields textarea[id$='_text']");
                const editor = window.tinyMCE.get(fieldText[0].id);
                widget.find(".widget-content textarea[id^='widget-text-'][id$='-text']").each(function (i, e) {
                    // const fieldSyncText = document.getElementById('widget-' + widgetId + '-text');
                    const hook = qtx.refreshContentHook(e);
                    console.log('calling setEditorHooks', editor.id);
                    const editorHook = qtx.attachEditorHook(editor, 'widget-' + widgetId + '-text');
                    editorHook.wpautop = true;
                    //editorHook.mce.setContent(wp.editor.autop( syncInput.val() ));
                    //editorHook.mce.save();

                    // Here the text field has not been synced after translation yet
                    // Because the text field has not been updated by wp.widgets when in Visual Mode,
                    // it still has the translated content before saving the widget.
                    // To allow updateField to change the MCE content, change the value of the text field.
                    const syncInput = widget.find( '.sync-input.text' );
                    fieldText.val(syncInput.val() + 'x');
                });
                console.log('onWidgetUpdate id', widgetId, Date());
                if (widgetId in wp.textWidgets.widgetControls) { // check if open?
                    wp.textWidgets.widgetControls[widgetId].updateFields();
                }
                wpWidgets.appendTitle(widget);
                break;
        }
    };

    $(document).on('widget-added', onWidgetUpdate);
    $(document).on('widget-updated', onWidgetUpdate);

    const onLanguageSwitchAfter = function () {
        $('#widgets-right .widget').each(function () {
            wpWidgets.appendTitle(this);
        });
    };

    // qtx.addLanguageSwitchBeforeListener(onLanguageSwitchBefore);
    qtx.addLanguageSwitchAfterListener(onLanguageSwitchAfter);
});
