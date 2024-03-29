<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'qtranslate_front_config', 'qwpseo_add_front_page_config' );
function qwpseo_add_front_page_config( $page_configs ) {
    $ids = qwpseo_get_meta_keys();
    foreach ( $ids as $id ) {
        add_filter( $id, 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 20 );
    }

    return $page_configs;
}

/* TODO check if this is missing, supposedly moved to i18n-config.json but not used!
function qwpseo_add_filters_front() {
	$use_filters = array(
		//'wpseo_opengraph_title' => 20,//comes already translated
		//'wpseo_metakey' => 20, //deprecated
		//'wpseo_metakeywords' => 20,//comes already translated
		'wpseo_title' => 20,
		'wpseo_meta' => 20,
		'wpseo_metadesc' => 20,
		'wpseo_replacements' => 20
	);

	foreach ( $use_filters as $name => $priority ) {
		add_filter( $name, 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', $priority );
	}
}
qwpseo_add_filters_front();
*/

/**
 * Remove duplicated images and translates image attributes.
 * @since 1.0.3
 */
function qwpseo_sitemap_urlimages( $images, $id ) {
    global $q_config;
    $lang = $q_config['language'];
    $srcs = array();
    foreach ( $images as $k => $image ) {
        $src = $image['src'];
        if ( isset( $srcs[ $src ] ) ) {
            unset( $images[ $k ] );
        } else {
            $srcs[ $src ] = $image;
            foreach ( $image as $p => $txt ) {
                if ( $p == 'src' ) {
                    continue;
                }
                $images[ $k ][ $p ] = qtranxf_use( $lang, $txt, false, true );
            }
        }
    }

    return $images;
}

add_filter( 'wpseo_sitemap_urlimages', 'qwpseo_sitemap_urlimages', 999, 2 );

/**
 * Adds other language sitemaps to the sitemap_index.xml
 * @since 1.0.3
 */
function qwpseo_sitemap_index( $sm ) {
    global $q_config, $wpseo_sitemaps;
    if ( isset( $q_config['sitemap-type'] ) ) {
        return '';
    }
    ob_start();
    $wpseo_sitemaps->output();
    $content = ob_get_contents();
    ob_end_clean();
    $p        = 0;
    $sitemaps = array();
    while ( ( $p = strpos( $content, '<sitemap>', $p ) ) !== false ) {
        if ( ( $e = strpos( $content, '</sitemap>', $p ) ) !== false ) {
            $len        = $e - $p + strlen( '</sitemap>' );
            $s          = substr( $content, $p, $len );
            $p          += $len;
            $sitemaps[] = $s;
        } else {
            $p += strlen( '<sitemap>' );
        }
    }
    $sm = '';
    foreach ( $q_config['enabled_languages'] as $lang ) {
        if ( $lang == $q_config['language'] ) {
            continue;
        }
        foreach ( $sitemaps as $s ) {
            if ( preg_match( '!<loc>([^<]+)</loc>!', $s, $matches ) ) {
                $loc = $matches[1];
                $sm  .= preg_replace( '!<loc>([^<]+)</loc>!', '<loc>' . qtranxf_convertURL( $loc, $lang ) . '</loc>', $s );
            }
        }
    }

    return $sm;
}

add_filter( 'wpseo_sitemap_index', 'qwpseo_sitemap_index' );

/**
 * Translates $p->post_content to make image lookup work correctly later.
 */
function qwpseo_enable_xml_sitemap_post_url( $loc, $p ) {
    global $q_config;
    $lang            = $q_config['language'];
    $p->post_content = qtranxf_use_language( $lang, $p->post_content, false, true );

    return $loc;
}

add_filter( 'wpseo_xml_sitemap_post_url', 'qwpseo_enable_xml_sitemap_post_url', 5, 2 );

/**
 * Has to be disabled for now, unless we ask Yoast to add a filter to alter cache key name depending on active language.
 * @since 1.0.3
 */
function qwpseo_enable_xml_sitemap_transient_caching( $caching ) {
    return false;
}

add_filter( 'wpseo_enable_xml_sitemap_transient_caching', 'qwpseo_enable_xml_sitemap_transient_caching' );

/**
 *
 * @since 1.0.3
 */
function qwpseo_build_sitemap_post_type( $type ) {
    if ( $type == 'i18n-index' ) {
        global $q_config;
        //root map for single language
        $q_config['sitemap-type'] = $type;

        return '1';
    }

    return $type;
}

add_filter( 'wpseo_build_sitemap_post_type', 'qwpseo_build_sitemap_post_type', 5 );

/**
 * Change XLS stylesheet URL.
 * @since 1.1
 */
function qwpseo_stylesheet_url( $stylesheet ) {
    if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], 'sitemap_index.xml' ) !== false ) {
        $prefix = 'index-';
    } elseif ( isset( $_SERVER['HTTP_REFERER'] ) && strpos( $_SERVER['HTTP_REFERER'], 'i18n-index-sitemap' ) !== false ) {
        $prefix = 'qtx-';
    } else {
        $prefix = 'qwp-';
    }
    $stylesheet = str_replace( 'main-', $prefix, $stylesheet );

    return $stylesheet;
}

// All these XSL callbacks are (dirty) hacks of the XSL content, assuming it contains:
// "Generated by Yoast SEO, this is an XML Sitemap, meant for consumption by search engines."

add_filter( 'wpseo_stylesheet_url', 'qwpseo_stylesheet_url' );

function qwpseo_xsl_heading( $buffer ) {
    $buffer = str_replace( ', this is an XML Sitemap', ' and <a href="https://github.com/qtranslate/qtranslate-xt">qTranslate&#8209;XT</a>, this is an XML Sitemap', $buffer );
    // fix whitespace between "Yoast SEO"
    $buffer = str_replace( '</a> <a ', '</a><xsl:text> </xsl:text><a ', $buffer );

    return $buffer;
}

function qwpseo_xsl_language( $buffer ) {
    global $q_config;
    $lang   = $q_config['language'];
    $buffer = str_replace( ', this is an XML Sitemap', ', this is an XML Sitemap of "' . $q_config['language_name'][ $lang ] . '" content', $buffer );

    return $buffer;
}

function qwpseo_xsl_callback_idx( $buffer ) {
    $buffer = str_replace( ', this is an XML Sitemap', ', this is an XML Sitemap of multilingual content', $buffer );

    return qwpseo_xsl_heading( $buffer );
}

function qwpseo_xsl_callback_qwp( $buffer ) {
    $buffer = qwpseo_xsl_language( $buffer );

    return qwpseo_xsl_heading( $buffer );
}

function qwpseo_xsl_callback_qtx( $buffer ) {
    $buffer = str_replace( 'sitemap_index.xml', 'i18n-index-sitemap.xml', $buffer );
    $buffer = qwpseo_xsl_language( $buffer );

    return qwpseo_xsl_heading( $buffer );
}

/**
 * Output 'qwpseo-sitemap.xsl' based on the output of function 'xsl_output' in /wp-content/plugins/wordpress-seo/inc/class-sitemaps.php.
 * @since 1.1
 */
function qwpseo_xsl_i18n( $callback ) {
    global $wpseo_sitemaps;
    ob_start( $callback );
    $wpseo_sitemaps->xsl_output( 'main' );
    ob_end_flush();
}

function qwpseo_xsl_idx( $callback ) {
    qwpseo_xsl_i18n( 'qwpseo_xsl_callback_idx' );
}

add_action( 'wpseo_xsl_index', 'qwpseo_xsl_idx', 20 );

function qwpseo_xsl_qwp( $callback ) {
    qwpseo_xsl_i18n( 'qwpseo_xsl_callback_qwp' );
}

add_action( 'wpseo_xsl_qwp', 'qwpseo_xsl_qwp', 20 );

function qwpseo_xsl_qtx( $callback ) {
    qwpseo_xsl_i18n( 'qwpseo_xsl_callback_qtx' );
}

add_action( 'wpseo_xsl_qtx', 'qwpseo_xsl_qtx', 20 );

/* TODO clean this, keep or remove?
function qwpseo_register_xsl_i18n()
{
	global $wpseo_sitemaps;
	$wpseo_sitemaps->register_xsl('i18n','qwpseo_xsl_i18n',true);
}
//add_action('init', 'qwpseo_register_xsl_i18n', 30);

$wpseo_sitemaps->set_stylesheet('');

function qwpseo_sitemap_entry( $url, $post_type, $p )
{
	global $q_config;

	$urls = array();
	foreach($q_config['enabled_languages'] as $lang){
		$urls[$lang] = $url;
		$urls[$lang]['loc'] = qtranxf_convertURL($url['loc'],$lang);
		if(isset($url['images'])){
			foreach($url['images'] as $k => $img){
				foreach($img as $p => $txt){
					if($p == 'src') continue;
					$urls[$lang]['images'][$k][$p] = qtranxf_use($lang,$txt,false,true);
				}
			}
		}
	}
	$url['urls'] = $urls;
	return $url;
}
//add_filter( 'wpseo_sitemap_entry', 'qwpseo_sitemap_entry', 999, 3 );

function qwpseo_test_filter( $arg )
{
	return $arg;
}
add_filter( 'wpseo_opengraph_title', 'qwpseo_test_filter');
add_filter( 'wpseo_metakeywords', 'qwpseo_test_filter');
add_filter( 'wpseo_title', 'qwpseo_test_filter');
add_filter( 'wpseo_meta', 'qwpseo_test_filter');
add_filter( 'wpseo_metadesc', 'qwpseo_test_filter');
add_filter( 'wpseo_replacements', 'qwpseo_test_filter');
*/
