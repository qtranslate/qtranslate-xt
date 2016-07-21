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

	__('Plugin %s needs to perform an update of the database in order to function correctly.', 'qtranslate');
	__('The number of database entries to be updated is equal to %d.', 'qtranslate');
	__('You should make sure to have a recent enough backup of the database, just in case something goes wrong during the update. When ready, please press the button below to start the update.', 'qtranslate');
	//translators: This is a title of a button.
	__('Run the Database Update Now', 'qtranslate');
	__('An error occurred during the database update.');
	__('The database update has finished successfully.');
	__('The database update has not finished. Please, refresh this page and run the update again.');

	//translators: An error message after communication with a server.
	__('A meaningless response has been received.', 'qtranslate');
	__('The response received is not an array.');

	__('Thank you for using plugin %s!', 'qtranslate');
	__('Please, help us to make a decision on "%s" feature, press the button below.', 'qtranslate');
	__('Translation Service', 'qtranslate');
	__('Survey on "%s" feature', 'qtranslate');
	__('I have already done it, dismiss this message.', 'qtranslate');
}

