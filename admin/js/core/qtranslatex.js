/**
 * Main qTranslateX class for LSB and content hooks
 *
 * Search for 'Designed as interface for other plugin integration' in comments to functions
 * to find out which functions are safe to use in the 3rd-party integration.
 * Avoid accessing internal variables directly, as they are subject to be re-designed at any time.
 * Single global variable 'qTranslateConfig' is an entry point to the interface.
 * - qTranslateConfig.qtx - is a shorthand reference to the only global object of type 'qTranslateX'.
 * - qTranslateConfig.js - is a place where custom Java script functions are stored, if needed.
 * Read Integration Guide: https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide for more information.
 */
import {qtranxj_ce} from './dom';
import {qtranxj_get_split_blocks, qtranxj_split, qtranxj_split_blocks} from './qblocks';
import {getStoredEditLanguage, storeEditLanguage} from './store';

const $ = jQuery;

const qTranslateConfig = window.qTranslateConfig;

const qTranslateX = function (pg) {
    const qtx = this;

    /**
     * Designed as interface for other plugin integration. The documentation is available at
     * https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide
     * return array keyed by two-letter language code. Example of usage:
     * const langs = getLanguages();
     * for(const lang_code in langs){
     *  const lang_conf = langs[lang_code];
     *  // variables available:
     *  //lang_conf.name //name of language in native language
     *  //lang_conf.admin_name //in the admin language chosen
     *  //lang_conf.flag
     *  //lang_conf.locale
     *  // and may be more properties later
     * }
     * @since 3.3
     */
    this.getLanguages = function () {
        return qTranslateConfig.language_config;
    };

    /**
     * Designed as interface for other plugin integration. The documentation is available at
     * https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide
     * return URL to folder with flag images.
     */
    this.getFlagLocation = function () {
        return qTranslateConfig.flag_location;
    };

    /**
     * Designed as interface for other plugin integration. The documentation is available at
     * https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide
     * return true if 'lang' is in the hash of enabled languages.
     * This function maybe needed, as function qtranxj_split may return languages,
     * which are not enabled, in case they were previously enabled and had some data.
     * Such data is preserved and re-saved until user deletes it manually.
     */
    this.isLanguageEnabled = function (lang) {
        return !!qTranslateConfig.language_config[lang];
    };

    if (qTranslateConfig.LSB) {
        qTranslateConfig.activeLanguage = getStoredEditLanguage();
        if (!qTranslateConfig.activeLanguage || !this.isLanguageEnabled(qTranslateConfig.activeLanguage)) {
            qTranslateConfig.activeLanguage = qTranslateConfig.language;
            if (this.isLanguageEnabled(qTranslateConfig.activeLanguage)) {
                storeEditLanguage(qTranslateConfig.activeLanguage);
            } else {
                // fallback to single mode
                qTranslateConfig.LSB = false;
            }
        }
    } else {
        qTranslateConfig.activeLanguage = qTranslateConfig.language;
        // no need to store for the current mode, but just in case the LSB are used later
        storeEditLanguage(qTranslateConfig.activeLanguage);
    }

    /**
     * Designed as interface for other plugin integration. The documentation is available at
     * https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide
     *
     * @since 3.3
     */
    this.getActiveLanguage = function () {
        return qTranslateConfig.activeLanguage;
    };

    const contentHooks = {};

    const updateFusedValueH = function (id, value) {
        if (qTranslateConfig.RAW)
            return;
        const h = contentHooks[id];
        h.fields[h.lang].value = value.trim();
    };

    /**
     * Designed as interface for other plugin integration. The documentation is available at
     * https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide
     *
     * @since 3.3.4
     */
    this.hasContentHook = function (id) {
        return contentHooks[id];
    };

    /**
     * Designed as interface for other plugin integration. The documentation is available at
     * https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide
     *
     * @since 3.3.2
     */
    this.addContentHook = function (inpField, encode, field_name) {
        if (!inpField) return false;
        switch (inpField.tagName) {
            case 'TEXTAREA':
                break;
            case 'INPUT':
                // reject the types which cannot be multilingual
                switch (inpField.type) {
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

        if (!field_name) {
            if (!inpField.name) return false;
            field_name = inpField.name;
        }
        if (inpField.id) {
            if (contentHooks[inpField.id]) {
                if ($.contains(document, inpField))
                    return contentHooks[inpField.id];
                // otherwise some Java script already removed previously hooked element
                qtx.removeContentHook(inpField);
            }
        } else if (!contentHooks[field_name]) {
            inpField.id = field_name;
        } else {
            let idx = 0;
            do {
                ++idx;
                inpField.id = field_name + idx;
            } while (contentHooks[inpField.id]);
        }

        /**
         * Highlighting the translatable fields
         * @since 3.2-b3
         */
        inpField.className += ' qtranxs-translatable';

        const h = contentHooks[inpField.id] = {};
        h.name = field_name;
        h.contentField = inpField;
        h.lang = qTranslateConfig.activeLanguage;

        let qtx_prefix;
        if (encode) {
            switch (encode) {
                case 'slug':
                    qtx_prefix = 'qtranslate-slugs[';
                    break;
                case 'term':
                    qtx_prefix = 'qtranslate-terms[';
                    break;
                default:
                    qtx_prefix = 'qtranslate-fields[';
                    break;
            }
        } else {
            // since 3.1 we get rid of <--:--> encoding
            encode = '[';
            qtx_prefix = 'qtranslate-fields[';
        }

        h.encode = encode;

        let bfnm, sfnm;
        const p = h.name.indexOf('[');
        if (p < 0) {
            bfnm = qtx_prefix + h.name + ']';
        } else {
            bfnm = qtx_prefix + h.name.substring(0, p) + ']';
            if (h.name.lastIndexOf('[]') < 0) {
                bfnm += h.name.substring(p);
            } else {
                const len = h.name.length - 2;
                if (len > p)
                    bfnm += h.name.substring(p, len);
                sfnm = '[]';
            }
        }

        let contents;

        h.fields = {};
        if (!qTranslateConfig.RAW) {
            // Most crucial moment when untranslated content is parsed
            contents = qtranxj_split(inpField.value);
            // Substitute the current ML content with translated content for the current language
            inpField.value = contents[h.lang];
            // Insert translated content for each language before the current field
            for (const lang in contents) {
                const text = contents[lang];
                let fnm = bfnm + '[' + lang + ']';
                if (sfnm)
                    fnm += sfnm;
                const f = qtranxj_ce('input', {name: fnm, type: 'hidden', className: 'hidden', value: text});
                h.fields[lang] = f;
                inpField.parentNode.insertBefore(f, inpField);
            }

            // insert a hidden element in the form so that the edit language is sent to the server
            const $form = $(inpField).closest('form');
            if ($form.length) {
                const $hidden = $form.find('input[name="qtranslate-edit-language"]');
                if (!$hidden.length) {
                    qtranxj_ce('input', {
                        type: 'hidden',
                        name: 'qtranslate-edit-language',
                        value: qTranslateConfig.activeLanguage
                    }, $form[0], true);
                }
            } else {
                console.error('No form found for translatable field id=', inpField.id);
            }
        }

        // since 3.2.9.8 - h.contents -> h.fields
        // since 3.3.8.7 - slug & term
        switch (encode) {
            case 'slug':
            case 'term': {
                if (qTranslateConfig.RAW)
                    contents = qtranxj_split(inpField.value);
                h.sepfield = qtranxj_ce('input', {
                    name: bfnm + '[qtranslate-original-value]',
                    type: 'hidden',
                    className: 'hidden',
                    value: contents[qTranslateConfig.default_language]
                });
            }
                break;
            default: {
                if (!qTranslateConfig.RAW) {
                    h.sepfield = qtranxj_ce('input', {
                        name: bfnm + '[qtranslate-separator]',
                        type: 'hidden',
                        className: 'hidden',
                        value: encode
                    });
                }
            }
                break;
        }

        if (h.sepfield)
            inpField.parentNode.insertBefore(h.sepfield, inpField);

        return h;
    };
    this.addContentHookC = function (inpField) {
        return qtx.addContentHook(inpField, '['); // TODO shouldn't it be '<' ?!
    };
    this.addContentHookB = function (inpField) {
        return qtx.addContentHook(inpField, '[');
    };

    this.addContentHookById = function (id, sep, nm) {
        return qtx.addContentHook(document.getElementById(id), sep, nm);
    };
    this.addContentHookByIdName = function (nm) {
        let sep;
        switch (nm[0]) {
            case '<':
            case '[':
                sep = nm.substring(0, 1);
                nm = nm.substring(1);
                break;
            default:
                break;
        }
        return qtx.addContentHookById(nm, sep);
    };
    this.addContentHookByIdC = function (id) {
        return qtx.addContentHookById(id, '['); // TODO shouldn't it be '<' ?!
    };
    this.addContentHookByIdB = function (id) {
        return qtx.addContentHookById(id, '[');
    };

    /**
     * Designed as interface for other plugin integration. The documentation is available at
     * https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide
     *
     * @since 3.1-b2
     */
    this.addContentHooks = function (fields, sep, field_name) {
        for (let i = 0; i < fields.length; ++i) {
            const field = fields[i];
            qtx.addContentHook(field, sep, field_name);
        }
    };

    const addContentHooksByClassName = function (nm, container, sep) {
        if (!container)
            container = document;
        const fields = container.getElementsByClassName(nm);
        qtx.addContentHooks(fields, sep);
    };

    this.addContentHooksByClass = function (nm, container) {
        let sep;
        if (nm.indexOf('<') === 0 || nm.indexOf('[') === 0) {
            sep = nm.substring(0, 1);
            nm = nm.substring(1);
        }
        addContentHooksByClassName(nm, container, sep);
    };

    /**
     * Designed as interface for other plugin integration. The documentation is available at
     * https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide
     *
     * @since 3.3.2
     */
    this.addContentHooksByTagInClass = function (nm, tag, container) {
        const elems = container.getElementsByClassName(nm);
        for (let i = 0; i < elems.length; ++i) {
            const elem = elems[i];
            const items = elem.getElementsByTagName(tag);
            qtx.addContentHooks(items);
        }
    };

    const removeContentHookH = function (h) {
        if (!h)
            return false;
        if (h.sepfield)
            $(h.sepfield).remove();
        const contents = {};
        for (const lang in h.fields) {
            const f = h.fields[lang];
            contents[lang] = f.value;
            $(f).remove();
        }
        $(h.contentField).removeClass('qtranxs-translatable');
        delete contentHooks[h.contentField.id];
        return contents;
    };

    /**
     * Designed as interface for other plugin integration. The documentation is available at
     * https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide
     *
     * @since 3.3
     */
    this.removeContentHook = function (inpField) {
        if (!inpField || !inpField.id || !contentHooks[inpField.id])
            return false;
        const h = contentHooks[inpField.id];
        removeContentHookH(h);
        // @since 3.2.9.8 - h.contents -> h.fields
        $(inpField).removeClass('qtranxs-translatable');
        return true;
    };

    /**
     * Designed as interface for other plugin integration. The documentation is available at
     * https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide
     * Re-create a hook, after a piece of HTML is dynamically replaced with a custom Java script.
     */
    this.refreshContentHook = function (inpField) {
        if (!inpField || !inpField.id)
            return false;
        const h = contentHooks[inpField.id];
        if (h)
            removeContentHookH(h);
        return qtx.addContentHook(inpField);
    };

    /**
     * @since 3.4.6.9
     */
    const getDisplayContentDefaultValue = function (contents) {
        if (contents[qTranslateConfig.language])
            return '(' + qTranslateConfig.language + ') ' + contents[qTranslateConfig.language];
        if (contents[qTranslateConfig.default_language])
            return '(' + qTranslateConfig.default_language + ') ' + contents[qTranslateConfig.default_language];
        for (const lang in contents) {
            if (!contents[lang])
                continue;
            return '(' + lang + ') ' + contents[lang];
        }
        return '';
    };

    /**
     * @since 3.4.6.9
     */
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

    /**
     * @since 3.2.7
     */
    const displayHookNodes = [];
    const addDisplayHookNode = function (nd) {
        if (!nd.nodeValue)
            return 0;
        const blocks = qtranxj_get_split_blocks(nd.nodeValue);
        if (!blocks || !blocks.length || blocks.length === 1)
            return 0;
        const h = {};
        h.nd = nd;
        h.contents = qtranxj_split_blocks(blocks);
        completeDisplayContent(h.contents);
        nd.nodeValue = h.contents[qTranslateConfig.activeLanguage];
        displayHookNodes.push(h);
        return 1;
    };

    /**
     * @since 3.2.7
     */
    const displayHookAttrs = [];
    const addDisplayHookAttr = function (nd, attr) {
        if (!nd.hasAttribute(attr)) return 0;
        const value = nd.getAttribute(attr);
        const blocks = qtranxj_get_split_blocks(value);
        if (!blocks || !blocks.length || blocks.length === 1)
            return 0;
        const h = {};
        h.nd = nd;
        h.attr = attr;
        h.contents = qtranxj_split_blocks(blocks);
        completeDisplayContent(h.contents);
        nd.setAttribute(attr, h.contents[qTranslateConfig.activeLanguage]);
        displayHookAttrs.push(h);
        return 1;
    };

    /**
     * Designed as interface for other plugin integration. The documentation is available at
     * https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide
     *
     * @since 3.2.7 switched to use of nodeValue instead of innerHTML.
     */
    this.addDisplayHook = function (elem) {
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
        //co('addDisplayHook: elem: ',elem);
        let cnt = 0;
        if (elem.childNodes && elem.childNodes.length) {
            for (let i = 0; i < elem.childNodes.length; ++i) {
                const nd = elem.childNodes[i];
                switch (nd.nodeType) {
                    // http://www.w3.org/TR/REC-DOM-Level-1/level-one-core.html#ID-1950641247
                    case 1: // ELEMENT_NODE
                        cnt += qtx.addDisplayHook(nd);
                        break;
                    case 2: // ATTRIBUTE_NODE
                    case 3: // TEXT_NODE
                        cnt += addDisplayHookNode(nd);
                        break;
                    default:
                        break;
                }
            }
        }
        return cnt;
    };

    /**
     * Designed as interface for other plugin integration. The documentation is available at
     * https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide
     *
     * @since 3.0
     */
    this.addDisplayHookById = function (id) {
        return qtx.addDisplayHook(document.getElementById(id));
    };

    const updateTinyMCE = function (h) {
        let text = h.contentField.value;
        if (h.wpautop && window.switchEditors) {
            text = window.switchEditors.wpautop(text);
        }
        h.mce.setContent(text, {format: 'html'});
    };

    const onTabSwitch = function (lang) {
        storeEditLanguage(lang);

        for (let i = displayHookNodes.length; --i >= 0;) {
            const h = displayHookNodes[i];
            if (h.nd.parentNode) {
                h.nd.nodeValue = h.contents[lang]; // IE gets upset here if node was removed
            } else {
                displayHookNodes.splice(i, 1); // node was removed by some other function
            }
        }
        for (let i = displayHookAttrs.length; --i >= 0;) {
            const h = displayHookAttrs[i];
            if (h.nd.parentNode) {
                h.nd.setAttribute(h.attr, h.contents[lang]);
            } else {
                displayHookAttrs.splice(i, 1); // node was removed by some other function
            }
        }
        if (qTranslateConfig.RAW)
            return;
        for (const key in contentHooks) {
            const h = contentHooks[key];
            const mce = h.mce && !h.mce.hidden;
            if (mce) {
                h.mce.save({format: 'html'});
            }

            const text = h.contentField.value.trim();
            const blocks = qtranxj_get_split_blocks(text);
            if (!blocks || blocks.length <= 1) {
                // value is not ML, switch it to other language
                h.fields[h.lang].value = text;
                h.lang = lang;
                const value = h.fields[h.lang].value;
                if (h.contentField.placeholder && value !== '') {
                    // since 3.2.7
                    h.contentField.placeholder = '';
                }
                h.contentField.value = value;
                if (mce) {
                    updateTinyMCE(h);
                }
            } else {
                // value is ML, fill out values per language
                const contents = qtranxj_split_blocks(blocks);
                for (const lng in h.fields) {
                    h.fields[lng].value = contents[lng];
                }
                h.lang = lang;
            }
        }
    };

    /**
     * Designed as interface for other plugin integration. The documentation is available at
     * https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide
     *
     * @since 3.0
     */
    this.addDisplayHooks = function (elems) {
        for (let i = 0; i < elems.length; ++i) {
            const e = elems[i];
            qtx.addDisplayHook(e);
        }
    };

    /**
     * Designed as interface for other plugin integration. The documentation is available at
     * https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide
     *
     * @since 3.4.7
     */
    this.addDisplayHookAttrs = function (elem, attrs) {
        for (let j = 0; j < attrs.length; ++j) {
            const a = attrs[j];
            addDisplayHookAttr(elem, a);
        }
    };

    /**
     * Designed as interface for other plugin integration. The documentation is available at
     * https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide
     *
     * @since 3.4.7
     */
    this.addDisplayHooksAttrs = function (elems, attrs) {
        for (let i = 0; i < elems.length; ++i) {
            const e = elems[i];
            qtx.addDisplayHookAttrs(e, attrs);
        }
    };

    /**
     * Designed as interface for other plugin integration. The documentation is available at
     * https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide
     *
     * @since 3.3
     */
    this.addDisplayHooksByClass = function (nm, container) {
        const elems = container.getElementsByClassName(nm);
        qtx.addDisplayHooks(elems);
    };

    /**
     * Designed as interface for other plugin integration. The documentation is available at
     * https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide
     *
     * @since 3.3
     */
    this.addDisplayHooksByTagInClass = function (nm, tag, container) {
        const elems = container.getElementsByClassName(nm);
        for (let i = 0; i < elems.length; ++i) {
            const elem = elems[i];
            const items = elem.getElementsByTagName(tag);
            qtx.addDisplayHooks(items);
        }
    };


    /**
     * adds custom hooks from configuration
     * @since 3.1-b2 - renamed to addCustomContentHooks, since addContentHooks used in qTranslateConfig.js
     * @since 3.0 - addContentHooks
     */
    this.addCustomContentHooks = function () {
        for (let i = 0; i < qTranslateConfig.custom_fields.length; ++i) {
            const fieldName = qTranslateConfig.custom_fields[i];
            qtx.addContentHookByIdName(fieldName);
        }
        for (let i = 0; i < qTranslateConfig.custom_field_classes.length; ++i) {
            const className = qTranslateConfig.custom_field_classes[i];
            qtx.addContentHooksByClass(className);
        }
        if (qTranslateConfig.LSB)
            qtx.addContentHooksTinyMCE();
    };

    /**
     * adds translatable hooks for fields marked with classes
     * i18n-multilingual
     * i18n-multilingual-curly
     * i18n-multilingual-term
     * i18n-multilingual-slug
     * i18n-multilingual-display
     * @since 3.4
     */
    const addMultilingualHooks = function () {
        $('.i18n-multilingual').each(function (i, e) {
            qtx.addContentHook(e, '[');
        });
        $('.i18n-multilingual-curly').each(function (i, e) {
            qtx.addContentHook(e, '{');
        });
        $('.i18n-multilingual-term').each(function (i, e) {
            qtx.addContentHook(e, 'term');
        });
        $('.i18n-multilingual-slug').each(function (i, e) {
            qtx.addContentHook(e, 'slug');
        });
        $('.i18n-multilingual-display').each(function (i, e) {
            qtx.addDisplayHook(e);
        });
    };

    /**
     * Parses page configuration, loaded in qtranxf_get_admin_page_config_post_type.
     * @since 3.1-b2
     */
    const addPageHooks = function (page_config_forms) {
        for (const form_id in page_config_forms) {
            const frm = page_config_forms[form_id];
            let form;
            if (frm.form) {
                if (frm.form.id) {
                    form = document.getElementById(frm.form.id);
                } else if (frm.form.jquery) {
                    form = $(frm.form.jquery);
                } else if (frm.form.name) {
                    const elms = document.getElementsByName(frm.form.name);
                    if (elms && elms.length) {
                        form = elms[0];
                    }
                }
            } else {
                form = document.getElementById(form_id);
            }
            if (!form) {
                form = getWrapForm();
                if (!form)
                    form = document;
            }
            for (const handle in frm.fields) {
                const fld = frm.fields[handle];
                let containers = [];
                if (fld.container_id) {
                    const container = document.getElementById(fld.container_id);
                    if (container)
                        containers.push(container);
                } else if (fld.container_jquery) {
                    containers = $(fld.container_jquery);
                } else if (fld.container_class) {
                    containers = document.getElementsByClassName(fld.container_class);
                } else {// if(form){
                    containers.push(form);
                }
                const sep = fld.encode;
                switch (sep) {
                    case 'none':
                        continue;
                    case 'display':
                        if (fld.jquery) {
                            for (let i = 0; i < containers.length; ++i) {
                                const container = containers[i];
                                const fields = $(container).find(fld.jquery);
                                if (fld.attrs) {
                                    qtx.addDisplayHooksAttrs(fields, fld.attrs);
                                } else {
                                    qtx.addDisplayHooks(fields);
                                }
                            }
                        } else {
                            const id = fld.id ? fld.id : handle;
                            //co('addPageHooks:display: id=',id);
                            const field = document.getElementById(id);
                            if (fld.attrs) {
                                qtx.addDisplayHookAttrs(field, fld.attrs);
                            } else {
                                qtx.addDisplayHook(field);
                            }
                        }
                        break;
                    case '[': // b - bracket
                    case '<': // c - comment
                    case '{': // s - swirly/curly bracket
                    case 'byline':
                    default:
                        if (fld.jquery) {
                            for (let i = 0; i < containers.length; ++i) {
                                const container = containers[i];
                                const fields = $(container).find(fld.jquery);
                                qtx.addContentHooks(fields, sep, fld.name);
                            }
                        } else {
                            const id = fld.id ? fld.id : handle;
                            qtx.addContentHookById(id, sep, fld.name);
                        }
                        break;
                }
            }
        }
    };

    /** Link a TinyMCE editor with translatable content. The editor should be initialized for TinyMCE. */
    const setEditorHooks = function (ed) {
        const id = ed.id;
        if (!id)
            return;
        const h = contentHooks[id];
        if (!h)
            return;
        if (h.mce) {
            return;  // already initialized for qTranslate
        }
        h.mce = ed;

        /**
         * Highlighting the translatable fields
         * @since 3.2-b3
         */
        ed.getContainer().className += ' qtranxs-translatable';
        ed.getElement().className += ' qtranxs-translatable';

        let updateTinyMCEonInit = h.updateTinyMCEonInit;
        if (updateTinyMCEonInit == null) {
            // 'tmce-active' or 'html-active' was not provided on the wrapper
            const text_e = ed.getContent({format: 'html'}).replace(/\s+/g, '');
            const text_h = h.contentField.value.replace(/\s+/g, '');
            /**
             * @since 3.2.9.8 - this is an ugly trick.
             * Before this version, it was working relying on properly timed synchronisation of the page loading process,
             * which did not work correctly in some browsers like IE or MAC OS, for example.
             * Now, function addContentHooksTinyMCE is called in the footer scripts, before TinyMCE initialization, and it always sets
             * tinyMCEPreInit.mceInit, which causes to call this function, setEditorHooks, on TinyMCE initialization of each editor.
             * However, function setEditorHooks gets invoked in two ways:
             *
             * 1. On page load, when Visual mode is initially on.
             *      In this case we need to apply updateTinyMCE, which possibly applies wpautop.
             *      Without q-X, WP applies wpautop in this case in php code in /wp-includes/class-wp-editor.php,
             *      function 'editor', line "add_filter('the_editor_content', 'wp_richedit_pre');".
             *      q-X disables this call in 'function qtranxf_the_editor',
             *      since wpautop does not work correctly on multilingual values, and there is no filter to adjust its behaviour.
             *      So, here we have to apply back wpautop to single-language value, which is achieved
             *      with a call to updateTinyMCE(h) below.
             *
             * 2. When user switches to Visual mode for the first time from a page, which was initially loaded in Text mode.
             *      In this case, wpautop gets applied internally inside TinyMCE, and we do not need to call updateTinyMCE(h) below.
             *
             * We could not figure out a good way to distinct within this function which way it was called,
             * except this tricky comparison on the next line.
             *
             * If somebody finds out a better way, please let us know at https://github.com/qtranslate/qtranslate-xt/issues/.
             */
            updateTinyMCEonInit = text_e !== text_h;
        }
        if (updateTinyMCEonInit) {
            updateTinyMCE(h);
        }
        return h;
    }

    /** Sets hooks on HTML-loaded TinyMCE editors via tinyMCEPreInit.mceInit. */
    this.addContentHooksTinyMCE = function () {
        if (!window.tinyMCEPreInit || !window.tinyMCE) {
            return;
        }
        for (const key in contentHooks) {
            const h = contentHooks[key];
            if (h.contentField.tagName !== 'TEXTAREA' || h.mce || h.mceInit || !tinyMCEPreInit.mceInit[key])
                continue;
            h.mceInit = tinyMCEPreInit.mceInit[key];
            if (h.mceInit.wpautop) {
                h.wpautop = h.mceInit.wpautop;
                const wrappers = tinymce.DOM.select('#wp-' + key + '-wrap');
                if (wrappers && wrappers.length) {
                    h.wrapper = wrappers[0];
                    if (h.wrapper) {
                        if (tinymce.DOM.hasClass(h.wrapper, 'tmce-active'))
                            h.updateTinyMCEonInit = true;
                        if (tinymce.DOM.hasClass(h.wrapper, 'html-active'))
                            h.updateTinyMCEonInit = false;
                        // otherwise h.updateTinyMCEonInit stays undetermined
                    }
                }
            } else {
                h.updateTinyMCEonInit = false;
            }
            tinyMCEPreInit.mceInit[key].init_instance_callback = function (ed) {
                setEditorHooks(ed);
            }
        }
    };

    /** Adds more TinyMCE editors, which may have been initialized dynamically. */
    this.loadAdditionalTinyMceHooks = function () {
        if (window.tinyMCE) {
            tinyMCE.get().forEach(function (editor) {
                setEditorHooks(editor);
            });
        }
    };

    if (!qTranslateConfig.onTabSwitchFunctions)
        qTranslateConfig.onTabSwitchFunctions = [];
    if (!qTranslateConfig.onTabSwitchFunctionsSave)
        qTranslateConfig.onTabSwitchFunctionsSave = [];
    if (!qTranslateConfig.onTabSwitchFunctionsLoad)
        qTranslateConfig.onTabSwitchFunctionsLoad = [];

    this.addLanguageSwitchListener = function (func) {
        qTranslateConfig.onTabSwitchFunctions.push(func);
    };

    /**
     * @since 3.2.9.8.6
     * Designed as interface for other plugin integration. The documentation is available at
     * https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide
     * The function passed will be called when user presses one of the Language Switching Buttons
     * before the content of all fields hooked is replaced with an appropriate language.
     * Two arguments are supplied:
     * - two-letter language code of currently active language from which the edit language is being switched.
     * - the language code to which the edit language is being switched.
     * The value of "this" is set to the only global instance of qTranslateX object.
     */
    this.addLanguageSwitchBeforeListener = function (func) {
        qTranslateConfig.onTabSwitchFunctionsSave.push(func);
    };

    /**
     * @since 3.3.2
     * Designed as interface for other plugin integration. The documentation is available at
     * https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide
     * Delete handler previously added by function addLanguageSwitchBeforeListener.
     */
    this.delLanguageSwitchBeforeListener = function (func) {
        for (let i = 0; i < qTranslateConfig.onTabSwitchFunctionsSave.length; ++i) {
            const f = qTranslateConfig.onTabSwitchFunctionsSave[i];
            if (f !== func)
                continue;
            qTranslateConfig.onTabSwitchFunctionsSave.splice(i, 1);
            return;
        }
    };

    /**
     * @since 3.2.9.8.6
     * Designed as interface for other plugin integration. The documentation is available at
     * https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide
     * The function passed will be called when user presses one of the Language Switching Buttons
     * after the content of all fields hooked is replaced with an appropriate language.
     * Two arguments are supplied:
     * - two-letter language code of active language to which the edit language is already switched.
     * - the language code from which the edit language is being switched.
     * The value of "this" is set to the only global instance of qTranslateX object.
     */
    this.addLanguageSwitchAfterListener = function (func) {
        qTranslateConfig.onTabSwitchFunctionsLoad.push(func);
    };

    /**
     * @since 3.3.2
     * Designed as interface for other plugin integration. The documentation is available at
     * https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide
     * Delete handler previously added by function addLanguageSwitchAfterListener.
     */
    this.delLanguageSwitchAfterListener = function (func) {
        for (let i = 0; i < qTranslateConfig.onTabSwitchFunctionsLoad.length; ++i) {
            const f = qTranslateConfig.onTabSwitchFunctionsLoad[i];
            if (f !== func)
                continue;
            qTranslateConfig.onTabSwitchFunctionsLoad.splice(i, 1);
            return;
        }
    };

    /**
     * @since 3.2.9.8.9
     * Designed as interface for other plugin integration. The documentation is available at
     * https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide
     *
     */
    this.enableLanguageSwitchingButtons = function (on) {
        const display = on ? 'block' : 'none';
        for (const lang in qTranslateConfig.tabSwitches) {
            const tabSwitches = qTranslateConfig.tabSwitches[lang];
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
            const w = wraps[i];
            const forms = w.getElementsByTagName('form');
            if (forms.length)
                return forms[0];
        }
        const forms = document.getElementsByTagName('form');
        if (forms.length === 1)
            return forms[0];
        for (let i = 0; i < forms.length; ++i) {
            const f = forms[i];
            const wraps = f.getElementsByClassName('wrap');
            if (wraps.length)
                return f;
        }
        return null;
    };

    const getFormWrap = function () {
        const forms = document.getElementsByTagName('form');
        for (let i = 0; i < forms.length; ++i) {
            const f = forms[i];
            const wraps = f.getElementsByClassName('wrap');
            if (wraps.length)
                return wraps[0];
        }
        const wraps = document.getElementsByClassName('wrap');
        for (let i = 0; i < wraps.length; ++i) {
            const w = wraps[i];
            const forms = w.getElementsByTagName('form');
            if (forms.length)
                return w;
        }
        return null;
    };

    if (typeof (pg.addContentHooks) == "function")
        pg.addContentHooks(this);

    if (qTranslateConfig.page_config && qTranslateConfig.page_config.forms)
        addPageHooks(qTranslateConfig.page_config.forms);

    addMultilingualHooks();

    if (!displayHookNodes.length && !displayHookAttrs.length) {
        let ok = false;
        for (const key in contentHooks) {
            ok = true;
            break;
        }
        if (!ok) {
            return;
        }
    }

    this.onLoadLanguage = function (lang, langFrom) {
        const onTabSwitchFunctionsLoad = qTranslateConfig.onTabSwitchFunctionsLoad;
        for (let i = 0; i < onTabSwitchFunctionsLoad.length; ++i) {
            onTabSwitchFunctionsLoad[i].call(qTranslateConfig.qtx, lang, langFrom);
        }
    };

    /**
     * former switchTab
     * @since 3.3.2
     */
    this.switchActiveLanguage = function (lang) {
        if (qTranslateConfig.activeLanguage === lang) {
            return;
        }
        if (qTranslateConfig.activeLanguage) {
            let ok2switch = true;
            const onTabSwitchFunctionsSave = qTranslateConfig.onTabSwitchFunctionsSave;
            for (let i = 0; i < onTabSwitchFunctionsSave.length; ++i) {
                const ok = onTabSwitchFunctionsSave[i].call(qTranslateConfig.qtx, qTranslateConfig.activeLanguage, lang);
                if (ok === false)
                    ok2switch = false;
            }
            if (!ok2switch)
                return; // cancel button switch, if one of onTabSwitchFunctionsSave returned 'false'

            const tabSwitches = qTranslateConfig.tabSwitches[qTranslateConfig.activeLanguage];
            for (let i = 0; i < tabSwitches.length; ++i) {
                tabSwitches[i].classList.remove(qTranslateConfig.lsb_style_active_class);
                $(tabSwitches[i]).find('.button').removeClass('active');
            }
        }

        const langFrom = qTranslateConfig.activeLanguage;
        qTranslateConfig.activeLanguage = lang;
        $('input[name="qtranslate-edit-language"]').val(lang);

        {
            const tabSwitches = qTranslateConfig.tabSwitches[qTranslateConfig.activeLanguage];
            for (let i = 0; i < tabSwitches.length; ++i) {
                tabSwitches[i].classList.add(qTranslateConfig.lsb_style_active_class);
                $(tabSwitches[i]).find('.button').addClass('active');
            }
        }
        const onTabSwitchFunctions = qTranslateConfig.onTabSwitchFunctions;
        for (let i = 0; i < onTabSwitchFunctions.length; ++i) {
            onTabSwitchFunctions[i].call(qTranslateConfig.qtx, lang, langFrom);
        }
        qtx.onLoadLanguage(lang, langFrom);
    };

    this.clickSwitchLanguage = function () {
        const tabSwitch = $(this).hasClass('button') ? this.parentNode : this;
        const lang = tabSwitch.lang;
        if (!lang) {
            alert('qTranslate-XT: This should not have happened: Please, report this incident to the developers: !lang');
            return;
        }
        if ($('.qtranxs-lang-switch-wrap').hasClass('copying')) {
            qtx.copyContentFrom(lang);
            $(tabSwitch).find('.button').blur();	// remove focus of source language in case of layout with button
            $('.qtranxs-lang-switch-wrap').removeClass('copying');
            $('.qtranxs-lang-copy .button').removeClass('active');
        } else {
            qtx.switchActiveLanguage(lang);
        }
    };

    this.toggleCopyFrom = function () {
        $('.qtranxs-lang-switch-wrap').toggleClass('copying');
        $('.qtranxs-lang-copy .button').toggleClass('active');
        // store or restore original title according to current mode (copy or switch)
        if ($('.qtranxs-lang-switch-wrap').hasClass('copying')) {
            $('.qtranxs-lang-switch').each(function () {
                $(this).attr('orig-title', $(this).attr('title'));
                if ($(this).attr('lang') === qTranslateConfig.activeLanguage)
                    $(this).attr('title', qTranslateConfig.strings.CopyFromAlt);
                else
                    $(this).attr('title', qTranslateConfig.strings.CopyFrom + ' [:' + $(this).attr('lang') + ']');
            });
        } else {
            $('.qtranxs-lang-switch').each(function () {
                $(this).attr('title', $(this).attr('orig-title'));
            });
        }
    };

    this.copyContentFrom = function (langFrom) {
        const lang = qTranslateConfig.activeLanguage;
        let changed = false;
        for (const key in contentHooks) {
            const h = contentHooks[key];
            const mce = h.mce && !h.mce.hidden;
            let value = mce ? h.mce.getContent({format: 'html'}) : h.contentField.value;
            if (value)
                continue; // do not overwrite existent content
            value = h.fields[langFrom].value;
            if (!value)
                continue;
            h.contentField.value = value;
            if (mce)
                updateTinyMCE(h);
            changed = true;
        }
        if (changed)
            qtx.onLoadLanguage(lang, langFrom);
    };

    /**
     * @since 3.3.2
     */
    this.createSetOfLSBwith = function (lsb_style_extra_wrap_classes) {
        const langSwitchWrap = qtranxj_ce('ul', {className: 'qtranxs-lang-switch-wrap ' + lsb_style_extra_wrap_classes});
        const langs = qTranslateConfig.language_config;
        if (!qTranslateConfig.tabSwitches)
            qTranslateConfig.tabSwitches = {};
        for (const lang in langs) {
            const lang_conf = langs[lang];
            const flag_location = qTranslateConfig.flag_location;
            const li_title = qTranslateConfig.strings.ShowIn + lang_conf.admin_name + ' [:' + lang + ']';
            const tabSwitch = qtranxj_ce('li', {
                lang: lang,
                className: 'qtranxs-lang-switch qtranxs-lang-switch-' + lang,
                title: li_title,
                onclick: qtx.clickSwitchLanguage
            }, langSwitchWrap);
            let tabItem = tabSwitch;
            if (qTranslateConfig.lsb_style_subitem === 'button') {
                // reuse WordPress secondary button
                tabItem = qtranxj_ce('button', {className: 'button button-secondary', type: 'button'}, tabSwitch);
            }
            qtranxj_ce('img', {src: flag_location + lang_conf.flag}, tabItem);
            qtranxj_ce('span', {innerHTML: lang_conf.name}, tabItem);
            if (qTranslateConfig.activeLanguage === lang) {
                tabSwitch.classList.add(qTranslateConfig.lsb_style_active_class);
                $(tabSwitch).find('.button').addClass('active');
            }
            if (!qTranslateConfig.tabSwitches[lang])
                qTranslateConfig.tabSwitches[lang] = [];
            qTranslateConfig.tabSwitches[lang].push(tabSwitch);
        }
        if (!qTranslateConfig.hide_lsb_copy_content) {
            const tab = qtranxj_ce('li', {className: 'qtranxs-lang-copy'}, langSwitchWrap);
            const btn = qtranxj_ce('button', {
                className: 'button button-secondary',
                type: 'button',
                title: qTranslateConfig.strings.CopyFromAlt,
                onclick: qtx.toggleCopyFrom
            }, tab);
            qtranxj_ce('span', {innerHTML: qTranslateConfig.strings.CopyFrom}, btn);
        }
        return langSwitchWrap;
    };

    /**
     * @since 3.4.8
     */
    this.createSetOfLSB = function () {
        return qtx.createSetOfLSBwith(qTranslateConfig.lsb_style_wrap_class + ' widefat');
    };

    const setupMetaBoxLSB = function () {
        const mb = document.getElementById('qtranxs-meta-box-lsb');
        if (!mb)
            return;

        const inside_elems = mb.getElementsByClassName('inside');
        if (!inside_elems.length)
            return; // consistency check in case WP did some changes

        mb.className += ' closed';
        $(mb).find('.hndle').remove(); // original h3 element is replaced with span below

        const sp = document.createElement('span');
        mb.insertBefore(sp, inside_elems[0]);
        sp.className = 'hndle ui-sortable-handle';

        const langSwitchWrap = qtx.createSetOfLSBwith(qTranslateConfig.lsb_style_wrap_class);
        sp.appendChild(langSwitchWrap);
        $('#qtranxs-meta-box-lsb .hndle').unbind('click.postboxes');
    };

    if (qTranslateConfig.LSB) {
        // additional initialization
        this.addContentHooksTinyMCE();
        setupMetaBoxLSB();

        // create sets of LSB
        const anchors = [];
        if (qTranslateConfig.page_config && qTranslateConfig.page_config.anchors) {
            for (const id in qTranslateConfig.page_config.anchors) {
                const anchor = qTranslateConfig.page_config.anchors[id];
                const f = document.getElementById(id);
                if (f) {
                    anchors.push({f: f, where: anchor.where});
                } else if (anchor.jquery) {
                    const list = $(anchor.jquery);
                    for (let i = 0; i < list.length; ++i) {
                        const f = list[i];
                        anchors.push({f: f, where: anchor.where});
                    }
                }
            }
        }
        if (!anchors.length) {
            let f = pg.langSwitchWrapAnchor;
            if (!f) {
                f = getWrapForm();
            }
            if (f) anchors.push({f: f, where: 'before'});
        }
        for (let i = 0; i < anchors.length; ++i) {
            const anchor = anchors[i];
            if (!anchor.where || anchor.where.indexOf('before') >= 0) {
                const langSwitchWrap = qtx.createSetOfLSB();
                anchor.f.parentNode.insertBefore(langSwitchWrap, anchor.f);
            }
            if (anchor.where && anchor.where.indexOf('after') >= 0) {
                const langSwitchWrap = qtx.createSetOfLSB();
                anchor.f.parentNode.insertBefore(langSwitchWrap, anchor.f.nextSibling);
            }
            if (anchor.where && anchor.where.indexOf('first') >= 0) {
                const langSwitchWrap = qtx.createSetOfLSB();
                anchor.f.insertBefore(langSwitchWrap, anchor.f.firstChild);
            }
            if (anchor.where && anchor.where.indexOf('last') >= 0) {
                const langSwitchWrap = qtx.createSetOfLSB();
                anchor.f.insertBefore(langSwitchWrap, null);
            }
        }

        /**
         * @since 3.2.4 Synchronization of multiple sets of Language Switching Buttons
         */
        this.addLanguageSwitchListener(onTabSwitch);
        if (pg.onTabSwitch) {
            this.addLanguageSwitchListener(pg.onTabSwitch);
        }
    }
};

/**
 * Designed as interface for other plugin integration. The documentation is available at
 * https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide
 *
 * qTranslateX instance is saved in global variable qTranslateConfig.qtx,
 * which can be used by theme or plugins to dynamically change content hooks.
 *
 * Note: be sure to enqueue this script before using it in other plugin (!)
 *
 * @since 3.4
 */
qTranslateConfig.js.get_qtx = function () {
    if (!qTranslateConfig.qtx)
        qTranslateConfig.qtx = new qTranslateX(qTranslateConfig.js);
    return qTranslateConfig.qtx;
};

// With jQuery3 ready handlers fire asynchronously and may be fired after load.
// See: https://github.com/jquery/jquery/issues/3194
$(window).on('load', function () {
    // qtx may already be initialized (see 'wp_tiny_mce_init' for the Classic Editor)
    const qtx = qTranslateConfig.js.get_qtx();
    // Setup hooks for additional TinyMCE editors initialized dynamically
    qtx.loadAdditionalTinyMceHooks();
});
