<?php

// provide qTranslate compatibility when using qTranslate-X

if (function_exists('qtrans_getLanguage') === false):
	function qtrans_getLanguage() {
		return qtranxf_getLanguage();
	}
endif;

if (function_exists('qtrans_getSortedLanguages') === false):
	function qtrans_getSortedLanguages($reverse = false) {
		return qtranxf_getSortedLanguages($reverse);
	}
endif;

if (function_exists('qtrans_join') === false):
	function qtrans_join($texts) {
		// qtranxf_join_c doesn't handle non-array values to
		// maintain compatibility with qtrans_join we must handle it here
		if (is_array($texts) === false) {
			$texts = qtranxf_split($texts, false);
		}
		return qtranxf_join_c($texts);
	}
endif;

if (function_exists('qtrans_split') === false):
	function qtrans_split($text, $quicktags = true) {
		return qtranxf_split($text, $quicktags);
	}
endif;

if (function_exists('qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage') === false):
	function qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage($content) {
		return qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($content);
	}
endif;
