<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function qtranxf_wpseo_add_filters_front() {
    // Use indexation only in backend on "publish/update" events to save all languages data in indexable tables.
    // If you allow indexation on the frontend - then indexable table will save data of the first visited page, all other languages data will be missed/filtered/gone/didn't saved.
    // After all this fixes everyone need to install "Yoast Test Helper", go to "Instruments -> Yoast Test" and reset "Indexables & migrations table", then go to "SEO -> Instruments" and start SEO data optimization.
    // This filters "wpseo_should_save_indexable", "wpseo_indexing_data" - right now works only on frontend, so using of "!is_admin()" is pointless, but maybe in future this filters will work on backend, that's why I have leaved "!is_admin()" below.
    // Unfortunately I didn't found how to totally disable indexable feature, Yoast documentation is very poor to quickly understand how everything works.
    // Also be advised that after Yoast have added indexables - now you also need to provide breadcrumb titles and its translations even if they are same as in title. If you wouldn't provide breadcrumb title & translation - Yoast will only index current language title.
    if ( ! is_admin() ) {
        add_filter( 'wpseo_should_save_indexable', '__return_false' );
        add_filter( 'wpseo_indexing_data', '__return_false' );
    }

    add_filter( 'wpseo_canonical', 'qtranxf_checkCanonical', 10, 2 );
    add_filter( 'wpseo_opengraph_url', 'qtranxf_checkCanonical', 10, 2 );

    # For reference: https://developer.yoast.com/customization/apis/metadata-api/
    $use_filters = array(
        # Generic presenters
        'wpseo_metadesc'            => 20,
        'wpseo_title'               => 20,
        # Twitter presenters
        'wpseo_twitter_description' => 20,
        'wpseo_twitter_title'       => 20,
        # OpenGraph presenters
        'wpseo_opengraph_desc'      => 20,
        'wpseo_opengraph_title'     => 20,
    );

    foreach ( $use_filters as $name => $priority ) {
        add_filter( $name, 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', $priority );
    }

    add_filter( 'wpseo_schema_webpage', 'qtranxf_wpseo_webpage_schema', 10, 2 );
    function qtranxf_wpseo_webpage_schema( $piece, $context ) {
        if ( array_key_exists( 'description', $piece ) ) {
            $piece['description'] = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $piece['description'] );
        }
        if ( array_key_exists( 'name', $piece ) ) {
            $piece['name'] = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $piece['name'] );
        }

        return $piece;
    }

    add_filter( 'wpseo_breadcrumb_single_link_info', 'qtranxf_wpseo_breadcrumbs_link', 10, 3 );
    function qtranxf_wpseo_breadcrumbs_link( $link_info, $index, $crumbs ) {
        $link_info['text'] = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $link_info['text'] );
        $link_info['url']  = qtranxf_convertURL( $link_info['url'], $lang );

        return $link_info;
    }

    function qtranxf_wpseo_schema_website( $data ) {
        $data['@id'] = qtranxf_convertURL( $data['@id'], $lang );
        $data['url'] = qtranxf_convertURL( $data['url'], $lang );

        return $data;
    }

    add_filter( 'wpseo_schema_website', 'qtranxf_wpseo_schema_website' );

    function qtranxf_wpseo_json_ld_search_url( $search_url ) {
        $search_url = qtranxf_convertURL( $search_url, $lang );

        return $search_url;
    }

    add_filter( 'wpseo_json_ld_search_url', 'qtranxf_wpseo_json_ld_search_url', 10, 3 );

    function qtranxf_wpseo_schema_imageobject( $data ) {
        $data['@id'] = qtranxf_convertURL( $data['@id'], $lang );

        return $data;
    }

    add_filter( 'wpseo_schema_imageobject', 'qtranxf_wpseo_schema_imageobject' );

    function qtranxf_wpseo_schema_webpage( $data ) {
        $data['@id'] = qtranxf_convertURL( $data['@id'], $lang );
        $data['url'] = qtranxf_convertURL( $data['url'], $lang );
        if ( $data['isPartOf']['@id'] ) {
            $data['isPartOf']['@id'] = qtranxf_convertURL( $data['isPartOf']['@id'], $lang );
        }
        if ( $data['primaryImageOfPage']['@id'] ) {
            $data['primaryImageOfPage']['@id'] = qtranxf_convertURL( $data['primaryImageOfPage']['@id'], $lang );
        }
        //if($data['author']['@id']){$data['author']['@id'] = qtranxf_convertURL($data['author']['@id'], $lang);} //Not sure is it required to filter or not???
        if ( $data['breadcrumb']['@id'] ) {
            $data['breadcrumb']['@id'] = qtranxf_convertURL( $data['breadcrumb']['@id'], $lang );
        }
        if ( $data['potentialAction'][0]['target'] ) {
            $data['potentialAction'][0]['target'] = [ qtranxf_convertURL( $data['potentialAction'][0]->target, $lang ) ];
        }

        return $data;
    }

    add_filter( 'wpseo_schema_webpage', 'qtranxf_wpseo_schema_webpage' );

    function qtranxf_wpseo_schema_breadcrumb( $data ) {
        $data['@id'] = qtranxf_convertURL( $data['@id'], $lang );

        return $data;
    }

    add_filter( 'wpseo_schema_breadcrumb', 'qtranxf_wpseo_schema_breadcrumb' );

    function qtranxf_wpseo_schema_person( $data ) {
        //$data['@id'] = qtranxf_convertURL($data['@id'], $lang); //Not sure is it required to filter or not???
        if ( $data['image']['@id'] ) {
            $data['image']['@id'] = qtranxf_convertURL( $data['image']['@id'], $lang );
        }

        return $data;
    }

    add_filter( 'wpseo_schema_person', 'qtranxf_wpseo_schema_person' );

    function qtranxf_wpseo_next_prev_filter( $link ) {
        preg_match_all( '/<link[^>]+href=([\'"])(?<href>.+?)\1[^>]*>/i', $link, $link_extract_href );
        preg_match_all( '/<link[^>]+rel=([\'"])(?<rel>.+?)\1[^>]*>/i', $link, $link_extract_rel );
        $link = '<link rel="' . $link_extract_rel['rel'][0] . '" href="' . qtranxf_convertURL( $link_extract_href['href'][0], $lang ) . '" />';

        return $link;
    }

    add_filter( 'wpseo_next_rel_link', 'qtranxf_wpseo_next_prev_filter' );
    add_filter( 'wpseo_prev_rel_link', 'qtranxf_wpseo_next_prev_filter' );
}

// TODO: trigger this with a proper hook - qtranslate_front_config can't be used in modules, here it's too late!
qtranxf_wpseo_add_filters_front();
