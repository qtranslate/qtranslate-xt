<?php

/**
 * QtranslateSlug class
 */
class QtranslateSlug {
    /**
     * Stores options slugs from database.
     * @var array
     */
    public $options_buffer;

    /**
     * Stores permalink_structure option, for save queries to db.
     * @var string
     */
    private $permalink_structure;

    /**
     * Variable used to override the language.
     * @var string
     */
    private $temp_lang = false;

    /**
     * Array of translated versions of the current url.
     * @var array
     */
    private $current_url = array();

    /**
     * Initialise the Class with all hooks.
     */
    function init() {
        // TODO: remove `plugins_loaded` hook to initialize the module, it is loaded by QTX handler not WordPress.
        if ( ! QTX_Module_Loader::is_module_active( 'slugs' ) ) {
            return;
        }

        // TODO: remove temporary import of legacy option for master, rely on plugin activation in next release.
        require_once( QTRANSLATE_DIR . '/admin/qtx_admin_options.php' );
        qtranxf_import_legacy_option( 'qts_options', QTX_OPTIONS_MODULE_SLUGS, false );

        $this->options_buffer      = get_option( QTX_OPTIONS_MODULE_SLUGS, array() );
        $this->permalink_structure = get_option( 'permalink_structure' );

        if ( ! is_admin() ) {
            add_filter( 'request', array( &$this, 'filter_request' ) );
        }
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
        global $q_config;
        if ( is_404() ) {
            return;
        }
        //TODO: double check following comment:
        // taken from qtx but see our #341 ticket for clarification
        echo '<link hreflang="x-default" href="' . esc_url( $this->get_current_url( $q_config['default_language'] ) ) . '" rel="alternate" />' . PHP_EOL;
        foreach ( $q_config['enabled_languages'] as $language ) {

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
        $qts_options = $this->options_buffer;
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
            $base_args['lang'] = $this->get_temp_lang();

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
        global $q_config;
        global $wp;

        /* As $wp->matched_query filters the query including slugs mudule custom rewrite rules, it provides all necessary data to reach the intended resource.
         * However discarding completely $query arg and using only $wp->matched_query would result in losing possible custom query vars.
         * Hence $query and $wp->matched_query are merged and unneeded/conflictual keys (e.g. 'error') from $query are removed.
         */

        if ( isset( $wp->matched_query ) ) {
            if ( isset( $query['error'] ) ){
                unset( $query['error'] );
            }
            $query = array_merge( wp_parse_args( $wp->matched_query ), $query );
        }

        // -> home url
        if ( empty( $query ) || isset( $query['error'] ) ):
            $function = 'home_url';
            $id       = '';

        // -> search
        elseif ( isset( $query['s'] ) ):
            $id       = $query['s'];
            $function = "get_search_link";

        // -> page
        elseif ( isset( $query['pagename'] ) || isset( $query['page_id'] ) ):
            /* 'name' key from passed $query var is removed if 'pagename' key is present in $wp->matched_query.
             * Both keys causes conflict between posts and pages as 'name' is used for posts, resulting in 404 error.
             */
            if ( isset( $query['name'] ) ) {
                unset($query['name']);
            }
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

        // -> category
        // If 'name' key is defined, query is relevant to a post with a /%category%/%postname%/ permalink structure and will be captured later.
        elseif ( ( ( isset( $query['category_name'] ) || isset( $query['cat'] ) ) && ! isset($query['name'] ) ) ):
            if ( isset( $query['category_name'] ) ) {
                $term_slug = $this->get_last_slash( empty( $query['category_name'] ) ? $wp->request : $query['category_name'] );
                $term      = $this->get_term_by( 'slug', $term_slug, 'category' );
            } else {
                $term = get_term( $query['cat'], 'category' );
            }

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

        else:

            // If none of the conditions above are matched, specific tests to identify custom post types and taxonomies are performed here.

            // -> custom post type
            foreach ( $this->get_public_post_types() as $post_type ) {
                if ( array_key_exists( $post_type->name, $query ) && ! in_array( $post_type->name, array(
                        'post',
                        'page'
                    ) ) ) {
                    $query['post_type'] = $post_type->name;
                    break;
                }
            }
            if ( isset( $query['post_type'] ) ) {
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
            }

            // -> taxonomy
            foreach ( $this->get_public_taxonomies() as $item ):
                if ( isset( $query[ $item->name ] ) ) {
                    $term_slug = $this->get_last_slash( empty( $query[ $item->name ] ) ? $wp->request : $query[ $item->name ] );
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

            /* As 'name' key is present also at least both for pages and custom post types, this condition alone cannot be used to identify uniquely the posts.
             * For pages and custom post types specific tests can be and are performed earlier but no additional specific condition seems to be applicable for posts.
             * Given the above, the following test needs to be placed after the custom post types one, and is it positive if 'name' key is there and no other match is found earlier.
             * If a specific condition was found to uniquely identify posts, this block should be placed in the main if block.
             */

             // -> post
            if ( ! $function && ( isset($query['name']) || isset($query['p'] ) ) ) {
                $post = isset($query['p']) ? get_post($query['p']) : $this->get_page_by_path($query['name'], OBJECT, 'post');
                if (!$post) {
                    return $query;
                }
                $query['name'] = $post->post_name;
                $id = $post->ID;
                $cache_array = array($post);
                update_post_caches($cache_array);
                $function = 'get_permalink';
            }
        endif;

        if ( isset( $function ) ) {
            // parse all languages links
            foreach ( $q_config['enabled_languages'] as $lang ) {

                $this->temp_lang            = $lang;
                $this->current_url[ $lang ] = esc_url( apply_filters( 'qts_url_args', call_user_func( $function, $id ) ) );
            }
            $this->temp_lang = false;
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
            $lang = $this->get_temp_lang();
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
            $url = qtranxf_convertURL( $url, $this->get_temp_lang(), true );
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

        if ( $base = $this->get_base_slug( $name, $this->get_temp_lang() ) ) {
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

        $slug = get_post_meta( $post->ID, QTS_META_PREFIX . $this->get_temp_lang(), true );
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

                    $category = get_metadata( 'term', $cats[0]->term_id, QTS_META_PREFIX . $this->get_temp_lang(), true );
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

                    $default_category_slug = get_metadata( 'term', $default_category->term_id, QTS_META_PREFIX . $this->get_temp_lang(), true );
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

            $post_slug = get_post_meta( $post->ID, QTS_META_PREFIX . $this->get_temp_lang(), true );
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

        $slug = get_metadata( 'term', $term->term_id, QTS_META_PREFIX . $this->get_temp_lang(), true );
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

                    $ancestor_slug = get_metadata( 'term', $ancestor_term->term_id, QTS_META_PREFIX . $this->get_temp_lang(), true );
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
    private function get_temp_lang() {
        global $q_config;

        return ( $this->temp_lang ) ? $this->temp_lang : $q_config['language'];
    }

    /**
     * Helper: news rules to translate the URL bases.
     *
     * @param string $name name of extra permastruct
     */
    private function generate_extra_rules( $name = false ) {
        global $q_config;
        global $wp_rewrite;

        foreach ( $q_config['enabled_languages'] as $lang ):
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
        $parts         = array_map( function ( $a ) use ( $wpdb ) {
            return sanitize_title_for_query( $wpdb->remove_placeholder_escape( esc_sql( $a ) ) );
        },
            $parts );
        $in_string     = "'" . implode( "','", $parts ) . "'";
        $meta_key      = QTS_META_PREFIX . $this->get_temp_lang();
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
            $name = get_metadata( 'term', $parent->term_id, QTS_META_PREFIX . $this->get_temp_lang(), true );
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

        $uri = get_post_meta( $page->ID, QTS_META_PREFIX . $this->get_temp_lang(), true );
        if ( ! $uri ) {
            $uri = $page->post_name;
        }

        // A page cannot be it's own parent.
        if ( $page->post_parent == $page->ID ) {
            return $uri;
        }

        while ( $page->post_parent != 0 ) {
            $page = get_post( $page->post_parent );

            $page_name = get_post_meta( $page->ID, QTS_META_PREFIX . $this->get_temp_lang(), true );
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
            $field = 'm.meta_key = \'' . QTS_META_PREFIX . $this->get_temp_lang() . '\' AND m.meta_value';
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
}
