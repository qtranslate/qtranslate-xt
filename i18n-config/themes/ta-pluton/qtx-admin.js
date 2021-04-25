(function ($) {
    $(document).on('qtxLoadAdmin:ta-pluton-panel', function (evt, qtx) {
        qtx.addLanguageSwitchAfterListener(function () {
            $('.slide-title').each(function (i, e) {
                var t = e.value;
                if (!t) return;
                $(e).parents().eq(3).find('.redux-slides-header').text(t);
            });
        });
    });
})(jQuery);
