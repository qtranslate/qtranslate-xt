/* executed for
 /wp-admin/post.php
 /wp-admin/post-new.php
*/
(function ($) {
    $(function () {
        var qtx = qTranslateConfig.js.get_qtx();
        var convertURL = function (url, lang) {
            switch (qTranslateConfig.url_mode.toString()) {
                // TODO define proper constants
                case '1':   // QTX_URL_QUERY
                    if (url.search) {
                        url.search += '&lang=' + lang;
                    } else {
                        url.search = '?lang=' + lang;
                    }
                    break;
                case '2': // QTX_URL_PATH
                    var homepath = qTranslateConfig.home_url_path;
                    var p = url.pathname;
                    if (p[0] !== '/')
                        p = '/' + p; // to deal with IE imperfection: http://stackoverflow.com/questions/956233/javascript-pathname-ie-quirk
                    var i = p.indexOf(homepath);
                    if (i >= 0)
                        url.pathname = qTranslateConfig.homeinfo_path + lang + p.substring(i + homepath.length - 1);
                    break;
                case '3': // QTX_URL_DOMAIN
                    url.host = lang + '.' + url.host;
                    break;
                case '4': // QTX_URL_DOMAINS
                    url.host = qTranslateConfig.domains[lang];
                    break;
            }
        };

        var btnViewPostA; // a node of 'View Page/Post' link.
        var origUrl, langUrl, origUrlQ;
        var slugSamplePermalink; // 'sample-permalink' node
        var origSamplePermalink;
        var view_link;
        var permalink_query_field;
        var setSlugLanguage = function (lang) {
            if (!btnViewPostA) {
                var btnViewPost = document.getElementById('view-post-btn');
                if (!btnViewPost || !btnViewPost.children.length)
                    return;
                btnViewPostA = btnViewPost.children[0];
                if (btnViewPostA.tagName !== 'A')
                    return;
                origUrl = btnViewPostA.href;
                langUrl = qtranxj_ce('a', {});
                origUrlQ = origUrl.search(/\?/) > 0;
            }

            langUrl.href = origUrl;
            convertURL(langUrl, lang);
            btnViewPostA.href = langUrl.href;

            var btnPreviewAction = document.getElementById('preview-action');
            if (btnPreviewAction && btnPreviewAction.children.length) {
                btnPreviewAction.children[0].href = langUrl.href;
            }

            // TODO define proper constants
            if (qTranslateConfig.url_mode !== 1) {
                // !QTX_URL_QUERY
                if (!slugSamplePermalink) {
                    var slugEl = document.getElementById('sample-permalink');
                    if (slugEl && slugEl.offsetHeight > 0 && slugEl.childNodes.length) {
                        slugSamplePermalink = slugEl.childNodes[0]; // span
                        origSamplePermalink = slugSamplePermalink.nodeValue;
                    }
                }
                if (slugSamplePermalink) {
                    langUrl.href = origSamplePermalink;
                    convertURL(langUrl, lang);
                    slugSamplePermalink.nodeValue = langUrl.href;
                }
            } else {
                // QTX_URL_QUERY
                if (!permalink_query_field) {
                    $('#sample-permalink').append('<span id="sample-permalink-lang-query"></span>');
                    permalink_query_field = $('#sample-permalink-lang-query');
                }
                if (permalink_query_field) {
                    permalink_query_field.text((origUrl.search(/\?/) < 0 ? '/?lang=' : '&lang=') + lang);
                }
            }

            if (!view_link) view_link = document.getElementById('wp-admin-bar-view');
            if (view_link && view_link.children.length) {
                view_link.children[0].href = btnViewPostA.href;
            }
        };

        // handle prompt text of empty field 'title', not important
        var field_title = $('#title');
        var title_label = $('#title-prompt-text');
        var hide_title_prompt_text = function (lang) {
            var value = field_title.val();
            if (value) {
                title_label.addClass('screen-reader-text');
            } else {
                title_label.removeClass('screen-reader-text');
            }
        };

        qtx.addCustomContentHooks(); // handles values of option 'Custom Fields'
        setSlugLanguage(qtx.getActiveLanguage());

        qtx.addLanguageSwitchAfterListener(setSlugLanguage);

        if (title_label && field_title) {
            qtx.addLanguageSwitchAfterListener(hide_title_prompt_text);
        }

        function parseQuery(queryString) {
            var query = {};
            var pairs = (queryString[0] === '?' ? queryString.substr(1) : queryString).split('&');
            for (var i = 0; i < pairs.length; i++) {
                var pair = pairs[i].split('=');
                query[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1] || '');
            }
            return query;
        }

        // language menu bar handler
        for (var lang in qtx.getLanguages()) {
            $('#wp-admin-bar-' + lang + ' a').on('click', function (e) {
                e.preventDefault();
                var params = parseQuery(window.location.search);
                var lang = $(this).attr('rel');
                params['lang'] = lang;
                window.location = window.location.origin + window.location.pathname + '?' + $.param(params);
            })
        }

    });
})(jQuery);
