<?php

class acf_qtranslate_acf_5_file extends acf_field_file {

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
		$this->name = 'qtranslate_file';
		$this->label = __("File (qTranslate)",'acf');
		$this->category = __("qTranslate", 'acf');
		$this->defaults = array(
			'return_format'	=> 'array',
			'library' 		=> 'all',
			'min_size'		=> 0,
			'max_size'		=> 0,
			'mime_types'	=> ''
		);
		$this->l10n = array(
			'select'		=> __("Select File",'acf'),
			'edit'			=> __("Edit File",'acf'),
			'update'		=> __("Update File",'acf'),
			'uploadedTo'	=> __("Uploaded to this post",'acf'),
		);


		// filters
		add_filter('get_media_item_args',			array($this, 'get_media_item_args'));

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
		$uploader = acf_get_setting('uploader');

		// enqueue
		if( $uploader == 'wp' ) {
			acf_enqueue_uploader();
		}

		// vars
		$o = array(
			'icon'		=> '',
			'title'		=> '',
			'url'		=> '',
			'filesize'	=> '',
			'filename'	=> '',
		);

		$div = array(
			'class'				=> 'acf-file-uploader acf-cf',
			'data-library' 		=> $field['library'],
			'data-mime_types'	=> $field['mime_types'],
			'data-uploader'		=> $uploader
		);

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
			$div['class'] = 'acf-file-uploader acf-cf';

			// has value?
			if( $field['value'] ) {
				$file = get_post( $field['value'] );
				if( $file ) {
					$o['icon'] = wp_mime_type_icon( $file->ID );
					$o['title']	= $file->post_title;
					$o['filesize'] = @size_format(filesize( get_attached_file( $file->ID ) ));
					$o['url'] = wp_get_attachment_url( $file->ID );

					$explode = explode('/', $o['url']);
					$o['filename'] = end( $explode );
				}

				// url exists
				if( $o['url'] ) {
					$div['class'] .= ' has-value';
				}
			}

			if ($language === $currentLanguage) {
				$div['class'] .= ' current-language';
			}

			?>
			<div <?php acf_esc_attr_e($div); ?>>
				<div class="acf-hidden">
					<?php acf_hidden_input(array( 'name' => $field['name'], 'value' => $field['value'], 'data-name' => 'id' )); ?>
				</div>
				<div class="show-if-value file-wrap acf-soh">
					<div class="file-icon">
						<img data-name="icon" src="<?php echo $o['icon']; ?>" alt=""/>
					</div>
					<div class="file-info">
						<p>
							<strong data-name="title"><?php echo $o['title']; ?></strong>
						</p>
						<p>
							<strong><?php _e('File name', 'acf'); ?>:</strong>
							<a data-name="filename" href="<?php echo $o['url']; ?>" target="_blank"><?php echo $o['filename']; ?></a>
						</p>
						<p>
							<strong><?php _e('File size', 'acf'); ?>:</strong>
							<span data-name="filesize"><?php echo $o['filesize']; ?></span>
						</p>

						<ul class="acf-hl acf-soh-target">
							<?php if( $uploader != 'basic' ): ?>
								<li><a class="acf-icon -pencil dark" data-name="edit" href="#"></a></li>
							<?php endif; ?>
							<li><a class="acf-icon -cancel dark" data-name="remove" href="#"></a></li>
						</ul>
					</div>
				</div>
				<div class="hide-if-value">
					<?php if( $uploader == 'basic' ): ?>

						<?php if( $field['value'] && !is_numeric($field['value']) ): ?>
							<div class="acf-error-message"><p><?php echo $field['value']; ?></p></div>
						<?php endif; ?>

						<input type="file" name="<?php echo $field['name']; ?>" id="<?php echo $field['id']; ?>" />

					<?php else: ?>

						<p style="margin:0;"><?php _e('No file selected','acf'); ?> <a data-name="add" class="acf-button button" href="#"><?php _e('Add File','acf'); ?></a></p>

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
	function update_value($values, $post_id, $field) {

		// validate
		if ( !is_array($values) ) return false;

		if (function_exists('acf_connect_attachment_to_post')) {
			foreach ($values as $value) {

				// bail early if not attachment ID
				if( !$value || !is_numeric($value) ) continue;

				// maybe connect attacments to post
				acf_connect_attachment_to_post( (int) $value, $post_id );

			}
		}

		return qtrans_join($values);
	}

}
