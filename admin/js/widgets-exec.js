/* executed for
 /wp-admin/widgets.php
*/
const $ = jQuery;

$(function () {
    if (!window.wpWidgets)
        return;

    const qtx = qTranslateConfig.js.get_qtx();

    const onWidgetUpdate = function (evt, widget) {
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
        $('#widgets-right .widget').each(function () {
            wpWidgets.appendTitle(this);
        });
    };

    qtx.addLanguageSwitchAfterListener(onLanguageSwitchAfter);
});
