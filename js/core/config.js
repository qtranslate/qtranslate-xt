'use strict';

const $ = jQuery;
const qTranslateConfig = window.qTranslateConfig;

const pageConfigKeys = function () {
    return qTranslateConfig['page_config']?.['keys'] ?? [];
};

export const isPageActive = function (page) {
    return (pageConfigKeys().indexOf(page) >= 0);
};
