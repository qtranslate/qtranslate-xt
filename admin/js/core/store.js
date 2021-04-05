/**
 * Storage for admin edit language
 */

// the edit language corresponds to the current LSB selection or the main admin language for single mode
const keyEditLanguage = 'qtranslate-xt-admin-edit-language';

export const getStoredEditLanguage = function () {
    return sessionStorage.getItem(keyEditLanguage);
};

export const storeEditLanguage = function (lang) {
    try {
        sessionStorage.setItem(keyEditLanguage, lang);
    } catch (e) {
        // no big deal if this can't be stored
        console.log('Failed to store "' + keyEditLanguage + '" with sessionStorage', e);
    }
};
