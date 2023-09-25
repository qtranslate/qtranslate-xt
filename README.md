# qTranslate-XT (eXTended)
Developed by: new qTranslate community, from qTranslate-X by John Clause and qTranslate by Qian Qin  
Contributors: herrvigg, johnclause, chineseleper, Vavooon, grafcom  
Tags: multilingual, language, admin, tinymce, bilingual, widget, switcher, i18n, l10n, multilanguage, translation  
Requires at least: 5.0  
Tested up to: 6.2.2  
Requires PHP: 7.1  
Stable tag: N/A  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

Adds user-friendly multilingual content support, stored in single post.

## Description

The qTranslate-XT plugin is an *eXTended* version of qTranslate-X that we are reviving with a new community, since the [original plugin](https://wordpress.org/plugins/qtranslate-x/) is abandoned by its author. Our first goal is to maintain the essential features of this plugin with the last Wordpress and PHP updates. The migration to Gutenberg will be a critical milestone for the survival of this plugin. We are currently building a [new organization](https://github.com/qtranslate) to give qTranslate a new life. Let's try together, anyone is welcome to participate!

GitHub repository of the new repo: [https://github.com/qtranslate/qtranslate-xt.git](https://github.com/qtranslate/qtranslate-xt).

## Installation

Since the -XT version is not officially available at Wordpress.org the initial installation must be done *manually*. You require the permissions to access the `plugins` folder of your Wordpress installation. Contact your system administrator if needed.

1. Download the [last release from GitHub](https://github.com/qTranslate/qtranslate-xt/releases) in zip or tar.gz format (usually zip for Windows users). Alternatively, download the [current master branch](https://github.com/qtranslate/qtranslate-xt/archive/refs/heads/master.zip) to test the last updates before a version release. Developers are encouraged to `git clone` the repo.
2. Uncompress the archive in your `plugins` folder and rename it to `qtranslate-xt`.
3. Deactivate plugin qTranslate-X, qTranslate, mqTranslate, qTranslate Plus, zTranslate or any other multilingual plugin, if you are running any.
4. Activate qTranslate-XT through the 'Plugins' admin page in WordPress (`/wp-admin/plugins.php`).

If you didn't already have qTranslate-X, proceed with the initial setup of qTranslate-XT:

1. Open Settings > Languages configuration page and add/delete/disable any languages you need.
2. Add the "qTranslate Language Chooser" widget or "Language Switcher" menu item to let your visitors switch the language.

Check the FAQ for further instructions.

## Frequently Asked Questions

### Why is qTranslate-X not maintained anymore?
The previous qTranslate-Team was only one person. We tried to contact the author many times but we got [no answer since 2016](https://github.com/qTranslate-Team/qtranslate-x/issues/579). Our goal is to build up a real team and make this plugin available again for the whole community. We can't update the official plugin yet. It is still not clear either if we should go on with the qTranslate name, but for now we should focus on new releases. The -XT version can be seen for the least as a "bridge" project.

### I'm still using qTranslate-X, can I test qTranslate-XT?
Yes and it's very easy! Currently you can have both qTranslate-X and qTranslate-XT installed in the plugins folder for experimentations, but you should have at most one active at any time: **BOTH -X AND -XT SHARE THE SAME OPTIONS!** So if you change some options and switch between the plugins, the last changes will remain for the other. The plugin can actually re-adapt its configuration after a switch, in the general case you have nothing to do. If you have some incompatible options you should see some warnings. Note that even if you uninstall either -X or -XT, the options are *not* erased!

*Disclaimer: be sure to backup your database regularly.*

### Is the Block Editor (Gutenberg) supported?
It is partially supported, with some limitations. Read carefully our [Gutenberg FAQ](https://github.com/qtranslate/qtranslate-xt/wiki/FAQ#gutenberg) before use.

### Is WooCommerce, ACF, QTS (Slugs), ... supported?
WooCommerce, ACF, Slugs and other plugins are now supported as built-in modules. Developers able to test properly are much welcome! Please send PR for bug fixes.
See the complete list of [available modules](https://github.com/qtranslate/qtranslate-xt/tree/master/modules) in our repo.

### Is any plugin/themes supported?
Some major plugins are now supported with the built-in modules. Some plugins are also supported with built-in i18n configurations. For other plugins you need to provide custom integration through i18n configuration (json) and/or code (PHP/JS). A major refactoring is needed to make this easier.

### I'm new to qTranslate, where can I find detailed instructions for startup?
Check our [Wiki pages](https://github.com/qtranslate/qtranslate-xt/wiki):

* For the new installers, it may be useful to read [Startup Guide](https://github.com/qtranslate/qtranslate-xt/wiki/Startup-Guide/ "Startup Guide").
* It is important to read [migration instructions](https://github.com/qtranslate/qtranslate-xt/wiki/Migration-Guide "Migration Guide"), if you previously used other multilingual plugin.
* Read [Integration Guide](https://github.com/qtranslate/qtranslate-xt/wiki/Integration-Guide "Integration Guide") when you need to make theme or other plugin custom fields to be multilingual.
* For more detailed questions see our [technical FAQ](https://github.com/qtranslate/qtranslate-xt/wiki/FAQ).

### How to update qTranslate-XT with the last release?
Since the -XT version is not available at wordpress.org, we recommend you to install [GitHub Updater](https://github.com/afragen/github-updater). This is is an awesome tool to update plugins from a git repo (with many other features). It checks regularly the last release available in github (from the `git tags`) and compares it to your current version (defined in the header of `qtranslate.php`). If a new release is available an update link will appear as for a regular plugin from Wordpress. The check is performed even if the plugin is deactivated.

Alternatively you can delete the current folder and repeat the installation from the last archive. Make sure to deactivate the previous version and then activate the new one, otherwise you will miss the execution of activation hooks and some options may become misconfigured.

Note for developers:

* since GitHub Updater deploys the archive (tarball) your local git project will be removed if you installed it through `git clone`. If you want to use a cloned version in production you should not update through GHU, use `git pull` instead.
* old releases may contain legacy headers that can become problematic. Be very cautious if you customize the updates for given branches!

## Upgrade Notice

See [Github releases](https://github.com/qtranslate/qtranslate-xt/releases).

## Screenshots

See [original plugin](https://wordpress.org/plugins/qtranslate-x/).

## Changelog

See [CHANGELOG.md](https://github.com/qtranslate/qtranslate-xt/blob/master/CHANGELOG.md) for the full history.

## Known Issues

The previous issues have been duplicated to our new git repository. Please check the [git issues](https://github.com/qTranslate/qtranslate-xt/issues) before creating new ones.

The [legacy issues](https://github.com/qtranslate/qtranslate-xt/wiki/Known-Issues) should also be reviewed before starting using the plugin.

## Credentials

* Thank you to all people motivated to make this plugin live again!
* Thank you to the authors of the legacy versions, qTranslate-X by John Clause, qTranslate by Qian Qin and all the previous contributors.

## Desirable Unimplemented Features

* rename to a new official plugin (#601) and migrate with a separate configuration
* refactor integration API, possibly without json files (i18n-config.json)
* support for [localized hreflang](https://support.google.com/webmasters/answer/189077) with country/region codes (ISO 3166-1 alpha-2)
* full support for block editor (Gutenberg) with LSB (see #1097)
* unit/integration tests, automated CI tests
* utilities for DB maintenance (audit, cleanup)
* legacy of [desirable features](https://github.com/qtranslate/qtranslate-xt/wiki/Legacy-Desirable-Features).
