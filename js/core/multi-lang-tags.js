/**
 * Utilities for multi-lang tags
 */
'use strict';
const qTranslateConfig = window.qTranslateConfig;

export const mlSplitRaw = function (rawText) {
    const regex = '(<!--:lang-->|<!--:-->|\\[:lang]|\\[:]|{:lang}|{:})'.replace(/lang/g, qTranslateConfig.lang_code_format);
    const splitRegex = new RegExp(regex, "gi");
    // Most browsers support RegExp.prototype[@@split]()... except IE (see debug info from troubleshooting)
    // https://caniuse.com/mdn-javascript_builtins_regexp_--split
    return rawText.split(splitRegex);
};

export const mlExplode = function (rawText) {
    const tokens = mlSplitRaw(rawText);
    return mlParseTokens(tokens);
};

export const mlParseTokens = function (tokens) {
    const result = new Object;
    for (const lang in qTranslateConfig.language_config) {
        result[lang] = '';
    }
    if (!tokens || !tokens.length)
        return result;
    if (tokens.length === 1) {
        // no language separator found, enter it to all languages
        const b = tokens[0];
        for (const lang in qTranslateConfig.language_config) {
            result[lang] += b;
        }
        return result;
    }
    const clang_regex = new RegExp('<!--:(lang)-->'.replace(/lang/g, qTranslateConfig.lang_code_format), 'gi');
    const blang_regex = new RegExp('\\[:(lang)]'.replace(/lang/g, qTranslateConfig.lang_code_format), 'gi');
    const slang_regex = new RegExp('{:(lang)}'.replace(/lang/g, qTranslateConfig.lang_code_format), 'gi');
    let lang = false;
    let matches;
    for (let i = 0; i < tokens.length; ++i) {
        const b = tokens[i];
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
            for (const key in result) {
                result[key] += b;
            }
        }
    }
    return result;
};
