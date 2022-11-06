<?php

/**
 * Names for languages in the corresponding native language.
 * @since 3.3
 */
function qtranxf_default_language_name() {
    return array(
        'en' => 'English',
        'zh' => '中文',   // 简体中
        'de' => 'Deutsch',
        'ru' => 'Русский',
        'fi' => 'suomi',
        'fr' => 'Français',
        'nl' => 'Nederlands',
        'sv' => 'Svenska',
        'it' => 'Italiano',
        'ro' => 'Română',
        'md' => 'Moldovenească',
        'hu' => 'Magyar',
        'ja' => '日本語',
        'es' => 'Español',
        'vi' => 'Tiếng Việt',
        'ar' => 'العربية',
        'pt' => 'Português',
        'pb' => 'Português do Brasil',
        'pl' => 'Polski',
        'gl' => 'galego',
        'tr' => 'Turkish',
        'et' => 'Eesti',
        'hr' => 'Hrvatski',
        'eu' => 'Euskera',
        'el' => 'Ελληνικά',
        'uk' => 'Українська',
        'ua' => 'Українська',  // TODO: disambiguate uk vs ua
        'cy' => 'Cymraeg',
        'ca' => 'Català',
        'sk' => 'Slovenčina',
        'lt' => 'Lietuvių',
        'kk' => 'Қазақ тілі',
        'cs' => 'Čeština',
        // tw => '繁體中文',
    );
}

/**
 * Locales for languages, matching WordPress locales when possible.
 * @since 3.3
 */
function qtranxf_default_locale() {
    // see locale -a for available locales
    return array(
        'de' => 'de_DE',
        'en' => 'en_US',
        'zh' => 'zh_CN',
        'ru' => 'ru_RU',
        'fi' => 'fi',
        'fr' => 'fr_FR',
        'nl' => 'nl_NL',
        'sv' => 'sv_SE',
        'it' => 'it_IT',
        'ro' => 'ro_RO',
        'md' => 'ro_RO',
        'hu' => 'hu_HU',
        'ja' => 'ja',
        'es' => 'es_ES',
        'vi' => 'vi',
        'ar' => 'ar',
        'pt' => 'pt_PT',
        'pb' => 'pt_BR',
        'pl' => 'pl_PL',
        'gl' => 'gl_ES',
        'tr' => 'tr_TR',
        'et' => 'et',
        'hr' => 'hr',
        'eu' => 'eu',
        'el' => 'el',
        'uk' => 'uk',
        'ua' => 'uk',  // TODO: disambiguate uk vs ua
        'cy' => 'cy',
        'ca' => 'ca',
        'sk' => 'sk_SK',
        'lt' => 'lt_LT',
        'kk' => 'kk',
        'cs' => 'cs_CZ',
        // 'tw' => 'zh_TW',
    );
}

/**
 * HTML locales for languages
 * @since 3.4
 */
function qtranxf_default_locale_html() {
    //HTML locales for languages are not provided by default
    return array();
}

/**
 * Language not available messages
 * @since 3.3
 */
function qtranxf_default_not_available() {
    // %LANG:<normal_separator>:<last_separator>% generates a list of languages separated by <normal_separator>
    // except for the last one, where <last_separator> will be used instead.
    // Not Available Message
    // Sorry, this entry is only available in "%LANG:, :" and "%".
    return array(
        'de' => 'Leider ist der Eintrag nur auf %LANG:, : und % verfügbar.',
        'en' => 'Sorry, this entry is only available in %LANG:, : and %.',
        'zh' => '对不起，此内容只适用于%LANG:，:和%。',
        'ru' => 'Извините, этот текст доступен только на &ldquo;%LANG:&rdquo;, &ldquo;:&rdquo; и &ldquo;%&rdquo;.',
        'fi' => 'Tämä teksti on valitettavasti saatavilla vain kielillä: %LANG:, : ja %.',
        'fr' => 'Désolé, cet article est seulement disponible en %LANG:, : et %.',
        'nl' => 'Onze verontschuldigingen, dit bericht is alleen beschikbaar in het %LANG:, : en %.',
        'sv' => 'Tyvärr är denna artikel enbart tillgänglig på %LANG:, : och %.',
        'it' => 'Ci spiace, ma questo articolo è disponibile soltanto in %LANG:, : e %.',
        'ro' => 'Din păcate acest articol este disponibil doar în %LANG:, : și %.',
        'md' => 'Ne pare rău, acest articol este disponibil numai în %LANG:, : şi  %.',
        'hu' => 'Sajnos ennek a bejegyzésnek csak %LANG:, : és % nyelvű változata van.',
        'ja' => '申し訳ありません、このコンテンツはただ今　%LANG:、 :と %　のみです。',
        'es' => 'Disculpa, pero esta entrada está disponible sólo en %LANG:, : y %.',
        'vi' => 'Rất tiếc, mục này chỉ tồn tại ở %LANG:, : và %.',
        'ar' => 'عفوا، هذه المدخلة موجودة فقط في %LANG:, : و %.',
        'pt' => 'Desculpe, este conteúdo só está disponível em %LANG:, : e %.',
        'pb' => 'Desculpe-nos, mas este texto está apenas disponível em %LANG:, : y %.',
        'pl' => 'Przepraszamy, ten wpis jest dostępny tylko w języku %LANG:, : i %.',
        'gl' => 'Sentímolo moito, ista entrada atopase unicamente en %LANG;,: e %.',
        'tr' => 'Sorry, this entry is only available in %LANG:, : and %.',
        'et' => 'Vabandame, see kanne on saadaval ainult %LANG : ja %.',
        'hr' => 'Žao nam je, ne postoji prijevod na raspolaganju za ovaj proizvod još %LANG:, : i %.',
        'eu' => 'Sentitzen dugu, baina sarrera hau %LANG-z:, : eta % bakarrik dago.',
        'el' => 'Συγγνώμη,αυτή η εγγραφή είναι διαθέσιμη μόνο στα %LANG:, : και %.',
        'uk' => 'Вибачте цей текст доступний тільки в &ldquo;%LANG:&rdquo;, &ldquo;: і &ldquo;%&rdquo;.',
        'ua' => 'Вибачте цей текст доступний тільки в &ldquo;%LANG:&rdquo;, &ldquo;: і &ldquo;%&rdquo;.',
        // TODO: disambiguate uk vs ua
        'cy' => 'Mae&#8217;n ddrwg gen i, mae\'r cofnod hwn dim ond ar gael mewn %LANG:, : a %.',
        'ca' => 'Ho sentim, aquesta entrada es troba disponible únicament en %LANG:, : i %.',
        'sk' => 'Ľutujeme, táto stránka je dostupná len v %LANG:, : a %.',
        'lt' => 'Atsiprašome, šis puslapis galimas tik %LANG:, : ir %.',
        'kk' => 'Кешіріңіз, бұл кіріс тек %LANG:, : және % тілінде ғана қол жетімді.',
        'cs' => 'Omlouváme se, tato položka je k dispozici pouze v %LANG:, : a %.',
        // 'tw' => '对不起，此内容只适用于%LANG:，:和%。',
    );
}

/**
 * Default date format by language.
 * @todo Deprecate strftime format
 * @since 3.3
 */
function qtranxf_default_date_format() {
    return array(
        'en' => '%A %B %e%q, %Y',
        'de' => '%A, \d\e\r %e. %B %Y',
        'zh' => '%x %A',
        'ru' => '%A %B %e%q, %Y',
        'fi' => '%d.%m.%Y',
        'fr' => '%A %e %B %Y',
        'nl' => '%d/%m/%y',
        'sv' => '%Y-%m-%d',
        'it' => '%e %B %Y',
        'ro' => '%A, %e %B %Y',
        'md' => '%A, %e %B %Y',
        'hu' => '%Y %B %e, %A',
        'ja' => '%Y年%m月%d日',
        'es' => '%d \d\e %B \d\e %Y',
        'vi' => '%d/%m/%Y',
        'ar' => '%d/%m/%Y',
        'pt' => '%A, %e \d\e %B \d\e %Y',
        'pb' => '%d \d\e %B \d\e %Y',
        'pl' => '%d/%m/%y',
        'gl' => '%d \d\e %B \d\e %Y',
        'tr' => '%A %B %e%q, %Y',
        'et' => '%A %B %e%q, %Y',
        'hr' => '%d/%m/%Y',
        'eu' => '%Y %B %e, %A',
        'el' => '%d/%m/%y',
        'uk' => '%A %B %e%q, %Y',
        'ua' => '%A %B %e%q, %Y',  // TODO: disambiguate uk vs ua
        'cy' => '%A %B %e%q, %Y',  // TODO check if valid
        'ca' => 'j F, Y',
        'sk' => 'j.F Y',
        'lt' => '%Y.%m.%d',
        'kk' => '%A, \d\e\r %e. %B %Y',
        'cs' => '%e. %m. %Y',
        // 'tw'  => '%x %A',
    );
}

/**
 * Default time format by language.
 * @todo Deprecate strftime format
 * @since 3.3
 */
function qtranxf_default_time_format() {
    return array(
        'en' => '%I:%M %p',
        'de' => '%H:%M',
        'zh' => '%I:%M%p',
        'ru' => '%H:%M',
        'fi' => '%H:%M',
        'fr' => '%H:%M',
        'nl' => '%H:%M',
        'sv' => '%H:%M',
        'it' => '%H:%M',
        'ro' => '%H:%M',
        'md' => '%H:%M',
        'hu' => '%H:%M',
        'ja' => '%H:%M',
        'es' => '%H:%M hrs.',
        'vi' => '%H:%M',
        'ar' => '%H:%M',
        'pt' => '%H:%M',
        'pb' => '%H:%M hrs.',
        'pl' => '%H:%M',
        'gl' => '%H:%M hrs.',
        'tr' => '%H:%M',
        'et' => '%H:%M',
        'hr' => '%H:%M',
        'eu' => '%H:%M',
        'el' => '%H:%M',
        'uk' => '%H:%M',
        'ua' => '%H:%M',    // TODO: disambiguate uk vs ua
        'cy' => '%I:%M %p', // TODO check if valid
        'ca' => 'G:i',
        'sk' => 'G:i',
        'lt' => '%H:%M',
        'kk' => '%H:%M',
        'cs' => '%H : %m',
        // 'tw' => '%I:%M%p',
    );
}

/**
 * Default flag file by language.
 * Look in /flags/ directory for a huge list of flags for usage.
 * @since 3.3
 */
function qtranxf_default_flag() {
    return array(
        'en' => 'gb.png',
        'de' => 'de.png',
        'zh' => 'cn.png',
        'ru' => 'ru.png',
        'fi' => 'fi.png',
        'fr' => 'fr.png',
        'nl' => 'nl.png',
        'sv' => 'se.png',
        'it' => 'it.png',
        'ro' => 'ro.png',
        'md' => 'md.png',
        'hu' => 'hu.png',
        'ja' => 'jp.png',
        'es' => 'es.png',
        'vi' => 'vn.png',
        'ar' => 'arle.png',
        'pt' => 'pt.png',
        'pb' => 'br.png',
        'pl' => 'pl.png',
        'gl' => 'galego.png',
        'tr' => 'tr.png',
        'et' => 'ee.png',
        'hr' => 'hr.png',
        'eu' => 'eu_ES.png',
        'el' => 'gr.png',
        'uk' => 'ua.png',
        'ua' => 'ua.png',  // TODO: disambiguate uk vs ua
        'cy' => 'cy_GB.png',
        'ca' => 'catala.png',
        'sk' => 'sk.png',
        'lt' => 'lt.png',
        'kk' => 'kz.png',
        'cs' => 'cz.png',
        // 'tw' = 'tw.png',
    );
}

/**
 * Full country names as locales for Windows systems, in English.
 * @since 3.3
 */
function qtranxf_default_windows_locale() {
    return array(
        'aa' => "Afar",
        'ab' => "Abkhazian",
        'ae' => "Avestan",
        'af' => "Afrikaans",
        'am' => "Amharic",
        'ar' => "Arabic",
        'as' => "Assamese",
        'ay' => "Aymara",
        'az' => "Azerbaijani",
        'ba' => "Bashkir",
        'be' => "Belarusian",
        'bg' => "Bulgarian",
        'bh' => "Bihari",
        'bi' => "Bislama",
        'bn' => "Bengali",
        'bo' => "Tibetan",
        'br' => "Breton",
        'bs' => "Bosnian",
        'ca' => "Catalan",
        'ce' => "Chechen",
        'ch' => "Chamorro",
        'co' => "Corsican",
        'cs' => "Czech",
        'cu' => "Church Slavic",
        'cv' => "Chuvash",
        'cy' => "Welsh",
        'da' => "Danish",
        'de' => "German",
        'dz' => "Dzongkha",
        'el' => "Greek",
        'en' => "English",
        'eo' => "Esperanto",
        'es' => "Spanish",
        'et' => "Estonian",
        'eu' => "Basque",
        'fa' => "Persian",
        'fi' => "Finnish",
        'fj' => "Fijian",
        'fo' => "Faeroese",
        'fr' => "French",
        'fy' => "Frisian",
        'ga' => "Irish",
        'gd' => "Gaelic (Scots)",
        'gl' => "Gallegan",
        'gn' => "Guarani",
        'gu' => "Gujarati",
        'gv' => "Manx",
        'ha' => "Hausa",
        'he' => "Hebrew",
        'hi' => "Hindi",
        'ho' => "Hiri Motu",
        'hr' => "Croatian",
        'hu' => "Hungarian",
        'hy' => "Armenian",
        'hz' => "Herero",
        'ia' => "Interlingua",
        'id' => "Indonesian",
        'ie' => "Interlingue",
        'ik' => "Inupiaq",
        'is' => "Icelandic",
        'it' => "Italian",
        'iu' => "Inuktitut",
        'ja' => "Japanese",
        'jw' => "Javanese",
        'ka' => "Georgian",
        'ki' => "Kikuyu",
        'kj' => "Kuanyama",
        'kk' => "Kazakh",
        'kl' => "Kalaallisut",
        'km' => "Khmer",
        'kn' => "Kannada",
        'ko' => "Korean",
        'ks' => "Kashmiri",
        'ku' => "Kurdish",
        'kv' => "Komi",
        'kw' => "Cornish",
        'ky' => "Kirghiz",
        'la' => "Latin",
        'lb' => "Letzeburgesch",
        'ln' => "Lingala",
        'lo' => "Lao",
        'lt' => "Lithuanian",
        'lv' => "Latvian",
        'mg' => "Malagasy",
        'mh' => "Marshall",
        'mi' => "Maori",
        'mk' => "Macedonian",
        'ml' => "Malayalam",
        'mn' => "Mongolian",
        'mo' => "Moldavian",
        'mr' => "Marathi",
        'ms' => "Malay",
        'mt' => "Maltese",
        'my' => "Burmese",
        'na' => "Nauru",
        'nb' => "Norwegian Bokmal",
        'nd' => "debele, North",
        'ne' => "Nepali",
        'ng' => "Ndonga",
        'nl' => "Dutch",
        'nn' => "Norwegian Nynorsk",
        'no' => "Norwegian",
        'nr' => "debele, South",
        'nv' => "Navajo",
        'ny' => "hichewa; Nyanja",
        'oc' => "Occitan (ost 1500)",
        'om' => "Oromo",
        'or' => "Oriya",
        'os' => "ssetian; Ossetic",
        'pa' => "Panjabi",
        'pi' => "Pali",
        'pl' => "Polish",
        'ps' => "Pushto",
        'pt' => "Portuguese",
        'pb' => "Brazilian Portuguese",
        'qu' => "Quechua",
        'rm' => "haeto-Romance",
        'rn' => "Rundi",
        'ro' => "Romanian",
        'ru' => "Russian",
        'rw' => "Kinyarwanda",
        'sa' => "Sanskrit",
        'sc' => "Sardinian",
        'sd' => "Sindhi",
        'se' => "Sami",
        'sg' => "Sango",
        'si' => "Sinhalese",
        'sk' => "Slovak",
        'sl' => "Slovenian",
        'sm' => "Samoan",
        'sn' => "Shona",
        'so' => "Somali",
        'sq' => "Albanian",
        'sr' => "Serbian",
        'ss' => "Swati",
        'st' => "Sotho",
        'su' => "Sundanese",
        'sv' => "Swedish",
        'sw' => "Swahili",
        'ta' => "Tamil",
        'te' => "Telugu",
        'tg' => "Tajik",
        'th' => "Thai",
        'ti' => "Tigrinya",
        'tk' => "Turkmen",
        'tl' => "Tagalog",
        'tn' => "Tswana",
        'to' => "Tonga",
        'tr' => "Turkish",
        'ts' => "Tsonga",
        'tt' => "Tatar",
        'tw' => "Twi",
        'ug' => "Uighur",
        'uk' => "Ukrainian",
        'ua' => "Ukrainian",
        'ur' => "Urdu",
        'uz' => "Uzbek",
        'vi' => "Vietnamese",
        'vo' => "Volapuk",
        'wo' => "Wolof",
        'xh' => "Xhosa",
        'yi' => "Yiddish",
        'yo' => "Yoruba",
        'za' => "Zhuang",
        'zh' => "Chinese",
        'zu' => "Zulu",
    );
}
