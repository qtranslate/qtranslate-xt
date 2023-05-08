<?php

add_filter( 'qtranslate_language_detect_redirect', 'qtranxf_slugs_language_detect_redirect', 600, 3 );

/**
* Allows url redirection due to language auto detection only if site url has been requested
*
* @see qtranxf_check_url_maybe_redirect
* @param string $url_lang proposed target URL for the active language to redirect to.
* @param string $url_orig original URL supplied to browser, which needs to be standardized.
* @param array $url_info a hash of various information parsed from original URL, cookies and other site configuration. The key names should be self-explanatory.
*
* @return string resulting redirection url
*/
function qtranxf_slugs_language_detect_redirect($url_lang, $url_orig, $url_info): string {
   global $q_config;
   if (site_url().'/' === $url_orig)
       return $url_lang;
   else {
       if ( empty( $url_info['lang_url'] ) ){
           return qtranxf_convertURL( $url_orig, $q_config['default_language'],false,true );
       } else {
           return $url_orig;
       }
   }
}

