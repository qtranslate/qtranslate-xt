# qTranslate X #
Developed by: qTranslate Team based on original code by Qian Qin
Contributors: johnclause, chineseleper, Vavooon, grafcom
Tags: multilingual, language, admin, tinymce, bilingual, widget, switcher, i18n, l10n, multilanguage, translation
Requires at least: 3.9
Tested up to: 4.3
Stable tag: 3.3
License: GPLv3 or later
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=QEXEK3HX8AR6U
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Adds user-friendly multilingual content management and translation support. It is an up-to-date fork of qTranslate with many additional features.

## Description ##

This plugin is a descendant of [qTranslate](https://wordpress.org/plugins/qtranslate/ "Original qTranslate plugin"), which has apparently been abandoned by the original author, [Qian Qin](http://www.qianqin.de/qtranslate/ "the original author of qTranslate"). 

While the back-end database framework is left almost intact, the design of editors is drastically changed and improved to be much less vulnerable to WP updates. Instead of seeing multiple lines per each language for title, qTranslate-X provides language switching buttons, which, once pressed, make all the text fields to be filled with the language chosen. The instant language change happens locally in your browser without sending an additional request to the server.

qTranslate-X makes creation of multilingual content as easy as working with a single language. Here are some features:

- One-click local switching between the languages - Changing the language as easy as switching between Visual and HTML.
- Language customizations without changing the .mo files - It stores all the translations in the same post fields, while shows it to user for editing one by one depending on the language to edit chosen.
- In-line syntax '`<!--:en-->English Text<!--:--><!--:de-->Deutsch<!--:-->`' or '`[:en]English Text[:de]Deutsch[:]`' for theme-custom fields gets them translated. See [FAQ](https://wordpress.org/plugins/qtranslate-x/faq/ "qTranslate-X FAQ") for more information.
- Starting from release 3.1 encoding like this '`[:en]English Text[:de]Deutsch`' may also have closing tag `[:]`, which makes the example looks like this: '`[:en]English Text[:de]Deutsch[:]`'. The advantage of this is that one now can encode strings like this '`[:en]English Text[:]<html-language-neutral-code>[:de]Deutsch[:]<another-html-language-neutral-code>`', with language-neutral text embedded. Closing tag is not required, but if it is absent and multilingual text happened to be used embedded within other language-neutral text, then a part of language-neutral text will be recognized as language-specific text and may be removed by translator. That was a common incompatibility issue with other plugins, which is now resolved. Comment-like encoding `<!--:-->` still works as well, and can be used if desired, but it does not have any feasible advantage over `[:]`-style.
- Multilingual dates out of the box - translates dates and time for you.
- Theme custom fields can be configured to be translatable too.
- Comes with a number of languages already built-in - English, German, Simplified Chinese, for example, and many more.
- Choose one of 3 Modes to make your URLs look pretty and SEO-friendly. - The simple and beautiful `/en/foo/`, or nice and neat `en.yoursite.com`, and everywhere compatible `?lang=en`.
- One language for each URL - Users and SEO will thank you for not mixing multilingual content.
- qTranslate-X supports unlimited number of languages, which can be easily added/modified/deleted via a comfortable Configuration Page at Settings->Languages.
- Custom CSS for "qTranslate Language Chooser" widget configurable via its properties.
- Menu item "Language Switcher" to enable language choosing from a menu.
- Use [Google XML Sitemaps v3 for qTranslate](https://wordpress.org/plugins/google-xml-sitemaps-v3-for-qtranslate/) plugin to rebuild your XML sitemap for better SEO support.

The website [qTranslate-X explained](https://qtranslatexteam.wordpress.com/about/) provides and keeps updated a few useful listings:

- The [list of plugins, which provide integration](https://qtranslatexteam.wordpress.com/integrated-plugins/) of qTranslate-X with other popular plugins.
- The [list of some plugins reported to be compatible](https://qtranslatexteam.wordpress.com/compatible/) with qTranslate-X without an additional integrating plugin.
- The [list of some plugins reported not to be currently compatible](https://qtranslatexteam.wordpress.com/incompatible/) with qTranslate-X.

If you encounter a conflicting plugin, please [let us know](https://qtranslatexteam.wordpress.com/contact-us/), and meanwhile try to use other plugin of similar functionality, if possible.

You may still find a lot of useful information through reading [qTranslate](https://wordpress.org/plugins/qtranslate/ "Original qTranslate plugin")'s original documentation, which is not duplicated here in full. There are also other plugins, which offer multilingual support, but it seems that Qian Qin has very good original back-end design, and many people have been pleasantly using his plugin ever since. It stores all translations in the same single post, which makes it easy to maintain and to use it with other plugins. However, the user interface of former qTranslate got out of sync with the recent versions of Wordpress, especially after WP went to TinyMCE 4. There is a number of forks of qTranslate, see for example, [mqTranslate](https://wordpress.org/plugins/mqtranslate/ "mqTranslate plugin"), [qTranslate Plus](https://wordpress.org/plugins/qtranslate-xp/ "qTranslate Plus plugin") and [zTranslate](https://wordpress.org/plugins/ztranslate/ "zTranslate plugin"). They all try to fix qTranslate's user interface preserving its original back-end, which is what this plugin does too. This plugin is a hybrid of all of them and fixes a few bugs in each of them. It also has many new features too, like theme custom translatable fields, for example. We hope that this plugin is the most complete working version which combines the best features of [qTranslate](https://wordpress.org/plugins/qtranslate/ "Original qTranslate plugin"), [mqTranslate](https://wordpress.org/plugins/mqtranslate/ "mqTranslate fork"), [qTranslate Plus](https://wordpress.org/plugins/qtranslate-xp/ "qTranslate Plus fork") and [zTranslate](https://wordpress.org/plugins/ztranslate/ "zTranslate fork").

We organized an anonymous entity [qTranslate Team](https://github.com/qTranslate-Team) to maintain a joint authority of all qTranslate-ish plugins. Anyone is welcome to join with a contribution. Participating plugin authors should share the support efforts for each other.

GitHub repository is available: [https://github.com/qTranslate-Team/qtranslate-x.git](https://github.com/qTranslate-Team/qtranslate-x).

We thank our sponsors for persistent help and support:

* [Citizens Law Group](http://www.citizenslawgroup.com "Chicago Bankruptcy Attorney - Citizens Law Group")
* [Gunu](https://profiles.wordpress.org/grafcom "Gunu (Marius Siroen)") (Marius Siroen)
* [Lightbulb Web Agency](http://lightbulb.lu/)
* [OptimWise](http://optimwise.com "OptimWise web design")
* [Pedro Mendonça](https://github.com/pedro-mendonca)
* [pictibe Werbeagentur](http://www.pictibe.de "pictibe Werbeagentur Köln Webdesign")

## Installation ##

Installation of this plugin is no different from any other plugin:

1. Download the plugin from [WordPress](http://wordpress.org/plugins/qtranslate-x/ "qTranslate-X") or take the latest development version from [GitHub](https://github.com/qTranslate-Team/qtranslate-x).
1. Use WordPress `/wp-admin/plugin-install.php` page to install a plugin or extract all the files and upload everything (keeping the directory structure) to the `/wp-content/plugins/` directory.
1. Deactivate plugin qTranslate, mqTranslate, qTranslate Plus, zTranslate or any other multilingual plugin, if you are running any.
1. Activate qTranslate-X through the 'Plugins' (`/wp-admin/plugins.php`) configuration page in WordPress.
1. Open Settings->Languages configuration page and add/delete/disable any languages you need.
1. Add the "qTranslate Language Chooser" widget or "Language Switcher" menu item to let your visitors switch the language.
1. For the new installers, it may be useful to read [Startup Guide](https://qtranslatexteam.wordpress.com/startup-guide/).
1. You may use [Google XML Sitemaps v3 for qTranslate](https://wordpress.org/plugins/google-xml-sitemaps-v3-for-qtranslate/) plugin to rebuild your XML sitemap for better SEO support.
1. Configure theme custom fields to be translatable if needed (Settings -> Languages: "Custom Fields").
1. Upgrading from [qTranslate](https://wordpress.org/plugins/qtranslate/ "qTranslate original plugin") required no additional actions, qTranslate-X will continue to work from the database entries of qTranslate. Upgrading from other qTranslate forks may require re-configuration of the languages and taxonomies names, unless you rename corresponding database entries directly.
1. **Important**: Read [migration instructions](https://qtranslatexteam.wordpress.com/2015/02/24/migration-from-other-multilingual-plugins/), if you previously used other multilingual plugin.

## Frequently Asked Questions ##

FAQ list is available at "qTranslate-X explained" website: [https://qtranslatexteam.wordpress.com/faq/](https://qtranslatexteam.wordpress.com/faq/ "qTranslate-X explained FAQ"), where it is easier to maintain it in between releases.

For the new installers, it may be useful to read [Startup Guide](https://qtranslatexteam.wordpress.com/startup-guide/).

It is important to read [migration instructions](https://qtranslatexteam.wordpress.com/2015/02/24/migration-from-other-multilingual-plugins/), if you previously used other multilingual plugin.

Developers: please drop new topics here, the text will be moved to [qTranslate-X explained](https://qtranslatexteam.wordpress.com/faq/ "qTranslate-X explained FAQ") at the time of the next release.

## Upgrade Notice ##

* Upgrading from [qTranslate](https://wordpress.org/plugins/qtranslate/ "qTranslate original plugin") and [zTranslate](https://wordpress.org/plugins/ztranslate/ "zTranslate plugin") requires no additional actions, qTranslate-X will continue to work from the database entries of qTranslate. One may also go back to qTranslate at any time.
* Upgrading from other qTranslate forks also painless with an additional steps of configuration import and using 'Convert Database' option. One may also go back at any time using configuration export and 'Convert Database' option.
* Former page and post translations are untouched and preserved in any case, except when using 'Convert Database' option.
* Upgrading from other multilingual frameworks will require custom re-configuration. We suggest to search for a plugin, which may be already implemented to transfer the translations to qTranslate or to qTranslate-X. If a plugin works for one, it should work for other too, since qTranslate-X and qTranslate share the same database structures.
* **Important**: read [migration instructions](https://qtranslatexteam.wordpress.com/2015/02/24/migration-from-other-multilingual-plugins/).

## Screenshots ##

1. Editing screen showing the buttons to switch the languages. Pressing a button does not make a call to the server, the editing happens locally, until "Update" button is pressed, the same way as it is for one language.
2. Language Management Interface

## Changelog ##

### 3.4 stable ###
* Includes all changes after version 3.3. Please, review [Release Notes](https://qtranslatexteam.wordpress.com/2015/05/15/release-notes-3-4/).
* Major new feature: [Integration Framework](https://qtranslatexteam.wordpress.com/integration/) is finalized in its first edition.
* Translation: a lot of thanks to all translators contributed.

### 3.3.7 ###
* Feature: finalizing [Integration Framework](https://qtranslatexteam.wordpress.com/integration/).
* Enhancement: 'plugins' vs 'mu-plugins', links of sub-folders, etc. [Issue #168](https://github.com/qTranslate-Team/qtranslate-x/pull/168).
* Enhancement: gettext filters in raw and single language modes.
* Feature: swirly-bracket(brace) language encoding added to be used in places where square-bracket and comment encoding do not work. '[:]' sometimes conflict with shortcodes. '<!--:-->' does not survive tag clean up. '{:}'(swirly-bracket) seems to survive all.
* Fix: handling non-standard language code: [Issue #171](https://github.com/qTranslate-Team/qtranslate-x/issues/171).
* Fix: 'Head inline CSS' update on language list changes.
* Fix: pagination of posts under Query Mode of URL Modification (filter 'qtranxf_convertBlogInfoURL'): [Issue #155](https://github.com/qTranslate-Team/qtranslate-x/issues/155). Filters 'home_url' and 'bloginfo_url' are disabled in Query mode.
* Fix: updated `qtranxf_get_option_config_files` to check for misconfigurations.
* Fix: prev/next_post_link [WP Topic](https://wordpress.org/support/topic/prevnext_post_link-return-links-to-articles-without-translation).

### 3.3.5 ###
* Feature: [Integration Framework](https://qtranslatexteam.wordpress.com/integration/) finalizing JSON file format.
* Enhancement: function `qtranxf_error_log` to show crucial error messages as admin notices on all admin pages and to update 'error_log' file.

### 3.3.4 ###
* Fix: function `qtrans_join`: [WP Topic](https://wordpress.org/support/topic/cant-switch-back-to-default-language).
* Feature: Meta-box 'Language' with Language Switching Buttons is now ready for functionality testing.
* Feature: [Integration Framework](https://qtranslatexteam.wordpress.com/integration/) for PHP code.

### 3.3.3 ###
* Fix: skip filter 'pre_get_posts' for post_type 'nav_menu_item': [WP Topic](https://wordpress.org/support/topic/menu-conflict-with-plugin-custom-field-template).
* Fix: hreflang in `<link>` is now locale instead of language code: [WP Topic](https://wordpress.org/support/topic/setting-hreflang-language-locale).

### 3.3.2 ###
* Feature: Meta-box 'LSB' for Language Switching Buttons, which can be placed anywhere on a page (not finished, just to test an idea on how it will look and work).
* Feature: integration framework is finished in its first version as it is described in [Integration Guide](https://qtranslatexteam.wordpress.com/integration/). The configuration is now loadable from JSON files, which 3rd-party themes or plugins may generate.
* Enhancement: pre-sets for option 'Highlight Style' are changed to use CSS property 'border' instead of 'box-shadow' and 'outline', otherwise the highlighting was not always visible, specifically when class 'widefat' is also in use.
* Fix: visual mode misbehaviour in field 'Details' for events managed by plugin [Events Made Easy](https://wordpress.org/plugins/events-made-easy/) [Issue #152](https://github.com/qTranslate-Team/qtranslate-x/issues/152).

### 3.3.1 ###
* Enhancement: added class attributes `qtranxs-available-language*` to the message about available languages to make it CSS-customizable: [WP Topic](https://wordpress.org/support/topic/translation-not-available-message-element-has-no-class).
* Enhancement: `qtrans_getAvailableLanguages` added to "Compatibility Functions".
* Enhancement: tabs on configuration page, thanks to [Pedro Mendonça](https://github.com/pedro-mendonca) for the idea and initial coding: [Issue #135 & #153](https://github.com/qTranslate-Team/qtranslate-x/pull/153).
* Fix: pagination of posts under Query Mode of URL Modification (filter 'get_pagenum_link'): [Issue #155](https://github.com/qTranslate-Team/qtranslate-x/issues/155), [WP Topic](https://wordpress.org/support/topic/pagination-does-not-work-if-query-mode-used) and [WP Topic](https://wordpress.org/support/topic/navigation-problem-20).
* Fix: locale 'ja_JP' changed back to 'ja' as this is what WordPress uses. Files lang/qtranslate-ja_JP.mo/po renamed to qtranslate-ja.mo/po.

### 3.3 stable ###
* Includes all changes after version 3.2.9. Please, review [Release Notes](https://qtranslatexteam.wordpress.com/2015/03/30/release-notes-3-3).
* Translation: a lot of thanks to all translators contributed.

### 3.2.9.9.8 (3.3 RC1) ###
* Enhancement: admin message with a link to [Startup Guide](https://qtranslatexteam.wordpress.com/startup-guide/) on the first install.
* Fix: sub-domains should be external hosts [Issue #148](https://github.com/qTranslate-Team/qtranslate-x/issues/148).

### 3.2.9.9.7 alpha ###
* Fix: one more problem is discovered in function `qtranxf_filter_postmeta` for the case of empty $meta_key, reported in [Issue #138](https://github.com/qTranslate-Team/qtranslate-x/issues/138).

### 3.2.9.9.6 alpha ###
* Enhancement: override admin language from `$_POST['WPLANG']` in case user tries to change language using field 'Site Language' on page `/wp-admin/options-general.php`.
* Fix: action 'updated_postmeta' added to clear post meta data cache: [Issue #138](https://github.com/qTranslate-Team/qtranslate-x/issues/138).

### 3.2.9.9.5 alpha ###
* Feature: query argument `qtranslate-mode=raw` to retrieve a page without translation as suggested in [issue #133](https://github.com/qTranslate-Team/qtranslate-x/issues/133).
* Fix: use of post meta cache after its update, [Issue #138](https://github.com/qTranslate-Team/qtranslate-x/issues/138).
* Translation: German (de_DE) po/mo files updated. Thanks to Robert Skiba.
* Translation: Dutch (nl_NL) po/mo files updated. Thanks to Marius Siroen.

### 3.2.9.9.4 alpha ###
* Feature replaced: Visual Composer compatibility moved to a separate plugin: [WPBakery Visual Composer & qTranslate-X](https://wordpress.org/plugins/js-composer-qtranslate-x)
* Enhancement: filters 'gettext' and 'gettext_with_context' are moved to qtranslate_frontend.php, as they are not needed on admin side.
* Enhancement: js scripts in the [3rd-party integration framework](https://qtranslatexteam.wordpress.com/integration/).
* Fix: the list of available languages in the column 'Languages' of post listing.

### 3.2.9.9.3 alpha ###
* Enhancement: support for flags of type `svg`.
* Enhancement: improved report messages for action 'markdefault'.
* Enhancement: improved messages in column "Language" of post listing pages.
* Translation: German (de_DE) po/mo files updated. Thanks to Robert Skiba.
* Enhancement: another attempt to check `REDIRECT_STATUS` in function `qtranxf_can_redirect` to prevent unnecessary redirection if `mod_rewrite` is already doing redirection. In particular, this should help to troubleshoot internal server error as it was observed in [Issue #96](https://github.com/qTranslate-Team/qtranslate-x/issues/96).

### 3.2.9.9.2 alpha ###
* Fix: troublesome use of deprecated function `mysql_real_escape_string` is removed: [WP Topic](https://wordpress.org/support/topic/bulk-remove-language).

### 3.2.9.9.1 alpha ###
* Fix: alt attribute for flag icons in widget 'qTranslate Language Chooser': [WP Topic](https://wordpress.org/support/topic/flag-icons-%E2%80%93-missing-alt-attributes).
* Fix: title attribute for language menu items: [WP Topic](https://wordpress.org/support/topic/language-switcher-menu-flag-images-in-title).
* Feature: take language menu title from field 'Navigation Label' of menu editor: [WP Topic](https://wordpress.org/support/topic/change-the-menu-label-text).
* Feature: options 'names' and 'colon' in Language Menu configuration.
* Translation: .pot, .po and .mo files updated.

### 3.2.9.9.0 alpha ###
* Design: Java script interface for 3rd-party integration, functions: getLanguages, getFlagLocation, isLanguageEnabled, addLanguageSwitchBeforeListener, addLanguageSwitchAfterListener, enableLanguageSwitchingButtons.
* Translation: pot and po files updated.

### 3.2.9.8.9 alpha ###
* Feature: option 'Post Types' to exclude some post types from translation: [WP Topic](https://wordpress.org/support/topic/activate-translation-only-for-specific-post-types) and [WP Topic](https://wordpress.org/support/topic/disable-qtranslate-x-for-specific-custom-post).
* Feature: Visual Composer compatibility (experimental).
* Enhancement (cancelled previously changed at 3.2.9.8.8, it breaks some other places, needs more investigation): added check `isset($_SERVER['REDIRECT_STATUS'])` in function `qtranxf_can_redirect` to prevent another redirection if `mod_rewrite` is already doing redirection. In particular, this should help to troubleshoot internal server error 500 as it was observed in [Issue #96](https://github.com/qTranslate-Team/qtranslate-x/issues/96).


### 3.2.9.8.8 alpha ###
* Translation: Greek ('el_GR') predefined language added, thanks to [Marios Bekatoros](https://github.com/bekatoros).
* Translation: Arabic (ar) po/mo files updated. Thanks to Nedal Elghamry.
* Enhancement: added check `isset($_SERVER['REDIRECT_STATUS'])` in function `qtranxf_can_redirect` to prevent another redirection if `mod_rewrite` is already doing redirection. In particular, this should help to troubleshoot internal server error 500 as it was observed in [Issue #96](https://github.com/qTranslate-Team/qtranslate-x/issues/96).
* Feature: js functions `addLanguageSwitchBeforeListener` and `addLanguageSwitchAfterListener` is designed for other plugin integration, read [Integration](https://qtranslatexteam.wordpress.com/integration/) for more information. Thanks to [Dmitry](https://github.com/picasso) for the useful design discussion [Issue #128](https://github.com/qTranslate-Team/qtranslate-x/issues/128).
* Fix: more special cases for arrays in POST, [Issue #127](https://github.com/qTranslate-Team/qtranslate-x/issues/127).

### 3.2.9.8.5 alpha ###
* Fix: special cases for arrays in POST, [Issue #127](https://github.com/qTranslate-Team/qtranslate-x/issues/127) and [WP Topic](https://wordpress.org/support/topic/qtranslate-x-learndash-lms-quizzes).
* Translation: thanks to all translators contributed. po files updated with correct version number.

### 3.2.9.8.4 alpha ###
* Fix: taxonomy names with apostrophe and other special characters: [Issue #122](https://github.com/qTranslate-Team/qtranslate-x/issues/122).
* Fix: locale 'ja' changed to 'ja_JP'. Thanks to Yusuke Noguchi.
* Translation: thanks to all translators contributed.

### 3.2.9.8.3 alpha ###
* Feature: choice 'Single Language Mode' for option 'Editor Mode'.
* Enhancement: New release handling framework.
* Fix: function `qtranxf_sanitize_url`, thanks to [HAYASHI Ryo](https://github.com/ryo88c): [Issue #117](https://github.com/qTranslate-Team/qtranslate-x/pull/117).

### 3.2.9.8.2 alpha ###
* Feature: js function `addLanguageSwitchListener` is designed for other plugin integration, read [Integration](https://qtranslatexteam.wordpress.com/integration/) for more information.
* Fix: handling of cookie `qtrans_edit_language`.

### 3.2.9.8.1 alpha ###
* Fix: function `qtranxf_join_b`, test for `qtranxf_allthesame`.
* PHP version compatibility: syntax changed in '`qtranxf_collect_translations*`'.

### 3.2.9.8 alpha ###
* Improvement: TinyMCE handling is re-designed once again. [[WP Topic](https://wordpress.org/support/topic/default-wordpress-photo-gallery)] [[Issue #115](https://github.com/qTranslate-Team/qtranslate-x/issues/115)]
* Feature: new type of hook, displayHookAttrs, in `admin/js/common.js` to translate submit button texts (used in Woocommerce, for example).

### 3.2.9.7 alpha ###
* Translation: German (de_DE) po/mo files updated, thanks to Maurizio Omissoni.
* Translation: Italian (it_IT) po/mo files updated, thanks to Maurizio Omissoni.
* Feature: new front-end option 'Show content in an alternative language': [Issue #21](https://github.com/qTranslate-Team/qtranslate-x/issues/21).
* Improvement: error handling for gettext updates [#105](https://github.com/qTranslate-Team/qtranslate-x/pull/105) and [#113](https://github.com/qTranslate-Team/qtranslate-x/pull/113), and other minor code clean up.

### 3.2.9.6 alpha ###
* Translation: Dutch (nl_NL) po/mo files updated. Thanks to Marius Siroen.
* Translation: Portuguese (pt_PT) po/mo files updated. Thanks to Pedro Mendonça.
* Feature: framework for handling admin notices on new releases.
* Improvement: design of option handling: optimization and simplification in the code.
* Fix: replaced pre-defined language code 'pt-br' with 'pb': [Issue #104](https://github.com/qTranslate-Team/qtranslate-x/issues/104).
* Fix: hidden multilingual input fields moved right before their single-lingual originals: [WP Topic](https://wordpress.org/support/topic/problems-with-exisiting-posts).

### 3.2.9.5 ###
* Improvement: 'Code' column in the list of languages. Thanks to Pedro Mendonça for the [discussion](https://github.com/qTranslate-Team/qtranslate-x/issues/102).
* Fix: date/time formats containing backslashes: [Issue #99](https://github.com/qTranslate-Team/qtranslate-x/issues/99).

### 3.2.9.4 ###
* Translation: po files updated. Thanks to Pedro Mendonça for a [discussion](https://github.com/qTranslate-Team/qtranslate-x/pull/100).
* Fix: languages management problems: [Issue #102](https://github.com/qTranslate-Team/qtranslate-x/issues/102).

### 3.2.9.3 ###
* Feature: `qtrans_join` added to option 'Compatibility Functions'. [[Issue #106](https://github.com/qTranslate-Team/qtranslate-x/issues/106)]
* Translation: Portuguese (pt_PT) po/mo files updated. Thanks to Pedro Mendonça.
* Fix: various problems with new option save/load methods.
* Fix: CSS syntax. Thanks to [Michel Weimerskirch](https://github.com/mweimerskirch): [Issue](https://github.com/qTranslate-Team/qtranslate-x/commit/83b4a9b513e623df3e9800888c742683c51eed6a#commitcomment-10435679).

### 3.2.9.2 ###
* Feature: option "LSB Style" to customize Language Switching Buttons style.
* Feature: after the first activation, the current WordPress active language becomes the default one for qTranslate-X, instead of English. No other languages are added anymore.
* Improvement: option load and save algorithm re-designed.
* Fix: special cases for function `convertURL`.

### 3.2.9.1 ###
* Feature: Option "Highlight Style". Thanks to [Michel Weimerskirch](https://github.com/mweimerskirch).
* Fix: handling of relative urls in qtranxf_convertURL.
* Fix: default locale for Estonian is 'et_EE'.
* Improvement: more on 'URL Modification Mode' option Per-Domain.
* Translation: Arabic (ar) po/mo files updated. Thanks to Nedal Elghamry.
* Translation: Dutch (nl_NL) po/mo files updated. Thanks to Marius Siroen.

### 3.2.9 stable ###
* Improvement: function `convertURL` has been re-designed to take into account scheme, user, password and fragment correctly.
* Improvement: added "x-default" link `<link hreflang="x-default" rel="alternate" />` as suggested by [Google](https://support.google.com/webmasters/answer/189077).
* Feature: added exclusions to `qtranxf_convertFormat` for language-neutral date formats 'Z', 'c' and 'r' in addition to 'U' [[Issue #76](https://github.com/qTranslate-Team/qtranslate-x/issues/76)]
* Feature: variable `$url_info['set_cookie']` can be overridden via `qtranslate_detect_language` filter. [[WP Topic](https://wordpress.org/support/topic/do-not-switch-admin-language-when-changing-language-on-frontend)]
* Feature: admin notices for integrating plugins 'ACF qTranslate', 'All in One SEO Pack & qTranslate&#8209;X', 'Events Made Easy & qTranslate&#8209;X', 'qTranslate support for GravityForms', 'WooCommerce & qTranslate&#8209;X' and 'Wordpress SEO & qTranslate&#8209;X'.
* Feature: added URL folder `/oauth/` to the list of language-neutral URLs. [[Issue #81](https://github.com/qTranslate-Team/qtranslate-x/issues/81)]
* Maintenance: GitHub repository information in the header of qtranslate.php
* Performance: function `convertURL` now uses cached values of previously converted urls.
* Performance: a few other little performance improvements.
* Translation: Dutch (nl_NL) po/mo files updated. Thanks to Marius Siroen.
* Translation: French (fr_FR) po/mo files updated. Thanks to Sophie.
* Translation: Portuguese (pt_PT) po/mo files updated. Thanks to Pedro Mendonça.
* Fix: Query in `qtranxf_excludePages`. [[WP Topic](https://wordpress.org/support/topic/bug-in-qtranxf_excludepages)]
* Fix: Warning 'Undefined index: doing_front_end' reported in [WP Topic](https://wordpress.org/support/topic/notice-undefined-index-doing_front_end).
* Fix: time functions adjusted. [[WP Topic](https://wordpress.org/support/topic/old-get_the_date-bug-is-back)]
* Fix: custom menu item query 'setlang=no': [[Issue #80](https://github.com/qTranslate-Team/qtranslate-x/issues/80)]


### 3.2.7 stable ###
* Includes all changes after version 3.2.2.
* Improvement: added `removeContentHook` in `admin/js/common.js`. Thanks to [Tim Robertson](https://github.com/funkjedi): [[GitHub Issue #69](https://github.com/qTranslate-Team/qtranslate-x/pull/69)]
* Improvement: use of `nodeValue` instead of `innerHTML` in `addDisplayHook` of `admin/js/common.js`.
* Translation: Dutch (nl_NL) po/mo updated, thanks to Marius Siroen.
* Translation: pot/po files updated

### 3.2.6 ###
* Feature: replaced option 'Remove plugin CSS' with 'Head inline CSS'.
* Fix: problem with url like `site.com/en` without slash.

### 3.2.5 ###
* Feature: options of similar functionality of mqTranslate: 'Remove plugin CSS', 'Cookie Settings' and 'Translation of options'. Thanks to [Christophe](https://github.com/xhaleera)) for the initial [pull](https://github.com/qTranslate-Team/qtranslate-x/pull/63).
* Improvement: `qtrans_getLanguageName` added to option 'Compatibility Functions'.
* Fix: url like `site.com/en?arg=123` without slash before question mark now works correctly.

### 3.2.4 ###
* Feature: multiple sets of Language Switching Buttons per page. Enabled by default above metabox 'Excerpt'. Will be customizable later.

### 3.2.3 ###
* Improvement: auto-translation of metadata at front-end, filter `qtranxf_filter_postmeta`. [[Ticket](https://wordpress.org/support/topic/qtranslate-x-hero-header)]

### 3.2.2 stable ###
* Translation: Dutch (nl_NL) po/mo updated, thanks to Marius Siroen.
* Improvement: common.js modifications needed for plugin [All in One SEO Pack & qTranslate-X](https://wordpress.org/plugins/all-in-one-seo-pack-qtranslate-x/).
* Fix: non-standard host port handling. Thanks to Christophe. [[Ticket](https://github.com/qTranslate-Team/qtranslate-x/pull/59)]

### 3.2.1 stable ###
* Feature: added option "Hide Title Colon" for widget "qTanslate Language Chooser". [[Ticket](https://wordpress.org/support/topic/semicolon-is-being-added-to-the-widget-title)]
* Improvement: disabled browser redirection for WP_CLI. [[Ticket](https://github.com/qTranslate-Team/qtranslate-x/pull/57)]
* Fix: wp-admin/nav-menus.php: new menu items for pages get added with title already translated.

### 3.2 stable ###
* Includes all changes after version 3.1.
* Translation: Dutch (nl_NL) po/mo updated, thanks to Marius Siroen.
* Improvement: `add_filter('term_description')` at front-end. Thanks to [josk79](https://github.com/qTranslate-Team/qtranslate-x/pull/39).

### 3.2-b3 ###
* Feature: class `qtranxs-translatable` is introduced to distinct all translatable fields. Thanks to [Michel Weimerskirch](https://github.com/mweimerskirch).
* Improvement: `QTRANS_INIT` constant is now defined when "Compatibility Functions" is on. [[WP issue](https://wordpress.org/support/topic/urgent-problem-with-dynamic-widgets-plugin).]
* Improvement: various code improvements, search for '3.2-b3' tag to look them them up.

### 3.2-b2 ###
* Translation: Hungarian (hu_HU) po/mo updated, thanks to Németh Balázs.
* Translation: German (de_DE) po/mo updated, thanks to Maurizio Omissoni.
* Improvement: Basque language added to the pre-set list of languages, thanks to Xabier Arrabal.
* Improvement: 'Convert Database' options now also convert `postmeta.meta_value` database field.
* Fix: 'Convert Database' options would not work correctly for some options.
* Fix: `qtranxf_http_negotiate_language` used to return `en_US` when PHP supports function `http_negotiate_language`.

### 3.2-b1 ###
* Translation: Dutch (nl_NL) po/mo updated, thanks to Marius Siroen.
* Improvement: updated activation/migration messages with a link to [Migration from other multilingual plugins](https://qtranslatexteam.wordpress.com/2015/02/24/migration-from-other-multilingual-plugins/) publication.
* Improvement: updated "Compatibility Functions" option with `qtrans_split`.
* Fix: dealing with https and port 443.

### 3.1 stable ###
* Includes all changes after version 3.0.
* Maintenance: 'Translate Service' feature has been disabled, as the vast majority of people [surveyed](http://www.marius-siroen.com/qTranslate-X/TranslateServices/) declined it. Thanks to [Gunu (Marius Siroen)](https://profiles.wordpress.org/grafcom) who made this survey possible.

### 3.1-a1 ###
* Improvement: up to date code for `updateGettextDatabases` and cleaning up of a lot of code. Thanks to [Michel Weimerskirch](https://github.com/mweimerskirch).
* Translations: Croatian po/mo - thanks to Sheldon Miles.
* Translations: po/mo adjusted for a typo fixed. Thanks to [Michel Weimerskirch](https://github.com/mweimerskirch).
* Translations: default time format for Sweden changed - thanks to Tor-Björn.
* Fix: import/export from other qTranslate-ish forks.
* Fix: problem with menu editor under some configurations.

### 3.1-b4 ###
* Fix: 'Hide Title' in the widget. [WP topic](https://wordpress.org/support/topic/widget-cannot-save-titlecannot-uncheck-hide-title)
* Fix: corrected redirection in some peculiar cases.

### 3.1-b3 ###
* Fix: query to implement option 'Hide Content which is not available for the selected language'

### 3.1-b2 ###
* Feature: more on framework for integration with other plugins and themes.

### 3.1-b1 ###
* Feature: closing tag `[:]` for square bracket language encoding mode is introduced.
* Feature: options to convert database to/from square bracket only mode.
* Feature: new language encoding mode 'byline', particularly needed for Woocommerce integration.
* Improvement: altered the response of filter 'esc_html' to return a translation to current language instead of the default language.
* Feature: more on framework for integration with other plugins and themes.
* Fix: import from [mqTranslate](https://wordpress.org/support/plugin/mqtranslate) (thanks to [Christophe](https://github.com/xhaleera)).

### 3.0 stable ###
* Includes all changes after version 2.9.6.
* Please, do not forget to respond to [survey on 'Translate Service' feature](http://www.marius-siroen.com/qTranslate-X/TranslateServices/) by courtesy of [Gunu (Marius Siroen)](https://profiles.wordpress.org/grafcom), whose continuous help is much appreciated.
* Feature:  framework for integration with other plugins and themes.
* Maintenance: po/mo files updated.

### 2.9.8.9 alpha ###
* Feature: editing of menu item description on page /wp-admin/nav-menus.php.
* Feature: hooks for integration with other plugins
* Improvement: safer comment query with cache support when 'Hide Untranslated Content' is on. [issue #17](https://github.com/qTranslate-Team/qtranslate-x/issues/17)
* Compatibility: [PS Disable Auto Formatting](https://wordpress.org/plugins/ps-disable-auto-formatting/). [WP issue](https://wordpress.org/support/topic/incompatibility-with-ps-disable-auto-formatting)
* Maintenance: .pot and .po files updated with new untranslated strings.

### 2.9.8.8 alpha ###
* request for survey on ['Translate Service' feature](http://www.marius-siroen.com/qTranslate-X/TranslateServices/)

### 2.9.8.7 alpha ###
* the version can be downloaded here: [2.9.8.7 alpha](https://github.com/qTranslate-Team/qtranslate-x/archive/2.9.8.7.zip).
* more on proper detection of front-end vs back-end on AJAX calls.
* 'attr_title' is now translated in menu display

### 2.9.8.5 alpha ###
* more on option "Hide Content which is not available for the selected language"
* thanks to Marius Siroen for Dutch translation

### 2.9.8.4 alpha ###
* .pot/.po files in order. Thanks to [Pedro Mendonça](https://github.com/pedro-mendonca) for an extensive discussion on the best way to proceed with translations.
* added 500ms delay before page refresh after new tag insertion on wp-admin/edit-tags.php.

### 2.9.8.3 alpha ###
* Translations of captions and attributes in standard WP galleries, which is, in fact, much bigger change affecting many places. Need to re-test all carefully.
* improved run-time performance.
* some improvements on plugin translation as suggested by [Gunu](https://wordpress.org/support/profile/grafcom).

### 2.9.8.2 alpha ###
* updated "Compatibility Functions" option with `qtrans_generateLanguageSelectCode` and `qtrans_useCurrentLanguageIfNotFoundShowAvailable`.
* more on TinyMCE compatibility
* taxonomy editor pages improved to switch languages for additional display fields.

### 2.9.8.1 alpha ###
* URL of a custom menu item gets converted to active language, unless query argument 'setlang=no' is added.
* filter 'get_search_form' is no longer need, since we adjusted home_url() [issue #8](https://github.com/qTranslate-Team/qtranslate-x/issues/8)

### 2.9.8.0 alpha ###
* [plugin integration design](https://wordpress.org/support/topic/plugin-integration-1)
* po/mo files are updated. Translators needed.

### 2.9.7.9 beta ###
* more fixes for [issue #5](https://github.com/qTranslate-Team/qtranslate-x/issues/5).

### 2.9.7.8 beta ###
* fix for wrong language on [AJAX requests](https://wordpress.org/support/topic/qtranslate-x-im8-qtranslate-woocommerce-bug)

### 2.9.7.7 beta ###
* menu items with empty text for the current language are not shown any more ([WP issue](https://wordpress.org/support/topic/hide-specific-menu-item-for-1-language)).
* enable Language Switching Buttons on menu editor page. Fields "Navigation Label", "Title Attribute" and "Description" now respond to Language Switching Buttons.
* option "Custom Pages" to enable Language Switching Buttons on custom-defined pages.
* [per-domain URL modification mode]https://wordpress.org/support/topic/qtranslate-tld-url-change-mode).
* split the qtranslate.js script into a few scripts in `admin/js/` folder to be loaded depending on the page which needs them.
* updated qtranslate.pot and fixed proper translation of various strings in the code (thanks to [Pedro Mendonça](https://github.com/pedro-mendonca)).
* fix for when cookie 'qtrans_edit_language' contains unavailable language.
* various performance improvements.
* option "Editor Raw Mode" to be able to edit database text entries as they are, with language tag separators, without Language Switching Buttons.
* fix for [random `<p>` in TinyMCE editors](https://github.com/qTranslate-Team/qtranslate-x/issues/5).
* fix for login problem when `siteurl` option is different from 'home'.
* compatibility with [Qtranslate Slug](https://wordpress.org/plugins/qtranslate-slug/).
* fix for [blank translations](https://wordpress.org/support/topic/duplicates-everything-doesnt-work-all-times).
* fix for `&amp;` in url [problem](https://wordpress.org/support/topic/strange-behavior-8).
* fix for option [Hide Untranslated Content](https://wordpress.org/support/topic/cant-hide-the-non-existent-language-posts).
* compatibility with plugin [Groups](https://wordpress.org/plugins/groups/), [issue](https://wordpress.org/support/topic/dropdown-doesnt-display-while-plugin-groups-is-active)

### 2.9.6 stable ###
* more fixes for `<!--more-->` and `<!--nextpage-->` tags and parsing multilingual texts.

### 2.9.5 ###
* more fixes for `<!--more-->` and `<!--nextpage-->` tags.

### 2.9.4 ###
* fix for https://wordpress.org/support/topic/comment-shows-404-error

### 2.9.3 ###
* "Language Switcher" menu options, read [FAQ](https://wordpress.org/plugins/qtranslate-x/faq/) for more information.
* fix for too early call to `current_user_can`, which caused a debug notice from within some other plugins.
* fix for https://wordpress.org/support/topic/editor-adds-characters-before-text

### 2.9.2 ###
* Option "Compatibility Functions" to enable former qTranslate function names: qtrans_getLanguage, qtrans_convertURL, qtrans_use, qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage, qtranxf_useTermLib and qtrans_getSortedLanguages
* "Language Switcher" menu options: flags=[yes|no], type=[LM|AL]. They can be used in a query string in URL field of Language Menu.

### 2.9.1 ###
* JS bug fixed, which would not show any field value if no languages are yet configured for that field.

### 2.9 ###
* ability to enable "Custom Fields" by either "id" or "class" attribute.
* ability to specify filters, which other theme or plugins define, to pass relevant data through the translation.
* support for `<!--more-->` and `<!--nextpage-->` tags.
* language cookie are renamed to minimize possible interference with other sites.

### 2.8 ###
* added option "Show displayed language prefix when content is not available for the selected language".
* compatibility with "BuddyPress" plugin and various improvements.
* custom CSS for "qTranslate Language Chooser" widget configurable via its properties.
* now always redirects to a canonical URL, as defined by options, before displaying a page.
* use of cookies to carry the language chosen from session to session.

### 2.7.9 ###
* [this does not work yet] created wrappers to make former qTranslate function names available: qtrans_getLanguage, qtrans_convertURL, qtrans_use, qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage.

### 2.7.8 ###
* user-friendly activation hook to deactivate/import/export other qTranslate forks.
* import/export settings from other forks

### 2.7.7 ###
* improved automatic downloading of gettext databases from WP repository.
* translation of "Site Title" and "Tagline" in Settings->General (/wp-admin/options-general.php).

### 2.7.6 ###
* Option "Custom Field": theme custom fields can be translatable.

### 2.7.5 ###
* handling multiple tinyMCE editors, as some themes have it. It will now make all fields in page and post editors of class "wp-editor-area" translatable.

### 2.7.4 ###
* fix permalink on edit pages
* disabled autosave script in editors, since it saves the active language only and sometimes hardly messes it up later.

### 2.7.3 ###
* fixes for flag path, when WP is not in /. Permalink on edit pages is still broken, apparently has always been for this case.
* various minor improvements

### 2.7.2 ###
* bug fixer

### 2.7.1 ###
* enabled translation of image 'alt' attribute.
* corrected behaviour of category and tag editing pages when admin language is not the default one.
* hid 'Quick Edit' in category and tag editing pages since it does not work as user would expect. One has to use "Edit" link to edit category or tag name.

### 2.7 ###
* enabled translations of image captions, titles and descriptions (but not 'alt').

### 2.6.4 ###
* improved Description, FAQ and other documentation.

### 2.6.3 (2014-12) (initial changes after zTranslate) ###

* added "Language Switcher" menu item to WP menu editing screen
* currently editing language is memorized in cookies and preserved from one post to another
* on the first page load, the default language is now activated instead of the last language
* full screen mode for tinyMCE integrated properly
* more translation on tag and category editor pages
* added 'post_title' filter to translate all titles fetched for display purpose
* fixed problem with comment date display in some themes

## Known Issues ##

It is important to review the list of [Known Issues](https://qtranslatexteam.wordpress.com/known-issues/) before starting using the plugin.

## Credentials ##

Please, review the [credentials page](https://qtranslatexteam.wordpress.com/credentials/) at [qTranslate-X explained](https://qtranslatexteam.wordpress.com/about/) website.

## Desirable Unimplemented Features ##

A list of [desirable features](https://qtranslatexteam.wordpress.com/desirable/) is maintained at [qTranslate-X explained](https://qtranslatexteam.wordpress.com/about/) website.
