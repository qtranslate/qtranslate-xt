<?php

class acf_qtranslate_plugin {

	/**
	 * An ACF instance.
	 * @var \acf_qtranslate_acf_interface
	 */
	protected $acf;


	/**
	 * Create an instance.
	 * @return void
	 */
	public function __construct() {
		add_action('plugins_loaded',                  array($this, 'init'), 3);
		add_action('after_setup_theme',               array($this, 'init'), -10);
		add_action('acf/input/admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		add_action('admin_footer',                    array($this, 'admin_footer'), -10);
		add_action('admin_menu',                      array($this, 'admin_menu'));
		add_action('admin_init',                      array($this, 'admin_init'));

		add_filter('qtranslate_load_admin_page_config',                             array($this, 'qtranslate_load_admin_page_config'));
		add_filter('plugin_action_links_' . plugin_basename(ACF_QTRANSLATE_PLUGIN), array($this, 'plugin_action_links'));
	}

	/**
	 * Setup plugin if Advanced Custom Fields is enabled.
	 * @return void
	 */
	public function init() {
		static $plugin_loaded;

		if (!$plugin_loaded && $this->acf_enabled() && $this->qtranslatex_enabled()) {

			// setup qtranslate fields for ACF 4
			if ($this->acf_major_version() === 4) {
				require_once ACF_QTRANSLATE_PLUGIN_DIR . 'src/acf_4/acf.php';
				$this->acf = new acf_qtranslate_acf_4($this);
			}

			// setup qtranslate fields for ACF 5
			if ($this->acf_major_version() === 5) {
				require_once ACF_QTRANSLATE_PLUGIN_DIR . 'src/acf_5/acf.php';
				$this->acf = new acf_qtranslate_acf_5($this);
			}

			// setup qtranslatex integration
			require_once ACF_QTRANSLATE_PLUGIN_DIR . 'src/qtranslatex.php';
			new acf_qtranslate_qtranslatex($this, $this->acf);

			$plugin_loaded = true;

		}
	}

	/**
	 * Check if Advanced Custom Fields is enabled.
	 * @return boolean
	 */
	public function acf_enabled() {
		if (function_exists('acf')) {
			return $this->acf_major_version() === 4 || $this->acf_major_version() === 5;
		}
		return false;
	}

	/**
	 * Return the major version number for Advanced Custom Fields.
	 * @return int
	 */
	public function acf_version() {
		return acf()->settings['version'];
	}

	/**
	 * Return the major version number for Advanced Custom Fields.
	 * @return int
	 */
	public function acf_major_version() {
		return (int) $this->acf_version();
	}

	/**
	 * Check if qTranslate-X is enabled.
	 */
	public function qtranslatex_enabled() {
		return function_exists('qtranxf_getLanguage');
	}

	/**
	 * Get the active language.
	 */
	public function get_active_language() {
		return apply_filters('acf_qtranslate_get_active_language', qtrans_getLanguage());
	}

	/**
	 * Load javascript and stylesheets on admin pages.
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_style('acf_qtranslate_common',  plugins_url('/assets/common.css', ACF_QTRANSLATE_PLUGIN), array('acf-input'));
		wp_enqueue_script('acf_qtranslate_common', plugins_url('/assets/common.js',  ACF_QTRANSLATE_PLUGIN), array('acf-input','underscore'));
	}

	/**
	 * Output a hidden block that can be use to force qTranslate-X
	 * to include the LSB.
	 */
	public function admin_footer() {
?>
<script type="text/javascript">
(function($){
	var anchors = {
		'#post-body-content': 'prepend',
		'#widgets-right': 'before',
		'#posts-filter': 'prepend',
		'#wpbody-content h1': 'after',
		'#wpbody-content': 'prepend'
	};
	$.each(anchors, function(anchor, fn){
		var $anchor = $(anchor);
		if ($anchor.length) {
			$anchor[fn]('<span id="acf-qtranslate-lsb-shim" style="display:none">[:en]LSB[:]</span>');
			return false;
		}
	});
})(jQuery);
</script>
<?php
	}

	/**
	 * Add settings link on plugin page.
	 * @param array
	 * @return array
	 */
	public function plugin_action_links($links) {
		array_unshift($links, '<a href="options-general.php?page=acf-qtranslate">Settings</a>');
		return $links;
	}

	/**
	 * Enable the display of the LSB on ACF Options pages.
	 * @param array
	 * @return array
	 */
	public function qtranslate_load_admin_page_config($config)
	{
		$pages = array(
			//'post.php' => '',
			'admin.php' => 'page=',
		);

		foreach (explode("\n", $this->get_plugin_setting('show_on_pages')) as $page) {
			$page = trim($page);
			if ($page) {
				$pages[$page] = '';
			}
		}

		$config['acf-display-nodes'] = array(
			'pages'   => $pages,
			'anchors' => array(
				'acf-qtranslate-lsb-shim' => array('where' => 'after'),
			),
			'forms'   => array(
				'wpwrap' => array(
					'fields' => array(
						'lsb-shim' => array(
							'jquery' => '#acf-qtranslate-lsb-shim',
							'encode' => 'display',
						),
						'acf4-field-group-handle' => array(
							'jquery' => '.acf_postbox h2 span,.acf_postbox h3 span',
							'encode' => 'display',
						),
						'acf5-field-group-handle' => array(
							'jquery' => '.acf-postbox h2 span,.acf-postbox h3 span',
							'encode' => 'display',
						),
						'acf5-field-label' => array(
							'jquery' => '.acf-field .acf-label label',
							'encode' => 'display',
						),
						'acf5-field-description' => array(
							'jquery' => '.acf-field .acf-label p.description',
							'encode' => 'display',
						),
				)),
			),
		);

		$config['acf-field-group'] = array(
			'pages'     => array('post.php' => ''),
			'post_type' => 'acf-field-group',
			'forms'     => array(
				'post' => array(
					'fields' => array(
						'field-group-object-label' => array(
							'jquery' => '.li-field-label .edit-field',
							'encode' => 'display',
						),
				)),
			),
		);

		return $config;
	}

	/**
	 * Retrieve the value of a plugin setting.
	 */
	function get_plugin_setting($name, $default = null) {
		$options = get_option('acf_qtranslate');
		if (isset($options[$name]) === true) {
			return $options[$name];
		}
		return $default;
	}

	/**
	 * Register the options page with the Wordpress menu.
	 */
	function admin_menu() {
		add_options_page('ACF qTranslate', 'ACF qTranslate', 'manage_options', 'acf-qtranslate', array($this, 'options_page'));
	}

	/**
	 * Register settings and default fields.
	 */
	function admin_init() {
		register_setting('acf_qtranslate', 'acf_qtranslate');

		add_settings_section(
			'qtranslatex_section',
			'qTranslate-X',
			array($this, 'render_section_qtranslatex'),
			'acf_qtranslate'
		);

		add_settings_field(
			'translate_standard_field_types',
			'Enable translation for Standard Field Types',
			array($this, 'render_setting_translate_standard_field_types'),
			'acf_qtranslate',
			'qtranslatex_section'
		);

		add_settings_field(
			'show_language_tabs',
			'Display language tabs',
			array($this, 'render_setting_show_language_tabs'),
			'acf_qtranslate',
			'qtranslatex_section'
		);

		add_settings_field(
			'show_on_pages',
			'Display the LSB on the following pages',
			array($this, 'render_setting_show_on_pages'),
			'acf_qtranslate',
			'qtranslatex_section'
		);
	}

	/**
	 * Render the options page.
	 */
	function options_page() {
		?>
		<form action="options.php" method="post">
			<h2>ACF qTranslate Settings</h2>
			<br>
			<?php

			settings_fields('acf_qtranslate');
			do_settings_sections('acf_qtranslate');
			submit_button();

			?>
		</form>
		<?php
	}

	/**
	 * Render the qTranslate-X section.
	 */
	function render_section_qtranslatex() {
		?>
		The following options represent additional functionality that is available when
		using qTranslate-X. These functionality is off by default and must be enabled below.
		<?php
	}

	/**
	 * Render setting.
	 */
	function render_setting_translate_standard_field_types() {
		?>
		<input type="checkbox" name="acf_qtranslate[translate_standard_field_types]" <?php checked($this->get_plugin_setting('translate_standard_field_types'), 1); ?> value="1">
		<?php
	}

	/**
	 * Render setting.
	 */
	function render_setting_show_language_tabs() {
		?>
		<input type="checkbox" name="acf_qtranslate[show_language_tabs]" <?php checked($this->get_plugin_setting('show_language_tabs'), 1); ?> value="1">
		<?php
	}

	/**
	 * Render setting.
	 */
	function render_setting_show_on_pages() {
		?>
		<textarea name="acf_qtranslate[show_on_pages]" style="max-width:500px;width:100%;height:200px;padding-top:6px" placeholder="post.php"><?= esc_html($this->get_plugin_setting('show_on_pages')) ?></textarea><br>
		<small>Enter each page on it's own line</small>
		<?php
	}

}
