/* executed for
 /wp-admin/widgets.php
*/
(function ($) {
    $(function () {
        if (!window.wpWidgets)
            return;

        var qtx = qTranslateConfig.js.get_qtx();

        var onWidgetUpdate = function (evt, widget) {
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

        var onLanguageSwitchAfter = function () {
            $('#widgets-right .widget').each(function () {
                wpWidgets.appendTitle(this);
            });
        };

        qtx.addLanguageSwitchAfterListener(onLanguageSwitchAfter);
    });
})(jQuery);
