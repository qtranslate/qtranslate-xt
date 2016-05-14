<?php
/**
Plugin Name: qTranslate-X
Plugin URI: http://wordpress.org/plugins/qtranslate-x/
Description: Adds user-friendly and database-friendly multilingual content support.
Version: 3.4.6.9
Author: qTranslate Team
Author URI: http://qtranslatexteam.wordpress.com/about
Tags: multilingual, multi, language, admin, tinymce, Polyglot, bilingual, widget, switcher, professional, human, translation, service, qTranslate, zTranslate, mqTranslate, qTranslate Plus, WPML
Text Domain: qtranslate
Domain Path: /lang/
License: GPL2
Author e-mail: qTranslateTeam@gmail.com
Original Author: Qian Qin (http://www.qianqin.de mail@qianqin.de)
GitHub Plugin URI: https://github.com/qTranslate-Team/qtranslate-x
GitHub Branch: master
*/
/* Unused keywords (as described in http://codex.wordpress.org/Writing_a_Plugin):
 * Network: Optional. Whether the plugin can only be activated network wide. Example: true
 */
/*
	Copyright 2014  qTranslate Team  (email : qTranslateTeam@gmail.com )

	The statement below within this comment block is relevant to
	this file as well as to all files in this folder and to all files
	in all sub-folders of this folder recursively.

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
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
define('QTX_VERSION','3.4.6.9');

if ( ! defined( 'QTRANSLATE_FILE' ) ) {
	define( 'QTRANSLATE_FILE', __FILE__ );
	define( 'QTRANSLATE_DIR', dirname(__FILE__) );
}

require_once(QTRANSLATE_DIR.'/inc/qtx_class_translator.php');

if(is_admin() ){ // && !(defined('DOING_AJAX') && DOING_AJAX) //todo cleanup
	require_once(QTRANSLATE_DIR.'/admin/qtx_activation_hook.php');
	qtranxf_register_activation_hooks();
}

// load additional functionalities

//if(file_exists(QTRANSLATE_DIR.'/slugs'))
//	require_once(QTRANSLATE_DIR.'/slugs/qtx_slug.php');

// load qTranslate Services if available // disabled since 3.1
//if(file_exists(QTRANSLATE_DIR.'/qtranslate_services.php'))
//	require_once(QTRANSLATE_DIR.'/qtranslate_services.php');
