<?php

class acf_qtranslate_acf_5_wysiwyg extends acf_field_wysiwyg {

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

		// actions

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
		$this->name = 'qtranslate_wysiwyg';
		$this->label = __("Wysiwyg Editor (qTranslate)",'acf');
		$this->category = __("qTranslate",'acf');
		$this->defaults = array(
			'tabs'			=> 'all',
			'toolbar'		=> 'full',
			'media_upload' 	=> 1,
			'default_value'	=> '',
			'delay'			=> 0
		);

		// add acf_the_content filters
		if (method_exists($this, 'add_filters')) {
			$this->add_filters();
		}

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

		// global
   		global $wp_version;


		// enqueue
		acf_enqueue_uploader();


		// vars
		$id = uniqid('acf-editor-');
		$default_editor = 'html';
		$show_tabs = true;
		$button = '';


		// get height
		$height = acf_get_user_setting('wysiwyg_height', 300);
		$height = max( $height, 300 ); // minimum height is 300


		// detect mode
		if( !user_can_richedit() ) {

			$show_tabs = false;

		} elseif( $field['tabs'] == 'visual' ) {

			// case: visual tab only
			$default_editor = 'tinymce';
			$show_tabs = false;

		} elseif( $field['tabs'] == 'text' ) {

			// case: text tab only
			$show_tabs = false;

		} elseif( wp_default_editor() == 'tinymce' ) {

			// case: both tabs
			$default_editor = 'tinymce';

		}


		// must be logged in tp upload
		if( !current_user_can('upload_files') ) {

			$field['media_upload'] = 0;

		}


		// mode
		$switch_class = ($default_editor === 'html') ? 'html-active' : 'tmce-active';


		// filter value for editor
		remove_filter( 'acf_the_editor_content', 'format_for_editor', 10, 2 );
		remove_filter( 'acf_the_editor_content', 'wp_htmledit_pre', 10, 1 );
		remove_filter( 'acf_the_editor_content', 'wp_richedit_pre', 10, 1 );


		// WP 4.3
		if( version_compare($wp_version, '4.3', '>=' ) ) {

			add_filter( 'acf_the_editor_content', 'format_for_editor', 10, 2 );

			$button = 'data-wp-editor-id="' . $id . '"';

		// WP < 4.3
		} else {

			$function = ($default_editor === 'html') ? 'wp_htmledit_pre' : 'wp_richedit_pre';

			add_filter('acf_the_editor_content', $function, 10, 1);

			$button = 'onclick="switchEditors.switchto(this);"';

		}


		global $q_config;

		$languages = qtrans_getSortedLanguages(true);
		$values = qtrans_split($field['value'], $quicktags = true);
		$currentLanguage = $this->plugin->get_active_language();

		echo '<div class="multi-language-field multi-language-field-wysiwyg">';

		foreach ($languages as $language) {
			$class = ($language === $currentLanguage) ? 'wp-switch-editor current-language' : 'wp-switch-editor';
			echo '<a class="' . $class . '" data-language="' . $language . '">' . $q_config['language_name'][$language] . '</a>';
		}

		$uid = uniqid('acf-editor-');
		foreach ($languages as $language):

			$id = $uid . "-$language";
			$name = $field['name'] . "[$language]";
			$class = $switch_class;
			if ($language === $currentLanguage) {
				$class .= ' current-language';
			}

			// WP 4.3
			if( version_compare($wp_version, '4.3', '>=' ) ) {
				$button = 'data-wp-editor-id="' . $id . '"';
			// WP < 4.3
			} else {
				$button = 'onclick="switchEditors.switchto(this);"';
			}

			$value = apply_filters('acf_the_editor_content', $values[$language], $default_editor);

			?>
			<div id="wp-<?php echo $id; ?>-wrap" class="acf-editor-wrap wp-core-ui wp-editor-wrap <?php echo $class; ?>" data-toolbar="<?php echo $field['toolbar']; ?>" data-upload="<?php echo $field['media_upload']; ?>" data-language="<?php echo $language; ?>">
				<div id="wp-<?php echo $id; ?>-editor-tools" class="wp-editor-tools hide-if-no-js">
					<?php if( $field['media_upload'] ): ?>
					<div id="wp-<?php echo $id; ?>-media-buttons" class="wp-media-buttons">
						<?php do_action( 'media_buttons' ); ?>
					</div>
					<?php endif; ?>
					<?php if( user_can_richedit() && $show_tabs ): ?>
						<div class="wp-editor-tabs">
							<button id="<?php echo $id; ?>-tmce" class="wp-switch-editor switch-tmce" <?php echo  $button; ?> type="button"><?php echo __('Visual', 'acf'); ?></button>
							<button id="<?php echo $id; ?>-html" class="wp-switch-editor switch-html" <?php echo  $button; ?> type="button"><?php echo _x( 'Text', 'Name for the Text editor tab (formerly HTML)', 'acf' ); ?></button>
						</div>
					<?php endif; ?>
				</div>
				<div id="wp-<?php echo $id; ?>-editor-container" class="wp-editor-container">
					<textarea id="<?php echo $id; ?>" class="qtx-wp-editor-area" name="<?php echo $name; ?>" <?php if($height): ?>style="height:<?php echo $height; ?>px;"<?php endif; ?>><?php echo $value; ?></textarea>
				</div>
			</div>

		<?php endforeach;

		echo '</div>';
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
