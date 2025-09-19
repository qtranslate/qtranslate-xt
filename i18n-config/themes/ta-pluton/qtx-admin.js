(function ($) {
    if (qTranx.isPageActive('ta-pluton-panel')) {
        wp.hooks.addAction('qtranx.languageSwitch', 'qtranx/pages/ta-pluton', function () {
            $('.slide-title').each(function (i, e) {
                var t = e.value;
                if (!t) return;
                $(e).parents().eq(3).find('.redux-slides-header').text(t);
            });
        });
    }
})(jQuery);
