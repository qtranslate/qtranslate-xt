/* executed for
 /wp-admin/post.php
 /wp-admin/post-new.php
 /wp-admin/term.php
*/
(function ($) {
    $(function () {
        if (!window.YoastSEO || !window.YoastSEO.app)
            return;

        var qtx = qTranslateConfig.js.get_qtx();

        //deal with imperfection of QTranslate Slug
        if ($('#qts_nonce').length) {
            $('#snippet-editor-slug').closest('label').hide();
        }

        //deal with Yoast
        var qreplace_vars = {};
        if (window.wpseoReplaceVarsL10n) {

            for (var lang in qTranslateConfig.language_config) {
                qreplace_vars[lang] = {};
            }

            for (var key in wpseoReplaceVarsL10n.replace_vars) {
                var rv = wpseoReplaceVarsL10n.replace_vars[key];
                if (typeof rv === 'string') {
                    var rvs = mlExplode(rv);
                    for (var lang in qTranslateConfig.language_config) {
                        qreplace_vars[lang][key] = rvs[lang];
                    }
                } else {
                    for (var lang in qTranslateConfig.language_config) {
                        qreplace_vars[lang][key] = rv;
                    }
                }
            }
            wpseoReplaceVarsL10n.replace_vars = qreplace_vars[qTranslateConfig.activeLanguage];
        }

        var focuskw_input = $('#yoast_wpseo_focuskw');
        var focuskw_edit = $('#yoast_wpseo_focuskw_text_input');
        focuskw_edit.addClass('qtranxs-translatable');

        //var title_snippet = $('#snippet_title');
        //var title_input = $('#yoast_wpseo_title');
        var title_edit = $('#snippet-editor-title');
        title_edit.addClass('qtranxs-translatable');

        //var metadesc_input = $('#yoast_wpseo_metadesc');
        var metadesc_edit = $('#snippet-editor-meta-description');
        metadesc_edit.addClass('qtranxs-translatable');

        var removeChildren = function (e) {
            while (e.firstChild) {
                e.removeChild(e.firstChild);
            }
        };

        // saveSnippetData

        qtx.addLanguageSwitchAfterListener(
            function (lang) {

                if (window.wpseoReplaceVarsL10n) {
                    wpseoReplaceVarsL10n.replace_vars = qreplace_vars[lang];
                }

                var app = YoastSEO.app;

                var e = document.getElementById(app.config.targets.output);
                if (e)
                    removeChildren(e);

                var c = document.getElementById('yoast-seo-content-analysis');
                if (c)
                    removeChildren(c);

                //app.showLoadingDialog();

                focuskw_edit.val(focuskw_input.val());//temporary until Yoast notice their error with duplicated entry in db and fix it
                app.rawData = app.callbacks.getData();

                //focuskw_edit.val(app.rawData.keyword);
                metadesc_edit.val(app.rawData.snippetMeta);
                title_edit.val(app.rawData.snippetTitle);

                // TODO this doesn't work anymore: app.snippetPreview is undefined!! Pretty much all JS code needs to be reviewed.
                // app.snippetPreview.data.title = app.rawData.snippetTitle;
                // //app.snippetPreview.data.urlPath = app.rawData.snippetCite;
                // app.snippetPreview.data.metaDesc = app.rawData.snippetMeta;
                // //app.snippetPreview.data = {};
                // //app.runAnalyzer();

                //app.getData();
                //app._pureRefresh();
                app.refresh();
                //app.snippetPreview.refresh();
                //app.removeLoadingDialog();
            }
        );

    });
})(jQuery);
