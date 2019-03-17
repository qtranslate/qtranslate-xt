<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( QTRANSLATE_DIR . '/admin/qtx_admin_options.php' );
require_once( QTRANSLATE_DIR . '/admin/qtx_languages.php' );
require_once( QTRANSLATE_DIR . '/admin/qtx_admin_class_translator.php' );
require_once( QTRANSLATE_DIR . '/admin/qtx_user_options.php' );
require_once( QTRANSLATE_DIR . '/admin/qtx_admin_taxonomy.php' );

/** see help notes for function 'qtranxf_collect_translations'
 */
function qtranxf_collect_translations_deep( $qfields, $sep ) {
	$content = reset( $qfields );
	//qtranxf_dbg_log('qtranxf_collect_translations_deep: $content: ',$content);
	if ( is_string( $content ) ) {
		return qtranxf_join_texts( $qfields, $sep );
	}
	$result = array();
	foreach ( $content as $f => $r ) {
		$texts = array();
		foreach ( $qfields as $lang => &$vals ) {
			$texts[ $lang ] = $vals[ $f ];
		}
		$result[ $f ] = qtranxf_collect_translations_deep( $texts, $sep ); // recursive call
	}

	return $result;
}

/**
 * Collect translations of all ML fileds posted in $_REQUEST into Raw ML values.
 * Called in response to action 'plugins_loaded'.
 * All data is yet unslashed when action 'plugins_loaded' is executed.
 *
 * @param array $qfields a sub-tree of $_REQUEST['qtranslate-fields'], which contains translations for field $request.
 * @param array|string $request an ML field of $_REQUEST.
 * @param string $edit_lang language of the active LSB at the time of sending the request.
 *
 * @return void
 */
function qtranxf_collect_translations( &$qfields, &$request, $edit_lang ) {
	if ( isset( $qfields['qtranslate-separator'] ) ) {
		$sep = $qfields['qtranslate-separator'];
		unset( $qfields['qtranslate-separator'] );
		if ( ! qtranxf_isMultilingual( $request ) ) {
			//convert $request to an ML value
			$qfields[ $edit_lang ] = $request;
			$request               = qtranxf_collect_translations_deep( $qfields, $sep );
		} else {
			//raw mode, or user mistakenly put ML value into an LSB-controlled field
			//leave $request as user entered it
			//$qfields = qtranxf_split($request);
			return;
		}
	} else {
		foreach ( $qfields as $nm => &$vals ) {
			if ( ! isset( $request[ $nm ] ) ) {
				unset( $qfields[ $nm ] );
				continue;
			}
			qtranxf_collect_translations( $vals, $request[ $nm ], $edit_lang ); // recursive call
		}
	}
}

/**
 * @since 3.4.6.5
 */
function qtranxf_decode_json_name_value( $val ) {
	if ( strpos( $val, 'qtranslate-fields' ) === false ) {
		return;
	}
	$nv = json_decode( stripslashes( $val ) );
	if ( is_null( $nv ) ) {
		return;
	}

	return qtranxf_decode_name_value( $nv );
}

/** see help notes for function 'qtranxf_collect_translations'
 */
function qtranxf_collect_translations_posted() {
	//qtranxf_dbg_log('qtranxf_collect_translations_posted: REQUEST: ', $_REQUEST);
	//qtranxf_dbg_log('qtranxf_collect_translations_posted: POST: ', $_POST);
	//qtranxf_dbg_log('qtranxf_collect_translations_posted: count(REQUEST): ', count($_REQUEST, COUNT_RECURSIVE));
	$edit_lang = null;
	if ( isset( $_REQUEST['qtranslate-fields'] ) ) {
		//$edit_lang = isset($_COOKIE['qtrans_edit_language']) ? $_COOKIE['qtrans_edit_language'] : qtranxf_getLanguage();
		$edit_lang = qtranxf_getLanguageEdit();
		foreach ( $_REQUEST['qtranslate-fields'] as $nm => &$qfields ) {
			//qtranxf_dbg_log('qtranxf_collect_translations_posted: REQUEST[qtranslate-fields]['.$nm.']: ',$qfields);
			if ( ! isset( $_REQUEST[ $nm ] ) ) {
				unset( $_REQUEST['qtranslate-fields'][ $nm ] );
				continue;
			}
			qtranxf_collect_translations( $qfields, $_REQUEST[ $nm ], $edit_lang );
			//qtranxf_dbg_log('qtranxf_collect_translations_posted: collected REQUEST['.$nm.']: ',$_REQUEST[$nm]);
			if ( isset( $_POST[ $nm ] ) ) {
				$_POST[ $nm ] = $_REQUEST[ $nm ];
			}
			if ( isset( $_GET[ $nm ] ) ) {
				$_GET[ $nm ] = $_REQUEST[ $nm ];
			}
		}
		qtranxf_clean_request( 'qtranslate-fields' );
	}

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		//parse variables collected as a query string in an option
		foreach ( $_REQUEST as $nm => $val ) {
			if ( ! is_string( $val ) ) {
				continue;
			}
			if ( strpos( $val, 'qtranslate-fields' ) === false ) {
				continue;
			}
			parse_str( $val, $r );
			//qtranxf_dbg_log('qtranxf_collect_translations_posted: REQUEST['.$nm.'] $r: ', $r);
			//qtranxf_dbg_log('qtranxf_collect_translations_posted: REQUEST['.$nm.']: ', $val);
			if ( empty( $r['qtranslate-fields'] ) ) {
				continue;
			}
			if ( ! $edit_lang ) {
				$edit_lang = qtranxf_getLanguageEdit();
			}
			qtranxf_collect_translations( $r['qtranslate-fields'], $r, $edit_lang );
			unset( $r['qtranslate-fields'] );
			//qtranxf_dbg_log('qtranxf_collect_translations_posted: $r parsed: ', $r);
			$q = http_build_query( $r );
			//qtranxf_dbg_log('qtranxf_collect_translations_posted: $q: ', $q);
			//qtranxf_dbg_log('qtranxf_collect_translations_posted: $v: ', $val);
			$_REQUEST[ $nm ] = $q;
			if ( isset( $_POST[ $nm ] ) ) {
				$_POST[ $nm ] = $q;
			}
			if ( isset( $_GET[ $nm ] ) ) {
				$_GET [ $nm ] = $q;
			}
		}
	}
}

add_action( 'plugins_loaded', 'qtranxf_collect_translations_posted', 5 );

function qtranxf_decode_translations_posted() {
	//quick fix, there must be a better way
	if ( isset( $_POST['nav-menu-data'] ) ) {
		$r = qtranxf_decode_json_name_value( $_POST['nav-menu-data'] );
		//qtranxf_dbg_log('qtranxf_collect_translations_posted: $r: ', $r);
		if ( ! empty( $r['qtranslate-fields'] ) ) {
			$edit_lang = qtranxf_getLanguageEdit();
			qtranxf_collect_translations( $r['qtranslate-fields'], $r, $edit_lang );
			unset( $r['qtranslate-fields'] );
			//qtranxf_dbg_log('qtranxf_collect_translations_posted: collected $r: ', $r);
			foreach ( $r as $k => $v ) {
				$_POST[ $k ] = $v;
			}
			unset( $_POST['nav-menu-data'] );
			//qtranxf_dbg_log('qtranxf_collect_translations_posted: nav-menu-data decoded $_POST: ', $_POST);
		}
	}
}

add_action( 'sanitize_comment_cookies', 'qtranxf_decode_translations_posted', 5 );//after POST & GET are set, and before all WP objects are created, alternatively can use action 'setup_theme' instead.

/**
 * Check if a plugin is active and if no legacy plugin prevents its integration.

 * @param string $plugin plugin file to be checked for integration
 * @param string $legacy_plugin legacy plugin that is incompatible and must be deactivated for module integration
 *
 * @return bool
 */
function qtranxf_admin_check_plugin( $plugin, $legacy_plugin ) {
	if ( is_plugin_active( $plugin ) ) {
		if ( isset($legacy_plugin) && is_plugin_active( $legacy_plugin ) ) {
			deactivate_plugins( $legacy_plugin );
			add_action( 'admin_notices', function () use ($legacy_plugin) {
                $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $legacy_plugin, false, true );
                $plugin_name = $plugin_data['Name'];
				if ( is_plugin_active( $legacy_plugin ) ) :
					?>
                    <div class="notice notice-error is-dismissible">
                        <p><?php printf( __( '[%s] Incompatible plugin detected: "%s". Please disable it.', 'qtranslate' ), 'qTranslate&#8209;XT', $plugin_name ); ?></p>
                        <p><a class="button"
                              href="<?php echo esc_url( wp_nonce_url( admin_url( 'plugins.php?action=deactivate&plugin=' . urlencode( $legacy_plugin ) ), 'deactivate-plugin_' . $legacy_plugin ) ) ?>"><strong><?php printf( __( 'Deactivate plugin %s', 'qtranslate' ), $plugin_name ) ?></strong></a>
                    </div>
				<?php
                else:
	                ?>
                    <div class="notice notice-warning is-dismissible">
                        <p><?php printf( __( '[%s] Incompatible plugin detected: "%s". This plugin has been deactivated.', 'qtranslate' ), 'qTranslate&#8209;XT', $plugin_name ); ?></p>
                    </div>
                <?php
				endif;
			} );
			return false;
		} else {
			return true;
		}
	}
	return false;
}

/**
 * Validate the list of modules to be loaded for plugin integration on server side.
 * Note each module can enable hooks both for admin and front requests.
 * The valid modules are stored in the 'qtranslate_modules' option.
 */
function qtranxf_admin_validate_integration_modules() {
	require_once(ABSPATH . 'wp-admin/includes/plugin.php');

	$modules = array();
	if (qtranxf_admin_check_plugin('woocommerce/woocommerce.php', 'woocommerce-qtranslate-x/woocommerce-qtranslate-x.php')) {
	    $modules[] = 'woo-commerce';
	}
	if (qtranxf_admin_check_plugin('gravityforms/gravityforms.php', 'qtranslate-support-for-gravityforms/qtranslate-support-for-gravityforms.php')) {
	    $modules[] = 'gravity-forms';
	}
	if (qtranxf_admin_check_plugin('wordpress-seo/wp-seo.php', 'wp-seo-qtranslate-x/wordpress-seo-qtranslate-x.php')) {
	    $modules[] = 'wp-seo';
	}
	update_option( 'qtranslate_modules', $modules);
}

function qtranxf_admin_load() {
	//qtranxf_dbg_log('1.4.qtranxf_admin_load:');
	qtranxf_admin_loadConfig();
	$bnm = qtranxf_plugin_basename();
	add_filter( 'plugin_action_links_' . $bnm, 'qtranxf_links', 10, 4 );
	add_action( 'qtranslate_init_language', 'qtranxf_load_admin_page_config', 20 );//should be excuted after all plugins loaded their *-admin.php
	qtranxf_add_admin_filters();
	qtranxf_admin_validate_integration_modules();
}

qtranxf_admin_load();

function qtranxf_load_admin_page_config() {
	//qtranxf_dbg_log('1.8.qtranxf_load_admin_page_config:');
	$page_configs = qtranxf_get_admin_page_config();
	if ( ! empty( $page_configs['']['filters'] ) ) {
		qtranxf_add_filters( $page_configs['']['filters'] );
	}
}

/**
 * @since 3.4.7
 * @return bool true is we are on qtx configuration page.
 */
function qtranxf_admin_is_config_page() {
	static $is_config_page;
	if ( ! $is_config_page ) {
		global $q_config, $pagenow;
		$is_config_page = $pagenow == 'options-general.php'
		                  && isset( $q_config['url_info']['query'] )
		                  && strpos( $q_config['url_info']['query'], 'page=qtranslate-xt' ) !== false;
	}

	return $is_config_page;
}

function qtranxf_admin_init() {
	global $q_config, $pagenow;
	//qtranxf_dbg_log('5.qtranxf_admin_init:');

	if ( current_user_can( 'manage_options' ) ) {
		add_action( 'admin_notices', 'qtranxf_admin_notices_config' );
	}

	if ( current_user_can( 'manage_options' ) && qtranxf_admin_is_config_page()
		//&& !empty($_POST) //todo run this only if one of the forms or actions submitted
	) {
		$q_config['url_info']['qtranslate-settings-url'] = admin_url( 'options-general.php?page=qtranslate-xt' );
		require_once( QTRANSLATE_DIR . '/admin/qtx_admin_options_update.php' );
		//call_user_func('qtranxf_editConfig');
		qtranxf_editConfig();
	}

	$next_thanks = get_option( 'qtranslate_next_thanks' );
	if ( $next_thanks !== false && $next_thanks < time() ) {
		$messages = get_option( 'qtranslate_admin_notices' );
		if ( isset( $messages['next_thanks'] ) ) {
			unset( $messages['next_thanks'] );
			update_option( 'qtranslate_admin_notices', $messages );
		}
		$next_thanks = false;
	}
	if ( $next_thanks === false ) {
		$next_thanks = strtotime( '+' . rand( 100, 300 ) . 'days' );
		update_option( 'qtranslate_next_thanks', $next_thanks );
	}

	if ( $q_config['auto_update_mo'] ) {
		qtranxf_updateGettextDatabases();
	}
}

add_action( 'admin_init', 'qtranxf_admin_init', 2 );

/**
 * load field configurations for the current admin page
 */
function qtranxf_get_admin_page_config() {
	global $q_config, $pagenow;
	if ( isset( $q_config['i18n-cache']['admin_page_configs'] ) ) {
		//qtranxf_dbg_log('qtranxf_get_admin_page_config: $page_configs cached: ', $q_config['i18n-cache']['admin_page_configs']);
		return $q_config['i18n-cache']['admin_page_configs'];
	}
	$admin_config = $q_config['admin_config'];
	//qtranxf_dbg_log('qtranxf_get_admin_page_config: $admin_config: raw: ',qtranxf_json_encode($admin_config));
	$admin_config = apply_filters( 'qtranslate_load_admin_page_config', $admin_config );//obsolete
	$url_query    = isset( $q_config['url_info']['query'] ) ? $q_config['url_info']['query'] : '';
	/**
	 * Customize the admin configuration for all pages.
	 *
	 * @param (array) $admin_config token 'admin-config' of the configuration.
	 */
	$admin_config = apply_filters( 'i18n_admin_config', $admin_config );
	//qtranxf_dbg_log('qtranxf_get_admin_page_config: $admin_config: filtered: ',qtranxf_json_encode($admin_config));

	$page_configs = qtranxf_parse_page_config( $admin_config, $pagenow, $url_query );
	//qtranxf_dbg_log('qtranxf_get_admin_page_config: $page_configs: ', $page_configs);
	$q_config['i18n-cache']['admin_page_configs'] = $page_configs;

	return $page_configs;
}

function qtranxf_get_admin_page_config_post_type( $post_type ) {
	global $q_config, $pagenow;
	static $page_config;//cache
	if ( ! is_null( $page_config ) ) {
		//qtranxf_dbg_log('qtranxf_get_admin_page_config_post_type: cached: '.$pagenow.'; post_type: ', $post_type);
		return $page_config;
	}
	if ( ! empty( $q_config['post_type_excluded'] ) ) {
		switch ( $pagenow ) {
			case 'post.php':
			case 'post-new.php':
				if ( in_array( $post_type, $q_config['post_type_excluded'] ) ) {
					//qtranxf_dbg_log('qtranxf_get_admin_page_config_post_type: post_type_excluded: pagenow: '.$pagenow.'; post_type: ', $post_type);
					$page_config = array();

					return $page_config;
				}
			default:
				break;
		}
	}
	//qtranxf_dbg_log('qtranxf_get_admin_page_config_post_type: pagenow: '.$pagenow.'; post_type: ', $post_type);
	$page_configs = qtranxf_get_admin_page_config();
	//$page_configs = apply_filters('i18n_admin_config_post_type', $page_configs, $post_type);

	//qtranxf_dbg_log('qtranxf_get_admin_page_config_post_type: $page_configs: ', $page_configs);
	$page_config = isset( $page_configs[''] ) ? $page_configs[''] : array();
	if ( $post_type ) {
		foreach ( $page_configs as $k => $cfg ) {
			if ( empty( $k ) ) {
				continue;
			}
			if ( isset( $cfg['post_type'] ) ) {
				$cfg_post_type = $cfg['post_type'];
				unset( $cfg['post_type'] );
			} else {
				$cfg_post_type = $k;
			}
			$matched = qtranxf_match_post_type( $cfg_post_type, $post_type );
			//qtranxf_dbg_log('qtranxf_get_admin_page_config_post_type: $cfg: ', $cfg);
			//qtranxf_dbg_log('qtranxf_get_admin_page_config_post_type: $matched: ', $matched);
			if ( $matched === false ) {
				continue;
			}
			if ( is_null( $matched ) ) {
				$page_config = array();
				break;
			}
			$page_config = qtranxf_merge_config( $page_config, $cfg );
		}
	}
	//qtranxf_dbg_log('qtranxf_get_admin_page_config_post_type: $page_config: ', $page_config);

	unset( $page_config['filters'] );

	if ( ! empty( $page_config ) ) {
		//clean up empty items
		if ( ! empty( $page_config['forms'] ) ) {
			foreach ( $page_config['forms'] as $form_id => &$frm ) {
				if ( ! isset( $frm['fields'] ) ) {
					continue;
				}
				foreach ( $frm['fields'] as $k => $f ) {
					if ( isset( $f['encode'] ) && $f['encode'] == 'none' ) {
						//unset($page_config['forms'][$form_id]['fields'][$k]);
						unset( $frm['fields'][ $k ] );
					}
					if ( $post_type && ! empty( $f['post-type-excluded'] ) && preg_match( '/' . $f['post-type-excluded'] . '/', $post_type ) ) {
						unset( $frm['fields'][ $k ] );
					}
				}
				foreach ( $frm as $k => $token ) {
					if ( empty( $token ) ) {
						unset( $frm[ $k ] );
					}
				}
				if ( empty( $frm ) ) {
					unset( $page_config['forms'][ $form_id ] );
				}
			}
		}
		foreach ( $page_config as $k => $cfg ) {
			if ( empty( $cfg ) ) {
				unset( $page_config[ $k ] );
			}
		}
	}

	if ( ! empty( $page_config ) ) {
		$page_config['js'] = array();
		if ( isset( $page_config['js-conf'] ) ) {
			foreach ( $page_config['js-conf'] as $k => $js ) {
				if ( ! isset( $js['handle'] ) ) {
					$js['handle'] = $k;
				}
				$page_config['js'][] = $js;
			}
			unset( $page_config['js-conf'] );
		}

		$page_config['js'][] = array( 'handle' => 'qtranslate-admin-common', 'src' => './admin/js/common.min.js' );

		if ( isset( $page_config['js-exec'] ) ) {
			foreach ( $page_config['js-exec'] as $k => $js ) {
				if ( ! isset( $js['handle'] ) ) {
					$js['handle'] = $k;
				}
				$page_config['js'][] = $js;
			}
			unset( $page_config['js-exec'] );
		}

		//make src to be relative to WP_CONTENT_DIR
		//$bnm = 'plugins/'.qtranxf_plugin_dirname();
		$bnm         = qtranxf_plugin_dirname_from_wp_content();
		$content_dir = trailingslashit( WP_CONTENT_DIR );
		foreach ( $page_config['js'] as $k => $js ) {
			if ( ! isset( $js['src'] ) ) {
				continue;
			}
			$src = $js['src'];
			//qtranxf_dbg_log('qtranxf_get_admin_page_config_post_type: js['.$k.']: $src: ',$src);
			if ( $src[0] == '.' && ( $src[1] == '/' || $src[1] == DIRECTORY_SEPARATOR ) ) {
				$page_config['js'][ $k ]['src'] = $bnm . substr( $src, 1 );
			} else {
				if ( file_exists( $content_dir . $src ) ) {
					continue;
				}  //from WP_CONTENT_DIR as expected
				$fp = dirname( $bnm ) . '/' . $src;  //from 'plugins' folder
				if ( file_exists( $content_dir . $fp ) ) {
					$page_config['js'][ $k ]['src'] = $fp;
					continue;
				}
				$fp = $bnm . '/' . $src; //from this plugin folder
				if ( file_exists( $content_dir . $fp ) ) {
					$page_config['js'][ $k ]['src'] = $fp;
					continue;
				}
				if ( file_exists( $src ) ) { //absolute path was given
					if ( qtranxf_startsWith( $src, $content_dir ) ) {
						$fp                             = substr( $src, strlen( $content_dir ) );
						$page_config['js'][ $k ]['src'] = $fp;
						continue;
					}
				}
				unset( $page_config['js'][ $k ] );
				qtranxf_error_log( sprintf( __( 'Could not find script file "%s" for handle "%s".', 'qtranslate' ), $src, $js['handle'] ) );
			}
		}
	}

	/*
	 * Customize the $page_config for this admin request.
	 * @param (array) $page_config 'admin_config', filtered for the current page.
	 * @param (string) $pagenow value of WordPress global variable $pagenow.
	 * @param (string) $url_query query part of URL without '?', sanitized version of $_SERVER['QUERY_STRING'].
	 * @param (string) $post_type type of post serving on the current page, or null if not applicable.
	 */
	//$page_config = apply_filters('i18n_admin_page_config', $page_config, $pagenow, $url_query, $post_type);
	//qtranxf_dbg_log('qtranxf_get_admin_page_config_post_type: $pagenow='.$pagenow.'; $url_query='.$q_config['url_info']['query'].'; $post_type='.$post_type.'; $page_config: ',qtranxf_json_encode($page_config));
	qtranxf_write_config_log( $page_config, '', $pagenow, '', $post_type );

	return $page_config;
}

function qtranxf_add_admin_footer_js() {
	global $q_config;
	$post_type   = qtranxf_post_type();
	$page_config = qtranxf_get_admin_page_config_post_type( $post_type );
	//qtranxf_dbg_log('qtranxf_add_admin_footer_js: $page_config: ',$page_config);
	if ( empty( $page_config ) ) {
		return;
	}

	wp_dequeue_script( 'autosave' );
	wp_deregister_script( 'autosave' );//autosave script saves the active language only and messes it up later in a hard way

	$config = array();
	// since 3.2.9.9.0 'enabled_languages' is replaced with 'language_config' structure
	$keys = array(
		'default_language',
		'language',
		'url_mode',
		'hide_default_language'
	); // ,'term_name'
	foreach ( $keys as $key ) {
		$config[ $key ] = $q_config[ $key ];
	}
	$config['lsb_style_subitem'] = ( $q_config['lsb_style'] == 'Simple_Buttons.css' ) ? 'button' : '';
	$config['lsb_style_active_class'] = ( $q_config['lsb_style'] == 'Tabs_in_Block.css' ) ? 'wp-ui-highlight' : 'active';
	$config['lsb_style_wrap_class'] = ( $q_config['lsb_style'] == 'Tabs_in_Block.css' ) ? 'wp-ui-primary' : '';

	$config['custom_fields']        = apply_filters( 'qtranslate_custom_fields', $q_config['custom_fields'] );
	$config['custom_field_classes'] = apply_filters( 'qtranslate_custom_field_classes', $q_config['custom_field_classes'] );
	if ( $q_config['url_mode'] == QTX_URL_DOMAINS ) {
		$config['domains'] = $q_config['domains'];
	}
	$homeinfo                = qtranxf_get_home_info();
	$config['homeinfo_path'] = trailingslashit( $homeinfo['path'] );
	$config['home_url_path'] = parse_url( home_url( '/' ), PHP_URL_PATH );//todo optimize
	$config['flag_location'] = qtranxf_flag_location();
	$config['js']            = array();

	$config['strings'] = array();//since 3.4.7
	//translators: The begining of the prompt on hover over an LSB. This string is appended with a edit-language name in admin language, so that the space at the end matters.
	$config['strings']['ShowIn'] = __( 'Show multilingual content in ', 'qtranslate' );

	$config['language_config'] = array();
	$language_config           = &$config['language_config'];
	foreach ( $q_config['enabled_languages'] as $lang ) {
		$language_config[ $lang ] = array();
		$lang_cfg                 = &$language_config[ $lang ];
		$lang_cfg['flag']         = $q_config['flag'][ $lang ];
		$lang_cfg['name']         = $q_config['language_name'][ $lang ];
		$lang_cfg['locale']       = $q_config['locale'][ $lang ];
		$lang_cfg['locale_html']  = ! empty( $q_config['locale_html'][ $lang ] ) ? $q_config['locale_html'][ $lang ] : $lang;
		$lang_cfg['admin_name']   = qtranxf_getLanguageName( $lang );
	}
	if ( ! empty( $page_config ) ) {
		$config['page_config'] = $page_config;
		//no need for javascript:
		unset( $config['page_config']['js'] );
	}

	$config['LSB'] = $q_config['editor_mode'] == QTX_EDITOR_MODE_LSB;
	$config['RAW'] = $q_config['editor_mode'] == QTX_EDITOR_MODE_RAW;

	if ( empty( $q_config['hide_lsb_copy_content'] ) ) {
		//translators: Prompt on hover over button "Copy From" to copy content from other language
		$config['strings']['CopyFromAlt'] = __( 'Fill empty multilingual fields with content from other language', 'qtranslate' );
		//translators: Prompt on hover over select-element to choose the language to copy content from
		$config['strings']['ChooseLangToCopy'] = __( 'Choose language to copy multilingual content from', 'qtranslate' );
		//translators: Title of button to copy content from otrher language
		$config['strings']['CopyFrom'] = __( 'Copy from', 'qtranslate' );
	} else {
		$config['hide_lsb_copy_content'] = true;
	}

	/**
	 * Last chance to customize Java script variable qTranslateConfig.
	 */
	$config = apply_filters( 'qtranslate_admin_page_config', $config );

	qtranxf_enqueue_scripts( $page_config['js'] );
	?>
    <script type="text/javascript">
        // <![CDATA[
		<?php
		echo 'var qTranslateConfig=' . json_encode( $config ) . ';' . PHP_EOL;
		// each script entry may define javascript code to be injected
		foreach ( $page_config['js'] as $k => $js ) {
			if ( isset( $js['javascript'] ) && ! empty( $js['javascript'] ) ) {
				echo $js['javascript'];
			}
		}
		if ( $q_config['qtrans_compatibility'] ) {
			echo 'qtrans_use = function(lang, text) { var result = qtranxj_split(text); return result[lang]; }' . PHP_EOL;
		}
		do_action( 'qtranslate_add_admin_footer_js' );
		?>
        //]]>
    </script>
	<?php
}

function qtranxf_add_admin_head_js( $enqueue_script = true ) {
	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
		$js_options = 'js/options.js';
	} else {
		$js_options = 'js/options.min.js';
	}
	if ( $enqueue_script ) {
		//wp_register_script( 'qtranslate-admin-options', plugins_url( $js_options, __FILE__ ), array(), QTX_VERSION );
		wp_enqueue_script( 'qtranslate-admin-options', plugins_url( $js_options, __FILE__ ), array(), QTX_VERSION );
	} else {
		echo '<script type="text/javascript">' . PHP_EOL . '// <![CDATA[' . PHP_EOL;
		$plugin_dir_path = plugin_dir_path( __FILE__ );
		readfile( $plugin_dir_path . $js_options );
		echo '//]]>' . PHP_EOL . '</script>' . PHP_EOL;
	}
}

function qtranxf_add_admin_lang_icons() {
	global $q_config;
	$flag_location = qtranxf_flag_location();
	echo '<style type="text/css">' . PHP_EOL;
	echo "#wpadminbar #wp-admin-bar-language>div.ab-item{ background-size: 0;";
	echo 'background-image: url(' . $flag_location . $q_config['flag'][ $q_config['language'] ] . ');}' . PHP_EOL;
	foreach ( $q_config['enabled_languages'] as $language ) {
		echo '#wpadminbar ul li#wp-admin-bar-' . $language . ' {background-size: 0; background-image: url(' . $flag_location . $q_config['flag'][ $language ] . '); margin-right: 5px; }' . PHP_EOL;
	}
	echo '</style>' . PHP_EOL;
}

/**
 * Add CSS code to highlight the translatable fields */
function qtranxf_add_admin_highlight_css() {
	global $q_config;
	if ( $q_config['highlight_mode'] == QTX_HIGHLIGHT_MODE_NONE || get_the_author_meta( 'qtranslate_highlight_disabled', get_current_user_id() ) ) {
		return '';
	}
	$highlight_mode = $q_config['highlight_mode'];
	switch ( $highlight_mode ) {
		case QTX_HIGHLIGHT_MODE_CUSTOM_CSS:
			$css = $q_config['highlight_mode_custom_css'];
			break;
		default:
			$css = qtranxf_get_admin_highlight_css( $highlight_mode );
	}

	return $css;
}

function qtranxf_get_admin_highlight_css( $highlight_mode ) {
	global $q_config;
	$css = 'input.qtranxs-translatable, textarea.qtranxs-translatable, div.qtranxs-translatable {' . PHP_EOL;
	switch ( $highlight_mode ) {
		case QTX_HIGHLIGHT_MODE_BORDER_LEFT:// v3
			$css .= 'border-left: 3px solid #UserColor2 !important;' . PHP_EOL;
			break;
		case QTX_HIGHLIGHT_MODE_BORDER:// v2
			$css .= 'border: 1px solid #UserColor2 !important;' . PHP_EOL;
			break;
		case QTX_HIGHLIGHT_MODE_LEFT_SHADOW: // v1
			$css .= 'box-shadow: -3px 0 #UserColor2 !important;' . PHP_EOL;
			break;
		case QTX_HIGHLIGHT_MODE_OUTLINE:// v1
			$css .= 'outline: 2px solid #UserColor2 !important;' . PHP_EOL;
			//$css .= 'div.qtranxs-translatable div.mce-panel {' . PHP_EOL;
			//$css .= 'margin-top: 2px' . PHP_EOL;
			break;
	}
	$css .= '}' . PHP_EOL;

	return $css;
}

function qtranxf_add_admin_css() {
	global $q_config;
	wp_register_style( 'qtranslate-admin-style', plugins_url( 'css/qtranslate_configuration.css', __FILE__ ), array(), QTX_VERSION );
	wp_enqueue_style( 'qtranslate-admin-style' );
	qtranxf_add_admin_lang_icons();
	$css = qtranxf_add_admin_highlight_css();
	$fn  = QTRANSLATE_DIR . '/admin/css/opLSBStyle/' . $q_config['lsb_style'];
	if ( file_exists( $fn ) ) {
		$css .= file_get_contents( $fn );
	}
	$css                  = preg_replace( '!/\\*.*?\\*/!ms', '', $css );
	$css                  = preg_replace( '!//.*?$!m', '', $css );
	$css                  = preg_replace( '/\\n\\s*\\n/m', "\n", $css );
	$current_color_scheme = qtranxf_get_user_admin_color();
	foreach ( $current_color_scheme as $k => $clr ) {
		$css = preg_replace( '/#UserColor' . $k . '/m', $clr, $css );
	}
	echo '<style type="text/css" media="screen">' . PHP_EOL;
	echo $css;
	do_action( 'qtranslate_admin_css' );
	do_action( 'qtranslate_css' );//should not be used
	echo '</style>' . PHP_EOL;
}

function qtranxf_admin_head() {
	//qtranxf_dbg_log('11.qtranxf_admin_head:');
	//wp_enqueue_script( 'jquery' );
	//qtranxf_add_css();//Since 3.2.5 no longer needed
	qtranxf_add_admin_css();
	global $q_config;
	if ( isset( $q_config['url_info']['query'] ) && strpos( $q_config['url_info']['query'], 'page=qtranslate-xt' ) !== false ) {
		//$enqueue_script = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG);
		qtranxf_add_admin_head_js( true );
	}
}

add_action( 'admin_head', 'qtranxf_admin_head' );

function qtranxf_admin_footer() {
	//qtranxf_dbg_log('18.qtranxf_admin_footer:');
	qtranxf_add_admin_footer_js();
}

add_action( 'admin_footer', 'qtranxf_admin_footer', 999 );

function qtranxf_customize_allowed_urls( $urls ) {
	global $q_config;
	$home   = home_url( '/', is_ssl() ? 'https' : 'http' );
	$urls[] = $home;
	foreach ( $q_config['enabled_languages'] as $lang ) {
		$url    = qtranxf_convertURL( $home, $lang, true, true );
		$urls[] = $url;
	}
	if ( $q_config['hide_default_language'] ) {
		$urls[] = qtranxf_convertURL( $home, $q_config['default_language'], true, false );
	}

	return $urls;
}

add_filter( 'customize_allowed_urls', 'qtranxf_customize_allowed_urls' );

/** @since 3.4 */
function qtranxf_settings_page() {
	require_once( QTRANSLATE_DIR . '/admin/qtx_configuration.php' );
	qtranxf_conf();
}

/**
 * @since 3.3.8.7
 */
function qtranxf_translate_menu( &$m ) {
	global $q_config;
	$lang = $q_config['language'];
	foreach ( $m as $k => &$item ) {
		if ( empty( $item[0] ) ) {
			continue;
		}
		$item[0] = qtranxf_use_language( $lang, $item[0] );
	}
}

/**
 * Adds Management Interface and translates admin menu.
 */
function qtranxf_admin_menu() {
	global $menu, $submenu;
	//qtranxf_dbg_log('7.qtranxf_admin_menu:');
	if ( ! empty( $menu ) ) {
		qtranxf_translate_menu( $menu );
	}
	if ( ! empty( $submenu ) ) {
		foreach ( $submenu as $k => $m ) {
			qtranxf_translate_menu( $submenu[ $k ] );
		}
	}
	//qtranxf_dbg_log('"admin_menu": qtranxf_admin_menu: REQUEST_TIME_FLOAT: ', $_SERVER['REQUEST_TIME_FLOAT']);
	// Configuration Page
	add_options_page( __( 'Language Management', 'qtranslate' ), __( 'Languages', 'qtranslate' ), 'manage_options', 'qtranslate-xt', 'qtranxf_settings_page' ); // returns 'settings_page_qtranslate-x'
	//qtranxf_dbg_log('qtranxf_admin_menu: $menu: ', $menu);
	//qtranxf_dbg_log('qtranxf_admin_menu: $submenu: ', $submenu);
}

/* Add a metabox in admin menu page */
function qtranxf_nav_menu_metabox( $object ) {
	global $nav_menu_selected_id;
	$nm    = __( 'Language Menu', 'qtranslate' );
	$elems = array( '#qtransLangSwLM#' => $nm );

	class qtranxcLangSwItems {
		public $db_id = 0;
		public $object = 'qtranslangsw';
		public $object_id;
		public $menu_item_parent = 0;
		public $type = 'custom';
		public $title;// = 'Language';
		public $url;
		public $target = '';
		public $attr_title = '';
		public $classes = array();
		public $xfn = '';
	}

	$elems_obj = array();
	foreach ( $elems as $value => $title ) {
		$elems_obj[ $title ] = new qtranxcLangSwItems();
		$obj                 = &$elems_obj[ $title ];
		$obj->object_id      = esc_attr( $value );
		if ( empty( $obj->title ) ) {
			$obj->title = esc_attr( $title );
		}
		$obj->label = esc_attr( $title );
		$obj->url   = esc_attr( $value );
	}

	$walker = new Walker_Nav_Menu_Checklist();
	?>
    <div id="qtranxs-langsw" class="qtranxslangswdiv">
        <div id="tabs-panel-qtranxs-langsw-all" class="tabs-panel tabs-panel-view-all tabs-panel-active">
            <ul id="qtranxs-langswchecklist" class="list:qtranxs-langsw categorychecklist form-no-clear">
				<?php echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $elems_obj ), 0, (object) array( 'walker' => $walker ) ) ?>
            </ul>
        </div>
        <span class="list-controls hide-if-no-js">
		<a href="javascript:void(0);" class="help"
           onclick="jQuery( '#qtranxs-langsw-help' ).toggle();"><?php _e( 'Help', 'qtranslate' ) ?></a>
		<span class="hide-if-js" id="qtranxs-langsw-help"><p><a name="qtranxs-langsw-help"></a>
				<?php
				echo __( 'Menu item added is replaced with a drop-down menu of available languages, when menu is rendered.', 'qtranslate' );
				echo ' ';
				printf( __( 'The rendered menu items have CSS classes %s and %s ("%s" is a language code), which can be defined in theme style, if desired. The label of language menu can also be customized via field "%s" in the menu configuration.', 'qtranslate' ), '.qtranxs-lang-menu, .qtranxs-lang-menu-xx, .qtranxs-lang-menu-item', '.qtranxs-lang-menu-item-xx', 'xx', qtranxf_translate_wp( 'Navigation Label' ) );
				echo ' ';
				printf( __( 'The field "%s" of inserted menu item allows additional configuration described in %sFAQ%s.', 'qtranslate' ), qtranxf_translate_wp( 'URL' ), '<a href="https://qtranslatexteam.wordpress.com/faq/#LanguageSwitcherMenuConfig" target="blank">', '</a>' ) ?></p>
		</span>
	</span>
        <p class="button-controls">
		<span class="add-to-menu">
			<input type="submit"<?php disabled( $nav_menu_selected_id, 0 ) ?>
                   class="button-secondary submit-add-to-menu right"
                   value="<?php esc_attr_e( 'Add to Menu', 'qtranslate' ) ?>" name="add-qtranxs-langsw-menu-item"
                   id="submit-qtranxs-langsw"/>
			<span class="spinner"></span>
		</span>
        </p>
    </div>
	<?php
}

function qtranxf_add_nav_menu_metabox() {
	add_meta_box( 'add-qtranxs-language-switcher', __( 'Language Switcher', 'qtranslate' ), 'qtranxf_nav_menu_metabox', 'nav-menus', 'side', 'default' );
}

function qtranxf_add_language_menu( $wp_admin_bar ) {
	global $q_config;
	if ( ! is_admin() || ! is_admin_bar_showing() ) {
		return;
	}

	if ( wp_is_mobile() ) {
		$title = '';
	} else {
		$title = $q_config['language_name'][ $q_config['language'] ];
	}

	$wp_admin_bar->add_menu( array(
			'id'     => 'language',
			'parent' => 'top-secondary',
			'title'  => $title
		)
	);

	foreach ( $q_config['enabled_languages'] as $language ) {
		$wp_admin_bar->add_menu(
			array
			(
				'id'     => $language,
				'parent' => 'language',
				'title'  => $q_config['language_name'][ $language ],
				'href'   => add_query_arg( 'lang', $language )
			)
		);
	}
}

function qtranxf_links( $links, $file, $plugin_data, $context ) {
	$settings_link = '<a href="options-general.php?page=qtranslate-xt">' . qtranxf_translate_wp( 'Settings' ) . '</a>';    //translators: expected in WordPress default textdomain
	array_unshift( $links, $settings_link ); // before other links

	return $links;
}

function qtranxf_admin_notices_config() {
	global $q_config;
	if ( empty( $q_config['url_info']['errors'] ) && empty( $q_config['url_info']['warnings'] ) && empty( $q_config['url_info']['messages'] ) && empty( $q_config['lic']['wrn'] ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( isset( $screen->id ) && $screen->id == 'settings_page_qtranslate-x' ) {
		$qtitle = '';
	} else {
		$qlink  = admin_url( 'options-general.php?page=qtranslate-xt' );
		$qtitle = '<a href="' . $qlink . '" style="color:magenta">qTranslate&#8209;XT</a>:&nbsp;';
	}
	$fmt = '<div class="%1$s notice is-dismissible" id="qtranxs-%2$s-%1$s"><p>' . $qtitle . '%3$s</p></div>' . PHP_EOL;

	if ( isset( $q_config['url_info']['errors'] ) ) {
		if ( is_array( $q_config['url_info']['errors'] ) ) {
			//translators: Colon after a title. Template reused from language menu item.
			$hdr = sprintf( __( '%s:', 'qtranslate' ), '<strong><span style="color: red;">' . __( 'Error', 'qtranslate' ) . '</span></strong>' ) . '&nbsp;';
			foreach ( $q_config['url_info']['errors'] as $key => $msg ) {
				printf( $fmt, 'error', $key, $hdr . $msg );
			}
		}
		unset( $q_config['url_info']['errors'] );
	}
	if ( isset( $q_config['url_info']['warnings'] ) ) {
		if ( is_array( $q_config['url_info']['warnings'] ) ) {
			//translators: Colon after a title. Template reused from language menu item.
			$hdr = sprintf( __( '%s:', 'qtranslate' ), '<strong><span style="color: blue;">' . __( 'Warning', 'qtranslate' ) . '</span></strong>' ) . '&nbsp;';
			foreach ( $q_config['url_info']['warnings'] as $key => $msg ) {
				printf( $fmt, 'update-nag', $key, $hdr . $msg );
			}
		}
		unset( $q_config['url_info']['warnings'] );
	}
	if ( isset( $q_config['url_info']['messages'] ) ) {
		if ( is_array( $q_config['url_info']['messages'] ) ) {
			foreach ( $q_config['url_info']['messages'] as $key => $msg ) {
				printf( $fmt, 'updated', $key, $msg );
			}
		}
		unset( $q_config['url_info']['messages'] );
	}
}

/**
 * Encode front end language on home_url, since, on admin side, it is mostly in use to create links to a preview pages.
 * @since 3.4.5
 */
function qtranxf_admin_home_url( $url, $path, $orig_scheme, $blog_id ) {
	global $q_config;
	//qtranxf_dbg_log('qtranxf_admin_home_url: $_COOKIE: ', $_COOKIE);
	if ( isset( $_COOKIE[ QTX_COOKIE_NAME_FRONT ] ) ) {
		$lang = $_COOKIE[ QTX_COOKIE_NAME_FRONT ];
	} else {
		$lang = $q_config['default_language'];
	}
	//qtranxf_dbg_log('qtranxf_admin_home_url: url='.$url.'; path='.$path.'; orig_scheme='.$orig_scheme);
	$url = qtranxf_get_url_for_language( $url, $lang, ! $q_config['hide_default_language'] || $lang != $q_config['default_language'] );

	//qtranxf_dbg_log('qtranxf_admin_home_url: url='.$url.'; lang='.$lang);
	return $url;
}

function qtranxf_admin_footer_text( $text ) {
	if ( qtranxf_admin_is_config_page() ) {
		$msg  = sprintf( __( 'Thank you for using plugin %s!', 'qtranslate' ), '<strong>qTranslate&#8209;XT</strong>' );
		$text = '<span id="footer-thankyou">' . $msg . '</span>';
	}

	return $text;
}

add_filter( 'admin_footer_text', 'qtranxf_admin_footer_text', 99 );

function qtranxf_admin_footer_update( $text ) {
	if ( qtranxf_admin_is_config_page() ) {
		$text        = sprintf( __( 'Plugin Version %s', 'qtranslate' ), QTX_VERSION );
		$current     = get_site_transient( 'update_plugins' );
		$plugin_file = qtranxf_plugin_basename();
		if ( isset( $current->response[ $plugin_file ] ) ) {
			$data = $current->response[ $plugin_file ];
			if ( is_plugin_active_for_network( $plugin_file ) ) {
				$url = network_admin_url( 'update-core.php' );
			} else {
				$url = admin_url( 'update-core.php' );
			}
			$text .= '&nbsp;<strong><a href="' . $url . '">' . sprintf( _x( 'Get %s', '%s is a version number of a plugin. It is a shortcut for "Get version %s of such a such plugin."', 'qtranslate' ), $data->new_version ) . '</a></strong>';
		}
	}

	return $text;
}

add_filter( 'update_footer', 'qtranxf_admin_footer_update', 99 );

function qtranxf_add_admin_filters() {
	global $q_config, $pagenow;
	if ( $q_config['url_mode'] != QTX_URL_QUERY //otherwise '?' may interfere with WP code
	     && $pagenow == 'customize.php'
	) {
		add_filter( 'home_url', 'qtranxf_admin_home_url', 5, 4 );
	}
}

add_action( 'admin_head-nav-menus.php', 'qtranxf_add_nav_menu_metabox' );
add_action( 'admin_menu', 'qtranxf_admin_menu', 999 );
add_action( 'admin_bar_menu', 'qtranxf_add_language_menu', 999 );
add_action( 'wp_before_admin_bar_render', 'qtranxf_before_admin_bar_render' );
