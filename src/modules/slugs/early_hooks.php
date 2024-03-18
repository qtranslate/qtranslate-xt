<?php

add_filter( 'qtranslate_language_detect_redirect', 'qtranxf_slugs_language_detect_redirect', 600, 3 );

/**
* Disables default redirection when language is not detectable from url (e.g. default language url with hide_default_language activated).
* Exception is made for site_url(), for which language detection feature is preserved.
* 
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
   /* Make sure urls with no lang info are treated as default language, unless it's site_url */
   if ( untrailingslashit( site_url() ) != untrailingslashit( $url_orig ) && empty( $url_info['lang_url'] ) ){
      return qtranxf_convertURL( $url_orig, $q_config['default_language'], false, true );
   /* The following fixes the case when the browser removes language marker from default language site url (caching?) passing directly _$SERVER['REQUEST_URI'] with language info removed.
    * In this case is not possible to switch to default language from site url.
    * Application of this hack is narrowed down to the cases where referer is site url in current language, to preserve language detection feature as much as possible.
    * @TODO: check if a cleaner fix is applicable. */
   } else if ( $q_config['hide_default_language'] && untrailingslashit( site_url() ) == untrailingslashit( $url_orig ) && untrailingslashit($url_lang) === untrailingslashit( wp_get_raw_referer() ) ) {
      return qtranxf_convertURL( $url_orig, $q_config['default_language'], false, true );
   }
   /* All other cases follow default behaviour */
      return $url_lang;
}
