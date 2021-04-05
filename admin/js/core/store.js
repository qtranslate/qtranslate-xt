/**
 * Storage for admin edit language
 */

// the edit language corresponds to the current LSB selection or the main admin language for single mode
var keyEditLanguage = 'qtranslate-xt-admin-edit-language';

export var getStoredEditLanguage = function() {
    sessionStorage.getItem(keyEditLanguage);
};

export var storeEditLanguage = function (lang) {
    try {
        sessionStorage.setItem(keyEditLanguage, lang);
    } catch (e) {
        // no big deal if this can't be stored
        console.log('Failed to store "' + keyEditLanguage + '" with sessionStorage', e);
    }
};
