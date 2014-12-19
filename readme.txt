=== qTranslate-X ===
Developed by: Qian Qin, John Clause
Contributors: johnclause, chineseleper, Vavooon, chsxf, michel.weimerskirch, Mirko_Primapagina
Tags: multilingual, language, admin, tinymce, bilingual, widget, switcher, i18n, l10n, multilanguage, translation
Requires at least: 3.9
Tested up to: 4.1
Stable tag: 2.7
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Adds user friendly and database friendly multilingual content management and translation support. It is a fork and a bug fixer of qTranslate.

== Description ==

This plugin is a descendant of qTranslate, which has apparently been abandoned by the original author, [Qian Qin](http://www.qianqin.de/qtranslate/ "the original author of qTranslate"). You will find a lot of useful information through reading [qTranslate](https://wordpress.org/plugins/qtranslate/ "Original qTranslate plugin")'s original documentation, which is not duplicated here in full. There are other plugins, which offer multilingual support, but it seems that Qian Qin has the best original design and many people have been pleasantly using his plugin. It stores all translations in the same single post, which makes it easy to maintain and to use it with other plugins. However, the user interface of former qTranslate got out of sync with the recent versions of Wordpress, especially after WP went to TinyMCE 4. There is a number of forks of [qTranslate](https://wordpress.org/plugins/qtranslate/ "Original qTranslate plugin"), see for example, [mqTranslate](https://wordpress.org/plugins/mqtranslate/ "mqTranslate plugin"), [qTranslate Plus](https://wordpress.org/plugins/qtranslate-xp/ "qTranslate Plus plugin") and [zTranslate](https://wordpress.org/plugins/ztranslate/ "zTranslate plugin"). They all try to fix qTranslate's user interface preserving its original backend, which is what this plugin does too. This plugin is a hybrid of all of them and fixes a few bugs in each of them. I hope that this one it the most complete working version which combines the best features of [qTranslate](https://wordpress.org/plugins/qtranslate/ "Original qTranslate plugin"), [mqTranslate](https://wordpress.org/plugins/mqtranslate/ "mqTranslate fork"), [qTranslate Plus](https://wordpress.org/plugins/qtranslate-xp/ "qTranslate Plus fork") and [zTranslate](https://wordpress.org/plugins/ztranslate/ "zTranslate fork").

We suggest all mentioned authors to get together and to continue supporting one single qTranslate-ish plugin in a joint effort.

qTranslate-X makes creation of multilingual content as easy as working with a single language. Here are some features:

- One-Click-Switching between the languages - Changing the language as easy as switching between Visual and HTML.
- Language customizations without changing the .mo files - it uses Quick-Tags.
- Multilingual dates out of the box - Translates dates and time for you.
- Comes with a lot of languages already builtin - English, German, Simplified Chinese and a lot of others.
- Choose one of 3 Modes to make your URLs pretty and SEO-friendly. - The everywhere compatible `?lang=en`, simple and beautiful `/en/foo/` or nice and neat `en.yoursite.com`.
- One language for each URL - Users and SEO will thank you for not mixing multilingual content.
- qTranslate-X supports unlimited number of languages, which can be easily added/modified/deleted via a comfortable Configuration Page at Settings->Languages.
- You may use [Google XML Sitemaps v3 for qTranslate](https://wordpress.org/plugins/google-xml-sitemaps-v3-for-qtranslate/) plugin to rebuild your XML sitemap for better SEO support.

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
1. Upgrading from [qTranslate](https://wordpress.org/plugins/qtranslate/ "qTranslate original plugin") required no additional actions, qTranslate-X will continue to work from the database entries of qTranslate. One may also go back to qTranslate at any time. Upgrading from other qTranslate forks may require re-configuration of the languages and taxonomies names.

== Frequently Asked Questions ==

= What is wrong with qTranslate? =

qTranslate still works fine at frontend, except one known to me bug of incorrect date display in comments for some themes. However, its backend breaks tinyMCE content editor in post editing page. Many people have been reporting the problems, but the author keeps silence. qTranslate-X uses the same database backend, and updated admin interface with a slightly different design.

= Does qTranslate-X offer anything new besides bug fixes of qTranslate? =

Yes, there is a number of new features, mostly of a convenience significance, which includes, but not limited to:

* A different design of language switching via conveniently located buttons (same way as it is done on [zTranslate](https://wordpress.org/plugins/mqtranslate/ "zTranslate plugin"). No multiple lines for title fields anymore. This design simplifies backend programming and is less likely to be broken on future WP changes.
* "Language Switcher" menu item on WP menu editing screen.
* Category and tag names in the lists on editing pages also respond to language switching and will display the taxonomy names in the currently editing language.

= How do I read the FAQ of original qTranslate? =

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

= 2.7 =
* enabled translations of image captions, titles and descriptions.

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
