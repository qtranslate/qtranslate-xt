/**
 * Utilities for multi-lang text.
 */
'use strict';
import {config} from '../config';

/**
 * Decompose a string containing ML tags into an object with keys for each language.
 * Former name: qtranxj_split
 *
 * @see parseTokens
 * Attention! The result is dependent on the current language configuration.
 *
 * @param {string} rawText e.g. '[:en]my text[:fr]mon texte[:]'
 * @return {Object} dictionary indexed by langs (code) e.g. {en: 'my text', fr: 'mon texte'}
 */
export const splitLangs = function (rawText) {
    const tokens = splitTokens(rawText);
    return parseTokens(tokens);
};

/**
 * Decompose a raw string containing ML tag+content (endTag) into an ordered array of tokens with tags and contents.
 * Former name: qtranxj_get_split_blocks
 *
 * Attention! This is a lower-level function for raw parsing, independent of the enabled languages.
 *
 * @param {string} rawText e.g. '[:en]my text[:fr]mon texte[:]'
 * @return {string[]} array of string tokens in sequence e.g. [ '[:en]', 'my-text', '[:fr]', 'mon-texte', '[:]' ]
 */
export const splitTokens = function (rawText) {
    const regex = '(<!--:lang-->|<!--:-->|\\[:lang]|\\[:]|{:lang}|{:})'.replace(/lang/g, config.lang.formatRegex);
    const splitRegex = new RegExp(regex, "gi");
    // Most browsers support RegExp.prototype[@@split]()... except IE (see debug info from troubleshooting)
    // https://caniuse.com/mdn-javascript_builtins_regexp_--split
    return rawText.split(splitRegex);
};

/**
 * Parse an ordered array of tokens of ML tag+content (endTag) and assign them to an object,
 * where keys are language and values the respective content.
 * Former nane: qtranxj_split_blocks
 *
 * Attention! The result is dependent on the current language configuration.
 * If no tag is found the same content is set to each langage.
 * Example: 'unique content' -> {en: 'unique content', fr: 'unique content'}
 *
 * @param {string[]} array of string tokens in sequence e.g. [ '[:en]', 'my-text', '[:fr]', 'mon-texte', '[:]' ]
 * @return {Object} dictionary indexed by langs (code) e.g. {en: 'my text', fr: 'mon texte'}
 */
export const parseTokens = function (tokens) {
    const result = new Object;
    for (const lang in config.languages) {
        result[lang] = '';
    }
    if (!tokens || !tokens.length)
        return result;
    if (tokens.length === 1) {
        // no language separator found, enter it to all languages
        const b = tokens[0];
        for (const lang in config.languages) {
            result[lang] += b;
        }
        return result;
    }
    const clang_regex = new RegExp('<!--:(lang)-->'.replace(/lang/g, config.lang.formatRegex), 'gi');
    const blang_regex = new RegExp('\\[:(lang)]'.replace(/lang/g, config.lang.formatRegex), 'gi');
    const slang_regex = new RegExp('{:(lang)}'.replace(/lang/g, config.lang.formatRegex), 'gi');
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
