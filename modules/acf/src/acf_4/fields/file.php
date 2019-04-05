<?php

class acf_qtranslate_acf_4_file extends acf_field_file {

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

		$this->name = 'qtranslate_file';
		$this->label = __("File",'acf');
		$this->category = __("qTranslate", 'acf');
		$this->defaults = array(
			'save_format' =>  'object',
			'library'     =>  'all'
		);
		$this->l10n = array(
			'select'     =>  __("Select File",'acf'),
			'edit'       =>  __("Edit File",'acf'),
			'update'     =>  __("Update File",'acf'),
			'uploadedTo' =>  __("uploaded to this post",'acf'),
		);

		acf_field::__construct();

		// filters
		add_filter('get_media_item_args', array($this, 'get_media_item_args'));
		add_filter('wp_prepare_attachment_for_js', array($this, 'wp_prepare_attachment_for_js'), 10, 3);


		// JSON
		add_action('wp_ajax_acf/fields/file/get_files', array($this, 'ajax_get_files'));
		add_action('wp_ajax_nopriv_acf/fields/file/get_files', array($this, 'ajax_get_files'), 10, 1);
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

		echo '<div class="multi-language-field multi-language-field-file">';

		foreach ($languages as $language) {
			$class = 'wp-switch-editor';
			if ($language === $currentLanguage) {
				$class .= ' current-language';
			}
			echo '<a class="' . $class . '" data-language="' . $language . '">' . $q_config['language_name'][$language] . '</a>';
		}

		$base_class = $field['class'];
		$base_name = $field['name'];
		foreach ($languages as $language) :
			$value = $values[$language];
			$o = array(
				'class'   =>  '',
				'icon'    =>  '',
				'title'   =>  '',
				'size'    =>  '',
				'url'   =>  '',
				'name'    =>  '',
			);

			if($value && is_numeric($value)) {
				$file = get_post($value);

				if($file) {
					$o['class'] = 'active';
					$o['icon'] = wp_mime_type_icon( $file->ID );
					$o['title'] = $file->post_title;
					$o['size'] = size_format(filesize( get_attached_file( $file->ID ) ));
					$o['url'] = wp_get_attachment_url( $file->ID );

					$explode = explode('/', $o['url']);
					$o['name'] = end( $explode );
				}
			}

			$field['class'] = $base_class;
			if ($language === $currentLanguage) {
				$field['class'] .= ' current-language';
				$o['class'] .= ' current-language';
			}

			$field['name'] = $base_name . '[' . $language . ']';

			?>
			<div class="acf-file-uploader clearfix <?php echo $o['class']; ?>" data-library="<?php echo $field['library']; ?>" data-language="<?php echo $language; ?>">
				<input class="acf-file-value" type="hidden" name="<?php echo $field['name']; ?>" value="<?php echo $value; ?>" />
				<div class="has-file">
					<ul class="hl clearfix">
						<li>
							<img class="acf-file-icon" src="<?php echo $o['icon']; ?>" alt=""/>
							<div class="hover">
								<ul class="bl">
									<li><a href="#" class="acf-button-delete ir">Remove</a></li>
									<li><a href="#" class="acf-button-edit ir">Edit</a></li>
								</ul>
							</div>
						</li>
						<li>
							<p>
								<strong class="acf-file-title"><?php echo $o['title']; ?></strong>
							</p>
							<p>
								<strong><?php _e('Name', 'acf'); ?>:</strong>
								<a class="acf-file-name" href="<?php echo $o['url']; ?>" target="_blank"><?php echo $o['name']; ?></a>
							</p>
							<p>
								<strong><?php _e('Size', 'acf'); ?>:</strong>
								<span class="acf-file-size"><?php echo $o['size']; ?></span>
							</p>

						</li>
					</ul>
				</div>
				<div class="no-file">
					<ul class="hl clearfix">
						<li>
							<p><?php _e('No File Selected','acf'); ?> <a href="#" class="button add-file"><?php _e('Add File','acf'); ?></p></a>
						</li>
					</ul>
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
