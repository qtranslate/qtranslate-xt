/* executed for
 /wp-admin/widgets.php
*/
'use strict';
import * as hooks from '../core/hooks';

const $ = jQuery;

export default function () {
    if (!window.wpWidgets)
        return;

    jQuery(document).on('tinymce-editor-init', (event, editor) => {
        const widget = $(editor.settings.selector).parents('.widget');
        const widgetId = widget.find('.widget-id').val();
        // The title is not dependent on TinyMCE
        // But the widget input fields are created dynamically by WP when the area is shown
        const titleContentId = 'widget-' + widgetId + '-title';
        widget.find(".text-widget-fields input[id$='_title']").each(function (i, e) {
            hooks.attachContentHook(e, titleContentId);
        });
        const textContentId = 'widget-' + widgetId + '-text';
        hooks.attachEditorHook(editor, textContentId);
    });

    const onWidgetUpdate = function (evt, widget) {
        const widgetBase = widget.find('.id_base').val();
        switch (widgetBase) {
            case 'text':
                const widgetId = widget.find('.widget-id').val();
                const fieldTitle = widget.find(".text-widget-fields input[id$='_title']");
                widget.find(".widget-content input[id^='widget-text-'][id$='-title']").each(function (i, e) {
                    hooks.refreshContentHook(e);
                    hooks.attachContentHook(fieldTitle[0], e.id);
                });

                const fieldText = widget.find(".text-widget-fields textarea[id$='_text']");
                const editor = window.tinyMCE.get(fieldText[0].id);
                widget.find(".widget-content textarea[id^='widget-text-'][id$='-text']").each(function (i, e) {
                    hooks.refreshContentHook(e);
                    if (editor) {
                        hooks.attachEditorHook(editor, e.id);
                        // The text field has not been synced after translation yet.
                        // Because the text field has not been updated by wp.widgets when in Visual Mode,
                        // it still has the translated content before saving the widget.
                        // To allow updateField to change the MCE content, change the value of the text field.
                        const syncInput = widget.find('.sync-input.text');
                        fieldText.val(syncInput.val() + '*');
                    }
                });
                if (widgetId in wp.textWidgets.widgetControls) {
                    wp.textWidgets.widgetControls[widgetId].updateFields();
                }
                break;
            default:
                widget.find(".widget-content input[id^='widget-'][id$='-title']").each(function (i, e) {
                    hooks.refreshContentHook(e);
                });
                break;
        }
        wpWidgets.appendTitle(widget);
    };

    const onWidgetAdded = function (evt, widget) {
        // Rely on refreshContent to create hooks
        onWidgetUpdate(evt, widget);
        // The LSB may not be initialized yet if all widget areas were empty on page load
        hooks.setupLanguageSwitch();
    };

    $(document).on('widget-added', onWidgetAdded);
    $(document).on('widget-updated', onWidgetUpdate);

    const onLanguageSwitch = function () {
        $('#widgets-right .widget').each(function () {
            wpWidgets.appendTitle(this);
        });
    };

    wp.hooks.addAction('qtranx.languageSwitch', 'qtranx/pages/widgets', onLanguageSwitch);
}
