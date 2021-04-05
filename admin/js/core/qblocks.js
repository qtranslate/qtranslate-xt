/**
 * Utilities for qTranslate blocks
 */
var qTranslateConfig = window.qTranslateConfig;

export var qtranxj_get_split_blocks = function (text) {
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

export var qtranxj_split = function (text) {
    var blocks = qtranxj_get_split_blocks(text);
    return qtranxj_split_blocks(blocks);
};

export var qtranxj_split_blocks = function (blocks) {
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
