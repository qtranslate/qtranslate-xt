<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'i18n_admin_config', 'qwpseo_add_admin_page_config' );
function qwpseo_add_admin_page_config( $page_configs ) {
    if ( ! isset( $page_configs['yoast_wpseo'] ) ) {
        $page_configs['yoast_wpseo'] = array();
    }
    $page_config = &$page_configs['yoast_wpseo'];
    if ( ! isset( $page_config['pages'] ) ) {
        $page_config['pages'] = array(
            'post.php'      => '',
            'post-new.php'  => '',
            'term.php'      => '',
            'edit-tags.php' => 'action=edit'
        );
    }

    global $pagenow;
    if ( $pagenow == 'term.php' ) {
        $page_config['anchors'] = array(
            'edittag'   => array( "where" => "before" ),
            'poststuff' => array( 'where' => 'before' )
        );
    }

    $page_config['js-exec'] = array();
    $js                     = &$page_config['js-exec']; // shorthand

    $dir = qtranxf_dir_from_wp_content( __FILE__ );

    switch ( $pagenow ) {
        case 'term.php':
            $js[]   = array(
                'handle' => 'qwpseo-prep',
                'src'    => $dir . '/js/qwpseo-prep.min.js',
                'ver'    => QWPSEO_VERSION,
                'deps'   => array( 'qtranslate-admin-common' )
            );
            $deps[] = 'yoast-seo-term-scraper';
            break;
        case 'post-new.php':
        case 'post.php':
            $deps[] = 'yoast-seo-post-scraper';
            break;
        default:
            $deps = array();
            break;
    }

    if ( ! empty( $deps ) ) {
        $js[] = array(
            'handle' => 'qwpseo-exec',
            'src'    => $dir . '/js/qwpseo-exec.min.js',
            'ver'    => QWPSEO_VERSION,
            'deps'   => $deps
        );
    }

    if ( empty( $page_config['forms'] ) ) {
        $page_config['forms'] = array();
    }
    if ( empty( $page_config['forms']['document'] ) ) {
        $page_config['forms']['document'] = array();
    }
    if ( empty( $page_config['forms']['document']['fields'] ) ) {
        $page_config['forms']['document']['fields'] = array();
    }
    $fields = &$page_config['forms']['document']['fields']; // shorthand

    $code = array( 'encode' => '{' );
    $ids  = qwpseo_get_meta_keys();
    foreach ( $ids as $id ) {
        //they keeps changing the HTML ids, so, set all values used so far
        $fields[ $id ]             = $code;
        $fields[ 'yoast_' . $id ]  = $code;
        $fields[ 'hidden_' . $id ] = $code;

        add_filter( 'wpseo_sanitize_tax_meta_' . $id, 'qwpseo_sanitize_tax_meta', 5, 3 );
    }

    return $page_configs;
}

function qwpseo_admin_filters() {
    global $pagenow, $q_config;
    switch ( $pagenow ) {
        case 'edit.php':
            $ids = qwpseo_get_meta_keys();
            foreach ( $ids as $id ) {
                add_filter( $id, 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage' );
                //add_filter( 'wpseo_title', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage');
                //add_filter( 'wpseo_meta', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage');
                //add_filter( 'wpseo_metadesc', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage');
            }
            //todo add_filter( "get_post_metadata", 'qwpseo_get_metadata_post', 5, 4);
            break;
        case 'post.php':
        case 'post-new.php':
            if ( $q_config['editor_mode'] == QTX_EDITOR_MODE_SINGLE ) {
                add_filter( 'get_post_metadata', 'qwpseo_get_post_metadata', 5, 4 );
                //add_filter( 'option_blogname', 'qtranxf_useCurrentLanguageIfNotFoundShowEmpty');
            }

            //to prevent the effect of 'strip_tags' in function 'retrieve_sitename' in '/wp-content/plugins/wordpress-seo/inc/class-wpseo-replace-vars.php'
            add_filter( 'option_blogname', 'qwpseo_encode_swirly' );
            add_filter( 'option_blogdescription', 'qwpseo_encode_swirly' );
            if ( defined( 'WPSEO_VERSION' ) && intval( substr( WPSEO_VERSION, 0, 1 ) ) < 3 ) {
                //to make "Page Analysis" work in Single Language Mode
                add_filter( 'wpseo_pre_analysis_post_content', 'qtranxf_useCurrentLanguageIfNotFoundShowEmpty' );
            }
            break;
    }

    add_action( 'admin_init', 'qwpseo_script_deps', 99 );

    if ( isset( $_POST['yoast_wpseo_focuskw_text_input'] ) ) {
        unset( $_POST['yoast_wpseo_focuskw_text_input'] );
    } // this causes creation a ghost db entry in wp_postmeta with meta_key '_yoast_wpseo_focuskw_text_input', while the wanted value is stored in '_yoast_wpseo_focuskw'
}

qwpseo_admin_filters();

/**
 * Modifies dependencies of the Yoast scripts.
 * @return void
 */
function qwpseo_script_deps() {
    global $pagenow;
    switch ( $pagenow ) {
        case 'edit-tags.php':
        case 'term.php':
            $handles = array( 'term-scraper' => 'qwpseo-prep' );
            break;
        case 'post.php':
        case 'post-new.php':
            $handles = array( 'post-scraper' => 'qtranslate-admin-common' );
            break;
        default:
            return;
    }

    $scripts    = wp_scripts();
    $registered = $scripts->registered;

    //$handles = array('post-scraper', 'term-scraper', 'replacevar-plugin', 'admin-global-script', 'metabox');
    //$handles = array('term-scraper');
    foreach ( $handles as $handle => $dep ) {
        $key = WPSEO_Admin_Asset_Manager::PREFIX . $handle;
        if ( ! isset( $registered[ $key ] ) ) {
            continue;
        }
        $r = &$registered[ $key ];
        if ( ! isset( $r->deps ) ) {
            $r->deps = array();
        }
        $r->deps[] = $dep;
    }
}

/**
 * Very ugly hack to workaround. Fortunately this problem is gone after some deprecation in 3.0
 *
 * @param mixed $original_value
 * @param mixed $object_id
 * @param mixed $meta_key
 * @param mixed $single
 *
 * @return mixed
 */
function qwpseo_get_post_metadata( $original_value, $object_id, $meta_key = '', $single = false ) {
    global $q_config;
    if ( empty( $meta_key ) ) {
        //very ugly hack
        $trace = debug_backtrace();
        if ( isset( $trace[7]['function'] ) && $trace[7]['function'] === 'calculate_results' &&
             isset( $trace[6]['args'][0] ) && $trace[6]['args'][0] === 'focuskw'
        ) {
            $key     = WPSEO_Meta::$meta_prefix . 'focuskw';
            $focuskw = get_metadata( 'post', $object_id, $key, true );
            $focuskw = qtranxf_use_language( $q_config['language'], $focuskw );

            return array( $key => array( $focuskw ) );
        }
    }

    return $original_value;
}

/**
 * adds single-language sitemap links to the Yoast configuration page for XML Sitemaps.
 */
function qwpseo_xmlsitemaps_config() {
    global $q_config;
    $options = get_option( 'wpseo_xml' );
    if ( empty( $options['enablexmlsitemap'] ) ) {
        return;
    }
    printf( __( '%sNotes from %s' . PHP_EOL ), '<h3>', 'qTranslate&#8209;XT</h3>' );
    echo '<p>' . PHP_EOL;
    echo __( 'In addition to main XML Sitemap, you may also view sitemaps for each individual language:' ) . PHP_EOL;
    echo '<ul>' . PHP_EOL;
    $sitemap_index_url = qtranxf_convertURL( get_option( 'home' ) . '/sitemap_index.xml', $q_config['default_language'], true );
    $url               = home_url( 'i18n-index-sitemap.xml' );
    $rb                = '';
    foreach ( $q_config['enabled_languages'] as $lang ) {
        $href = qtranxf_convertURL( $url, $lang, true, true );
        $u    = $q_config['default_language'] == $lang ? qtranxf_convertURL( $url, $lang, true, false ) : $href;
        echo '<li>' . $q_config['language_name'][ $lang ] . ' (' . $lang . ', ' . $q_config['locale'][ $lang ] . '): <a href="' . $href . '" target="_blank">' . $u . '</a></li>' . PHP_EOL;
        $rb .= 'Sitemap: ' . $u . PHP_EOL;
    }
    echo '</ul><br />' . PHP_EOL;
    printf( __( 'It is advisable to append the site\'s "%s" with the list of index sitemaps separated by language' ), '/robots.txt' );
    $nmaps = count( $q_config['enabled_languages'] ) + 1;
    echo '<br /><textarea class="widefat" rows="' . $nmaps . '" name="robots-sitemaps" readonly="readonly">' . $rb . '</textarea>' . PHP_EOL;
    //echo '<pre>'.$rb.'</pre>'.PHP_EOL;
    echo '<br />or with this single entry of flat multilingual index sitemap<br /><textarea class="widefat" rows="2" name="robots-sitemap" readonly="readonly">Sitemap: ' . $sitemap_index_url . '</textarea>' . PHP_EOL;
    echo '<br />Do not combine two sets together, since they both equally cover all languages in all pages as defined by Yoast configuration.';
    echo '</p>' . PHP_EOL;
}

add_action( 'wpseo_xmlsitemaps_config', 'qwpseo_xmlsitemaps_config' );

/**
 * Change encoding of $value to swirly breckets, '{'.
 * @since 1.1
 */
function qwpseo_encode_swirly( $value ) {
    $lang_code = QTX_LANG_CODE;
    $value     = preg_replace( '#\[:($lang_code|)]#i', '{:$1}', $value );

    return $value;
}

/**
 * Workaround for "filter_input( INPUT_POST, $key )" in function update_term(...) in /plugins/wordpress-seo/admin/taxonomy/class-taxonomy.php.
 * Function 'filter_input' does not read $_POST and there seem to be no way to alter values provided by INPUT_POST.
 *
 * @param string $key
 * @param mixed $value
 * @param mixed $value_posted
 * @param mixed $value_old
 *
 * @return mixed
 */
function qwpseo_parse_value_posted( $key, $value, $value_posted = null, $value_old = null ) {
    if ( empty( $_POST[ $key ] ) || ! qtranxf_isMultilingual( $_POST[ $key ] ) || ! class_exists( 'WPSEO_Utils' ) ) {
        return $value;
    }
    $v = WPSEO_Utils::sanitize_text_field( stripslashes( $_POST[ $key ] ) );

    return $v;
}

/**
 * Response to
 * apply_filters( 'wpseo_sanitize_tax_meta_' . $key, $clean[ $key ], ( isset( $meta_data[ $key ] ) ? $meta_data[ $key ] : null ), ( isset( $old_meta[ $key ] ) ? $old_meta[ $key ] : null ) );
 *
 * @param mixed $value
 * @param mixed $value_posted
 * @param mixed $value_old
 *
 * @return mixed
 */
function qwpseo_sanitize_tax_meta( $value, $value_posted = null, $value_old = null ) {
    $key = current_filter();
    $key = substr( $key, 24 );

    return qwpseo_parse_value_posted( $key, $value, $value_posted, $value_old );
}

/**
 * Response to
 * $check = apply_filters( "get_{$meta_type}_metadata", null, $object_id, $meta_key, $single );
 *
 * @param mixed $value
 * @param mixed $object_id
 * @param mixed $meta_key
 * @param mixed $single
 *
 * @return mixed
 */
function qwpseo_get_metadata_post( $value, $object_id, $meta_key, $single ) {
    $meta_type = 'post';
    //code from function get_metadata in /wp-includes/meta.php

    $meta_cache = wp_cache_get( $object_id, $meta_type . '_meta' );

    if ( ! $meta_cache ) {
        $meta_cache = update_meta_cache( $meta_type, array( $object_id ) );
        $meta_cache = $meta_cache[ $object_id ];
    }

    $mlcache = array();
    if ( ! isset( $mlcache[ $object_id ] ) ) {
        $mlcache[ $object_id ] = array();
        $cache                 = &$mlcache[ $object_id ];
        $lang                  = qtranxf_getLanguage();
        foreach ( $meta_cache as $key => $values ) {
            foreach ( $values as $i => $value ) {
                if ( qtranxf_isMultilingual( $value ) ) {
                    if ( is_serialized( $value ) ) {
                        $mlv   = unserialize( $value );
                        $v     = qtranxf_use( $lang, $mlv, false, false );
                        $value = serialize( $v );
                    } else {
                        $value = qtranxf_use_language( $lang, $value, false, false );
                    }
                    $values[ $i ] = $value;
                }
            }
            $cache[ $key ] = $values;
        }
        $meta_cache = $cache;
    }

    if ( ! $meta_key ) {
        return $meta_cache;
    }

    if ( isset( $meta_cache[ $meta_key ] ) ) {
        if ( $single ) {
            return maybe_unserialize( $meta_cache[ $meta_key ][0] );
        } else {
            return array_map( 'maybe_unserialize', $meta_cache[ $meta_key ] );
        }
    }

    if ( $single ) {
        return '';
    } else {
        return array();
    }
}
