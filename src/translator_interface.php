<?php

/**
 * Flags used in translate_text.
 */
const QTX_TRANSLATOR_SHOW_DEFAULT   = 1;
const QTX_TRANSLATOR_SHOW_AVAILABLE = 2;
const QTX_TRANSLATOR_SHOW_EMPTY     = 4;

/**
 * Interface QTX_Translator_Interface
 *
 * Designed as interface for other plugin integration. The documentation is available at
 * https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide
 *
 * It is recommended to only use the functions listed here when developing a 3rd-party integration.
 * It is not recommended to access global variables directly.
 *
 * Each `translate_{item}` method declared here is connected to a filter with the same item name.
 * For example, to call 'translate_text', one may use the following line of code:
 *
 *   $text = apply_filters('qtranslate_text', $text, $lang, $flags);
 *
 * where arguments $lang and $flags may be omitted.
 *
 * If a translating plugin is not loaded, the variable $text will not be altered, otherwise it may get translated, if applicable. This is a safe and easy way to integrate your plugin or theme with a translating plugin.
 *
 * Below is the list of all available filter calls, printed here for the sake of convenience for a developer to copy and paste.
 *
 * Available at both, front- and admin-side:
 *
 *   $text = apply_filters('qtranslate_text', $text, $lang=null, $flags=0);
 *   $term = apply_filters('qtranslate_term', $term, $lang=null, $taxonomy=null);
 *   $url  = apply_filters('qtranslate_url', $url, $lang=null);
 *
 * @since 3.4
 */
interface QTX_Translator_Interface {
    /**
     * Get QTX_Translator_Interface global object.
     */
    public static function get_translator();

    /**
     * @return string two-letter code of active language.
     * @since 3.4.6.9
     */
    public function get_language(): string;

    /**
     * @param string $lang two-letter code of language to be set as active. Further translations will be to this language unless desired languge is specified.
     *
     * @return string two-letter code of new active language.
     * @since 3.4.6.9
     *
     */
    public function set_language( string $lang ): string;

    /**
     * Get translated value from a multilingual string.
     *
     * @param mixed $text - a string, an array or an object possibly containing multilingual values.
     * @param string|null $lang (optional) - a two-letter language code of the language to be extracted from $text. If omitted or null, then the currently active language is assumed.
     * @param int $flags (optional) - what to return if text for language $lang is not available. Possible choices are:
     *     QTX_TRANSLATOR_SHOW_DEFAULT - show the value for default language
     *     QTX_TRANSLATOR_SHOW_AVAILABLE - return a list of available languages with language-encoded links to the current page.
     *     QTX_TRANSLATOR_SHOW_EMPTY - return empty string.
     */
    public function translate_text( $text, ?string $lang = null, int $flags = 0 ): string;

    /**
     * Get translated value for a term name.
     *
     * @param mixed $term The term name to be translated. It may be an array of terms.
     * @param string|null $lang (optional) A two-letter language code of the language to translate $term to. If omitted or null, then the currently active language is assumed.
     * @param string|null $taxonomy (optional) Taxonomy name that $term is part of. Currently unused, since all term names assumed to be unique across all taxonomies.
     */
    public function translate_term( $term, ?string $lang = null, ?string $taxonomy = null ): string;

    /**
     * Get language-encoded value for a URL.
     *
     * @param mixed $url The URL to be encoded. It may be an array of URLs.
     * @param string|null $lang (optional) A two-letter language code of the language to encode $url with. If omitted or null, then the currently active language is assumed.
     */
    public function translate_url( $url, ?string $lang = null ): string;
}
