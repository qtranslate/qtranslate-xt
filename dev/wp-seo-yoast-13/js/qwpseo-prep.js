/* executed for
 /wp-admin/term.php
*/
(function ($) {
    $(function () {
        var qtx = qTranslateConfig.js.get_qtx();

        var h = qtx.hasContentHook('description');
        if (!h)
            return;

        //deal with imperfection of QTranslate Slug
        if (!$('#slug').length) {
            $('.term-slug-wrap').append('<input name="slug" id="slug" type="hidden" value="">');
        }

        //deal with imperfection of Yoast
        var d = $('#edittag').find('#description');
        if (!d.length)
            return;

        //Yoast will delete this field in term-scraper
        var contents = mlExplode(d.val());
        h.contentField.value = contents[qTranslateConfig.activeLanguage];
        for (var lang in h.fields) {
            h.fields[lang].value = contents[lang];
        }
        d.val(h.contentField.value);
    });
})(jQuery);
