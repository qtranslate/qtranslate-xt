<?php
/**
 * Plugin Name: qTranslate-XT
 * Plugin URI: http://github.com/qtranslate/qtranslate-xt/
 * Description: Adds user-friendly and database-friendly multilingual content support.
 * Version: 3.5.1
 * Author: qTranslate Community
 * Author URI: http://github.com/qtranslate/
 * Tags: multilingual, multi, language, admin, tinymce, Polyglot, bilingual, widget, switcher, professional, human, translation, service, qTranslate, zTranslate, mqTranslate, qTranslate Plus, WPML
 * Text Domain: qtranslate
 * Domain Path: /lang/
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Author e-mail: herrvigg@gmail.com
 * Original Author: John Clause and Qian Qin (http://www.qianqin.de mail@qianqin.de)
 * GitHub Plugin URI: https://github.com/qtranslate/qtranslate-xt/
 */
/* Unused keywords (as described in http://codex.wordpress.org/Writing_a_Plugin):
 * Network: Optional. Whether the plugin can only be activated network wide. Example: true
 */
/*
	Copyright 2018  qTranslate Community

	The statement below within this comment block is relevant to
	this file as well as to all files in this folder and to all files
	in all sub-folders of this folder recursively.

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/
/*
 * Search for 'Designed as interface for other plugin integration' in comments to functions
 * to find out which functions are safe to use in the 3rd-party integration.
 * Avoid accessing internal variables directly, as they are subject to be re-designed at any time.
*/
if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
/**
 * The constants defined below are
 * Designed as interface for other plugin integration. The documentation is available at
 * https://qtranslatexteam.wordpress.com/integration/
 */
define( 'QTX_VERSION', '3.5.1' );

if ( ! defined( 'QTRANSLATE_FILE' ) ) {
	define( 'QTRANSLATE_FILE', __FILE__ );
	define( 'QTRANSLATE_DIR', dirname( __FILE__ ) );
}

require_once( QTRANSLATE_DIR . '/inc/qtx_class_translator.php' );

if ( is_admin() ) { // && !(defined('DOING_AJAX') && DOING_AJAX) //todo cleanup
	require_once( QTRANSLATE_DIR . '/admin/qtx_activation_hook.php' );
	qtranxf_register_activation_hooks();
}

// load additional functionalities
if ( is_plugin_active('woocommerce/woocommerce.php') ) {
	define( 'QTX_QWC_PLUGIN_FILE', 'woocommerce-qtranslate-x/woocommerce-qtranslate-x.php' );
	if ( is_plugin_active(QTX_QWC_PLUGIN_FILE) ) {
        deactivate_plugins(QTX_QWC_PLUGIN_FILE );
		add_action( 'admin_notices', function() {
            if ( is_plugin_active(QTX_QWC_PLUGIN_FILE) ) :
              $pluginData = get_plugin_data( WP_PLUGIN_DIR . '/' . QTX_QWC_PLUGIN_FILE, false, true );
			  $pluginName = $pluginData['Name'];
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php printf( __('[%s] Incompatible plugin detected: "%s". Please disable it.', 'qtranslate' ), 'qTranslate&#8209;XT', $pluginName); ?></p>
				<p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'plugins.php?action=deactivate&plugin=' . urlencode( QTX_QWC_PLUGIN_FILE ) ), 'deactivate-plugin_' . QTX_QWC_PLUGIN_FILE ) ) ?>"><strong><?php printf( __( 'Deactivate plugin %s', 'qtranslate' ), $pluginName ) ?></strong></a>
			</div>
			<?php
            endif;
		} );
	}
	elseif ( file_exists( QTRANSLATE_DIR . '/modules/woo-commerce/qwc.php' ) ) {
		require_once( QTRANSLATE_DIR . '/modules/woo-commerce/qwc.php' );
	}
}
