<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function qtranxf_wpseo_add_filters_front(): void {
    // Use indexation on "publish/update" events to save all languages data in indexable tables.
    // If indexation is allowed on the frontend then indexable table saves data of the first visited page
    // and other languages are missed.
    add_filter( 'Yoast\WP\SEO\should_index_indexables', '__return_false' );
    add_filter( 'wpseo_should_save_indexable', '__return_false' );
    add_filter( 'wpseo_indexing_data', '__return_false' );

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

    function qtranxf_wpseo_webpage_schema( array $piece, string $context ): array {
        if ( isset( $piece['description'] ) ) {
            $piece['description'] = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $piece['description'] );
        }
        if ( isset( $piece['name'] ) ) {
            $piece['name'] = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $piece['name'] );
        }

        return $piece;
    }

    add_filter( 'wpseo_schema_webpage', 'qtranxf_wpseo_webpage_schema', 10, 2 );

    function qtranxf_wpseo_breadcrumbs_link( array $link_info, int $index, array $crumbs ): array {
        global $q_config;

        if ( isset( $link_info['text'] ) ) {
            $link_info['text'] = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $link_info['text'] );
        }
        if ( isset( $link_info['url'] ) ) {
            $link_info['url'] = qtranxf_convertURL( $link_info['url'], $q_config['language'] );
        }

        return $link_info;
    }

    add_filter( 'wpseo_breadcrumb_single_link_info', 'qtranxf_wpseo_breadcrumbs_link', 10, 3 );

    function qtranxf_wpseo_schema_organization( array $data ): array {
        global $q_config;

        if ( isset( $data['@id'] ) ) {
            $data['@id'] = qtranxf_convertURL( $data['@id'], $q_config['language'] );
        }
        if ( isset( $data['name'] ) ) {
            $data['name'] = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $data['name'] );
        }
        if ( isset( $data['url'] ) ) {
            $data['url'] = qtranxf_convertURL( $data['url'], $q_config['language'] );
        }
        if ( isset( $data['logo']['@id'] ) ) {
            $data['logo']['@id'] = qtranxf_convertURL( $data['logo']['@id'], $q_config['language'] );
        }
        if ( isset( $data['logo']['caption'] ) ) {
            $data['logo']['caption'] = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $data['logo']['caption'] );
        }
        if ( isset( $data['image']['@id'] ) ) {
            $data['image']['@id'] = qtranxf_convertURL( $data['image']['@id'], $q_config['language'] );
        }

        return $data;
    }

    add_filter( 'wpseo_schema_organization', 'qtranxf_wpseo_schema_organization' );

    function qtranxf_wpseo_schema_website( array $data ): array {
        global $q_config;

        if ( isset( $data['@id'] ) ) {
            $data['@id'] = qtranxf_convertURL( $data['@id'], $q_config['language'] );
        }
        if ( isset( $data['url'] ) ) {
            $data['url'] = qtranxf_convertURL( $data['url'], $q_config['language'] );
        }
        if ( isset( $data['publisher']['@id'] ) ) {
            $data['publisher']['@id'] = qtranxf_convertURL( $data['publisher']['@id'], $q_config['language'] );
        }

        return $data;
    }

    add_filter( 'wpseo_schema_website', 'qtranxf_wpseo_schema_website' );

    function qtranxf_wpseo_json_ld_search_url( string $search_url ): string {
        global $q_config;

        return qtranxf_convertURL( $search_url, $q_config['language'] );
    }

    add_filter( 'wpseo_json_ld_search_url', 'qtranxf_wpseo_json_ld_search_url', 10, 3 );

    function qtranxf_wpseo_schema_imageobject( array $data ): array {
        global $q_config;

        if ( isset( $data['@id'] ) ) {
            $data['@id'] = qtranxf_convertURL( $data['@id'], $q_config['language'] );
        }

        return $data;
    }

    add_filter( 'wpseo_schema_imageobject', 'qtranxf_wpseo_schema_imageobject' );

    function qtranxf_wpseo_schema_webpage( array $data ): array {
        global $q_config;

        $lang = $q_config['language'];

        if ( isset( $data['@id'] ) ) {
            $data['@id'] = qtranxf_convertURL( $data['@id'], $lang );
        }
        if ( isset( $data['url'] ) ) {
            $data['url'] = qtranxf_convertURL( $data['url'], $lang );
        }
        if ( isset( $data['isPartOf']['@id'] ) ) {
            $data['isPartOf']['@id'] = qtranxf_convertURL( $data['isPartOf']['@id'], $lang );
        }
        if ( isset( $data['primaryImageOfPage']['@id'] ) ) {
            $data['primaryImageOfPage']['@id'] = qtranxf_convertURL( $data['primaryImageOfPage']['@id'], $lang );
        }
        // It seems to work fine for $data['author']['@id'] without filter.
        if ( isset( $data['breadcrumb']['@id'] ) ) {
            $data['breadcrumb']['@id'] = qtranxf_convertURL( $data['breadcrumb']['@id'], $lang );
        }
        if ( isset( $data['potentialAction'][0]['target'][0] ) ) {
            $data['potentialAction'][0]['target'][0] = [ qtranxf_convertURL( $data['potentialAction'][0]['target'][0], $lang ) ];
        }

        return $data;
    }

    add_filter( 'wpseo_schema_webpage', 'qtranxf_wpseo_schema_webpage' );

    function qtranxf_wpseo_schema_breadcrumb( array $data ): array {
        global $q_config;

        if ( isset( $data['@id'] ) ) {
            $data['@id'] = qtranxf_convertURL( $data['@id'], $q_config['language'] );
        }

        return $data;
    }

    add_filter( 'wpseo_schema_breadcrumb', 'qtranxf_wpseo_schema_breadcrumb' );

    function qtranxf_wpseo_schema_person( array $data ): array {
        global $q_config;

        //$data['@id'] = qtranxf_convertURL($data['@id'], $lang); //Not sure is it required to filter or not???
        if ( isset( $data['image']['@id'] ) ) {
            $data['image']['@id'] = qtranxf_convertURL( $data['image']['@id'], $q_config['language'] );
        }

        return $data;
    }

    add_filter( 'wpseo_schema_person', 'qtranxf_wpseo_schema_person' );

    function qtranxf_wpseo_next_prev_filter( string $link ): string {
        global $q_config;

        if ( preg_match_all( '/<link[^>]+href=([\'"])(?<href>.+?)\1[^>]*>/i', $link, $link_extract_href ) &&
             preg_match_all( '/<link[^>]+rel=([\'"])(?<rel>.+?)\1[^>]*>/i', $link, $link_extract_rel ) ) {
            $link = '<link rel="' . $link_extract_rel['rel'][0] . '" href="' . qtranxf_convertURL( $link_extract_href['href'][0], $q_config['language'] ) . '" />';
        }

        return $link;
    }

    add_filter( 'wpseo_next_rel_link', 'qtranxf_wpseo_next_prev_filter' );
    add_filter( 'wpseo_prev_rel_link', 'qtranxf_wpseo_next_prev_filter' );
}

// TODO: trigger this with a proper hook - qtranslate_front_config can't be used in modules, here it's too late!
qtranxf_wpseo_add_filters_front();
