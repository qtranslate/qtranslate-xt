<?php
/**
 * Admin handler for Gutenberg
 * @author: herrvigg
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class QTX_Admin_Gutenberg
 *
 * Manages the Gutenberg block editor with the related REST API
 */
class QTX_Admin_Gutenberg {

    /**
     * QTX_Admin_Gutenberg constructor
     */
    public function __construct() {
        //add_filter( 'register_post_type_args', array($this, 'register_rest_controller'), 10, 2 );

        // TODO generalize to selected post types in options
        $post_type = 'post';
        add_filter( "rest_prepare_{$post_type}", array( $this, 'rest_prepare' ), 99, 3 );
        add_filter( 'rest_request_before_callbacks', array( $this, 'rest_request_before_callbacks' ), 99, 3 );
        add_filter( 'rest_request_after_callbacks', array( $this, 'rest_request_after_callbacks' ), 99, 3 );

        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
        add_action( 'qtranslate_admin_loadConfig', array( $this, 'load_configuration' ) );
        add_action( 'admin_notices', array( $this, 'admin_notices_block_editor' ) );
    }

//	/**
//	 * Set up a custom REST API controller class
//	 *
//	 * @param array $args The post type arguments.
//	 * @param string $name The name of the post type.
//	 *
//	 * @return array $args The post type arguments, possibly modified.
//	 */
//	public function register_rest_controller( $args, $name ) {
//		$args['rest_controller_class'] = 'QTX_REST_Post_Controller';
//
//		return $args;
//	}

    /**
     * @param WP_REST_Response $response
     * @param WP_Post $post
     * @param WP_REST_Request $request
     *
     * @return mixed
     */
    public function rest_prepare( $response, $post, $request ) {
        global $q_config;

        if ( $request['context'] !== 'edit' || $request->get_method() !== 'GET' ) {
            return $response;
        }

        assert( ! $q_config['url_info']['doing_front_end'] );

        // TODO allow user to select editor lang with buttons
        $editor_lang = $q_config['url_info']['language'];

        $response = $this->select_raw_response_language( $response, $editor_lang );

        return $response;
    }

    /**
     * @param WP_HTTP_Response $response
     * @param array $handler
     * @param WP_REST_Request $request
     *
     * @return mixed
     */
    public function rest_request_before_callbacks( $response, $handler, $request ) {
        if ( $request->get_method() !== 'PUT' && $request->get_method() !== 'POST' ) {
            return $response;
        }

        $editor_lang = $request->get_param( 'qtx_editor_lang' );
        if ( ! isset( $editor_lang ) ) {
            return $response;
        }

        $request_body = json_decode( $request->get_body(), true );
        $post         = get_post( $request->get_param( 'id' ), ARRAY_A );

        $fields = [ 'content', 'title' ];
        foreach ( $fields as $field ) {

            if ( isset( $request_body[ $field ] ) ) {
                $new_value = $request_body[ $field ];

                $original_value = $post[ 'post_' . $field ];
                $blocks         = qtranxf_get_language_blocks( $original_value );
                if ( count( $blocks ) > 1 ) {
                    $split                 = qtranxf_split_languages( $blocks );
                    $split[ $editor_lang ] = $new_value;
                } else {
                    global $q_config;
                    $split = array();
                    foreach ( $q_config['enabled_languages'] as $lang ) {
                        if ( $lang === $editor_lang ) {
                            continue;
                        }
                        if ( $field === 'title' && $post['post_status'] === 'auto-draft' ) {
                            // remove default title for auto-draft for other languages
                            $split[ $lang ] = '';
                        } else {
                            $split[ $lang ] = $original_value;
                        }
                    }
                    $split[ $editor_lang ] = $new_value;
                }

                // TODO handle custom separator
                //$sep = '[';
                //$new_data = qtranxf_collect_translations_deep( $split, $sep );
                //$new_data = qtranxf_join_texts( $split, $sep );
                $new_data = qtranxf_join_b( $split );

                $request->set_param( $field, $new_data );
                //$request_body[ $field ] =  $new_data;
            }
        }

        return $response;
    }

    /**
     * @param WP_HTTP_Response $response
     * @param array $handler
     * @param WP_REST_Request $request
     *
     * @return mixed
     */
    public function rest_request_after_callbacks( $response, $handler, $request ) {
        if ( $request['context'] !== 'edit' || $request->get_method() !== 'PUT' && $request->get_method() !== 'POST' ) {
            return $response;
        }

        $editor_lang = $request->get_param( 'qtx_editor_lang' );
        if ( ! isset( $editor_lang ) ) {
            return $response;
        }

        $response = $this->select_raw_response_language( $response, $editor_lang );

        return $response;
    }

    public function enqueue_block_editor_assets() {
        $script_file = 'js/lib/editor-gutenberg.js';
        wp_register_script(
            'qtx-gutenberg',
            plugins_url( $script_file, __FILE__ ),
            array(),
            filemtime( plugin_dir_path( __FILE__ ) . $script_file ),
            true
        );
        wp_enqueue_script( 'qtx-gutenberg' );
    }

    public function load_configuration() {
        global $q_config;

        if ( $q_config['editor_mode'] == QTX_EDITOR_MODE_LSB ) {
            $q_config['editor_mode'] = QTX_EDITOR_MODE_SINGLE;
        }
    }

    public function admin_notices_block_editor() {
        $link = "https://wordpress.org/plugins/classic-editor/";
        ?>
        <div class="notice notice-warning">
            <p><?php printf( __( 'The block editor (Gutenberg) is only partially supported in %s, yet experimental. Use at your own discretion! Alternatively, install and activate the <a href="%s"> Classic Editor</a> plugin.', 'qtranslate' ), 'qTranslate&#8209;XT', $link ); ?></p>
        </div>
        <?php
    }

    /**
     * @param WP_HTTP_Response|WP_REST_Response $response
     * @param string $editor_lang
     *
     * @return mixed
     */
    private function select_raw_response_language( $response, $editor_lang ) {
        $response_data = $response->get_data();
        if ( isset( $response_data['content'] ) && is_array( $response_data['content'] ) && isset( $response_data['content']['raw'] ) ) {
            $response_data['title']['raw']    = qtranxf_use( $editor_lang, $response_data['title']['raw'], false, true );
            $response_data['content']['raw']  = qtranxf_use( $editor_lang, $response_data['content']['raw'], false, true );
            $response_data['qtx_editor_lang'] = $editor_lang;
            $response->set_data( $response_data );
        }

        return $response;
    }
}

new QTX_Admin_Gutenberg();
