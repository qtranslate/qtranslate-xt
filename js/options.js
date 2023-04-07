/* executed for
 /wp-admin/options-general.php
*/
'use strict';
const $ = jQuery;

const keySection = 'qtranslate-xt-admin-section';

const switchTab = function (hash) {
    if (!hash) {
        return false;
    }

    const anchor = $('.nav-tab-wrapper a[href="' + hash + '"]');
    if (!anchor.length) {
        return false;
    }

    anchor.parent().children().removeClass('nav-tab-active');
    anchor.addClass('nav-tab-active');

    const form = $('#qtranxs-configuration-form');
    const tabId = hash.replace('#', '#tab-');
    const tabContents = $('.tabs-content');
    tabContents.children().addClass('hidden');
    tabContents.find('div' + tabId).removeClass('hidden');
    const action = form.attr('action').replace(/(#.*|$)/, hash);
    form.attr('action', action);

    try {
        sessionStorage.setItem(keySection, hash)
    } catch (e) {
        // no big deal if this can't be stored
        console.log('Failed to store "' + keySection + '" with sessionStorage', e);
    }

    return true;
};

const onHashChange = function (defaultHash) {
    const locationHash = window.location.hash;
    if (switchTab(locationHash)) {
        return;
    }

    const storedHash = sessionStorage.getItem(keySection);
    if (switchTab(storedHash)) {
        return;
    }

    switchTab(defaultHash);
};

const onFlagChange = function (url) {
    const $preview = $('#preview_flag');
    $preview.css('display', 'inline');
    $preview.attr('src', $preview.attr('data-flag-path') + url);
};

$(function () {
    $(window).bind('hashchange', function () {
        onHashChange();
    });
    onHashChange('#general');

    const $langFlag = $('#language_flag');
    $langFlag.on('change', function () {
        onFlagChange(this.value);
    });
    onFlagChange($langFlag.val());

    $('#qtranxs_debug_query').on('click', function () {
        const cookies = document.cookie.split(';');
        // Check "RegExp: @@split" support, see also: https://caniuse.com/mdn-javascript_builtins_regexp_--split
        const isRegexSplitSupported = ('a~b'.split(/(~)/).length === 3);
        const browserInfo = {
            'cookies': [],
            'navigator': navigator.userAgent,
            'Javascript built-in RegExp: @@split': isRegexSplitSupported ? 'supported' : 'not supported!',
        };
        for (let i = 0; i < cookies.length; i++) {
            const cookieStr = cookies[i].trim();
            if (cookieStr.indexOf('qtrans') === 0) {
                browserInfo['cookies'].push(cookieStr);
            }
        }

        if (!isRegexSplitSupported) {
            $('#qtranxs_debug_info_browser').css('color', 'red');
        }
        $('#qtranxs_debug_info_browser').val(JSON.stringify(browserInfo, null, 2));
        $('#qtranxs_debug_info_versions').val('...');
        $('#qtranxs_debug_info_configuration').val('...');
        $('#qtranxs_debug_info').show();

        $.ajax({
            url: ajaxurl,
            dataType: 'json',
            data: {
                action: 'admin_debug_info'
            },
            success: function (response) {
                console.log('debug-info', response);
                $('#qtranxs_debug_info_versions').val(JSON.stringify(response['versions'], null, 2));
                $('#qtranxs_debug_info_configuration').val(JSON.stringify(response['configuration'], null, 2));
            },
            error: function (xhr) {
                console.error('debug-info', xhr);
                $('#qtranxs_debug_info_versions').val('An error occurred: status=' + xhr.status + ' (' + xhr.statusText + ')');
            }
        });
    })

    // Checkboxes with double-check has associated checkbox(es) depending on it.
    $('.qtranxs_double_check').on('click', function () {
        const check = $($(this).attr('data-double-check'));  // There may be more than one checkbox (CSS selector).
        check.prop('disabled', !$(this).prop('checked'));
        check.prop('checked', false);
    });
});
