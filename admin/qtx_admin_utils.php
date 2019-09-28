<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @since 3.3.8.4
 */
function qtranxf_add_admin_notice( $msg, $kind ) {
	global $q_config;
	if ( isset( $q_config['url_info'][ $kind ] ) ) {
		if ( ! in_array( $msg, $q_config['url_info'][ $kind ] ) ) {
			$q_config['url_info'][ $kind ][] = $msg;
		}
	} else {
		if ( ! isset( $q_config['url_info'] ) ) {
			$q_config['url_info'] = array();
		}
		$q_config['url_info'][ $kind ] = array( $msg );
	}
}

/**
 * @since 3.3.7
 */
function qtranxf_add_error( $msg ) {
	qtranxf_add_admin_notice( $msg, 'errors' );
}

function qtranxf_add_warning( $msg ) {
	qtranxf_add_admin_notice( $msg, 'warnings' );
}

function qtranxf_add_message( $msg ) {
	qtranxf_add_admin_notice( $msg, 'messages' );
}

/**
 * @since 3.3.1
 */
function qtranxf_error_log( $msg ) {
	qtranxf_add_error( $msg );
	error_log( 'qTranslate-X: ' . strip_tags( $msg ) );
}

/**
 * Enqueue Javascript files listed in $jss.
 * @since 3.5.1
 */
function qtranxf_enqueue_scripts( $jss ) {
	$dbg  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
	$cnt  = 0;
	$deps = array();
	foreach ( $jss as $k => $js ) {
		if ( isset( $js['src'] ) ) {
			$handle = isset( $js['handle'] ) ? $js['handle'] : ( is_string( $k ) ? $k : 'qtranslate-admin-js-' . ( ++ $cnt ) );
			$src    = $js['src'];
			$ver    = isset( $js['ver'] ) ? $js['ver'] : QTX_VERSION;
			if ( $dbg ) {
				$src = str_replace( '.min.js', '.js', $src );
				$ver .= '.' . filemtime( WP_CONTENT_DIR . '/' . $src );    // prevent cache issues in debug mode (.js file changes for same version)
			}
			$url = content_url( $src );
			if ( isset( $js['deps'] ) ) {
				$deps = array_merge( $deps, $js['deps'] );
			}
			wp_register_script( $handle, $url, $deps, $ver, true );
			wp_enqueue_script( $handle );
			$deps[] = $handle;
		}
	}
}

function qtranxf_detect_admin_language( $url_info ) {
	global $q_config;
	$cs   = null;
	$lang = null;

	/** @since 3.2.9.9.6
	 * Detect language from $_POST['WPLANG'].
	 */
	if ( isset( $_POST['WPLANG'] ) ) {
		// User is switching the language using "Site Language" field on page /wp-admin/options-general.php
		$wplang = sanitize_text_field( $_POST['WPLANG'] );
		if ( empty( $wplang ) ) {
			$wplang = 'en';
		}
		foreach ( $q_config['enabled_languages'] as $language ) {
			if ( $q_config['locale'][ $language ] != $wplang ) {
				continue;
			}
			$lang = $language;
			break;
		}
		if ( ! $lang ) {
			$lang = substr( $wplang, 0, 2 );
			$lang = qtranxf_resolveLangCase( $lang, $cs );
		}
	}

	if ( ! $lang && isset( $_COOKIE[ QTX_COOKIE_NAME_ADMIN ] ) ) {
		$lang                          = qtranxf_resolveLangCase( $_COOKIE[ QTX_COOKIE_NAME_ADMIN ], $cs );
		$url_info['lang_cookie_admin'] = $lang;
	}

	if ( ! $lang ) {
		$lang = $q_config['default_language'];
	}
	$url_info['doing_front_end'] = false;
	$url_info['lang_admin']      = $lang;

	return $url_info;
}

add_filter( 'qtranslate_detect_admin_language', 'qtranxf_detect_admin_language' );

/**
 * @return bool true if $a and $b are equal.
 */
function qtranxf_array_compare( $a, $b ) {
	if ( ! is_array( $a ) || ! is_array( $b ) ) {
		return false;
	}
	if ( count( $a ) != count( $b ) ) {
		return false;
	}
	foreach ( $a as $k => $v ) {
		if ( ! isset( $b[ $k ] ) ) {
			return false;
		}
		if ( is_array( $v ) ) {
			if ( ! qtranxf_array_compare( $v, $b[ $k ] ) ) {
				return false;
			}
		} else {
			if ( $b[ $k ] !== $v ) {
				return false;
			}
		}
	}

	return true;
}

function qtranxf_join_texts( $texts, $sep ) {
	switch ( $sep ) {
		case 'byline':
			return qtranxf_join_byline( $texts );
		case '{':
			return qtranxf_join_s( $texts );
		default:
			return qtranxf_join_b( $texts );
	}
}

/**
 * @since 3.4.6.9
 */
function qtranxf_clean_request( $nm ) {
	unset( $_GET[ $nm ] );
	unset( $_POST[ $nm ] );
	unset( $_REQUEST[ $nm ] );
}

/**
 * @since 3.4.6.9
 */
function qtranxf_clean_request_of( $type, $nm ) {
	unset( $_GET[ $type ][ $nm ] );
	unset( $_POST[ $type ][ $nm ] );
	unset( $_REQUEST[ $type ][ $nm ] );
	if ( empty( $_GET[ $type ] ) ) {
		unset( $_GET[ $type ] );
	}
	if ( empty( $_POST[ $type ] ) ) {
		unset( $_POST[ $type ] );
	}
	if ( empty( $_REQUEST[ $type ] ) ) {
		unset( $_REQUEST[ $type ] );
	}
}

function qtranxf_ensure_language_set( &$langs, $lang, $default_value = null ) {
	if ( ! empty( $langs[ $lang ] ) ) {
		return $langs[ $lang ];
	}
	if ( ! empty( $default_value ) ) {
		return ( $langs[ $lang ] = $default_value );
	}
	global $q_config;
	foreach ( $q_config['enabled_languages'] as $lng ) {
		if ( empty( $langs[ $lng ] ) ) {
			continue;
		}

		return ( $langs[ $lang ] = $langs[ $lng ] );
	}

	return '';
}

function qtranxf_getLanguageEdit() {
	global $q_config;

	return isset( $_COOKIE['qtrans_edit_language'] ) ? $_COOKIE['qtrans_edit_language'] : $q_config['language'];
}

function qtranxf_languageColumnHeader( $columns ) {
	$new_columns = array();
	if ( isset( $columns['cb'] ) ) {
		$new_columns['cb'] = '';
	}
	if ( isset( $columns['title'] ) ) {
		$new_columns['title'] = '';
	}
	if ( isset( $columns['author'] ) ) {
		$new_columns['author'] = '';
	}
	if ( isset( $columns['categories'] ) ) {
		$new_columns['categories'] = '';
	}
	if ( isset( $columns['tags'] ) ) {
		$new_columns['tags'] = '';
	}
	$new_columns['language'] = __( 'Languages', 'qtranslate' );

	return array_merge( $new_columns, $columns );
}

function qtranxf_languageColumn( $column ) {
	global $q_config, $post;
	if ( $column == 'language' ) {
		$missing_languages   = null;
		$available_languages = qtranxf_getAvailableLanguages( $post->post_content );
		if ( $available_languages === false ) {
			echo _x( 'Languages are not set', 'Appears in the column "Languages" on post listing pages, when content has no language tags yet.', 'qtranslate' );
		} else {
			$missing_languages        = array_diff( $q_config['enabled_languages'], $available_languages );
			$available_languages_name = array();
			$language_names           = null;
			foreach ( $available_languages as $language ) {
				if ( isset( $q_config['language_name'][ $language ] ) ) {
					$language_name = $q_config['language_name'][ $language ];
				} else {
					if ( ! $language_names ) {
						$language_names = qtranxf_default_language_name();
					}
					$language_name = isset( $language_names[ $language ] ) ? $language_names[ $language ] : __( 'Unknown Language', 'qtranslate' );
					$language_name .= ' (' . __( 'Not enabled', 'qtranslate' ) . ')';
				}
				$available_languages_name[] = $language_name;
			}
			$available_languages_names = join( ', ', $available_languages_name );
			echo apply_filters( 'qtranslate_available_languages_names', $available_languages_names );
		}
		do_action( 'qtranslate_languageColumn', $available_languages, $missing_languages );
	}

	return $column;
}

function qtranxf_fetch_file_selection( $dir, $suffix = '.css' ) {
	//qtranxf_dbg_log('qtranxf_fetch_file_selection: dir:',$dir);
	$files      = array();
	$dir_handle = @opendir( $dir );
	if ( ! $dir_handle ) {
		return false;
	}
	while ( false !== ( $file = readdir( $dir_handle ) ) ) {
		if ( ! qtranxf_endsWith( $file, $suffix ) ) {
			continue;
		}
		$nm = basename( $file, $suffix );
		if ( ! $nm ) {
			continue;
		}
		$nm = str_replace( '_', ' ', $nm );
		if ( qtranxf_endsWith( $nm, '.min' ) ) {
			$nm           = substr( $nm, - 4 );
			$files[ $nm ] = $file;
		} elseif ( ! isset( $files[ $nm ] ) ) {
			$files[ $nm ] = $file;
		}
	}
	ksort( $files );

	//qtranxf_dbg_log('qtranxf_fetch_file_selection: files:',$files);
	return $files;
}

/**
 * former qtranxf_fixAdminBar($wp_admin_bar)
 */
function qtranxf_before_admin_bar_render() {
	global $wp_admin_bar, $q_config;
	if ( ! isset( $wp_admin_bar ) ) {
		return;
	}
	$nodes = $wp_admin_bar->get_nodes();
	//qtranxf_dbg_log('qtranxf_before_admin_bar_render: $nodes:', $nodes);
	if ( ! isset( $nodes ) ) {
		return;
	}//sometimes $nodes is NULL
	$lang = $q_config['language'];
	foreach ( $nodes as $node ) {
		$nd = qtranxf_use( $lang, $node );
		$wp_admin_bar->add_node( $nd );
	}
	//qtranxf_dbg_log('qtranxf_before_admin_bar_render: $wp_admin_bar:', $wp_admin_bar);
}

function qtranxf_admin_the_title( $title ) {
	// For nav menus, keep the raw value as the languages are handled client-side (LSB)
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'add-menu-item' ) {
		// When a nav menu is being added it is first handled by AJAX, see "wp_ajax_add_menu_item" in ajax-actions.php
		// For the call to the filter "the_title", see "wp_setup_nav_menu_item" in nav-menus.php
		return $title;
	}
	global $pagenow;
	switch ( $pagenow ) {
		case 'nav-menus.php':
			// When the nav menu is updated, keep the raw value.
			return $title;
		default:
			// For general display purposes only
			return qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $title );
	}
}

add_filter( 'the_title', 'qtranxf_admin_the_title', 0 );

if ( ! function_exists( 'qtranxf_trim_words' ) ) {
	function qtranxf_trim_words( $text, $num_words, $more, $original_text ) {
		//qtranxf_dbg_log('qtranxf_trim_words: $text: ',$text);
		//qtranxf_dbg_log('qtranxf_trim_words: $original_text: ',$original_text);
		$blocks = qtranxf_get_language_blocks( $original_text );
		//qtranxf_dbg_log('qtranxf_trim_words: $blocks: ',$blocks);
		if ( count( $blocks ) <= 1 ) {
			return $text;
		}
		$texts = qtranxf_split_blocks( $blocks );
		foreach ( $texts as $key => $txt ) {
			$texts[ $key ] = wp_trim_words( $txt, $num_words, $more );
		}

		// has to be 'b', because 'c' gets stripped in /wp-admin/includes/nav-menu.php:182: esc_html( $item->description )
		return qtranxf_join_b( $texts );
	}
}

/**
 * The same as core wp_htmledit_pre in /wp-includes/formatting.php,
 * but with last argument of htmlspecialchars $double_encode off,
 * which makes it to survive multiple applications from other plugins,
 * for example, "PS Disable Auto Formatting" (https://wordpress.org/plugins/ps-disable-auto-formatting/)
 * cited on support thread https://wordpress.org/support/topic/incompatibility-with-ps-disable-auto-formatting.
 * @since 2.9.8.9
 */
if ( ! function_exists( 'qtranxf_htmledit_pre' ) ) {
	function qtranxf_htmledit_pre( $output ) {
		if ( ! empty( $output ) ) {
			// convert only < > &
			$output = htmlspecialchars( $output, ENT_NOQUOTES, get_option( 'blog_charset' ), false );
		}

		return apply_filters( 'htmledit_pre', $output );
	}
}

function qtranxf_the_editor( $editor_div ) {
	// remove wpautop, which causes unmatched <p> on combined language strings
	if ( 'html' != wp_default_editor() ) {
		remove_filter( 'the_editor_content', 'wp_richedit_pre' );
		add_filter( 'the_editor_content', 'qtranxf_htmledit_pre', 99 );
	}

	return $editor_div;
}

/* @since 3.3.8.7 use filter 'admin_title' instead
 * function qtranxf_filter_options_general($value){
 * global $q_config;
 * global $pagenow;
 * switch($pagenow){
 * case 'options-general.php':
 * case 'customize.php'://there is more work to do for this case
 * return $value;
 * default: break;
 * }
 * $lang = $q_config['language'];
 * return qtranxf_use_language($lang,$value,false,false);
 * }
 * add_filter('option_blogname', 'qtranxf_filter_options_general');
 * add_filter('option_blogdescription', 'qtranxf_filter_options_general');
 */

function qtranxf_updateGettextDatabases( $force = false, $only_for_language = '' ) {
	require_once( QTRANSLATE_DIR . '/admin/qtx_update_gettext_db.php' );

	return qtranxf_updateGettextDatabasesEx( $force, $only_for_language );
}

function qtranxf_add_conf_filters() {
	global $q_config;
	switch ( $q_config['editor_mode'] ) {
		case QTX_EDITOR_MODE_SINGLGE:
		case QTX_EDITOR_MODE_RAW:
			add_filter( 'gettext', 'qtranxf_gettext', 0 );
			add_filter( 'gettext_with_context', 'qtranxf_gettext_with_context', 0 );
			add_filter( 'ngettext', 'qtranxf_ngettext', 0 );
			break;
		case QTX_EDITOR_MODE_LSB:
		default:
			//applied in /wp-includes/class-wp-editor.php
			add_filter( 'the_editor', 'qtranxf_the_editor' );
			break;
	}
}

function qtranxf_del_conf_filters() {
	remove_filter( 'gettext', 'qtranxf_gettext', 0 );
	remove_filter( 'gettext_with_context', 'qtranxf_gettext_with_context', 0 );
	remove_filter( 'ngettext', 'qtranxf_ngettext', 0 );
	remove_filter( 'the_editor', 'qtranxf_the_editor' );
}

/**
 * Get the currently selected admin color scheme (to be used for generated CSS)
 * @return array
 */
function qtranxf_get_user_admin_color() {
	global $_wp_admin_css_colors;
	$user_id          = get_current_user_id();
	$user_admin_color = get_user_meta( $user_id, 'admin_color', true );
	if ( ! $user_admin_color ) { //ajax calls do not have user authenticated?
		$user_admin_color = 'fresh';
	}

	return $_wp_admin_css_colors[ $user_admin_color ]->colors;
}

function qtranxf_meta_box_LSB() {
	printf( __( 'This is a set of "%s" from %s. Click any blank space between the buttons and drag it to a place where you would need it the most. Click the handle at the top-right corner of this widget to hide this message.', 'qtranslate' ), __( 'Language Switching Buttons', 'qtranslate' ), '<a href="https://github.com/qTranslate/qtranslate-xt/" target="_blank">qTranslate&#8209;XT</a>' );
}

function qtranxf_add_meta_box_LSB( $post_type, $post = null ) {
	global $q_config, $pagenow;
	if ( $q_config['editor_mode'] != QTX_EDITOR_MODE_LSB ) {
		return;
	}
	switch ( $pagenow ) {
		case 'post-new.php':
		case 'post.php':
			break;
		default:
			return;
	}
	if ( empty( $post_type ) ) {
		if ( isset( $post->post_type ) ) {
			$post_type = $post->post_type;
		} else {
			return;
		}
	}
	//qtranxf_dbg_log('qtranxf_add_meta_box_LSB: $post_type: ', $post_type);//, true);
	$page_config = qtranxf_get_admin_page_config_post_type( $post_type );
	if ( empty( $page_config ) ) {
		return;
	}
	// translators: expected in WordPress default textdomain
	add_meta_box( 'qtranxs-meta-box-lsb', qtranxf_translate_wp( 'Language' ), 'qtranxf_meta_box_LSB', $post_type, 'normal', 'low' );
}

add_action( 'add_meta_boxes', 'qtranxf_add_meta_box_LSB', 10, 2 );

/**
 * @return true if post type is listed in option 'Post Types'.
 * @since 3.3
 */
function qtranxf_post_type_optional( $post_type ) {
	switch ( $post_type ) {
		case 'revision':
		case 'nav_menu_item':
			return false; // no option for this type
		default:
			return true;
	}
}

function qtranxf_json_encode( $o ) {
	if ( version_compare( PHP_VERSION, '5.4.0' ) >= 0 ) {
		return json_encode( $o, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	return json_encode( $o );
}

/**
 * @since 3.4
 * return reference to $page_config['forms'][$nm]['fields']
 */
function qtranxf_config_add_form( &$page_config, $nm ) {
	if ( ! isset( $page_config['forms'][ $nm ] ) ) {
		$page_config['forms'][ $nm ] = array( 'fields' => array() );
	} else if ( ! isset( $page_config['forms'][ $nm ]['fields'] ) ) {
		$page_config['forms'][ $nm ]['fields'] = array();
	}
}

/**
 * @param string $nonce_name Name specified when generating the nonce
 * @param string $nonce_field Form input name for the nonce
 *
 * @return boolean             True if the nonce is ok
 * @link https://codex.wordpress.org/Function_Reference/wp_nonce_field#Examples
 *
 * @since 3.4.5
 * check the WP Nonce - OK if POST is empty
 */
function qtranxf_verify_nonce( $nonce_name, $nonce_field = '_wpnonce' ) {
	return empty( $_POST ) || check_admin_referer( $nonce_name, $nonce_field );
}

/**
 * @since 3.4.6.5
 */
function qtranxf_decode_name_value_pair( &$a, $nam, $val ) {
	if ( preg_match( '#([^\[]*)\[([^\]]+)\](.*)#', $nam, $matches ) ) {
		$n = $matches[1];
		$k = $matches[2];
		$s = $matches[3];
		if ( is_numeric( $n ) ) {
			$n = (int) $n;
		}
		if ( is_numeric( $k ) ) {
			$k = (int) $k;
		}
		if ( empty( $a[ $n ] ) ) {
			$a[ $n ] = array();
		}
		if ( empty( $s ) ) {
			$a[ $n ][ $k ] = $val;
		} else {
			qtranxf_decode_name_value_pair( $a[ $n ], $k . $s, $val );
		}
	} else {
		$a[ $nam ] = $val;
	}
}

/**
 * @since 3.4.6.5
 */
function qtranxf_decode_name_value( $data ) {
	$a = array();
	foreach ( $data as $nv ) {
		qtranxf_decode_name_value_pair( $a, $nv->name, wp_slash( $nv->value ) );
	}

	return $a;
}

add_filter( 'manage_posts_columns', 'qtranxf_languageColumnHeader' );
add_filter( 'manage_posts_custom_column', 'qtranxf_languageColumn' );
add_filter( 'manage_pages_columns', 'qtranxf_languageColumnHeader' );
add_filter( 'manage_pages_custom_column', 'qtranxf_languageColumn' );
