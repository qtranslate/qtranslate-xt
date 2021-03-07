/*
	Copyright 2019  qTranslate-XT  (https://github.com/qtranslate/qtranslate-xt)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/
/**
 * Search for 'Designed as interface for other plugin integration' in comments to functions
 * to find out which functions are safe to use in the 3rd-party integration.
 * Avoid accessing internal variables directly, as they are subject to be re-designed at any time.
 * Single global variable 'qTranslateConfig' is an entry point to the interface.
 * - qTranslateConfig.qtx - is a shorthand reference to the only global object of type 'qTranslateX'.
 * - qTranslateConfig.js - is a place where custom Java script functions are stored, if needed.
 * Read Integration Guide: https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide for more information.
 */

// global config
var qTranslateConfig = window.qTranslateConfig;

/**
 * since 3.2.7
 */
qtranxj_get_split_blocks = function (text) {
    var regex = '(<!--:lang-->|<!--:-->|\\[:lang]|\\[:]|{:lang}|{:})'.replace(/lang/g, qTranslateConfig.lang_code_format);
    var split_regex = new RegExp(regex, "gi");

    // Most browsers support RegExp.prototype[@@split]()... except IE
    if ('a~b'.split(/(~)/).length === 3) {
        return text.split(split_regex);
    }

    // compatibility for unsupported engines
    var start = 0, arr = [];
    var result;
    while ((result = split_regex.exec(text)) != null) {
        arr.push(text.slice(start, result.index));
        if (result.length > 1)
            arr.push(result[1]);
        start = split_regex.lastIndex;
    }
    if (start < text.length)
        arr.push(text.slice(start));
    if (start === text.length)
        arr.push(''); // delimiter at the end
    return arr;
};

/**
 * since 3.2.7
 */
qtranxj_split = function (text) {
    var blocks = qtranxj_get_split_blocks(text);
    return qtranxj_split_blocks(blocks);
};

/**
 * since 3.1-b1 - closing tag [:]
 */
qtranxj_split_blocks = function (blocks) {
    var result = new Object;
    for (var lang in qTranslateConfig.language_config) {
        result[lang] = '';
    }
    if (!blocks || !blocks.length)
        return result;
    if (blocks.length === 1) {
        // no language separator found, enter it to all languages
        var b = blocks[0];
        for (var lang in qTranslateConfig.language_config) {
            result[lang] += b;
        }
        return result;
    }
    var clang_regex = new RegExp('<!--:(lang)-->'.replace(/lang/g, qTranslateConfig.lang_code_format), 'gi');
    var blang_regex = new RegExp('\\[:(lang)]'.replace(/lang/g, qTranslateConfig.lang_code_format), 'gi');
    var slang_regex = new RegExp('{:(lang)}'.replace(/lang/g, qTranslateConfig.lang_code_format), 'gi');
    var lang = false;
    var matches;
    for (var i = 0; i < blocks.length; ++i) {
        var b = blocks[i];
        if (!b.length)
            continue;
        matches = clang_regex.exec(b);
        clang_regex.lastIndex = 0;
        if (matches != null) {
            lang = matches[1];
            continue;
        }
        matches = blang_regex.exec(b);
        blang_regex.lastIndex = 0;
        if (matches != null) {
            lang = matches[1];
            continue;
        }
        matches = slang_regex.exec(b);
        slang_regex.lastIndex = 0;
        if (matches != null) {
            lang = matches[1];
            continue;
        }
        if (b === '<!--:-->' || b === '[:]' || b === '{:}') {
            lang = false;
            continue;
        }
        if (lang) {
            if (!result[lang]) result[lang] = b;
            else result[lang] += b;
            lang = false;
        } else {
            // keep neutral text
            for (var key in result) {
                result[key] += b;
            }
        }
    }
    return result;
};

function qtranxj_ce(tagName, props, pNode, isFirst) {
    var el = document.createElement(tagName);
    if (props) {
        for (var prop in props) {
            el[prop] = props[prop];
        }
    }
    if (pNode) {
        if (isFirst && pNode.firstChild) {
            pNode.insertBefore(el, pNode.firstChild);
        } else {
            pNode.appendChild(el);
        }
    }
    return el;
}

(function ($) {
    // the edit language corresponds to the current LSB selection or the main admin language for single mode
    var keyEditLanguage = 'qtranslate-xt-admin-edit-language';
    var storeEditLanguage = function (lang) {
        try {
            sessionStorage.setItem(keyEditLanguage, lang);
        } catch (e) {
            // no big deal if this can't be stored
            console.log('Failed to store "' + keyEditLanguage + '" with sessionStorage', e);
        }
    };

    var qTranslateX = function (pg) {
        var qtx = this;

        /**
         * Designed as interface for other plugin integration. The documentation is available at
         * https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide
         * return array keyed by two-letter language code. Example of usage:
         * var langs = getLanguages();
         * for(var lang_code in langs){
         *  var lang_conf = langs[lang_code];
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
            qTranslateConfig.activeLanguage = sessionStorage.getItem(keyEditLanguage);
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

        var contentHooks = {};

        var updateFusedValueH = function (id, value) {
            if (qTranslateConfig.RAW)
                return;
            var h = contentHooks[id];
            var text = value.trim();
            h.fields[h.lang].value = text;
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
                var idx = 0;
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

            var h = contentHooks[inpField.id] = {};
            h.name = field_name;
            h.contentField = inpField;
            h.lang = qTranslateConfig.activeLanguage;

            var qtx_prefix;
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

            var bfnm, sfnm, p = h.name.indexOf('[');
            if (p < 0) {
                bfnm = qtx_prefix + h.name + ']';
            } else {
                bfnm = qtx_prefix + h.name.substring(0, p) + ']';
                if (h.name.lastIndexOf('[]') < 0) {
                    bfnm += h.name.substring(p);
                } else {
                    var len = h.name.length - 2;
                    if (len > p)
                        bfnm += h.name.substring(p, len);
                    sfnm = '[]';
                }
            }

            var contents;

            h.fields = {};
            if (!qTranslateConfig.RAW) {
                // Most crucial moment when untranslated content is parsed
                contents = qtranxj_split(inpField.value);
                // Substitute the current ML content with translated content for the current language
                inpField.value = contents[h.lang];
                // Insert translated content for each language before the current field
                for (var lang in contents) {
                    var text = contents[lang];
                    var fnm = bfnm + '[' + lang + ']';
                    if (sfnm)
                        fnm += sfnm;
                    var f = qtranxj_ce('input', {name: fnm, type: 'hidden', className: 'hidden', value: text});
                    h.fields[lang] = f;
                    inpField.parentNode.insertBefore(f, inpField);
                }

                // insert a hidden element in the form so that the edit language is sent to the server
                $form = $(inpField).closest('form');
                if ($form.length) {
                    var $hidden = $form.find('input[name="qtranslate-edit-language"]');
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
            var sep;
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
            for (var i = 0; i < fields.length; ++i) {
                var field = fields[i];
                qtx.addContentHook(field, sep, field_name);
            }
        };

        var addContentHooksByClassName = function (nm, container, sep) {
            if (!container)
                container = document;
            var fields = container.getElementsByClassName(nm);
            qtx.addContentHooks(fields, sep);
        };

        this.addContentHooksByClass = function (nm, container) {
            var sep;
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
            var elems = container.getElementsByClassName(nm);
            for (var i = 0; i < elems.length; ++i) {
                var elem = elems[i];
                var items = elem.getElementsByTagName(tag);
                qtx.addContentHooks(items);
            }
        };

        var removeContentHookH = function (h) {
            if (!h)
                return false;
            if (h.sepfield)
                $(h.sepfield).remove();
            var contents = {};
            for (var lang in h.fields) {
                var f = h.fields[lang];
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
            var h = contentHooks[inpField.id];
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
            var h = contentHooks[inpField.id];
            if (h)
                removeContentHookH(h);
            return qtx.addContentHook(inpField);
        };

        /**
         * @since 3.4.6.9
         */
        var getDisplayContentDefaultValue = function (contents) {
            if (contents[qTranslateConfig.language])
                return '(' + qTranslateConfig.language + ') ' + contents[qTranslateConfig.language];
            if (contents[qTranslateConfig.default_language])
                return '(' + qTranslateConfig.default_language + ') ' + contents[qTranslateConfig.default_language];
            for (var lang in contents) {
                if (!contents[lang])
                    continue;
                return '(' + lang + ') ' + contents[lang];
            }
            return '';
        };

        /**
         * @since 3.4.6.9
         */
        var completeDisplayContent = function (contents) {
            var default_value = null;
            for (var lang in contents) {
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
        var displayHookNodes = [];
        var addDisplayHookNode = function (nd) {
            if (!nd.nodeValue)
                return 0;
            var blocks = qtranxj_get_split_blocks(nd.nodeValue);
            if (!blocks || !blocks.length || blocks.length === 1)
                return 0;
            var h = {};
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
        var displayHookAttrs = [];
        var addDisplayHookAttr = function (nd, attr) {
            if (!nd.hasAttribute(attr)) return 0;
            var value = nd.getAttribute(attr);
            var blocks = qtranxj_get_split_blocks(value);
            if (!blocks || !blocks.length || blocks.length === 1)
                return 0;
            var h = {};
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
            var cnt = 0;
            if (elem.childNodes && elem.childNodes.length) {
                for (var i = 0; i < elem.childNodes.length; ++i) {
                    var nd = elem.childNodes[i];
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

        var updateTinyMCE = function (h) {
            text = h.contentField.value;
            if (h.wpautop && window.switchEditors) {
                text = window.switchEditors.wpautop(text);
            }
            h.mce.setContent(text, {format: 'html'});
        };

        var onTabSwitch = function (lang) {
            storeEditLanguage(lang);

            for (var i = displayHookNodes.length; --i >= 0;) {
                var h = displayHookNodes[i];
                if (h.nd.parentNode) {
                    h.nd.nodeValue = h.contents[lang]; // IE gets upset here if node was removed
                } else {
                    displayHookNodes.splice(i, 1); // node was removed by some other function
                }
            }
            for (var i = displayHookAttrs.length; --i >= 0;) {
                var h = displayHookAttrs[i];
                if (h.nd.parentNode) {
                    h.nd.setAttribute(h.attr, h.contents[lang]);
                } else {
                    displayHookAttrs.splice(i, 1); // node was removed by some other function
                }
            }
            if (qTranslateConfig.RAW)
                return;
            for (var key in contentHooks) {
                var h = contentHooks[key];
                var mce = h.mce && !h.mce.hidden;
                if (mce) {
                    h.mce.save({format: 'html'});
                }

                var text = h.contentField.value.trim();
                var blocks = qtranxj_get_split_blocks(text);
                if (!blocks || blocks.length <= 1) {
                    // value is not ML, switch it to other language
                    h.fields[h.lang].value = text;
                    h.lang = lang;
                    var value = h.fields[h.lang].value;
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
                    var contents = qtranxj_split_blocks(blocks);
                    for (var lng in h.fields) {
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
            for (var i = 0; i < elems.length; ++i) {
                var e = elems[i];
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
            for (var j = 0; j < attrs.length; ++j) {
                var a = attrs[j];
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
            for (var i = 0; i < elems.length; ++i) {
                var e = elems[i];
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
            var elems = container.getElementsByClassName(nm);
            qtx.addDisplayHooks(elems);
        };

        /**
         * Designed as interface for other plugin integration. The documentation is available at
         * https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide
         *
         * @since 3.3
         */
        this.addDisplayHooksByTagInClass = function (nm, tag, container) {
            var elems = container.getElementsByClassName(nm);
            for (var i = 0; i < elems.length; ++i) {
                var elem = elems[i];
                var items = elem.getElementsByTagName(tag);
                qtx.addDisplayHooks(items);
            }
        };


        /**
         * adds custom hooks from configuration
         * @since 3.1-b2 - renamed to addCustomContentHooks, since addContentHooks used in qTranslateConfig.js
         * @since 3.0 - addContentHooks
         */
        this.addCustomContentHooks = function () {
            for (var i = 0; i < qTranslateConfig.custom_fields.length; ++i) {
                var fieldName = qTranslateConfig.custom_fields[i];
                qtx.addContentHookByIdName(fieldName);
            }
            for (var i = 0; i < qTranslateConfig.custom_field_classes.length; ++i) {
                var className = qTranslateConfig.custom_field_classes[i];
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
        var addMultilingualHooks = function () {
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
        var addPageHooks = function (page_config_forms) {
            for (var form_id in page_config_forms) {
                var frm = page_config_forms[form_id];
                var form;
                if (frm.form) {
                    if (frm.form.id) {
                        form = document.getElementById(frm.form.id);
                    } else if (frm.form.jquery) {
                        form = $(frm.form.jquery);
                    } else if (frm.form.name) {
                        var elms = document.getElementsByName(frm.form.name);
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
                for (var handle in frm.fields) {
                    var fld = frm.fields[handle];
                    var containers = [];
                    if (fld.container_id) {
                        var container = document.getElementById(fld.container_id);
                        if (container)
                            containers.push(container);
                    } else if (fld.container_jquery) {
                        containers = $(fld.container_jquery);
                    } else if (fld.container_class) {
                        containers = document.getElementsByClassName(fld.container_class);
                    } else {// if(form){
                        containers.push(form);
                    }
                    var sep = fld.encode;
                    switch (sep) {
                        case 'none':
                            continue;
                        case 'display':
                            if (fld.jquery) {
                                for (var i = 0; i < containers.length; ++i) {
                                    var container = containers[i];
                                    var fields = $(container).find(fld.jquery);
                                    if (fld.attrs) {
                                        qtx.addDisplayHooksAttrs(fields, fld.attrs);
                                    } else {
                                        qtx.addDisplayHooks(fields);
                                    }
                                }
                            } else {
                                var id = fld.id ? fld.id : handle;
                                //co('addPageHooks:display: id=',id);
                                var field = document.getElementById(id);
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
                                for (var i = 0; i < containers.length; ++i) {
                                    var container = containers[i];
                                    var fields = $(container).find(fld.jquery);
                                    qtx.addContentHooks(fields, sep, fld.name);
                                }
                            } else {
                                var id = fld.id ? fld.id : handle;
                                qtx.addContentHookById(id, sep, fld.name);
                            }
                            break;
                    }
                }
            }
        };

        var setEditorHooks = function (ed) {
            var id = ed.id;
            if (!id)
                return;
            var h = contentHooks[id];
            if (!h)
                return;
            if (h.mce) {
                return;  // already initialized
            }
            h.mce = ed;

            /**
             * Highlighting the translatable fields
             * @since 3.2-b3
             */
            ed.getContainer().className += ' qtranxs-translatable';
            ed.getElement().className += ' qtranxs-translatable';

            var updateTinyMCEonInit = h.updateTinyMCEonInit;
            if (updateTinyMCEonInit == null) {
                // 'tmce-active' or 'html-active' was not provided on the wrapper
                var text_e = ed.getContent({format: 'html'}).replace(/\s+/g, '');
                var text_h = h.contentField.value.replace(/\s+/g, '');
                /**
                 * @since 3.2.9.8 - this is an ugly trick.
                 * Before this version, it was working relying on properly timed synchronisation of the page loading process,
                 * which did not work correctly in some browsers like IE or MAC OS, for example.
                 * Now, function addContentHooksTinyMCE is called in the footer scripts, before TinyMCE initialization, and it always set
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
            for (var key in contentHooks) {
                var h = contentHooks[key];
                if (h.contentField.tagName !== 'TEXTAREA' || h.mce || h.mceInit || !tinyMCEPreInit.mceInit[key])
                    continue;
                h.mceInit = tinyMCEPreInit.mceInit[key];
                if (h.mceInit.wpautop) {
                    h.wpautop = h.mceInit.wpautop;
                    var wrappers = tinymce.DOM.select('#wp-' + key + '-wrap');
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
        this.loadTinyMceHooks = function () {
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
            for (var i = 0; i < qTranslateConfig.onTabSwitchFunctionsSave.length; ++i) {
                var f = qTranslateConfig.onTabSwitchFunctionsSave[i];
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
            for (var i = 0; i < qTranslateConfig.onTabSwitchFunctionsLoad.length; ++i) {
                var f = qTranslateConfig.onTabSwitchFunctionsLoad[i];
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
            var display = on ? 'block' : 'none';
            for (var lang in qTranslateConfig.tabSwitches) {
                var tabSwitches = qTranslateConfig.tabSwitches[lang];
                for (var i = 0; i < tabSwitches.length; ++i) {
                    var tabSwitchParent = tabSwitches[i].parentElement;
                    tabSwitchParent.style.display = display;
                    break;
                }
                break;
            }
        };

        var getWrapForm = function () {
            var wraps = document.getElementsByClassName('wrap');
            for (var i = 0; i < wraps.length; ++i) {
                var w = wraps[i];
                var forms = w.getElementsByTagName('form');
                if (forms.length)
                    return forms[0];
            }
            var forms = document.getElementsByTagName('form');
            if (forms.length === 1)
                return forms[0];
            for (var i = 0; i < forms.length; ++i) {
                var f = forms[i];
                wraps = f.getElementsByClassName('wrap');
                if (wraps.length)
                    return f;
            }
            return null;
        };

        var getFormWrap = function () {
            var forms = document.getElementsByTagName('form');
            for (var i = 0; i < forms.length; ++i) {
                var f = forms[i];
                var wraps = f.getElementsByClassName('wrap');
                if (wraps.length)
                    return wraps[0];
            }
            var wraps = document.getElementsByClassName('wrap');
            for (var i = 0; i < wraps.length; ++i) {
                var w = wraps[i];
                forms = w.getElementsByTagName('form');
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
            var ok = false;
            for (var key in contentHooks) {
                ok = true;
                break;
            }
            if (!ok) {
                return;
            }
        }

        this.onLoadLanguage = function (lang, langFrom) {
            var onTabSwitchFunctionsLoad = qTranslateConfig.onTabSwitchFunctionsLoad;
            for (var i = 0; i < onTabSwitchFunctionsLoad.length; ++i) {
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
                var ok2switch = true;
                var onTabSwitchFunctionsSave = qTranslateConfig.onTabSwitchFunctionsSave;
                for (var i = 0; i < onTabSwitchFunctionsSave.length; ++i) {
                    var ok = onTabSwitchFunctionsSave[i].call(qTranslateConfig.qtx, qTranslateConfig.activeLanguage, lang);
                    if (ok === false)
                        ok2switch = false;
                }
                if (!ok2switch)
                    return; // cancel button switch, if one of onTabSwitchFunctionsSave returned 'false'

                var tabSwitches = qTranslateConfig.tabSwitches[qTranslateConfig.activeLanguage];
                for (var i = 0; i < tabSwitches.length; ++i) {
                    tabSwitches[i].classList.remove(qTranslateConfig.lsb_style_active_class);
                    $(tabSwitches[i]).find('.button').removeClass('active');
                }
            }

            var langFrom = qTranslateConfig.activeLanguage;
            qTranslateConfig.activeLanguage = lang;
            $('input[name="qtranslate-edit-language"]').val(lang);

            {
                var tabSwitches = qTranslateConfig.tabSwitches[qTranslateConfig.activeLanguage];
                for (var i = 0; i < tabSwitches.length; ++i) {
                    tabSwitches[i].classList.add(qTranslateConfig.lsb_style_active_class);
                    $(tabSwitches[i]).find('.button').addClass('active');
                }
            }
            var onTabSwitchFunctions = qTranslateConfig.onTabSwitchFunctions;
            for (var i = 0; i < onTabSwitchFunctions.length; ++i) {
                onTabSwitchFunctions[i].call(qTranslateConfig.qtx, lang, langFrom);
            }
            qtx.onLoadLanguage(lang, langFrom);
        };

        this.clickSwitchLanguage = function () {
            var tabSwitch = $(this).hasClass('button') ? this.parentNode : this;
            var lang = tabSwitch.lang;
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
            var lang = qTranslateConfig.activeLanguage;
            var changed = false;
            for (var key in contentHooks) {
                var h = contentHooks[key];
                var mce = h.mce && !h.mce.hidden;
                var value = mce ? h.mce.getContent({format: 'html'}) : h.contentField.value;
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
            var langSwitchWrap = qtranxj_ce('ul', {className: 'qtranxs-lang-switch-wrap ' + lsb_style_extra_wrap_classes});
            var langs = qTranslateConfig.language_config;
            if (!qTranslateConfig.tabSwitches)
                qTranslateConfig.tabSwitches = {};
            for (var lang in langs) {
                var lang_conf = langs[lang];
                var flag_location = qTranslateConfig.flag_location;
                var li_title = qTranslateConfig.strings.ShowIn + lang_conf.admin_name + ' [:' + lang + ']';
                var tabSwitch = qtranxj_ce('li', {
                    lang: lang,
                    className: 'qtranxs-lang-switch qtranxs-lang-switch-' + lang,
                    title: li_title,
                    onclick: qtx.clickSwitchLanguage
                }, langSwitchWrap);
                var tabItem = tabSwitch;
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
                var tab = qtranxj_ce('li', {className: 'qtranxs-lang-copy'}, langSwitchWrap);
                var btn = qtranxj_ce('button', {
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

        var setupMetaBoxLSB = function () {
            var mb = document.getElementById('qtranxs-meta-box-lsb');
            if (!mb)
                return;

            var inside_elems = mb.getElementsByClassName('inside');
            if (!inside_elems.length)
                return; // consistency check in case WP did some changes

            mb.className += ' closed';
            $(mb).find('.hndle').remove(); // original h3 element is replaced with span below

            var sp = document.createElement('span');
            mb.insertBefore(sp, inside_elems[0]);
            sp.className = 'hndle ui-sortable-handle';

            var langSwitchWrap = qtx.createSetOfLSBwith(qTranslateConfig.lsb_style_wrap_class);
            sp.appendChild(langSwitchWrap);
            $('#qtranxs-meta-box-lsb .hndle').unbind('click.postboxes');
        };

        if (qTranslateConfig.LSB) {
            // additional initialization
            this.addContentHooksTinyMCE();
            setupMetaBoxLSB();

            // create sets of LSB
            var anchors = [];
            if (qTranslateConfig.page_config && qTranslateConfig.page_config.anchors) {
                for (var id in qTranslateConfig.page_config.anchors) {
                    var anchor = qTranslateConfig.page_config.anchors[id];
                    var f = document.getElementById(id);
                    if (f) {
                        anchors.push({f: f, where: anchor.where});
                    } else if (anchor.jquery) {
                        var list = $(anchor.jquery);
                        for (var i = 0; i < list.length; ++i) {
                            var f = list[i];
                            anchors.push({f: f, where: anchor.where});
                        }
                    }
                }
            }
            if (!anchors.length) {
                var f = pg.langSwitchWrapAnchor;
                if (!f) {
                    f = getWrapForm();
                }
                if (f) anchors.push({f: f, where: 'before'});
            }
            for (var i = 0; i < anchors.length; ++i) {
                var anchor = anchors[i];
                if (!anchor.where || anchor.where.indexOf('before') >= 0) {
                    var langSwitchWrap = qtx.createSetOfLSB();
                    anchor.f.parentNode.insertBefore(langSwitchWrap, anchor.f);
                }
                if (anchor.where && anchor.where.indexOf('after') >= 0) {
                    var langSwitchWrap = qtx.createSetOfLSB();
                    anchor.f.parentNode.insertBefore(langSwitchWrap, anchor.f.nextSibling);
                }
                if (anchor.where && anchor.where.indexOf('first') >= 0) {
                    var langSwitchWrap = qtx.createSetOfLSB();
                    anchor.f.insertBefore(langSwitchWrap, anchor.f.firstChild);
                }
                if (anchor.where && anchor.where.indexOf('last') >= 0) {
                    var langSwitchWrap = qtx.createSetOfLSB();
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
        var qtx = qTranslateConfig.js.get_qtx();
        qtx.loadTinyMceHooks();
    });
})(jQuery);
