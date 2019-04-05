<?php

class acf_qtranslate_acf_5_post_object extends acf_field_post_object {

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
	function initialize() {

		// vars
		$this->name = 'qtranslate_post_object';
		$this->label = __("Post Object (qTranslate)",'acf');
		$this->category = 'qTranslate';
		$this->defaults = array(
			'post_type'		=> array(),
			'taxonomy'		=> array(),
			'allow_null' 	=> 0,
			'multiple'		=> 0,
			'return_format'	=> 'object',
			'ui'			=> 1,
		);


		// extra
		add_action('wp_ajax_acf/fields/qtranslate_post_object/query',			array($this, 'ajax_query'));
		add_action('wp_ajax_nopriv_acf/fields/qtranslate_post_object/query',	array($this, 'ajax_query'));

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
	 *  @date	10/07/18
	 */
	function render_field($field) {
		global $q_config;
		$languages = qtrans_getSortedLanguages(true);
		$values = array_map('maybe_unserialize', qtrans_split($field['value'], $quicktags = true));
		$currentLanguage = $this->plugin->get_active_language();

		// populate atts
		$atts = array(
			'id' => $field['id'],
			'name' => $field['name'],
		);

		// render
		echo '<div class="multi-language-field multi-language-field-post-object">';

		foreach ($languages as $language) {
			$class = ($language === $currentLanguage) ? 'wp-switch-editor current-language' : 'wp-switch-editor';
			echo '<a class="' . $class . '" data-language="' . $language . '">' . $q_config['language_name'][$language] . '</a>';
		}

		foreach ($languages as $language) {
			$class = ($language === $currentLanguage) ? 'acf-post-object current-language' : 'acf-post-object';
			$field['id'] = $atts['id'] . "-$language";
			$field['name'] = $atts['name'] . "[$language]";
			$field['value'] = $values[$language];

			echo '<div class="' . $class . '" data-language="' . $language . '">';
			parent::render_field($field);
			echo '</div>';
		}

		// return
		echo '</div>';
	}

	/*
	 *  format_value()
	 *
	 *  This filter is appied to the $value after it is loaded from the db and before it is returned to the template
	 *
	 *  @type	filter
	 *  @since	3.6
	 *  @date	23/01/13
	 *
	 *  @param	$value (mixed) the value which was loaded from the database
	 *  @param	$post_id (mixed) the $post_id from which the value was loaded
	 *  @param	$field (array) the field array holding all the field options
	 *
	 *  @return	$value (mixed) the modified value
	 */
	function format_value($value, $post_id, $field) {
		$value = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage($value);
		$value = maybe_unserialize($value);

		return parent::format_value($value, $post_id, $field);
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
	function update_value($values, $post_id, $field) {

		// validate
		if ( !is_array($values) ) return false;

		foreach ($values as &$value) {
			$value = parent::update_value($value, $post_id, $field);
			$value = maybe_serialize($value);
		}

		return qtrans_join($values);
	}

}
