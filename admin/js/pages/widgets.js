/* executed for
 /wp-admin/widgets.php
*/
'use strict';

const $ = jQuery;

$(document).on('qtxLoadAdmin:widgets', (event, qtx) => {
    if (!window.wpWidgets)
        return;

    console.log('QTX widgets');
    // Editors are created dynamically for each widget
    const editors = {};

    $(document).on('wp-before-tinymce-init', (event, editor) => {
        console.log('wp-before-tinymce-init', editor);
        const widget = $(editor.selector).parents('.widget');

        // Normally the title is not dependent on TinyMCE
        // But the elements are created dynamically by WP when the area is shown
        widget.find(".text-widget-fields input[id$='_title']").each(function (i, e) {
            // qtx.addContentHookById(e.id, '[', 'title');
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

    jQuery(document).on('tinymce-editor-init', (event, editor) => {
        // qtx.loadAdditionalTinyMceHooks();
        // TODO get widget id
        editors['widget-text-19-text'] = editor.id;
        const hook = qtx.attachEditorHook(editor, 'widget-text-19-text');
        hook.wpautop = true;
        console.log('tinymce-editor-init', editor.id, hook);
        // const editor = tinyMCE.get(editor_id);
        // $(editor.getContainer()).addClass('qtranxs-translatable');
        // $(editor.getElement()).addClass('qtranxs-translatable');
    });

    // TODO hook elements of basic widgets without TinyMCE such as CustomHTML
    const onWidgetAdd = function (evt, widget) {
        const widgetBase = widget.find('.id_base').val();
        console.log('onWidgetAdd', widget, widgetBase);
        switch(widgetBase) {
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

            case 'text':
                break;
        }
    }

    const onWidgetUpdate = function (evt, widget) {
        // const widget = $(widget);
        const widgetBase = widget.find('.id_base').val();
        console.log('onWidgetUpdate', widget, widgetBase, editors);
        switch(widgetBase) {
            case 'custom_html':
                // TODO
                break;
            case 'text':
                const widgetId = widget.find('.widget-id').val();
                // widget.find('span.in-widget-title').each(function (i, e) {
                //     qtx.refreshContentHook(e);
                // });
                const editorId = editors['widget-' + widgetId + '-text'];
                const editor = window.tinyMCE.get(editorId);
                widget.find(".widget-content input[id^='widget-text-'][id$='-title']").each(function (i, e) {
                    qtx.refreshContentHook(e);
                });
                widget.find(".widget-content textarea[id^='widget-text-'][id$='-text']").each(function (i, e) {
                    const hook = qtx.refreshContentHook(e);
                    console.log('calling setEditorHooks', editorId, editor, widgetId, editors);
                    qtx.attachEditorHook(editor, 'widget-' + widgetId + '-text');
                    // todo hack
                    $('#' + editorId).val('');
                });
                console.log('onWidgetUpdate id', widgetId, Date());
                if (widgetId in wp.textWidgets.widgetControls) { // check if open?
                    // const syncInput = widget.find( '.sync-input.text' );
                    wp.textWidgets.widgetControls[widgetId].updateFields();
                    if (!editor.hidden) {
                        editor.save({format: 'html'});
                    }
                }
                wpWidgets.appendTitle(widget);
                break;
        }
    };

    $(document).on('widget-added', onWidgetAdd);
    $(document).on('widget-updated', onWidgetUpdate);

    // const onLanguageSwitchBefore = function () {
    //     console.log('switch before', editors);
    //     for (const editor_id of editors) {
    //         const editor = tinyMCE.get(editor_id);
    //         if (!editor.hidden) {
    //             editor.save({format: 'html'});
    //         }
    //     }
    // };

    const onLanguageSwitchAfter = function () {
        $('#widgets-right .widget').each(function () {
            const widget = $(this);
            const widgetBase = widget.find('.id_base').val();
            if (widgetBase === 'text') {
                const widgetId = widget.find('.widget-id').val();
                console.log('onLanguageSwitchAfter textwidget update', widget, widgetId, Date());
                const editorId = editors['widget-' + widgetId + '-text'];
                if (widgetId in wp.textWidgets.widgetControls) { // check if open?
                    // const syncInput = widget.find( '.sync-input.text' );
                    wp.textWidgets.widgetControls[widgetId].updateFields();
                    const editor = tinyMCE.get(editorId);
                    if (!editor.hidden) {
                        editor.save({format: 'html'});
                    }
                }
            }
            wpWidgets.appendTitle(this);
        });
    };

    // qtx.addLanguageSwitchBeforeListener(onLanguageSwitchBefore);
    qtx.addLanguageSwitchAfterListener(onLanguageSwitchAfter);
});
