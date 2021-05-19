/* executed for
 /wp-admin/widgets.php
*/
'use strict';

const $ = jQuery;

$(document).on('qtxLoadAdmin:widgets', (event, qtx) => {
    if (!window.wpWidgets)
        return;

    console.log('QTX widgets');

    const getWidgetId = function (field) {
        const widgetInside = $(field).parents('.widget-inside');
        const widgetId = widgetInside.find('.widget-id').val();
        return widgetId;
    };

    jQuery(document).on('tinymce-editor-init', (event, editor) => {
        const widget = $(editor.settings.selector).parents('.widget');
        // The title is not dependent on TinyMCE
        // But the widget input fields are created dynamically by WP when the area is shown
        widget.find(".text-widget-fields input[id$='_title']").each(function (i, e) {
            const fieldId = 'widget-' + getWidgetId(e) + '-title';
            const hook = qtx.hasContentHook(fieldId);
            // TODO improve attach hack
            hook.contentField = e;
            e.classList.add('qtranxs-translatable');
        });
        // qtx.loadAdditionalTinyMceHooks();
        const textArea = document.getElementById(editor.id);
        const fieldId = 'widget-' + getWidgetId(textArea) + '-text';
        qtx.attachEditorHook(editor, fieldId);
    });

    const onWidgetUpdate = function (evt, widget) {
        const widgetBase = widget.find('.id_base').val();
        switch(widgetBase) {
            case 'custom_html':
                // TODO
                break;
            case 'text':
                const widgetId = widget.find('.widget-id').val();
                const fieldTitle = widget.find(".text-widget-fields input[id$='_title']")[0];
                widget.find(".widget-content input[id^='widget-text-'][id$='-title']").each(function (i, e) {
                    const fieldSyncTitle = document.getElementById('widget-' + widgetId + '-title');
                    const hook = qtx.refreshContentHook(e);
                    // TODO improve attach hack
                    hook.contentField = fieldTitle;
                    fieldTitle.classList.add('qtranxs-translatable');
                });
                const fieldText = widget.find(".text-widget-fields textarea[id$='_text']");
                const editor = window.tinyMCE.get(fieldText[0].id);
                widget.find(".widget-content textarea[id^='widget-text-'][id$='-text']").each(function (i, e) {
                    qtx.refreshContentHook(e);
                    qtx.attachEditorHook(editor, e.id);

                    // Here the text field has not been synced after translation yet
                    // Because the text field has not been updated by wp.widgets when in Visual Mode,
                    // it still has the translated content before saving the widget.
                    // To allow updateField to change the MCE content, change the value of the text field.
                    const syncInput = widget.find('.sync-input.text');
                    fieldText.val(syncInput.val() + 'x');
                });
                if (widgetId in wp.textWidgets.widgetControls) {
                    wp.textWidgets.widgetControls[widgetId].updateFields();
                }
                break;
            default:
                widget.find(".widget-content input[id^='widget-'][id$='-title']").each(function (i, e) {
                    qtx.refreshContentHook(e);
                });
                break;
        }

        wpWidgets.appendTitle(widget);
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
