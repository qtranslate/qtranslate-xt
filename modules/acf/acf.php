<?php
/**
 * Module: ACF
 *
 * Converted from: ACF qTranslate (https://github.com/funkjedi/acf-qtranslate)
 * @author: funkjedi (http://funkjedi.com)
 */

define( 'ACF_QTRANSLATE_PLUGIN', __FILE__ );
define( 'ACF_QTRANSLATE_PLUGIN_DIR', plugin_dir_path( ACF_QTRANSLATE_PLUGIN ) );

require_once ACF_QTRANSLATE_PLUGIN_DIR . 'src/plugin.php';
new QTX_Module_Acf_Plugin;
