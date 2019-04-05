<?php

class acf_qtranslate_acf_4_textarea extends acf_field_textarea {

	/**
	 * The plugin instance.
	 * @var \acf_qtranslate_plugin
	 */
	protected $plugin;


	/*
	 *  __construct
	 *
	 *  Set name / label needed for actions / filters
	 *
	 *  @since	3.6
	 *  @date	23/01/13
	 */
	function __construct($plugin) {
		$this->plugin = $plugin;

		$this->name = 'qtranslate_textarea';
		$this->label = __("Text Area",'acf');
		$this->category = __("qTranslate",'acf');
		$this->defaults = array(
			'default_value'	=> '',
			'formatting' 	=> 'br',
			'maxlength'		=> '',
			'placeholder'	=> '',
			'rows'			=> ''
		);

		acf_field::__construct();
	}

	/*
	 *  create_field()
	 *
	 *  Create the HTML interface for your field
	 *
	 *  @param	$field - an array holding all the field's data
	 *
	 *  @type	action
	 *  @since	3.6
	 *  @date	23/01/13
	 */
	function create_field($field) {
		global $q_config;
		$languages = qtrans_getSortedLanguages(true);
		$values = qtrans_split($field['value'], $quicktags = true);
		$currentLanguage = $this->plugin->get_active_language();

		echo '<div class="multi-language-field">';

		foreach ($languages as $language) {
			$class = ($language === $currentLanguage) ? 'wp-switch-editor current-language' : 'wp-switch-editor';
			echo '<a class="' . $class . '" data-language="' . $language . '">' . $q_config['language_name'][$language] . '</a>';
		}

		foreach ($languages as $language) {
			$class = ($language === $currentLanguage) ? $field['class'] . ' current-language' : $field['class'];
			echo '<textarea data-language="' . esc_attr($language) . '" id="' . esc_attr( $field['id'] ) . '" rows="4" class="' . esc_attr($class) . '" name="' . esc_attr($field['name'] . "[$language]") . '">' . esc_textarea($values[$language]) . '</textarea>';
		}

		echo '</div>';
	}

	/*
	 *  format_value
	 *
	 *  @description: uses the basic value and allows the field type to format it
	 *  @since: 3.6
	 *  @created: 26/01/13
	 */
	function format_value($value, $post_id, $field) {
		return $value;
	}

	/*
	 *  format_value_for_api()
	 *
	 *  This filter is appied to the $value after it is loaded from the db and before it is passed back to the api functions such as the_field
	 *
	 *  @type	filter
	 *  @since	3.6
	 *  @date	23/01/13
	 *
	 *  @param	$value	- the value which was loaded from the database
	 *  @param	$post_id - the $post_id from which the value was loaded
	 *  @param	$field	- the field array holding all the field options
	 *
	 *  @return	$value	- the modified value
	 */
	function format_value_for_api($value, $post_id, $field) {
		$value = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage($value);
		return parent::format_value_for_api($value, $post_id, $field);
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
