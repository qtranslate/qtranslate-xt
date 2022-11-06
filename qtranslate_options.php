<?php // encoding: utf-8
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Option types for front/admin settings.
 */
const QTX_STRING      = 1;
const QTX_BOOLEAN     = 2;
const QTX_INTEGER     = 3;
const QTX_URL         = 4;
const QTX_LANGUAGE    = 5;
const QTX_ARRAY       = 6;
const QTX_BOOLEAN_SET = 7;
const QTX_TEXT        = 8;  // multi-line string

/**
 * URL modes defining how the languages are set for HTTP.
 */
const QTX_URL_QUERY   = 1;  // Query string: domain.com?lang=en
const QTX_URL_PATH    = 2;  // Pre-path: domain.com/en (default)
const QTX_URL_DOMAIN  = 3;  // Pre-domain: en.domain.com
const QTX_URL_DOMAINS = 4;  // Domain per language

/**
 * Date/time conversion, see "use_strftime" option.
 */
const QTX_DATE_WP           = 0;  // obsolete, value reserved
const QTX_STRFTIME_OVERRIDE = 1;  // TODO: deprecate strftime format
const QTX_DATE_OVERRIDE     = 2;  // deprecated
const QTX_DATE              = 3;  // default format at first activation - not consistent with default date/time values
const QTX_STRFTIME          = 4;  // deprecated

/**
 * Translation of WordPress / qTranslate options.
 */
const QTX_FILTER_OPTIONS_ALL     = 0;
const QTX_FILTER_OPTIONS_LIST    = 1;
const QTX_FILTER_OPTIONS_DEFAULT = 'blogname blogdescription widget_%';

/**
 * Editor mode to handle the ML translations.
 */
const QTX_EDITOR_MODE_LSB    = 0;  // Language Switching Buttons
const QTX_EDITOR_MODE_RAW    = 1;  // ML not translated
const QTX_EDITOR_MODE_SINGLE = 2;  // ML translated for current admin language

/**
 * How the translatable fields are highlighted (CSS styles).
 */
const QTX_HIGHLIGHT_MODE_NONE        = 0;
const QTX_HIGHLIGHT_MODE_BORDER_LEFT = 1;
const QTX_HIGHLIGHT_MODE_BORDER      = 2;
const QTX_HIGHLIGHT_MODE_LEFT_SHADOW = 3;
const QTX_HIGHLIGHT_MODE_OUTLINE     = 4;
const QTX_HIGHLIGHT_MODE_CUSTOM_CSS  = 9;

/**
 * Cookies settings.
 */
const QTX_COOKIE_NAME_FRONT = 'qtrans_front_language';
const QTX_COOKIE_NAME_ADMIN = 'qtrans_admin_language';
const QTX_COOKIE_SAMESITE   = 'Lax';

/**
 * File extensions excluded for the translations of URL links, assumed to be language-independent.
 */
const QTX_IGNORE_FILE_TYPES = 'gif,jpg,jpeg,png,svg,pdf,swf,tif,rar,zip,7z,mpg,divx,mpeg,avi,css,js,mp3,mp4,apk';

/**
 * Language code format: ISO 639-1 (2 alpha), 639-2 or 639-3 (3 alpha).
 */
const QTX_LANG_CODE_FORMAT = '[a-z]{2,3}';

/**
 * Module option names.
 */
const QTX_OPTIONS_MODULES_STATE = 'qtranslate_modules_state';
const QTX_OPTIONS_MODULE_ACF    = 'qtranslate_module_acf';
const QTX_OPTIONS_MODULE_SLUGS  = 'qtranslate_module_slugs';

/**
 * @global array $q_config Global configuration, interpreted from settings and i18n configuration loaded from JSON.
 */
global $q_config;

/**
 * @global array $qtranslate_options Global options, mapped at a lower level to the settings.
 */
global $qtranslate_options;

/**
 * array of default option values
 * other plugins and themes should not use global variables directly, they are subject to change at any time.
 * @since 3.3
 */
function qtranxf_set_default_options( &$ops ) {
    $ops = array();

    // options processed in a standardized way
    $ops['front'] = array();

    $ops['front']['int'] = array(
        'url_mode'            => QTX_URL_PATH,  // sets default url mode
        'use_strftime'        => QTX_DATE,  // strftime usage (backward compatibility)
        'filter_options_mode' => QTX_FILTER_OPTIONS_ALL,
        'language_name_case'  => 0  //Camel Case
    );

    $ops['front']['bool'] = array(
        'detect_browser_language'        => true,   // enables browser language detection
        'hide_untranslated'              => false,  // hide pages without content
        'show_menu_alternative_language' => false,  // hide menu items without a translation
        'show_displayed_language_prefix' => true,
        'show_alternative_content'       => false,
        'hide_default_language'          => true,   // hide language tag for default language in urls
        'use_secure_cookie'              => false,
        'header_css_on'                  => true,
    );

    // single line options
    $ops['front']['str'] = array();

    // multi-line options
    $ops['front']['text'] = array(
        'header_css' => 'qtranxf_front_header_css_default',
    );

    $ops['front']['array'] = array(
        //'term_name'// uniquely special treatment
        'text_field_filters' => array(),
        'front_config'       => array(),
    );

    // options processed in a special way

    // store other default values of specially handled options
    $ops['default_value'] = array(
        'default_language'       => null,   // string
        'enabled_languages'      => null,   // array
        'qtrans_compatibility'   => false,  // enables compatibility with former qtrans_* functions
        'disable_client_cookies' => false,  // bool
        'flag_location'          => null,   // string
        'filter_options'         => QTX_FILTER_OPTIONS_DEFAULT, // array
        'ignore_file_types'      => QTX_IGNORE_FILE_TYPES,  // array
        'domains'                => null,   // array
    );

    // must have function 'qtranxf_default_option_name()' which returns a default value for option 'option_name'.
    $ops['languages'] = array(
        'language_name' => 'qtranslate_language_names',
        'locale'        => 'qtranslate_locales',
        'locale_html'   => 'qtranslate_locales_html',
        'not_available' => 'qtranslate_na_messages',
        'date_format'   => 'qtranslate_date_formats',
        'time_format'   => 'qtranslate_time_formats',
        'flag'          => 'qtranslate_flags',
    );

    /**
     * A chance to add additional options
     */
    $ops = apply_filters( 'qtranslate_option_config', $ops );
}

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

function qtranxf_language_predefined( $lang ) {
    $language_names = qtranxf_default_language_name();

    return isset( $language_names[ $lang ] );
}

function qtranxf_language_configured( $prop, $opn = null ) {
    global $qtranslate_options;
    $val = call_user_func( 'qtranxf_default_' . $prop );
    if ( ! $opn ) {
        if ( isset( $qtranslate_options['languages'][ $prop ] ) ) {
            $opn = $qtranslate_options['languages'][ $prop ];
        } else {
            $opn = 'qtranslate_' . $prop;
        }
    }
    $opt = get_option( $opn, array() );
    if ( $opt ) {
        $val = array_merge( $val, $opt );
    }

    return $val;
}

/**
 * Fill merged array of stored and pre-defined language properties
 * @since 3.3
 */
function qtranxf_languages_configured( &$cfg ) {
    global $qtranslate_options;
    foreach ( $qtranslate_options['languages'] as $name => $option ) {
        $cfg[ $name ] = qtranxf_language_configured( $name, $option );
    }

    return $cfg;
}

/**
 * Load enabled languages properties from  database
 * @since 3.3
 */
function qtranxf_load_languages_enabled() {
    global $q_config, $qtranslate_options;
    foreach ( $qtranslate_options['languages'] as $name => $option ) {
        $func = 'qtranxf_default_' . $name;
        qtranxf_load_option_func( $name, $option, $func );
        $val = array();
        $def = null;
        foreach ( $q_config['enabled_languages'] as $lang ) {
            if ( isset( $q_config[ $name ][ $lang ] ) ) {
                $val[ $lang ] = $q_config[ $name ][ $lang ];
            } else {
                if ( is_null( $def ) && function_exists( $func ) ) {
                    $def = call_user_func( $func );
                }
                $val[ $lang ] = isset( $def[ $lang ] ) ? $def[ $lang ] : '';
            }
        }
        $q_config[ $name ] = $val;
    }
}
