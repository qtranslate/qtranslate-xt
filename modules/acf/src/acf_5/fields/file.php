<?php

class acf_qtranslate_acf_5_file extends acf_field_file {

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
	function __construct($plugin) {
		$this->plugin = $plugin;

		if (version_compare($plugin->acf_version(), '5.6.0') < 0) {
			$this->initialize();
		}

		acf_field::__construct();
	}

	/**
	 * Setup the field type data
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

	/**
	 * Hook/override ACF render_field to create the HTML interface
	 *
	 *  @param array $field
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
			<div <?php echo acf_esc_attrs($div); ?>>
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

	/**
	 * Hook/override ACF update_value
     *
	 * @param array $values - the values to save in database
	 * @param int $post_id - the post_id of which the value will be saved
	 * @param array $field - the field array holding all the field options
	 *
	 * @return string - the modified value
	 */
	function update_value($values, $post_id, $field) {
		assert( is_array($values) );

		// TODO validation seems unnecessary here, keep until assert has been used for some time
		if ( !is_array($values) ) return false;

		if (function_exists('acf_connect_attachment_to_post')) {
			foreach ($values as $value) {

				// bail early if not attachment ID
				if( !$value || !is_numeric($value) ) continue;

				// maybe connect attacments to post
				acf_connect_attachment_to_post( (int) $value, $post_id );

			}
		}

		return $this->plugin->encode_language_values($values);
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
			$valid = $this->plugin->validate_language_values( $this, $valid, $value, $field, $input);
		}

		return $valid;
	}

}
