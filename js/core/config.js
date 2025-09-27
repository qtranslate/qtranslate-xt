'use strict';
import {EditorMode} from './config-defs';

const qTranslateConfig = window.qTranslateConfig;

/**
 * Object providing a public interface to retrieve the current configuration.
 *
 * DO NOT USE `qTranslateConfig` directly, this is exported from PHP and the internal format may change at any time.
 * This mapping also allows to document the types and allows auto-completion.
 * The values of plain fields are not supposed to be changed by plugins. Modifying them may lead to undefined behavior.
 *
 * @type {*}
 */
export const config = {
    /**
     * @type EditorMode
     */
    editorMode: qTranslateConfig?.editorMode,
    /**
     * Dictionary of i18n configurations (mapped from JSON structure).
     * @type {*}
     */
    i18n: {
        customFields: {
            /**
             * @type [string]
             */
            classes: qTranslateConfig?.custom_field_classes,
            /**
             * @type [string]
             */
            ids: qTranslateConfig?.custom_fields,
        },
        anchors: qTranslateConfig?.page_config?.anchors,
        forms: qTranslateConfig?.page_config?.forms,
        keys: qTranslateConfig?.page_config?.keys,
        pages: qTranslateConfig?.page_config?.pages,
    },
    lang: {
        /**
         * Default language (code) in settings.
         * @type string
         */
        default: qTranslateConfig?.default_language,
        /**
         * Language detected from server-side.
         * @type string
         */
        detected: qTranslateConfig.language,
        /**
         * @type string
         */
        formatRegex: qTranslateConfig?.lang_code_format,
    },
    /**
     * Enabled languages with their settings.
     * @type {*} Dictionary indexed by language code.
     */
    languages: qTranslateConfig.language_config,
    /**§
     * @type {string}
     */
    l10n: qTranslateConfig?.strings,
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
     * This function maybe needed, as function mlExplode may return languages,
     * which are not enabled, in case they were previously enabled and had some data.
     * Such data is preserved and re-saved until user deletes it manually.
     */
    isLanguageEnabled: function (lang) {
        return !!this.languages[lang];
    },
    /**
     * Check if a page config is active.
     *
     * @param page
     * @returns {boolean}
     */
    isPageActive: function (page) {
        return (this.i18n.setup.keys?.indexOf(page) >= 0);
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
    rawData: qTranslateConfig,
    /**
     * Enum type definitions.
     */
    enum: {
        EditorMode: EditorMode,
    },
};
