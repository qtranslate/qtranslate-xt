<?php

/**
 * Class QTX_Admin_Settings_Language_List
 *
 * Display the list of available languages in the admin options page
 */
class QTX_Admin_Settings_Language_List extends WP_List_Table {

    /**
     * @var string URI to the admin options page of qTranslate-XT
     */
    private $options_uri;

    /**
     * @var array names of the available languages, translated in the current admin language
     */
    private $language_names;

    public function __construct( $language_names, $options_uri ) {
        parent::__construct( array( 'screen' => 'language' ) );
        $this->language_names = $language_names;
        $this->options_uri    = $options_uri;
    }

    public function get_columns(): array {
        return array(
            'code'   => _x( 'Code', 'Two-letter Language Code meant.', 'qtranslate' ),
            'flag'   => __( 'Flag', 'qtranslate' ),
            'locale' => __( 'Locale', 'qtranslate' ),
            'name'   => __( 'Name', 'qtranslate' ),
            'status' => __( 'Status', 'qtranslate' ),
            'action' => __( 'Action', 'qtranslate' ),
            'edit'   => __( 'Edit', 'qtranslate' ),
            'stored' => __( 'Stored', 'qtranslate' )
        );
    }

    public function prepare_items(): void {
        global $q_config;

        $flags                 = qtranxf_language_configured( 'flag' );
        $locales               = qtranxf_language_configured( 'locale' );
        $languages_stored      = get_option( 'qtranslate_language_names', array() );
        $languages_predef      = qtranxf_default_language_name();
        $flag_location_url     = qtranxf_flag_location();
        $flag_location_dir     = trailingslashit( WP_CONTENT_DIR ) . $q_config['flag_location'];
        $flag_location_url_def = content_url( qtranxf_flag_location_default() );
        $options_uri           = $this->options_uri;
        $data                  = array();

        foreach ( $this->language_names as $lang => $language ) {
            if ( $lang == 'code' ) {
                continue;
            }
            $flag = $flags[ $lang ];
            if ( file_exists( $flag_location_dir . $flag ) ) {
                $flag_url = $flag_location_url . $flag;
            } else {
                $flag_url = $flag_location_url_def . $flag;
            }
            $flag_item = '<img src="' . $flag_url . '" alt="' . sprintf( __( '%s Flag', 'qtranslate' ), $language ) . '">';

            if ( isset( $q_config['locale'][ $lang ] ) ) {
                $locale_item = $q_config['locale'][ $lang ];
            } else {
                $locale_item = $locales[ $lang ] ?? '?';
            }

            $icon_enable  = 'dashicons dashicons-insert';
            $icon_disable = 'dashicons dashicons-remove';
            if ( in_array( $lang, $q_config['enabled_languages'] ) ) {
                if ( $q_config['default_language'] == $lang ) {
                    $status = '<span class="dashicons dashicons-star-filled" title="' . esc_attr( __( 'Default', 'qtranslate' ) ) . '"></span>';
                    $action = '<span class="disabled ' . $icon_disable . '"></span>';
                    $action .= '<span class="disabled ' . $icon_enable . '"></span>';
                } else {
                    $status = '<span class="dashicons dashicons-star-empty" title="' . esc_attr( __( 'Enabled', 'qtranslate' ) ) . '"></span>';
                    $action = '<a class="edit" href="' . $options_uri . '&disable=' . $lang . '#languages" title="' . esc_attr( __( 'Disable', 'qtranslate' ) ) . '"><span class="' . $icon_disable . '"></span></a>';
                    $action .= '<span class="disabled ' . $icon_enable . '"></span>';
                }
            } else {
                $status = '';
                $action = '<span class="disabled ' . $icon_disable . '"></span>';
                $action .= '<a class="edit" href="' . $options_uri . '&enable=' . $lang . '#languages" title="' . esc_attr( __( 'Enable', 'qtranslate' ) ) . '"><span class="' . $icon_enable . '"></span></a>';
            }

            $edit = '<a class="edit" href="' . $options_uri . '&edit=' . $lang . '" title="' . esc_attr( __( 'Edit', 'qtranslate' ) ) . '"><span class="dashicons dashicons-edit"></span></a>';

            if ( ! isset( $languages_stored[ $lang ] ) ) {
                $stored = '<span class="dashicons dashicons-saved" title="' . __( 'Pre-Defined', 'qtranslate' ) . '"></span>';
            } else {
                $label  = isset( $languages_predef[ $lang ] ) ? __( 'Reset', 'qtranslate' ) : __( 'Delete', 'qtranslate' );
                $icon   = isset( $languages_predef[ $lang ] ) ? 'dashicons-undo' : 'dashicons-trash';
                $stored = '<a class="delete" href="' . $options_uri . '&delete=' . $lang . '#languages" title="' . esc_attr( $label ) . '"><span class="dashicons ' . $icon . '"></span></a>';
            }

            $data[] = array(
                'code'   => $lang,
                'flag'   => $flag_item,
                'name'   => $language,
                'locale' => $locale_item,
                'status' => $status,
                'action' => $action,
                'edit'   => $edit,
                'stored' => $stored
            );
        }
        $this->items = $data;
    }

    protected function column_default( $item, $column_name ): string {
        return $item[ $column_name ];
    }

    protected function get_default_primary_column_name(): string {
        return 'name';
    }

    protected function display_tablenav( $which ): void {
    }

    protected function get_table_classes(): array {
        return array( 'widefat', 'qtranxs-language-list' );
    }
}
