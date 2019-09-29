<?php

class acf_qtranslate_acf_5_image extends acf_field_image {

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
		$this->name = 'qtranslate_image';
		$this->label = __("Image (qTranslate)",'acf');
		$this->category = __("qTranslate", 'acf');
		$this->defaults = array(
			'return_format'	=> 'array',
			'preview_size'	=> 'thumbnail',
			'library'		=> 'all',
			'min_width'		=> 0,
			'min_height'	=> 0,
			'min_size'		=> 0,
			'max_width'		=> 0,
			'max_height'	=> 0,
			'max_size'		=> 0,
			'mime_types'	=> ''
		);
		$this->l10n = array(
			'select'		=> __("Select Image",'acf'),
			'edit'			=> __("Edit Image",'acf'),
			'update'		=> __("Update Image",'acf'),
			'uploadedTo'	=> __("Uploaded to this post",'acf'),
			'all'			=> __("All images",'acf'),
		);


		// filters
		add_filter('get_media_item_args', array($this, 'get_media_item_args'));
		// removed from ACF 5.8.3
		if (method_exists($this, 'wp_prepare_attachment_for_js')) {
			add_filter('wp_prepare_attachment_for_js', array($this, 'wp_prepare_attachment_for_js'), 10, 3);
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
		global $q_config;

		$languages = qtranxf_getSortedLanguages(true);
		$values = $this->plugin->decode_language_values($field['value']);
		$currentLanguage = $this->plugin->get_active_language();


		// vars
		$uploader = acf_get_setting('uploader');

		// enqueue
		if( $uploader == 'wp' ) {
			acf_enqueue_uploader();
		}

		// vars
		$url = '';
		$alt = '';
		$div = array(
			'class'					=> 'acf-image-uploader acf-cf',
			'data-preview_size'		=> $field['preview_size'],
			'data-library'			=> $field['library'],
			'data-mime_types'		=> $field['mime_types'],
			'data-uploader'			=> $uploader
		);

		// get size of preview value
		$size = acf_get_image_size($field['preview_size']);


		echo '<div class="multi-language-field multi-language-field-image">';

		foreach ($languages as $language) {
			$class = 'wp-switch-editor';
			if ($language === $currentLanguage) {
				$class .= ' current-language';
			}
			echo '<a class="' . $class . '" data-language="' . $language . '">' . $q_config['language_name'][$language] . '</a>';
		}

		$field_name = $field['name'];

		foreach ($languages as $language):

			$field['name'] = $field_name . '[' . $language . ']';
			$field['value'] = $values[$language];
			$div['data-language'] = $language;
			$div['class'] = 'acf-image-uploader acf-cf';

			// has value?
			if( $field['value'] ) {
				// update vars
				$url = wp_get_attachment_image_src($field['value'], $field['preview_size']);
				$alt = get_post_meta($field['value'], '_wp_attachment_image_alt', true);

				// url exists
				if( $url ) $url = $url[0];

				// url exists
				if( $url ) {
					$div['class'] .= ' has-value';
				}
			}

			if ($language === $currentLanguage) {
				$div['class'] .= ' current-language';
			}

			?>
			<div <?php echo acf_esc_attrs( $div ); ?>>
				<?php acf_hidden_input(array( 'name' => $field['name'], 'value' => $field['value'] )); ?>
				<div class="show-if-value image-wrap" <?php if( $size['width'] ): ?>style="<?php echo esc_attr('max-width: '.$size['width'].'px'); ?>"<?php endif; ?>>
					<img data-name="image" src="<?php echo esc_url($url); ?>" alt="<?php echo esc_attr($alt); ?>"/>
					<div class="acf-actions -hover">
						<?php
						if( $uploader != 'basic' ):
							?><a class="acf-icon -pencil dark" data-name="edit" href="#" title="<?php _e('Edit', 'acf'); ?>"></a><?php
						endif;
						?><a class="acf-icon -cancel dark" data-name="remove" href="#" title="<?php _e('Remove', 'acf'); ?>"></a>
					</div>
				</div>
				<div class="hide-if-value">
					<?php if( $uploader == 'basic' ): ?>

						<?php if( $field['value'] && !is_numeric($field['value']) ): ?>
							<div class="acf-error-message"><p><?php echo acf_esc_html($field['value']); ?></p></div>
						<?php endif; ?>

						<label class="acf-basic-uploader">
							<?php acf_file_input(array( 'name' => $field['name'], 'id' => $field['id'] )); ?>
						</label>

					<?php else: ?>

						<p><?php _e('No image selected','acf'); ?> <a data-name="add" class="acf-button button" href="#"><?php _e('Add Image','acf'); ?></a></p>

					<?php endif; ?>
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
		return acf_get_field_type('qtranslate_file')->update_value( $value, $post_id, $field );
	}

	/*
	*  validate_value
	*
	*  This function will validate a basic file input
	*
	*  @type	function
	*  @date	11/02/2014
	*  @since	5.0.0
	*
	*  @param	$post_id (int)
	*  @return	$post_id (int)
	*/
	function validate_value( $valid, $value, $field, $input ){
		return acf_get_field_type('qtranslate_file')->validate_value( $valid, $value, $field, $input );
	}

}
