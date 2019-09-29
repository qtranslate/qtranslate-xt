<?php

require_once ACF_QTRANSLATE_PLUGIN_DIR . 'src/acf_interface.php';

class acf_qtranslate_qtranslatex {

	/**
	 * An ACF instance
	 * @var acf_qtranslate_acf_interface
	 */
	protected $acf;

	/**
	 * The plugin instance
	 * @var acf_qtranslate_plugin
	 */
	protected $plugin;

	/**
	 * Constructor
	 *
	 * @param acf_qtranslate_plugin $plugin
	 * @param acf_qtranslate_acf_interface $acf
	 */
	public function __construct(acf_qtranslate_plugin $plugin, acf_qtranslate_acf_interface $acf) {
		$this->acf = $acf;
		$this->plugin = $plugin;

		add_action('admin_head',                         array($this, 'admin_head'));
		add_filter('acf_qtranslate_get_active_language', array($this, 'get_active_language'));
		add_action('acf/input/admin_enqueue_scripts',    array($this, 'admin_enqueue_scripts'));
	}

	/**
	 * Add additional styles and scripts to head
	 */
	public function admin_head() {
		// Hide the language tabs if they shouldn't be displayed
		$show_language_tabs = $this->plugin->get_plugin_setting('show_language_tabs');
		if (!$show_language_tabs) {
			?>
			<style>
			.multi-language-field {margin-top:0!important;}
			.multi-language-field .wp-switch-editor[data-language] {display:none!important;}
			</style>
			<?php
		}

		// Enable translation of standard field types
		$translate_standard_field_types = $this->plugin->get_plugin_setting('translate_standard_field_types');
		if ($translate_standard_field_types) {
			?>
			<script>
			var acf_qtranslate_translate_standard_field_types = <?= json_encode($translate_standard_field_types) ?>;
			</script>
			<?php
		}
	}

	/**
	 * Load javascript and stylesheets on admin pages
	 */
	public function admin_enqueue_scripts() {
		$version = $this->plugin->acf_major_version();
		wp_enqueue_script('acf_qtranslatex', plugins_url("/assets/acf_{$version}/qtranslatex.js",  ACF_QTRANSLATE_PLUGIN), array('acf_qtranslate_common'));
	}

	/**
	 * Get the active language
	 *
	 * @param string $language
	 *
	 * @return string
	 */
	public function get_active_language($language) {
	    // TODO modules should not read this cookie directly
		if (empty($_COOKIE['qtrans_edit_language']) === false) {
			$enabledLanguages = qtranxf_getSortedLanguages();
			if (in_array($_COOKIE['qtrans_edit_language'], $enabledLanguages)) {
				$language = $_COOKIE['qtrans_edit_language'];
			}
		}
		return $language;
	}

}
