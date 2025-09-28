/**
 * Package to retrieve the configuration in a convenient way.
 */
'use strict';
import {EditorMode} from './enums';

const qTranslateConfig = window.qTranslateConfig;

/**
 * Object providing a public interface to retrieve the current configuration for the active page.
 *
 * DO NOT USE `qTranslateConfig` directly, this is exported from PHP and the internal format may change at any time.
 * This mapping also allows to document the types and allows auto-completion.
 * The values and properties are NOT supposed to be changed by plugins. Modifying them will lead to undefined behavior.
 *
 * @since 3.16.0
 * @type {*}
 */
export const config = {
    /**
     * @type EditorMode
     */
    editorMode: qTranslateConfig?.editorMode,
    /**
     * Enum type definitions.
     */
    enum: {
        EditorMode: EditorMode,
    },
    lang: {
        /**
         * @type string
         */
        codeRegex: qTranslateConfig?.lang_code_format,
        /**
         * Default language (code) in settings.
         * @type string
         */
        default: qTranslateConfig?.default_language,
    },
    /**
     * Enabled languages with their settings.
     * @type {*} Dictionary indexed by language code.
     */
    languages: qTranslateConfig.language_config,
    /**
     * Localization strings.
     * @type {string}
     */
    l10n: qTranslateConfig?.strings,
    /**
     * Triggers for active page.
     * @type {*}
     */
    page: {
        /**
         * Language detected from page URL server-side.
         * @type string
         */
        detectedLang: qTranslateConfig.language,
        /**
         * Dictionary of active i18n page configurations for multi-lang hooks.
         * @see https://github.com/qtranslate/qtranslate-xt/wiki/JSON-Configuration
         * @type {*} mapped partially from i18n JSON structure (sub-selection)
         */
        i18n: {
            anchors: qTranslateConfig?.page_config?.anchors,  // To set LSB
            forms: qTranslateConfig?.page_config?.forms,      // Main entry point for ML fields
            keys: qTranslateConfig?.page_config?.keys,        // Keys of active pages being matched
        },
    },
    /**
     * Paths to resources.
     * @type {*}
     */
    path: {
        /**
         * @type string
         */
        flags: qTranslateConfig?.flag_location,
    },
    /**
     * @type {*}
     */
    styles: {
        lsb: {
            activeClass: qTranslateConfig?.lsb_style_active_class,
            subItem: qTranslateConfig?.lsb_style_subitem,
            wrapClass: qTranslateConfig?.lsb_style_wrap_class,
            /**
             * @type bool
             */
            hideCopyContent: qTranslateConfig?.hide_lsb_copy_content,
        },
    },

    /**
     * Check if a language is enabled.
     *
     * @param {string} lang
     * @return {boolean} true if 'lang' is in the hash of enabled languages.
     * This function maybe needed, as function ml.splitLangs may return languages,
     * which are not enabled, in case they were previously enabled and had some data.
     * Such data is preserved and re-saved until user deletes it manually.
     */
    isLanguageEnabled: function (lang) {
        return !!this.languages[lang];
    },
    /**
     * Check if a page i18n config is currently active, meaning its selectors have matched the current URL.
     *
     * Several pages can be activated for the current URL, matched from multiple i18n configurations or entries.
     * This function allows to narrow down the selection, for example to trigger code conditionally for one entry.
     * @see https://github.com/qtranslate/qtranslate-xt/wiki/JSON-Configuration
     *
     * @param {string} pageKey main page key in the i18n configuration
     * @return {boolean} true if the page i18n entry has been selected for the current URL
     */
    isPageActive: function (pageKey) {
        return (this.page.i18n.keys?.indexOf(pageKey) >= 0);
    },
    /**
     * @type bool
     */
    isEditorModeRAW: function () {
        return this.editorMode == EditorMode.RAW;
    },
    /**
     * @type bool
     */
    isEditorModeLSB: function () {
        return this.editorMode == EditorMode.LSB;
    },

    /**
     * This should NOT be used, only in case of fallback. If you need a parameter that is missing, open a ticket.
     */
    _raw: qTranslateConfig,
};

/**
 * Internal fields under construction (WIP), do NOT use!
 */
// Might be generalized in page config fields
config.page.i18n._custom = {
    classes: qTranslateConfig?.custom_field_classes,
    ids: qTranslateConfig?.custom_fields,
};
// Consider a URL section domains, url info, ...
config._urlMode = qTranslateConfig?.url_mode;
