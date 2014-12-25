=== qTranslate-X ===
Developed by: John Clause based on original code by Qian Qin
Contributors: johnclause, chineseleper, Vavooon
Tags: multilingual, language, admin, tinymce, bilingual, widget, switcher, i18n, l10n, multilanguage, translation
Requires at least: 3.9
Tested up to: 4.1
Stable tag: 2.7.6
License: GPLv3 or later
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=QEXEK3HX8AR6U
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Adds user friendly and database friendly multilingual content management and translation support. It is a fork and a bug fixer of qTranslate.

== Description ==

This plugin is a descendant of [qTranslate](https://wordpress.org/plugins/qtranslate/ "Original qTranslate plugin"), which has apparently been abandoned by the original author, [Qian Qin](http://www.qianqin.de/qtranslate/ "the original author of qTranslate"). 

While the back-end database framework left almost intact and fully compatible with former qTranslate, the design of editors is drastically changed and improved to be much less vulnerable to WP updates. Instead of seeing multiple lines per each language for title, qTranslate-X provides language switching buttons, which, once pressed, make all the text fields to be filled with the language chosen. The instant language change happens locally in your browser without sending an additional request to the server.

qTranslate-X makes creation of multilingual content as easy as working with a single language. Here are some features:

- One-click local switching between the languages - Changing the language as easy as switching between Visual and HTML.
- Language customizations without changing the .mo files - It stores all the translations in the same post fields, while shows it to user for editing one by one depending on the language to edit chosen.
- In-line syntax '`<!--:en-->English Text<--:--><!--:de-->Deutsch<--:-->`' or '`[:en]English Text[:de]Deutsch`' for theme-custom fields gets them translated. See [FAQ](https://wordpress.org/plugins/qtranslate-x/faq/ "qTranslate-X FAQ") for more information.
- Multilingual dates out of the box - Translates dates and time for you.
- Theme custom fields can be configured to be translatable too.
- Comes with a number of languages already built-in - English, German, Simplified Chinese, for example, and many more.
- Choose one of 3 Modes to make your URLs look pretty and SEO-friendly. - The simple and beautiful `/en/foo/`, or nice and neat `en.yoursite.com`, and everywhere compatible `?lang=en`.
- One language for each URL - Users and SEO will thank you for not mixing multilingual content.
- qTranslate-X supports unlimited number of languages, which can be easily added/modified/deleted via a comfortable Configuration Page at Settings->Languages.
- You may use [Google XML Sitemaps v3 for qTranslate](https://wordpress.org/plugins/google-xml-sitemaps-v3-for-qtranslate/) plugin to rebuild your XML sitemap for better SEO support.

You may still find a lot of useful information through reading [qTranslate](https://wordpress.org/plugins/qtranslate/ "Original qTranslate plugin")'s original documentation, which is not duplicated here in full. There are also other plugins, which offer multilingual support, but it seems that Qian Qin has the best original back-end design, and many people have been pleasantly using his plugin. It stores all translations in the same single post, which makes it easy to maintain and to use it with other plugins. However, the user interface of former qTranslate got out of sync with the recent versions of Wordpress, especially after WP went to TinyMCE 4. There is a number of forks of qTranslate, see for example, [mqTranslate](https://wordpress.org/plugins/mqtranslate/ "mqTranslate plugin"), [qTranslate Plus](https://wordpress.org/plugins/qtranslate-xp/ "qTranslate Plus plugin") and [zTranslate](https://wordpress.org/plugins/ztranslate/ "zTranslate plugin"). They all try to fix qTranslate's user interface preserving its original back-end, which is what this plugin does too. This plugin is a hybrid of all of them and fixes a few bugs in each of them. It also has many new features too, like theme custom translatable fields, for example. We hope that this plugin is the most complete working version which combines the best features of [qTranslate](https://wordpress.org/plugins/qtranslate/ "Original qTranslate plugin"), [mqTranslate](https://wordpress.org/plugins/mqtranslate/ "mqTranslate fork"), [qTranslate Plus](https://wordpress.org/plugins/qtranslate-xp/ "qTranslate Plus fork") and [zTranslate](https://wordpress.org/plugins/ztranslate/ "zTranslate fork").

We suggest all mentioned authors to get together and to continue supporting one single qTranslate-ish plugin in a joint effort.

== Installation ==

Installation of this plugin is no different from any other plugin:

1. Download the plugin from [here](http://wordpress.org/plugins/qtranslate-x/ "qTranslate-X").
1. Extract all the files.
1. Upload everything (keeping the directory structure) to the `/wp-content/plugins/` directory.
1. Deactivate plugin qTranslate, mqTranslate, qTranslate Plus, or zTranslate, if you running any.
1. Activate qTranslate-X through the 'Plugins' configuration page in WordPress.
1. Open Settings->Languages configuration page and add/delete/disable any languages you need.
1. Add the qTranslate Widget or Language Switcher menu item to let your visitors switch the language.
1. You may use [Google XML Sitemaps v3 for qTranslate](https://wordpress.org/plugins/google-xml-sitemaps-v3-for-qtranslate/) plugin to rebuild your XML sitemap for better SEO support.
1. Configure theme custom fields to be translatable if needed (Settings -> Languages: "Custom Fields").
1. Upgrading from [qTranslate](https://wordpress.org/plugins/qtranslate/ "qTranslate original plugin") required no additional actions, qTranslate-X will continue to work from the database entries of qTranslate. One may also go back to qTranslate at any time. Upgrading from other qTranslate forks may require re-configuration of the languages and taxonomies names, unless you rename corresponding database entries directly.

== Frequently Asked Questions ==

= Is it possible to translate theme custom fields? =

Yes, some themes put additional text fields per page or per post. By default, those fields have no way to respond to language switching buttons in editors. However, you may enter "id" attribute of those fields into "Custom Fields" section of "Languages" configuration page in "Settings", and they will then respond to the language switching buttons allowing you to enter different text for each language. To lookup "id", right-click on the field in the post or the page editor, choose "Inspect Element", and look for an attribute of the field named "id".

The theme must pass those values through [translation](http://codex.wordpress.org/Function_Reference/_2) function '__()' before displaying on the output. Most themes do this by default, otherwise you may ask theme author to make this little modification for each field you need to be translatable.

This applies to post, pages and media editors (/wp-admin/post*).

= How do I translate custom configuration fields, which are not handled by language switch buttons? =

Some themes have additional to the standard WP design fields, which need to be translated. In such a case, enter all translations in one field using syntax like this:

`<!--:en-->English Text<--:--><!--:de-->Deutsch<--:-->`

or like this

`[:en]English Text[:de]Deutsch`

If a theme uses `__()` [translate](http://codex.wordpress.org/Function_Reference/_2 "WP Function 'translate'") function before displaying those fields, then they will be shown correctly, otherwise suggest theme author to put `__()` calls in. Most themes do it this way.

The '`[:]`' syntax works well for one-line text fields, while '`<--:-->`' syntax is more suitable for text areas.

= What is wrong with the original qTranslate? =

qTranslate still works fine at frontend, except one known to me bug of incorrect date display in comments for some themes. However, its backend breaks tinyMCE content editor in post editing page. Many people have been reporting the problems, but the author keeps silence. qTranslate-X uses the same database backend, and updated admin interface with a slightly different design.

= Does qTranslate-X offer anything new besides bug fixes of qTranslate? =

Yes, there is a number of new features, mostly of a convenience significance, which includes, but not limited to:

* A different design of language switching via conveniently located buttons (same way as it is done on [zTranslate](https://wordpress.org/plugins/mqtranslate/ "zTranslate plugin"). No multiple lines for title fields anymore. This design simplifies backend programming and is less likely to be broken on future WP changes.
* "Language Switcher" menu item on WP menu editing screen.
* Category and tag names in the lists on editing pages also respond to language switching and will display the taxonomy names in the currently editing language.

= How do I read the FAQ of the original qTranslate? =

One can find the original qTranslate FAQ [here](https://wordpress.org/plugins/qtranslate/faq) and support forum [here](https://wordpress.org/support/plugin/qtranslate).


== Upgrade Notice ==

* Upgrading from [qTranslate](https://wordpress.org/plugins/qtranslate/ "qTranslate original plugin") required no additional actions, qTranslate-X will continue to work from the database entries of qTranslate. One may also go back to qTranslate at any time.
* Upgrading from other qTranslate forks may require re-configuration of the languages and taxonomies names.
* Former page and post translations are untouched and preserved.
* Upgrading from other multilingual frameworks will require custom re-configuration. We suggest to search for a plugin, which may be already implemented to transfer the translations to qTranslate or to qTranslate-X. If a plugin works for one, it should work for other too, since qTranslate-X and qTranslate share the same database structures.

== Screenshots ==

1. Editing screen showing the buttons to switch the languages. Pressing a button does not make a call to the server, the editing happens locally, until "Update" button is pressed, the same way as it is for one language.
2. Language Management Interface
3. qTranslate translation services

== Changelog ==

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

== Credentials ==

* The code of this plugin mostly originally based on [qTranslate](https://wordpress.org/plugins/qtranslate/ "qTranslate original plugin") and [zTranslate](https://wordpress.org/plugins/ztranslate/ "zTranslate fork").
* Most flags in flags directory are made by Luc Balemans and downloaded from [FOTW Flags Of The World website](http://flagspot.net/flags/ "FOTW Flags Of The World website")

== Desirable Unimplemented Features ==

* "Quick Edit" action in category or tag list pages will update the default language only.
* Full screen editor mode does not have language switch buttons
* If a language was switched on a page or post, but no edits were done, browser still complains about page changes, when leaving page.

== Known Bugs ==

* Message "The backup of this post in your browser is different from the version below" appears sometimes in the post editor. Clicking on "Restore the backup" may produce unexpected result, since backup has one language only,
the one which was active at the time of the last pressing of button "Update". The code which causes this is in /wp-includes/js/autosave.js.
