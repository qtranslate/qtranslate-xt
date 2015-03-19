# qTranslate X #
Developed by: qTranslate Team based on original code by Qian Qin
Contributors: johnclause, chineseleper, Vavooon, grafcom
Tags: multilingual, language, admin, tinymce, bilingual, widget, switcher, i18n, l10n, multilanguage, translation
Requires at least: 3.9
Tested up to: 4.1.1
Stable tag: 3.2.9
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

The following plugins provide integration of qTranslate-X with other popular plugins:

- [ACF qTranslate](https://wordpress.org/plugins/acf-qtranslate/) for [Advanced Custom Fields](https://wordpress.org/plugins/advanced-custom-fields/). [[GitHub](https://github.com/funkjedi/acf-qtranslate)]
- [All in One SEO Pack & qTranslate-X](https://wordpress.org/plugins/all-in-one-seo-pack-qtranslate-x/) for [All in One SEO Pack](https://wordpress.org/plugins/all-in-one-seo-pack). [[GitHub](https://github.com/qTranslate-Team/all-in-one-seo-pack-qtranslate-x)]
- [Events Made Easy & qTranslate-X](https://wordpress.org/plugins/events-made-easy-qtranslate-x/) for [Events Made Easy](https://wordpress.org/plugins/events-made-easy/). [[GitHub](https://github.com/qTranslate-Team/events-made-easy-qtranslate-x)]
- [qTranslate support for GravityForms](https://wordpress.org/plugins/qtranslate-support-for-gravityforms) for [Gravity Forms Directory](https://wordpress.org/plugins/gravity-forms-addons/). [[GitHub](https://github.com/mweimerskirch/wordpress-qtranslate-support-for-gravityforms)]
- [WooCommerce & qTranslate-X](https://wordpress.org/plugins/woocommerce-qtranslate-x/) for [WooCommerce - excelling eCommerce](https://wordpress.org/plugins/woocommerce/). [[GitHub](https://github.com/qTranslate-Team/woocommerce-qtranslate-x)]
- [Wordpress SEO & qTranslate-X](https://wordpress.org/plugins/wp-seo-qtranslate-x/) for [WordPress SEO by Yoast](https://wordpress.org/plugins/wordpress-seo/). [[GitHub](https://github.com/qTranslate-Team/wp-seo-qtranslate-x)]

Below is the list of plugins recently made compatible with qTranslate-X without an additional integrating plugin, as reported by users:

- [ALO EasyMail Newsletter](https://wordpress.org/support/plugin/alo-easymail)
- [BuddyPress](https://wordpress.org/plugins/buddypress/) and its satellites.
- [Cookie Law Info](https://wordpress.org/plugins/cookie-law-info/)
- [Google XML Sitemaps v3 for qTranslate](https://wordpress.org/plugins/google-xml-sitemaps-v3-for-qtranslate/)
- [Groups](https://wordpress.org/plugins/groups/)
- [Multiple content blocks](https://wordpress.org/plugins/multiple-content-blocks/)
- [WP Photo Album Plus](https://wordpress.org/plugins/wp-photo-album-plus/)

If you encounter a conflicting a plugin, please let us know, and meanwhile try to use other plugin of similar functionality, if possible.

You may still find a lot of useful information through reading [qTranslate](https://wordpress.org/plugins/qtranslate/ "Original qTranslate plugin")'s original documentation, which is not duplicated here in full. There are also other plugins, which offer multilingual support, but it seems that Qian Qin has very good original back-end design, and many people have been pleasantly using his plugin ever since. It stores all translations in the same single post, which makes it easy to maintain and to use it with other plugins. However, the user interface of former qTranslate got out of sync with the recent versions of Wordpress, especially after WP went to TinyMCE 4. There is a number of forks of qTranslate, see for example, [mqTranslate](https://wordpress.org/plugins/mqtranslate/ "mqTranslate plugin"), [qTranslate Plus](https://wordpress.org/plugins/qtranslate-xp/ "qTranslate Plus plugin") and [zTranslate](https://wordpress.org/plugins/ztranslate/ "zTranslate plugin"). They all try to fix qTranslate's user interface preserving its original back-end, which is what this plugin does too. This plugin is a hybrid of all of them and fixes a few bugs in each of them. It also has many new features too, like theme custom translatable fields, for example. We hope that this plugin is the most complete working version which combines the best features of [qTranslate](https://wordpress.org/plugins/qtranslate/ "Original qTranslate plugin"), [mqTranslate](https://wordpress.org/plugins/mqtranslate/ "mqTranslate fork"), [qTranslate Plus](https://wordpress.org/plugins/qtranslate-xp/ "qTranslate Plus fork") and [zTranslate](https://wordpress.org/plugins/ztranslate/ "zTranslate fork").

We organized an anonymous entity [qTranslate Team](https://github.com/qTranslate-Team) to maintain a joint authority of all qTranslate-ish plugins. Anyone is welcome to join with a contribution. Participating plugin authors should share the support efforts for each other.

GitHub repository is available: https://github.com/qTranslate-Team/qtranslate-x.git

We thank our sponsors for persistent help and support:

* [Citizens Law Group](http://www.citizenslawgroup.com "Chicago Bankruptcy Attorney - Citizens Law Group")
* [Gunu](https://profiles.wordpress.org/grafcom "Gunu (Marius Siroen)") (Marius Siroen)
* [OptimWise](http://optimwise.com "OptimWise web design")
* [Pedro Mendonça](https://github.com/pedro-mendonca)
* [pictibe Werbeagentur](http://www.pictibe.de "pictibe Werbeagentur Köln Webdesign")

## Installation ##

Installation of this plugin is no different from any other plugin:

1. Download the plugin from [here](http://wordpress.org/plugins/qtranslate-x/ "qTranslate-X").
1. Extract all the files.
1. Upload everything (keeping the directory structure) to the `/wp-content/plugins/` directory.
1. Deactivate plugin qTranslate, mqTranslate, qTranslate Plus, or zTranslate, if you are running any.
1. Activate qTranslate-X through the 'Plugins' configuration page in WordPress.
1. Open Settings->Languages configuration page and add/delete/disable any languages you need.
1. Add the "qTranslate Language Chooser" widget or "Language Switcher" menu item to let your visitors switch the language.
1. You may use [Google XML Sitemaps v3 for qTranslate](https://wordpress.org/plugins/google-xml-sitemaps-v3-for-qtranslate/) plugin to rebuild your XML sitemap for better SEO support.
1. Configure theme custom fields to be translatable if needed (Settings -> Languages: "Custom Fields").
1. Upgrading from [qTranslate](https://wordpress.org/plugins/qtranslate/ "qTranslate original plugin") required no additional actions, qTranslate-X will continue to work from the database entries of qTranslate. One may also go back to qTranslate at any time. Upgrading from other qTranslate forks may require re-configuration of the languages and taxonomies names, unless you rename corresponding database entries directly.

## Frequently Asked Questions ##

### Is my language supported or included? ###

Yes, all languages are supported and more and more get included. If yours is not included, you can easily add it through the Language Manager. If you are a native speaker of that language, consider sending us the information to be included permanently in the plugin configuration.


### What language switching methods are available at front end? ###

- Add menu item "Language Switcher" to an appropriate menu on your site. It has a few customizable options as described in other FAQ topics or embedded help text.
- Add widget "qTranslate Language Chooser" to an appropriate widget area on your site. It has a few customizable options as described in other FAQ topics or embedded help text.
- Use direct call to `qtranxf_generateLanguageSelectCode($type,$id)` in your templates. Argument `$type` currently accepts 'image', 'text', 'both' and 'dropdown' choices, which match the choices available in "qTranslate Language Chooser" widget. Example: `<?php echo qtranxf_generateLanguageSelectCode('both'); ?>`. You can change the look of language select list via CSS entries.


### I used to make direct calls to one of `qtrans_*` functions in my theme/plugin, but now those functions are not available. ###

Wordpress policy prohibits use of the same function names if they already defined in other plugins, since this possibly leads to user-unfriendly fatal errors. That is why all functions with prefix `qtrans_` were renamed to have prefix `qtranxf_`. However, once the plugin is running and all other conflicting plugins are disabled, you can turn on option "Compatibility Functions" and a number of former qTranslate methods with prefix `qtrans_` become available again. This ensures compatibility with other plugins and themes that used direct calls to qTranslate methods in their code.


### Is it possible to translate theme custom fields? ###

Yes, some themes put additional text fields per page or per post. By default, those fields have no way to respond to language switching buttons in editors. However, you may enter "id" or "class" name attribute of those fields into "Custom Fields" section of "Languages" configuration page in "Settings", and they will then respond to the language switching buttons allowing you to enter different text for each language. To lookup "id" or "class", right-click on the field in the post or the page editor, choose "Inspect Element", and look for which attributes are defined for that field. If you cannot uniquely distinct the field neither by if nor by class, report on the forum threads.

The theme must pass those values through [translation](http://codex.wordpress.org/Function_Reference/_2) function `__()` before displaying on the front-end output. If this is not done, you will see the text of all languages displayed one after another. Most themes use `__()` translation by default, otherwise you may ask theme author to make this little modification for each field you need to be translatable. However, sometimes, they pass a value through  'apply_filters()' function before displaying the value, and then you may put that filter name into configuration filed "Custom Filters" to get the value translated properly.

The following fields are pre-configured to be translatable by default:

- all input fields of class "wp-editor-area", which normally include all TinyMCE visual editors.
- fields with the following id: "title", "excerpt", "attachment_caption", "attachment_alt".

This applies to post, pages and media editors (/wp-admin/post*). 

### How do I translate custom configuration fields, which are not handled by language switch buttons? ###

Some themes have additional to the standard WP design fields, which need to be translated. In such a case, enter all translations in one field using syntax like this:

`<!--:en-->English Text<!--:--><!--:de-->Deutsch<!--:-->`

or like this

`[:en]English Text[:de]Deutsch[:]`

You may also embed language-neutral text in-between the language-specific content, like this:

`<html-language-neutral-text>[:en]English Text[:]<html-language-neutral-text>[:de]Deutsch[:]<html-language-neutral-text>`

Note that closing tag right before the opening tag is redundant. For example, the following encoding is equivalent to the example above:

`[:en]English Text[:][:de]Deutsch[:]`

Two encoding modes are interchangeable, although most people prefer to use square-bracket style '[:]', because it is shorter and easier to edit.

If a theme uses `__()` [translate](http://codex.wordpress.org/Function_Reference/_2 "WP Function 'translate'") function before displaying those fields, then they will be shown correctly, otherwise suggest theme author to put `__()` calls in. Most themes do it this way.

### Can I change the look of Language Switcher Menu? ###

The following query options can be typed in the field "URL" of "Language Menu" custom menu item, after "#qtransLangSw?", separated by "&", same way as options are provided on a query string:

- type=[LM|AL] - type of menu:
 - "LM" - Language Menu (default).
 - "AL" - Alternative Language: the top menu entry displays the first available language other than the current.

- title=[none|Language|Current] - title text of the top item:
 - "Language" - word "Language" translated to current language (default).
 - "none" - no title in the top of menu, flag only.
 - "Current" - displays current language name.

- flags=[none|all|items] - the way to display language flags:
 - "none" - no flag is shown in any item, including the top item.
 - "all" - all items show flag, including the top item.
 - "items" - only sub-items show corresponding flag, top item does not.

- current=[shown|hidden] - whether to display the current language in the menu.

For example, to show flag only in the top language menu item, enter `#qtransLangSw?title=none`, if in addition to this current language is not needed to be shown, enter `#qtransLangSw?title=none&current=hidden`, and so on.

We understand that this is not a very user-friendly way to adjust the options, but it works, and we will provide a better in-editor interface to specify them in the future.

### How can I prevent URL of a custom menu item from being converted ###

URL of a custom menu item gets converted to a URL for active language according to option "URL Modification Mode", unless query argument 'setlang=no' is added to the URL typed in. For example, if URL of a menu item is "http://example.com" change it to "http://example.com?setlang=no", or if it already has some query like this "http://example.com?arg=value", then change it to "http://example.com?arg=value&setlang=no". The additional query 'setlang=no' is always removed when the item gets rendered on a web page, for example,

* "http://example.com" is rendered language-encoded, like "http://example.com/en".
* "http://example.com?setlang=no" is rendered as "http://example.com" without language encoding and argument 'setlang=no' removed.
* "http://example.com?arg=value&setlang=no" is rendered as "http://example.com?arg=value" without language encoding and argument 'setlang=no' removed.

### Can I enable Language Switching Buttons on my plugin custom page? ###

Yes, enter the relevant and distinctive part of your page URL into "Custom Pages" configuration option. When page is loaded, two Java scripts will be added, "admin/js/common.js" and "admin/js/edit-custom-page.js", from which you may figure out how it works. The Language Switching Buttons will control fields listed in "Custom Fields" option. Those fields will now store the input for all enabled languages. It is up to the theme and other relevant plugins, if those field values will show up translated on the front-end. Some theme and plugins pass the values through `__()` translation function and then values are translated. They might use `apply_filters` method, and then name of that filter can be listed in "Custom Filters" configuration option, in order to get the field translated on the front-end.

If your case is still cannot be handled in this general way, you may develop your own Java script, similar to  "admin/js/edit-custom-page.js", and load it from your own file path using "qtranslate_custom_admin_js" hook. Looking through other "admin/js/edit-*.js" scripts may give you an idea how to do yours.

This is a work in progress and any suggestions are appreciated. We will probably end up using some kind of an xml configuration file customizable per each plugin/theme which needs to be integrated. Such an xml-file will list pages with queries affected ("Custom Pages" option for now) along with ids and classes of fields on each page to have multilingual data either for editing or for display.

Ideally, only such an xml configuration file will need to be created in order to integrate a plugin or theme without additional coding.

### How can I customize menu depending on the language? ###

If you wish a menu item not to show up for a specific language, remove its translation for that language from "Navigation Label" field in menu editor.

### Can I translate slugs? ###

Plugin [Qtranslate Slug](https://wordpress.org/plugins/qtranslate-slug/) is semi-integrated, will work on some configurations, but it is not safe, generally you will end up with some problems. It needs a better integration with qTranslate-X.

### Can I build Google XML Sitemap, including pages with all different languages? ###

Use plugin [Google XML Sitemaps v3 for qTranslate](https://wordpress.org/plugins/google-xml-sitemaps-v3-for-qtranslate/)

### How do I customize images for flags? ###

If you wish to use different flag images, point option "Flag Image Path" to your own folder, containing custom images outside of "plugins" or "themes" folders, where it will not be overridden during an update. Most people would put it somewhere under "uploads" folder.

### After activation qTranslate-X my front page goes into an infinite redirection loop. ###

qTranslate-X redirects to a canonical URL before rendering a page, which is necessary for some plugins ([BuddyPress](https://wordpress.org/plugins/buddypress/), for example) to work correctly. Canonical URL is defined based on options "Site Address (URL)" and "WordPress Address (URL)" from page /wp-admin/options-general.php, option "URL Modification Mode" from /wp-admin/options-general.php?page=qtranslate-x.

If .htaccess then redirects to a different URL, an infinite redirection loop may occur, which can be fixed after proper editing of .htaccess file. In most cases, the default .htaccess from hosting service provider works correctly.

### What is wrong with the original qTranslate? ###

qTranslate still works fine at frontend, except one known to me bug of incorrect date display in comments for some themes. However, its backend breaks tinyMCE content editor in post editing page. Many people have been reporting the problems, but the author keeps silence. qTranslate-X uses the same database backend, and updated admin interface with a slightly different design.

### Does qTranslate-X offer anything new besides bug fixes of qTranslate? ###

Yes, there is a number of new features, mostly of a convenience significance, which includes, but not limited to:

* A different design of language switching via conveniently located buttons (same way as it is done on [zTranslate](https://wordpress.org/plugins/mqtranslate/ "zTranslate plugin"). No multiple lines for title fields anymore. This design simplifies backend programming and is less likely to be broken on future WP changes.
* "Language Switcher" menu item on WP menu editing screen.
* Category and tag names in the lists on editing pages also respond to language switching and will display the taxonomy names in the currently editing language.
* Theme custom fields can be made translatable in addition to the default translatable fields.


### Does qTranslate-X preserve all the original functionality of qTranslate? ###

The correct strict answer would be 'No', although some sites may never notice it. What is modified, changed for a reason to provide better support for WP general design and policies, and to ensure better survivability on WP, themes and other plugins updates. While it may cause temporary grief and pain, it should work out better in a long run. Below is a list of the most important changes.

- behaviour of function `home_url()` is changed to consistently and always return language-enabled URL. This allowed to provide better compatibility with other plugins and themes, some of which used to modify their code to offset inconsistent behaviour of former `home_url()`. Those tricky changes will have to be now undone.
- language detection algorithm is modified to ensure canonical URL to be processed, otherwise we first redirect to a canonical URL.
- language detection within AJAX calls has been improved and custom compatibility modifications in other plugins and themes may now become unnecessary.


### How do I read the FAQ of the original qTranslate? ###

One can find the original qTranslate FAQ [here](https://wordpress.org/plugins/qtranslate/faq) and support forum [here](https://wordpress.org/support/plugin/qtranslate).


## Upgrade Notice ##

* Upgrading from [qTranslate](https://wordpress.org/plugins/qtranslate/ "qTranslate original plugin") and [zTranslate](https://wordpress.org/plugins/ztranslate/ "zTranslate plugin") requires no additional actions, qTranslate-X will continue to work from the database entries of qTranslate. One may also go back to qTranslate at any time.
* Upgrading from other qTranslate forks also painless with an additional steps of configuration import and using 'Convert Database' option. One may also go back at any time using configuration export and 'Convert Database' option.
* Former page and post translations are untouched and preserved in any case, except when using 'Convert Database' option.
* Upgrading from other multilingual frameworks will require custom re-configuration. We suggest to search for a plugin, which may be already implemented to transfer the translations to qTranslate or to qTranslate-X. If a plugin works for one, it should work for other too, since qTranslate-X and qTranslate share the same database structures.
* Additional information is available on [migration notes](https://qtranslatexteam.wordpress.com/2015/02/24/migration-from-other-multilingual-plugins/).

## Screenshots ##

1. Editing screen showing the buttons to switch the languages. Pressing a button does not make a call to the server, the editing happens locally, until "Update" button is pressed, the same way as it is for one language.
2. Language Management Interface

## Changelog ##

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
