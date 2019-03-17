# Module: Yoast SEO

## Description

This module was taken from the legacy plugin [Integration: Yoast SEO & qTranslate-X](https://github.com/qTranslate-Team/wp-seo-qtranslate-x).

## Frequently Asked Questions ##

### Sitemaps suddenly stopped working showing 404 page? ###

Most likely you deactivated "Yoast SEO" plugin and then activated it again. When XML Sitemaps are enabled on Yoast "XML Sitemaps" configuration page `/wp-admin/admin.php?page=wpseo_xml` and Yoast plugin is deactivated, it clears rewrite rules needed for sitemap to function. On next activation of Yoast plugin, sitemaps no longer function until their functionality is deactivated and then activated again on Yoast configuration page "XML Sitemaps".

### Something does not work right, is it me? ###

Please, review section "[Known Issues](https://wordpress.org/plugins/wp-seo-qtranslate-x/other_notes/)".

## Upgrade Notice ##

### 1.2 ###
* Fixer for whatever got broken after Yoast has re-designed his plugin in version 3.0

### 1.1.1 ###
* Change of plugin name "Yoast SEO & qTranslate-X" to "Integration: Yoast SEO & qTranslate-X" to satisfy [WordPress trademark guideline #17](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/): no plugin name should start with other plugin name. Sorry, there is no any functionality-wise improvement.

## Changelog ##

### 1.2 ###
* Fix: the functionality after Yoast has re-designed his plugin in version 3.0.
* Enhancement: Adjustments for [Qtranslate Slug](https://wordpress.org/plugins/qtranslate-slug/).

### 1.1.1 ###
* Change of this plugin name "Yoast SEO & qTranslate-X" to "Integration: Yoast SEO & qTranslate-X" to satisfy [WordPress trademark guideline #17](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/): no plugin name should start with other plugin name.

### 1.1 ###
* Enhancement: multilingual sitemaps, require qTranslate-X 3.4.5: [Issue #1](https://github.com/qTranslate-Team/wp-seo-qtranslate-x/issues/1).
* Enhancement: moved to the new [integration](https://qtranslatexteam.wordpress.com/integration/) way using i18n-config.json file. You have to deactivate/activate plugin when updating. Normal WP update would be sufficient, but if you simply override the files, then you will miss the plugin integration configuration.
* Enhancement: A few more fields are made multilingual.
* Fix: "Page Analysis" is disabled unlesss Single Language Editor Mode is in use. "Page Analysis" is not currently integrated in any other Editor Mode.

### 1.0.2 ###
* Improvement: encoding of `yoast_wpseo_metadesc` and `yoast_wpseo_focuskw` is changed to '{' to deal with imperfections of Yoast java script.

### 1.0.1 ###
* Improvement: added multilingual fields on `edit-tags.php` page.

### 1.0 ###
* Initial release

## Known Issues ##

* Page `/wp-admin/edit.php` shows columns 'Meta Desc.' and 'Focus KW' in [Raw ML format](https://qtranslatexteam.wordpress.com/multilingual-fields/).
* Page `/wp-admin/edit.php`: column 'SEO' shows the result of analysis from the last analyzed language, which may be different from the admin language.
* [plugin Yoast SEO issue] When XML Sitemaps are enabled on Yoast configuration page `/wp-admin/admin.php?page=wpseo_xml` and Yoast plugin is deactivated, it clears rewrite rules needed for sitemap to function. On next activation of Yoast plugin, sitemaps no longer function until their functionality is deactivated and then activated again on Yoast configuration page "XML Sitemaps".
* [not really an issue] Sitemaps do not work quite right in Query URL Modification Mode. Query Mode is not supposed to be used for SEO.


### Former Known Issues ###
* [Resolved in version 1.2] Yoast SEO "Page Analysis" is not yet integrated and is mostly disabled to prevent confusions. It is only experimentally enabled in Single Language Editor Mode, which can be set on "Advanced" tab of "Languages" configuration page, `/wp-admin/options-general.php?page=qtranslate-x#advanced`. If you have time and resources, please feel free to submit pool request to the plugin [repository at GitHub](https://github.com/qTranslate-Team/wp-seo-qtranslate-x/pulls) with the implementation of "Page Analysis" for other editor modes. Unfortunately, it may not be possible without asking Yoast to put a few additional filters within ["Yoast SEO" plugin code](https://github.com/Yoast/wordpress-seo).
* [Resolved in plugin version 1.0.2 under qTranslate-X 3.4.4] Field 'Meta description' is not coming back correctly after saving. In some configurations it works though. The nature of conflict is not yet known. You would need to keep this field empty, if you are affected.
