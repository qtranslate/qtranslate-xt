/* executed for
 /wp-admin/widgets.php
*/
'use strict';
const $ = jQuery;

$(document).on('qtxLoadAdmin:widgets', (event, qtx) => {
    if (!window.wpWidgets)
        return;

    console.log('QTX widgets');

    jQuery( document ).on( 'tinymce-editor-setup', function( event, editor ) {
        console.log('QTX tinymce-editor-init');
        editor.settings.toolbar1 += ',mybutton';
        editor.addButton( 'mybutton', {
            text: 'My button',
            icon: false,
            onclick: function () {
                editor.insertContent( 'Text from my button' );
            }
        });

        editor.on( 'init', function() {
            var widget = jQuery('#widgets-right');
            widget.find('span.in-widget-title').each(function (i, e) {
                qtx.addDisplayHook(e);
            });
            widget.find(".text-widget-fields input[id$='_title']").each(function (i, e) {
                console.log('found title', e)
                var ret = qtx.addContentHookById(e.id, '[', 'title');
                console.log('addContentHook', ret)
                // qtx.refreshContentHook(e);
            });
            widget.find(".text-widget-fields textarea[id$='_text']").each(function (i, e) {
                console.log('found text', e)
                var ret = qtx.addContentHook(e, '[', 'text');
                console.log('addContentHook', ret)
                // qtx.refreshContentHook(e);
            });
        } );

    });

    const onWidgetUpdate = function (evt, widget) {
        console.log('onWidgetUpdate', widget);
        widget.find('span.in-widget-title').each(function (i, e) {
            qtx.addDisplayHook(e);
        });
        widget.find("input[id^='widget-'][id$='-title']").each(function (i, e) {
            qtx.refreshContentHook(e);
        });
        widget.find("textarea[id^='widget-text-'][id$='-text']").each(function (i, e) {
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
