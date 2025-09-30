/* executed for
 /wp-admin/post.php
 /wp-admin/post-new.php
*/
'use strict';
import {config} from '../config'
import {UrlMode} from '../config/enums';
import * as hooks from '../hooks';
import {domCreateElement} from '../support/dom';

const $ = jQuery;

// For now this function is private, quite specific to URL element in DOM and using internal config data (anti-pattern).
const _convertElementURL = function (url, lang) {
    const rawConfig = window.qTranslateConfig;  // Do not re-use this pattern, use public `qTranx.config` API.
    switch (config._urlMode) {
        case UrlMode.QUERY:
            if (url.search) {
                url.search += '&lang=' + lang;
            } else {
                url.search = '?lang=' + lang;
            }
            break;

        case UrlMode.PATH:
            const homepath = rawConfig.home_url_path;
            let path = url.pathname;
            if (path[0] !== '/')
                path = '/' + path; // to deal with IE imperfection: https://stackoverflow.com/questions/956233/javascript-pathname-ie-quirk
            const i = path.indexOf(homepath);
            if (i >= 0)
                url.pathname = rawConfig.homeinfo_path + lang + path.substring(i + homepath.length - 1);
            break;

        case UrlMode.DOMAIN:
            url.host = lang + '.' + url.host;
            break;

        case UrlMode.DOMAINS:
            url.host = rawConfig.domains[lang];
            break;
    }
};

export default function () {
    let btnViewPostA; // a node of 'View Page/Post' link.
    let origUrl, langUrl, origUrlQ;
    let slugSamplePermalink; // 'sample-permalink' node
    let origSamplePermalink;
    let view_link;
    let permalink_query_field;

    const _setSlugLanguage = function (lang) {
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
        _convertElementURL(langUrl, lang);
        btnViewPostA.href = langUrl.href;

        const btnPreviewAction = document.getElementById('preview-action');
        if (btnPreviewAction && btnPreviewAction.children.length) {
            btnPreviewAction.children[0].href = langUrl.href;
        }

        if (config._urlMode !== UrlMode.QUERY) {
            if (!slugSamplePermalink) {
                const slugEl = document.getElementById('sample-permalink');
                if (slugEl && slugEl.offsetHeight > 0 && slugEl.childNodes.length) {
                    slugSamplePermalink = slugEl.childNodes[0]; // span
                    origSamplePermalink = slugSamplePermalink.nodeValue;
                }
            }
            if (slugSamplePermalink) {
                langUrl.href = origSamplePermalink;
                _convertElementURL(langUrl, lang);
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

    _setSlugLanguage(hooks.getActiveLanguage());

    wp.hooks.addAction('qtranx.languageSwitch', 'qtranx/pages/post', function (lang) {
        _setSlugLanguage(lang);
        if (labelTitle && fieldTitle) {
            const value = fieldTitle.val();
            if (value) {
                labelTitle.addClass('screen-reader-text');
            } else {
                labelTitle.removeClass('screen-reader-text');
            }
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
    for (const lang in config.languages) {
        $('#wp-admin-bar-' + lang + ' a').on('click', function (e) {
            e.preventDefault();
            const params = parseQuery(window.location.search);
            const lang = $(this).attr('rel');
            params['lang'] = lang;
            window.location = window.location.origin + window.location.pathname + '?' + $.param(params);
        })
    }
}
