(function ($) {
    wp.hooks.addAction('qtranx.load', 'qtranx/plugins/imaginem-builder-r2', function () {
        if (!$.fn.wp_editor)
            return;
        var qtx = qTranx.hooks;
        if (!qtx.get_ml) {
            qtx.get_ml = function (h, sep) {
                var text = h.contentField.value.trim();
                var tokens = mlUnserializeTokens(text);
                if (!tokens || tokens.length > 1) {//already ML
                    var contents = mlParseTokens(tokens);
                    for (var lang in h.fields) {
                        h.fields[lang].value = contents[lang];
                    }
                    return text;
                }
                if (!sep)
                    sep = h.sepfield ? h.sepfield.value : '{';
                var sb, se;
                switch (sep) {
                    case '[':
                        sb = '[:';
                        se = ']';
                        break;
                    case '<':
                        sb = '<!--:';
                        se = '-->';
                        break;
                    case '{':
                    default:
                        sb = '{:';
                        se = '}';
                        break;
                }
                var lang = h.lang;
                h.fields[lang].value = text;
                var s = '';
                for (var lang in h.fields) {
                    s += sb + lang + se;
                    s += h.fields[lang].value;
                }
                if (s) {
                    s += sb + se;
                }
                return s;
            }
        }

        if (!qtx.addTinyMceHook) {
            qtx.addTinyMceHook = function (h) {
                if (!window.tinyMCE)
                    return;
                if (!tinyMCE.editors[h.contentField.id])
                    return;
                var id = h.contentField.id;
                var ed = tinyMCE.editors[id];
                h.mce = ed;
                $(ed.getContainer()).addClass('qtranxs-translatable');
            }
        }

        var aqpb_wp_editor = $.fn.wp_editor;
        $.fn.wp_editor = function (content) {
            var id = $(this).attr('id');
            var bsm = $(this).closest('div.block-settings.modal');

            //console.log('wp_editor: this: %o', this);
            var h = qtx.hasContentHook(id);
            if (h) {
                var lang = h.lang;
                var text = h.fields[lang].value;
                h.contentField.value = text;
                //$(this).html(text);
            }
            aqpb_wp_editor.call(this, content);
            if (!h)
                return;
            var e = document.getElementById(id);
            if (!e)
                return;

            h.contentField = e;
            for (var lang in h.fields) {
                var f = h.fields[lang];
                e.parentNode.insertBefore(f, e);
            }
            e.parentNode.insertBefore(h.sepfield, e);

            bsm.one('hidden.bs.modal', function () {
                //console.log('hide.bs.modal: this: %o', this);
                var v = qtx.get_ml(h);
                h.contentField.value = v;
                h.mce = null;
            });

            qtx.addTinyMceHook(h);

            //var lsb = qtx.createSetOfLSB();
            //$(e).closest('.wp-editor-wrap').parent().each( function() {
            //	this.insertBefore(lsb, this.firstChild);
            //});

            //qtx.addContentHooksTinyMCE();
            //qtx.addContentHook(e);
        }
        /*
            $('.wp-editor-area.qtranxs-translatable').each( function() {
                //console.log('.wp-editor-area: this: %o', this);
                var p = $(this).parent();
                var pp = $(this).closest('.wp-editor-wrap').parent();
                if(pp.length == 0)
                    return;
                p.find('input[type="hidden"]').each( function() {
                    console.log('input[type="hidden"]: this: %o', this);
                    $(this).appendTo(pp);
                });
            });
        */
    });
})(jQuery);
