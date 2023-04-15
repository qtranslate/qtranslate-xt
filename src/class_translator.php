<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once QTRANSLATE_DIR . '/src/translator_interface.php';

/**
 * Implementation of QTX_Translator_Interface interface.
 * For a function documentation look up definition of QTX_Translator_Interface.
 * @since 3.4
 */
class QTX_Translator implements QTX_Translator_Interface {
    public function __construct() {
        add_filter( 'translate_text', array( $this, 'translate_text' ), 10, 3 );
        add_filter( 'translate_term', array( $this, 'translate_term' ), 10, 3 );
        add_filter( 'translate_url', array( $this, 'translate_url' ), 10, 2 );
        // TODO what about 'translate_date' and 'translate_time'?
    }

    public static function get_translator() {
        global $q_config;
        if ( ! isset( $q_config['translator'] ) ) {
            $q_config['translator'] = new QTX_Translator;
        }

        return $q_config['translator'];
    }

    public function get_language(): string {
        global $q_config;

        return $q_config['language'];
    }

    public function set_language( string $lang ): string {
        global $q_config;
        $lang_curr = $q_config['language'];
        if ( qtranxf_isEnabled( $lang ) ) {
            $q_config['language'] = $lang;
        }

        return $lang_curr;
    }

    public function translate_text( $text, ?string $lang = null, int $flags = 0 ): string {
        global $q_config;
        if ( ! $lang ) {
            $lang = $q_config['language'];
        }
        $show_available = $flags & QTX_TRANSLATOR_SHOW_AVAILABLE;
        $show_empty     = $flags & QTX_TRANSLATOR_SHOW_EMPTY;

        return qtranxf_use( $lang, $text, $show_available, $show_empty );
    }

    public function translate_term( $term, ?string $lang = null, ?string $taxonomy = null ): string {
        global $q_config;
        if ( ! $lang ) {
            $lang = $q_config['language'];
        }

        return qtranxf_term_use( $lang, $term, $taxonomy );
    }

    public function translate_url( $url, ?string $lang = null ): string {
        global $q_config;
        if ( $lang ) {
            $showLanguage = true;
        } else {
            $lang         = $q_config['language'];
            $showLanguage = ! $q_config['hide_default_language'] || $lang != $q_config['default_language'];
        }

        return qtranxf_get_url_for_language( $url, $lang, $showLanguage );
    }
}
