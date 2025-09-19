/* executed for
 /wp-admin/post.php
 /wp-admin/post-new.php
*/
'use strict';
import * as hooks from '../core/hooks';

const $ = jQuery;

const UrlMode = Object.freeze({
   QTX_URL_QUERY: 1,
   QTX_URL_PATH: 2,
   QTX_URL_DOMAIN: 3,
   QTX_URL_DOMAINS: 4,
});

export default function () {
    const convertURL = function (url, lang) {
        switch (qTranslateConfig.url_mode) {
            case UrlMode.QTX_URL_QUERY:
                if (url.search) {
                    url.search += '&lang=' + lang;
                } else {
                    url.search = '?lang=' + lang;
                }
                break;

            case UrlMode.QTX_URL_PATH:
                const homepath = qTranslateConfig.home_url_path;
                let path = url.pathname;
                if (path[0] !== '/')
                    path = '/' + path; // to deal with IE imperfection: https://stackoverflow.com/questions/956233/javascript-pathname-ie-quirk
                const i = path.indexOf(homepath);
                if (i >= 0)
                    url.pathname = qTranslateConfig.homeinfo_path + lang + path.substring(i + homepath.length - 1);
                break;

            case UrlMode.QTX_URL_DOMAIN:
                url.host = lang + '.' + url.host;
                break;

            case UrlMode.QTX_URL_DOMAINS:
                url.host = qTranslateConfig.domains[lang];
                break;
        }
    };

    let btnViewPostA; // a node of 'View Page/Post' link.
    let origUrl, langUrl, origUrlQ;
    let slugSamplePermalink; // 'sample-permalink' node
    let origSamplePermalink;
    let view_link;
    let permalink_query_field;
    const setSlugLanguage = function (lang) {
        if (!btnViewPostA) {
            const btnViewPost = document.getElementById('view-post-btn');
            if (!btnViewPost || !btnViewPost.children.length)
                return;
            btnViewPostA = btnViewPost.children[0];
            if (btnViewPostA.tagName !== 'A')
                return;
            origUrl = btnViewPostA.href;
            langUrl = domCreateElement('a', {});
            origUrlQ = origUrl.search(/\?/) > 0;
        }

        langUrl.href = origUrl;
        convertURL(langUrl, lang);
        btnViewPostA.href = langUrl.href;

        const btnPreviewAction = document.getElementById('preview-action');
        if (btnPreviewAction && btnPreviewAction.children.length) {
            btnPreviewAction.children[0].href = langUrl.href;
        }

        if (qTranslateConfig.url_mode !== UrlMode.QTX_URL_QUERY) {
            if (!slugSamplePermalink) {
                const slugEl = document.getElementById('sample-permalink');
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
    const fieldTitle = $('#title');
    const labelTitle = $('#title-prompt-text');
    const hide_title_prompt_text = function (lang) {
        const value = fieldTitle.val();
        if (value) {
            labelTitle.addClass('screen-reader-text');
        } else {
            labelTitle.removeClass('screen-reader-text');
        }
    };

    hooks.addCustomContentHooks(); // handles values of option 'Custom Fields'
    setSlugLanguage(hooks.getActiveLanguage());

    wp.hooks.addAction('qtranx.languageSwitch', 'qtranx/pages/post', function () {
        setSlugLanguage(lang);
        if (labelTitle && fieldTitle) {
            hide_title_prompt_text(lang);
        }
    });

    function parseQuery(queryString) {
        const query = {};
        const pairs = (queryString[0] === '?' ? queryString.substr(1) : queryString).split('&');
        for (let i = 0; i < pairs.length; i++) {
            const pair = pairs[i].split('=');
            query[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1] || '');
        }
        return query;
    }

    // language menu bar handler
    for (const lang in hooks.getLanguages()) {
        $('#wp-admin-bar-' + lang + ' a').on('click', function (e) {
            e.preventDefault();
            const params = parseQuery(window.location.search);
            const lang = $(this).attr('rel');
            params['lang'] = lang;
            window.location = window.location.origin + window.location.pathname + '?' + $.param(params);
        })
    }
}
