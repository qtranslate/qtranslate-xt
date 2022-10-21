<?php

/**
 * Locale-formatted strftime using \IntlDateFormatter (PHP 8.1 compatible)
 * This provides a cross-platform alternative to strftime() for when it will be removed from PHP.
 * Note that output can be slightly different between libc sprintf and this function as it is using ICU.
 * Non-standard strftime: '%q' is a qTranslate format to mimic date 'S'.
 *
 * Usage:
 * echo strftime('%A %e %B %Y %X', new \DateTime('2021-09-28 00:00:00'), 'fr_FR');
 *
 * Original use:
 * \setlocale('fr_FR.UTF-8', LC_TIME);
 * echo \strftime('%A %e %B %Y %X', strtotime('2021-09-28 00:00:00'));
 *
 * @param string $format Date format
 * @param integer|string|DateTime $timestamp Timestamp
 *
 * @return string
 * @deprecated Avoid using this function, meant to transition from legacy strftime formats - might be removed.
 * @see https://gist.github.com/bohwaz/42fc223031e2b2dd2585aab159a20f30 (for the original code).
 */
function qxtranxf_intl_strftime( $format, $timestamp = null, $locale = null ) {
    if ( null === $timestamp ) {
        $timestamp = new \DateTime;
    } elseif ( is_numeric( $timestamp ) ) {
        $timestamp = date_create( '@' . $timestamp );

        if ( $timestamp ) {
            $timestamp->setTimezone( new \DateTimezone( date_default_timezone_get() ) );
        }
    } elseif ( is_string( $timestamp ) ) {
        $timestamp = date_create( $timestamp );
    }

    if ( ! ( $timestamp instanceof \DateTimeInterface ) ) {
        throw new \InvalidArgumentException( '$timestamp argument is neither a valid UNIX timestamp, a valid date-time string or a DateTime object.' );
    }

    $locale = substr( (string) $locale, 0, 5 );

    $intl_formats = [
        '%a' => 'EEE',    // An abbreviated textual representation of the day	Sun through Sat
        '%A' => 'EEEE',    // A full textual representation of the day	Sunday through Saturday
        '%b' => 'MMM',    // Abbreviated month name, based on the locale	Jan through Dec
        '%B' => 'MMMM',    // Full month name, based on the locale	January through December
        '%h' => 'MMM',    // Abbreviated month name, based on the locale (an alias of %b)	Jan through Dec
    ];

    // \DateTimeInterface, string
    $intl_formatter = function ( $timestamp, $format ) use ( $intl_formats, $locale ) {
        $tz        = $timestamp->getTimezone();
        $date_type = \IntlDateFormatter::FULL;
        $time_type = \IntlDateFormatter::FULL;
        $pattern   = '';

        // %c = Preferred date and time stamp based on locale
        // Example: Tue Feb 5 00:45:10 2009 for February 5, 2009 at 12:45:10 AM
        if ( $format == '%c' ) {
            $date_type = \IntlDateFormatter::LONG;
            $time_type = \IntlDateFormatter::SHORT;
        }
        // %x = Preferred date representation based on locale, without the time
        // Example: 02/05/09 for February 5, 2009
        elseif ( $format == '%x' ) {
            $date_type = \IntlDateFormatter::SHORT;
            $time_type = \IntlDateFormatter::NONE;
        } // Localized time format
        elseif ( $format == '%X' ) {
            $date_type = \IntlDateFormatter::NONE;
            $time_type = \IntlDateFormatter::MEDIUM;
        } else {
            $pattern = $intl_formats[ $format ];
        }

        return ( new \IntlDateFormatter( $locale, $date_type, $time_type, $tz, null, $pattern ) )->format( $timestamp );
    };

    // Same order as https://www.php.net/manual/en/function.strftime.php
    $translation_table = [
        // Day
        '%a' => $intl_formatter,
        '%A' => $intl_formatter,
        '%d' => 'd',
        '%e' => function ( $timestamp ) {
            return sprintf( '% 2u', $timestamp->format( 'j' ) );
        },
        '%j' => function ( $timestamp ) {
            // Day number in year, 001 to 366
            return sprintf( '%03d', $timestamp->format( 'z' ) + 1 );
        },
        '%u' => 'N',
        '%w' => 'w',

        // Week
        '%U' => function ( $timestamp ) {
            // Number of weeks between date and first Sunday of year
            $day = new \DateTime( sprintf( '%d-01 Sunday', $timestamp->format( 'Y' ) ) );

            return sprintf( '%02u', 1 + ( $timestamp->format( 'z' ) - $day->format( 'z' ) ) / 7 );
        },
        '%V' => 'W',
        '%W' => function ( $timestamp ) {
            // Number of weeks between date and first Monday of year
            $day = new \DateTime( sprintf( '%d-01 Monday', $timestamp->format( 'Y' ) ) );

            return sprintf( '%02u', 1 + ( $timestamp->format( 'z' ) - $day->format( 'z' ) ) / 7 );
        },

        // Month
        '%b' => $intl_formatter,
        '%B' => $intl_formatter,
        '%h' => $intl_formatter,
        '%m' => 'm',

        // Year
        '%C' => function ( $timestamp ) {
            // Century (-1): 19 for 20th century
            return floor( $timestamp->format( 'Y' ) / 100 );
        },
        '%g' => function ( $timestamp ) {
            return substr( $timestamp->format( 'o' ), -2 );
        },
        '%G' => 'o',
        '%y' => 'y',
        '%Y' => 'Y',

        // Time
        '%H' => 'H',
        '%k' => function ( $timestamp ) {
            return sprintf( '% 2u', $timestamp->format( 'G' ) );
        },
        '%I' => 'h',
        '%l' => function ( $timestamp ) {
            return sprintf( '% 2u', $timestamp->format( 'g' ) );
        },
        '%M' => 'i',
        '%p' => 'A', // AM PM (this is reversed on purpose!)
        '%P' => 'a', // am pm
        '%r' => 'h:i:s A', // %I:%M:%S %p
        '%R' => 'H:i', // %H:%M
        '%S' => 's',
        '%T' => 'H:i:s', // %H:%M:%S
        '%X' => $intl_formatter, // Preferred time representation based on locale, without the date

        // Timezone
        '%z' => 'O',
        '%Z' => 'T',

        // Time and Date Stamps
        '%c' => $intl_formatter,
        '%D' => 'm/d/Y',
        '%F' => 'Y-m-d',
        '%s' => 'U',
        '%x' => $intl_formatter,

        // QTranslate: non-standard strftime
        '%E' => 'j', // Day number no zero
        '%q' => 'S', // Day english ordinal
        '%f' => 'w', // Week number
        '%v' => 'T', // Timezone abbreviation, if known; otherwise the GMT offset.
        '%i' => 'n', // Numeric representation of a month, without leading zeros
        '%J' => 't', // Number of days in the given month
        '%K' => 'B', // Swatch internet time 000-999
        '%L' => 'G', // 24-hour format of an hour without leading zeros ---> %L should be %k!
        '%N' => 'u', // Microseconds
        '%Q' => 'e', // Timezone identifier
        '%o' => 'I', // 1 if Daylight Saving Time, 0 otherwise.
        '%O' => 'O', // Difference to Greenwich time (GMT) without colon between hours and minutes
        '%1' => 'Z', // Timezone offset in seconds
        '%2' => 'c', // ISO 8601 date
        '%3' => 'r', // RFC 2822/» RFC 5322 formatted date
        '%4' => 'U',  // Seconds since the Unix Epoch
    ];

    $out = preg_replace_callback( '/(?<!%)(%[a-zA-Z])/', function ( $match ) use ( $translation_table, $timestamp ) {
        if ( $match[1] == '%n' ) {
            return "\n";
        } elseif ( $match[1] == '%t' ) {
            return "\t";
        }

        if ( ! isset( $translation_table[ $match[1] ] ) ) {
            throw new \InvalidArgumentException( sprintf( 'Format "%s" is unknown in time format', $match[1] ) );
        }

        $replace = $translation_table[ $match[1] ];

        if ( is_string( $replace ) ) {
            return $timestamp->format( $replace );
        } else {
            return $replace( $timestamp, $match[1] );
        }
    }, $format );

    $out = str_replace( '%%', '%', $out );

    return $out;
}

/**
 * [Legacy] Converter of a format given in DateTime format, transformed to the extended "QTX-strftime" format.
 *
 * @param string $format in DateTime format.
 *
 * @return string
 * @deprecated Don't use strftime formats anymore, since strftime is deprecated from PHP8.1.
 * @see https://www.php.net/manual/en/function.strftime.php
 * @see https://www.php.net/manual/en/datetime.format.php
 */
function qtranxf_convertDateFormatToStrftimeFormat( $format ) {
    $mappings = array(
        // day
        'd' => '%d',
        'D' => '%a',
        'l' => '%A',
        'N' => '%u',
        // week
        'W' => '%V',
        // month
        'F' => '%B',
        'm' => '%m',
        'M' => '%b',
        // year
        'o' => '%G',
        'Y' => '%Y',
        'y' => '%y',
        // time
        'a' => '%P',
        'A' => '%p',
        'g' => '%l',
        'h' => '%I',
        'H' => '%H',
        'i' => '%M',
        's' => '%S',
        // QTranslate: override strftime, not consistent with date formats :-/
        'z' => '%F', // z: The day of the year (starting from 0) -- %F: Same as "%Y-%m-%d
        'P' => '%s', // P: Difference to Greenwich time (GMT) with colon between hours and minutes -- %s: unix timestamp
        'L' => '%k', // L: leap year -- %k: Hour in 24-hour format, single digit
        // QTranslate: non-standard strftime to mimic some date formats
        'j' => '%E', // Day number no zero
        'S' => '%q', // Day english ordinal
        'w' => '%f', // Week number
        'T' => '%v', // Timezone abbreviation, if known; otherwise the GMT offset.
        'n' => '%i', // Numeric representation of a month, without leading zeros
        't' => '%J', // Number of days in the given month
        'B' => '%K', // Swatch internet time 000-999
        'G' => '%L', // 24-hour format of an hour without leading zeros --> %L should be %k!
        'u' => '%N', // Microseconds
        'e' => '%Q', // Timezone identifier
        'I' => '%o', // 1 if Daylight Saving Time, 0 otherwise.
        'O' => '%O', // Difference to Greenwich time (GMT) without colon between hours and minutes
        'Z' => '%1', // Timezone offset in seconds
        'c' => '%2', // ISO 8601 date
        'r' => '%3', // RFC 2822/» RFC 5322 formatted date
        'U' => '%4'  // Seconds since the Unix Epoch
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
 * @deprecated Don't use strftime formats anymore, since strftime is deprecated from PHP8.1.
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
            if ( empty( $format ) ) {
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
 * Return the date or time format set for the current language config or default language.
 *
 * @param string $config_key
 *
 * @return string
 */
function qtranxf_get_language_date_or_time_format( $config_key ) {
    assert( $config_key == 'date_format' || $config_key == 'time_format' );
    global $q_config;
    if ( isset( $q_config[ $config_key ][ $q_config['language'] ] ) ) {
        return $q_config[ $config_key ][ $q_config['language'] ];
    } elseif ( isset( $q_config[ $config_key ][ $q_config['default_language'] ] ) ) {
        return $q_config[ $config_key ][ $q_config['default_language'] ];
    } else {
        return '';
    }
}

/**
 * [Legacy] Converter of a date format to "QTX-strftime" format, applying qTranslate 'use_strftime' configuration.
 *
 * @param string $format
 *
 * @return string
 * @deprecated Use qtranxf_get_language_date_or_time_format.
 */
function qtranxf_convertDateFormat( $format ) {
    _deprecated_function( __FUNCTION__, '3.13.0', 'qtranxf_get_language_date_or_time_format' );
    $default_format = qtranxf_get_language_date_or_time_format( 'date_format' );
    return qtranxf_convertFormat( $format, $default_format );
}

/**
 * [Legacy] Converter of a time format to "QTX-strftime" format, applying qTranslate 'use_strftime' configuration.
 *
 * @param string $format
 *
 * @return string
 * @deprecated Use qtranxf_get_language_date_or_time_format.
 */
function qtranxf_convertTimeFormat( $format ) {
    _deprecated_function( __FUNCTION__, '3.13.0', 'qtranxf_get_language_date_or_time_format' );
    $default_format = qtranxf_get_language_date_or_time_format( 'time_format' );
    return qtranxf_convertFormat( $format, $default_format );
}

/**
 * [Legacy] Extension of PHP "QTX-strftime", valid up to PHP 8.0.
 *
 * @param string $format extended strftime with additional features such as %q
 * @param int $date timestamp
 * @param string $default Default result when $format is empty.
 * @param string $before Text copied before result.
 * @param string $after Text copied after result.
 *
 * @return mixed|string
 * @deprecated Use qxtranxf_intl_strftime, since strftime is deprecated from PHP8.1.
 * @See https://www.php.net/manual/en/function.strftime.php
 */
function qtranxf_strftime( $format, $date, $default = '', $before = '', $after = '' ) {
    _deprecated_function( __FUNCTION__, '3.13.0', 'qxtranxf_intl_strftime' );

    if ( empty( $format ) ) {
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
    $replace[] = '${2}' . date( 'n', $date ); // date n
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

    // TODO: abandon strftime format in qTranslate.
    $format = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $format );
    if ( ! empty( $format ) && $q_config['use_strftime'] == QTX_STRFTIME ) {
        $format = qtranxf_convertDateFormatToStrftimeFormat( $format );
    }

    $language_format = qtranxf_get_language_date_or_time_format( 'date_format' );
    $date_format     = qtranxf_convertFormat( $format, $language_format );
    $date_output     = empty( $date_format ) ? $default : qxtranxf_intl_strftime( $date_format, $ts, get_locale() );

    return $before . $date_output . $after;
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

    // TODO: abandon strftime format in qTranslate.
    $format = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $format );
    if ( ! empty( $format ) && $q_config['use_strftime'] == QTX_STRFTIME ) {
        $format = qtranxf_convertDateFormatToStrftimeFormat( $format );
    }

    $language_format = qtranxf_get_language_date_or_time_format( 'time_format' );
    $time_format     = qtranxf_convertFormat( $format, $language_format );
    $time_output     = empty( $time_format ) ? $default : qxtranxf_intl_strftime( $time_format, $ts, get_locale() );

    return $before . $time_output . $after;
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
