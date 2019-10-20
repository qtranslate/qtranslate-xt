<?php
/**
 * Built-in module for Jetpack
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class QTX_Jetpack {
    public function __construct() {
        add_filter( 'jetpack_relatedposts_returned_results', array( $this, 'translate_related_posts' ) );
    }

    /**
     * Translates related posts through Jetpack REST API
     *
     * @param array $results related posts with fetched data in Jetpack format
     *
     * @return array updated related posts
     * @see get_related_post_data_for_post in jetpack/modules/related_posts/jetpack-related-posts.php
     */
    function translate_related_posts( $results ) {
        return array_map( function ( $result ) {
            $result['title']   = qtranxf_useCurrentLanguageIfNotFoundShowAvailable( $result['title'] );
            $result['excerpt'] = qtranxf_useCurrentLanguageIfNotFoundShowAvailable( $result['excerpt'] );

            return $result;
        }, $results );
    }

}

new QTX_Jetpack();
