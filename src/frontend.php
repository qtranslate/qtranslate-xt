<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function qtranxf_get_front_page_config() {
    static $page_configs;
    if ( $page_configs ) {
        return $page_configs;
    }

    global $q_config;
    $url_path  = $q_config['url_info']['wp-path'];
    $url_query = $q_config['url_info']['query'] ?? '';

    $front_config = $q_config['front_config'];
    /**
     * Customize the front configuration for all pages.
     *
     * @param (array) $front_config token 'front-config' of the configuration.
     */
    $front_config = apply_filters( 'qtranslate_front_config', $front_config );
    $front_config = apply_filters_deprecated( 'i18n_front_config', array( $front_config ), '3.10.0', 'qtranslate_front_config' );

    $page_configs = qtranxf_parse_page_config( $front_config, $url_path, $url_query );

    return $page_configs;
}

function qtranxf_wp_head() {
    global $q_config;

    if ( $q_config['header_css_on'] ) {
        echo '<style>' . PHP_EOL . $q_config['header_css'] . '</style>' . PHP_EOL;
    }

    if ( is_404() ) {
        return;
    }

    // Set links to localized versions of the current page using for SEO (hreflang)
    // See https://github.com/qtranslate/qtranslate-xt/wiki/Browser-redirection#localized-versions-for-seo-hreflang
    $hreflangs = array();
    foreach ( $q_config['enabled_languages'] as $lang ) {
        $hreflang = ! empty ( $q_config['locale_html'][ $lang ] ) ? $q_config['locale_html'][ $lang ] : $lang;

        // The default URL may be deterministic or not, depending on the option for language detection by the browser
        // If language detected, enforce default language shown to make the default URL deterministic for SEO
        // Otherwise, allow option "Hide URL language information for default language"
        $hreflangs[ $hreflang ] = qtranxf_convertURL( '', $lang, false, $q_config['detect_browser_language'] );
    }
    // Fallback for unmatched language (default hreflang for SEO)
    $hreflangs['x-default'] = qtranxf_convertURL( '', $q_config['default_language'] );

    foreach ( $hreflangs as $hreflang => $href ) {
        echo '<link hreflang="' . $hreflang . '" href="' . $href . '" rel="alternate" />' . PHP_EOL;
    }
}

/**
 * Moved line '<meta name="generator"' to a separate action.
 * Developers may use code
 *   remove_action('wp_head','qtranxf_wp_head_meta_generator');
 * to remove this line from the header.
 * @since 3.4.5.4
 */
function qtranxf_wp_head_meta_generator() {
    echo '<meta name="generator" content="qTranslate-XT ' . QTX_VERSION . '" />' . PHP_EOL;
}

function qtranxf_wp_get_nav_menu_items( $items, $menu, $args ) {
    global $q_config;
    $language     = $q_config['language'];
    $itemid       = 0;
    $menu_order   = 0;
    $itemsremoved = array();
    $qtransmenus  = array();
    foreach ( $items as $key => $item ) {
        if ( isset( $item->item_lang ) ) {
            continue;
        }
        if ( isset( $itemsremoved[ $item->menu_item_parent ] ) ) {
            $itemsremoved[ $item->ID ] = $item;
            unset( $items[ $key ] );//remove a child of removed item
            continue;
        }
        $item->item_lang = $language;
        $qtransLangSw    = isset( $item->url ) && stristr( $item->url, 'qtransLangSw' ) !== false;
        if ( ! $qtransLangSw ) {
            $item_title = $item->title;
            if ( ! empty( $item_title ) ) {
                if ( empty( $item->post_title ) && ! qtranxf_isMultilingual( $item_title ) ) {
                    // Item does not have custom menu title.
                    switch ( $item->type ) {
                        case 'post_type':
                            // Fetch information from post_title, but it's already translated with ShowEmpty=false,
                            // which gives either valid translation or possibly something like "(English) English Text".
                            // Translate again and skip menu item if translation does not exist.
                            $post = get_post( $item->object_id );
                            if ( $post ) {
                                $post_title_ml = $post->post_title_ml ?? $post->post_title;
                                $item_title    = qtranxf_use_language( $language, $post_title_ml, false, ! $q_config['show_menu_alternative_language'] );
                            }
                            break;
                        case 'taxonomy':
                            $term = wp_cache_get( $item->object_id, $item->object );
                            if ( $term ) {
                                if ( isset( $q_config['term_name'][ $term->name ][ $language ] ) ) {
                                    $item_title = $q_config['term_name'][ $term->name ][ $language ];
                                } else {
                                    $item_title = '';
                                }
                                if ( ! empty( $term->description ) ) {
                                    $item->description = $term->description;
                                }
                            }
                            break;
                    }
                } else {
                    $item_title = qtranxf_use_language( $language, $item_title, false, ! $q_config['show_menu_alternative_language'] );
                }
            }
            if ( empty( $item_title ) ) {
                $itemsremoved[ $item->ID ] = $item;
                unset( $items[ $key ] );//remove menu item with empty title for this language
                continue;
            }
            $item->title = $item_title;
            if ( $item->object == 'custom' && ! empty( $item->url ) ) {
                if ( strpos( $item->url, 'setlang=no' ) === false ) {
                    $item->url = qtranxf_convertURL( $item->url, $language );
                } else {
                    $item->url = remove_query_arg( 'setlang', $item->url );
                }
                $i = strpos( $item->url, '#?lang=' );
                if ( $i !== false ) {
                    $lang            = substr( $item->url, $i + 7, 2 );
                    $item->url       = qtranxf_convertURL( '', $lang, false, true );
                    $item->item_lang = $lang;
                }
            }
        }

        $item->post_content = qtranxf_use_language( $language, $item->post_content, false, true );
        $item->post_title   = qtranxf_use_language( $language, $item->post_title, false, true );
        $item->post_excerpt = qtranxf_use_language( $language, $item->post_excerpt, false, true );
        $item->description  = qtranxf_use_language( $language, $item->description, false, true );
        if ( isset( $item->attr_title ) ) {
            $item->attr_title = qtranxf_use_language( $language, $item->attr_title, false, true );
        }

        if ( $itemid < $item->ID ) {
            $itemid = $item->ID;
        }
        if ( $menu_order < $item->menu_order ) {
            $menu_order = $item->menu_order;
        }
        if ( ! $qtransLangSw ) {
            continue;
        }
        $qtransmenus[ $key ] = $item;
    }

    if ( ! empty( $itemsremoved ) ) {
        qtranxf_remove_detached_children( $items, $itemsremoved );
    }

    if ( ! empty( $qtransmenus ) ) {
        foreach ( $qtransmenus as $key => $item ) {
            $nlang = count( $items );
            qtranxf_add_language_menu_item( $items, $menu_order, $itemid, $key, $language );
            $nlang       = count( $items ) - $nlang;
            $menu->count += $nlang;
            $menu_order  += $nlang;
        }
    }

    return $items;
}

function qtranxf_add_language_menu_item( &$items, &$menu_order, &$itemid, $key, $language ) {
    global $q_config;
    $item          = $items[ $key ];
    $flag_location = qtranxf_flag_location();
    $altlang       = null;
    $url           = '';
    //options
    $type       = 'LM'; // [LM|AL]
    $title      = 'Language'; // [none|Language|Current]
    $current    = true; // [shown|hidden]
    $flags      = true; // [none|all|items]
    $lang_names = true; // names=[shown|hidden]
    $colon      = true; // [shown|hidden]
    $topflag    = true;

    $p = strpos( $item->url, '?' );
    if ( $p !== false ) {
        $qs   = substr( $item->url, $p + 1 );
        $qs   = str_replace( '#', '', $qs );
        $pars = array();
        parse_str( $qs, $pars );
        if ( isset( $pars['type'] ) && stripos( $pars['type'], 'AL' ) !== false ) {
            $type = 'AL';
        }
        if ( isset( $pars['flags'] ) ) {
            $flags = ( stripos( $pars['flags'], 'no' ) === false );
            if ( $flags ) {
                $topflag = ( stripos( $pars['flags'], 'items' ) === false );
            } else {
                $topflag = false;
            }
        }
        if ( isset( $pars['names'] ) ) {
            $lang_names = ( stripos( $pars['names'], 'hid' ) === false );
        }
        if ( isset( $pars['title'] ) ) {
            $title = $pars['title'];
            if ( stripos( $pars['title'], 'no' ) !== false ) {
                $title = '';
            }
            if ( ! $topflag && empty( $title ) ) {
                $title = 'Language';
            }
        }
        if ( isset( $pars['colon'] ) ) {
            $colon = ( stripos( $pars['colon'], 'hid' ) === false );
        }
        if ( isset( $pars['current'] ) ) {
            $current = ( stripos( $pars['current'], 'hid' ) === false );
        }
        if ( ! $lang_names && ! $flags ) {
            $flags = true;
        }
    }
    $toplang = $language;
    if ( $type == 'AL' ) {
        foreach ( $q_config['enabled_languages'] as $lang ) {
            if ( $lang == $language ) {
                continue;
            }
            $toplang = $lang;
            $altlang = $lang;
            break;
        }
        $item->title = empty( $title ) ? '' : $q_config['language_name'][ $toplang ];
        $item->url   = qtranxf_convertURL( $url, $altlang, false, true );
    } else {
        if ( empty( $title ) ) {
            $item->title = '';
        } elseif ( stripos( $title, 'Current' ) !== false ) {
            $item->title = $q_config['language_name'][ $toplang ];
        } else {
            $blocks = qtranxf_get_language_blocks( $item->title );
            if ( count( $blocks ) <= 1 ) {//no customization is done
                $item->title = qtranxf_translate_wp( 'Language' );    // translators: expected in WordPress default textdomain
            } else {
                $item->title = qtranxf_use_block( $language, $blocks );
            }
        }
        $item->url = '#';
    }
    if ( $topflag ) {
        if ( ! empty( $item->title ) ) {
            if ( $colon ) {
                $item->title = sprintf( __( '%s:', 'qtranslate' ), $item->title );
            }    // translators: Colon after a title. For example, in top item of Language Menu.
            $item->title .= '&nbsp;';
        }
        $item->title .= '<img class="qtranxs-flag" src="' . $flag_location . $q_config['flag'][ $toplang ] . '" alt="' . $q_config['language_name'][ $toplang ] . '" />';//.' '.__('Flag', 'qtranslate')
    }
    if ( empty( $item->attr_title ) ) {
        $item->attr_title = $q_config['language_name'][ $toplang ];
    }
    $item->classes[] = 'qtranxs-lang-menu';
    $item->classes[] = 'qtranxs-lang-menu-' . $toplang;
    $qtransmenu      = $item;

    // find children in case this function was already applied (customize.php on menu change)
    foreach ( $items as $k => $item ) {
        if ( $item->menu_item_parent != $qtransmenu->ID ) {
            continue;
        }
        unset( $items[ $k ] );
    }

    foreach ( $q_config['enabled_languages'] as $lang ) {
        if ( $type == 'AL' ) {
            if ( $lang == $language ) {
                continue;
            }
            if ( $lang == $altlang ) {
                continue;
            }
        } elseif ( ! $current ) {
            if ( $lang == $language ) {
                continue;
            }
        }
        $item = new WP_Post( (object) array( 'ID' => ++$itemid ) );

        // add properties required for nav_menu_item, whose absense causes class-wp-customize-setting.php to throw Exception in function __construct
        $item->target      = '';
        $item->description = '';
        $item->xfn         = '';

        // set properties for language menu item
        $item->menu_item_parent = $qtransmenu->ID;
        $item->menu_order       = ++$menu_order;
        $item->post_type        = 'nav_menu_item';
        $item->object           = 'custom';
        $item->object_id        = $qtransmenu->object_id;
        $item->type             = 'custom';
        $item->type_label       = 'Custom';
        $item->title            = '';
        if ( $flags ) {
            $item->title = '<img class="qtranxs-flag" src="' . $flag_location . $q_config['flag'][ $lang ] . '" alt="' . $q_config['language_name'][ $lang ] . '" />';
        }
        if ( $lang_names ) {
            if ( $flags ) {
                $item->title .= '&nbsp;';
            }
            $item->title .= $q_config['language_name'][ $lang ];
        }
        $item->post_title = $item->title;
        $item->post_name  = 'language-menuitem-' . $lang;
        $item->url        = qtranxf_convertURL( $url, $lang, false, true );
        $item->url        = esc_url( $item->url );//not sure if this is needed
        $item->attr_title = $q_config['language_name'][ $lang ];
        $item->classes    = array();
        $item->classes[]  = 'qtranxs-lang-menu-item';
        $item->classes[]  = 'qtranxs-lang-menu-item-' . $lang;
        //qtx specific properties
        $item->item_lang = $lang; // to store the language assigned
        $items[]         = $item;
    }
}

function qtranxf_remove_detached_children( &$items, &$itemsremoved ) {
    do {
        $more = false;
        foreach ( $items as $key => $item ) {
            if ( $item->menu_item_parent == 0 ) {
                continue;
            }
            if ( ! isset( $itemsremoved[ $item->menu_item_parent ] ) ) {
                continue;
            }
            $itemsremoved[ $item->ID ] = $item;
            unset( $items[ $key ] );
            $more = true;
        }
    } while ( $more );
}

/**
 * @param (mixed) $value to translate, which may be array, object or string
 *                and may have serialized parts with embedded multilingual values.
 *
 * @since 3.3.8.9
 *
 */
function qtranxf_translate_deep( $value, $lang ) {
    if ( is_string( $value ) ) {
        if ( ! qtranxf_isMultilingual( $value ) ) {
            return $value;
        } //most frequent case
        if ( is_serialized( $value ) ) {
            $value = unserialize( $value );
            $value = qtranxf_translate_deep( $value, $lang );//recursive call

            return serialize( $value );
        }
        $lang_value = qtranxf_use_language( $lang, $value );

        return $lang_value;
    } else if ( is_array( $value ) ) {
        foreach ( $value as $k => $v ) {
            $value[ $k ] = qtranxf_translate_deep( $v, $lang );
        }
    } else if ( is_object( $value ) || $value instanceof __PHP_Incomplete_Class ) {
        foreach ( get_object_vars( $value ) as $k => $v ) {
            if ( ! isset( $value->$k ) ) {
                continue;
            }
            $value->$k = qtranxf_translate_deep( $v, $lang );
        }
    }

    return $value;
}

/**
 * @since 3.3.8.9
 * Used to filter option values
 */
function qtranxf_translate_option( $value, $lang = null ) {
    global $q_config;
    if ( ! $lang ) {
        $lang = $q_config['language'];
    }

    return qtranxf_translate_deep( $value, $lang );
}

/**
 * Filter all options for language tags
 */
function qtranxf_filter_options() {
    global $q_config, $wpdb;
    switch ( $q_config['filter_options_mode'] ) {
        case QTX_FILTER_OPTIONS_ALL:
            // Exclude the 'cron' option because the cron jobs can't be deleted after translation.
            $where = ' WHERE autoload=\'yes\' AND option_name != \'cron\' AND (option_value LIKE \'%![:__!]%\' ESCAPE \'!\' OR option_value LIKE \'%{:__}%\' OR option_value LIKE \'%<!--:__-->%\')';
            break;

        case QTX_FILTER_OPTIONS_LIST:
            if ( empty( $q_config['filter_options'] ) ) {
                return;
            }
            $where = ' WHERE FALSE';
            foreach ( $q_config['filter_options'] as $nm ) {
                $where .= ' OR option_name LIKE "' . $nm . '"';
            }
            break;

        default:
            return;
    }
    $result = $wpdb->get_results( 'SELECT option_name FROM ' . $wpdb->options . $where );
    if ( ! $result ) {
        return;
    }
    foreach ( $result as $row ) {
        $option = $row->option_name;
        add_filter( 'option_' . $option, 'qtranxf_translate_option', 5 );
    }
}

/**
 * @since 3.4.7
 */
function qtranxf_translate_object_property( $lang, $txt, $key, $post, $show_available, $show_empty ) {
    $blocks = qtranxf_get_language_blocks( $txt );
    if ( count( $blocks ) <= 1 ) {
        return;  // value is not multilingual
    }
    $key_ml           = $key . '_ml';
    $post->$key_ml    = $txt;
    $langs            = array();
    $content          = qtranxf_split_blocks( $blocks, $langs );
    $post->$key       = qtranxf_use_content( $lang, $content, $langs, $show_available, $show_empty );
    $key_langs        = $key . '_langs';
    $post->$key_langs = $langs;
}

/**
 * @since 3.4.6.5
 */
function qtranxf_translate_post( $post, $lang ) {
    foreach ( get_object_vars( $post ) as $key => $txt ) {
        switch ( $key ) {
            // known to skip
            case 'ID'://int
            case 'post_author':
            case 'post_date':
            case 'post_date_gmt':
            case 'post_status':
            case 'comment_status':
            case 'ping_status':
            case 'post_password':
            case 'post_name': //slug!
            case 'to_ping':
            case 'pinged':
            case 'post_modified':
            case 'post_modified_gmt':
            case 'post_parent': //int
            case 'guid':
            case 'menu_order': //int
            case 'post_type':
            case 'post_mime_type':
            case 'comment_count':
            case 'filter':
                break;
            // known to translate
            case 'post_content':
                qtranxf_translate_object_property( $lang, $txt, $key, $post, true, false );
                break;
            case 'post_excerpt':
            case 'post_content_filtered'://not sure how this is in use
            case 'post_title':
                qtranxf_translate_object_property( $lang, $txt, $key, $post, false, false );
                break;
            // other maybe, if it is a string, most likely it never comes here
            default:
                $post->$key = qtranxf_use( $lang, $txt, false );
        }
    }
}

function qtranxf_postsFilter( $posts, $query ) {
    global $q_config;
    if ( ! is_array( $posts ) ) {
        return $posts;
    }
    switch ( $query->query_vars['post_type'] ) {
        case 'nav_menu_item':
            return $posts;//will translate later in qtranxf_wp_get_nav_menu_items: to be able to filter empty labels.
        default:
            break;
    }
    $lang = $q_config['language'];
    // post is an object derived from WP_Post
    foreach ( $posts as $post ) {
        qtranxf_translate_post( $post, $lang );
    }

    return $posts;
}

/** allow all filters within WP_Query - many other add_filters may not be needed now? */
function qtranxf_pre_get_posts( $query ) {//WP_Query
    if ( isset( $query->query_vars['post_type'] ) ) {
        switch ( $query->query_vars['post_type'] ) {
            case 'nav_menu_item':
                return;
            default:
                break;
        }
    }
    $query->query_vars['suppress_filters'] = false;
}

/**
 * since 3.1-b3 new query to pass empty content and content without closing tags (sliders, galleries and other special kind of posts that never get translated)
 */
function qtranxf_where_clause_translated_posts( $lang, $table_posts ) {
    $post_content = $table_posts . '.post_content';

    return "($post_content='' OR $post_content LIKE '%![:$lang!]%' ESCAPE '!' OR $post_content LIKE '%<!--:$lang-->%' OR ($post_content NOT LIKE '%![:!]%' ESCAPE '!' AND $post_content NOT LIKE '%<!--:-->%'))";
}

function qtranxf_excludePages( $pages ) {
    static $exclude = 0;
    if ( ! is_array( $exclude ) ) {
        global $wpdb;
        $lang       = qtranxf_getLanguage();
        $where      = qtranxf_where_clause_translated_posts( $lang, $wpdb->posts );
        $query      = "SELECT ID FROM $wpdb->posts WHERE post_type = 'page' AND post_status = 'publish' AND NOT " . $where;
        $hide_pages = $wpdb->get_results( $query );
        $exclude    = array();
        foreach ( $hide_pages as $page ) {
            $exclude[] = $page->ID;
        }
    }

    return array_merge( $exclude, $pages );
}

/**
 * @since 3.3.7
 * applied in /wp-includes/link-template.php on line
 *
 * $where = apply_filters( "get_{$adjacent}_post_where", $wpdb->prepare( "WHERE p.post_date $op %s AND p.post_type = %s $where", $current_post_date, $post->post_type ), $in_same_term, $excluded_terms );
 *
 */
function qtranxf_excludeUntranslatedAdjacentPosts( $where ) {
    $lang  = qtranxf_getLanguage();
    $where .= ' AND ' . qtranxf_where_clause_translated_posts( $lang, 'p' );

    return $where;
}

function qtranxf_excludeUntranslatedPosts( $where, $query ) {//WP_Query
    switch ( $query->query_vars['post_type'] ) {
        //known not to filter
        case 'nav_menu_item':
            return $where;
        //known to filter
        case '':
        case 'any':
        case 'page':
        case 'post':
        default:
            break;
    }
    $single_post_query = $query->is_singular();//since 3.1 instead of top is_singular()
    while ( ! $single_post_query ) {
        $single_post_query = preg_match( '/ID\s*=\s*[\'"]*(\d+)[\'"]*/i', $where, $matches ) == 1;
        if ( $single_post_query ) {
            break;
        }
        $single_post_query = preg_match( '/post_name\s*=\s*[^\s]+/i', $where, $matches ) == 1;
        break;
    }

    if ( ! $single_post_query ) {
        global $wpdb;
        $lang  = qtranxf_getLanguage();
        $where .= ' AND ' . qtranxf_where_clause_translated_posts( $lang, $wpdb->posts );
    }

    return $where;
}

function qtranxf_excludeUntranslatedPostComments( $clauses, $q/*WP_Comment_Query*/ ) {
    global $wpdb;


    if ( ! isset( $clauses['join'] ) || empty( $clauses['join'] ) ) {
        $clauses['join'] = "JOIN $wpdb->posts ON $wpdb->posts.ID = $wpdb->comments.comment_post_ID";
    } elseif ( strpos( $clauses['join'], $wpdb->posts ) === false ) {
        //do not break some more complex JOIN if it ever happens
        return $clauses;
    }

    $single_post_query = is_singular();
    if ( $single_post_query && isset( $clauses['where'] ) ) {
        $single_post_query = preg_match( '/comment_post_ID\s*=\s*[\'"]*(\d+)[\'"]*/i', $clauses['where'] ) == 1;
    }
    if ( ! $single_post_query ) {
        $lang             = qtranxf_getLanguage();
        $clauses['where'] .= ' AND ' . qtranxf_where_clause_translated_posts( $lang, $wpdb->posts );
    }

    return $clauses;
}

function qtranxf_get_attachment_image_attributes( $attr, $attachment = null, $size = null ) {
    global $q_config;
    $lang = $q_config['language'];
    //qtranxf_dbg_echo('qtranxf_get_attachment_image_attributes: $attachment:',$attachment);
    if ( isset( $attr['alt'] ) ) {
        $attr['alt'] = qtranxf_use_language( $lang, $attr['alt'], false, false );
    }

    return $attr;
}

function qtranxf_home_url( $url, $path, $orig_scheme, $blog_id ) {
    global $q_config;
    $lang = $q_config['language'];
    $url  = qtranxf_get_url_for_language( $url, $lang, ! $q_config['hide_default_language'] || $lang != $q_config['default_language'] );

    return $url;
}

function qtranxf_esc_html( $text ) {
    //qtranxf_dbg_echo('qtranxf_esc_html:text=',$text,true);
    /**
     * since 3.1-b1
     * used to return qtranxf_useDefaultLanguage($text)
     */
    return qtranxf_useCurrentLanguageIfNotFoundShowEmpty( $text );
}

if ( ! function_exists( 'qtranxf_trim_words' ) ) {
// filter added in qtranslate_hooks.php
    function qtranxf_trim_words( $text, $num_words, $more, $original_text ) {
        global $q_config;
        $blocks = qtranxf_get_language_blocks( $original_text );
        if ( count( $blocks ) <= 1 ) {
            return $text;
        }
        $lang = $q_config['language'];
        $text = qtranxf_use_block( $lang, $blocks, true, false );

        return wp_trim_words( $text, $num_words, $more );
    }
}

/**
 * @since 3.2.9.9.6
 * Delete translated post_meta cache for all languages.
 * Cache may have a few languages, if it is persistent.
 */
function qtranxf_cache_delete_metadata( $meta_type, $object_id ) {//, $meta_key) {
    global $q_config;
    // maybe optimized to only replace the meta_key needed ?
    foreach ( $q_config['enabled_languages'] as $lang ) {
        $cache_key_lang = $meta_type . '_meta' . $lang;
        wp_cache_delete( $object_id, $cache_key_lang );
    }
}

/**
 * @since 3.2.3 translation of meta data
 * @since 3.4.6.4 improved caching algorithm
 */
function qtranxf_translate_metadata( $meta_type, $original_value, $object_id, $meta_key = '', $single = false ) {
    global $q_config;
    static $meta_cache_unserialized = array();
    if ( ! isset( $q_config['url_info'] ) ) {
        return $original_value;
    }

    $lang           = $q_config['language'];
    $cache_key      = $meta_type . '_meta';
    $cache_key_lang = $cache_key . $lang;

    $meta_cache_wp = wp_cache_get( $object_id, $cache_key );
    if ( $meta_cache_wp ) {
        // if there is wp cache, then we check if there is qtx cache
        $meta_cache = wp_cache_get( $object_id, $cache_key_lang );
    } else {
        // reset qtx cache, since it would not be valid in the absence of wp cache
        qtranxf_cache_delete_metadata( $meta_type, $object_id );
        $meta_cache = null;
    }

    if ( ! isset( $meta_cache_unserialized[ $meta_type ] ) ) {
        $meta_cache_unserialized[ $meta_type ] = array();
    }
    if ( ! isset( $meta_cache_unserialized[ $meta_type ][ $object_id ] ) ) {
        $meta_cache_unserialized[ $meta_type ][ $object_id ] = array();
    }
    $meta_unserialized = &$meta_cache_unserialized[ $meta_type ][ $object_id ];

    if ( ! $meta_cache ) {
        if ( $meta_cache_wp ) {
            $meta_cache = $meta_cache_wp;
        } else {
            $meta_cache = update_meta_cache( $meta_type, array( $object_id ) );
            $meta_cache = $meta_cache[ $object_id ];
        }
        $meta_unserialized = array();//clear this cache if we are re-doing meta_cache
        foreach ( $meta_cache as $mkey => $mval ) {
            $meta_unserialized[ $mkey ] = array();
            if ( strpos( $mkey, '_url' ) !== false ) {
                switch ( $mkey ) {
                    case '_menu_item_url':
                        break; // function qtranxf_wp_get_nav_menu_items takes care of this later
                    default:
                        foreach ( $mval as $k => $v ) {
                            $s = is_serialized( $v );
                            if ( $s ) {
                                $v = unserialize( $v );
                            }
                            $v                                = qtranxf_convertURLs( $v, $lang );
                            $meta_unserialized[ $mkey ][ $k ] = $v;
                            if ( $s ) {
                                $v = serialize( $v );
                            }
                            $meta_cache[ $mkey ][ $k ] = $v;
                        }
                        break;
                }
            } else {
                foreach ( $mval as $k => $v ) {
                    if ( ! qtranxf_isMultilingual( $v ) ) {
                        continue;
                    }
                    $s = is_serialized( $v );
                    if ( $s ) {
                        $v = unserialize( $v );
                    }
                    $v                                = qtranxf_use( $lang, $v, false, false );
                    $meta_unserialized[ $mkey ][ $k ] = $v;
                    if ( $s ) {
                        $v = serialize( $v );
                    }
                    $meta_cache[ $mkey ][ $k ] = $v;
                }
            }
        }
        wp_cache_set( $object_id, $meta_cache, $cache_key_lang );
    }

    if ( ! $meta_key ) {
        if ( $single ) {
            return array( $meta_cache );
        }

        return $meta_cache;
    }

    if ( isset( $meta_cache[ $meta_key ] ) ) {
        // cache unserialized values, just for the sake of performance.
        $meta_key_unserialized = &$meta_unserialized[ $meta_key ];
        if ( $single ) {
            if ( ! isset( $meta_key_unserialized[0] ) ) {
                $meta_key_unserialized[0] = maybe_unserialize( $meta_cache[ $meta_key ][0] );
            }
        } else {
            foreach ( $meta_cache[ $meta_key ] as $k => $v ) {
                if ( ! isset( $meta_key_unserialized[ $k ] ) ) {
                    $meta_key_unserialized[ $k ] = maybe_unserialize( $meta_cache[ $meta_key ][ $k ] );
                }
            }
        }

        return $meta_key_unserialized;
    }

    if ( $single ) {
        return '';
    } else {
        return array();
    }
}

/**
 * @since 3.2.3 translation of postmeta
 */
function qtranxf_filter_postmeta( $original_value, $object_id, $meta_key = '', $single = false ) {
    return qtranxf_translate_metadata( 'post', $original_value, $object_id, $meta_key, $single );
}

/**
 * @since 3.2.9.9.6
 * Delete translated post_meta cache for all languages on cache update.
 * Cache may have a few languages, if it is persistent.
 */
function qtranxf_updated_postmeta( $meta_id, $object_id, $meta_key, $meta_value ) {
    qtranxf_cache_delete_metadata( 'post', $object_id );
}


/**
 * @since 3.4 translation of usermeta
 */
function qtranxf_filter_usermeta( $original_value, $object_id, $meta_key = '', $single = false ) {
    return qtranxf_translate_metadata( 'user', $original_value, $object_id, $meta_key, $single );
}

/**
 * @since 3.4
 * Delete translated user_meta cache for all languages on cache update.
 * Cache may have a few languages, if it is persistent.
 */
function qtranxf_updated_usermeta( $meta_id, $object_id, $meta_key, $meta_value ) {
    qtranxf_cache_delete_metadata( 'user', $object_id );
}

function qtranxf_checkCanonical( $redirect_url, $requested_url ) {
    global $q_config;
    $lang = $q_config['language'];
    // fix canonical conflicts with language urls
    $redirect_url_lang = qtranxf_convertURL( $redirect_url, $lang );

    return $redirect_url_lang;
}

/**
 * @since 3.2.8 moved here from _hooks.php
 */
function qtranxf_convertBlogInfoURL( $url, $what ) {
    switch ( $what ) {
        case 'stylesheet_url':
        case 'template_url':
        case 'template_directory':
        case 'stylesheet_directory':
            return $url;
        default:
            return qtranxf_convertURL( $url );
    }
}

/**
 * @since 3.3.1
 * Moved here from qtranslate_hooks.php and modified.
 */
function qtranxf_pagenum_link( $url ) {
    $lang_code = QTX_LANG_CODE_FORMAT;
    $url_fixed = preg_replace( "#\?lang=$lang_code/#i", '/', $url ); //kind of ugly fix for function get_pagenum_link in /wp-includes/link-template.php. Maybe we should cancel filter 'bloginfo_url' instead?

    return qtranxf_convertURL( $url_fixed );
}

/**
 * @since 3.3.7
 */
function qtranxf_add_front_filters() {
    global $q_config;

    add_action( 'wp_head', 'qtranxf_wp_head' );
    add_action( 'wp_head', 'qtranxf_wp_head_meta_generator' );
    add_filter( 'wp_get_nav_menu_items', 'qtranxf_wp_get_nav_menu_items', 20, 3 );
    add_filter( 'wp_get_attachment_image_attributes', 'qtranxf_get_attachment_image_attributes', 5, 3 );
    add_filter( 'esc_html', 'qtranxf_esc_html', 0 );
    add_filter( 'the_posts', 'qtranxf_postsFilter', 5, 2 );
    add_action( 'pre_get_posts', 'qtranxf_pre_get_posts', 99 );
    add_filter( 'get_post_metadata', 'qtranxf_filter_postmeta', 5, 4 );
    add_action( 'updated_postmeta', 'qtranxf_updated_postmeta', 5, 4 );
    add_filter( 'get_user_metadata', 'qtranxf_filter_usermeta', 5, 4 );
    add_action( 'updated_usermeta', 'qtranxf_updated_usermeta', 5, 4 );
    add_filter( 'redirect_canonical', 'qtranxf_checkCanonical', 10, 2 );
    add_filter( 'get_pagenum_link', 'qtranxf_pagenum_link' );

    // Time critical filters, not needed on admin side.
    // In particular, they break WPBakery Visual Composer in raw Editor Mode.
    add_filter( 'gettext', 'qtranxf_gettext', 0 );
    add_filter( 'gettext_with_context', 'qtranxf_gettext_with_context', 0 );
    add_filter( 'ngettext', 'qtranxf_ngettext', 0 );

    if ( $q_config['hide_untranslated'] ) {
        add_filter( 'wp_list_pages_excludes', 'qtranxf_excludePages' );//moved here from _hooks.php since 3.2.8
        add_filter( 'posts_where_request', 'qtranxf_excludeUntranslatedPosts', 10, 2 );
        add_filter( 'comments_clauses', 'qtranxf_excludeUntranslatedPostComments', 10, 2 );
        add_filter( 'get_previous_post_where', 'qtranxf_excludeUntranslatedAdjacentPosts' );
        add_filter( 'get_next_post_where', 'qtranxf_excludeUntranslatedAdjacentPosts' );
    }

    foreach ( $q_config['text_field_filters'] as $nm ) {
        add_filter( $nm, 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage' );
    }

    $page_configs = qtranxf_get_front_page_config();
    if ( ! empty( $page_configs['']['filters'] ) ) {
        qtranxf_add_filters( $page_configs['']['filters'] );
    }

    if ( $q_config['url_mode'] != QTX_URL_QUERY ) {
        /* WP uses line like 'trailingslashit( get_bloginfo( 'url' ) )' in /wp-includes/link-template.php, for example, which obviously breaks the further processing in QTX_URL_QUERY mode.
        */
        add_filter( 'bloginfo_url', 'qtranxf_convertBlogInfoURL', 10, 2 );
        add_filter( 'home_url', 'qtranxf_home_url', 0, 4 );
    }

    qtranxf_filter_options();
}
