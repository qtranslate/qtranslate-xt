<?php // encoding: utf-8
if ( !defined( 'ABSPATH' ) ) exit;

/* There is no need to edit anything here! */
define('QTX_STRING',	1);
define('QTX_BOOLEAN',	2);
define('QTX_INTEGER',	3);
define('QTX_URL',	4);
define('QTX_LANGUAGE',	5);
define('QTX_ARRAY',	6);
define('QTX_BOOLEAN_SET',	7);
define('QTX_TEXT',	8);//multi-line string

define('QTX_URL_QUERY'  , 1);// query: domain.com?lang=en
define('QTX_URL_PATH'   , 2);// pre path: domain.com/en
define('QTX_URL_DOMAIN' , 3);// pre domain: en.domain.com
define('QTX_URL_DOMAINS', 4);// domain per language

define('QTX_STRFTIME_OVERRIDE',	1);
define('QTX_DATE_OVERRIDE',	2);
define('QTX_DATE',	3);
define('QTX_STRFTIME',	4);

define('QTX_FILTER_OPTIONS_ALL', 0);
define('QTX_FILTER_OPTIONS_LIST', 1);
define('QTX_FILTER_OPTIONS_DEFAULT','blogname blogdescription widget_%');

define('QTX_EX_DATE_FORMATS_DEFAULT','\'U\'');

define('QTX_EDITOR_MODE_LSB', 0);//Language Switching Buttons
define('QTX_EDITOR_MODE_RAW', 1);
define('QTX_EDITOR_MODE_SINGLGE', 2);

define('QTX_HIGHLIGHT_MODE_NONE', 0);
define('QTX_HIGHLIGHT_MODE_BORDER_LEFT', 1);
define('QTX_HIGHLIGHT_MODE_BORDER', 2);
define('QTX_HIGHLIGHT_MODE_LEFT_SHADOW', 3);
define('QTX_HIGHLIGHT_MODE_OUTLINE', 4);
define('QTX_HIGHLIGHT_MODE_CUSTOM_CSS', 9);

define('QTX_COOKIE_NAME_FRONT','qtrans_front_language');
define('QTX_COOKIE_NAME_ADMIN','qtrans_admin_language');

define('QTX_IGNORE_FILE_TYPES','gif,jpg,jpeg,png,svg,pdf,swf,tif,rar,zip,7z,mpg,divx,mpeg,avi,css,js,mp3,mp4,apk');


global $q_config;
global $qtranslate_options;


/**
 * array of default option values
 * other plugins and themes should not use global variables directly, they are subject to change at any time.
 * @since 3.3
 */
function qtranxf_set_default_options(&$ops)
{
	$ops = array();

	//options processed in a standardized way
	$ops['front'] = array();

	$ops['front']['int']=array(
		'url_mode' => QTX_URL_PATH,// sets default url mode
		'use_strftime' => QTX_DATE,// strftime usage (backward compability)
		'filter_options_mode' => QTX_FILTER_OPTIONS_ALL,
	);

	$ops['front']['bool']=array(
		'detect_browser_language' => true,// enables browser language detection
		'hide_untranslated' => false,// hide pages without content
		'show_displayed_language_prefix' => true,
		'show_alternative_content' => false,
		'hide_default_language' => true,// hide language tag for default language in urls
		'use_secure_cookie' => false,
		'header_css_on' => true,
	);

	//single line options
	$ops['front']['str']=array(
	);

	//multi-line options
	$ops['front']['text']=array(
		'header_css' => 'qtranxf_front_header_css_default',
	);

	$ops['front']['array']=array(
		//'term_name'// uniquely special treatment
		'text_field_filters' => array(),
		'front_config' => array(),
	);

	//options processed in a special way

	// store other default values of specially handled options
	$ops['default_value']=array(
		'default_language' => null,//string
		'enabled_languages' => null,//array
		'qtrans_compatibility' => false,//enables compatibility with former qtrans_* functions
		'disable_client_cookies' => false,//bool
		'flag_location' => null,//string
		'filter_options' => QTX_FILTER_OPTIONS_DEFAULT,//array
		'ignore_file_types' => QTX_IGNORE_FILE_TYPES,//array
		'domains' => null,//array
	);

	//must have function 'qtranxf_default_option_name()' which returns a default value for option 'option_name'.
	$ops['languages']=array(
		'language_name' => 'qtranslate_language_names',
		'locale' => 'qtranslate_locales',
		'locale_html' => 'qtranslate_locales_html',
		'not_available' => 'qtranslate_na_messages',
		'date_format' => 'qtranslate_date_formats',
		'time_format' => 'qtranslate_time_formats',
		'flag' => 'qtranslate_flags',
		//'windows_locale' => null,//this property is not stored
	);

	/**
	 * A chance to add additional options
	*/
	$ops = apply_filters('qtranslate_option_config',$ops);
}

/* pre-Domain Endings - for future use
	$cfg['pre_domain'] = array();
	$cfg['pre_domain']['de'] = 'de';
	$cfg['pre_domain']['en'] = 'en';
	$cfg['pre_domain']['zh'] = 'zh';
	$cfg['pre_domain']['ru'] = 'ru';
	$cfg['pre_domain']['fi'] = 'fs';
	$cfg['pre_domain']['fr'] = 'fr';
	$cfg['pre_domain']['nl'] = 'nl';
	$cfg['pre_domain']['sv'] = 'sv';
	$cfg['pre_domain']['it'] = 'it';
	$cfg['pre_domain']['ro'] = 'ro';
	$cfg['pre_domain']['hu'] = 'hu';
	$cfg['pre_domain']['ja'] = 'ja';
	$cfg['pre_domain']['es'] = 'es';
	$cfg['pre_domain']['vi'] = 'vi';
	$cfg['pre_domain']['ar'] = 'ar';
	$cfg['pre_domain']['pt'] = 'pt';
	$cfg['pre_domain']['pb'] = 'pb';
	$cfg['pre_domain']['pl'] = 'pl';
	$cfg['pre_domain']['gl'] = 'gl';
	$cfg['pre_domain']['tr'] = 'tr';
*/

/**
 * Names for languages in the corresponding language, add more if needed
 * @since 3.3
 */
function qtranxf_default_language_name()
{
	$cfg = array();
	$cfg['de'] = 'Deutsch';
	$cfg['en'] = 'English';
	$cfg['zh'] = '中文';
	$cfg['ru'] = 'Русский';
	$cfg['fi'] = 'suomi';
	$cfg['fr'] = 'Français';
	$cfg['nl'] = 'Nederlands';
	$cfg['sv'] = 'Svenska';
	$cfg['it'] = 'Italiano';
	$cfg['ro'] = 'Română';
	$cfg['hu'] = 'Magyar';
	$cfg['ja'] = '日本語';
	$cfg['es'] = 'Español';
	$cfg['vi'] = 'Tiếng Việt';
	$cfg['ar'] = 'العربية';
	$cfg['pt'] = 'Português';
	$cfg['pb'] = 'Português do Brasil';
	$cfg['pl'] = 'Polski';
	$cfg['gl'] = 'galego';
	$cfg['tr'] = 'Turkish';
	$cfg['et'] = 'Eesti';
	$cfg['hr'] = 'Hrvatski';
	$cfg['eu'] = 'Euskera';
	$cfg['el'] = 'Ελληνικά';
	$cfg['ua'] = 'Українська';
	//$cfg['tw'] = '中文';
	return $cfg;
}

/**
 * Locales for languages
 * @since 3.3
 */
function qtranxf_default_locale()
{
	// see locale -a for available locales
	$cfg = array();
	$cfg['de'] = 'de_DE';
	$cfg['en'] = 'en_US';
	$cfg['zh'] = 'zh_CN';
	$cfg['ru'] = 'ru_RU';
	$cfg['fi'] = 'fi_FI';
	$cfg['fr'] = 'fr_FR';
	$cfg['nl'] = 'nl_NL';
	$cfg['sv'] = 'sv_SE';
	$cfg['it'] = 'it_IT';
	$cfg['ro'] = 'ro_RO';
	$cfg['hu'] = 'hu_HU';
	$cfg['ja'] = 'ja';
	$cfg['es'] = 'es_ES';
	$cfg['vi'] = 'vi';
	$cfg['ar'] = 'ar';
	$cfg['pt'] = 'pt_PT';
	$cfg['pb'] = 'pt_BR';
	$cfg['pl'] = 'pl_PL';
	$cfg['gl'] = 'gl_ES';
	$cfg['tr'] = 'tr_TR';
	$cfg['et'] = 'et_EE';
	$cfg['hr'] = 'hr_HR';
	$cfg['eu'] = 'eu_ES';
	$cfg['el'] = 'el_GR';
	$cfg['ua'] = 'uk';
	//$cfg['tw'] = 'zh_TW';
	return $cfg;
}

/**
 * HTML locales for languages
 * @since 3.4
 */
function qtranxf_default_locale_html(){
	//HTML locales for languages are not provided by default
	return array();
}

/**
 * Language not available messages
 * @since 3.3
 */
function qtranxf_default_not_available()
{
	// %LANG:<normal_separator>:<last_separator>% generates a list of languages separated by <normal_separator> except for the last one, where <last_separator> will be used instead.
	$cfg = array();
	$cfg['de'] = 'Leider ist der Eintrag nur auf %LANG:, : und % verfügbar.';
	$cfg['en'] = 'Sorry, this entry is only available in %LANG:, : and %.';
	$cfg['zh'] = '对不起，此内容只适用于%LANG:，:和%。';
	$cfg['ru'] = 'Извините, этот техт доступен только в %LANG:, : и %.';
	$cfg['fi'] = 'Anteeksi, mutta tämä kirjoitus on saatavana ainoastaan näillä kielillä: %LANG:, : ja %.';
	$cfg['fr'] = 'Désolé, cet article est seulement disponible en %LANG:, : et %.';
	$cfg['nl'] = 'Onze verontschuldigingen, dit bericht is alleen beschikbaar in %LANG:, : en %.';
	$cfg['sv'] = 'Tyvärr är denna artikel enbart tillgänglig på %LANG:, : och %.';
	$cfg['it'] = 'Ci spiace, ma questo articolo è disponibile soltanto in %LANG:, : e %.';
	$cfg['ro'] = 'Din păcate acest articol este disponibil doar în %LANG:, : și %.';
	$cfg['hu'] = 'Sajnos ennek a bejegyzésnek csak %LANG:, : és % nyelvű változata van.';
	$cfg['ja'] = '申し訳ありません、このコンテンツはただ今　%LANG:、 :と %　のみです。';
	$cfg['es'] = 'Disculpa, pero esta entrada está disponible sólo en %LANG:, : y %.';
	$cfg['vi'] = 'Rất tiếc, mục này chỉ tồn tại ở %LANG:, : và %.';
	$cfg['ar'] = 'عفوا، هذه المدخلة موجودة فقط في %LANG:, : و %.';
	$cfg['pt'] = 'Desculpe, este conteúdo só está disponível em %LANG:, : e %.';
	$cfg['pb'] = 'Desculpe-nos, mas este texto está apenas disponível em %LANG:, : y %.';
	$cfg['pl'] = 'Przepraszamy, ten wpis jest dostępny tylko w języku %LANG:, : i %.';
	$cfg['gl'] = 'Sentímolo moito, ista entrada atopase unicamente en %LANG;,: e %.';
	$cfg['tr'] = 'Sorry, this entry is only available in %LANG:, : and %.';
	$cfg['et'] = 'Vabandame, see kanne on saadaval ainult %LANG : ja %.';
	$cfg['hr'] = 'Žao nam je, ne postoji prijevod na raspolaganju za ovaj proizvod još %LANG:, : i %.';
	$cfg['eu'] = 'Sentitzen dugu, baina sarrera hau %LANG-z:, : eta % bakarrik dago.';
	$cfg['el'] = 'Συγγνώμη,αυτή η εγγραφή είναι διαθέσιμη μόνο στα %LANG:, : και %.';
	$cfg['ua'] = 'Вибачте цей текст доступний тільки в %LANG:, : і %.';
	//$cfg['tw'] = '对不起，此内容只适用于%LANG:，:和%。';
	return $cfg;
}

/**
 * Date Configuration
 * @since 3.3
 */
function qtranxf_default_date_format()
{
	$cfg = array();
	$cfg['en'] = '%A %B %e%q, %Y';
	$cfg['de'] = '%A, \d\e\r %e. %B %Y';
	$cfg['zh'] = '%x %A';
	$cfg['ru'] = '%A %B %e%q, %Y';
	$cfg['fi'] = '%e.&m.%C';
	$cfg['fr'] = '%A %e %B %Y';
	$cfg['nl'] = '%d/%m/%y';
	$cfg['sv'] = '%Y-%m-%d';
	$cfg['it'] = '%e %B %Y';
	$cfg['ro'] = '%A, %e %B %Y';
	$cfg['hu'] = '%Y %B %e, %A';
	$cfg['ja'] = '%Y年%m月%d日';
	$cfg['es'] = '%d \d\e %B \d\e %Y';
	$cfg['vi'] = '%d/%m/%Y';
	$cfg['ar'] = '%d/%m/%Y';
	$cfg['pt'] = '%A, %e \d\e %B \d\e %Y';
	$cfg['pb'] = '%d \d\e %B \d\e %Y';
	$cfg['pl'] = '%d/%m/%y';
	$cfg['gl'] = '%d \d\e %B \d\e %Y';
	$cfg['tr'] = '%A %B %e%q, %Y';
	$cfg['et'] = '%A %B %e%q, %Y';
	$cfg['hr'] = '%d/%m/%Y';
	$cfg['eu'] = '%Y %B %e, %A';
	$cfg['el'] = '%d/%m/%y';
	$cfg['ua'] = '%A %B %e%q, %Y';
	//$cfg['tw'] = '%x %A';
	return $cfg;
}

/**
 * Time Configuration
 * @since 3.3
 */
function qtranxf_default_time_format()
{
	$cfg = array();
	$cfg['en'] = '%I:%M %p';
	$cfg['de'] = '%H:%M';
	$cfg['zh'] = '%I:%M%p';
	$cfg['ru'] = '%H:%M';
	$cfg['fi'] = '%H:%M';
	$cfg['fr'] = '%H:%M';
	$cfg['nl'] = '%H:%M';
	$cfg['sv'] = '%H:%M';
	$cfg['it'] = '%H:%M';
	$cfg['ro'] = '%H:%M';
	$cfg['hu'] = '%H:%M';
	$cfg['ja'] = '%H:%M';
	$cfg['es'] = '%H:%M hrs.';
	$cfg['vi'] = '%H:%M';
	$cfg['ar'] = '%H:%M';
	$cfg['pt'] = '%H:%M';
	$cfg['pb'] = '%H:%M hrs.';
	$cfg['pl'] = '%H:%M';
	$cfg['gl'] = '%H:%M hrs.';
	$cfg['tr'] = '%H:%M';
	$cfg['et'] = '%H:%M';
	$cfg['hr'] = '%H:%M';
	$cfg['eu'] = '%H:%M';
	$cfg['el'] = '%H:%M';
	$cfg['ua'] = '%H:%M';
	//$cfg['tw'] = '%I:%M%p';
	return $cfg;
}

/**
 * Flag images configuration
 * Look in /flags/ directory for a huge list of flags for usage
 * @since 3.3
 */
function qtranxf_default_flag()
{
	$cfg = array();
	$cfg['en'] = 'gb.png';
	$cfg['de'] = 'de.png';
	$cfg['zh'] = 'cn.png';
	$cfg['ru'] = 'ru.png';
	$cfg['fi'] = 'fi.png';
	$cfg['fr'] = 'fr.png';
	$cfg['nl'] = 'nl.png';
	$cfg['sv'] = 'se.png';
	$cfg['it'] = 'it.png';
	$cfg['ro'] = 'ro.png';
	$cfg['hu'] = 'hu.png';
	$cfg['ja'] = 'jp.png';
	$cfg['es'] = 'es.png';
	$cfg['vi'] = 'vn.png';
	$cfg['ar'] = 'arle.png';
	$cfg['pt'] = 'pt.png';
	$cfg['pb'] = 'br.png';
	$cfg['pl'] = 'pl.png';
	$cfg['gl'] = 'galego.png';
	$cfg['tr'] = 'tr.png';
	$cfg['et'] = 'ee.png';
	$cfg['hr'] = 'hr.png';
	$cfg['eu'] = 'eu_ES.png';
	$cfg['el'] = 'gr.png';
	$cfg['ua'] = 'ua.png';
	//$cfg['tw'] = 'tw.png';
	return $cfg;
}

/**
 * Full country names as locales for Windows systems
 * @since 3.3
 */
function qtranxf_default_windows_locale()
{
	$cfg = array();
	$cfg['aa'] = "Afar";
	$cfg['ab'] = "Abkhazian";
	$cfg['ae'] = "Avestan";
	$cfg['af'] = "Afrikaans";
	$cfg['am'] = "Amharic";
	$cfg['ar'] = "Arabic";
	$cfg['as'] = "Assamese";
	$cfg['ay'] = "Aymara";
	$cfg['az'] = "Azerbaijani";
	$cfg['ba'] = "Bashkir";
	$cfg['be'] = "Belarusian";
	$cfg['bg'] = "Bulgarian";
	$cfg['bh'] = "Bihari";
	$cfg['bi'] = "Bislama";
	$cfg['bn'] = "Bengali";
	$cfg['bo'] = "Tibetan";
	$cfg['br'] = "Breton";
	$cfg['bs'] = "Bosnian";
	$cfg['ca'] = "Catalan";
	$cfg['ce'] = "Chechen";
	$cfg['ch'] = "Chamorro";
	$cfg['co'] = "Corsican";
	$cfg['cs'] = "Czech";
	$cfg['cu'] = "Church Slavic";
	$cfg['cv'] = "Chuvash";
	$cfg['cy'] = "Welsh";
	$cfg['da'] = "Danish";
	$cfg['de'] = "German";
	$cfg['dz'] = "Dzongkha";
	$cfg['el'] = "Greek";
	$cfg['en'] = "English";
	$cfg['eo'] = "Esperanto";
	$cfg['es'] = "Spanish";
	$cfg['et'] = "Estonian";
	$cfg['eu'] = "Basque";
	$cfg['fa'] = "Persian";
	$cfg['fi'] = "Finnish";
	$cfg['fj'] = "Fijian";
	$cfg['fo'] = "Faeroese";
	$cfg['fr'] = "French";
	$cfg['fy'] = "Frisian";
	$cfg['ga'] = "Irish";
	$cfg['gd'] = "Gaelic (Scots)";
	$cfg['gl'] = "Gallegan";
	$cfg['gn'] = "Guarani";
	$cfg['gu'] = "Gujarati";
	$cfg['gv'] = "Manx";
	$cfg['ha'] = "Hausa";
	$cfg['he'] = "Hebrew";
	$cfg['hi'] = "Hindi";
	$cfg['ho'] = "Hiri Motu";
	$cfg['hr'] = "Croatian";
	$cfg['hu'] = "Hungarian";
	$cfg['hy'] = "Armenian";
	$cfg['hz'] = "Herero";
	$cfg['ia'] = "Interlingua";
	$cfg['id'] = "Indonesian";
	$cfg['ie'] = "Interlingue";
	$cfg['ik'] = "Inupiaq";
	$cfg['is'] = "Icelandic";
	$cfg['it'] = "Italian";
	$cfg['iu'] = "Inuktitut";
	$cfg['ja'] = "Japanese";
	$cfg['jw'] = "Javanese";
	$cfg['ka'] = "Georgian";
	$cfg['ki'] = "Kikuyu";
	$cfg['kj'] = "Kuanyama";
	$cfg['kk'] = "Kazakh";
	$cfg['kl'] = "Kalaallisut";
	$cfg['km'] = "Khmer";
	$cfg['kn'] = "Kannada";
	$cfg['ko'] = "Korean";
	$cfg['ks'] = "Kashmiri";
	$cfg['ku'] = "Kurdish";
	$cfg['kv'] = "Komi";
	$cfg['kw'] = "Cornish";
	$cfg['ky'] = "Kirghiz";
	$cfg['la'] = "Latin";
	$cfg['lb'] = "Letzeburgesch";
	$cfg['ln'] = "Lingala";
	$cfg['lo'] = "Lao";
	$cfg['lt'] = "Lithuanian";
	$cfg['lv'] = "Latvian";
	$cfg['mg'] = "Malagasy";
	$cfg['mh'] = "Marshall";
	$cfg['mi'] = "Maori";
	$cfg['mk'] = "Macedonian";
	$cfg['ml'] = "Malayalam";
	$cfg['mn'] = "Mongolian";
	$cfg['mo'] = "Moldavian";
	$cfg['mr'] = "Marathi";
	$cfg['ms'] = "Malay";
	$cfg['mt'] = "Maltese";
	$cfg['my'] = "Burmese";
	$cfg['na'] = "Nauru";
	$cfg['nb'] = "Norwegian Bokmal";
	$cfg['nd'] = "Ndebele, North";
	$cfg['ne'] = "Nepali";
	$cfg['ng'] = "Ndonga";
	$cfg['nl'] = "Dutch";
	$cfg['nn'] = "Norwegian Nynorsk";
	$cfg['no'] = "Norwegian";
	$cfg['nr'] = "Ndebele, South";
	$cfg['nv'] = "Navajo";
	$cfg['ny'] = "Chichewa; Nyanja";
	$cfg['oc'] = "Occitan (post 1500)";
	$cfg['om'] = "Oromo";
	$cfg['or'] = "Oriya";
	$cfg['os'] = "Ossetian; Ossetic";
	$cfg['pa'] = "Panjabi";
	$cfg['pi'] = "Pali";
	$cfg['pl'] = "Polish";
	$cfg['ps'] = "Pushto";
	$cfg['pt'] = "Portuguese";
	$cfg['pb'] = "Brazilian Portuguese";
	$cfg['qu'] = "Quechua";
	$cfg['rm'] = "Rhaeto-Romance";
	$cfg['rn'] = "Rundi";
	$cfg['ro'] = "Romanian";
	$cfg['ru'] = "Russian";
	$cfg['rw'] = "Kinyarwanda";
	$cfg['sa'] = "Sanskrit";
	$cfg['sc'] = "Sardinian";
	$cfg['sd'] = "Sindhi";
	$cfg['se'] = "Sami";
	$cfg['sg'] = "Sango";
	$cfg['si'] = "Sinhalese";
	$cfg['sk'] = "Slovak";
	$cfg['sl'] = "Slovenian";
	$cfg['sm'] = "Samoan";
	$cfg['sn'] = "Shona";
	$cfg['so'] = "Somali";
	$cfg['sq'] = "Albanian";
	$cfg['sr'] = "Serbian";
	$cfg['ss'] = "Swati";
	$cfg['st'] = "Sotho";
	$cfg['su'] = "Sundanese";
	$cfg['sv'] = "Swedish";
	$cfg['sw'] = "Swahili";
	$cfg['ta'] = "Tamil";
	$cfg['te'] = "Telugu";
	$cfg['tg'] = "Tajik";
	$cfg['th'] = "Thai";
	$cfg['ti'] = "Tigrinya";
	$cfg['tk'] = "Turkmen";
	$cfg['tl'] = "Tagalog";
	$cfg['tn'] = "Tswana";
	$cfg['to'] = "Tonga";
	$cfg['tr'] = "Turkish";
	$cfg['ts'] = "Tsonga";
	$cfg['tt'] = "Tatar";
	$cfg['tw'] = "Twi";
	$cfg['ug'] = "Uighur";
	$cfg['uk'] = "Ukrainian";
	$cfg['ur'] = "Urdu";
	$cfg['uz'] = "Uzbek";
	$cfg['vi'] = "Vietnamese";
	$cfg['vo'] = "Volapuk";
	$cfg['wo'] = "Wolof";
	$cfg['xh'] = "Xhosa";
	$cfg['yi'] = "Yiddish";
	$cfg['yo'] = "Yoruba";
	$cfg['za'] = "Zhuang";
	$cfg['zh'] = "Chinese";
	$cfg['zu'] = "Zulu";
	return $cfg;
}

function qtranxf_language_predefined($lang)
{
	$language_names = qtranxf_default_language_name();
	return isset($language_names[$lang]);
}

function qtranxf_language_configured($prop,$opn=null)
{
	global $qtranslate_options;
	$val = call_user_func('qtranxf_default_'.$prop);
	if(!$opn){
		if(isset($qtranslate_options['languages'][$prop])){
			$opn = $qtranslate_options['languages'][$prop];
		}else{
			$opn = 'qtranslate_'.$prop;
		}
	}
	$opt = get_option($opn,array());
	if($opt){
		$val = array_merge($val,$opt);
	}
	return $val;
}

/**
 * Fill merged array of stored and pre-defined language properties
 * @since 3.3
 */
function qtranxf_languages_configured(&$cfg)
{
	global $qtranslate_options;
	foreach($qtranslate_options['languages'] as $nm => $opn){
		$cfg[$nm] = qtranxf_language_configured($nm,$opn);
	}
	//$cfg['windows_locale'] = qtranxf_language_configured('windows_locale');
	return $cfg;
}

/**
 * Load enabled languages properties from  database
 * @since 3.3
 */
function qtranxf_load_languages_enabled()
{
	global $q_config, $qtranslate_options;
	foreach($qtranslate_options['languages'] as $nm => $opn){
		$f = 'qtranxf_default_'.$nm;
		$val = qtranxf_load_option_func($nm,$opn,$f);
		$def = null;
		foreach($q_config['enabled_languages'] as $lang){
			if(isset($q_config[$nm][$lang])) continue;
			if(is_null($def) && function_exists($f)) $def = call_user_func($f);
			$q_config[$nm][$lang] = isset($def[$lang]) ? $def[$lang] : '';
		}
	}
	//$locales = qtranxf_default_windows_locale();
	//foreach($q_config['enabled_languages'] as $lang){
	//	$q_config['windows_locale'][$lang] = $locales[$lang];
	//}
}
