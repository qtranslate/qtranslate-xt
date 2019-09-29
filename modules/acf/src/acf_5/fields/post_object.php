<?php

class acf_qtranslate_acf_5_post_object extends acf_field_post_object {

	/**
	 * The plugin instance
	 * @var acf_qtranslate_plugin
	 */
	protected $plugin;

	/**
	 * Constructor
	 *
	 * @param acf_qtranslate_plugin $plugin
	 */
	function __construct( $plugin ) {
		$this->plugin = $plugin;

		if ( version_compare( $plugin->acf_version(), '5.6.0' ) < 0 ) {
			$this->initialize();
		}

		acf_field::__construct();
	}

	/**
	 *  Setup the field type data
	 */
	function initialize() {
		$this->name     = 'qtranslate_post_object';
		$this->label    = __( "Post Object (qTranslate)", 'acf' );
		$this->category = 'qTranslate';
		$this->defaults = array(
			'post_type'     => array(),
			'taxonomy'      => array(),
			'allow_null'    => 0,
			'multiple'      => 0,
			'return_format' => 'object',
			'ui'            => 1,
		);

		add_action( 'wp_ajax_acf/fields/qtranslate_post_object/query', array( $this, 'ajax_query' ) );
		add_action( 'wp_ajax_nopriv_acf/fields/qtranslate_post_object/query', array( $this, 'ajax_query' ) );
	}

	/**
	 * Hook/override ACF render_field to create the HTML interface
	 *
	 * @param array $field
	 */
	function render_field( $field ) {
		global $q_config;
		$languages       = qtranxf_getSortedLanguages( true );
		$decoded         = $this->plugin->decode_language_values( $field['value'] );
		$values          = array_map( 'maybe_unserialize', $decoded );
		$currentLanguage = $this->plugin->get_active_language();

		$atts = array(
			'id'   => $field['id'],
			'name' => $field['name'],
		);

		echo '<div class="multi-language-field multi-language-field-post-object">';

		foreach ( $languages as $language ) {
			$class = ( $language === $currentLanguage ) ? 'wp-switch-editor current-language' : 'wp-switch-editor';
			echo '<a class="' . $class . '" data-language="' . $language . '">' . $q_config['language_name'][ $language ] . '</a>';
		}

		foreach ( $languages as $language ) {
			$class          = ( $language === $currentLanguage ) ? 'acf-post-object current-language' : 'acf-post-object';
			$field['id']    = $atts['id'] . "-$language";
			$field['name']  = $atts['name'] . "[$language]";
			$field['value'] = $values[ $language ];

			echo '<div class="' . $class . '" data-language="' . $language . '">';
			parent::render_field( $field );
			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * Hook/override ACF format_value
	 *
	 * This filter is applied to the $value after it is loaded from the db and
	 * before it is returned to the template via functions such as get_field().
	 *
	 * @param $value
	 * @param $post_id
	 * @param $field
	 *
	 * @return array|mixed|string|void
	 */
	function format_value( $value, $post_id, $field ) {
		$value = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $value );
		$value = maybe_unserialize( $value );

		return parent::format_value( $value, $post_id, $field );
	}

	/**
	 * Hook/override ACF update_value
	 *
	 * @param array $values - the values to save in database
	 * @param int $post_id - the post_id of which the value will be saved
	 * @param array $field - the field array holding all the field options
	 *
	 * @return string - the modified value
	 */
	function update_value( $values, $post_id, $field ) {
		assert( is_array( $values ) );

		// TODO validation seems unnecessary here, keep until assert has been used for some time
		if ( ! is_array( $values ) ) {
			return false;
		}

		foreach ( $values as &$value ) {
			$value = parent::update_value( $value, $post_id, $field );
			$value = maybe_serialize( $value );
		}

		return $this->plugin->encode_language_values( $values );
	}

	/**
	 *  Hook/override ACF validation to handle the value formatted to a multi-lang array instead of string
	 *
	 * @param bool|string $valid
	 * @param array $value containing values per language
	 * @param string $field
	 * @param string $input
	 *
	 * @return bool|string
	 * @see acf_validation::acf_validate_value
	 */
	function validate_value( $valid, $value, $field, $input ) {
		if ( is_array( $value ) ) {
			$valid = $this->plugin->validate_language_values( $this, $valid, $value, $field, $input );
		}

		return $valid;
	}
}
