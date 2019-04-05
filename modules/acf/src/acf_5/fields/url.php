<?php

class acf_qtranslate_acf_5_url extends acf_field_url {

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
		$this->name = 'qtranslate_url';
		$this->label = __("Url (qTranslate)",'acf');
		$this->category = __("qTranslate",'acf');
		$this->defaults = array(
			'default_value'	=> '',
			'placeholder'	=> '',
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
		$atts = array();
		$keys = array( 'type', 'id', 'class', 'name', 'value', 'placeholder', 'pattern' );
		$keys2 = array( 'readonly', 'disabled', 'required' );
		$html = '';


		// atts (value="123")
		foreach( $keys as $k ) {
			if( isset($field[ $k ]) ) $atts[ $k ] = $field[ $k ];
		}


		// atts2 (disabled="disabled")
		foreach( $keys2 as $k ) {
			if( !empty($field[ $k ]) ) $atts[ $k ] = $k;
		}


		// remove empty atts
		$atts = acf_clean_atts( $atts );


		// render
		$html .= '<div class="acf-input-wrap multi-language-field">';

		foreach ($languages as $language) {
			$class = ($language === $currentLanguage) ? 'wp-switch-editor current-language' : 'wp-switch-editor';
			$html .= '<a class="' . $class . '" data-language="' . $language . '">' . $q_config['language_name'][$language] . '</a>';
		}

		$html .= '<div class="acf-url">';
		$html .= '<i class="acf-icon -globe -small"></i>';

		foreach ($languages as $language) {
			$atts['class'] = $field['class'];
			if ($language === $currentLanguage) {
				$atts['class'] .= ' current-language';
			}
			$atts['type'] = 'url';
			$atts['name'] = $field['name'] . "[$language]";
			$atts['value'] = $values[$language];
			$atts['data-language'] = $language;
			$html .= acf_get_text_input( $atts );
		}

		$html .= '</div>';
		$html .= '</div>';

		// return
		echo $html;
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
