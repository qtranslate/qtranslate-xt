=== Qtranslate Slug ===
Contributors: carlos_a_sanz, pedroghandi  
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=GYS2L7627B4F8&lc=GB&item_name=Qtranslate%2dSlug%20Improvement%20Fund&item_number=qts%2dpaypal&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHosted
Tags: qtranslate, slug, multilanguage, widget
Requires at least: 4.0
Tested up to: 4.5
Stable tag: 1.1.18
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds support for permalink translations to QTranslate-X

== Description ==

[Qtranslate-X](http://wordpress.org/plugins/qtranslate-x/) is a nice plugin for Multilingual Websites. **Qtranslate Slug** is an addon to QTranslate, which adds support for permalinks translations.





1.1.17 fixes a dangerous Security Exploit. Please update right now!

= Requirements: =

* Wordpress 4.0 (PHP 5.4 and MySQL 5)
* mQtranslate 2.6.2.4 or Qtranslate 2.5.8 or qtranslate-x ( 2.9.1 )

= New in 1.1.18 =

* works with upcomming wp 4.5! thats new for a change! 
* widget is now compatible with wp 4.3. thanks to @adnanoner ( #111) and @gitmad (#112)
* saving taxonomies wont print warning. thanks to @jmarceli ( #113)
* saving post quick edit wont print warnings. thanks again to jmarceli ( #114 )
* Code from wp.org is now been merged with the github account
* Some notices are fixed. Thanks to @rafa-aguilar ( #89 )
* custom post types are fixed! thanks to @MicheleBertoli ( #102 )
* slug box now shows the utf8 chars instead of the enconded ones.
* you can now toggle the default slug box, in case you want to edit it.
* edit term has been re-worked, so no more errors and notices! ( hopefully!)
* couple of other stuff has been fixed by me thanks to your awesome bug reports!

= New in 1.1.17 =
* Hability to filter the position of the Metabox
* Fixed dangerous security exploit!

= New in 1.1.16 =
Minor fix for the language menu using qtranslate's function

= New in 1.1.15 =
* Fixes the duplicated hreflang links in <head>

= New in 1.1.14 =

The menu widget didn't allow the visitors to change to the default language if qtranslate-x was being used. So, adjusted the Language Menu widget to play nice with qtranslate-x. 
Hope to bring some nice changes that were made in the github repository in the next version. For now, enjoy.

= New in 1.1.13 =
== Thanks to returning @pedro-mendonca for these commits: ==
* Cleaned duplicated label in widget
* Bug fix in "Slug (%s)" string translation
* Changed text strings with no text-domain and with text-domain 'qtranlate' to text-domain 'qts'
* pot catalog updated with current strings, including last found is "More information about".
== Thanks to @johnclause for these : ==
* Convenience links in notice_dependences
* Menu compatibility with qTranslate-X
* Fixed extra characters in widget
### Thanks to vbkun for casting the much wanted function to get a slug based on an id and language
* Added a global qts_get_slug( $id, $lang)
### and sadly:
* removed the menu admin box until better implementation


See you next Version!


**Advice: If you're using a multisite installation, you will must activate qtranslate plugins by separately on each site.**

You can also check the [project website](http://not-only-code.github.com/qtranslate-slug/) hosted on [GitHub](http://not-only-code.github.com).
Thanks for use this plugin!

= Contributors =

* [Pedro de Carvalho](https://github.com/LC43/)
* [Risto Niinemets](https://github.com/RistoNiinemets)
* [Pedro Mendonça](https://github.com/pedro-mendonca)
* [codep0et](https://github.com/codep0et)
* [Giraldi Maggio](https://github.com/bedex78)
* [jinoOM](https://github.com/jinoOM)
* [Juanfran](https://github.com/juanfran-granados)
* [Arild](https://github.com/arildm)
* [Rafa Aguilar](https://github.com/rafitaFCB)
* [Bastian Heist](https://github.com/beheist)
* [John Clause](https://github.com/johnclause)


== Installation ==
**This plugins requires [Qtranslate](http://wordpress.org/extend/plugins/qtranslate/) or [mqTranslate](https://wordpress.org/plugins/mqtranslate/) installed previously, if not, it will not activate.**

1. Upload `qtranslate-slug` to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. That's all!

= Changing base permastructs =

1. In admin: navigate to *Settings/Slug options*.
1. Set the base permastructs for **post types** and **taxonomies** (If you setup a base permastruct for *categories* or *tags* in *Settings/Permalinks*, these will be overwritten by the translated ones).
1. Save settings and that's all!

== Frequently Asked Questions ==

= It works with posts and pages, but with other content type? =

This plugin allows to translate slugs of: posts, pages, custom post types, categories, tags and custom taxonomies.

= Do I have to configure anything? =

If you want to translate also the base permastructs (ex. *category*, *tag*, etc). Visit the plugin settings page in the admin *Settings/Slug options*

= How can i insert a language selector in my theme ? =

You can choose to:
* use **Qtranslate Slug Widget** in your sidebar.
* place in your template `<?php if (function_exists('qts_language_menu') ) qts_language_menu('text'); ?>`. Options are: `dropdown`, `text`, `image`, and `both`.

= Appears an error 404, what can i do? =

In the admin go to *Settings/Permalinks* or *Settings/Slug options* and save.

= I can't manage translations in Nav Menus. = 

That's because language selector metabox is hidden, if you are in admin *nav menus* screen, press the button **Screen options** (on top and right) and after, check the option *Languages*. It will appear a **Language** meta box on top of the left sidebar.

= How to get the current url in a specific language? =

You can use `qts_get_url()` or the awkwardly named `qTranslateSlug_getSelfUrl()` which is an alias for the first one.


== Screenshots ==

1. Edit page for: post / page / post_type, you can see the meta box for translated slugs on top and right.
2. Add new taxonomy page
3. Edit taxonomy page
4. Qtranslate Slug options page for translate base permastructs of post_types and taxonomies.

== Changelog ==


= New in Versions 1.1.12 =

* fixed warnings in settings
* replace qtranslate with our own for taxonomies

= New in Versions 1.1.10 and 1.1.11 =

* Fixing wrong commit to wp.org
* Clean deleted files 
= New in Version 1.1.9 =

Lots of bug fixes! Thanks again to everyone that contributed to this project, with commits, bug reports and suggestions.

* Compatibility with qtranslate-X! ( thanks @beheist, pull #85, fixing most of #80 )
* More updates to the portuguese translation ( thanks pedro-mendonca, pull #86)
* Corrected the link to language files ( thanks pedro-mendonca )
* Added translation for some hardcoded texts ( thanks pedro-mendonca )
* Corrected a link from 'qtranslate' to 'qts' language files ( thanks pedro-mendonca )
* Fixed taxonomies slugs ( thanks to [eirikv's bug report](https://wordpress.org/support/topic/categories-slug-dont-work) )
* Fixed many warnings ( thanks piffpaffpuff, issue #78 and to [pedrodu1](https://wordpress.org/support/topic/warnings-qtranslate-slugphp) )
* Changed the behaviour of "Quick Edit", from the wp forums [1](https://wordpress.org/support/topic/categories-tags-and-quick-edit-dont-show-in-admin) [2](https://wordpress.org/support/topic/quick-edit-inhibited-by-qtranslate-slug-with-wp-41-mqtranslate) ( thanks everyone!! )
* Fixed the menus! Now you can properly use one menu for every language. Use the dropdown section "Languages", and for each item, change the "Navigation Label" and "Title Attribute". Select "All languages", to make sure everything is awesome! All these features were a consequence of fixing all the warnings based on [Gery's bug report](https://wordpress.org/support/topic/qtranslate-slug-conflicting-with-ubermenu). 
* Minor fixes, etc. 


= New in Version 1.1.9 =

Lots of bug fixes! Thanks again to everyone that contributed to this project, with commits, bug reports and suggestions.

* Compatibility with qtranslate-X! ( thanks @beheist, pull #85, fixing most of #80 )
* More updates to the portuguese translation ( thanks pedro-mendonca, pull #86)
* Corrected the link to language files ( thanks pedro-mendonca )
* Added translation for some hardcoded texts ( thanks pedro-mendonca )
* Corrected a link from 'qtranslate' to 'qts' language files ( thanks pedro-mendonca )
* Fixed taxonomies slugs ( thanks to [eirikv's bug report](https://wordpress.org/support/topic/categories-slug-dont-work)
* Fixed many warnings ( thanks piffpaffpuff, issue #78 and to [pedrodu1](https://wordpress.org/support/topic/warnings-qtranslate-slugphp) )
* Changed the behaviour of "Quick Edit", from the wp forums [1](https://wordpress.org/support/topic/categories-tags-and-quick-edit-dont-show-in-admin) [2](https://wordpress.org/support/topic/quick-edit-inhibited-by-qtranslate-slug-with-wp-41-mqtranslate) ( thanks everyone!! )
* Fixed the menus! Now you can properly use one menu for every language. Use the dropdown section "Languages", and for each item, change the "Navigation Label" and "Title Attribute". Select "All languages", to make sure everything is awesome! All these features were a consequence of fixing all the warnings based on [Gery's bug report)[https://wordpress.org/support/topic/qtranslate-slug-conflicting-with-ubermenu). 
* Minor fixes, etc. 

See you next Version!


= 1.1.8 =

Many thanks to everyone that contributed to this update, for their commits, bug reports and for simply using it

* Portuguese translation and fixed some translation bugs  ( thanks pedro-mendonca )
* removed mqtranslate switcher widget hook
* Updated the plugin structure and coding style
* solve some conflicts with search and pagination queries
* Settings php errors, syntax indent and fixed settings assets url
* Change titles when there is a click on pagination on show all pages tab ( thanks juanfran-granados )
* Formatted dependency notice message ( thanks arildm )
* Updated the deprecated jquery 'live' function and solve php strict standards error ( thanks rafitaFCB )  
* Fixed error showing if PHP was newer than 5.3 ( thanks rafitaFCB )  
* Strict standard advise prevented ( thanks rafitaFCB )
* php notices are prevented in post edit, when using adding new translated tags  ( thanks rafitaFCB )
* fixed the hreflang issue! 'bout time!
* As discussed in issue #25, the flags are now img tags, intead of background-url.
* Added another option to include the css style in a minified file. ( and also showing in the option screen the styles we would use.)

= 1.1.7 = 
* removed styles from html elements and added options to use .css file or print inline styles
* fixed tag creation on post edit.
* fixed earlier bad post slug introduced in 1.1.6

= 1.1.6 =
* compatible with mqtranslate
* php5.4+ compatible

= 1.1.5 = 
* bugfixes

= 1.1 = 
* added multisite support
* fixed some parse url bugs
* fixed slug bases validation 

= 1.0 =
* **works** with any permalink combination and qtranslate mode.
* new branch, the plugin has been rewritten: now the code is commented and wrapped inside a class, much code has change and the performance has been increased (use caches).
* data system changed, no ID for slug type, then it don't needs install `qtrasnlate_slug` table. That means slugs now are stored on meta tables and installation creates a termmeta table with some new *core functions* to access/save data, based on [simple term meta](http://wordpress.org/extend/plugins/simple-term-meta/). Upgrade process when the plugin updates from older versions.
* the plugin generates translated slug automatically from title in empty cases.
* the plugin checks if the slug already exists (per each language and `post_type`/`taxonomy`), and adds a progressive number in this case. Works on ajax requests for example when new taxonomies are created in edit post page.
* possibility to translate the base of permastructs for *post_types* and *taxonomies*, uses [$wp_rewrite](http://codex.wordpress.org/Class_Reference/WP_Rewrite). New admin options page for save the base permastructs.
* added some filters, see in [other notes](http://wordpress.org/extend/plugins/qtranslate-slug/other_notes/).
* added plugin language textdomain (.pot file).
* updated **Language selector Widget**, and some new conventions like accessible functions for templating.
* some bug fixes.
* some Qtranslate patches.

= 0.9 =
* some wordpress qTranslate bug fixes
* adds a javascript solution for qTranslate Nav Menus

= 0.8 =
* added support por Categories
* added support por Tags
* added support por Taxonomies
* added support por Custom Post Types

= 0.7 = [Zapo](http://www.qianqin.de/qtranslate/forum/viewtopic.php?f=4&t=1049&start=50#p7499)
* added suport for qTranslate TLD domain mode (en: domain.com | fr: domain.fr) visit 

= 0.5 and 0.6 enhanched by Marco Del Percio =

== Upgrade Notice ==

= 1.0 =
Major version, the plugin has been rewritten. Better performance, and some enhancements.

= 0.9 =
This version fix some bugs and allow multilanguage in nav-menus.

= 0.8 =
A lot of slugs content allowed

= 0.7 =
This version allows TLD domain option for a different Qtranslate fork maded by Zappo


== Other notes ==

Plugin filters reference:

= qts_validate_post_slug =  
filter to process the post slug before is saved on the database.  
`args: $post (object), $slug (string), $lang (string)`

= qts_validate_term_slug =  
filter to process the term slug before is saved on the database.  
`args: $term (object), $slug (string), $lang (string)`

= qts_url_args =  
filter to process the entire url after it has been generated.  
`args: $url (string), $lang (string)`
 
= qts_permastruct =  
filter to process the permastruct, used for change the base.  
`args: $permastruct (string), $name (string)`


= Todo =

* detect Slug for each language and redirect accordingly in parse_query.
* expand qtranslate for translate attachment names and descriptions ( useful for galleries )
* translate other slugs like attachments.
* qtranslate integration with other plugins like Jigoshop, e-commerce, etc. Addapt **$wp_rewrite**.
