<?php
if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Strings to keep in .pot file.
 * The strings listed are expected to also be translated in the default WP domain.
 * In local .po files they may be overwritten if desired.
 * The strings which may be in use in other integrating plugins are aslo kept here.
 * This function is for gettext processing uilities, it is never loaded or executed.
 */
function qtranxf_add_to_po(){

	//translators: Colon after a title. For example, in top item of Language Menu.
	__(':', 'qtranslate');

	//translators: Title for something like a menu item, normally followed by a colon.
	__('Language', 'qtranslate');

	//translators: Title for error messages, normally followed by a colon
	__('Error', 'qtranslate');

	//translators: Title for warning messages, normally followed by a colon
	__('Warning', 'qtranslate');

	//translators: A title of an important message, normally followed by a colon.
	__('Attention', 'qtranslate');

	//translators: Title for something like a menu item.
	__('Settings', 'qtranslate');

	//translators: A mark for an important message.
	__('Important!', 'qtranslate');
}
