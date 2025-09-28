/**
 * Deprecated functions that are isolated or can use public API directly.
 * ATTENTION - ALL THESE FUNCTIONS WILL BE REMOVED IN NEXT MAJOR RELEASE.
 */
'use strict';
import {config} from '../core/config';
import {addContentHook, addContentHooks, addDisplayHook, addDisplayHooks} from './handlers';

// -------------------------------------------------------------------------------------------------------
// Moved to config
// -------------------------------------------------------------------------------------------------------

/**
 * Get language meta-data.
 *
 * @returns {*} dictionnary indexed by two-letter language code.
 *  .name in native language
 *  .admin_name in the admin language chosen
 *  .flag
 *  .locale
 *  .locale_html
 */
export const getLanguages = function () {
    wp.deprecated('getLanguages', {
        since: '3.16.0',
        version: '4.0.0',
        plugin: 'qTranslate-XT',
        alternative: 'qTranx.config.languages'
    });
    return config.languages;
};

/**
 * Get URL to folder with flag images.
 *
 * @returns {string}
 */
export const getFlagLocation = function () {
    wp.deprecated('getFlagLocation', {
        since: '3.16.0',
        version: '4.0.0',
        plugin: 'qTranslate-XT',
        alternative: 'qTranx.config.path.flags'
    });
    return config.path.flags;
};

/**
 * Check if a language is enabled.
 *
 * @param {string} lang
 * @returns {boolean} true if 'lang' is in the hash of enabled languages.
 * This function maybe needed, as function mlSplitLangs may return languages,
 * which are not enabled, in case they were previously enabled and had some data.
 * Such data is preserved and re-saved until user deletes it manually.
 */
export const isLanguageEnabled = function (lang) {
    wp.deprecated('isLanguageEnabled', {
        since: '3.16.0',
        version: '4.0.0',
        plugin: 'qTranslate-XT',
        alternative: 'qTranx.config.isLanguageEnabled'
    });
    return config.isLanguageEnabled(lang);
};

// -------------------------------------------------------------------------------------------------------
// addContentHook variants
// -------------------------------------------------------------------------------------------------------

export const addContentHookC = function (inputField) {
    wp.deprecated('addContentHookC', {
        since: '3.16.0',
        version: '4.0.0',
        plugin: 'qTranslate-XT',
        alternative: 'addContentHook(inputField, "<"))',
        hint: 'Comment separators are not supported in addContentHookC since 3.2.9.8.5.'
    });
    return addContentHook(inputField, '['); // Enforced to B-separator since 3.2.9.8.5.
};

export const addContentHookB = function (inputField) {
    wp.deprecated('addContentHookB', {
        since: '3.16.0',
        version: '4.0.0',
        plugin: 'qTranslate-XT',
        alternative: 'addContentHook',
        hint: 'Bracket separator is the default.'
    });
    return addContentHook(inputField, '[');
};

export const addContentHookById = function (id, sep, name) {
    wp.deprecated('addContentHookId', {
        since: '3.16.0',
        version: '4.0.0',
        plugin: 'qTranslate-XT',
        alternative: 'addContentHook(document.getElementById(id), sep, name)',
    });
    return addContentHook(document.getElementById(id), sep, name);
};

export const addContentHookByIdName = function (name) {
    wp.deprecated('addContentHookByIdName', {
        since: '3.16.0',
        version: '4.0.0',
        plugin: 'qTranslate-XT',
        alternative: 'addContentHook(document.getElementById(nameWithoutSeparator),container,sep)',
        hint: 'Only meant for custom fields (separator extracted from name), now handled internally.'
    });
    // ATTENTION we don't support this separator extraction in addContentHooks!
    let sep = '[';
    switch (name[0]) {
        case '<':
        case '[':
            sep = name.substring(0, 1);
            name = name.substring(1);  // The rest hold the element ID.
            break;
        default:
            break;
    }
    return addContentHook(document.getElementById(name), sep);
};

export const addContentHookByIdC = function (id) {
    wp.deprecated('addContentHookByIdC', {
        since: '3.16.0',
        version: '4.0.0',
        plugin: 'qTranslate-XT',
        alternative: 'addContentHook(document.getElementById(id), "<")',
        hint: 'Comment separators are not supported in addContentHookByIdC since 3.2.9.8.5.'
    });
    return addContentHook(document.getElementById(id), '[');  // Enforced to B-separator since 3.2.9.8.5.
};

export const addContentHookByIdB = function (id) {
    wp.deprecated('addContentHookByIdB', {
        since: '3.16.0',
        version: '4.0.0',
        plugin: 'qTranslate-XT',
        alternative: 'addContentHook(document.getElementById(id))',
        hint: 'Bracket separator is the default and id prop is used first for selection.'
    });
    return addContentHook(document.getElementById(id), '[');
};

export const addContentHooksByTagInClass = function (name, tag, container) {
    wp.deprecated('addContentHooksByTagInClass', {
        since: '3.16.0',
        version: '4.0.0',
        plugin: 'qTranslate-XT',
        alternative: 'addContentHooks($(container).find("." + name + " " + tag));',
        hint: 'Select items by class name then sub-items by tag.'
    });
    addContentHooks($(container).find('.' + name + ' ' + tag));
};

// -------------------------------------------------------------------------------------------------------
// addDisplayHook variants
// -------------------------------------------------------------------------------------------------------

export const addDisplayHookById = function (id) {
    wp.deprecated('addDisplayHookById', {
        since: '3.16.0',
        version: '4.0.0',
        plugin: 'qTranslate-XT',
        alternative: 'addDisplayHook(document.getElementById(id))',
    });
    return addDisplayHook(document.getElementById(id));
};

export const addDisplayHooksByClass = function (name, container) {
    wp.deprecated('addDisplayHooksByClass', {
        since: '3.16.0',
        version: '4.0.0',
        plugin: 'qTranslate-XT',
        alternative: 'addDisplayHooks(container.getElementsByClassName(name))',
    });
    addDisplayHooks(container.getElementsByClassName(elems));
};

export const addDisplayHooksByTagInClass = function (name, tag, container) {
    wp.deprecated('addDisplayHooksByTagInClass', {
        since: '3.16.0',
        version: '4.0.0',
        plugin: 'qTranslate-XT',
        alternative: 'addDisplayHooks($(container).find("." + name + " " + tag)',
        hint: 'Select items by class name then sub-items by tag.'
    });
    addDisplayHooks($(container).find('.' + name + ' ' + tag));
};
