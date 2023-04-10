<?php
/**
 * Admin handler for the block editor (Gutenberg)
 * @author: herrvigg
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class QTX_Admin_Block_Editor
 *
 * Manages the block editor (Gutenberg) with the related REST API.
 * Limitation: only the single language mode is supported.
 */
class QTX_Admin_Block_Editor {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
    }

    /**
     * Register the REST filters
     */
    public function rest_api_init(): void {
        global $q_config;

        // Filter to allow qTranslate-XT to manage the block editor (single language mode)
        $admin_block_editor = apply_filters( 'qtranslate_admin_block_editor', true );
        if ( ! $admin_block_editor ) {
            return;
        }

        $post_types = get_post_types( array( 'show_in_rest' => true ) );
        foreach ( $post_types as $post_type ) {
            $post_type_excluded = isset( $q_config['post_type_excluded'] ) && in_array( $post_type, $q_config['post_type_excluded'] );
            if ( ! $post_type_excluded ) {
                add_filter( "rest_prepare_{$post_type}", array( $this, 'rest_prepare' ), 99, 3 );
            }
        }

        add_filter( 'rest_request_before_callbacks', array( $this, 'rest_request_before_callbacks' ), 99, 3 );
        add_filter( 'rest_request_after_callbacks', array( $this, 'rest_request_after_callbacks' ), 99, 3 );
    }

    /**
     * Prepare the REST request for a post being edited
     *
     * Set the raw content and the 'qtx_editor_lang' field for the current language.
     *
     * @param WP_REST_Response $response
     * @param WP_Post $post
     * @param WP_REST_Request $request
     *
     * @return mixed
     */
    public function rest_prepare( WP_REST_Response $response, WP_Post $post, WP_REST_Request $request ) {
        global $q_config;

        if ( $request->get_param( 'context' ) !== 'edit' || $request->get_method() !== 'GET' ) {
            return $response;
        }

        // See https://github.com/WordPress/gutenberg/issues/14012#issuecomment-467015362
        require_once ABSPATH . 'wp-admin/includes/post.php';

        if ( ! use_block_editor_for_post( $post ) ) {
            return $response;
        }

        assert( ! $q_config['url_info']['doing_front_end'] );

        // TODO allow user to select editor lang with buttons
        $editor_lang = $q_config['url_info']['language'];

        return $this->select_raw_response_language( $response, $editor_lang );
    }

    /**
     * Intercepts the post update and recompose the multi-language fields before being written in DB
     *
     * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $response
     * @param array $handler
     * @param WP_REST_Request $request
     *
     * @return mixed
     */
    public function rest_request_before_callbacks( $response, array $handler, WP_REST_Request $request ) {
        if ( $request->get_method() !== 'PUT' && $request->get_method() !== 'POST' ) {
            return $response;
        }

        $editor_lang = $request->get_param( 'qtx_editor_lang' );
        if ( ! isset( $editor_lang ) ) {
            return $response;
        }

        $request_body = json_decode( $request->get_body(), true );
        $post         = get_post( $request->get_param( 'id' ), ARRAY_A );

        $fields = [ 'title', 'content', 'excerpt' ];
        foreach ( $fields as $field ) {
            if ( ! isset( $request_body[ $field ] ) ) {
                continue; // only the changed fields are set in the REST request
            }

            // split original values with empty strings by default
            $original_value = $post[ 'post_' . $field ];
            $split          = qtranxf_split( $original_value );

            // replace current language with the new value
            $split[ $editor_lang ] = $request_body[ $field ];

            // remove auto-draft default title for other languages (not the correct translation)
            if ( $field === 'title' && $post['post_status'] === 'auto-draft' ) {
                global $q_config;
                foreach ( $q_config['enabled_languages'] as $lang ) {
                    if ( $lang !== $editor_lang ) {
                        $split[ $lang ] = '';
                    }
                }
            }

            // TODO handle custom separator
            //$sep = '[';
            //$new_data = qtranxf_collect_translations_deep( $split, $sep );
            //$new_data = qtranxf_join_texts( $split, $sep );
            $new_data = qtranxf_join_b( $split );

            $request->set_param( $field, $new_data );
        }

        return $response;
    }

    /**
     * Restore the raw content of the post just updated and set the 'qtx_editor_lang', as for the prepare step
     *
     * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $response
     * @param array $handler
     * @param WP_REST_Request $request
     *
     * @return mixed
     */
    public function rest_request_after_callbacks( $response, array $handler, WP_REST_Request $request ) {
        if ( ! $response instanceof WP_HTTP_Response // This includes WP_REST_Response that derives from it.
             || $request->get_param( 'context' ) !== 'edit' || ( $request->get_method() !== 'PUT' && $request->get_method() !== 'POST' ) ) {
            return $response;
        }

        $editor_lang = $request->get_param( 'qtx_editor_lang' );
        if ( ! isset( $editor_lang ) ) {
            return $response;
        }

        return $this->select_raw_response_language( $response, $editor_lang );
    }

    /**
     * Enqueue the JS script
     */
    public function enqueue_block_editor_assets(): void {
        // By default, excluded post types are filtered out.
        global $q_config;
        $post_type          = qtranxf_post_type();
        $post_type_excluded = isset( $q_config['post_type_excluded'] ) && isset( $post_type ) && in_array( $post_type, $q_config['post_type_excluded'] );

        // Filter to allow qTranslate-XT to manage the block editor (single language mode)
        $admin_block_editor = apply_filters( 'qtranslate_admin_block_editor', ! $post_type_excluded );
        if ( ! $admin_block_editor ) {
            return;
        }

        wp_register_script(
            'qtx-block-editor',
            plugins_url( 'dist/block-editor.js', QTRANSLATE_FILE ),
            array(),
            QTX_VERSION,
            true
        );
        wp_enqueue_script( 'qtx-block-editor' );
    }

    /**
     * Replace the multi-language raw content with only the current language used for edition and set 'qtx_editor_lang'
     *
     * @param WP_HTTP_Response|WP_REST_Response $response
     * @param string $editor_lang
     *
     * @return WP_HTTP_Response|WP_REST_Response
     */
    private function select_raw_response_language( $response, string $editor_lang ) {
        $response_data = $response->get_data();
        if ( isset( $response_data['title']['raw'] ) ) {
            $response_data['title']['raw'] = qtranxf_use( $editor_lang, $response_data['title']['raw'], false, true );
        }
        if ( isset( $response_data['content']['raw'] ) ) {
            $response_data['content']['raw'] = qtranxf_use( $editor_lang, $response_data['content']['raw'], false, true );
        }
        if ( isset( $response_data['excerpt']['raw'] ) ) {
            $response_data['excerpt']['raw'] = qtranxf_use( $editor_lang, $response_data['excerpt']['raw'], false, true );
        }
        $response_data['qtx_editor_lang'] = $editor_lang;
        $response->set_data( $response_data );
        return $response;
    }

}

new QTX_Admin_Block_Editor();
