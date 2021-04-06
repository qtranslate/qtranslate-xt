/**
 * Utilities for qTranslate blocks
 */
const qTranslateConfig = window.qTranslateConfig;

export const qtranxj_get_split_blocks = function (text) {
    const regex = '(<!--:lang-->|<!--:-->|\\[:lang]|\\[:]|{:lang}|{:})'.replace(/lang/g, qTranslateConfig.lang_code_format);
    const split_regex = new RegExp(regex, "gi");

    // Most browsers support RegExp.prototype[@@split]()... except IE
    if ('a~b'.split(/(~)/).length === 3) {
        return text.split(split_regex);
    }

    // compatibility for unsupported engines
    let start = 0, arr = [];
    let result;
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

export const qtranxj_split = function (text) {
    const blocks = qtranxj_get_split_blocks(text);
    return qtranxj_split_blocks(blocks);
};

export const qtranxj_split_blocks = function (blocks) {
    const result = new Object;
    for (const lang in qTranslateConfig.language_config) {
        result[lang] = '';
    }
    if (!blocks || !blocks.length)
        return result;
    if (blocks.length === 1) {
        // no language separator found, enter it to all languages
        const b = blocks[0];
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
    for (let i = 0; i < blocks.length; ++i) {
        const b = blocks[i];
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
