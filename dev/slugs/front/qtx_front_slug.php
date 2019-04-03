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
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function qtranxf_slug_sanitize_title( $title, $raw_title = '', $context = 'save' ) {
	switch ( $context ) {
		case 'query':
			$name = qtranxf_slug_get_name( $title );
			if ( $name ) {
				return $name;
			}
			break;
		default:
			break;
	}

	return $title;
}

add_filter( 'sanitize_title', 'qtranxf_slug_sanitize_title', 5, 3 );
