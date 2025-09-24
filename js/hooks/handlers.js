/**
 * Multi-lang hooks for LSB (Language Switching Buttons), ML content, editors and display.
 *
 * Attention! The interface is not fully initialized before the `qtxLoadAdmin` event.
 * @see init function
 *
 * Read Integration Guide: https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide for more information.
 */
'use strict';
import {config} from '../core';
import {domCreateElement} from '../core/dom';
import {mlSplitRaw, mlExplode, mlParseTokens} from '../core/multi-lang-tags';
import {getStoredEditLanguage, storeEditLanguage} from '../core/store';

const $ = jQuery;

const qTranslateConfig = window.qTranslateConfig;
/**
 * Internal state of hooks and languageSwitch, not exposed
 */
const _contentHooks = {};
const _displayHookNodes = [];
const _displayHookAttrs = [];
let _languageSwitchInitialized = false;
const _tabSwitchElements = {};  // DOM elements indexed by language.

// TODO: remove deprecated switch handlers in next major release
const _onTabSwitchFunctionsAction = [];
const _onTabSwitchFunctionsLoad = [];
const _onTabSwitchFunctionsSave = [];

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
 * This function maybe needed, as function mlExplode may return languages,
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

/**
 * Get the currently active language selected in LSB.
 *
 * @returns {string}
 */
export const getActiveLanguage = function () {
    return qTranslateConfig.activeLanguage;
};

/**
 * Check if a content hooks exists.
 * @param {string} id hook
 * @returns {*}
 */
export const hasContentHook = function (id) {
    return _contentHooks[id];
};

/**
 * Attach an input field to an existing content hook.
 *
 * Usually, this function is called internally for an input field edited by the user.
 * In some cases (e.g. widgets), the editable fields are different from those containing
 * the translatable content. This function allows to attach them to the hook.
 * The single content field initially attached is not updated anymore but the hidden fields
 * storing each language content are still updated.
 * @see addContentHook
 * @see attachEditorHook
 *
 * @param inputField field editable by the user
 * @param contentId optional element ID to override the content hook key (default: input ID)
 */
export const attachContentHook = function (inputField, contentId) {
    const hook = _contentHooks[contentId ? contentId : inputField.id];
    if (!hook) {
        return;
    }
    inputField.classList.add('qtranxs-translatable');
    hook.contentField = inputField;
}

/**
 * Add a content hook.
 */
export const addContentHook = function (inputField, encode, fieldName) {
    if (!inputField) return false;
    switch (inputField.tagName) {
        case 'TEXTAREA':
            break;
        case 'INPUT':
            // reject the types which cannot be multilingual
            switch (inputField.type) {
                case 'button':
                case 'checkbox':
                case 'password':
                case 'radio':
                case 'submit':
                    return false;
            }
            break;
        default:
            return false;
    }

    if (!fieldName) {
        if (!inputField.name) {
            console.error('Missing name in field', inputField);
            return false;
        }
        fieldName = inputField.name;
    }

    if (inputField.id) {
        if (_contentHooks[inputField.id]) {
            if ($.contains(document, inputField))
                return _contentHooks[inputField.id];
            // otherwise some Java script already removed previously hooked element
            console.warn('No input field with id=', inputField.id);
            removeContentHook(inputField);
        }
    } else if (!_contentHooks[fieldName]) {
        inputField.id = fieldName;
    } else {
        let idx = 0;
        do {
            ++idx;
            inputField.id = fieldName + idx;
        } while (_contentHooks[inputField.id]);
    }

    const hook = _contentHooks[inputField.id] = {};
    hook.name = fieldName;
    hook.lang = qTranslateConfig.activeLanguage;
    attachContentHook(inputField);

    let qtxPrefix;
    if (encode) {
        switch (encode) {
            case 'slug':
                qtxPrefix = 'qtranslate-slugs[';
                break;
            case 'term':
                qtxPrefix = 'qtranslate-terms[';
                break;
            default:
                qtxPrefix = 'qtranslate-fields[';
                break;
        }
    } else {
        // since 3.1 we get rid of <--:--> encoding
        encode = '[';
        qtxPrefix = 'qtranslate-fields[';
    }

    hook.encode = encode;

    let baseName, suffixName;
    const pos = hook.name.indexOf('[');
    if (pos < 0) {
        baseName = qtxPrefix + hook.name + ']';
    } else {
        baseName = qtxPrefix + hook.name.substring(0, pos) + ']';
        if (hook.name.lastIndexOf('[]') < 0) {
            baseName += hook.name.substring(pos);
        } else {
            const len = hook.name.length - 2;
            if (len > pos)
                baseName += hook.name.substring(pos, len);
            suffixName = '[]';
        }
    }

    const inputFieldFormId = $(inputField).attr('form');
    const $form = (inputFieldFormId !== undefined) ? $('#' + inputFieldFormId) : $(inputField).closest('form');
    if (!$form.length) {
        console.error('No form found for translatable field id=', inputField.id);
        return;
    }
    const form = $form[0];

    let contents;
    hook.fields = {};
        if (!config.isEditorModeRAW()) {
        // Most crucial moment when untranslated content is parsed
        contents = mlExplode(inputField.value);
        // Substitute the current ML content with translated content for the current language
        inputField.value = contents[hook.lang];

        // Insert translated content for each language before the current field
        for (const lang in contents) {
            const text = contents[lang];
            let newName = baseName + '[' + lang + ']';
            if (suffixName)
                newName += suffixName;
            const newField = domCreateElement('input', {
                name: newName,
                type: 'hidden',
                className: 'hidden',
                value: text
            }, inputField.parentNode, inputField);
            if (inputFieldFormId !== undefined) {
                $(newField).attr('form', inputFieldFormId);
            }
            hook.fields[lang] = newField;
        }

        // The edit language allows the server to assign the fields being edited to the active language (not switched).
        const $hidden = $form.find('input[name="qtranslate-edit-language"]');
        if (!$hidden.length) {
            domCreateElement('input', {
                type: 'hidden',
                name: 'qtranslate-edit-language',
                value: qTranslateConfig.activeLanguage
            }, form, form.firstChild);
        }
    }

    // since 3.2.9.8 - hook.contents -> hook.fields
    // since 3.3.8.7 - slug & term
    switch (encode) {
        case 'slug':
        case 'term': {
            if (config.isEditorModeRAW())
                contents = mlExplode(inputField.value);
            hook.sepfield = domCreateElement('input', {
                name: baseName + '[qtranslate-original-value]',
                type: 'hidden',
                className: 'hidden',
                value: contents[config.lang.default]
            }, inputField.parentNode, inputField);
        }
            break;
        default: {
            if (!config.isEditorModeRAW()) {
                hook.sepfield = domCreateElement('input', {
                    name: baseName + '[qtranslate-separator]',
                    type: 'hidden',
                    className: 'hidden',
                    value: encode
                }, inputField.parentNode, inputField);
            }
        }
            break;
    }

    if (hook.sepfield && inputFieldFormId !== undefined) {
        $(hook.sepfield).attr('form', inputFieldFormId);
    }

    return hook;
};
export const addContentHookC = function (inputField) {
    return addContentHook(inputField, '['); // TODO shouldn't it be '<' ?!
};
export const addContentHookB = function (inputField) {
    return addContentHook(inputField, '[');
};

export const addContentHookById = function (id, sep, name) {
    return addContentHook(document.getElementById(id), sep, name);
};
export const addContentHookByIdName = function (name) {
    let sep;
    switch (name[0]) {
        case '<':
        case '[':
            sep = name.substring(0, 1);
            name = name.substring(1);
            break;
        default:
            break;
    }
    return addContentHookById(name, sep);
};

export const addContentHookByIdC = function (id) {
    return addContentHookById(id, '['); // TODO shouldn't it be '<' ?!
};

export const addContentHookByIdB = function (id) {
    return addContentHookById(id, '[');
};

export const addContentHooks = function (fields, sep, fieldName) {
    for (let i = 0; i < fields.length; ++i) {
        const field = fields[i];
        addContentHook(field, sep, fieldName);
    }
};

const addContentHooksByClassName = function (name, container, sep) {
    if (!container)
        container = document;
    const fields = container.getElementsByClassName(name);
    addContentHooks(fields, sep);
};

export const addContentHooksByClass = function (name, container) {
    let sep;
    if (name.indexOf('<') === 0 || name.indexOf('[') === 0) {
        sep = name.substring(0, 1);
        name = name.substring(1);
    }
    addContentHooksByClassName(name, container, sep);
};

export const addContentHooksByTagInClass = function (name, tag, container) {
    const elems = container.getElementsByClassName(name);
    for (let i = 0; i < elems.length; ++i) {
        const elem = elems[i];
        const items = elem.getElementsByTagName(tag);
        addContentHooks(items);
    }
};

export const removeContentHook = function (inputField) {
    if (!inputField || !inputField.id) {
        return false;
    }
    const hook = _contentHooks[inputField.id];
    if (!hook) {
        return false;
    }
    if (hook.sepfield) {
        $(hook.sepfield).remove();
    }
    for (const lang in hook.fields) {
        const langField = hook.fields[lang];
        $(langField).remove();
    }
    if (hook.mce) {
        const editor = hook.mce;
        editor.getContentAreaContainer().classList.remove('qtranxs-translatable');
        editor.getElement().classList.remove('qtranxs-translatable');
    }
    // The current content field may not be the same as the input field, in case it was re-attached (e.g. widgets)
    hook.contentField.classList.remove('qtranxs-translatable');
    delete _contentHooks[inputField.id];
    inputField.classList.remove('qtranxs-translatable');
    return true;
};

export const refreshContentHook = function (inputField) {
    removeContentHook(inputField);
    return addContentHook(inputField);
};

const getDisplayContentDefaultValue = function (contents) {
    if (contents[config.lang.detected])
        return '(' + config.lang.detected + ') ' + contents[config.lang.detected];
    if (contents[config.lang.default])
        return '(' + config.lang.default + ') ' + contents[config.lang.default];
    for (const lang in contents) {
        if (!contents[lang])
            continue;
        return '(' + lang + ') ' + contents[lang];
    }
    return '';
};

const completeDisplayContent = function (contents) {
    let default_value = null;
    for (const lang in contents) {
        if (contents[lang])
            continue;
        if (!default_value)
            default_value = getDisplayContentDefaultValue(contents);
        contents[lang] = default_value;
    }
};

const addDisplayHookNode = function (node) {
    if (!node.nodeValue)
        return 0;
    const tokens = mlSplitRaw(node.nodeValue);
    if (!tokens || !tokens.length || tokens.length === 1)
        return 0;
    const hook = {};
    hook.nd = node;
    hook.contents = mlParseTokens(tokens);
    completeDisplayContent(hook.contents);
    node.nodeValue = hook.contents[qTranslateConfig.activeLanguage];
    _displayHookNodes.push(hook);
    return 1;
};

const addDisplayHookAttr = function (node, attr) {
    if (!node.hasAttribute(attr)) return 0;
    const value = node.getAttribute(attr);
    const tokens = mlSplitRaw(value);
    if (!tokens || !tokens.length || tokens.length === 1)
        return 0;
    const hook = {};
    hook.nd = node;
    hook.attr = attr;
    hook.contents = mlParseTokens(tokens);
    completeDisplayContent(hook.contents);
    node.setAttribute(attr, hook.contents[qTranslateConfig.activeLanguage]);
    _displayHookAttrs.push(hook);
    return 1;
};

export const addDisplayHook = function (elem) {
    if (!elem || !elem.tagName)
        return 0;
    switch (elem.tagName) {
        case 'TEXTAREA':
            return 0;
        case 'INPUT':
            if (elem.type === 'submit' && elem.value) {
                return addDisplayHookAttr(elem, 'value');
            }
            return 0;
        default:
            break;
    }

    let nbHooks = 0;
    if (elem.childNodes && elem.childNodes.length) {
        for (let i = 0; i < elem.childNodes.length; ++i) {
            const node = elem.childNodes[i];
            switch (node.nodeType) {
                // https://www.w3.org/TR/REC-DOM-Level-1/level-one-core.html#ID-1950641247
                case 1: // ELEMENT_NODE
                    nbHooks += addDisplayHook(node);
                    break;
                case 2: // ATTRIBUTE_NODE
                case 3: // TEXT_NODE
                    nbHooks += addDisplayHookNode(node);
                    break;
                default:
                    break;
            }
        }
    }
    return nbHooks;
};

export const addDisplayHookById = function (id) {
    return addDisplayHook(document.getElementById(id));
};

const updateMceEditorContent = function (hook) {
    let text = hook.contentField.value;
    if (hook.mce.settings.wpautop && window.switchEditors) {
        text = window.switchEditors.wpautop(text);
    }
    hook.mce.setContent(text);
};

const doTabSwitch = function (lang) {
    storeEditLanguage(lang);

    for (let i = _displayHookNodes.length; --i >= 0;) {
        const hook = _displayHookNodes[i];
        if (hook.nd.parentNode) {
            hook.nd.nodeValue = hook.contents[lang]; // IE gets upset here if node was removed
        } else {
            _displayHookNodes.splice(i, 1); // node was removed by some other function
        }
    }
    for (let i = _displayHookAttrs.length; --i >= 0;) {
        const hook = _displayHookAttrs[i];
        if (hook.nd.parentNode) {
            hook.nd.setAttribute(hook.attr, hook.contents[lang]);
        } else {
            _displayHookAttrs.splice(i, 1); // node was removed by some other function
        }
    }
    if (config.isEditorModeRAW())
        return;
    for (const key in _contentHooks) {
        const hook = _contentHooks[key];
        const visualMode = hook.mce && !hook.mce.hidden;
        if (visualMode) {
            hook.mce.save();
        }

        const text = hook.contentField.value.trim();
        const tokens = mlSplitRaw(text);
        if (!tokens || tokens.length <= 1) {
            // value is not ML, switch it to other language
            hook.fields[hook.lang].value = text;
            hook.lang = lang;
            const value = hook.fields[hook.lang].value;
            if (hook.contentField.placeholder && value !== '') {
                // since 3.2.7
                hook.contentField.placeholder = '';
            }

            hook.contentField.value = value;
            // Some widgets such as text-widget sync the widget content on 'change' event on the input field
            $(hook.contentField).trigger('change');
            if (visualMode) {
                updateMceEditorContent(hook);
            }
        } else {
            // value is ML, fill out values per language
            const contents = mlParseTokens(tokens);
            for (const langField in hook.fields) {
                hook.fields[langField].value = contents[langField];
            }
            hook.lang = lang;
        }
    }
};

export const addDisplayHooks = function (elems) {
    for (let i = 0; i < elems.length; ++i) {
        const e = elems[i];
        addDisplayHook(e);
    }
};

export const addDisplayHookAttrs = function (elem, attrs) {
    for (let j = 0; j < attrs.length; ++j) {
        const a = attrs[j];
        addDisplayHookAttr(elem, a);
    }
};

export const addDisplayHooksAttrs = function (elems, attrs) {
    for (let i = 0; i < elems.length; ++i) {
        const e = elems[i];
        addDisplayHookAttrs(e, attrs);
    }
};

export const addDisplayHooksByClass = function (name, container) {
    const elems = container.getElementsByClassName(name);
    addDisplayHooks(elems);
};

export const addDisplayHooksByTagInClass = function (name, tag, container) {
    const elems = container.getElementsByClassName(name);
    for (let i = 0; i < elems.length; ++i) {
        const elem = elems[i];
        const items = elem.getElementsByTagName(tag);
        addDisplayHooks(items);
    }
};

/**
 * Add custom hooks from configuration.
 */
export const addCustomContentHooks = function () {
    for (let i = 0; i < config.customFields.ids.length; ++i) {
        const fieldName = config.customFields.ids[i];
        addContentHookByIdName(fieldName);
    }
    for (let i = 0; i < config.customFields.classes.length; ++i) {
        const className = config.customFields.classes[i];
        addContentHooksByClass(className);
    }
    addContentHooksTinyMCE();
};

/**
 * Add translatable hooks for fields marked with classes
 * - i18n-multilingual
 * - i18n-multilingual-curly
 * - i18n-multilingual-term
 * - i18n-multilingual-slug
 * - i18n-multilingual-display
 */
const addMultilingualHooks = function () {
    $('.i18n-multilingual').each(function (i, e) {
        addContentHook(e, '[');
    });
    $('.i18n-multilingual-curly').each(function (i, e) {
        addContentHook(e, '{');
    });
    $('.i18n-multilingual-term').each(function (i, e) {
        addContentHook(e, 'term');
    });
    $('.i18n-multilingual-slug').each(function (i, e) {
        addContentHook(e, 'slug');
    });
    $('.i18n-multilingual-display').each(function (i, e) {
        addDisplayHook(e);
    });
};

/**
 * Parse page configuration, loaded in qtranxf_get_admin_page_config_post_type.
 */
const addPageHooks = function (pageConfigForms) {
    for (const formId in pageConfigForms) {
        const formConfig = pageConfigForms[formId];
        let form;
        if (formConfig.form) {
            if (formConfig.form.id) {
                form = document.getElementById(formConfig.form.id);
            } else if (formConfig.form.jquery) {
                form = $(formConfig.form.jquery);
            } else if (formConfig.form.name) {
                const elms = document.getElementsByName(formConfig.form.name);
                if (elms && elms.length) {
                    form = elms[0];
                }
            }
        } else {
            form = document.getElementById(formId);
        }
        if (!form) {
            form = getWrapForm();
            if (!form)
                form = document;
        }
        for (const handle in formConfig.fields) {
            const field = formConfig.fields[handle];
            let containers = [];
            if (field.container_id) {
                const container = document.getElementById(field.container_id);
                if (container)
                    containers.push(container);
            } else if (field.container_jquery) {
                containers = $(field.container_jquery);
            } else if (field.container_class) {
                containers = document.getElementsByClassName(field.container_class);
            } else {// if(form){
                containers.push(form);
            }
            const sep = field.encode;
            switch (sep) {
                case 'none':
                    break;
                case 'display':
                    if (field.jquery) {
                        for (let i = 0; i < containers.length; ++i) {
                            const container = containers[i];
                            const fields = $(container).find(field.jquery);
                            if (field.attrs) {
                                addDisplayHooksAttrs(fields, field.attrs);
                            } else {
                                addDisplayHooks(fields);
                            }
                        }
                    } else {
                        const id = field.id ? field.id : handle;
                        const element = document.getElementById(id);
                        if (field.attrs) {
                            addDisplayHookAttrs(element, field.attrs);
                        } else {
                            addDisplayHook(element);
                        }
                    }
                    break;
                case '[': // b - bracket
                case '<': // c - comment
                case '{': // s - swirly/curly bracket
                case 'byline':
                default:
                    if (field.jquery) {
                        for (let i = 0; i < containers.length; ++i) {
                            const container = containers[i];
                            const fields = $(container).find(field.jquery);
                            addContentHooks(fields, sep, field.name);
                        }
                    } else {
                        const id = field.id ? field.id : handle;
                        addContentHookById(id, sep, field.name);
                    }
                    break;
            }
        }
    }
};

/**
 * Link a TinyMCE editor with translatable content.
 *
 * Usually, this function is called internally for an input field edited by the user.
 * In some cases (e.g. widgets), the editable fields are different from those containing
 * the translatable content. This function allows to attach them to the hook.
 * The single content field initially attached is not updated anymore but the hidden fields
 * storing each language content are still updated.
 * @see attachContentHook
 *
 * @param editor tinyMCE editor, should be initialized for TinyMCE
 * @param contentId optional element ID to override the content hook key (default: input ID)
 * @return hook
 */
export const attachEditorHook = function (editor, contentId) {
    if (!editor.id)
        return null;
    // The MCE editor can be linked to translatable content having a different ID, e.g. for widgets
    if (!contentId) {
        contentId = editor.id;
    }
    const hook = _contentHooks[contentId];
    if (!hook)
        return null;
    // The hook may have been created for a different content field, e.g. for widgets
    // The main content field should always match the editor ID so that its value is synced on tab switch
    if (contentId !== editor.id) {
        hook.contentField = document.getElementById(editor.id);
    }
    if (hook.mce) {
        return hook;  // already initialized for qTranslate
    }
    hook.mce = editor;

    editor.getContentAreaContainer().classList.add('qtranxs-translatable');
    editor.getElement().classList.add('qtranxs-translatable');

    return hook;
}

/**
 * Sets hooks on HTML-loaded TinyMCE editors via tinyMCEPreInit.mceInit.
 */
export const addContentHooksTinyMCE = function () {
    if (!window.tinyMCEPreInit || config.isEditorModeRAW()) {
        return;
    }
    for (const key in _contentHooks) {
        const hook = _contentHooks[key];
        if (hook.contentField.tagName !== 'TEXTAREA' || hook.mce || hook.mceInit || !tinyMCEPreInit.mceInit[key])
            continue;
        hook.mceInit = tinyMCEPreInit.mceInit[key];
        tinyMCEPreInit.mceInit[key].init_instance_callback = function (editor) {
            attachEditorHook(editor);
        }
    }
};

/**
 * Adds more TinyMCE editors, which may have been initialized dynamically.
 */
export const loadAdditionalTinyMceHooks = function () {
    if (window.tinyMCE) {
        tinyMCE.get().forEach(function (editor) {
            attachEditorHook(editor);
        });
    }
};

export const addLanguageSwitchListener = function (func) {
    wp.deprecated('addLanguageSwitchListener', {
        since: '3.16.0',
        version: '4.0.0',
        plugin: 'qTranslate-XT',
        alternative: 'wp.hooks.addAction("qtranx.LanguageSwitch", ...)'
    });
    _onTabSwitchFunctionsAction.push(func);
};

/**
 * The function passed will be called when user presses one of the Language Switching Buttons
 * before the content of all fields hooked is replaced with an appropriate language.
 * Two arguments are supplied:
 * - two-letter language code of currently active language from which the edit language is being switched.
 * - the language code to which the edit language is being switched.
 */
export const addLanguageSwitchBeforeListener = function (func) {
    wp.deprecated('addLanguageSwitchBeforeListener', {
        since: '3.16.0',
        version: '4.0.0',
        plugin: 'qTranslate-XT',
        alternative: 'wp.hooks.addAction("qtranx.LanguageSwitchPre", ...)'
    });
    _onTabSwitchFunctionsSave.push(func);
};

/**
 * Delete handler previously added by function addLanguageSwitchBeforeListener.
 */
export const delLanguageSwitchBeforeListener = function (func) {
    wp.deprecated('delLanguageSwitchBeforeListener', {
        since: '3.16.0',
        version: '4.0.0',
        plugin: 'qTranslate-XT',
        alternative: 'wp.hooks.removeAction("qtranx.LanguageSwitchPre", ...)'
    });
    for (let i = 0; i < _onTabSwitchFunctionsSave.length; ++i) {
        const funcSave = _onTabSwitchFunctionsSave[i];
        if (funcSave !== func)
            continue;
        _onTabSwitchFunctionsSave.splice(i, 1);
        return;
    }
};

/**
 * The function passed will be called when user presses one of the Language Switching Buttons
 * after the content of all fields hooked is replaced with an appropriate language.
 * Two arguments are supplied:
 * - two-letter language code of active language to which the edit language is already switched.
 * - the language code from which the edit language is being switched.
 */
export const addLanguageSwitchAfterListener = function (func) {
    wp.deprecated('addLanguageSwitchAfterListener', {
        since: '3.16.0',
        version: '4.0.0',
        plugin: 'qTranslate-XT',
        alternative: 'wp.hooks.addAction("qtranx.LanguageSwitch", ...)'
    });
    _onTabSwitchFunctionsLoad.push(func);
};

/**
 * Delete handler previously added by function addLanguageSwitchAfterListener.
 */
export const delLanguageSwitchAfterListener = function (func) {
    wp.deprecated('delLanguageSwitchAfterListener', {
        since: '3.16.0',
        version: '4.0.0',
        plugin: 'qTranslate-XT',
        alternative: 'wp.hooks.removeAction("qtranx.LanguageSwitch", ...)'
    });
    for (let i = 0; i < _onTabSwitchFunctionsLoad.length; ++i) {
        const funcLoad = _onTabSwitchFunctionsLoad[i];
        if (funcLoad !== func)
            continue;
        _onTabSwitchFunctionsLoad.splice(i, 1);
        return;
    }
};

export const enableLanguageSwitchingButtons = function (on) {
    const display = on ? 'block' : 'none';
    for (const lang in _tabSwitchElements) {
        const tabSwitches = _tabSwitchElements[lang];
        for (let i = 0; i < tabSwitches.length; ++i) {
            const tabSwitchParent = tabSwitches[i].parentElement;
            tabSwitchParent.style.display = display;
            break;
        }
        break;
    }
};

const getWrapForm = function () {
    const wraps = document.getElementsByClassName('wrap');
    for (let i = 0; i < wraps.length; ++i) {
        const wrap = wraps[i];
        const forms = wrap.getElementsByTagName('form');
        if (forms.length)
            return forms[0];
    }
    const forms = document.getElementsByTagName('form');
    if (forms.length === 1)
        return forms[0];
    for (let i = 0; i < forms.length; ++i) {
        const form = forms[i];
        const wraps = form.getElementsByClassName('wrap');
        if (wraps.length)
            return form;
    }
    return null;
};

export const onLoadLanguage = function (lang, langFrom) {
    wp.deprecated('onLoadLanguage', {
        since: '3.16.0',
        version: '4.0.0',
        plugin: 'qTranslate-XT',
        hint: 'Internal function, should not be used.'
    });
    _onLoadLanguage(lang, langFrom);
};

const _onLoadLanguage = function (lang, langFrom) {
    for (let i = 0; i < _onTabSwitchFunctionsLoad.length; ++i) {
        // TODO: deprecate qtx arg
        _onTabSwitchFunctionsLoad[i].call(qTranx.hooks, lang, langFrom);
    }
}

/**
 * Switch to a new active language.
 *
 * @param lang
 */
export const switchActiveLanguage = function (lang) {
    if (qTranslateConfig.activeLanguage === lang) {
        return;
    }
    if (qTranslateConfig.activeLanguage) {
        /**
         * Action triggered before a language switch.
         *
         * @param langTo language code of currently active language from which the edit language is being switched.
         * @param langFrom the language code to which the edit language is being switched.
         */
        wp.hooks.doAction('qtranx.languageSwitchPre', lang, qTranslateConfig.activeLanguage);
        // TODO: remove deprecated switch handlers
        let ok2switch = true;
        for (let i = 0; i < _onTabSwitchFunctionsSave.length; ++i) {
            // TODO: deprecate qtx arg
            const ok = _onTabSwitchFunctionsSave[i].call(qTranx.hooks, qTranslateConfig.activeLanguage, lang);
            if (ok === false)
                ok2switch = false;
        }
        if (!ok2switch)
            return; // cancel button switch, if one of _onTabSwitchFunctionsSave returned 'false'
        // TODO: substitute cancel logic with a lock design

        const tabSwitches = _tabSwitchElements[qTranslateConfig.activeLanguage];
        for (let i = 0; i < tabSwitches.length; ++i) {
            tabSwitches[i].classList.remove(config.styles.lsb.activeClass);
            $(tabSwitches[i]).find('.button').removeClass('active');
        }
    }

    const langFrom = qTranslateConfig.activeLanguage;
    qTranslateConfig.activeLanguage = lang;
    $('input[name="qtranslate-edit-language"]').val(lang);

    {
        const tabSwitches = _tabSwitchElements[qTranslateConfig.activeLanguage];
        for (let i = 0; i < tabSwitches.length; ++i) {
            tabSwitches[i].classList.add(config.styles.lsb.activeClass);
            $(tabSwitches[i]).find('.button').addClass('active');
        }
    }

    doTabSwitch(lang);
    for (let i = 0; i < _onTabSwitchFunctionsAction.length; ++i) {
        // TODO: deprecate qtx arg
        _onTabSwitchFunctionsAction[i].call(qTranx.hooks, lang, langFrom);
    }

    /**
     * Action triggered after a language switch.
     *
     * @param langTo language code of currently active language from which the edit language is being switched.
     * @param langFrom the language code to which the edit language is being switched.
     */
    wp.hooks.doAction('qtranx.languageSwitch', lang, langFrom);
    _onLoadLanguage(lang, langFrom);
};

export const clickSwitchLanguage = function () {
    wp.deprecated('clickSwitchLanguage', {
        since: '3.16.0',
        version: '4.0.0',
        plugin: 'qTranslate-XT',
        hint: 'Internal callback, should not be used.'
    });
    _clickSwitchLanguage.call(this);
}

const _clickSwitchLanguage = function () {
    const tabSwitch = $(this).hasClass('button') ? this.parentNode : this;
    const lang = tabSwitch.lang;
    if (!lang) {
        log.error('qTranslate-XT: This should not have happened: Please, report this incident to the developers: !lang');
        return;
    }
    if ($('.qtranxs-lang-switch-wrap').hasClass('copying')) {
        _copyContentFrom(lang);
        $(tabSwitch).find('.button').blur();	// remove focus of source language in case of layout with button
        $('.qtranxs-lang-switch-wrap').removeClass('copying');
        $('.qtranxs-lang-copy .button').removeClass('active');
    } else {
        switchActiveLanguage(lang);
    }
};

export const toggleCopyFrom = function () {
    wp.deprecated('toggleCopyFrom', {
        since: '3.16.0',
        version: '4.0.0',
        plugin: 'qTranslate-XT',
        hint: 'Internal callback, should not be used.'
    });
    _toggleCopyFrom.call(this);
}

const _toggleCopyFrom = function () {
    $('.qtranxs-lang-switch-wrap').toggleClass('copying');
    $('.qtranxs-lang-copy .button').toggleClass('active');
    // store or restore original title according to current mode (copy or switch)
    if ($('.qtranxs-lang-switch-wrap').hasClass('copying')) {
        $('.qtranxs-lang-switch').each(function () {
            $(this).attr('orig-title', $(this).attr('title'));
            if ($(this).attr('lang') === qTranslateConfig.activeLanguage)
                $(this).attr('title', config.l10n.CopyFromAlt);
            else
                $(this).attr('title', config.l10n.CopyFrom + ' [:' + $(this).attr('lang') + ']');
        });
    } else {
        $('.qtranxs-lang-switch').each(function () {
            $(this).attr('title', $(this).attr('orig-title'));
        });
    }
};

export const copyContentFrom = function (langFrom) {
    wp.deprecated('copyContentFrom', {
        since: '3.16.0',
        version: '4.0.0',
        plugin: 'qTranslate-XT',
        hint: 'Internal function, should not be used.'
    });
    _copyContentFrom.call(langFrom);
}

const _copyContentFrom = function (langFrom) {
    const lang = qTranslateConfig.activeLanguage;
    let changed = false;
    for (const key in _contentHooks) {
        const hook = _contentHooks[key];
        const visualMode = hook.mce && !hook.mce.hidden;
        let value = visualMode ? hook.mce.getContent() : hook.contentField.value;
        if (value)
            continue; // do not overwrite existent content
        value = hook.fields[langFrom].value;
        if (!value)
            continue;
        hook.contentField.value = value;
        if (visualMode) {
            updateMceEditorContent(hook);
        }
        changed = true;
    }
    if (changed)
        _onLoadLanguage(lang, langFrom);
};

export const createSetOfLSBwith = function (lsb_style_extra_wrap_classes) {
    const langSwitchWrap = domCreateElement('ul', {className: 'qtranxs-lang-switch-wrap ' + lsb_style_extra_wrap_classes});
    for (const lang in config.languages) {
        const lang_conf = config.languages[lang];
        const li_title = config.l10n.ShowIn + lang_conf.admin_name + ' [:' + lang + ']';
        const tabSwitch = domCreateElement('li', {
            lang: lang,
            className: 'qtranxs-lang-switch qtranxs-lang-switch-' + lang,
            title: li_title,
            onclick: _clickSwitchLanguage
        }, langSwitchWrap);
        let tabItem = tabSwitch;
        if (config.styles.lsb.subItem === 'button') {
            // reuse WordPress secondary button
            tabItem = domCreateElement('button', {className: 'button button-secondary', type: 'button'}, tabSwitch);
        }
        domCreateElement('img', {src: config.path.flags + lang_conf.flag}, tabItem);
        domCreateElement('span', {innerHTML: lang_conf.name}, tabItem);
        if (qTranslateConfig.activeLanguage === lang) {
            tabSwitch.classList.add(config.styles.lsb.activeClass);
            $(tabSwitch).find('.button').addClass('active');
        }
        if (!_tabSwitchElements[lang])
            _tabSwitchElements[lang] = [];
        _tabSwitchElements[lang].push(tabSwitch);
    }
    if (!config.styles.lsb.hideCopyContent) {
        const tab = domCreateElement('li', {className: 'qtranxs-lang-copy'}, langSwitchWrap);
        const btn = domCreateElement('button', {
            className: 'button button-secondary',
            type: 'button',
            title: config.l10n.CopyFromAlt,
            onclick: _toggleCopyFrom
        }, tab);
        domCreateElement('span', {innerHTML: config.l10n.CopyFrom}, btn);
    }
    return langSwitchWrap;
};

/**
 * @since 3.4.8
 */
export const createSetOfLSB = function () {
    return createSetOfLSBwith(config.styles.lsb.wrapClass + ' widefat');
};

const setupMetaBoxLSB = function () {
    const metaBox = document.getElementById('qtranxs-meta-box-lsb');
    if (!metaBox)
        return;

    const insideElems = metaBox.getElementsByClassName('inside');
    if (!insideElems.length)
        return; // consistency check in case WP did some changes

    metaBox.classList.add('closed');
    $(metaBox).find('.hndle').remove(); // original h3 element is replaced with span below

    const span = document.createElement('span');
    metaBox.insertBefore(span, insideElems[0]);
    span.classList.add('hndle', 'ui-sortable-handle');

    const langSwitchWrap = createSetOfLSBwith(config.styles.lsb.wrapClass);
    span.appendChild(langSwitchWrap);
    $('#qtranxs-meta-box-lsb .hndle').off('click.postboxes');
};

const setupAnchorsLSB = function () {
    // create sets of LSB
    const anchors = [];
    if (config.pageConfig.anchors) {
        for (const id in config.pageConfig.anchors) {
            const anchor = config.pageConfig.anchors[id];
            const target = document.getElementById(id);
            if (target) {
                anchors.push({target: target, where: anchor.where});
            } else if (anchor.jquery) {
                const targets = $(anchor.jquery);
                for (let i = 0; i < targets.length; ++i) {
                    const target = targets[i];
                    anchors.push({target: target, where: anchor.where});
                }
            }
        }
    }
    if (!anchors.length) {
        const target = getWrapForm();
        if (target)
            anchors.push({target: target, where: 'before'});
    }
    for (let i = 0; i < anchors.length; ++i) {
        const anchor = anchors[i];
        if (!anchor.where || anchor.where.indexOf('before') >= 0) {
            const langSwitchWrap = createSetOfLSB();
            anchor.target.parentNode.insertBefore(langSwitchWrap, anchor.target);
        }
        if (anchor.where && anchor.where.indexOf('after') >= 0) {
            const langSwitchWrap = createSetOfLSB();
            anchor.target.parentNode.insertBefore(langSwitchWrap, anchor.target.nextSibling);
        }
        if (anchor.where && anchor.where.indexOf('first') >= 0) {
            const langSwitchWrap = createSetOfLSB();
            anchor.target.insertBefore(langSwitchWrap, anchor.target.firstChild);
        }
        if (anchor.where && anchor.where.indexOf('last') >= 0) {
            const langSwitchWrap = createSetOfLSB();
            anchor.target.insertBefore(langSwitchWrap, null);
        }
    }
};

/**
 * Setup the language switching buttons, meta box and listeners.
 *
 * Usually, this is called internally after the display and content hooks have been added.
 * However some pages may initialize the hooks later on events (e.g. widget-added).
 * Switching buttons should only be created if there is at least one hook, so this offers
 * the possibility to setup the language switch dynamically later.
 */
export const setupLanguageSwitch = function () {
    if (_languageSwitchInitialized || !config.isEditorModeLSB()) {
        return;
    }
    if (!_displayHookNodes.length && !_displayHookAttrs.length && !Object.keys(_contentHooks).length) {
        return;
    }

    setupMetaBoxLSB();
    setupAnchorsLSB();

    _languageSwitchInitialized = true;
}

/**
 * Initialize the internal state of hooks and switch.
 * - restore the active language
 * - setup hooks for the page config
 * - setup MCE callbacks for editors created with preInit
 *
 * ATTENTION! NOT SUPPORTED IN THE OFFICIAL API.
 * Integration plugins should subscribe for the `qtranx.load` WP action before using ML hooks.
 * This function is only meant for internal usage at loading time and may change.
 * The current behavior may change in next releases. If you really think you need to use this, ask on github.
 */
export const init = function () {
    if (config.isEditorModeLSB()) {
        qTranslateConfig.activeLanguage = getStoredEditLanguage();
        if (!qTranslateConfig.activeLanguage || !config.isLanguageEnabled(qTranslateConfig.activeLanguage)) {
            qTranslateConfig.activeLanguage = config.lang.detected;
            if (config.isLanguageEnabled(qTranslateConfig.activeLanguage)) {
                storeEditLanguage(qTranslateConfig.activeLanguage);
            } else {
                // fallback to single mode
                config.editorMode = config.defs.EditorMode.SINGLE;
            }
        }
    } else {
        qTranslateConfig.activeLanguage = config.lang.detected;
        // no need to store for the current mode, but just in case the LSB are used later
        storeEditLanguage(qTranslateConfig.activeLanguage);
    }

    if (config.pageConfig.forms)
        addPageHooks(config.pageConfig.forms);

    addMultilingualHooks();

    addContentHooksTinyMCE();

    setupLanguageSwitch();
};
