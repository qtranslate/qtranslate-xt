<?php

add_filter( 'qtranslate_language_detect_redirect', 'qtranxf_slugs_language_detect_redirect', 600, 3 );

/**
* Disables all redirections except when site url is requested, if default language is hidden. In this case url is redirected to default language site url.
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

   if ( ( site_url().'/' === $url_orig && $q_config['hide_default_language'] ) || empty( $url_info['lang_url'] ) ){

      $url_res = qtranxf_convertURL( $url_orig, $q_config['default_language'],false,true );

      if ( qtranxf_detect_language_front( $url_info ) != $q_config['default_language'] ) {

          if ( $url_info['cookie_front_or_admin_found'] )
            qtranxf_set_language_cookie( $q_config['default_language'] );

          if ( $url_res == $url_orig )
            $q_config['url_info'] = qtranxf_url_set_language( $urlinfo, $q_config['default_language'], $showLanguage );

      }

   } else
      $url_res = $url_orig;

   return $url_res;
}
