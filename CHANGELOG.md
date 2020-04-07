### 3.8.0
New feature! Initial support of Gutenberg, with some limitations. Read carefully our [Gutenberg FAQ](https://github.com/qtranslate/qtranslate-xt/wiki/FAQ#gutenberg) before use.

### 3.7.3
Admin
* Fix plugin, mu-plugin and theme config search (#797)
* Refactor plugin dirname and basename (#796)
* Remove legacy plugin config override (#793)
* Set url for Classic Editor direct install (#799)
* Deprecate functions:
  - `qtranxf_plugin_dirname`
  - `qtranxf_plugin_basename`
  - `qtranxf_find_plugin_config_files`
  - `qtranxf_find_plugin_by_folder`

WooCommerce
* Fix translation custom attributes (#612, #752)
* Remove `woocommerce_product_get_attributes` hook to fix `cannot_implode` (#612, #707)
* Fix translations checkout and account settings (#787)
* Refactor admin page configs

### 3.7.2
Core
* No front-detect redirect on neutral path (#749)
* Add url info in assert no redirect (#757)
* Improve debug info with plugins

ACF
* Fix missing ACF dependency on qtranslate (#759)
* Check SCRIPT_DEBUG properly in ACF

### 3.7.1
License
* Update license to GPLv2 or later. Meant for harmonization for the WordPress community. More info in related commit.
* *Important*: if you ever redistribute this work you should also do it under the same license.

REST
* Fix 404 and wrong redirects by disallowing lang query switch with REST (#720)
* Allow cookie read as last fallback with REST (#720)
* Check `HTTP_REFERER` with REST for language & doing front/admin (#744)

Core
* Use WP core functions to check globals (#747):
  * `WP_ADMIN` -> `is_admin()`
  * `DOING_AJAX` -> `wp_doing_ajax()` # from WP 4.7
  * `DOING_CRON` -> `wp_doing_cron()` # from WP 4.8 (new minimum required version)
  * `WP_CLI` -> no function but we should check the value properly
* Disambiguate core loading setup

Admin
* Disambiguate admin loading sequence in `qtranxf_admin_load`
  * Remove `qtranxf_add_admin_filters` which should not be called from outside
  * Move `qtranxf_admin_debug_info` to utils

Misc
* Update license, authors and links for Composer
* Reformat PHP and JS code with 4 spaces (#737)
* Add debugging asserts for invalid redirects. Disclaimer: only meant for dev with WP_DEBUG, not for production mode!
* Cleanup .gitattributes, clarify text (check-in) and EOL (check-out) conversions for source files
* Remove irrelevant .gitignore # NB: use git global/system config for your IDE or OS crap ;)

### 3.7.0
General
* Fix critical overwrite content issues due to duplicate cookies (#741, #711, #724, #739)
* Remove session cookie `qtrans_edit_language`, replaced by sessionStorage `qtranslate-xt-admin-edit-language`
* Introduce new hidden field `qtranslate-edit-language` in POST form to provide the active language to the server

Core
* Enable **secure** `qtrans_admin_language` cookie (#467)
* Restrict `qtrans_admin_language`, `qtrans_front_language` cookies with **httponly** flag (#467)
* Restrict legacy `url_info[original_url]` to qtranslate-slug for retro-compatibility only

Admin
* Add troubleshooting section in admin options
* Refactor settings handlers (options panels)
  * Create new class `QTX_Admin_Settings` (from `qtx_admin_configuration.php`)
  * Create new class `QTX_Admin_Settings_Language_List` (from `QTX_LanguageList`)
  * Remove session cookie `qtrans_admin_section`, replaced by sessionStorage `qtranslate-xt-admin-section`
* Remove obsolete admin settings hooks
  * filter: `qtranslate_clean_uri`
  * action: `qtranslate_configuration_pre`
  * action: `qtranslate_url_mode_choices`
* Reorder URL mode options, polish domains layout
* Cleanup configuration CSS

Modules
* Raise format priority for ACF PRO custom options (#740)
* Add support for SCRIPT_DEBUG and minified scripts in ACF
* Refactor ACF without `qtrans_edit_language` cookie, with a temporary fix for the initial language selection

### 3.6.3
General
* Update minimum requirement to PHP 5.4 (#732)
* Rename many ambiguous variables
* Remove dead code and unnecessary comments
* Fix dozens of code warnings: unused variables, redundant escape char in RegExp, comparison coercions, ...
* Fix typo in define `QTX_EDITOR_MODE_SINGLE`

Core
* Remove unused action `qtranslate_head_add_css`
* Remove unused utils functions: `qtranxf_stripSlashesIfNecessary, qtranxf_get_domain_language, qtranxf_isAvailableIn`
* Move date/time functions to `qtranslate_date_time.php`
* Refactor init url_info path and query

Admin
* Remove obsolete admin action `qtranslate_css`
* Move admin notice and log functions to admin_utils
* Fix potential bug in `qtranxj_get_cookie` (#724)
* Fix invalid admin CSS and remove unused CSS files
* Fix invalid HTML in admin nav menu

Modules
* Refactor ACF with native `qtranxf` functions (#736)
* Refactor ACF code for better readability, update PHPDoc
* Drop support of obsolete ACF 4
* Remove obsolete `qtranslate_custom_admin_js` in ACF
* Fix use of deprecated `acf_esc_attrs_e` in ACF
* Fix parameter mismatch in ACF
* Fix missing return in sitemap Yoast SEO and cleanup

### 3.6.2
* Fix media library broken with ACF 5.8.3 (#718)
* Fix ACF language values not validated (#710)
* Fix ACF image field rendering (#708)
* Fix ACF validation of url field (#703)

### 3.6.1

* New [Wiki pages](https://github.com/qtranslate/qtranslate-xt/wiki)! Help and doc hyperlinks now point to our internal Wiki.
* Fix Jetpack: translate related post titles (#699), with a new module for Jetpack
* Fix deprecated WC filters: product attributes (#686), woocommerce_add_cart_hash
* Fix ACF post object titles not translated (#678)
* Fix CSS for ACF admin field (#697)
* Fix translation embedded content (#673), for fields given by oembed_response_data
* Update vendor info in i18n-config.json (#702)
* Update Hungarian localization to 3.6.0 (#692)

### 3.6.0
* New feature: the built-in **modules** replace the legacy integration plugins. You have to **deactivate/reactivate qTranslate-XT** to detect the active modules. See the integration tab and the main modules [README](https://github.com/qtranslate/qtranslate-xt/blob/master/modules/README.md) for more info.
* New modules: ACF, All in One SEO Pack, Events Made Easy, Gravity Forms, WooCommerce. Note: these modules have been converted from the last available versions of the legacy plugins, but they still remain the same. See each module README for more info.
* Fix CSS admin notices (#664)
* Cleanup obsolete admin version notices. The version options become obsolete (`qtranslate_version_previous, qtranslate_versions`).

### 3.5.5
* Adds support for Composer (#659)
* Fix no LSB on categories (#643): this issue occurred with NextGen Gallery but there might be other plugins concerned.
* Code cleanup: fix potential minor bugs with variables overwritten in loops, fix missing returns, undefined variables, unused local variables, reformat json
* Fix potential minor bug with the terms names (get_term_args)
* Revert to legacy suffix for integration config files (searched in "-qtranslate-x" folders instead of "-qtranslate-xt") for consistency with legacy online documentation
* Fix typo in qtranxf_find_plugin_by_folder (deprecate old version with errated name)

### 3.5.4
* Shorten front-end message for alternative content (#655). The long part "For the sake of convenience... " is removed, for sake of convenience. Only the first part with the available languages is kept, also sent in the 'i18n_content_translation_not_available' filter.
* Disambiguate the admin options for untranslated content: clarify descriptions, reorder by relevance.
* Fix JS loading for early get_ctx (#650) for better admin-side integration. Could lead to LSB not shown (qTranslateConfig.js.get_qtx() not declared).
* Fix no CSPRNG for gettext DB update (#649). Could raise PHP Fatal error : 'no suitable CSPRNG installed' when cryptographic libraries are missing.
* Fix date periods for DST (#653) by using strtime() instead of time() + sec. Could affect some admin checks, notices and cookie expirations (very minor impacts).

### 3.5.3
* Fix REST API: no redirect allowed (PR #621, issues #609, #575, #528, #489, #427). NOTE: your rewrite rules should be updated by saving the permalink structures from the admin page.
* Fix warning PHP 7.3
* Fix migration DB: skipped options, warning PHP 7.3 (PR #633)
* Disable admin notice for unsupported block editor with plugins disable-gutenberg, no-gutenberg

### 3.5.2
* Add admin notice for WP5.0: "Gutenberg" block editor not supported, install Classic Editor plugin.
* Fix unresolved variables and unused PHP syntax error in dev code.
* Fix deprecated jQuery.ready JS handler, refactor jQuery wrapper/closure functions and standard coding style.

### 3.5.1
* Cleanup: reformat all PHP code with WordPress coding style, remove lots of commented code for better clarity. Breathe again!
* Redesign admin Language Switching Buttons (built-in LSB styles) and 'Copy From' button with new ergonomics.
* Remove admin options `lsb_style_wrap_class` & `lsb_style_active_class`. No impact for built-in LSB styles, please make a request if further custom CSS needed.
* Remove `qtranxf_loadfiles_js` now replaced with `qtranxf_enqueue_scripts`. Neither should be used by other plugins.
* Fix: prevent cache issues with non-minified Javascript when using `SCRIPT_DEBUG` (for developers).
* Fix: remove unlimited output buffering introduced with pre-release patch 3.4.8.
* Cleanup: rename `qTranslate-X` to `qTranslate-XT` as plugin name and for options pages. Replace obsolete links in admin pages, now redirecting to github.
* Localization: update 'fr_FR'.

### 3.5.0
* **First release** of **qTranslate-XT**! Read carefully the new instructions, FAQ and changelog.
* Reorganize project structure for releases through git archives with support of GitHub Updater (see FAQ).
* Fix PHP 7.1+ warnings (expected references)
* Re-package the pending pre-releases (3.4.6.9, 3.4.7, 3.4.8) that were never distributed to wordpress.org (!): new feature "Copy From" and many other changes. Note the last official release of qTranslate-X is 3.4.6.8. Check the changelog for more details.

### 3.4.8
* Feature: Button "Copy From", which allows to copy multilingual content from other language. Option 'Hide button "Copy From"' on page `/wp-admin/options-general.php?page=qtranslate-x#advanced` to turn this feature off is also provided.
* Workaround: added `addContentHooksTinyMCE` back to `qTranslateConfig.qtx` namespace in order to recover compatibility with outdated code of plugin [ACF qTranslate](https://wordpress.org/plugins/acf-qtranslate/).

### 3.4.7
* Improvement: cached values of raw ML fields in WP_Post object, function `qtranxf_translate_object_property` [Topic #426](http://qtranslate-x.com/support/index.php?topic=426).
* Language preset 'md': locale 'ro_RO' (Moldovan, Moldovenească).
* Language preset 'cs': locale 'cs_CZ' (Czech, Čeština).
* Fix: "Invalid argument supplied for foreach() ... on line 14": [Issue #392](https://github.com/qTranslate-Team/qtranslate-x/issues/392).

### 3.4.6.9
* Improvement: Consistent term framework. Database operation "Clean Legacy Term Names" (at plugin settings page `/wp-admin/options-general.php?page=qtranslate-x#import`), which cleans up old imperfections of taxonomy framework.
* Improvement: editing of categories and tags in Raw Editor Mode [WP Topic](https://wordpress.org/support/topic/taxonomy-term-translate-filter-in-editor-raw-mode-in-admin)
* Improvement: using now native code for editing of terms. Script `edit-tag-exec.js` is no longer needed.
* Improvement: ML fields are now also highlighted with a color bar in Raw Editor Mode.
* Information: Translators acknowledgement section has been moved from qtranslate.php to /lang/translators-notes.txt to keep all translation-related updates in one folder.
* New Tool: Database operation "Split database file by languages" on page `/wp-admin/options-general.php?page=qtranslate-x#import`.
* Language preset 'kk': locale 'kk' (Kazakh, Қазақ тілі).
