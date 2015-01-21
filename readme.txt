=== qTranslate X ===
Developed by: qTranslate Team based on original code by Qian Qin
Contributors: johnclause, chineseleper, Vavooon
Tags: multilingual, language, admin, tinymce, bilingual, widget, switcher, i18n, l10n, multilanguage, translation
Requires at least: 3.9
Tested up to: 4.1
Stable tag: 2.9.6
License: GPLv3 or later
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=QEXEK3HX8AR6U
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Adds user-friendly and database-friendly multilingual content management and translation support. It is a fork and a bug fixer of qTranslate.

== Description ==

This plugin is a descendant of [qTranslate](https://wordpress.org/plugins/qtranslate/ "Original qTranslate plugin"), which has apparently been abandoned by the original author, [Qian Qin](http://www.qianqin.de/qtranslate/ "the original author of qTranslate"). 

While the back-end database framework is left almost intact and fully compatible with former qTranslate, the design of editors is drastically changed and improved to be much less vulnerable to WP updates. Instead of seeing multiple lines per each language for title, qTranslate-X provides language switching buttons, which, once pressed, make all the text fields to be filled with the language chosen. The instant language change happens locally in your browser without sending an additional request to the server.

qTranslate-X makes creation of multilingual content as easy as working with a single language. Here are some features:

- One-click local switching between the languages - Changing the language as easy as switching between Visual and HTML.
- Language customizations without changing the .mo files - It stores all the translations in the same post fields, while shows it to user for editing one by one depending on the language to edit chosen.
- In-line syntax '`<!--:en-->English Text<!--:--><!--:de-->Deutsch<!--:-->`' or '`[:en]English Text[:de]Deutsch`' for theme-custom fields gets them translated. See [FAQ](https://wordpress.org/plugins/qtranslate-x/faq/ "qTranslate-X FAQ") for more information.
- Multilingual dates out of the box - Translates dates and time for you.
- Theme custom fields can be configured to be translatable too.
- Comes with a number of languages already built-in - English, German, Simplified Chinese, for example, and many more.
- Choose one of 3 Modes to make your URLs look pretty and SEO-friendly. - The simple and beautiful `/en/foo/`, or nice and neat `en.yoursite.com`, and everywhere compatible `?lang=en`.
- One language for each URL - Users and SEO will thank you for not mixing multilingual content.
- qTranslate-X supports unlimited number of languages, which can be easily added/modified/deleted via a comfortable Configuration Page at Settings->Languages.
- Custom CSS for "qTranslate Language Chooser" widget configurable via its properties.
- Menu item "Language Switcher" to enable language choosing from a menu.
- Use [Google XML Sitemaps v3 for qTranslate](https://wordpress.org/plugins/google-xml-sitemaps-v3-for-qtranslate/) plugin to rebuild your XML sitemap for better SEO support.
- Use plugin [Qtranslate Slug](https://wordpress.org/plugins/qtranslate-slug/) if you need to translate slugs.

You may still find a lot of useful information through reading [qTranslate](https://wordpress.org/plugins/qtranslate/ "Original qTranslate plugin")'s original documentation, which is not duplicated here in full. There are also other plugins, which offer multilingual support, but it seems that Qian Qin has very good original back-end design, and many people have been pleasantly using his plugin ever since. It stores all translations in the same single post, which makes it easy to maintain and to use it with other plugins. However, the user interface of former qTranslate got out of sync with the recent versions of Wordpress, especially after WP went to TinyMCE 4. There is a number of forks of qTranslate, see for example, [mqTranslate](https://wordpress.org/plugins/mqtranslate/ "mqTranslate plugin"), [qTranslate Plus](https://wordpress.org/plugins/qtranslate-xp/ "qTranslate Plus plugin") and [zTranslate](https://wordpress.org/plugins/ztranslate/ "zTranslate plugin"). They all try to fix qTranslate's user interface preserving its original back-end, which is what this plugin does too. This plugin is a hybrid of all of them and fixes a few bugs in each of them. It also has many new features too, like theme custom translatable fields, for example. We hope that this plugin is the most complete working version which combines the best features of [qTranslate](https://wordpress.org/plugins/qtranslate/ "Original qTranslate plugin"), [mqTranslate](https://wordpress.org/plugins/mqtranslate/ "mqTranslate fork"), [qTranslate Plus](https://wordpress.org/plugins/qtranslate-xp/ "qTranslate Plus fork") and [zTranslate](https://wordpress.org/plugins/ztranslate/ "zTranslate fork").

We suggest all mentioned authors to get together and to continue supporting one single qTranslate-ish plugin in a joint effort.

GitHub repository is available: https://github.com/qTranslate-Team/qtranslate-x.git

== Installation ==

Installation of this plugin is no different from any other plugin:

1. Download the plugin from [here](http://wordpress.org/plugins/qtranslate-x/ "qTranslate-X").
1. Extract all the files.
1. Upload everything (keeping the directory structure) to the `/wp-content/plugins/` directory.
1. Deactivate plugin qTranslate, mqTranslate, qTranslate Plus, or zTranslate, if you running any.
1. Activate qTranslate-X through the 'Plugins' configuration page in WordPress.
1. Open Settings->Languages configuration page and add/delete/disable any languages you need.
1. Add the "qTranslate Language Chooser" widget or "Language Switcher" menu item to let your visitors switch the language.
1. You may use [Google XML Sitemaps v3 for qTranslate](https://wordpress.org/plugins/google-xml-sitemaps-v3-for-qtranslate/) plugin to rebuild your XML sitemap for better SEO support.
1. Configure theme custom fields to be translatable if needed (Settings -> Languages: "Custom Fields").
1. Upgrading from [qTranslate](https://wordpress.org/plugins/qtranslate/ "qTranslate original plugin") required no additional actions, qTranslate-X will continue to work from the database entries of qTranslate. One may also go back to qTranslate at any time. Upgrading from other qTranslate forks may require re-configuration of the languages and taxonomies names, unless you rename corresponding database entries directly.

== Frequently Asked Questions ==

= Is it possible to translate theme custom fields? =

Yes, some themes put additional text fields per page or per post. By default, those fields have no way to respond to language switching buttons in editors. However, you may enter "id" or "class" name attribute of those fields into "Custom Fields" section of "Languages" configuration page in "Settings", and they will then respond to the language switching buttons allowing you to enter different text for each language. To lookup "id" or "class", right-click on the field in the post or the page editor, choose "Inspect Element", and look for which attributes are defined for that field. If you cannot uniquely distinct the field neither by if nor by class, report on the forum threads.

The theme must pass those values through [translation](http://codex.wordpress.org/Function_Reference/_2) function `__()` before displaying on the front-end output. If this is not done, you will see the text of all languages displayed one after another. Most themes use `__()` translation by default, otherwise you may ask theme author to make this little modification for each field you need to be translatable. However, sometimes, they pass a value through  'apply_filters()' function before displaying the value, and then you may put that filter name into configuration filed "Custom Filters" to get the value translated properly.

The following fields are pre-configured to be translatable by default:

- all input fields of class "wp-editor-area", which normally include all TinyMCE visual editors.
- fields with the following id: "title", "attachment_caption", "attachment_alt".

This applies to post, pages and media editors (/wp-admin/post*). 

= How do I translate custom configuration fields, which are not handled by language switch buttons? =

Some themes have additional to the standard WP design fields, which need to be translated. In such a case, enter all translations in one field using syntax like this:

`<!--:en-->English Text<!--:--><!--:de-->Deutsch<!--:-->`

or like this

`[:en]English Text[:de]Deutsch`

If a theme uses `__()` [translate](http://codex.wordpress.org/Function_Reference/_2 "WP Function 'translate'") function before displaying those fields, then they will be shown correctly, otherwise suggest theme author to put `__()` calls in. Most themes do it this way.

The '`[:]`' syntax works well for one-line text fields, while '`<!--:-->`' syntax is more suitable for text areas.

= Can I change the look of Language Switcher Menu? =

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

We understand that this is not a very user-friendly way to adjust the options, but it works, and we will provide a better in-editor interface to specify them in the future.

= Can I enable Language Switching Buttons on my plugin's custom page? =

Yes, enter the relevant and distinctive part of your page URL into "Custom Pages" configuration option. When page is loaded, two Java scripts will be added, "admin/js/common.js" and "admin/js/edit-custom-page.js", from which you may figure out how it works. The Language Switching Buttons will control fields listed in "Custom Fields" option. Those fields will now store the input for all enabled languages. It is up to the theme and other relevant plugins, if those field values will show up translated on the front-end. Some theme and plugins pass the values through `__()` translation function and then values are translated. They might use `apply_filters` method, and then name of that filter can be listed in "Custom Filters" configuration option, in order to get the field translated on the front-end.

If your case is still cannot be handled in this general way, you may develop your own Java script, similar to  "admin/js/edit-custom-page.js", and load it from your own file path using "qtranxf_custom_admin_js" hook. Looking through other "admin/js/edit-*.js" scripts may give you an idea how to do yours.

= How can I customize menu depending on the language? =

If you wish a menu item not to show up for a specific language, remove its translation for that language from "Navigation Label" field in menu editor.

= Can I translate slugs? =

Use plugin [Qtranslate Slug](https://wordpress.org/plugins/qtranslate-slug/).

= Can I build Google XML Sitemap, including pages with all different languages? =

Use plugin [Google XML Sitemaps v3 for qTranslate](https://wordpress.org/plugins/google-xml-sitemaps-v3-for-qtranslate/)

= How do I customize images for flags? =

If you wish to use different flag images, point option "Flag Image Path" to your own folder, containing custom images outside of "plugins" or "themes" folders. Most people would put it somewhere under "uploads" folder.

= What is wrong with the original qTranslate? =

qTranslate still works fine at frontend, except one known to me bug of incorrect date display in comments for some themes. However, its backend breaks tinyMCE content editor in post editing page. Many people have been reporting the problems, but the author keeps silence. qTranslate-X uses the same database backend, and updated admin interface with a slightly different design.

= Does qTranslate-X offer anything new besides bug fixes of qTranslate? =

Yes, there is a number of new features, mostly of a convenience significance, which includes, but not limited to:

* A different design of language switching via conveniently located buttons (same way as it is done on [zTranslate](https://wordpress.org/plugins/mqtranslate/ "zTranslate plugin"). No multiple lines for title fields anymore. This design simplifies backend programming and is less likely to be broken on future WP changes.
* "Language Switcher" menu item on WP menu editing screen.
* Category and tag names in the lists on editing pages also respond to language switching and will display the taxonomy names in the currently editing language.
* Theme custom fields can be made translatable in addition to the default translatable fields.

= How do I read the FAQ of the original qTranslate? =

One can find the original qTranslate FAQ [here](https://wordpress.org/plugins/qtranslate/faq) and support forum [here](https://wordpress.org/support/plugin/qtranslate).


== Upgrade Notice ==

* Upgrading from [qTranslate](https://wordpress.org/plugins/qtranslate/ "qTranslate original plugin") and [zTranslate](https://wordpress.org/plugins/ztranslate/ "zTranslate plugin") requires no additional actions, qTranslate-X will continue to work from the database entries of qTranslate. One may also go back to qTranslate at any time.
* Upgrading from other qTranslate forks also painless with an additional step of configuration import. One may also go back at any time using configuration export.
* Former page and post translations are untouched and preserved in any case.
* Upgrading from other multilingual frameworks will require custom re-configuration. We suggest to search for a plugin, which may be already implemented to transfer the translations to qTranslate or to qTranslate-X. If a plugin works for one, it should work for other too, since qTranslate-X and qTranslate share the same database structures.

== Screenshots ==

1. Editing screen showing the buttons to switch the languages. Pressing a button does not make a call to the server, the editing happens locally, until "Update" button is pressed, the same way as it is for one language.
2. Language Management Interface
3. qTranslate translation services

== Changelog ==

= 2.9.7 =
* menu items with empty text for the current language are not shown any more.
* enable Language Switching Buttons on menu editor page. Fields "Navigation Label", "Title Attribute" and "Description" now respond to Language Switching Buttons.
* option "Custom Pages" to enable Language Switching Buttons on custom-defined pages.
* split the qtranslate.js script into a few scripts in `admin/js/` folder to be loaded depending on the page which needs them.
* updated qtranslate.pot and fixed proper translation of various strings in the code (thanks to Pedro Carvalho).
* fix for when cookie 'wp_qtrans_edit_language' contains unavailable language.
* various performance improvements.
* option "Editor Raw Mode" to be able to edit database text entries as they are, with language tag separators, without Language Switching Buttons.
* fix for [random `<p>` in TinyMCE editors](https://github.com/qTranslate-Team/qtranslate-x/issues/5).
* fix for login problem when `siteurl` option is different from 'home'.
* compatibility with (Qtranslate Slug](https://wordpress.org/plugins/qtranslate-slug/).
* fix for [blank translations](https://wordpress.org/support/topic/duplicates-everything-doesnt-work-all-times)

= 2.9.6 =
* more fixes for `<!--more-->` and `<!--nextpage-->` tags and parsing multilingual texts.

= 2.9.5 =
* more fixes for `<!--more-->` and `<!--nextpage-->` tags.

= 2.9.4 =
* fix for https://wordpress.org/support/topic/comment-shows-404-error

= 2.9.3 =
* "Language Switcher" menu options, read [FAQ](https://wordpress.org/plugins/qtranslate-x/faq/) for more information.
* fix for too early call to `current_user_can`, which caused a debug notice from within some other plugins.
* fix for https://wordpress.org/support/topic/editor-adds-characters-before-text

= 2.9.2 =
* Option "Compatibility Functions" to enable former qTranslate function names: qtrans_getLanguage, qtrans_convertURL, qtrans_use, qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage, qtranxf_useTermLib and qtrans_getSortedLanguages
* "Language Switcher" menu options: flags=[yes|no], type=[LM|AL]. They can be used in a query string in URL field of Language Menu.

= 2.9.1 =
* JS bug fixed, which would not show any field value if no languages are yet configured for that field.

= 2.9 =
* ability to enable "Custom Fields" by either "id" or "class" attribute.
* ability to specify filters, which other theme or plugins define, to pass relevant data through the translation.
* support for `<!--more-->` and `<!--nextpage-->` tags.
* language cookie are renamed to minimize possible interference with other sites.

= 2.8 =
* added option "Show displayed language prefix when content is not available for the selected language".
* compatibility with "BuddyPress" plugin and various improvements.
* custom CSS for "qTranslate Language Chooser" widget configurable via its properties.
* now always redirects to a canonical URL, as defined by options, before displaying a page.
* use of cookies to carry the language chosen from session to session.

= 2.7.9 =
* [this does not work yet] created wrappers to make former qTranslate function names available: qtrans_getLanguage, qtrans_convertURL, qtrans_use, qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage.

= 2.7.8 =
* user-friendly activation hook to deactivate/import/export other qTranslate forks.
* import/export settings from other forks

= 2.7.7 =
* improved automatic downloading of gettext databases from WP repository.
* translation of "Site Title" and "Tagline" in Settings->General (/wp-admin/options-general.php).

= 2.7.6 =
* Option "Custom Field": theme custom fields can be translatable.

= 2.7.5 =
* handling multiple tinyMCE editors, as some themes have it. It will now make all fields in page and post editors of class "wp-editor-area" translatable.

= 2.7.4 =
* fix permalink on edit pages
* disabled autosave script in editors, since it saves the active language only and sometimes hardly messes it up later.

= 2.7.3 =
* fixes for flag path, when WP is not in /. Permalink on edit pages is still broken, apparently has always been for this case.
* various minor improvements

= 2.7.2 =
* bug fixer

= 2.7.1 =
* enabled translation of image 'alt' attribute.
* corrected behaviour of category and tag editing pages when admin language is not the default one.
* hid 'Quick Edit' in category and tag editing pages since it does not work as user would expect. One has to use "Edit" link to edit category or tag name.

= 2.7 =
* enabled translations of image captions, titles and descriptions (but not 'alt').

= 2.6.4 =
* improved Description, FAQ and other documentation.

= 2.6.3 (2014-12) (initial changes after zTranslate) =

* added "Language Switcher" menu item to WP menu editing screen
* currently editing language is memorized in cookies and preserved from one post to another
* on the first page load, the default language is now activated instead of the last language
* full screen mode for tinyMCE integrated properly
* more translation on tag and category editor pages
* added 'post_title' filter to translate all titles fetched for display purpose
* fixed problem with comment date display in some themes

== Known Bugs ==

* Incompatibility with plugin [WP Editor](https://wordpress.org/support/plugin/wp-editor). Language switching buttons do not change the content of main editor in pages and posts. For now, you would need to deactivate "WP Editor".
* Sometimes after a new plugin update is released, the language switching buttons disappear on the first editor page load. Refresh the page to bring them back. Apparently, it has something to do with browse caching mechanism.
* Message "The backup of this post in your browser is different from the version below" appears sometimes in the post editor. Clicking on "Restore the backup" may produce unexpected result, since backup has one language only,
the one which was active at the time of the last pressing of button "Update". The code which causes this is in /wp-includes/js/autosave.js.

== Credentials ==

* The code of this plugin mostly originally based on [qTranslate](https://wordpress.org/plugins/qtranslate/ "qTranslate original plugin") and [zTranslate](https://wordpress.org/plugins/ztranslate/ "zTranslate fork").
* Most flags in flags directory are made by Luc Balemans and downloaded from [FOTW Flags Of The World website](http://flagspot.net/flags/ "FOTW Flags Of The World website")

== Desirable Unimplemented Features ==

* Add ability to put Language Switching buttons on other editors besides, post, pages and taxonomies.
* "Quick Edit" action in category or tag list pages will update the default language only.
* If a language was switched on a page or post, but no edits were done, browser sometimes still complains about page changes, when leaving page.
* Full screen editor mode does not have language switch buttons (not applicable in WP 4.1 any more).
