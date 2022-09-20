<?php

/**
 * [Legacy] Converter of a format given in DateTime format, transformed to the extended "QTX-strftime" format.
 *
 * @param string $format in DateTime format.
 *
 * @return string
 * @see https://www.php.net/manual/en/function.strftime.php
 * @see https://www.php.net/manual/en/datetime.format.php
 */
function qtranxf_convertDateFormatToStrftimeFormat( $format ) {
    $mappings = array(
        'd' => '%d',
        'D' => '%a',
        'j' => '%E',
        'l' => '%A',
        'N' => '%u',
        'S' => '%q',
        'w' => '%f',
        'z' => '%F',
        'W' => '%V',
        'F' => '%B',
        'm' => '%m',
        'M' => '%b',
        'n' => '%i',
        't' => '%J',
        'L' => '%k',
        'o' => '%G',
        'Y' => '%Y',
        'y' => '%y',
        'a' => '%P',
        'A' => '%p',
        'B' => '%K',
        'g' => '%l',
        'G' => '%L',
        'h' => '%I',
        'H' => '%H',
        'i' => '%M',
        's' => '%S',
        'u' => '%N',
        'e' => '%Q',
        'I' => '%o',
        'O' => '%O',
        'P' => '%s',
        'T' => '%v',
        'Z' => '%1',
        'c' => '%2',
        'r' => '%3',
        'U' => '%4'
    );

    $date_parameters       = array();
    $strftime_parameters   = array();
    $date_parameters[]     = '#%#';
    $strftime_parameters[] = '%';
    foreach ( $mappings as $df => $sf ) {
        $date_parameters[]     = '#(([^%\\\\])' . $df . '|^' . $df . ')#';
        $strftime_parameters[] = '${2}' . $sf;
    }
    // convert everything
    $format = preg_replace( $date_parameters, $strftime_parameters, $format );
    // remove single backslashes from dates
    $format = preg_replace( '#\\\\([^\\\\]{1})#', '${1}', $format );
    // remove double backslashes from dates
    $format = preg_replace( '#\\\\\\\\#', '\\\\', $format );

    return $format;
}

/**
 * [Legacy] Converter of a format/default pair to "QTX-strftime" format, applying 'use_strftime' configuration.
 *
 * @param string $format
 * @param string $default_format
 *
 * @return string
 */
function qtranxf_convertFormat( $format, $default_format ) {
    global $q_config;
    // if one of special language-neutral formats are requested, don't replace it
    switch ( $format ) {
        case 'Z':
        case 'c':
        case 'r':
        case 'U':
            return qtranxf_convertDateFormatToStrftimeFormat( $format );
        default:
            break;
    }
    // check for multilang formats
    $format         = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $format );
    $default_format = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $default_format );
    switch ( $q_config['use_strftime'] ) {
        case QTX_DATE:
            if ( $format == '' ) {
                $format = $default_format;
            }

            return qtranxf_convertDateFormatToStrftimeFormat( $format );
        case QTX_DATE_OVERRIDE:
            return qtranxf_convertDateFormatToStrftimeFormat( $default_format );
        case QTX_STRFTIME:
            return $format;
        case QTX_STRFTIME_OVERRIDE:
        default:
            return $default_format;
    }
}

/**
 * [Legacy] Converter of a date format to "QTX-strftime" format, applying qTranslate 'use_strftime' configuration.
 *
 * @param string $format
 *
 * @return string
 */
function qtranxf_convertDateFormat( $format ) {
    global $q_config;
    if ( isset( $q_config['date_format'][ $q_config['language'] ] ) ) {
        $default_format = $q_config['date_format'][ $q_config['language'] ];
    } elseif ( isset( $q_config['date_format'][ $q_config['default_language'] ] ) ) {
        $default_format = $q_config['date_format'][ $q_config['default_language'] ];
    } else {
        $default_format = '';
    }

    return qtranxf_convertFormat( $format, $default_format );
}

/**
 * [Legacy] Converter of a time format to "QTX-strftime" format, applying qTranslate 'use_strftime' configuration.
 *
 * @param string $format
 *
 * @return string
 */
function qtranxf_convertTimeFormat( $format ) {
    global $q_config;
    if ( isset( $q_config['time_format'][ $q_config['language'] ] ) ) {
        $default_format = $q_config['time_format'][ $q_config['language'] ];
    } elseif ( isset( $q_config['time_format'][ $q_config['default_language'] ] ) ) {
        $default_format = $q_config['time_format'][ $q_config['default_language'] ];
    } else {
        $default_format = '';
    }

    return qtranxf_convertFormat( $format, $default_format );
}

/**
 * [Legacy] Extension of PHP "QTX-strftime", valid up to PHP 8.0.
 *
 * @param string $format extended strftime with additional features such as %q
 * @param int $date timestamp
 * @param string $default Default result when $format is empty.
 * @param string $before Text copied before result.
 * @param $after Text copied after result.
 *
 * @return mixed|string
 * @deprecated Don't use this since strftime is deprecated from PHP8.1.
 * @See https://www.php.net/manual/en/function.strftime.php
 */
function qtranxf_strftime( $format, $date, $default = '', $before = '', $after = '' ) {
    // don't do anything if format is not given
    if ( $format == '' ) {
        return $default;
    }
    // add date suffix ability (%q) to strftime
    $day     = intval( ltrim( strftime( "%d", $date ), '0' ) );
    $search  = array();
    $replace = array();

    // date S
    $search[] = '/(([^%])%q|^%q)/';
    if ( $day == 1 || $day == 21 || $day == 31 ) {
        $replace[] = '$2st';
    } elseif ( $day == 2 || $day == 22 ) {
        $replace[] = '$2nd';
    } elseif ( $day == 3 || $day == 23 ) {
        $replace[] = '$2rd';
    } else {
        $replace[] = '$2th';
    }

    $search[]  = '/(([^%])%E|^%E)/';
    $replace[] = '${2}' . $day; // date j
    $search[]  = '/(([^%])%f|^%f)/';
    $replace[] = '${2}' . date( 'w', $date ); // date w
    $search[]  = '/(([^%])%F|^%F)/';
    $replace[] = '${2}' . date( 'z', $date ); // date z
    $search[]  = '/(([^%])%i|^%i)/';
    $replace[] = '${2}' . date( 'n', $date ); // date i
    $search[]  = '/(([^%])%J|^%J)/';
    $replace[] = '${2}' . date( 't', $date ); // date t
    $search[]  = '/(([^%])%k|^%k)/';
    $replace[] = '${2}' . date( 'L', $date ); // date L
    $search[]  = '/(([^%])%K|^%K)/';
    $replace[] = '${2}' . date( 'B', $date ); // date B
    $search[]  = '/(([^%])%l|^%l)/';
    $replace[] = '${2}' . date( 'g', $date ); // date g
    $search[]  = '/(([^%])%L|^%L)/';
    $replace[] = '${2}' . date( 'G', $date ); // date G
    $search[]  = '/(([^%])%N|^%N)/';
    $replace[] = '${2}' . date( 'u', $date ); // date u
    $search[]  = '/(([^%])%Q|^%Q)/';
    $replace[] = '${2}' . date( 'e', $date ); // date e
    $search[]  = '/(([^%])%o|^%o)/';
    $replace[] = '${2}' . date( 'I', $date ); // date I
    $search[]  = '/(([^%])%O|^%O)/';
    $replace[] = '${2}' . date( 'O', $date ); // date O
    $search[]  = '/(([^%])%s|^%s)/';
    $replace[] = '${2}' . date( 'P', $date ); // date P
    $search[]  = '/(([^%])%v|^%v)/';
    $replace[] = '${2}' . date( 'T', $date ); // date T
    $search[]  = '/(([^%])%1|^%1)/';
    $replace[] = '${2}' . date( 'Z', $date ); // date Z
    $search[]  = '/(([^%])%2|^%2)/';
    $replace[] = '${2}' . date( 'c', $date ); // date c
    $search[]  = '/(([^%])%3|^%3)/';
    $replace[] = '${2}' . date( 'r', $date ); // date r
    $search[]  = '/(([^%])%4|^%4)/';
    $replace[] = '${2}' . $date; // date U
    $format    = preg_replace( $search, $replace, $format );

    return $before . strftime( $format, $date ) . $after;
}

/**
 * [Legacy] Generalized formatting of a date, applying qTranslate 'use_strftime' config.
 *
 * @param string $format
 * @param string $mysq_time date string in MySQL format
 * @param string $default
 * @param string $before
 * @param string $after
 *
 * @return string
 */
function qtranxf_format_date( $format, $mysq_time, $default, $before = '', $after = '' ) {
    global $q_config;
    $ts = mysql2date( 'U', $mysq_time );
    if ( $format == 'U' ) {
        return $ts;
    }

    $format = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $format );
    if ( ! empty( $format ) && $q_config['use_strftime'] == QTX_STRFTIME ) {
        $format = qtranxf_convertDateFormatToStrftimeFormat( $format );
    }

    return qtranxf_strftime( qtranxf_convertDateFormat( $format ), $ts, $default, $before, $after );
}

/**
 * [Legacy] Generalized formatting of a date, applying qTranslate 'use_strftime' config.
 *
 * @param string $format
 * @param string $mysq_time time string in MySQL format
 * @param string $default
 * @param string $before
 * @param string $after
 *
 * @return string
 */
function qtranxf_format_time( $format, $mysq_time, $default, $before = '', $after = '' ) {
    global $q_config;
    $ts = mysql2date( 'U', $mysq_time );
    if ( $format == 'U' ) {
        return $ts;
    }
    $format = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $format );
    if ( ! empty( $format ) && $q_config['use_strftime'] == QTX_STRFTIME ) {
        $format = qtranxf_convertDateFormatToStrftimeFormat( $format );
    }

    return qtranxf_strftime( qtranxf_convertTimeFormat( $format ), $ts, $default, $before, $after );
}

// @see get_the_date
function qtranxf_dateFromPostForCurrentLanguage( $old_date, $format = '', $post = null ) {
    $post = get_post( $post );
    if ( ! $post ) {
        return $old_date;
    }

    return qtranxf_format_date( $format, $post->post_date, $old_date );
}

// @see get_the_modified_date
function qtranxf_dateModifiedFromPostForCurrentLanguage( $old_date, $format = '' ) {
    global $post;
    if ( ! $post ) {
        return $old_date;
    }

    return qtranxf_format_date( $format, $post->post_modified, $old_date );
}

// @see get_the_time
function qtranxf_timeFromPostForCurrentLanguage( $old_date, $format = '', $post = null, $gmt = false ) {
    $post = get_post( $post );
    if ( ! $post ) {
        return $old_date;
    }
    $post_date = $gmt ? $post->post_date_gmt : $post->post_date;

    return qtranxf_format_time( $format, $post_date, $old_date );
}

// @see get_post_modified_time
function qtranxf_timeModifiedFromPostForCurrentLanguage( $old_date, $format = '', $gmt = false ) {
    global $post;
    if ( ! $post ) {
        return $old_date;
    }
    $post_date = $gmt ? $post->post_modified_gmt : $post->post_modified;

    return qtranxf_format_time( $format, $post_date, $old_date );
}

// @see get_comment_date
function qtranxf_dateFromCommentForCurrentLanguage( $old_date, $format, $comment = null ) {
    if ( ! $comment ) {
        global $comment;  // TODO drop obsolete compatibility with older WP
    }
    if ( ! $comment ) {
        return $old_date;
    }

    return qtranxf_format_date( $format, $comment->comment_date, $old_date );
}

// @see get_comment_time
function qtranxf_timeFromCommentForCurrentLanguage( $old_date, $format = '', $gmt = false, $translate = true, $comment = null ) {
    if ( ! $translate ) {
        return $old_date;
    }
    if ( ! $comment ) {
        global $comment; // TODO drop obsolete compatibility with older WP
    }
    if ( ! $comment ) {
        return $old_date;
    }
    $comment_date = $gmt ? $comment->comment_date_gmt : $comment->comment_date;

    return qtranxf_format_time( $format, $comment_date, $old_date );
}
