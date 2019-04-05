<?php

class acf_qtranslate_acf_5_text extends acf_field_text {

	/**
	 * The plugin instance.
	 * @var \acf_qtranslate_plugin
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
		$this->name = 'qtranslate_text';
		$this->label = __("Text (qTranslate)",'acf');
		$this->category = __("qTranslate",'acf');
		$this->defaults = array(
			'default_value'	=> '',
			'maxlength'		=> '',
			'placeholder'	=> '',
			'prepend'		=> '',
			'append'		=> ''
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
		$o = array( 'type', 'id', 'class', 'name', 'value', 'placeholder' );
		$s = array( 'readonly', 'disabled' );
		$e = '';

		// maxlength
		if( $field['maxlength'] !== "" ) {
			$o[] = 'maxlength';
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
			$atts['type'] = 'text';
			$atts['name'] = $field['name'] . "[$language]";
			$atts['value'] = $values[$language];
			$atts['data-language'] = $language;
			$e .= '<input ' . acf_esc_attr( $atts ) . ' />';
		}

		$e .= '</div>';

		// return
		echo $e;
	}

	/*
	 *  render_field_settings()
	 *
	 *  Create extra options for your field. This is rendered when editing a field.
	 *  The value of $field['name'] can be used (like bellow) to save extra data to the $field
	 *
	 *  @param	$field	- an array holding all the field's data
	 *
	 *  @type	action
	 *  @since	3.6
	 *  @date	23/01/13
	 */
	function render_field_settings($field) {
		// default_value
		acf_render_field_setting( $field, array(
			'label'			=> __('Default Value','acf'),
			'instructions'	=> __('Appears when creating a new post','acf'),
			'type'			=> 'text',
			'name'			=> 'default_value',
		));

		// placeholder
		acf_render_field_setting( $field, array(
			'label'			=> __('Placeholder Text','acf'),
			'instructions'	=> __('Appears within the input','acf'),
			'type'			=> 'text',
			'name'			=> 'placeholder',
		));

		// maxlength
		acf_render_field_setting( $field, array(
			'label'			=> __('Character Limit','acf'),
			'instructions'	=> __('Leave blank for no limit','acf'),
			'type'			=> 'number',
			'name'			=> 'maxlength',
		));
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
