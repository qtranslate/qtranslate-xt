### 3.12.0
New module: **Slugs** (experimental)
* Add support for permalink (slug/URL) translations to qTranslate-XT (#671)
* Integrated from [qtranslate-slug (QTS)](https://github.com/not-only-code/qtranslate-slug) plugin v1.1.18 (#1060)

Core
* Add `qtranslate_admin_block_editor` filter to disable Gutenberg support (#1112)
* Refactor bool-array setting to `QTX_BOOLEAN_SET` (#1151)
* Fix 'Headers already sent' for `wp_doing_cron` (#1114)
* Fix regression on reset config (#1109)
* Fix deprecated `preg_split` with PHP8.1 (#1085)
* Check qTranslate-X plugin disabled on QT-XT activation

Modules
* Admin modules
  * Generalize manual activation (#1147, #1137, #1135, #1136)
  * Generalize custom module settings tabs (#1146)
  * Harmonize module options as `qtranslate_module_<name>` (#1158)
  * Refactor module classes and file structure (#1153)
* ACF
  * Integrate ACF settings in modules tab (#1154)
  * Simplify module init (#1139)
* Gravity Forms
  * Fix translation of choice text in Gravity Forms (#1095)
* Slugs
  * separate admin functions from qts class, cleaning, refactoring (#1134, #1141)
  * refactor language setup with internal `q_config` (#1130)
  * refactor with new `qtranslate_convert_url` filter (#1117)
  * refactor and fix add/edit terms slugs (#1126)
  * improve slug admin metabox handling (#1124)
  * rationalize get post_types/taxonomies (#1121)
  * fix hide slug field in post quickedit (#1125)
  * refactor install with WP API (#1122)
  * merge qts textdomain to qtranslate, update l10n (#1120)
  * remove nav functions and cleanup (#1118)
  * handle deactivation, remove widget, cleanup (#1111)
  * remove obsolete migration functions and styling (#1113)
  * integrate Slugs settings in qtranslate (#1115, #1107)
  * use `$post` arg in `validate_post_slug` (#1102)
  * fix warnings and major cleanup (PHPDoc, termmeta wrappers) (#671)
  * delete unused function raising warning in PHP8.1 (#1103)
* WooComerce
  * Removed unneeded action mistakenly used as a filter (#1145)
  * Fix untranslated options in product variations (#1144)
  * Fix product attributes translations (#1143)

### 3.11.4
* Fix Yoast filter front schema webpage (#1086)

### 3.11.3
* Fix regression Yoast filter front in 3.11.2 (#1086)
* Add Yoast filters for organization schema and publisher (#1090)

### 3.11.2
* Fix warnings with Yoast breadcrumbs front (#1086)

### 3.11.1
Core
* Remove obsolete HTML `type` attributes from `script` and `style` tags (#1074)

Integration
* ACF: Delete obsolete WP version check in ACF wysiwyg
* ACF: Fix localization of ACF field labels (#1081)
* Yoast: Fix yoast breadcrumbs front (#1079)
* WooComerce: Add hook for WC privacy policy text (#1083)

Localization
* Update keywords list POT template
* Update keywords and localization zh_CN (100%) (#1059)

### 3.11.0
Core
* Enable language switch for text widget with TinyMCE editor (#1042, #529, #616, #912)
* Disable the block-based widget editor with WordPress 5.8 (#1058, #1042)
* Set cookies with explicit `SameSite=Lax` policy (#1053)
* Fix URL conversion for 3-letter language code (#1035)
* Fix uninitialized string offset in utils (#1047)
* Refactor and fix `removeContentHook` (#1043)

Localization
* Update localization zh_CN (80%) (#1049)
* Update localization ru_RU (45%) (#444)
* Update localization sl_SI (53%) (#437)
* Add localization nl_NL_formal (70%) (#416)
* Update language names for km (85%) (#420)
* Update POT template

### 3.10.1
Core
* Add qtranxs-flag class to flags on frontend (#1015)
* Remove obsolete wpautop hack for editor init (#1019)
* Remove wpautop from hook fields and format specifier (#1024)
* Remove Ajax qtranslate-fields collect as string (#1026)
* Create internal QTX initialize function in JS
* Use classList in JS instead of className or jQuery

Yoast
* Fix Yoast 'name' schema for WebPages (#1033)
* Fix Yoast canonical URL (#1032)

### 3.10.0
Core
* Major overhaul of Javascript builds
  * New feature! Javascript bundled with Webpack and Babel (#990), production builds delivered in `dist`
  * Update Wiki for [debugging Javascript](https://github.com/qtranslate/qtranslate-xt/wiki/Troubleshooting#debugging-javascript)
  * Reorganize Javascript sources (#994)   
  * Refactor source code with const let ES6 (#996), new jQuery wrappers (#998), rename variables (#1001)
* Improve integration of [Custom Javscript](https://github.com/qtranslate/qtranslate-xt/wiki/Custom-Javascript)
  * Refactor `js-exec` config entries with JS events (#1009) - allows fusion of fragmented scripts into prod bundle
  * Deprecate `js-conf` and `javascript` config entries (#1000) - no more Javascript code in JSON configs
* Deprecate custom JSON configuration user field (#1012)
* Update hooks
  * Rename filter `i18n_admin_config` -> `qtranslate_admin_config`
  * Rename filter `i18n_front_config` -> `qtranslate_front_config`
  * Deprecate duplicate filter `qtranslate_load_admin_page_config` (use `qtranslate_admin_config`)
  * Rename config actions with underscores, e.g. `qtranslate_loadConfig` -> `qtranslate_load_config`
  * Deprecate action `qtranslate_admin_css`
  * Replace `admin_head` hook with `admin_enqueue_scripts`
* Update functions
  * Deprecate functions `qtranxf_json_encode`, `qtranxf_config_add_form`
  * Rename config and utils functions with underscores, e.g. `qtranxf_loedConfig` -> `qtranxf_load_config`
  * Delete functions deprecated in 3.7.3
  * Delete internal functions `qtranxf_add_admin_head_js`, `qtranxf_add_admin_footer_js`, `qtranxf_clean_request_of`
* Fix undefined `use_block_editor_for_post` for Gutenberg (#1004)

ACF
* Fix async qtx loading in ACF (#998)
* Fix qtx and repeaterFieldRemove in ACF JS (#1006)
* Fix visual editor switch with ACF tabs (#1007)
* Refactor ACF js with ES6 const let (#997)

### 3.9.3
Core
* Fix Javascript init for Classic Editor with WP5.6 (#946, #931)
  * Fix async ready/load events with jQuery3
  * Anticipate qtx init before TinyMCE init
  * Remove ready handler from common.js
  * Fix deprecated tinymce.editors
* Refactor TinyMCE hook functions in common.js (#978)
* Redesign admin language list with icons (#945)
* Prefix global functions in modules with qtranxf_ (#959)
* Add permalink info for incompatible pre-path mode (#821)

Yoast
* Rewrite Yoast module from scratch (#794)
* Move legacy Yoast 13 module to dev (#794)
* Fix wp-seo opengraph title translation (#944)
* Add front filters schema and breadcumbs (#947)
* Generalize front filters (#947)
* Translate org/company name (#947)

WooCommerce
* Add front filter for product_get_name (#957)
* Add support for Paypal Checkout (#949)

ACF
* Add CSS for acf-autosize (#955)

Localization
* Add Bangla localization files (#960)

### 3.9.2
Core
* Fix unitialized string offset in urlinfo (#928, #939, #940)

Google Site Kit
* Add support for Google Site Kit (#934)

WooCommerce
* Fix config edit attributes (#915)
* Fix disable translations emails (#652)
* Fix additional content not translatable (#943)

AIOSEO
* Fix AIOSEO PRO config for terms (#845)

### 3.9.1
Core
* Fix warning in admin language tab (#900, #916)
* Fix display title placeholder for new post (#897)
* Fix language negociation for any path (#875)
* Fix 'disable_client_cookies' option ignored (#886)
* Refactoring
  * Disambiguate 'cookie_enabled' internal field -> 'cookie_front_or_admin_found'
  * Refactor url_info without base-path-length (#893) 
  * Truncate front/admin config from debug info

ACF
* Fix deprecated JS code for ACF (#890)
* Uniformize jQuery wrapper in ACF

WooCommerce
* Fix CSS path for products (#894)

### 3.9.0
Core
* New feature: extend language code to 3-letter (ISO 639-2 and ISO 639-3), lower case (#836, #668)
* Major refactoring with unique regex of language code for future ISO 3166 and 15924 support (#880, #668)
* Fix hreflang default URL when browser detection disabled (#198, #819)
* Cleanup repo, remove experimental dev slugs

WooCommerce
* Fix language column CSS in WC products (#831, #804)

### 3.8.1
Core
* Fix no language buttons with WP4 (#825)
* Fix built-in i18n config search (#824)
* Fix plugin activation with i18n config (#823)
* Clarify variables, PHPDoc, code cleanup

WooCommerce
* Fix translate product name in WC order admin page (#827)

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
