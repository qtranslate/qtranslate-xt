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
 * @todo Maybe deprecate. Avoid using this function, meant to transition from legacy strftime formats.
 * @see https://gist.github.com/bohwaz/42fc223031e2b2dd2585aab159a20f30 (for the original code).
 */
function qxtranxf_intl_strftime( string $format, $timestamp = null, ?string $locale = null ): string {
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
    $intl_formatter = function ( DateTimeInterface $timestamp, string $format ) use ( $intl_formats, $locale ) {
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

    return str_replace( '%%', '%', $out );
}

/**
 * Converter of a format given in DateTime format, transformed to the extended "QTX-strftime" format.
 *
 * @param string $format in DateTime format. Format characters can be quoted with backslashes.
 *
 * @return string
 * @todo Maybe deprecate. Don't use strftime formats anymore, since strftime is deprecated from PHP8.1.
 * @see https://www.php.net/manual/en/function.strftime.php
 * @see https://www.php.net/manual/en/datetime.format.php
 */
function qtranxf_convert_date_format_to_strftime_format( string $format ): string {
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
        // Format characters can be quoted with backslashes.
        $date_parameters[]     = '#(([^%\\\\])' . $df . '|^' . $df . ')#';
        $strftime_parameters[] = '${2}' . $sf;
    }
    // convert everything
    $format = preg_replace( $date_parameters, $strftime_parameters, $format );
    // Remove single backslashes and convert double to single.
    return stripslashes( $format );
}

/**
 * Converter of a format/default pair to "QTX-strftime" format, applying 'use_strftime' configuration.
 *
 * @param string $format ATTENTION - always given in date PHP format.
 * @param string $default_format language format following the 'use_strftime' configuration.
 *
 * @return string format in strftime
 * @todo Maybe deprecate. Don't use strftime formats anymore, since strftime is deprecated from PHP8.1.
 */
function qtranxf_convert_to_strftime_format_using_config( string $format, string $default_format ): string {
    global $q_config;
    // If one of special language-neutral formats is requested, don't override it.
    switch ( $format ) {
        case 'Z':
        case 'c':
        case 'r':
        case 'U':
            return qtranxf_convert_date_format_to_strftime_format( $format );
    }
    // Attention - this part is quite tricky.
    // The user format is always given in date format, but not the language format which depends on use_strftime settings.
    // The language format may contain escape backslash characters that must be unquoted in any case.
    switch ( $q_config['use_strftime'] ) {
        case QTX_DATE:
            // Convert both.
            return qtranxf_convert_date_format_to_strftime_format( ! empty( $format ) ? $format : $default_format );
        case QTX_DATE_OVERRIDE:
            return qtranxf_convert_date_format_to_strftime_format( $default_format );
        case QTX_STRFTIME:
            return ( ! empty( $format ) ? qtranxf_convert_date_format_to_strftime_format( $format ) : stripslashes( $default_format ) );
        case QTX_STRFTIME_OVERRIDE:
            return stripslashes( $default_format );
        case QTX_DATE_WP:
        default:
            return '';
    }
}

/**
 * Return the date or time format set for the current language config or default language.
 *
 * @param string $config_key
 *
 * @return string
 */
function qtranxf_get_language_date_or_time_format( string $config_key ): string {
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
 * [Legacy] Generalized formatting of a date, applying qTranslate 'use_strftime' config.
 *
 * @param string $format
 * @param string $mysql_time date string in MySQL format
 * @param string $default_value default date value.
 * @param string $before Deprecated. Not used, will be removed in a future version.
 * @param string $after Deprecated. Not used, will be removed in a future version.
 *
 * @return string date/time if the format is valid, default value otherwise.
 */
function qtranxf_format_date( string $format, string $mysql_time, string $default_value, string $before = '', string $after = '' ): string {
    if ( ! empty( $before ) || ! empty( $after ) ) {
        _deprecated_argument( __FUNCTION__, '3.13.0' );
    }
    $timestamp = mysql2date( 'U', $mysql_time );
    if ( $format == 'U' ) {
        return $timestamp;
    }
    $language_format = qtranxf_get_language_date_or_time_format( 'date_format' );
    // TODO: abandon strftime format in qTranslate.
    $date_format = qtranxf_convert_to_strftime_format_using_config( $format, $language_format );
    return ( ! empty( $date_format ) ? qxtranxf_intl_strftime( $date_format, $timestamp, get_locale() ) : $default_value );
}

/**
 * [Legacy] Generalized formatting of a date, applying qTranslate 'use_strftime' config.
 *
 * @param string $format
 * @param string $mysql_time time string in MySQL format
 * @param string $default_value default time value.
 * @param string $before Deprecated. Not used, will be removed in a future version.
 * @param string $after Deprecated. Not used, will be removed in a future version.
 *
 * @return string date/time if the format is valid, default value otherwise.
 */
function qtranxf_format_time( string $format, string $mysql_time, string $default_value, string $before = '', string $after = '' ): string {
    if ( ! empty( $before ) || ! empty( $after ) ) {
        _deprecated_argument( __FUNCTION__, '3.13.0' );
    }
    $timestamp = mysql2date( 'U', $mysql_time );
    if ( $format == 'U' ) {
        return $timestamp;
    }
    $language_format = qtranxf_get_language_date_or_time_format( 'time_format' );
    // TODO: abandon strftime format in qTranslate.
    $date_format = qtranxf_convert_to_strftime_format_using_config( $format, $language_format );
    return ( ! empty( $date_format ) ? qxtranxf_intl_strftime( $date_format, $timestamp, get_locale() ) : $default_value );
}

// @see get_the_date
function qtranxf_dateFromPostForCurrentLanguage( string $old_date, string $format = '', $post = null ): string {
    $post = get_post( $post );
    if ( ! $post ) {
        return $old_date;
    }

    return qtranxf_format_date( $format, $post->post_date, $old_date );
}

// @see get_the_modified_date
function qtranxf_dateModifiedFromPostForCurrentLanguage( string $old_date, string $format = '' ): string {
    global $post;
    if ( ! $post ) {
        return $old_date;
    }

    return qtranxf_format_date( $format, $post->post_modified, $old_date );
}

// @see get_the_time
function qtranxf_timeFromPostForCurrentLanguage( string $old_date, string $format = '', $post = null, bool $gmt = false ): string {
    $post = get_post( $post );
    if ( ! $post ) {
        return $old_date;
    }
    $post_date = $gmt ? $post->post_date_gmt : $post->post_date;

    return qtranxf_format_time( $format, $post_date, $old_date );
}

// @see get_post_modified_time
function qtranxf_timeModifiedFromPostForCurrentLanguage( string $old_date, string $format = '', bool $gmt = false ): string {
    global $post;
    if ( ! $post ) {
        return $old_date;
    }
    $post_date = $gmt ? $post->post_modified_gmt : $post->post_modified;

    return qtranxf_format_time( $format, $post_date, $old_date );
}

// @see get_comment_date
function qtranxf_dateFromCommentForCurrentLanguage( string $old_date, string $format, ?string $comment = null ): string {
    if ( ! $comment ) {
        global $comment;  // TODO drop obsolete compatibility with older WP
    }
    if ( ! $comment ) {
        return $old_date;
    }

    return qtranxf_format_date( $format, $comment->comment_date, $old_date );
}

// @see get_comment_time
function qtranxf_timeFromCommentForCurrentLanguage( string $old_date, string $format = '', bool $gmt = false, bool $translate = true, ?string $comment = null ): string {
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

/**
 * Add date/time filters if the configuration allows it.
 *
 * @return void
 */
function qtranxf_add_date_time_filters(): void {
    global $q_config;

    if ( $q_config['use_strftime'] != QTX_DATE_WP && class_exists( 'IntlDateFormatter' ) ) {
        add_filter( 'get_comment_date', 'qtranxf_dateFromCommentForCurrentLanguage', 0, 3 );
        add_filter( 'get_comment_time', 'qtranxf_timeFromCommentForCurrentLanguage', 0, 5 );
        add_filter( 'get_post_modified_time', 'qtranxf_timeModifiedFromPostForCurrentLanguage', 0, 3 );
        add_filter( 'get_the_time', 'qtranxf_timeFromPostForCurrentLanguage', 0, 3 );
        add_filter( 'get_the_date', 'qtranxf_dateFromPostForCurrentLanguage', 0, 3 );
        add_filter( 'get_the_modified_date', 'qtranxf_dateModifiedFromPostForCurrentLanguage', 0, 2 );
    }
}
