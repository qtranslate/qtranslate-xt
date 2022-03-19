<?php

/**
 * QtranslateSlug class
 */
class QtranslateSlug {
    /**
     * Stores options slugs from database.
     * @var array
     */
    protected $options;

    /**
     * Array with old data system.
     * @var bool
     */
    //TODO: seems to be unused: remove
    private $old_data = null;

    /**
     * Stores permalink_structure option, for save queries to db.
     * @var string
     */
    private $permalink_structure;

    /**
     * Variable used to override the language.
     * @var string
     */
    private $lang = false;

    /**
     * Variable for current language.
     */
    //TODO: Check why not using QTX directly
    private $current_lang = false;

    /**
     * Variable for default language.
     */
    //TODO: Check why not using QTX directly
    private $default_language = false;

    /**
     * Array of enabled languages.
     * @var array
     */
    //TODO: Check why not using QTX directly
    private $enabled_languages = array();

    /**
     * Slug in meta_key name in meta tables.
     * @var string
     */
    private $meta_key = QTS_META_PREFIX . "%s";

    /**
     * Array of translated versions of the current url.
     * @var array
     */
    private $current_url = array();

    /**
     * getter: options.
     */
    public function get_options() {
        $this->set_options();

        return $this->options;
    }

    /**
     * setter: options | permalink_structure.
     */
    public function set_options() {
        if ( empty( $this->options ) ) {
            $this->options = get_option( QTS_OPTIONS_NAME );
        }
        if ( ! $this->options ) {
            add_option( QTS_OPTIONS_NAME, array() );
        }
        if ( is_null( $this->permalink_structure ) ) {
            $this->permalink_structure = get_option( 'permalink_structure' );
        }
    }

    /**
     * setter: options | permalink_structure.
     */
    public function save_options( $new_options = false ) {
        if ( ! $new_options || empty( $new_options ) ) {
            return;
        }
        if ( $this->options == $new_options ) {
            return;
        }
        update_option( QTS_OPTIONS_NAME, $new_options );
        flush_rewrite_rules();
        $this->options = $new_options;
    }

    /**
     * getter: meta key.
     */
    public function get_meta_key( $force_lang = false ) {
        $lang = $this->get_lang();
        if ( $force_lang ) {
            $lang = $force_lang;
        }

        return sprintf( $this->meta_key, $lang ); // returns: _qts_slug_en
    }

    /**
     * Do the installation, support multisite.
     */
    public function install() {
        if ( is_plugin_active_for_network( plugin_basename( QTRANSLATE_FILE ) ) ) {
            $old_blog = get_current_blog_id();
            $blogs    = get_sites();
            foreach ( $blogs as $blog ) {
                switch_to_blog( $blog->blog_id );
                $this->activate();
            }
            switch_to_blog( $old_blog );

            return;
        }

        $this->activate();
    }

    /**
     * Actions when deactivating the plugin.
     */
    public function deactivate() {
        global $wp_rewrite;

        // regenerate rewrite rules in db
        remove_action( 'generate_rewrite_rules', array( &$this, 'modify_rewrite_rules' ) );
        $wp_rewrite->flush_rules();
    }

    function qtranslate_updated_settings() {
        global $q_config;

        $options_modules = get_option( 'qtranslate_modules', array() );
        if ( $q_config['slugs_enabled'] ) {
            $this->install();
            $options_modules['slugs'] = QTX_MODULE_STATUS_ACTIVE;
        } else {
            $this->deactivate();
            $options_modules['slugs'] = QTX_MODULE_STATUS_INACTIVE;
        }
        update_option( 'qtranslate_modules', $options_modules );
    }

    /**
     * Initialise the Class with all hooks.
     */
    function init() {
        global $q_config;
        if ( ! $q_config['slugs_enabled'] ) {
            return;
        }

        if ( is_admin() ) {
            include_once( dirname( __FILE__ ) . '/qtranslate-slug-settings.php' );
        }
        // until we get  a proper function, this will make it for it.
        $this->current_lang      = $q_config['language'];
        $this->enabled_languages = $q_config['enabled_languages'];
        $this->default_language  = $q_config['default_language'];

        if ( is_admin() ) {
            // add filters
            add_filter( 'qts_validate_post_slug', array( &$this, 'validate_post_slug' ), 0, 3 );
            add_filter( 'qts_validate_post_slug', array( &$this, 'unique_post_slug' ), 1, 3 );
            add_filter( 'qts_validate_term_slug', array( &$this, 'validate_term_slug' ), 0, 3 );
            add_filter( 'qts_validate_term_slug', array( &$this, 'unique_term_slug' ), 1, 3 );
            add_filter( 'wp_get_object_terms', array( &$this, 'get_object_terms' ), 0, 4 );
            add_filter( 'get_terms', array( &$this, 'get_terms' ), 0, 3 );
            // admin actions
            add_action( 'add_meta_boxes', array( &$this, 'add_slug_meta_box' ) );
            add_action( 'save_post', array( &$this, 'save_postdata' ), 605, 2 );
            add_action( 'edit_attachment', array( $this, 'save_postdata' ) );
            add_action( 'created_term', array( &$this, 'save_term' ), 605, 3 );
            add_action( 'edited_term', array( &$this, 'save_term' ), 605, 3 );
            add_action( 'admin_head', array( &$this, 'hide_slug_box' ), 900 );

            add_action( 'init', array( &$this, 'taxonomies_hooks' ), 805 );

            add_action( 'admin_head', array( &$this, 'hide_quick_edit' ), 600 );

        } else {
            add_filter( 'request', array( &$this, 'filter_request' ) );
            $this->set_options();
        }
        //FIXME: query vars are broken
        add_filter( 'query_vars', array( &$this, 'query_vars' ) );
        add_action( 'generate_rewrite_rules', array( &$this, 'modify_rewrite_rules' ) );

        // remove from qtranslate the discouraged meta http-equiv, inline styles
        // (including flag URLs) and wrong hreflang links
        remove_action( 'wp_head', 'qtranxf_header' ); //TODO: check if it is needed, and why it is not in the main plugin in case
        remove_action( 'wp_head', 'qtranxf_wp_head' ); //TODO: check if it is needed, and why it is not in the main plugin in case

        // add proper hreflang links
        add_action( 'wp_head', array( &$this, 'qtranslate_slug_header_extended' ) );

        // remove some Qtranslate filters
        remove_filter( 'page_link', 'qtranxf_convertURL' ); //TODO: check if it is needed
        remove_filter( 'post_link', 'qtranxf_convertURL' ); //TODO: check if it is needed
        remove_filter( 'category_link', 'qtranxf_convertURL' ); //TODO: check if it is needed
        remove_filter( 'tag_link', 'qtranxf_convertURL' ); //TODO: check if it is needed

        //FIXME: query vars are broken
        add_filter( 'qts_permastruct', array( &$this, 'get_extra_permastruct' ), 0, 2 );
        add_filter( 'qts_url_args', array( &$this, 'parse_url_args' ), 0, 1 );
        add_filter( 'home_url', array( &$this, 'home_url' ), 10, 4 );
        add_filter( 'post_type_link', array( &$this, 'post_type_link' ), 600, 4 );
        add_filter( 'post_link', array( &$this, 'post_link' ), 0, 3 );
        add_filter( '_get_page_link', array( &$this, '_get_page_link' ), 0, 2 );
        add_filter( 'term_link', array( &$this, 'term_link' ), 600, 3 );

        add_filter( 'single_term_title', 'qtranxf_useTermLib', 805 );
        add_filter( 'get_blogs_of_user', array( &$this, 'blog_names' ), 1 );
        // Add specific CSS class to body class based on current lang
        add_filter( 'body_class', array(
            $this,
            'qts_body_class'
        ), 600, 1 ); //TODO: if it is needed, this should be moved to main plugin...
    }

    /**
     * Adds proper links to the content with available translations.
     * Fixes issue #25
     *
     * @global QtranslateSlug $qtranslate_slug used to convert the url
     * @global array $q_config available languages
     */
    public function qtranslate_slug_header_extended() {
        if ( is_404() ) {
            return;
        }
        // taken from qtx but see our #341 ticket for clarification
        echo '<link hreflang="x-default" href="' . esc_url( $this->get_current_url( $this->default_language ) ) . '" rel="alternate" />' . PHP_EOL;
        foreach ( $this->get_enabled_languages() as $language ) {

            echo '<link hreflang="' . $language . '" href="' . esc_url( $this->get_current_url( $language ) ) . '" rel="alternate" />' . "\n";
        }
    }

    /**
     * Add a class based on the current language.
     *
     * @param array $classes list of classes
     */
    public function qts_body_class( $classes ) {
        $classes[] = qtranxf_getLanguage();

        return $classes;
    }

    /**
     * Finds the translated slug of the given post
     * based on: https://wordpress.org/support/topic/permalink-for-other-languages.
     *
     * @param int $id the post id
     * @param string $lang which language to look for
     *
     * @return string the slug or empty if not found
     */
    public function get_slug( $id, $lang ) {
        $slugArray = get_post_meta( $id, QTS_META_PREFIX . $lang );

        return ! empty( $slugArray ) ? $slugArray[0] : "";
    }

    /**
     * Adds news rules to translate the URL bases,
     * this function must be called on flush_rewrite or 'flush_rewrite_rules'.
     *
     * @param object $wp_rewrite
     */
    public function modify_rewrite_rules() {
        // post types rules
        $post_types = $this->get_public_post_types();
        foreach ( $post_types as $post_type ) {
            $this->generate_extra_rules( $post_type->name );
        }
        // taxonomies rules
        $taxonomies = $this->get_public_taxonomies();
        foreach ( $taxonomies as $taxonomy ) {
            $this->generate_extra_rules( $taxonomy->name );
        }
    }

    /**
     * Helper that gets a base slug stored in options.
     *
     * @param string $name of extra permastruct
     *
     * @return string base slug for 'post_type' and 'language' or false
     */
    public function get_base_slug( $name = false, $lang = false ) {
        if ( ! $name || ! $lang ) {
            return false;
        }
        if ( taxonomy_exists( $name ) ) {
            $type = 'taxonomy';
        } else if ( post_type_exists( $name ) ) {
            $type = 'post_type';
        } else {
            return false;
        }
        $qts_options = $this->get_options();
        $option_name = QTS_PREFIX . $type . '_' . $name;
        if ( ! isset( $qts_options[ $option_name ] ) || empty( $qts_options[ $option_name ] ) ) {
            return false;
        }
        if ( isset( $qts_options[ $option_name ][ $lang ] ) ) {
            return $qts_options[ $option_name ][ $lang ];
        }

        return false;
    }

    /**
     * Parse and adds $_GET args passed to an URL.
     *
     * @param string $url parameters
     * @param string $lang processed
     *
     * @return string converted URL
     */
    public function parse_url_args( $url ) {
        global $q_config; //TODO: q_config  : url_info, url_mode

        if ( is_admin() ) {
            return $url;
        }
        $url = preg_replace( '/&amp;/', '&', $url );
        // if no permalink structure ads ?lang=en
        $base_query = parse_url( $_SERVER['REQUEST_URI'] );
        // FIXME: why we do this :
        $base_args = isset( $base_query['query'] ) ? wp_parse_args( $base_query['query'] ) : array();

        if ( empty( $this->permalink_structure ) || $q_config['url_mode'] == 1 ) {
            $base_args['lang'] = $this->get_lang();

        }
        // rebuild query with all args
        $url = add_query_arg( $base_args, $url );
        $url = str_replace( '/?', '?', $url ); // TODO: hack: improve this code
        $url = str_replace( '?', '/?', $url ); // TODO: hack: improve this code

        return $url;
    }

    /**
     * Fix get_page_by_path when querying vars.
     *
     * @param $query_vars object query vars founded
     *
     * @return object $query_vars processed
     */
    public function query_vars( $query_vars ) {
        global $wp, $wp_rewrite;

        $wp->query_vars = array();

        // Fetch the rewrite rules.
        $rewrite = $wp_rewrite->wp_rewrite_rules();

        if ( ! empty( $rewrite ) ) {
            // If we match a rewrite rule, this will be cleared.
            $error             = '404';
            $wp->did_permalink = true;

            if ( isset( $_SERVER['PATH_INFO'] ) ) {
                $pathinfo = $_SERVER['PATH_INFO'];
            } else {
                $pathinfo = '';
            }
            $pathinfo_array = explode( '?', $pathinfo );
            $pathinfo       = str_replace( "%", "%25", $pathinfo_array[0] );
            $req_uri        = $_SERVER['REQUEST_URI'];
            $req_uri_array  = explode( '?', $req_uri );
            $req_uri        = $req_uri_array[0];
            $self           = $_SERVER['PHP_SELF'];
            $home_path      = parse_url( home_url() );

            if ( isset( $home_path['path'] ) ) {
                $home_path = $home_path['path'];
            } else {
                $home_path = '';
            }
            $home_path = trim( $home_path, '/' );

            // Trim path info from the end and the leading home path from the
            // front. For path info requests, this leaves us with the requesting
            // filename, if any. For 404 requests, this leaves us with the
            // requested permalink.
            $req_uri = str_replace( $pathinfo, '', $req_uri );
            $req_uri = trim( $req_uri, '/' );
            $req_uri = preg_replace( "|^$home_path|", '', $req_uri );
            $req_uri = trim( $req_uri, '/' );
            if ( $GLOBALS['q_config']['url_mode'] == QTX_URL_PATH ) {
                $req_uri = preg_replace( "/^{$GLOBALS['q_config']['language']}(\/|$)/", '', $req_uri );
            }
            $pathinfo = trim( $pathinfo, '/' );
            $pathinfo = preg_replace( "|^$home_path|", '', $pathinfo );
            $pathinfo = trim( $pathinfo, '/' );
            $self     = trim( $self, '/' );
            $self     = preg_replace( "|^$home_path|", '', $self );
            $self     = trim( $self, '/' );

            // The requested permalink is in $pathinfo for path info requests and
            //  $req_uri for other requests.
            if ( ! empty( $pathinfo ) && ! preg_match( '|^.*' . $wp_rewrite->index . '$|', $pathinfo ) ) {
                $request = $pathinfo;
            } else {
                // If the request uri is the index, blank it out so that
                // we don't try to match it against a rule.
                if ( $req_uri == $wp_rewrite->index ) {
                    $req_uri = '';
                }
                $request = $req_uri;
            }

            $wp->request = $request;

            // Look for matches.
            $request_match = $request;
            if ( empty( $request_match ) ) {
                // An empty request could only match against ^$ regex
                if ( isset( $rewrite['$'] ) ) {
                    $wp->matched_rule = '$';
                    $query            = $rewrite['$'];
                    $matches          = array( '' );
                }
            } else if ( $req_uri != 'wp-app.php' ) {
                foreach ( (array) $rewrite as $match => $query ) {
                    // If the requesting file is the anchor of the match, prepend it to the path info.
                    if ( ! empty( $req_uri ) && strpos( $match, $req_uri ) === 0 && $req_uri != $request ) {
                        $request_match = $req_uri . '/' . $request;
                    }
                    if ( preg_match( "#^$match#", $request_match, $matches ) || preg_match( "#^$match#", urldecode( $request_match ), $matches ) ) {
                        if ( $wp_rewrite->use_verbose_page_rules && preg_match( '/pagename=\$matches\[([0-9]+)\]/', $query, $varmatch ) ) {
                            // this is a verbose page match, lets check to be sure about it
                            if ( ! $page_foundid = $this->get_page_by_path( $matches[ $varmatch[1] ] ) ) {
                                continue;
                            } else {
                                wp_cache_set( 'qts_page_request', $page_foundid ); // caching query :)
                            }
                        }
                        // Got a match.
                        $wp->matched_rule = $match;
                        break;
                    }
                }
            }

            if ( isset( $wp->matched_rule ) ) {
                // Trim the query of everything up to the '?'.
                $query = preg_replace( "!^.+\?!", '', $query );
                // Substitute the substring matches into the query.
                $query             = addslashes( WP_MatchesMapRegex::apply( $query, $matches ) );
                $wp->matched_query = $query;
                // Parse the query.
                parse_str( $query, $perma_query_vars );
                // If we're processing a 404 request, clear the error var
                // since we found something.
                unset( $_GET['error'] );
                unset( $error );
            }

            // If req_uri is empty or if it is a request for ourself, unset error.
            if ( empty( $request ) || $req_uri == $self || strpos( $_SERVER['PHP_SELF'], 'wp-admin/' ) !== false ) {
                unset( $_GET['error'] );
                unset( $error );
                if ( isset( $perma_query_vars ) && strpos( $_SERVER['PHP_SELF'], 'wp-admin/' ) !== false ) {
                    unset( $perma_query_vars );
                }
                $wp->did_permalink = false;
            }
        }

        // TODO check this call, looks bug-prone
        return count( array_diff( $query_vars, $wp->public_query_vars ) ) > 0 ? $query_vars : $wp->public_query_vars;
    }

    /**
     * Function called when query parameters are processed by Wordpress.
     *
     * @param array $query query parameters
     *
     * @return array $query processed
     */
    function filter_request( $query ) {
        global $wp;
        // FIXME: why is this here? it breaks custom variables getter
        // https://wordpress.org/support/topic/cant-retrieve-public-query-variables
        if ( ( isset( $wp->matched_query ) || empty( $query ) ) && ! isset( $query['s'] ) ) {
            $query = wp_parse_args( $wp->matched_query );
        }
        foreach ( $this->get_public_post_types() as $post_type ) {
            if ( array_key_exists( $post_type->name, $query ) && ! in_array( $post_type->name, array(
                    'post',
                    'page'
                ) ) ) {
                $query['post_type'] = $post_type->name;
            }
        }
        // -> page
        if ( isset( $query['pagename'] ) || isset( $query['page_id'] ) ):
            $page = wp_cache_get( 'qts_page_request' );
            if ( ! $page ) {
                $page = isset( $query['page_id'] ) ? get_post( $query['page_id'] ) : $this->get_page_by_path( $query['pagename'] );
            }
            if ( ! $page ) {
                return $query;
            }
            $id          = $page->ID;
            $cache_array = array( $page );
            update_post_caches( $cache_array, 'page' ); // caching query :)
            wp_cache_delete( 'qts_page_request' );
            $query['pagename'] = get_page_uri( $page );
            $function          = 'get_page_link';
        // -> custom post type
        elseif ( isset( $query['post_type'] ) ):
            if ( count( $query ) == 1 ) {
                $function = 'get_post_type_archive_link';
                $id       = $query['post_type'];
            } else {
                $page_slug = ( isset( $query['name'] ) && ! empty( $query['name'] ) ) ? $query['name'] : $query[ $query['post_type'] ];
                $page      = $this->get_page_by_path( $page_slug, OBJECT, $query['post_type'] );
                if ( ! $page ) {
                    return $query;
                }
                $id          = $page->ID;
                $cache_array = array( $page );
                update_post_caches( $cache_array, $query['post_type'] ); // caching query :)
                $query['name'] = $query[ $query['post_type'] ] = get_page_uri( $page );
                $function      = 'get_post_permalink';
            }
        // -> post
        elseif ( isset( $query['name'] ) || isset( $query['p'] ) ):
            $post = isset( $query['p'] ) ? get_post( $query['p'] ) : $this->get_page_by_path( $query['name'], OBJECT, 'post' );
            if ( ! $post ) {
                return $query;
            }
            $query['name'] = $post->post_name;
            $id            = $post->ID;
            $cache_array   = array( $post );
            update_post_caches( $cache_array );
            $function = 'get_permalink';

        // -> category
        elseif ( ( isset( $query['category_name'] ) || isset( $query['cat'] ) ) ):
            if ( isset( $query['category_name'] ) ) {
                $term_slug = $this->get_last_slash( $query['category_name'] );
            }
            $term = isset( $query['cat'] ) ? get_term( $query['cat'], 'category' ) : $this->get_term_by( 'slug', $term_slug, 'category' );
            if ( ! $term ) {
                return $query;
            }
            $cache_array = array( $term );
            update_term_cache( $cache_array, 'category' ); // caching query :)
            $id                     = $term->term_id;
            $query['category_name'] = $term->slug; // uri
            $function               = 'get_category_link';

        // -> tag
        elseif ( isset( $query['tag'] ) ):
            $term = $this->get_term_by( 'slug', $query['tag'], 'post_tag' );
            if ( ! $term ) {
                return $query;
            }
            $cache_array = array( $term );
            update_term_cache( $cache_array, 'post_tag' ); // caching query :)
            $id           = $term->term_id;
            $query['tag'] = $term->slug;
            $function     = 'get_tag_link';

        endif;


        // -> taxonomy
        foreach ( $this->get_public_taxonomies() as $item ):
            if ( isset( $query[ $item->name ] ) ) {
                $term_slug = $this->get_last_slash( $query[ $item->name ] );
                $term      = $this->get_term_by( 'slug', $term_slug, $item->name );
                if ( ! $term ) {
                    return $query;
                }
                $cache_array = array( $term );
                update_term_cache( $cache_array, $item->name ); // caching query :)
                $id                   = $term;
                $query[ $item->name ] = $term->slug;
                $function             = 'get_term_link';

            }
        endforeach;

        // -> home url
        if ( empty( $query ) ) {
            $function = 'home_url';
            $id       = '';
        }

        // -> search
        if ( isset( $query['s'] ) ) {
            $id       = $query['s'];
            $function = "get_search_link";
        }

        if ( isset( $function ) ) {
            // parse all languages links
            foreach ( $this->get_enabled_languages() as $lang ) {

                $this->lang                 = $lang;
                $this->current_url[ $lang ] = esc_url( apply_filters( 'qts_url_args', call_user_func( $function, $id ) ) );
            }
            $this->lang = false;
        }

        return $query;
    }

    /**
     * Parse a hierarquical name and extract the last one
     *
     * @param string $lang Page path
     *
     * @return string
     *
     * @since 1.0
     */
    public function get_current_url( $lang = false ) {

        if ( ! $lang ) {
            $lang = $this->get_lang();
        }

        if ( isset( $this->current_url[ $lang ] ) && ! empty( $this->current_url[ $lang ] ) ) {
            return $this->current_url[ $lang ];
        }

        return '';
    }

    /**
     * Retrieve the home url for a given site.
     *
     * @param int $blog_id (optional) Blog ID. Defaults to current blog.
     * @param string $path (optional) Path relative to the home url.
     * @param string $scheme (optional) Scheme to give the home url context. Currently 'http', 'https'.
     *
     * @return string Home url link with optional path appended.
     */
    public function home_url( $url, $path, $scheme, $blog_id ) {
        if ( ! in_array( $scheme, array( 'http', 'https' ) ) ) {
            $scheme = is_ssl() && ! is_admin() ? 'https' : 'http';
        }

        if ( empty( $blog_id ) || ! is_multisite() ) {
            $url = get_option( 'home' );
        } else {
            $url = get_blog_option( $blog_id, 'home' );
        }

        if ( 'http' != $scheme ) {
            $url = str_replace( 'http://', "$scheme://", $url );
        }

        $ignore_caller = $this->ignore_rewrite_caller();

        if ( ! empty( $path ) && is_string( $path ) && strpos( $path, '..' ) === false ) {
            $url .= '/' . ltrim( $path, '/' );
        }

        if ( ! $ignore_caller ) {
            $url = qtranxf_convertURL( $url, $this->get_lang(), true );
        }

        return $url;
    }

    /**
     * Filter that changes the permastruct depending .. on what?
     *
     * @param string $permastruct default permastruct given b wp_rewrite
     * @param string $name the name of the extra permastruct
     *
     * @return string processed permastruct
     */
    public function get_extra_permastruct( $permastruct = false, $name = false ) {

        if ( ! $name || ! $permastruct ) {
            return '';
        }

        if ( $base = $this->get_base_slug( $name, $this->get_lang() ) ) {
            return "/$base/%$name%";
        }

        return $permastruct;
    }

    // TODO: properly test this

    /**
     * Filter that translates the slug parts in a page link.
     *
     * @param string $link the link for the page generated by Wordpress
     * @param WP_Post|WP_Error $post
     * @param bool $leavename
     * @param bool $sample
     *
     * @return string|WP_Error the link translated
     */
    public function post_type_link( $link, $post, $leavename, $sample ) {
        global $wp_rewrite;

        if ( is_wp_error( $post ) ) {
            return $post;
        }

        $post_link = apply_filters( 'qts_permastruct', $wp_rewrite->get_extra_permastruct( $post->post_type ), $post->post_type );

        $slug = get_post_meta( $post->ID, $this->get_meta_key(), true );
        if ( ! $slug ) {
            $slug = $post->post_name;
        }

        $draft_or_pending = isset( $post->post_status ) && in_array( $post->post_status, array(
                'draft',
                'pending',
                'auto-draft'
            ) );

        $post_type = get_post_type_object( $post->post_type );

        if ( ! empty( $post_link ) && ( ! $draft_or_pending || $sample ) ) {
            if ( ! $leavename ) {
                if ( $post_type->hierarchical ) {
                    $slug = $this->get_page_uri( $post->ID );
                }
                $post_link = str_replace( "%$post->post_type%", $slug, $post_link );
            }

            $post_link = home_url( user_trailingslashit( $post_link ) );

        } else {

            if ( $post_type->query_var && ( isset( $post->post_status ) && ! $draft_or_pending ) ) {
                $post_link = add_query_arg( $post_type->query_var, $slug, '' );
            } else {
                $post_link = add_query_arg( array( 'post_type' => $post->post_type, 'p' => $post->ID ), '' );
            }

            $post_link = home_url( $post_link );
        }

        return $post_link;
    }

    /**
     * Filter that translates the slug in a post link.
     *
     * @param string $link the link generated by wordpress
     * @param WP_Post $post the post data
     * @param bool $leavename parameter used by get_permalink. Whether to keep post name or page name.
     *
     * @return string the link translated
     */
    public function post_link( $link, $post, $leavename ) {
        global $q_config; //TODO: q_config  : url_mode

        $rewritecode = array(
            '%year%',
            '%monthnum%',
            '%day%',
            '%hour%',
            '%minute%',
            '%second%',
            $leavename ? '' : '%postname%',
            '%post_id%',
            '%category%',
            '%author%',
            $leavename ? '' : '%pagename%',
        );

        if ( empty( $post->ID ) ) {
            return false;
        }

        $permalink = $this->permalink_structure;

        if ( '' != $permalink && ! in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft' ) ) ) {
            $unixtime = strtotime( $post->post_date );

            $category = '';
            if ( strpos( $permalink, '%category%' ) !== false ) {
                $cats = get_the_category( $post->ID );
                if ( $cats ) {
                    usort( $cats, '_usort_terms_by_ID' ); // order by ID

                    $category = get_metadata( 'term', $cats[0]->term_id, $this->get_meta_key(), true );
                    if ( ! $category ) {
                        $category = $cats[0]->slug;
                    }

                    if ( $parent = $cats[0]->parent ) {
                        $category = $this->get_category_parents( $parent, false, '/', true ) . $category;
                    }
                }
                // show default category in permalinks, without
                // having to assign it explicitly
                if ( empty( $category ) ) {
                    $default_category = get_category( get_option( 'default_category' ) );

                    $default_category_slug = get_metadata( 'term', $default_category->term_id, $this->get_meta_key(), true );
                    if ( ! $default_category_slug ) {
                        $default_category_slug = $default_category->slug;
                    }

                    $category = is_wp_error( $default_category ) ? '' : $default_category_slug;
                }
            }

            $author = '';
            if ( strpos( $permalink, '%author%' ) !== false ) {
                $authordata = get_userdata( $post->post_author );
                $author     = $authordata->user_nicename;
            }

            $date = explode( " ", date( 'Y m d H i s', $unixtime ) );

            $post_slug = get_post_meta( $post->ID, $this->get_meta_key(), true );
            if ( ! $post_slug ) {
                $post_slug = $post->post_name;
            }

            $rewritereplace =
                array(
                    $date[0],
                    $date[1],
                    $date[2],
                    $date[3],
                    $date[4],
                    $date[5],
                    $post_slug,
                    $post->ID,
                    $category,
                    $author,
                    $post_slug,
                );
            $permalink      = home_url( str_replace( $rewritecode, $rewritereplace, $permalink ) );
            if ( $q_config['url_mode'] != 1 ) {
                $permalink = user_trailingslashit( $permalink, 'single' );
            }
        } else { // if they're not using the fancy permalink option
            $permalink = home_url( '?p=' . $post->ID );
        }

        return $permalink;
    }

    /**
     * Filter that translates the slug parts in a page link.
     *
     * @param string $link the link for the page generated by Wordpress
     * @param int $id the id of the page
     *
     * @return string the link translated
     */
    public function _get_page_link( $link, $id ) {
        global $post, $wp_rewrite, $q_config;  //TODO: q_config  : url_mode

        $current_post = $post;

        if ( ! $id ) {
            $id = (int) $post->ID;
        } else {
            $current_post = get_post( $id );
        }

        $draft_or_pending = in_array( $current_post->post_status, array( 'draft', 'pending', 'auto-draft' ) );

        $link = $wp_rewrite->get_page_permastruct();

        if ( ! empty( $link ) && ( isset( $current_post->post_status ) && ! $draft_or_pending ) ) {

            $link = str_replace( '%pagename%', $this->get_page_uri( $id ), $link );

            $link = trim( $link, '/' ); // hack
            $link = home_url( "/$link/" ); // hack

            if ( $q_config['url_mode'] != 1 ) {
                $link = user_trailingslashit( $link, 'page' );
            }

        } else {

            $link = home_url( "?page_id=$id" );
        }

        return $link;
    }

    /**
     * Filter that translates the slug parts in a term link.
     *
     * @param string $link the link for the page generated by Wordpress
     * @param WP_Term $term
     * @param object $taxonomy
     *
     * @return string the link translated
     */
    //TODO: review this function vs get_term_link(), e.g. checks and error handling may be unneeded here
    public function term_link( $link, $term, $taxonomy ) {
        global $wp_rewrite;

        // parse normal term names for ?tag=tagname
        if ( empty( $this->permalink_structure ) ) {
            return $link;
        }

        if ( ! is_object( $term ) ) {
            if ( is_int( $term ) ) {
                $term = get_term( $term, $taxonomy );
            } else {
                $term = $this->get_term_by( 'slug', $term, $taxonomy );
            }
        }

        if ( ! is_object( $term ) ) {
            $term = new WP_Error( 'invalid_term', __( 'Empty Term' ) );
        }

        if ( is_wp_error( $term ) ) {
            return $term;
        }

        $taxonomy = $term->taxonomy;

        $termlink = apply_filters( 'qts_permastruct', $wp_rewrite->get_extra_permastruct( $taxonomy ), $taxonomy );

        $slug = get_metadata( 'term', $term->term_id, $this->get_meta_key(), true );
        if ( ! $slug ) {
            $slug = $term->slug;
        }

        $t = get_taxonomy( $taxonomy );

        if ( empty( $termlink ) ) {
            if ( 'category' == $taxonomy ) {
                $termlink = '?cat=' . $term->term_id;
            } elseif ( $t->query_var ) {
                $termlink = "?$t->query_var=$slug";
            } else {
                $termlink = "?taxonomy=$taxonomy&term=$slug";
            }
            $termlink = home_url( $termlink );
        } else {
            if ( $t->rewrite['hierarchical'] ) {
                $hierarchical_slugs = array();
                $ancestors          = get_ancestors( $term->term_id, $taxonomy );
                foreach ( (array) $ancestors as $ancestor ) {
                    $ancestor_term = get_term( $ancestor, $taxonomy );

                    $ancestor_slug = get_metadata( 'term', $ancestor_term->term_id, $this->get_meta_key(), true );
                    if ( ! $ancestor_slug ) {
                        $ancestor_slug = $ancestor_term->slug;
                    }

                    $hierarchical_slugs[] = $ancestor_slug;
                }
                $hierarchical_slugs   = array_reverse( $hierarchical_slugs );
                $hierarchical_slugs[] = $slug;
                $termlink             = str_replace( "%$taxonomy%", implode( '/', $hierarchical_slugs ), $termlink );
            } else {
                $termlink = str_replace( "%$taxonomy%", $slug, $termlink );
            }
            $termlink = home_url( user_trailingslashit( $termlink, 'category' ) );
        }

        return $termlink;
    }

    /**
     * Fix for:
     * - Taxonomy names in Taxonomy Manage page
     * - 'Popular Tags' in Taxonomy (Tags) Manage page
     * - Category filter dropdown menu in Post Manage page
     * - Category list in Post Edit page
     * - 'Most Used' tags list in Post Edit page (but have issues when saving)
     *
     * @param (array) $terms
     * @param (string|array) $taxonomy
     */
    function get_terms( $terms, $taxonomy ) {

        global $pagenow;

        if ( $pagenow != 'admin-ajax.php' ) {

            $meta = get_option( 'qtranslate_term_name' );
            $lang = qtranxf_getLanguage();


            if ( ! empty( $terms ) ) {
                foreach ( $terms as $term ) {
                    // after saving, dont do anything
                    if ( ( isset( $_POST['action'] ) && $_POST['action'] == "editedtag" ) ||
                         ! is_object( $term ) ) {
                        return $terms;
                    }
                    if ( isset( $meta[ $term->name ][ $lang ] ) ) {
                        $term->name = $meta[ $term->name ][ $lang ];
                    }
                }
            }
        }

        return $terms;
    }

    /**
     * Fix for:
     * - Taxonomy & custom taxonomy names in Post Manage page
     * - List of tags already added to the post in Post
     * - Edit page (but have issues when saving)
     *
     * @param (array) $terms
     * @param (int|array) $obj_id
     * @param (string|array) $taxonomy
     * @param (array) $taxonomy
     */
    function get_object_terms( $terms, $obj_id, $taxonomy, $args ) {

        global $pagenow;

        // Although in post edit page the tags are translated,
        // but when saving/updating the post Wordpress considers
        // the translated tags as new tags. Due to this
        // issue I limit this 'hack' to the post manage
        // page only.
        if ( $pagenow == 'edit.php' ) {
            $meta = get_option( 'qtranslate_term_name' );
            $lang = qtranxf_getLanguage();

            if ( ! empty( $terms ) ) {
                foreach ( $terms as $term ) {
                    if ( isset( $meta[ $term->name ][ $lang ] ) ) {
                        $term->name = $meta[ $term->name ][ $lang ];
                    }
                }
            }

        }

        return $terms;
    }

    /**
     * Hide quickedit slug.
     */
    public function hide_quick_edit() {
        echo "<!-- QTS remove quick edit box -->" . PHP_EOL;
        echo "<style media=\"screen\">" . PHP_EOL;
        echo "  .inline-edit-row fieldset.inline-edit-col-left .inline-edit-col *:first-child + label { display: none !important }" . PHP_EOL;
        echo "</style>" . PHP_EOL;
    }

    /**
     * Hide auttomatically the wordpress slug box in edit terms page.
     */
    public function hide_slug_box() {
        global $pagenow;
        switch ( $pagenow ):
            case 'edit-tags.php':
                echo "<!-- QTS remove slug box -->" . PHP_EOL;
                echo "<script type=\"text/javascript\" charset=\"utf-8\">" . PHP_EOL;
                echo "  jQuery(document).ready(function($){" . PHP_EOL;
                echo "      $(\"#tag-slug\").parent().hide();" . PHP_EOL;
                echo "      $(\".form-field td #slug\").parent().parent().hide();" . PHP_EOL;
                echo "  });" . PHP_EOL;
                echo "</script>" . PHP_EOL;
                break;
        endswitch;
    }

    /**
     * Creates a metabox for every post type available.
     */
    public function add_slug_meta_box() {
        remove_meta_box( 'slugdiv', null, 'normal' );
        add_meta_box( 'qts_sectionid', __( 'Slugs per language', 'qtranslate' ), array(
            &$this,
            'draw_meta_box'
        ), null, 'side', 'high' );
    }

    /**
     * Shows the fields where insert the translated slugs in the post and page edit form.
     *
     * @param $post (object) current post object
     */
    public function draw_meta_box( $post ) {
        global $q_config; // //TODO: q_config  : language_name

        // Use nonce for verification
        echo "<table style=\"width:100%\">" . PHP_EOL;
        echo "<input type=\"hidden\" name=\"qts_nonce\" id=\"qts_nonce\" value=\"" . wp_create_nonce( 'qts_nonce' ) . "\" />" . PHP_EOL;

        foreach ( $this->enabled_languages as $lang ):

            $slug = get_post_meta( $post->ID, $this->get_meta_key( $lang ), true );

            $value = ( $slug ) ? htmlspecialchars( $slug, ENT_QUOTES ) : '';

            echo "<tr>" . PHP_EOL;
            echo "<th style=\"text-align:left; width:10%; color:#555 \"><label for=\"qts_{$lang}_slug\">" . __( $q_config['language_name'][ $lang ], 'qtranslate' ) . "</label></th>" . PHP_EOL;
            echo "<td><input type=\"text\" id=\"qts_{$lang}_slug\" name=\"qts_{$lang}_slug\" value=\"" . urldecode( $value ) . "\" style=\"width:90%; margin-left:10%; color:#777\" /></td>" . PHP_EOL;
            echo "</tr>" . PHP_EOL;

        endforeach;

        echo '</table>' . PHP_EOL;
    }

    /**
     * Sanitize title as slug, if empty slug.
     *
     * @param $post (object) the post object
     * @param $slug (string) the slug name
     * @param $lang (string) the language
     *
     * @return string the slug validated
     */
    public function validate_post_slug( $slug, $post, $lang ) {

        $post_title = trim( qtranxf_use( $lang, $post->post_title ) );

        $post_name = get_post_meta( $post->ID, $this->get_meta_key( $lang ), true );
        if ( ! $post_name ) {
            $post_name = $post->post_name;
        }

        //TODO: if has a slug, test and use it
        //TODO: and then replace the default slug with the dafault language slug
        $name = ( $post_title == '' || strlen( $post_title ) == 0 ) ? $post_name : $post_title;

        $slug = trim( $slug );

        $slug = ( empty( $slug ) ) ? sanitize_title( $name ) : sanitize_title( $slug );

        return htmlspecialchars( $slug, ENT_QUOTES );
    }

    /**
     * Validates post slug against repetitions per language
     *
     * @param $post (object) the post object
     * @param $slug (string) the slug name
     * @param $lang (string) the language
     *
     * @return string the slug validated
     */
    public function unique_post_slug( $slug, $post, $lang ) {

        $original_status = $post->post_status;

        if ( in_array( $post->post_status, array( 'draft', 'pending' ) ) ) {
            $post->post_status = 'publish';
        }

        $slug = $this->wp_unique_post_slug( $slug, $post->ID, $post->post_status, $post->post_type, $post->post_parent, $lang );

        $post->post_status = $original_status;

        return $slug;
    }

    /**
     * Computes a unique slug for the post and language, when given the desired slug and some post details.
     *
     * @param string $slug the desired slug (post_name)
     * @param integer $post_ID
     * @param string $post_status no uniqueness checks are made if the post is still draft or pending
     * @param string $post_type
     * @param integer $post_parent
     *
     * @return string unique slug for the post, based on language meta_value (with a -1, -2, etc. suffix)
     */
    public function wp_unique_post_slug( $slug, $post_ID, $post_status, $post_type, $post_parent, $lang ) {
        if ( in_array( $post_status, array( 'draft', 'pending', 'auto-draft' ) ) ) {
            return $slug;
        }

        global $wpdb, $wp_rewrite;

        $feeds = $wp_rewrite->feeds;
        if ( ! is_array( $feeds ) ) {
            $feeds = array();
        }

        $meta_key = $this->get_meta_key( $lang );
        if ( 'attachment' == $post_type ) {
            // Attachment slugs must be unique across all types.
            $check_sql       = "SELECT post_name FROM $wpdb->posts WHERE post_name = %s AND ID != %d LIMIT 1";
            $post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $slug, $post_ID ) );

            if ( $post_name_check || in_array( $slug, $feeds ) || apply_filters( 'wp_unique_post_slug_is_bad_attachment_slug', false, $slug ) ) {
                $suffix = 2;
                do {
                    // TODO: update unique_slug :: differs from current wp func ( 4.3.1 )
                    $alt_post_name   = substr( $slug, 0, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
                    $post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $alt_post_name, $post_ID ) );
                    $suffix++;
                } while ( $post_name_check );
                $slug = $alt_post_name;
            }
        } else {
            // TODO: update unique_slug :: missing hieararchical from current wp func ( 4.3.1 )
            // Post slugs must be unique across all posts.
            $check_sql       = "SELECT $wpdb->postmeta.meta_value FROM $wpdb->posts,$wpdb->postmeta WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_key = '%s' AND $wpdb->postmeta.meta_value = '%s' AND $wpdb->posts.post_type = %s AND ID != %d LIMIT 1";
            $post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $meta_key, $slug, $post_type, $post_ID ) );

            // TODO: update unique_slug :: missing check for conflict with dates archive from current wp func ( 4.3.1 )
            if ( $post_name_check || in_array( $slug, $feeds ) || apply_filters( 'wp_unique_post_slug_is_bad_flat_slug', false, $slug, $post_type ) ) {
                $suffix = 2;
                do {
                    // TODO: update unique_slug :: same as above: differs from current wp func ( 4.3.1 )
                    $alt_post_name   = substr( $slug, 0, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
                    $post_name_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $meta_key, $alt_post_name, $post_type, $post_ID ) );
                    $suffix++;
                } while ( $post_name_check );
                $slug = $alt_post_name;
            }
        }

        return $slug;
    }

    /**
     * Saves the translated slug when the page is saved.
     *
     * @param $post_id int the post id
     * @param $post object the post object
     *
     * @return void
     */
    public function save_postdata( $post_id, $post = null ) {
        if ( is_null( $post ) ) {
            $post = get_post( $post_id );
        }
        $post_type_object = get_post_type_object( $post->post_type );

        if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )                       // check autosave
             || ( ! isset( $_POST['post_ID'] ) || $post_id != $_POST['post_ID'] ) // check revision
             || ( isset( $_POST['qts_nonce'] ) && ! wp_verify_nonce( $_POST['qts_nonce'], 'qts_nonce' ) )   // verify nonce
             || ( ! current_user_can( $post_type_object->cap->edit_post, $post_id ) ) ) {  // check permission
            return;
        }
        foreach ( $this->get_enabled_languages() as $lang ) {

            // check required because it is not available inside quick edit
            if ( isset( $_POST["qts_{$lang}_slug"] ) ) {
                $meta_name  = $this->get_meta_key( $lang );
                $meta_value = apply_filters( 'qts_validate_post_slug', $_POST["qts_{$lang}_slug"], $post, $lang );
                delete_post_meta( $post_id, $meta_name );
                update_post_meta( $post_id, $meta_name, $meta_value );
            }
        }
    }

    /**
     * Display multiple input fields, one per language.
     *
     * @param $term string the term object
     */
    public function show_term_fields( $term ) {
        global $q_config; //TODO: q_config  : language_name

        // prints the fields in edit page
        if ( isset( $_GET['action'] ) && $_GET['action'] == 'edit' ):
            echo "<table class=\"form-table\">" . PHP_EOL;
            echo "<input type=\"hidden\" name=\"qts_nonce\" id=\"qts_nonce\" value=\"" . wp_create_nonce( 'qts_nonce' ) . "\" />" . PHP_EOL;

            foreach ( $this->enabled_languages as $lang ) {

                $slug = ( is_object( $term ) ) ? get_metadata( 'term', $term->term_id, $this->get_meta_key( $lang ), true ) : '';

                $value = ( $slug ) ? htmlspecialchars( $slug, ENT_QUOTES ) : '';

                echo "<tr class=\"form-field form-required\">" . PHP_EOL;
                echo "<th scope=\"row\"><label for=\"qts_{$lang}_slug\">" . sprintf( __( 'Slug' ) . ' (%s)', $q_config['language_name'][ $lang ] ) . "</label></th>" . PHP_EOL;
                echo "<td><input type=\"text\" name=\"qts_{$lang}_slug\" value=\"" . urldecode( $value ) . "\" /></td></tr>" . PHP_EOL;

            }

            echo '</table>';

        // prints the fields in new page
        else:
            echo "<input type=\"hidden\" name=\"qts_nonce\" id=\"qts_nonce\" value=\"" . wp_create_nonce( 'qts_nonce' ) . "\" />" . PHP_EOL;
            echo "<div id=\"qts_term_slugs\"><div class=\"qts_term_block\">" . PHP_EOL;
            foreach ( $this->enabled_languages as $lang ) {

                echo "<div class=\"form-field\">" . PHP_EOL;

                $slug = ( is_object( $term ) ) ? get_metadata( 'term', $term->term_id, $this->get_meta_key( $lang ), true ) : '';

                $value = ( $slug ) ? htmlspecialchars( $slug, ENT_QUOTES ) : '';

                echo "<label for=\"qts_{$lang}_slug\">" . sprintf( __( 'Slug' ) . ' (%s)', $q_config['language_name'][ $lang ] ) . "</label>" . PHP_EOL;
                echo "<input type=\"text\" name=\"qts_{$lang}_slug\" value=\"" . urldecode( $value ) . "\" aria-required=\"true\">" . PHP_EOL;
                echo '</div>';
            }
            echo '</div></div>';
        endif;
    }

    /**
     * Sanitize title as slug, if empty slug.
     *
     * @param $term (object) the term object
     * @param $slug (string) the slug name
     * @param $lang (string) the language
     *
     * @return string the slug validated
     */
    public function validate_term_slug( $slug, $term, $lang ) {

        global $q_config; //TODO: q_config  : term_name

        $term_key = qtranxf_split( $term->name );
        // after split we will get array (with language code as a key )

        $term_key = $term_key[ $this->default_language ];

        $name_in_lang = $q_config['term_name'][ $term_key ][ $lang ];

        $ajax_name = 'new' . $term->taxonomy;
        $post_name = isset( $_POST['name'] ) ? $_POST['name'] : '';
        $term_name = isset( $_POST[ $ajax_name ] ) ? trim( $_POST[ $ajax_name ] ) : $post_name;

        if ( empty( $term_name ) ) {
            return $slug;
        }

        $name = ( $name_in_lang == '' || strlen( $name_in_lang ) == 0 ) ? $term_name : $name_in_lang;
        $slug = trim( $slug );
        $slug = ( empty( $slug ) ) ? sanitize_title( $name ) : sanitize_title( $slug );

        return htmlspecialchars( $slug, ENT_QUOTES );
    }

    /**
     * Will make slug unique per language, if it isn't already.
     *
     * @param string $slug The string that will be tried for a unique slug
     * @param object $term The term object that the $slug will belong too
     * @param object $lang The language reference
     *
     * @return string Will return a true unique slug.
     *
     * @since 1.0
     */
    public function unique_term_slug( $slug, $term, $lang ) {
        global $wpdb;

        $meta_key_name = $this->get_meta_key( $lang );
        $query         = $wpdb->prepare( "SELECT term_id FROM $wpdb->termmeta WHERE meta_key = '%s' AND meta_value = '%s' AND term_id != %d ", $meta_key_name, $slug, $term->term_id );
        $exists_slug   = $wpdb->get_results( $query );

        if ( empty( $exists_slug ) ) {
            return $slug;
        }

        // If we didn't get a unique slug, try appending a number to make it unique.
        $query = $wpdb->prepare( "SELECT meta_value FROM $wpdb->termmeta WHERE meta_key = '%s' AND meta_value = '%s' AND term_id != %d", $meta_key_name, $slug, $term->term_id );

        if ( $wpdb->get_var( $query ) ) {
            $num = 2;
            do {
                $alt_slug = $slug . "-$num";
                $num++;
                $slug_check = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT meta_value FROM $wpdb->termmeta WHERE meta_key = '%s' AND meta_value = '%s'",
                        $meta_key_name,
                        $alt_slug ) );
            } while ( $slug_check );
            $slug = $alt_slug;
        }

        return $slug;
    }

    /**
     * Display multiple input fields, one per language.
     *
     * @param $term_id int the term id
     * @param $tt_id int the term taxonomy id
     * @param $taxonomy object the term object
     *
     * @return void
     */
    public function save_term( $term_id, $tt_id, $taxonomy ) {
        $cur_screen = get_current_screen();
        if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )  // check autosave
             || ( ! current_user_can( 'edit_posts' ) ) // check permission
             || ( isset( $cur_screen ) && $cur_screen->id === "nav-menus" ) //TODO: check if this condition is really needed
        ) {
            return;
        }

        $term = get_term( $term_id, $taxonomy );
        foreach ( $this->get_enabled_languages() as $lang ) {
            $meta_name = $this->get_meta_key( $lang );

            //43LC: when at the post edit screen and creating a new tag
            // the $slug comes from $_POST with the value of the post slug,
            // not with the term slug.
            if ( $_POST['action'] == "editpost" ) {
                // so we use the slug wp gave it
                $term_slug = $term->slug;
            } else {
                // otherwise, its the edit term screen
                $term_slug = $_POST["qts_{$lang}_slug"];
            }

            $meta_value = apply_filters( 'qts_validate_term_slug', $term_slug, $term, $lang );

            delete_metadata( 'term', $term_id, $meta_name );
            update_metadata( 'term', $term_id, $meta_name, $meta_value );
        }
    }

    /**
     * Creates and prints the forms and hides the default fields.
     *
     * @param object $term the term object
     * TODO: change Slug column and View link
     * TODO: move code into js file
     */
    public function qts_modify_term_form( $term ) {
        echo "<script type=\"text/javascript\">\n// <![CDATA[\r\n";
        echo "
		var slugforms = jQuery('#qts_term_slugs').html();
		jQuery('#slug').parent().html(slugforms)\n;
		console.log(slugforms);
		
		";
        echo "// ]]>\n</script>\n";
    }

    /**
     * Adds support for qtranslate in taxonomies.
     */
    public function taxonomies_hooks() {

        $taxonomies = $this->get_public_taxonomies();

        if ( $taxonomies ) {
            foreach ( $taxonomies as $taxonomy ) {
                add_action( $taxonomy->name . '_add_form', array( &$this, 'qts_modify_term_form' ) );
                add_action( $taxonomy->name . '_edit_form', array( &$this, 'qts_modify_term_form' ) );
                add_action( $taxonomy->name . '_add_form_fields', array( &$this, 'show_term_fields' ) );
                add_action( $taxonomy->name . '_edit_form_fields', array( &$this, 'show_term_fields' ) );
                add_filter( 'manage_edit-' . $taxonomy->name . '_columns', array( &$this, 'taxonomy_columns' ) );
                add_filter( 'manage_' . $taxonomy->name . '_custom_column', array(
                    &$this,
                    'taxonomy_custom_column'
                ), 0, 3 );
            }
        }
    }

    public function taxonomy_columns( $columns ) {
        unset( $columns['slug'] );
        unset( $columns['posts'] );

        $columns['qts-slug'] = __( 'Slug' );
        $columns['posts']    = __( 'Posts' );

        return $columns;
    }

    public function taxonomy_custom_column( $str, $column_name, $term_id ) {

        switch ( $column_name ) {
            case 'qts-slug':
                echo get_metadata( 'term', $term_id, $this->get_meta_key(), true );
                break;
        }

        return false;
    }

    /**
     * Bug fix for multisite blog names.
     */
    public function blog_names( $blogs ) {

        foreach ( $blogs as $blog ) {
            $blog->blogname = __( $blog->blogname );
        }

        return $blogs;
    }

    /**
     * Helper: returns public taxonomies.
     *
     * @return array of public taxonomies objects
     */
    public function get_public_taxonomies() {
        $all_taxonomies = get_taxonomies( array( 'public' => true, 'show_ui' => true ), 'objects' );
        $taxonomies     = array();

        foreach ( $all_taxonomies as $taxonomy ) {
            if ( $taxonomy->rewrite ) {
                $taxonomies[] = $taxonomy;
            }
        }

        return $taxonomies;
    }

    /**
     * Helper: returns public post_types with rewritable slugs.
     *
     * @return array of public post_types objects
     */
    public function get_public_post_types() {
        $all_post_types = get_post_types( array( 'public' => true ), 'objects' );
        $post_types     = array();

        foreach ( $all_post_types as $post_type ) {
            if ( $post_type->rewrite ) {
                $post_types[] = $post_type;
            }
        }

        return $post_types;
    }

    /**
     * Return the current / temp language.
     */
    //TODO: Check why not using QTX directly
    private function get_lang() {
        //TODO: check, $this->lang is never supposed to be true...
        return ( $this->lang ) ? $this->lang : $this->current_lang;
    }

    /**
     * Return the current / temp language.
     * we store and use it all the way!
     */
    //TODO: Check why not using QTX directly. Also it seems to be unused
    private function get_currentlang() {
        return $this->current_lang;
    }

    /**
     * Return the enabled languages.
     * we store and use it all the way!
     */
    //TODO: Check why not using QTX directly
    private function get_enabled_languages() {
        return $this->enabled_languages;
    }

    /**
     * Activates and do the installation.
     */
    private function activate() {
        $this->set_options();

        // regenerate rewrite rules in db
        add_action( 'generate_rewrite_rules', array( &$this, 'modify_rewrite_rules' ) );
        flush_rewrite_rules();
    }

    /**
     * Helper: news rules to translate the URL bases.
     *
     * @param string $name name of extra permastruct
     */
    private function generate_extra_rules( $name = false ) {
        global $wp_rewrite;

        foreach ( $this->get_enabled_languages() as $lang ):
            if ( $base = $this->get_base_slug( $name, $lang ) ):
                $struct = $wp_rewrite->extra_permastructs[ $name ];
                if ( is_array( $struct ) ) {
                    if ( count( $struct ) == 2 ) {
                        $rules = $wp_rewrite->generate_rewrite_rules( "/$base/%$name%", $struct[1] );
                    } else {
                        $rules = $wp_rewrite->generate_rewrite_rules( "/$base/%$name%", $struct['ep_mask'], $struct['paged'], $struct['feed'], $struct['forcomments'], $struct['walk_dirs'], $struct['endpoints'] );
                    }
                } else {
                    $rules = $wp_rewrite->generate_rewrite_rules( "/$base/%$name%" );
                }
                $wp_rewrite->rules = array_merge( $rules, $wp_rewrite->rules );
            endif;
        endforeach;
    }

    /**
     * Parse a hierarchical name and extract the last one.
     *
     * @param string $slug Page path
     *
     * @return string
     */
    private function get_last_slash( $slug ) {
        $slug          = rawurlencode( urldecode( $slug ) );
        $slug          = str_replace( '%2F', '/', $slug );
        $slug          = str_replace( '%20', ' ', $slug );
        $exploded_slug = explode( '/', $slug );

        return array_pop( $exploded_slug );
    }

    /**
     * Retrieves a page id given its path.
     *
     * @param string $page_path Page path
     * @param string $output Optional. Output type. OBJECT, ARRAY_N, or ARRAY_A. Default OBJECT.
     * @param string $post_type Optional. Post type. Default page.
     *
     * @return mixed Null when complete.
     */
    private function get_page_id_by_path( $page_path, $output = OBJECT, $post_type = 'page' ) {
        global $wpdb;

        $page_path     = rawurlencode( urldecode( $page_path ) );
        $page_path     = str_replace( '%2F', '/', $page_path );
        $page_path     = str_replace( '%20', ' ', $page_path );
        $parts         = explode( '/', trim( $page_path, '/' ) );
        $parts         = array_map( 'esc_sql', $parts );
        $parts         = array_map( 'sanitize_title_for_query', $parts );
        $in_string     = "'" . implode( "','", $parts ) . "'";
        $meta_key      = $this->get_meta_key();
        $post_type_sql = $post_type;
        $wpdb->escape_by_ref( $post_type_sql );

        $pages = $wpdb->get_results( "SELECT $wpdb->posts.ID, $wpdb->posts.post_parent, $wpdb->postmeta.meta_value FROM $wpdb->posts,$wpdb->postmeta WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_key = '$meta_key' AND $wpdb->postmeta.meta_value IN ($in_string) AND ($wpdb->posts.post_type = '$post_type_sql' OR $wpdb->posts.post_type = 'attachment')", OBJECT_K );

        $revparts = array_reverse( $parts );

        $foundid = 0;
        foreach ( (array) $pages as $page ) {
            if ( $page->meta_value == $revparts[0] ) {
                $count = 0;
                $p     = $page;
                while ( $p->post_parent != 0 && isset( $pages[ $p->post_parent ] ) ) {
                    $count++;
                    $parent = $pages[ $p->post_parent ];
                    if ( ! isset( $revparts[ $count ] ) || $parent->meta_value != $revparts[ $count ] ) {
                        break;
                    }
                    $p = $parent;
                }

                if ( $p->post_parent == 0 && $count + 1 == count( $revparts ) && $p->meta_value == $revparts[ $count ] ) {
                    $foundid = $page->ID;
                    break;
                }
            }
        }

        if ( $foundid ) {
            return $foundid;

        } else {
            $last_part = array_pop( $parts );
            $page_id   = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_name = '$last_part' AND (post_type = '$post_type_sql' OR post_type = 'attachment')" );

            if ( $page_id ) {
                return $page_id;
            }
        }

        return null;
    }

    /**
     * Retrieves a page given its path.
     *
     * @param string $page_path Page path
     * @param string $output Optional. Output type. OBJECT, ARRAY_N, or ARRAY_A. Default OBJECT.
     * @param string $post_type Optional. Post type. Default page.
     *
     * @return mixed Null when complete.
     */
    private function get_page_by_path( $page_path, $output = OBJECT, $post_type = 'page' ) {
        $foundid = $this->get_page_id_by_path( $page_path, $output, $post_type );
        if ( $foundid ) {
            return get_post( $foundid, $output );
        }

        return null;
    }

    /**
     * Ignores if the mod_rewrite func is the caller.
     *
     * @return boolean
     */
    private function ignore_rewrite_caller() {
        $backtrace = debug_backtrace();

        $ignore_functions = array(
            'mod_rewrite_rules',
            'save_mod_rewrite_rules',
            'flush_rules',
            'rewrite_rules',
            'wp_rewrite_rules',
            'query_vars'
        );

        if ( isset( $backtrace['function'] ) ) {
            if ( in_array( $backtrace['function'], $ignore_functions ) ) {
                return true;
            }
        } else {
            foreach ( $backtrace as $trace ) {
                if ( isset( $trace['function'] ) && in_array( $trace['function'], $ignore_functions ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Retrieve category parents with separator.
     *
     * @param int $id Category ID.
     * @param bool $link Optional, default is false. Whether to format with link.
     * @param string $separator Optional, default is '/'. How to separate categories.
     * @param bool $nicename Optional, default is false. Whether to use nice name for display.
     * @param array $visited Optional. Already linked to categories to prevent duplicates.
     *
     * @return string
     */
    //TODO: $link seems to be unused (always false), to be removed and function cleaned up
    private function get_category_parents( $id, $link = false, $separator = '/', $nicename = false, $visited = array() ) {
        $chain  = '';
        $parent = get_category( $id );

        if ( is_wp_error( $parent ) ) {
            return $parent;
        }

        if ( $nicename ) {
            $name = get_metadata( 'term', $parent->term_id, $this->get_meta_key(), true );
            if ( ! $name ) {
                $name = $parent->slug;
            }
        } else {
            $name = $parent->name;
        }

        if ( $parent->parent && ( $parent->parent != $parent->term_id ) && ! in_array( $parent->parent, $visited ) ) {
            $visited[] = $parent->parent;
            $chain     .= $this->get_category_parents( $parent->parent, $link, $separator, $nicename, $visited );
        }

        if ( $link ) {
            $chain .= '<a href="' . get_category_link( $parent->term_id ) . '" title="' . esc_attr( sprintf( __( "View all posts in %s", "qts" ), $parent->name ) ) . '">' . $name . '</a>' . $separator;
        } else {
            $chain .= $name . $separator;
        }

        return $chain;
    }

    /**
     * Builds URI for a page.
     *
     * Sub pages will be in the "directory" under the parent page post name.
     *
     * @param mixed $page Page object or page ID.
     *
     * @return string Page URI.
     */
    private function get_page_uri( $page ) {
        if ( ! is_object( $page ) ) {
            $page = get_post( $page );
        }

        $uri = get_post_meta( $page->ID, $this->get_meta_key(), true );
        if ( ! $uri ) {
            $uri = $page->post_name;
        }

        // A page cannot be it's own parent.
        if ( $page->post_parent == $page->ID ) {
            return $uri;
        }

        while ( $page->post_parent != 0 ) {
            $page = get_post( $page->post_parent );

            $page_name = get_post_meta( $page->ID, $this->get_meta_key(), true );
            if ( ! $page_name ) {
                $page_name = $page->post_name;
            }

            $uri = $page_name . "/" . $uri;
        }

        return $uri;
    }

    /**
     * Get all Term data from database by Term field and data.
     *
     * @param (string) $field Either 'slug', 'name', or 'id'
     * @param (string|int) $value Search for this term value
     * @param (string) $taxonomy Taxonomy Name
     * @param (string) $output Constant OBJECT, ARRAY_A, or ARRAY_N
     * @param (string) $filter Optional, default is raw or no WordPress defined filter will applied.
     *
     * @return (mixed) Term Row from database. Will return false if $taxonomy does not exist or $term was not found.
     */
    private function get_term_by( $field, $value, $taxonomy, $output = OBJECT, $filter = 'raw' ) {
        global $wpdb;

        if ( ! taxonomy_exists( $taxonomy ) ) {
            return false;
        }
        $original_field = $field;

        if ( 'slug' == $field ) {
            $field = 'm.meta_key = \'' . $this->get_meta_key() . '\' AND m.meta_value';
            $value = sanitize_title( $value );
            if ( empty( $value ) ) {
                return false;
            }
        } else if ( 'name' == $field ) {
            // Assume already escaped
            $value = stripslashes( $value );
            $field = 't.name';
        } else {
            $term = get_term( (int) $value, $taxonomy, $output, $filter );
            if ( is_wp_error( $term ) ) {
                $term = false;
            }

            return $term;
        }

        $term = $wpdb->get_row( $wpdb->prepare( "SELECT t.*, tt.* FROM $wpdb->terms AS t, $wpdb->term_taxonomy AS tt, $wpdb->termmeta AS m WHERE t.term_id = tt.term_id AND tt.term_id = m.term_id AND tt.taxonomy = %s AND $field = %s LIMIT 1", $taxonomy, $value ) );

        if ( ! $term && 'slug' == $original_field ) {
            $field = 't.slug';
            $term  = $wpdb->get_row( $wpdb->prepare( "SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy = %s AND $field = %s LIMIT 1", $taxonomy, $value ) );
        }

        if ( ! $term ) {
            return false;
        }

        wp_cache_add( $term->term_id, $term, $taxonomy );

        $term = apply_filters( 'get_term', $term, $taxonomy );
        $term = apply_filters( "get_$taxonomy", $term, $taxonomy );
        $term = sanitize_term( $term, $taxonomy, $filter );

        if ( $output == OBJECT ) {
            return $term;
        } elseif ( $output == ARRAY_A ) {
            return get_object_vars( $term );
        } elseif ( $output == ARRAY_N ) {
            return array_values( get_object_vars( $term ) );
        } else {
            return $term;
        }
    }

    /**
     * Helper for qts_modify_term_form_for.
     *
     * @param string $id the term id
     * @param object #term the term
     * @param string $language the term name
     * @param string $action the term name
     *
     * @return string $html the new input fields
     * TODO: use DocumentFragment
     */
    private function qts_insert_term_input( $id, $name, $termname, $language, $action ) {
        global $q_config; //TODO: q_config  : language_name, term_name
        $html = "";
        if ( $action === "new" ) {
            $html = "
	        var il = document.getElementsByTagName('input'),
	        	 d = document.createElement('div'),
                 l = document.createTextNode('" . $name . " (" . $q_config['language_name'][ $language ] . ")'),
	            ll = document.createElement('label'),
	             i = document.createElement('input'),
	           ins = null;
	        for(var j = 0; j < il.length; j++) {
	            if(il[j].id=='" . $id . "') {
	                ins = il[j];
	                break;
	            }
	        }
	        i.type = 'text';
	        i.id = i.name = ll.htmlFor ='qtrans_term_" . $language . "';
	    ";
        } elseif ( $action === "edit" ) {
            $html = "
	        var tr = document.createElement('tr'),
	            th = document.createElement('th'),
	            ll = document.createElement('label'),
	             l = document.createTextNode('" . $name . " (" . $q_config['language_name'][ $language ] . ")'),
	            td = document.createElement('td'),
	             i = document.createElement('input'),
	           ins = document.getElementById('" . $id . "');
	        i.type = 'text';
	        i.id = i.name = ll.htmlFor ='qtrans_term_" . $language . "';
	    ";
        }
        if ( isset( $q_config['term_name'][ $termname ][ $language ] ) ) {
            $html .= "
		     i.value = '" . addslashes( htmlspecialchars_decode( $q_config['term_name'][ $termname ][ $language ], ENT_QUOTES ) ) . "';";
            //43LC: applied ENT_QUOTES to both edit and new forms.
        } else {
            $html .= "
			  if (ins != null)
				  i.value = ins.value;
			  ";
        }

        if ( $language == $this->default_language ) {
            $html .= "
				i.onchange = function() { 
					var il = document.getElementsByTagName('input'),
					   ins = null;
					for(var j = 0; j < il.length; j++) {
						if(il[j].id=='" . $id . "') {
							ins = il[j];
							break;
						}
					}
					if (ins != null)
						ins.value = document.getElementById('qtrans_term_" . $language . "').value;
				};
				";
        }
        if ( $action === "new" ) {
            $html .= "
	        if (ins != null)
	            ins = ins.parentNode;
	        d.className = 'form-field form-required';
	        ll.appendChild(l);
	        d.appendChild(ll);
	        d.appendChild(i);
	        if (ins != null)
	            ins.parentNode.insertBefore(d,ins);
	        ";
        } elseif ( $action === "edit" ) {
            $html .= "
	        ins = ins.parentNode.parentNode;
	        tr.className = 'form-field form-required';
	        th.scope = 'row';
	        th.vAlign = 'top';
	        ll.appendChild(l);
	        th.appendChild(ll);
	        tr.appendChild(th);
	        td.appendChild(i);
	        tr.appendChild(td);
	        ins.parentNode.insertBefore(tr,ins);
	        ";
        }

        return $html;
    }
}
