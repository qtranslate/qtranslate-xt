<?php

class acf_qtranslate_acf_5_textarea extends acf_field_textarea {

	/**
	 * The plugin instance.
	 * @var \acf_qtranslate\plugin
	 */
	protected $plugin;


	/*
	 *  __construct
	 *
	 *  This function will setup the field type data
	 *
	 *  @type	function
	 *  @date	5/03/2014
	 *  @since	5.0.0
	 *
	 *  @param	n/a
	 *  @return	n/a
	 */
	function __construct($plugin) {
		$this->plugin = $plugin;

		if (version_compare($plugin->acf_version(), '5.6.0') < 0) {
			$this->initialize();
		}

		acf_field::__construct();
	}

	/*
	 *  initialize
	 *
	 *  This function will setup the field type data
	 *
	 *  @type	function
	 *  @date	5/03/2014
	 *  @since	5.0.0
	 *
	 *  @param	n/a
	 *  @return	n/a
	 */
	function initialize() {

		// vars
		$this->name = 'qtranslate_textarea';
		$this->label = __("Text Area (qTranslate)",'acf');
		$this->category = __("qTranslate",'acf');
		$this->defaults = array(
			'default_value'	=> '',
			'new_lines'		=> '',
			'maxlength'		=> '',
			'placeholder'	=> '',
			'rows'			=> ''
		);

	}

	/*
	 *  render_field()
	 *
	 *  Create the HTML interface for your field
	 *
	 *  @param	$field - an array holding all the field's data
	 *
	 *  @type	action
	 *  @since	3.6
	 *  @date	23/01/13
	 */
	function render_field($field) {
		global $q_config;
		$languages = qtrans_getSortedLanguages(true);
		$values = qtrans_split($field['value'], $quicktags = true);
		$currentLanguage = $this->plugin->get_active_language();

		// vars
		$o = array( 'id', 'class', 'name', 'placeholder', 'rows' );
		$s = array( 'readonly', 'disabled' );
		$e = '';

		// maxlength
		if( $field['maxlength'] !== '' ) {
			$o[] = 'maxlength';
		}

		// rows
		if( empty($field['rows']) ) {
			$field['rows'] = 8;
		}

		// populate atts
		$atts = array();
		foreach( $o as $k ) {
			$atts[ $k ] = $field[ $k ];
		}

		// special atts
		foreach( $s as $k ) {
			if( isset($field[ $k ]) && $field[ $k ] ) {
				$atts[ $k ] = $k;
			}
		}

		// render
		$e .= '<div class="acf-input-wrap multi-language-field">';

		foreach ($languages as $language) {
			$class = ($language === $currentLanguage) ? 'wp-switch-editor current-language' : 'wp-switch-editor';
			$e .= '<a class="' . $class . '" data-language="' . $language . '">' . $q_config['language_name'][$language] . '</a>';
		}

		foreach ($languages as $language) {
			$atts['class'] = $field['class'];
			if ($language === $currentLanguage) {
				$atts['class'] .= ' current-language';
			}
			$atts['name'] = $field['name'] . "[$language]";
			$atts['data-language'] = $language;
			$e .= '<textarea ' . acf_esc_attr( $atts ) . ' >';
			$e .= esc_textarea( $values[$language] );
			$e .= '</textarea>';
		}

		$e .= '</div>';

		// return
		echo $e;
	}

	/*
	 *  update_value()
	 *
	 *  This filter is appied to the $value before it is updated in the db
	 *
	 *  @type	filter
	 *  @since	3.6
	 *  @date	23/01/13
	 *
	 *  @param	$value - the value which will be saved in the database
	 *  @param	$post_id - the $post_id of which the value will be saved
	 *  @param	$field - the field array holding all the field options
	 *
	 *  @return	$value - the modified value
	 */
	function update_value($value, $post_id, $field) {
		return qtrans_join($value);
	}

}
