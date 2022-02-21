# Module: Slugs

Adds support for permalink (slug/URL) translations to qTranslate-XT.

This module was created from [qtranslate-slug](https://github.com/not-only-code/qtranslate-slug) plugin v1.1.8, integrated as [a module](https://github.com/qtranslate/qtranslate-xt/pull/1060).
For more details see the discussion [Include qtranslate slug into -XT](https://github.com/qtranslate/qtranslate-xt/issues/671).

## Frequently Asked Questions

### It works with posts and pages, but with other content type?
This plugin allows to translate slugs of: posts, pages, custom post types, categories, tags and custom taxonomies.

### Do I have to configure anything?
If you want to translate also the base permastructs (ex. *category*, *tag*, etc). Visit the settings page in the admin *Settings/Slug options*:
- Set the base permastructs for **post types** and **taxonomies** (If you setup a base permastruct for *categories* or *tags* in *Settings/Permalinks*, these will be overwritten by the translated ones).
- Save settings and that's all!

### How can I insert a language selector in my theme ?
You can choose to:
* use **Qtranslate Slug Widget** in your sidebar.
* place in your template `<?php if (function_exists('qts_language_menu') ) qts_language_menu('text'); ?>`. Options are: `dropdown`, `text`, `image`, and `both`.

### Appears an error 404, what can I do?
In the admin go to *Settings/Permalinks* or *Settings/Slug options* and save.

### I can't manage translations in Nav Menus.
That's because language selector metabox is hidden, if you are in admin *nav menus* screen, press the button **Screen options** (on top and right) and after, check the option *Languages*. It will appear a **Language** meta box on top of the left sidebar.

### How to get the current url in a specific language?
You can use `qts_get_url()` or the awkwardly named `qTranslateSlug_getSelfUrl()` which is an alias for the first one.

## TODO

In QTS slug options you can change the bases for taxonomies and custom post types.  
So, for example, you can change /category/ for /category/ for english and /categoria/ for spanish version.
But these won't work:
* slug with UTF8 charactes in taxonomies bases: example:  /類別/.. instead of /category/..
  utf8 in taxonomies works just fine: /category_zh/魚/
* slug with UTF8 charactes in custom post type bases : example:  /圖書/.. instead of /books/..
  utf8 in custom post slugs works just fine: /tushu/彩繪中國經典名著/
* translating custom post types archives with custom base name /tushu/ isnt working. but using utf8 in the the default slug, as expected : /中國/

## Contributors

Original plugin
* [Carlos Sanz García](https://github.com/not-only-code)
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

Integration into qTranslate-XT
* [Giovanni Cascione](https://github.com/spleen1981)
* [HerrVigg](https://github.com/herrvigg)
