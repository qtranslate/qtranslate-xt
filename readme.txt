# qTranslate-XT (eXTended) #
Developed by: new qTranslate community, from qTranslate-X by John Clause and qTranslate by Qian Qin  
Contributors: herrvigg, johnclause, chineseleper, Vavooon, grafcom  
Tags: multilingual, language, admin, tinymce, bilingual, widget, switcher, i18n, l10n, multilanguage, translation  
Requires: 4.0  
Tested up to: 4.9.7  
Stable tag: N/A  
License: GPLv3 or later  
License URI: http://www.gnu.org/licenses/gpl-3.0.html  

Adds a user-friendly multilingual dynamic content management.

## Description ##

The qTranslate-XT plugin is an *eXTended* version of qTranslate-X that we as a community are trying to revive, since the [original plugin](https://wordpress.org/plugins/qtranslate-x/) is abandoned by its author.  
Our first goal is to maintain the main features of this plugin with the last Wordpress and PHP updates. The migration to Gutenberg will definitely be a critical milestone for the survival of this plugin.

*Disclaimer: in many sections of the documentation and admin messages, the plugin is still named qTranslate-X and not qTranslate-XT, so don't be confused.*

We are currently building a new organization [qTranslate](https://github.com/qTranslate) to give qTranslate a new life. It is not clear yet if it will survive under this name but anyone is welcome to participate!

GitHub repository of the new repo: [https://github.com/qTranslate-Team/qtranslate-x.git](https://github.com/qTranslate/qtranslate-xt).

## Installation ##

The XT version is not an official plugin so it is not available at Wordpress.org. The initial installation must be done manually so you need the permissions to access the `plugins` folder of your Wordpress installation. Contact your system administrator if needed.

1. Download the last release from [GitHub](https://github.com/qTranslate/qtranslate-xt/releases), taking the archive either in zip or tar.gz format (usually zip for Windows users). Alternatively, for developers or those familiar with git, you can `git clone` the new repo.
1. Uncompress the archive in your plugin folder (`/wp-content/plugins`) to extract all the files (keeping the directory structure) to the `/wp-content/plugins/` directory.
1. Deactivate plugin qTranslate-X, qTranslate, mqTranslate, qTranslate Plus, zTranslate or any other multilingual plugin, if you are running any.
1. Activate qTranslate-XT through the 'Plugins' (`/wp-admin/plugins.php`) configuration page in WordPress.

Then proceed with the initial setup as for qTranslate-X:

1. Open Settings->Languages configuration page and add/delete/disable any languages you need.
1. Add the "qTranslate Language Chooser" widget or "Language Switcher" menu item to let your visitors switch the language.

Check the FAQ for further instructions.

## Frequently Asked Questions ##

### Why is qtranslate-X not maintained anymore? ###
The previous qTranslate-Team was only one person. We tried to contact the author many times but we got [no answer since 2016](https://github.com/qTranslate-Team/qtranslate-x/issues/579).  
Our goal is to build up a real team and make this plugin available again for the whole community. There are still many questions about the future but for now we should focus on new releases.

### I'm still using qTranslate-X, can I test to qTranslate-XT? ###
YES! Currently you can have both qTranslate-X and qTranslate-XT installed in the plugins folder for experimentations, but you should have at most one active at any time!  
**BOTH X AND XT SHARE THE SAME OPTIONS!** So if you change some options in one and switch between the plugins, the last changes will remain valid for the other one.  
*Disclaimer: we cannot guarantee that all the functionalites are preserved and the installation is at your own responsibility. Be sure to save your database regularly.*

### Where can I find detailed instructions for startup? ###
Please check the legacy website:

* For the new installers, it may be useful to read [Startup Guide](https://qtranslatexteam.wordpress.com/startup-guide/ "Startup Guide").
* It is important to read [migration instructions](https://qtranslatexteam.wordpress.com/migration/ "Migration Guide"), if you previously used other multilingual plugin.
* Read [Integration Guide](https://qtranslatexteam.wordpress.com/integration/ "Integration Guide") when you need to make theme or other plugin custom fields to be multilingual.
* The legacy FAQ is available at "qTranslate-X explained" website: [https://qtranslatexteam.wordpress.com/faq/](https://qtranslatexteam.wordpress.com/faq/ "qTranslate-X explained FAQ").

### How can qTranslate-XT be updated with the last release? ###
Since this plugin is not available at wordpress.org yet, we recommend you to install [GitHub Updater](https://github.com/afragen/github-updater). This is is an awesome tool to update plugins from a git repo (with many other features). It checks regularly the last release available in github (from the git tags) and compares it to your current version (see header of qtranslate.php). If a new release is available you will be given the possibility to update your plugin as for a regular plugin from Wordpress.

Alternatively you can delete the current folder and repeat the installation from the last archive. Make sure to deactivate the previous version and then activate the new one, otherwise you will miss the execution of activation hooks and some options may become misconfigured. 

Note for developers:

* since GitHub Updater deploys the archive (tarball) you may lose your current git project if you installed through `git clone`. If you want to use a cloned version in production you should not update through GHU but rather `git pull`.
* old releases may contain legacy headers that can become problematic. Be very cautious if you customize the updates for given branches!

## Upgrade Notice ##

Obsolete. 

## Screenshots ##

See [original plugin](https://wordpress.org/plugins/qtranslate-x/).

## Changelog ##

See CHANGELOG.md. New releases to come!

## Known Issues ##

The previous issues have been duplicated to our new git repository. Please check the [git issues](https://github.com/qTranslate/qtranslate-xt/issues) before creating new ones.

The [legacy issues](https://qtranslatexteam.wordpress.com/known-issues/) should also be reviewed before starting using the plugin.

## Credentials ##

Thank you to all people motivated to make this plugin live again!  
Thank you to the authors of the legacy versions, first of all qTranslate-X by John Clause and qTranslate by Qian Qin and all the previous participants.  

## Desirable Unimplemented Features ##

* support for Gutenberg (!!)
* support for translatable slugs (!)
* support for WooCommerce (revive the add-on!)
* legacy of [desirable features](https://qtranslatexteam.wordpress.com/desirable/).
