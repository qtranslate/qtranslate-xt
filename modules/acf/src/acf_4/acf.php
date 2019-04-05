<?php

require_once ACF_QTRANSLATE_PLUGIN_DIR . 'src/acf_interface.php';

class acf_qtranslate_acf_4 implements acf_qtranslate_acf_interface {

	/**
	 * The plugin instance.
	 * @var \acf_qtranslate_plugin
	 */
	protected $plugin;


	/*
	 * Create an instance.
	 * @return void
	 */
	public function __construct($plugin) {
		$this->plugin = $plugin;

		add_filter('acf/format_value_for_api',        array($this, 'format_value_for_api'));
		add_action('acf/register_fields',             array($this, 'register_fields'));
		add_action('acf/input/admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
	}

	/**
	 * Load javascript and stylesheets on admin pages.
	 */
	public function register_fields() {
		require_once ACF_QTRANSLATE_PLUGIN_DIR . 'src/acf_4/fields/file.php';
		require_once ACF_QTRANSLATE_PLUGIN_DIR . 'src/acf_4/fields/image.php';
		require_once ACF_QTRANSLATE_PLUGIN_DIR . 'src/acf_4/fields/text.php';
		require_once ACF_QTRANSLATE_PLUGIN_DIR . 'src/acf_4/fields/textarea.php';
		require_once ACF_QTRANSLATE_PLUGIN_DIR . 'src/acf_4/fields/wysiwyg.php';

		new acf_qtranslate_acf_4_text($this->plugin);
		new acf_qtranslate_acf_4_textarea($this->plugin);
		new acf_qtranslate_acf_4_wysiwyg($this->plugin);
		new acf_qtranslate_acf_4_image($this->plugin);
		new acf_qtranslate_acf_4_file($this->plugin);
	}

	/**
	 * Load javascript and stylesheets on admin pages.
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_script('acf_qtranslate_main', plugins_url('/assets/acf_4/main.js', ACF_QTRANSLATE_PLUGIN), array('acf-input','underscore'));
	}

	/**
	 * This filter is applied to the $value after it is loaded from the db and
	 * before it is returned to the template via functions such as get_field().
	 */
	public function format_value_for_api($value) {
		if (is_string($value)) {
			$value = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage($value);
		}
		return $value;
	}

	/**
	 * Get the visible ACF fields.
	 * @return array
	 */
	public function get_visible_acf_fields() {
		$visible_fields = array();

		// build field group filters required for current screen
		$filter = $this->get_acf_field_group_filters();
		if (count($filter) === 0) {
			return $visible_fields;
		}

		$supported_field_types = array(
			'email',
			'text',
			'textarea',
			'repeater',
			'flexible_content',
			'qtranslate_file',
			'qtranslate_image',
			'qtranslate_text',
			'qtranslate_textarea',
			'qtranslate_wysiwyg'
		);

		$visible_field_groups = apply_filters('acf/location/match_field_groups', array(), $filter);

		foreach (apply_filters('acf/get_field_groups', array()) as $field_group) {
			if (in_array($field_group['id'], $visible_field_groups)) {
				$fields = apply_filters('acf/field_group/get_fields', array(), $field_group['id']);
				foreach ($fields as $field) {
					if (in_array($field['type'], $supported_field_types)) {
						$visible_fields[] = array('id' => $field['id']);
					}
				}
			}
		}

		return $visible_fields;
	}

	/**
	 * Get field group filters based on active screen.
	 */
	public function get_acf_field_group_filters() {
		global $post, $pagenow, $typenow, $plugin_page;

		$filter = array();
		if ($pagenow === 'post.php' || $pagenow === 'post-new.php') {
			if ($typenow !== 'acf') {
				$filter['post_id'] = apply_filters('acf/get_post_id', false);
				$filter['post_type'] = $typenow;
			}
		}
		elseif ($pagenow === 'admin.php' && isset($plugin_page)) {
			if ($this->acf_get_options_page($plugin_page)) {
				$filter['post_id'] = apply_filters('acf/get_post_id', false);
			}
		}
		elseif ($pagenow === 'edit-tags.php' && isset($_GET['taxonomy'])) {
			$filter['ef_taxonomy'] = filter_var($_GET['taxonomy'], FILTER_SANITIZE_STRING);
		}
		elseif ($pagenow === 'profile.php') {
			$filter['ef_user'] = get_current_user_id();
		}
		elseif ($pagenow === 'user-edit.php' && isset($_GET['user_id'])) {
			$filter['ef_user'] = filter_var($_GET['user_id'], FILTER_SANITIZE_NUMBER_INT);
		}
		elseif ($pagenow === 'user-new.php') {
			$filter['ef_user'] = 'all';
		}
		elseif ($pagenow === 'media.php' || $pagenow === 'upload.php') {
			$filter['post_type'] = 'attachment';
		}

		return $filter;
	}

	/**
	 * Get details about ACF Options page.
	 */
	public function acf_get_options_page($slug) {
		global $acf_options_page;

		if (!is_object($acf_options_page) || !is_array($acf_options_page->settings)) {
			return false;
		}

		if ($acf_options_page->settings['slug'] === $slug) {
			return array(
				'title'       => $acf_options_page->settings['title'],
				'menu'        => $acf_options_page->settings['menu'],
				'slug'        => $acf_options_page->settings['slug'],
				'capability'  => $acf_options_page->settings['capability'],
				'show_parent' => $acf_options_page->settings['show_parent'],
			);
		}

		foreach ($acf_options_page->settings['pages'] as $page) {
			if ($page['slug'] === $slug) {
				return $page;
			}
		}
	}

}
