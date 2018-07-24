<?php
if ( !defined( 'ABSPATH' ) ) exit;

function qtranxf_version_int()
{
	$ver = str_replace('.', '', QTX_VERSION);
	while (strlen($ver) < 5) $ver.= '0';
	return intval($ver);
}

function qtranxf_version_db()
{
	$ver = get_option('qtranslate_version_db', array());
	if (empty($ver))
	{
		$ver = QTX_DB_VERSION;
	}
	return $ver;
}