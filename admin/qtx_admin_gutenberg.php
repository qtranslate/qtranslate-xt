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
     * @var bool displays a warning if the single language editor mode is enforced
     */
    private $single_mode_enforced = false;

    /**
     * QTX_Admin_Gutenberg constructor
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
        add_action( 'qtranslate_admin_loadConfig', array( $this, 'load_configuration' ) );
        add_action( 'qtranslate_saveConfig', array( $this, 'save_configuration' ) );
        add_action( 'admin_notices', array( $this, 'admin_notices_block_editor' ) );
    }

    /**
     * Register the REST filters
     */
    public function rest_api_init() {
        global $q_config;

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
     * Intercepts the post update and recompose the multi-language fields before being written in DB
     *
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

        $fields = [ 'title', 'content', 'excerpt' ];
        foreach ( $fields as $field ) {
            if ( ! isset( $request_body[ $field ] ) ) {
                // only the changed fields are set in the REST request
                continue;
            }
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

        return $response;
    }

    /**
     * Restore the raw content of the post just updated and set the 'qtx_editor_lang', as for the prepare step
     *
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

    /**
     * Enqueue the JS script
     */
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

    /**
     * Force configuration to single language mode on loading
     */
    public function load_configuration() {
        global $q_config;

        if ( $q_config['editor_mode'] != QTX_EDITOR_MODE_SINGLE ) {
            $q_config['editor_mode']    = QTX_EDITOR_MODE_SINGLE;
            $this->single_mode_enforced = true;
        }
    }

    /**
     * Force configuration to single language mode on options update
     */
    public function save_configuration() {
        global $q_config;

        if ( $q_config['editor_mode'] != QTX_EDITOR_MODE_SINGLE ) {
            $q_config['editor_mode']    = QTX_EDITOR_MODE_SINGLE;
            $this->single_mode_enforced = true;
        } else {
            // cancel warning if single mode just saved
            $this->single_mode_enforced = false;
        }
    }

    /**
     * Show admin notice for Gutenberg
     */
    public function admin_notices_block_editor() {
        $link_classic = "https://wordpress.org/plugins/classic-editor/";
        $link_plugins = admin_url( 'plugins.php' );
        ?>
        <div class="notice notice-warning">
            <p><?php printf( __( 'Caution! The block editor (Gutenberg) is only partially supported in %s, yet experimental. Use at your own discretion! Alternatively, install and activate the <a href="%s"> Classic Editor</a> in your <a href="%s">plugins</a>.', 'qtranslate' ), 'qTranslate&#8209;XT', $link_classic, $link_plugins ); ?></p>
        </div>
        <?php

        if ( $this->single_mode_enforced ) {
            $link = admin_url( 'options-general.php?page=qtranslate-xt#advanced' );
            ?>
            <div class="notice notice-warning">
                <p><?php printf( __( 'With the block editor (Gutenberg) only the single language mode is currently supported in %s, which has been enforced. Review and save your <a href="%s"> options</a> to remove this warning. Be sure to switch back if you turn back to the Classic Editor.', 'qtranslate' ), 'qTranslate&#8209;XT', $link ); ?></p>
            </div>
            <?php
        }
    }

    /**
     * Replace the multi-language raw content with only the current language used for edition and set 'qtx_editor_lang'
     *
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
            $response_data['excerpt']['raw']  = qtranxf_use( $editor_lang, $response_data['excerpt']['raw'], false, true );
            $response_data['qtx_editor_lang'] = $editor_lang;
            $response->set_data( $response_data );
        }

        return $response;
    }

}

new QTX_Admin_Gutenberg();
