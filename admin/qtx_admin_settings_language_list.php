<?php

class QTX_Admin_Settings_Language_List extends WP_List_Table {
	private $clean_uri;
	private $language_names;

	public function __construct( $language_names, $clean_uri ) {
		parent::__construct( array( 'screen' => 'language' ) );
		$this->language_names = $language_names;
		$this->clean_uri      = $clean_uri;
	}

	public function get_columns() {
		return array(
			'code'   => _x( 'Code', 'Two-letter Language Code meant.', 'qtranslate' ),
			'flag'   => __( 'Flag', 'qtranslate' ),
			'name'   => __( 'Name', 'qtranslate' ),
			'action' => __( 'Action', 'qtranslate' ),
			'edit'   => __( 'Edit', 'qtranslate' ),
			'stored' => __( 'Stored', 'qtranslate' )
		);
	}

	public function prepare_items() {
		global $q_config;

		$flags                 = qtranxf_language_configured( 'flag' );
		$languages_stored      = get_option( 'qtranslate_language_names', array() );
		$languages_predef      = qtranxf_default_language_name();
		$flag_location_url     = qtranxf_flag_location();
		$flag_location_dir     = trailingslashit( WP_CONTENT_DIR ) . $q_config['flag_location'];
		$flag_location_url_def = content_url( qtranxf_flag_location_default() );
		$clean_uri             = $this->clean_uri;
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
			$data[] = array(
				'code'   => $lang,
				'flag'   => '<img src="' . $flag_url . '" alt="' . sprintf( __( '%s Flag', 'qtranslate' ), $language ) . '">',
				'name'   => $language,
				'action' => in_array( $lang, $q_config['enabled_languages'] ) ? ( $q_config['default_language'] == $lang ? __( 'Default', 'qtranslate' ) : '<a class="edit" href="' . $clean_uri . '&disable=' . $lang . '#languages">' . __( 'Disable', 'qtranslate' ) . '</a>' ) : '<a class="edit" href="' . $clean_uri . '&enable=' . $lang . '#languages">' . __( 'Enable', 'qtranslate' ) . '</a>',
				'edit'   => '<a class="edit" href="' . $clean_uri . '&edit=' . $lang . '">' . __( 'Edit', 'qtranslate' ) . '</a>',
				'stored' => ! isset( $languages_stored[ $lang ] ) ? __( 'Pre-Defined', 'qtranslate' ) : '<a class="delete" href="' . $clean_uri . '&delete=' . $lang . '#languages">' . ( isset( $languages_predef[ $lang ] ) ? __( 'Reset', 'qtranslate' ) : __( 'Delete', 'qtranslate' ) ) . '</a>'
			);
		}
		$this->items = $data;
	}

	protected function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	protected function get_default_primary_column_name() {
		return 'name';
	}

	protected function display_tablenav( $which ) {
	}

	protected function get_table_classes() {
		return array( 'widefat', 'qtranxs-language-list' );
	}
}
