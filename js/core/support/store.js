/**
 * Storage for admin edit language
 */
'use strict';
// the edit language corresponds to the current LSB selection or the main admin language for single mode
const STORE_KEY = 'qtranslate-xt-admin-edit-language';

export const getStoredEditLanguage = function () {
    return sessionStorage.getItem(STORE_KEY);
};

export const storeEditLanguage = function (lang) {
    try {
        sessionStorage.setItem(STORE_KEY, lang);
    } catch (e) {
        // no big deal if this can't be stored
        console.log('Failed to store "' + STORE_KEY + '" with sessionStorage', e);
    }
};
