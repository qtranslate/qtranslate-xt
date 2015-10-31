<?php
/*
	Copyright 2014  qTranslate Team  (email : qTranslateTeam@gmail.com )

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
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Apply translations of slugs to all $wp->query_vars
*/
function qtranxf_slug_parse_request(&$wp){//calss WP
	//qtranxf_dbg_log('qtranxf_slug_parse_request: $wp: ',$wp);
	//qtranxf_dbg_log('qtranxf_slug_parse_request: query_vars: ',$wp->query_vars);
	//qtranxf_dbg_log('qtranxf_slug_parse_request: query_string: ',$wp->query_string);
	//qtranxf_dbg_log('qtranxf_slug_parse_request: request: ',$wp->request);
	//qtranxf_dbg_log('qtranxf_slug_parse_request: matched_rule: ',$wp->matched_rule);
	//qtranxf_dbg_log('qtranxf_slug_parse_request: matched_query: ',$wp->matched_query);
	//qtranxf_dbg_log('qtranxf_slug_parse_request: did_permalink: ',$wp->did_permalink);
	foreach($wp->query_vars as $k => $v){
		$info = qtranxf_slug_translation($v);
		if(!isset($info['name'])) continue;
		$wp->query_vars[$k] = $info['name'];
	}
	//qtranxf_dbg_log('qtranxf_slug_parse_request: done: query_vars: ',$wp->query_vars);
	//global $wp_rewrite;
	//qtranxf_dbg_log('qtranxf_slug_parse_request: wp_rewrite: ',$wp_rewrite);
}
add_action( 'parse_request', 'qtranxf_slug_parse_request' );

function qtranxf_slug_sanitize_title($title, $raw_title = '', $context = 'save') {
	switch($context) {
		case 'query':{
			$name = qtranxf_slug_get_name($title);
			if($name) return $name;
		} break;
		default: break;
	}
	return $title;
}
add_filter('sanitize_title', 'qtranxf_slug_sanitize_title', 5, 3);
