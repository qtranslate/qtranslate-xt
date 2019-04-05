<?php

class acf_qtranslate_acf_4_wysiwyg extends acf_field_wysiwyg {

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

		$this->name = 'qtranslate_wysiwyg';
		$this->label = __("Wysiwyg Editor",'acf');
		$this->category = __("qTranslate",'acf');
		$this->defaults = array(
			'toolbar'		=>	'full',
			'media_upload' 	=>	'yes',
			'default_value'	=>	'',
		);

		// Create an acf version of the_content filter (acf_the_content)
		if(	isset($GLOBALS['wp_embed']) ) {

			add_filter( 'acf_the_content', array( $GLOBALS['wp_embed'], 'run_shortcode' ), 8 );
			add_filter( 'acf_the_content', array( $GLOBALS['wp_embed'], 'autoembed' ), 8 );

		}

		add_filter( 'acf_the_content', 'capital_P_dangit', 11 );
		add_filter( 'acf_the_content', 'wptexturize' );
		add_filter( 'acf_the_content', 'convert_smilies' );
		add_filter( 'acf_the_content', 'convert_chars' );
		add_filter( 'acf_the_content', 'wpautop' );
		add_filter( 'acf_the_content', 'shortcode_unautop' );
		//add_filter( 'acf_the_content', 'prepend_attachment' ); *should only be for the_content (causes double image on attachment page)
		add_filter( 'acf_the_content', 'do_shortcode', 11);

		acf_field::__construct();

		// filters
    	add_filter( 'acf/fields/wysiwyg/toolbars', array( $this, 'toolbars'), 1, 1 );
    	add_filter( 'mce_external_plugins', array( $this, 'mce_external_plugins'), 20, 1 );
	}

	/*
	 *  input_admin_head()
	 *
	 *  This action is called in the admin_head action on the edit screen where your field is created.
	 *  Use this action to add css and javascript to assist your create_field() action.
	 *
	 *  @info	http://codex.wordpress.org/Plugin_API/Action_Reference/admin_head
	 *  @type	action
	 *  @since	3.6
	 *  @date	23/01/13
	 */
	function input_admin_head() {}

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
		global $q_config, $wp_version;
		$languages = qtrans_getSortedLanguages(true);
		$values = qtrans_split($field['value'], $quicktags = true);
		$currentLanguage = $this->plugin->get_active_language();

		// vars
		//$id = uniqid('acf-editor-');
		$id = 'wysiwyg-' . $field['id'] . '-' . uniqid();
		$default_editor = 'tinymce';

		// filter value for editor
		remove_filter( 'acf_the_editor_content', 'format_for_editor', 10, 2 );
		remove_filter( 'acf_the_editor_content', 'wp_htmledit_pre', 10, 1 );
		remove_filter( 'acf_the_editor_content', 'wp_richedit_pre', 10, 1 );

		// WP 4.3
		if( version_compare($wp_version, '4.3', '>=' ) ) {
			add_filter( 'acf_the_editor_content', 'format_for_editor', 10, 2 );
		// WP < 4.3
		} else {
			$function = user_can_richedit() ? 'wp_richedit_pre' : 'wp_htmledit_pre';
			add_filter('acf_the_editor_content', $function, 10, 1);
		}

		echo '<div class="multi-language-field multi-language-field-wysiwyg">';

		foreach ($languages as $language) {
			$class = ($language === $currentLanguage) ? 'wp-switch-editor current-language' : 'wp-switch-editor';
			echo '<a class="' . $class . '" data-language="' . $language . '">' . $q_config['language_name'][$language] . '</a>';
		}

		foreach ($languages as $language):
			$id = 'wysiwyg-' . $field['id'] . '-' . uniqid();
			$name = $field['name'] . "[$language]";
			$class = ($language === $currentLanguage) ? 'current-language' : '';
			$value = apply_filters('acf_the_editor_content', $values[$language], 'tinymce');

			?>
			<div id="wp-<?php echo $id; ?>-wrap" class="acf_wysiwyg wp-core-ui wp-editor-wrap tmce-active <?php echo $class; ?>" data-toolbar="<?php echo $field['toolbar']; ?>" data-upload="<?php echo $field['media_upload']; ?>" data-language="<?php echo $language; ?>">
				<div id="wp-<?php echo $id; ?>-editor-tools" class="wp-editor-tools hide-if-no-js">
					<?php if( user_can_richedit() && $field['media_upload'] == 'yes' ): ?>
					<div id="wp-<?php echo $id; ?>-media-buttons" class="wp-media-buttons">
						<?php do_action( 'media_buttons', $id ); ?>
					</div>
					<?php endif; ?>
				</div>
				<div id="wp-<?php echo $id; ?>-editor-container" class="wp-editor-container">
					<textarea id="<?php echo $id; ?>" class="qtx-wp-editor-area" name="<?php echo $name; ?>"><?php echo $value; ?></textarea>
				</div>
			</div>

		<?php endforeach;

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
