/**
 * Multi-lang hooks for LSB (Language Switching Buttons), ML content, editors and display.
 *
 * Attention! The interface is not fully initialized before the `qtranx.load` action.
 * @see init
 * @see [Integration Guide](https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide)
 */
'use strict';
import {config} from '../config';
import {splitLangs, splitTokens, parseTokens} from '../multi-lang';
import {domCreateElement, getStoredEditLanguage, storeEditLanguage} from '../support';

const $ = jQuery;

/**
 * Internal state of hooks and languageSwitch, not exposed
 */
const _contentHooks = {};
const _displayHookNodes = [];
const _displayHookAttrs = [];
let _languageSwitchInitialized = false;
const _tabSwitchElements = {};  // DOM elements indexed by language.
let _activeLanguage;

// TODO: remove deprecated switch handlers in next major release
const _deprecatedSwitch = {
    actionFuncs: [],
    loadFuncs: [],
    saveFuncs: [],
};

/**
 * Get the currently active language selected in LSB.
 *
 * @returns {string}
 */
export const getActiveLanguage = function () {
    return _activeLanguage;
};

const _setActiveLanguage = function (lang) {
    _activeLanguage = lang;
    window.qTranslateConfig.activeLanguage = lang;  // Deprecated, do not use!
};

/**
 * Check if a content hooks exists.
 *
 * @param {string} id hook
 * @returns {bool} True if the hooks exists, false otherwise.
 */
export const hasContentHook = function (id) {
    return !!_contentHooks[id];
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
 * @param {string} inputField field editable by the user
 * @param {string} contentId optional element ID to override the content hook key (default: input ID)
 * @return {bool} True if the hook was found, false otherwise.
 */
export const attachContentHook = function (inputField, contentId) {
    const hook = _contentHooks[contentId ? contentId : inputField.id];
    if (!hook) {
        return false;
    }
    inputField.classList.add(config.styles.translatable);
    hook.contentField = inputField;
    return true;
}

/**
 * Add a content hook by associating it to a given DOM element (field).
 *
 * @param {DOMElement} inputField a unique DOM element.
 * @param {string} [encode] separator used for serialization '[' by default - TODO clarify supported values
 * @param {string} [fieldName] provide an explicit name in case the input field lacks name prop. Used in POST.
 * @return {boolean} True if the hook was created, false otherwise.
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
            if ($.contains(document, inputField)) {
                return false;
            }
            // otherwise some Java script already removed previously hooked element
            console.warn('No input field with id=', inputField.id);
            removeContentHook(inputField);
            return false;
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

    const inputFieldFormId = $(inputField).attr('form');
    const $form = (inputFieldFormId !== undefined) ? $('#' + inputFieldFormId) : $(inputField).closest('form');
    if (!$form.length) {
        console.error('No form found for translatable field id=', inputField.id);
        return false;
    }
    const form = $form[0];

    const hook = {};
    hook.name = fieldName;
    hook.lang = _activeLanguage;

    _contentHooks[inputField.id] = hook;
    if (!attachContentHook(inputField)) {
        console.error('Failed to attachContentHook', inputField.id);
        removeContentHook(inputField);
        return false;
    }

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

    let contents;
    hook.fields = {};
    if (!config.isEditorModeRAW()) {
        // Most crucial moment when untranslated content is parsed
        contents = splitLangs(inputField.value);
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
                value: _activeLanguage
            }, form, form.firstChild);
        }
    }

    // since 3.2.9.8 - hook.contents -> hook.fields
    // since 3.3.8.7 - slug & term
    switch (encode) {
        case 'slug':
        case 'term': {
            if (config.isEditorModeRAW())
                contents = splitLangs(inputField.value);
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

    return true;
};

/**
 * Add multiple content hooks by associating them to given DOM elements (fields).
 *
 * @see addContentHook
 * @param {DOMElement} inputField a unique DOM element.
 * @param {string} [encode] separator used for serialization '[' by default
 * @param {string} [fieldName] provide an explicit name in case the fields lack name prop. Used in POST.
 */
export const addContentHooks = function (fields, sep, fieldName) {
    for (let i = 0; i < fields.length; ++i) {
        const field = fields[i];
        addContentHook(field, sep, fieldName);
    }
};

const _addContentHooksByClassName = function (name, container, sep) {
    if (!container)
        container = document;
    const fields = container.getElementsByClassName(name);
    addContentHooks(fields, sep);
};

export const addContentHooksByClass = function (name, container) {
    wp.deprecated('addContentHooksByClass', {
        since: '3.16.0',
        version: '4.0.0',
        plugin: 'qTranslate-XT',
        alternative: 'addContentHooks(container.getElementsByClassName(nameWithoutSeparator),container,sep)',
        hint: 'Only meant for custom fields (separator extracted from name), now handled internally.'
    });
    // ATTENTION we don't support this separator extraction in addContentHooks!
    let sep = '[';
    if (name.indexOf('<') === 0 || name.indexOf('[') === 0) {
        sep = name.substring(0, 1);
        name = name.substring(1);
    }
    _addContentHooksByClassName(name, container, sep);
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
        editor.getContentAreaContainer().classList.remove(config.styles.translatable);
        editor.getElement().classList.remove(config.styles.translatable);
    }
    // The current content field may not be the same as the input field, in case it was re-attached (e.g. widgets)
    hook.contentField.classList.remove(config.styles.translatable);
    delete _contentHooks[inputField.id];
    inputField.classList.remove(config.styles.translatable);
    return true;
};

export const refreshContentHook = function (inputField) {
    removeContentHook(inputField);
    return addContentHook(inputField);
};

const _getDisplayContentDefaultValue = function (contents) {
    if (contents[config.page.detectedLang])
        return '(' + config.page.detectedLang + ') ' + contents[config.page.detectedLang];
    if (contents[config.lang.default])
        return '(' + config.lang.default + ') ' + contents[config.lang.default];
    for (const lang in contents) {
        if (!contents[lang])
            continue;
        return '(' + lang + ') ' + contents[lang];
    }
    return '';
};

const _completeDisplayContent = function (contents) {
    let default_value = null;
    for (const lang in contents) {
        if (contents[lang])
            continue;
        if (!default_value)
            default_value = _getDisplayContentDefaultValue(contents);
        contents[lang] = default_value;
    }
};

const _addDisplayHookNode = function (node) {
    if (!node.nodeValue)
        return 0;
    const tokens = splitTokens(node.nodeValue);
    if (!tokens || !tokens.length || tokens.length === 1)
        return 0;
    const hook = {};
    hook.nd = node;
    hook.contents = parseTokens(tokens);
    _completeDisplayContent(hook.contents);
    node.nodeValue = hook.contents[_activeLanguage];
    _displayHookNodes.push(hook);
    return 1;
};

const _addDisplayHookAttr = function (node, attr) {
    if (!node.hasAttribute(attr)) return 0;
    const value = node.getAttribute(attr);
    const tokens = splitTokens(value);
    if (!tokens || !tokens.length || tokens.length === 1)
        return 0;
    const hook = {};
    hook.nd = node;
    hook.attr = attr;
    hook.contents = parseTokens(tokens);
    _completeDisplayContent(hook.contents);
    node.setAttribute(attr, hook.contents[_activeLanguage]);
    _displayHookAttrs.push(hook);
    return 1;
};

/**
 * Add a display hook (read-only) by associating it to a given DOM element.
 *
 * @param {DOMElement} elem a unique DOM element.
 */
export const addDisplayHook = function (elem) {
    if (!elem || !elem.tagName)
        return 0;
    switch (elem.tagName) {
        case 'TEXTAREA':
            return 0;
        case 'INPUT':
            if (elem.type === 'submit' && elem.value) {
                return _addDisplayHookAttr(elem, 'value');
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
                    nbHooks += _addDisplayHookNode(node);
                    break;
                default:
                    break;
            }
        }
    }
    return nbHooks;
};

const _updateMceEditorContent = function (hook) {
    let text = hook.contentField.value;
    if (hook.mce.settings.wpautop && window.switchEditors) {
        text = window.switchEditors.wpautop(text);
    }
    hook.mce.setContent(text);
};

const _doTabSwitch = function (lang) {
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
        const tokens = splitTokens(text);
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
                _updateMceEditorContent(hook);
            }
        } else {
            // value is ML, fill out values per language
            const contents = parseTokens(tokens);
            for (const langField in hook.fields) {
                hook.fields[langField].value = contents[langField];
            }
            hook.lang = lang;
        }
    }
};

/**
 * Add multiple display hooks by associating them to a given DOM elements.
 *
 * @see addDisplayHook
 * @param {DOMElement[]} elems array of DOM elements.
 */
export const addDisplayHooks = function (elems) {
    for (let i = 0; i < elems.length; ++i) {
        const e = elems[i];
        addDisplayHook(e);
    }
};

export const addDisplayHookAttrs = function (elem, attrs) {
    for (let j = 0; j < attrs.length; ++j) {
        const a = attrs[j];
        _addDisplayHookAttr(elem, a);
    }
};

export const addDisplayHooksAttrs = function (elems, attrs) {
    for (let i = 0; i < elems.length; ++i) {
        const e = elems[i];
        addDisplayHookAttrs(e, attrs);
    }
};

/**
 * Add custom hooks from configuration.
 */
export const _addCustomContentHooks = function (i18nCustomFields) {
    // Special handling for custom fields that may contain the separator in the first character
    // TODO This format is quite convoluted, not sure we should still support this. Anyway broken because HTML encoded...
    const _parseSeparatorFromName = function (name) {
        const sepFound = (name.indexOf('<') === 0 || name.indexOf('[') === 0);
        return {
            sep: sepFound ? name.substring(0, 1) : '[',
            name: sepFound ? name.substring(1) : name,
        };
    }
    for (const customId of (i18nCustomFields?.ids ?? [])) {
        const parsed = _parseSeparatorFromName(customId);
        addContentHook(document.getElementById(parsed.name), parsed.sep); // unique
    }
    for (const customClass of (i18nCustomFields?.classes ?? [])) {
        const parsed = _parseSeparatorFromName(customClass);
        addContentHooks(document.getElementsByClassName(parsed.name), parsed.sep); // multiple
    }
};

export const addCustomContentHooks = function () {
    wp.deprecated('addCustomContentHooks', {
        since: '3.16.0',
        version: '4.0.0',
        plugin: 'qTranslate-XT',
        hint: 'Custom fields are handled internally.'
    });
    _addCustomContentHooks(config.page.i18n._custom);
    addContentHooksTinyMCE();
};

/**
 * Add translatable hooks for fields marked with predefined classes.
 */
const _addMultilingualHooks = function () {
    addContentHooks($('.i18n-multilingual'), '[');
    addContentHooks($('.i18n-multilingual-curly'), '{');
    addContentHooks($('.i18n-multilingual-term'), 'term');
    addContentHooks($('.i18n-multilingual-slug'), 'slug');
    addDisplayHooks($('.i18n-multilingual-display'));
};

/**
 * Parse page i18n form configuration, loaded in qtranxf_get_admin_page_config_post_type.
 * @see https://github.com/qtranslate/qtranslate-xt/wiki/JSON-Configuration
 */
const _addPageHooks = function (i18nPageConfigForms) {
    for (const formId in (i18nPageConfigForms ?? [])) {
        const formConfig = i18nPageConfigForms[formId];
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
            form = _getWrapForm();
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
                        addContentHook(document.getElementById(id), sep, field.name);
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

    editor.getContentAreaContainer().classList.add(config.styles.translatable);
    editor.getElement().classList.add(config.styles.translatable);

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
    _deprecatedSwitch.actionFuncs.push(func);
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
    _deprecatedSwitch.saveFuncs.push(func);
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
    for (let i = 0; i < _deprecatedSwitch.saveFuncs.length; ++i) {
        const funcSave = _deprecatedSwitch.saveFuncs[i];
        if (funcSave !== func)
            continue;
        _deprecatedSwitch.saveFuncs.splice(i, 1);
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
    _deprecatedSwitch.loadFuncs.push(func);
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
    for (let i = 0; i < _deprecatedSwitch.loadFuncs.length; ++i) {
        const funcLoad = _deprecatedSwitch.loadFuncs[i];
        if (funcLoad !== func)
            continue;
        _deprecatedSwitch.loadFuncs.splice(i, 1);
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

const _getWrapForm = function () {
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
    for (let i = 0; i < _deprecatedSwitch.loadFuncs.length; ++i) {
        // TODO: deprecate qtx arg
        _deprecatedSwitch.loadFuncs[i].call(qTranx.hooks, lang, langFrom);
    }
}

/**
 * Switch to a new active language, triggering a `qtranx.languageSwitch` action.
 *
 * @fires qtranx.languageSwitchPre
 * @fires qtranx.languageSwitch
 * @param {string} lang the requested language
 * @return {boolean} true if the language was switched, false otherwise
 */
export const switchActiveLanguage = function (lang) {
    if (_activeLanguage === lang || !config.isLanguageEnabled(lang)) {
        return false;
    }
    if (_activeLanguage) {
        /**
         * Action triggered before a language switch.
         * @event qtranx.languageSwitchPre
         * @param langTo language code of currently active language from which the edit language is being switched.
         * @param langFrom the language code to which the edit language is being switched.
         */
        wp.hooks.doAction('qtranx.languageSwitchPre', lang, _activeLanguage);
        // TODO: remove deprecated switch handlers
        let ok2switch = true;
        for (let i = 0; i < _deprecatedSwitch.saveFuncs.length; ++i) {
            // TODO: deprecate qtx arg
            const ok = _deprecatedSwitch.saveFuncs[i].call(qTranx.hooks, _activeLanguage, lang);
            if (ok === false)
                ok2switch = false;
        }
        if (!ok2switch)
            return false; // cancel button switch, if one of _deprecatedSwitch.saveFuncs returned 'false'
        // TODO: substitute cancel logic with a lock design

        const tabSwitches = _tabSwitchElements[_activeLanguage];
        for (let i = 0; i < tabSwitches.length; ++i) {
            tabSwitches[i].classList.remove(config.styles.lsb.activeClass);
            $(tabSwitches[i]).find('.button').removeClass('active');
        }
    }

    const langFrom = _activeLanguage;
    _setActiveLanguage(lang);
    $('input[name="qtranslate-edit-language"]').val(lang);

    {
        const tabSwitches = _tabSwitchElements[_activeLanguage];
        for (let i = 0; i < tabSwitches.length; ++i) {
            tabSwitches[i].classList.add(config.styles.lsb.activeClass);
            $(tabSwitches[i]).find('.button').addClass('active');
        }
    }

    _doTabSwitch(lang);
    for (let i = 0; i < _deprecatedSwitch.actionFuncs.length; ++i) {
        // TODO: deprecate qtx arg
        _deprecatedSwitch.actionFuncs[i].call(qTranx.hooks, lang, langFrom);
    }

    /**
     * Action triggered after a language switch.
     * @event qtranx.languageSwitch
     * @param langTo language code of currently active language from which the edit language is being switched.
     * @param langFrom the language code to which the edit language is being switched.
     */
    wp.hooks.doAction('qtranx.languageSwitch', lang, langFrom);
    _onLoadLanguage(lang, langFrom);
    return true;
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
            if ($(this).attr('lang') === _activeLanguage)
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
    const lang = _activeLanguage;
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
            _updateMceEditorContent(hook);
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
        if (_activeLanguage === lang) {
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

const _setupMetaBoxLSB = function () {
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

const _setupAnchorsLSB = function (i18nPageAnchors) {
    // create sets of LSB
    const anchors = [];
    if (i18nPageAnchors) {
        for (const id in i18nPageAnchors) {
            const anchor = i18nPageAnchors[id];
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
        const target = _getWrapForm();
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
    _setupMetaBoxLSB();
    _setupAnchorsLSB(config.page.i18n.anchors);
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
        _setActiveLanguage(getStoredEditLanguage());
        if (!_activeLanguage || !config.isLanguageEnabled(_activeLanguage)) {
            _setActiveLanguage(config.page.detectedLang);
            if (config.isLanguageEnabled(_activeLanguage)) {
                storeEditLanguage(_activeLanguage);
            } else {
                // fallback to single mode
                config.editorMode = config.enum.EditorMode.SINGLE;
            }
        }
    } else {
        _setActiveLanguage(config.page.detectedLang);
        // no need to store for the current mode, but just in case the LSB are used later
        storeEditLanguage(_activeLanguage);
    }
    _addPageHooks(config.page.i18n.forms);
    _addCustomContentHooks(config.page.i18n._custom);
    _addMultilingualHooks();
    addContentHooksTinyMCE();
    setupLanguageSwitch();
};
