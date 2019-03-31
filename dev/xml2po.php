<?
/*
	This file can be used to convert xml files from http://unicode.org/Public/cldr/latest to the .po format.
*/
header( 'Content-type: text/plain; charset=utf-8' );
$dir    = 'core/common/main';
$po_dir = 'language-translations';

# Get all needed files
$files = glob( $dir . '/*.xml' );
foreach ( $files as $file ) {
	preg_match( "/(?:^|[\/])(([^_\/]+)[_]?([^_\/]*?)[_]?([^_\/]*))[.]xml$/", $file, $matches );
	$languages[ $matches[1] ] = array(
		'file' => $file,
		'lang' => $matches[2]
	);
}
unset( $files );


# Parse all translation files from unicode consortium
$langs = array();
foreach ( $languages as $lang_code => $language ) {
	$xml   = file_get_contents( $language['file'] );
	$infos = array();
	preg_replace_callback( "/[<](identity|languages)[>](.+)[<][\/]\\1[>]/siu", function ( $matches ) use ( &$infos ) {
		$infos[ $matches[1] ] = $matches[2];

		return '';
	}, $xml );

	preg_replace_callback( "/[<](.+)\s+type[=][\"](([^\"]+)(?:[_]([^\"]+))?)[\"][^\<>]*[>]([^<>]+)/iu", function ( $matches ) use ( &$languages, &$lang_code ) {
		$languages[ $lang_code ][ $matches[1] ] = $matches[2];

		return '';
	}, $infos['identity'] );

	preg_replace_callback( "/[<]language\s+type[=][\"](([^\"]+)(?:[_]([^\"]+))?)[\"](?:\s+alt[=][\"]([^\"]+)[\"])?[^\<>]*[>]([^<>]+)/iu", function ( $matches ) use ( &$languages, &$lang_code ) {
		if ( ! isset( $languages[ $lang_code ]['translations'][ $matches[1] ] ) ) {
			$languages[ $lang_code ]['translations'][ $matches[1] ] = $matches[5];
		} else {
			$languages[ $lang_code ]['translation_alernatives'][ $matches[1] ][] = array( 'type'        => $matches[4],
			                                                                              'translation' => $matches[5]
			);
		}

		return '';
	}, $infos['languages'] );
}

# WP locales according to https://make.wordpress.org/polyglots/teams/   + en_US + en
# 'Locale','WP Locale','Version','GlotPress',''
$wp_langs = array(
	array( 'English', 'en', 'None', '100 ', 'en' ),
	array( 'English (USA)', 'en_US', 'None', '100 ', 'en' ),
	array( 'Afrikaans', 'af', 'None', '33 ', 'af' ),
	array( 'Akan', 'ak', 'No site', '', 'ak' ),
	array( 'Albanian', 'sq', '4.3.1', '100', 'sq' ),
	array( 'Algerian Arabic', 'arq', 'None', '13', 'arq' ),
	array( 'Amharic', 'am', 'None', '8', 'am' ),
	array( 'Arabic', 'ar', '4.3.1', '100', 'ar' ),
	array( 'Armenian', 'hy', '4.3.1', '100', 'hy' ),
	array( 'Aromanian', 'rup_MK', 'No site', '', 'rup' ),
	array( 'Arpitan', 'frp', 'None', '1', 'frp' ),
	array( 'Assamese', 'as', 'None', '38', 'as' ),
	array( 'Azerbaijani', 'az', '4.3.1', '100', 'az' ),
	array( 'Azerbaijani (Turkey)', 'az_TR', 'None', '0', 'az-tr' ),
	array( 'Balochi Southern', 'bcc', 'None', '2', 'bcc' ),
	array( 'Bashkir', 'ba', 'No site', '', 'ba' ),
	array( 'Basque', 'eu', '4.3.1', '100', 'eu' ),
	array( 'Belarusian', 'bel', 'None', '52', 'bel' ),
	array( 'Bengali', 'bn_BD', '4.3.1', '100', 'bn' ),
	array( 'Bosnian', 'bs_BA', '4.3.1', '100', 'bs' ),
	array( 'Breton', 'bre', 'None', '0', 'br' ),
	array( 'Bulgarian', 'bg_BG', '4.3.1', '100', 'bg' ),
	array( 'Catalan', 'ca', '4.3', '100', 'ca' ),
	array( 'Catalan (Balear)', 'bal', 'No site', '0', 'bal' ),
	array( 'Chinese (China)', 'zh_CN', '4.3.1', '100', 'zh-cn' ),
	array( 'Chinese (Hong Kong)', 'zh_HK', 'None', '', 'zh-hk' ),
	array( 'Chinese (Taiwan)', 'zh_TW', '4.3.1', '100', 'zh-tw' ),
	array( 'Corsican', 'co', 'None', '6', 'co' ),
	array( 'Croatian', 'hr', '4.3.1', '100', 'hr' ),
	array( 'Czech', 'cs_CZ', '4.2.4', '87', 'cs' ),
	array( 'Danish', 'da_DK', '4.3.1', '100', 'da' ),
	array( 'Dhivehi', 'dv', 'None', '4', 'dv' ),
	array( 'Dutch', 'nl_NL', '4.3.1', '100', 'nl' ),
	array( 'Dutch (Belgium)', 'nl_BE', 'None', '', 'nl-be' ),
	array( 'Dzongkha', 'dzo', 'None', '0', 'dzo' ),
	array( 'English (Australia)', 'en_AU', '4.3.1', '100', 'en-au' ),
	array( 'English (Canada)', 'en_CA', '4.3.1', '100', 'en-ca' ),
	array( 'English (New Zealand)', 'en_NZ', '4.3.1', '100', 'en-nz' ),
	array( 'English (South Africa)', 'en_ZA', 'None', '0', 'en-za' ),
	array( 'English (UK)', 'en_GB', '4.3.1', '100', 'en-gb' ),
	array( 'Esperanto', 'eo', '4.3.1', '100', 'eo' ),
	array( 'Estonian', 'et', '4.3.1', '100', 'et' ),
	array( 'Faroese', 'fo', 'No site', '34', 'fo' ),
	array( 'Finnish', 'fi', '4.3.1', '100', 'fi' ),
	array( 'French (Belgium)', 'fr_BE', '4.3.1', '100', 'fr-be' ),
	array( 'French (Canada)', 'fr_CA', 'None', '94', 'fr-ca' ),
	array( 'French (France)', 'fr_FR', '4.3.1', '100', 'fr' ),
	array( 'Frisian', 'fy', '3.2.1', '53', 'fy' ),
	array( 'Friulian', 'fur', 'None', '0', 'fur' ),
	array( 'Fulah', 'fuc', 'None', '1', 'fuc' ),
	array( 'Galician', 'gl_ES', '4.3.1', '100', 'gl' ),
	array( 'Georgian', 'ka_GE', '3.3', '93', 'ka' ),
	array( 'German', 'de_DE', '4.3.1', '100', 'de' ),
	array( 'German (Switzerland)', 'de_CH', '4.3.1', '100', 'de-ch' ),
	array( 'Greek', 'el', '4.3.1', '100', 'el' ),
	array( 'Guaraní', 'gn', 'No site', '', 'gn' ),
	array( 'Gujarati', 'gu', 'None', '9', 'gu' ),
	array( 'Hawaiian', 'haw_US', 'No site', '0', 'haw' ),
	array( 'Hazaragi', 'haz', '4.1.2', '93', 'haz' ),
	array( 'Hebrew', 'he_IL', '4.3.1', '100', 'he' ),
	array( 'Hindi', 'hi_IN', 'None', '98', 'hi' ),
	array( 'Hungarian', 'hu_HU', '4.3.1', '100', 'hu' ),
	array( 'Icelandic', 'is_IS', '4.3.1', '100', 'is' ),
	array( 'Ido', 'ido', 'None', '0', 'ido' ),
	array( 'Indonesian', 'id_ID', '4.3.1', '100', 'id' ),
	array( 'Irish', 'ga', 'None', '63', 'ga' ),
	array( 'Italian', 'it_IT', '4.3.1', '100', 'it' ),
	array( 'Japanese', 'ja', '4.3.1', '100', 'ja' ),
	array( 'Javanese', 'jv_ID', '3.0.1', '46', 'jv' ),
	array( 'Kabyle', 'kab', 'None', '81', 'kab' ),
	array( 'Kannada', 'kn', '3.6', '27', 'kn' ),
	array( 'Kazakh', 'kk', 'None', '44', 'kk' ),
	array( 'Khmer', 'km', 'None', '80', 'km' ),
	array( 'Kinyarwanda', 'kin', 'No site', '0', 'kin' ),
	array( 'Kirghiz', 'ky_KY', '4.2', '30', 'ky' ),
	array( 'Korean', 'ko_KR', '4.3.1', '100', 'ko' ),
	array( 'Kurdish (Sorani)', 'ckb', '4.3', '78', 'ckb' ),
	array( 'Lao', 'lo', 'None', '65', 'lo' ),
	array( 'Latvian', 'lv', '4.3.1', '65', 'lv' ),
	array( 'Limburgish', 'li', 'None', '0', 'li' ),
	array( 'Lingala', 'lin', 'None', '', 'lin' ),
	array( 'Lithuanian', 'lt_LT', '4.3.1', '100', 'lt' ),
	array( 'Luxembourgish', 'lb_LU', 'None', '9', 'lb' ),
	array( 'Macedonian', 'mk_MK', '4.1.1', '93', 'mk' ),
	array( 'Malagasy', 'mg_MG', 'None', '14', 'mg' ),
	array( 'Malay', 'ms_MY', '2.9.2', '77', 'ms' ),
	array( 'Malayalam', 'ml_IN', 'None', '1', 'ml' ),
	array( 'Maori', 'mri', 'None', '0', 'mri' ),
	array( 'Marathi', 'mr', 'None', '7', 'mr' ),
	array( 'Mingrelian', 'xmf', 'No site', '', 'xmf' ),
	array( 'Mongolian', 'mn', 'None', '60', 'mn' ),
	array( 'Montenegrin', 'me_ME', 'None', '70', 'me' ),
	array( 'Moroccan Arabic', 'ary', '4.3.1', '100', 'ary' ),
	array( 'Myanmar (Burmese)', 'my_MM', '4.1', '91', 'mya' ),
	array( 'Nepali', 'ne_NP', 'None', '48', 'ne' ),
	array( 'Norwegian (Bokmål)', 'nb_NO', '4.3.1', '100', 'nb' ),
	array( 'Norwegian (Nynorsk)', 'nn_NO', '4.3.1', '100', 'nn' ),
	array( 'Occitan', 'oci', '4.2.4', '99', 'oci' ),
	array( 'Oriya', 'ory', 'None', '0', 'ory' ),
	array( 'Ossetic', 'os', '3.4.2', '16', 'os' ),
	array( 'Pashto', 'ps', '4.1.2', '96', 'ps' ),
	array( 'Persian', 'fa_IR', '4.3', '100', 'fa' ),
	array( 'Persian (Afghanistan)', 'fa_AF', 'None', '23', 'fa-af' ),
	array( 'Polish', 'pl_PL', '4.3.1', '100', 'pl' ),
	array( 'Portuguese (Brazil)', 'pt_BR', '4.3.1', '100', 'pt-br' ),
	array( 'Portuguese (Portugal)', 'pt_PT', '4.3.1', '100', 'pt' ),
	array( 'Punjabi', 'pa_IN', 'None', '2', 'pa' ),
	array( 'Rohingya', 'rhg', 'None', '56', 'rhg' ),
	array( 'Romanian', 'ro_RO', '4.3.1', '100', 'ro' ),
	array( 'Romansh Vallader', 'roh', 'None', '0', 'roh' ),
	array( 'Russian', 'ru_RU', '4.3.1', '100', 'ru' ),
	array( 'Russian (Ukraine)', 'ru_UA', 'No site', '', 'ru-ua' ),
	array( 'Rusyn', 'rue', 'No site', '', 'rue' ),
	array( 'Sakha', 'sah', 'None', '14', 'sah' ),
	array( 'Sanskrit', 'sa_IN', 'None', '2', 'sa-in' ),
	array( 'Sardinian', 'srd', 'None', '0', 'srd' ),
	array( 'Scottish Gaelic', 'gd', '4.3.1', '100', 'gd' ),
	array( 'Serbian', 'sr_RS', '4.3.1', '100', 'sr' ),
	array( 'Silesian', 'szl', 'None', '28', 'szl' ),
	array( 'Sindhi', 'sd_PK', 'No site', '', 'sd' ),
	array( 'Sinhala', 'si_LK', '2.8.5', '85', 'si' ),
	array( 'Slovak', 'sk_SK', '4.3.1', '100', 'sk' ),
	array( 'Slovenian', 'sl_SI', '4.3.1', '100', 'sl' ),
	array( 'Somali', 'so_SO', 'None', '32', 'so' ),
	array( 'South Azerbaijani', 'azb', 'None', '42', 'azb' ),
	array( 'Spanish (Argentina)', 'es_AR', 'None', '31', 'es-ar' ),
	array( 'Spanish (Chile)', 'es_CL', '4.0', '89', 'es-cl' ),
	array( 'Spanish (Colombia)', 'es_CO', 'None', '0', 'es-co' ),
	array( 'Spanish (Mexico)', 'es_MX', '4.3', '99', 'es-mx' ),
	array( 'Spanish (Peru)', 'es_PE', '4.3.1', '100', 'es-pe' ),
	array( 'Spanish (Puerto Rico)', 'es_PR', 'No site', '', 'es-pr' ),
	array( 'Spanish (Spain)', 'es_ES', '4.3.1', '100', 'es' ),
	array( 'Spanish (Venezuela)', 'es_VE', '4.0', '71', 'es-ve' ),
	array( 'Sundanese', 'su_ID', '3.1.3', '42', 'su' ),
	array( 'Swahili', 'sw', '3.0.5', '43', 'sw' ),
	array( 'Swedish', 'sv_SE', '4.3.1', '100', 'sv' ),
	array( 'Swiss German', 'gsw', '3.7', '74', 'gsw' ),
	array( 'Tagalog', 'tl', '4.3.1', '100', 'tl' ),
	array( 'Tajik', 'tg', 'None', '6', 'tg' ),
	array( 'Tamazight (Central Atlas)', 'tzm', 'None', '4', 'tzm' ),
	array( 'Tamil', 'ta_IN', 'None', '35', 'ta' ),
	array( 'Tamil (Sri Lanka)', 'ta_LK', '3.9', '79', 'ta-lk' ),
	array( 'Tatar', 'tt_RU', 'None', '2', 'tt' ),
	array( 'Telugu', 'te', '4.3', '43', 'te' ),
	array( 'Thai', 'th', '4.3', '100', 'th' ),
	array( 'Tibetan', 'bo', 'None', '0', 'bo' ),
	array( 'Tigrinya', 'tir', 'No site', '', 'tir' ),
	array( 'Turkish', 'tr_TR', '4.3.1', '100', 'tr' ),
	array( 'Turkmen', 'tuk', 'None', '0', 'tuk' ),
	array( 'Uighur', 'ug_CN', '4.1.2', '91', 'ug' ),
	array( 'Ukrainian', 'uk', '4.3', '100', 'uk' ),
	array( 'Urdu', 'ur', '3.6.2', '52', 'ur' ),
	array( 'Uzbek', 'uz_UZ', '4.1.1', '70', 'uz' ),
	array( 'Vietnamese', 'vi', '4.2.1', '93', 'vi' ),
	array( 'Walloon', 'wa', 'No site', '', 'wa' ),
	array( 'Welsh', 'cy', '4.3', '100', 'cy' ),
	array( 'Yoruba', 'yor', 'None', '0', 'yor' )
);


# Get all possible translation sources in the correct order
foreach ( $wp_langs as $wp_lang ) {
	$langs2translate[ $wp_lang[1] ]['sources'][] = $wp_lang[1];
	$langs2translate[ $wp_lang[1] ]['sources'][] = $wp_lang[4];

	if ( strpos( $wp_lang[1], '_' ) ) {
		$lang = strstr( $wp_lang[1], '_', true );
		if ( $languages[ $wp_lang[1] ]['script'] ) {
			$langs2translate[ $wp_lang[1] ]['sources'][] = $languages[ $lang ]['language'] . '_' . $languages[ $lang ]['script'];
		}
		$langs2translate[ $wp_lang[1] ]['sources'][] = $languages[ $lang ]['language'];
	}
	$langs2translate[ $wp_lang[1] ]['sources'] = array_unique( $langs2translate[ $wp_lang[1] ]['sources'] );
}
$translations = array();
foreach ( $langs2translate as $lang => $lang2translate ) {
	$translations[ $lang ] = array();
	#echo "source: $lang ->";print_r($lang2translate);
	foreach ( $lang2translate['sources'] as $source ) {
		foreach ( $wp_langs as $wp_lang ) {
			if ( ! isset( $translations[ $lang ][ $wp_lang[1] ] ) and isset( $languages[ $source ] ) and isset( $languages[ $source ]['translations'] ) ) {
				$translations[ $lang ][ $wp_lang[1] ] = $languages[ $source ]['translations'][ $wp_lang[1] ] ? $languages[ $source ]['translations'][ $wp_lang[1] ] : $languages[ $source ]['translations'][ $wp_lang[4] ];

				# 
			}
		}
	}
}

if ( ! file_exists( $po_dir ) ) {
	mkdir( $po_dir );
}

# Compile .po files
foreach ( $translations as $lang => $words ) {
	$po_content = 'msgid ""' . "\n";
	$po_content .= 'msgstr ""' . "\n";
	$po_content .= '"Content-Type: text/plain; charset=UTF-8\n"' . "\n";
	$po_content .= '"Language: ' . $lang . '\n"' . "\n\n";
	foreach ( $words as $key => $translation ) {
		$po_content .= 'msgid "' . $key . '"' . "\n";
		$po_content .= 'msgstr "' . $translation . '"' . "\n";
		$po_content .= "\n";
	}
	file_put_contents( $po_dir . '/language-' . $lang . '.po', $po_content );
}
