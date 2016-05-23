<?php

function qtranxf_convert_database($action){
	switch($action){
		case 'b_only':
		case 'c_dual':{
				global $wpdb;
				$wpdb->show_errors(); @set_time_limit(0);
				qtranxf_convert_database_options($action);
				qtranxf_convert_database_posts($action);
				qtranxf_convert_database_postmeta($action);
			} break;
		case 'db_split':
			if(!empty($_POST['db_file'])){
				global $q_config;
				$ifp = wp_unslash(sanitize_text_field($_POST['db_file']));
				$q_config['db_file'] = $ifp;
				$db_langs = wp_unslash(sanitize_text_field($_POST['db_langs']));
				$languages_to_keep = preg_split('/[,\\s]+/',$db_langs);
				$enabled_languages = $q_config['enabled_languages'];
				$default_language = $q_config['default_language'];
				if(!is_array($languages_to_keep)){
					$languages_to_keep = array($default_language);
				}
				$q_config['db_langs'] = implode(', ',$languages_to_keep);
				$msg = qtranxf_split_database_file($ifp,$languages_to_keep);
				$q_config['enabled_languages'] = $enabled_languages;
				$q_config['default_language'] = $default_language;
				return $msg;
			}
		default: break;
	}
	switch($action){
		case 'b_only':
			return __('Database has been converted to square bracket format.', 'qtranslate').'<br/>'.__('Note: custom entries are not touched.', 'qtranslate');
		case 'c_dual':
			return __('Database has been converted to legacy dual-tag format.', 'qtranslate').'<br/>'.__('Note: custom entries are not touched.', 'qtranslate');
		default: return '';
	}
}

function qtranxf_convert_database_options($action){
	global $wpdb;
	$wpdb->show_errors();
	$result = $wpdb->get_results('SELECT option_id, option_value FROM '.$wpdb->options);
	if(!$result) return;
	switch($action){
		case 'b_only':
			foreach($result as $row) {
				if(!qtranxf_isMultilingual($row->option_value)) continue;
				$value = maybe_unserialize($row->option_value);
				$value_converted=qtranxf_convert_to_b_deep($value);
				$value_serialized = maybe_serialize($value_converted);
				if($value_serialized === $row->option_value) continue;
				//Since 3.2-b3: Replaced mysql_real_escape_string with $wpdb->prepare
				$wpdb->query($wpdb->prepare('UPDATE '.$wpdb->options.' set option_value = %s WHERE option_id = %d', $value_serialized, $row->option_id));
			}
			break;
		case 'c_dual':
			foreach($result as $row) {
				if(!qtranxf_isMultilingual($row->option_value)) continue;
				$value = maybe_unserialize($row->option_value);
				$value_converted=qtranxf_convert_to_b_no_closing_deep($value);
				$value_serialized = maybe_serialize($value_converted);
				if($value_serialized === $row->option_value) continue;
				//Since 3.2-b3: Replaced mysql_real_escape_string with $wpdb->prepare
				$wpdb->query($wpdb->prepare('UPDATE '.$wpdb->options.' set option_value = %s WHERE option_id = %d', $value_serialized, $row->option_id));
			}
			break;
		default: break;
	}
}

function qtranxf_convert_database_posts($action){
	global $wpdb;
	$result = $wpdb->get_results('SELECT ID, post_title, post_content, post_excerpt FROM '.$wpdb->posts);
	if(!$result) return;
	switch($action){
		case 'b_only':
			foreach($result as $row) {
				$title=qtranxf_convert_to_b($row->post_title);
				$content=qtranxf_convert_to_b($row->post_content);
				$excerpt=qtranxf_convert_to_b($row->post_excerpt);
				if( $title==$row->post_title && $content==$row->post_content && $excerpt==$row->post_excerpt ) continue;
				//Since 3.2-b3: Replaced mysql_real_escape_string with $wpdb->prepare
				$wpdb->query($wpdb->prepare('UPDATE '.$wpdb->posts.' set post_content = %s, post_title = %s, post_excerpt = %s WHERE ID = %d',$content, $title, $excerpt, $row->ID));
				//$wpdb->query('UPDATE '.$wpdb->posts.' set post_content = "'.mysql_real_escape_string($content).'", post_title = "'.mysql_real_escape_string($title).'", post_excerpt = "'.mysql_real_escape_string($excerpt).'" WHERE ID='.$row->ID);
			}
			break;
		case 'c_dual':
			foreach($result as $row) {
				$title=qtranxf_convert_to_c($row->post_title);
				$content=qtranxf_convert_to_c($row->post_content);
				$excerpt=qtranxf_convert_to_c($row->post_excerpt);
				if( $title==$row->post_title && $content==$row->post_content && $excerpt==$row->post_excerpt ) continue;
				//Since 3.2-b3: Replaced mysql_real_escape_string with $wpdb->prepare
				$wpdb->query($wpdb->prepare('UPDATE '.$wpdb->posts.' set post_content = %s, post_title = %s, post_excerpt = %s WHERE ID = %d',$content, $title, $excerpt, $row->ID));
				//$wpdb->query('UPDATE '.$wpdb->posts.' set post_content = "'.mysql_real_escape_string($content).'", post_title = "'.mysql_real_escape_string($title).'", post_excerpt = "'.mysql_real_escape_string($excerpt).'" WHERE ID='.$row->ID);
			}
			break;
		default: break;
	}
}

function qtranxf_convert_database_postmeta($action){
	global $wpdb;
	$result = $wpdb->get_results('SELECT meta_id, meta_value FROM '.$wpdb->postmeta);
	if(!$result) return;
	switch($action){
		case 'b_only':
			foreach($result as $row) {
				if(!qtranxf_isMultilingual($row->meta_value)) continue;
				$value = maybe_unserialize($row->meta_value);
				$value_converted=qtranxf_convert_to_b_deep($value);
				$value_serialized = maybe_serialize($value_converted);
				if($value_serialized === $row->meta_value) continue;
				$wpdb->query($wpdb->prepare('UPDATE '.$wpdb->postmeta.' set meta_value = %s WHERE meta_id = %d', $value_serialized, $row->meta_id));
			}
			break;
		case 'c_dual':
			foreach($result as $row) {
				if(!qtranxf_isMultilingual($row->meta_value)) continue;
				$value = maybe_unserialize($row->meta_value);
				$value_converted=qtranxf_convert_to_b_no_closing_deep($value);
				$value_serialized = maybe_serialize($value_converted);
				if($value_serialized === $row->meta_value) continue;
				$wpdb->query($wpdb->prepare('UPDATE '.$wpdb->postmeta.' set meta_value = %s WHERE meta_id = %d', $value_serialized, $row->meta_id));
			}
			break;
		default: break;
	}
}

/**
 */
function qtranxf_split_database_file($ifp,$languages_to_keep){
	global $q_config;
	$errors = $q_config['url_info']['errors'];
	$ifh = fopen($ifp,'r');
	if(!$ifh){
		$errors[] = sprintf(__('Failed to open input database file "%s"','qtranslate'), $ifp);
		return '';
	}
	$fld = dirname($ifp);
	$bnm = basename($ifp, '.sql');

	$q_config['enabled_languages'] = $languages_to_keep;
	$default_language = $languages_to_keep[0];

	$dfp = $fld.'/'.$bnm.'-'.$default_language.'.sql';
	$dfh = fopen($dfp,'w');
	if(!$dfh){
		fclose($ifh);
		$errors[] = sprintf(__('Failed to open output database file "%s"','qtranslate'), $dfp);
		return '';
	}
	$files[$default_language] = array('fp' => $dfp, 'fh' => $dfh);

	$mfp ='';
	$mfh = null;
	$lang2keep = array();
	$n = count($languages_to_keep);
	if($n > 1){
		$sfx = $n < 5 ? implode('-',$languages_to_keep) : $n.'_languages_requested';
		$mfp = $fld.'/'.$bnm.'-'.$sfx.'.sql';
		$mfh = fopen($mfp,'w');
		if(!$mfh){
			fclose($ifh);
			fclose($dfh);
			$errors[] = sprintf(__('Failed to open output database file "%s"','qtranslate'), $mfp);
			return '';
		}
	}

	foreach($languages_to_keep as $lang){
		$lang2keep[$lang] = $lang;
	}

	$cnt = 0;
	while(($s = fgets($ifh))){
		if(qtranxf_isMultilingual($s)){
			++$cnt;
			$lns = qtranxf_split($s);
			$ok = true;
			foreach($lns as $lang => $ln){
				if(!isset($lang2keep[$lang]) && !isset($files[$lang])){
					$ok = false;
					$q_config['enabled_languages'][] = $lang;
					$lfp = $fld.'/'.$bnm.'-'.$lang.'.sql';
					fflush($dfh);
					//$pos = ftell($dfh);
					//fclose($dfh); unset($files[$default_language]['fh']);
					copy($dfp,$lfp);
					$lfh = fopen($lfp,'a+');
					//$dfh = fopen($dfp,'a+');
					if(!$lfh || !$dfh){
						fclose($ifh);
						foreach($files as $lang => &$file){
							if(!isset($file['fh'])) continue;
							fclose($file['fh']);
						}
						if(!$lfh)
							$errors[] = sprintf(__('Failed to open output database file "%s"','qtranslate'), $lfp);
						if(!$dfh)
							$errors[] = sprintf(__('Failed to re-open output database file "%s"','qtranslate'), $dfp);
						return '';
					}
					//$files[$default_language]['fh'] = $dfh;
					$files[$lang] = array('fp' => $lfp, 'fh' => $lfh);
				}
			}
			if(!$ok)
				$lns = qtranxf_split($s);
			if($mfh){
				foreach($languages_to_keep as $lang){
					if($default_language != $lang)
						unset($lns[$lang]);
				}
				$ln = qtranxf_extract_languages($s,$lang2keep);
				fputs($mfh,$ln);
			}
			foreach($lns as $lang => $ln){
				fputs($files[$lang]['fh'],$ln.PHP_EOL);
			}
		}else{
			foreach($files as $lang => &$file){
				fputs($file['fh'],$s);
			}
			if($mfh)
				fputs($mfh,$s);
		}
	}
	fclose($ifh);
	foreach($files as $lang => &$file){
		fclose($file['fh']);
	}
	if($mfh){
		fclose($mfh);
		if(isset($lang2keep[$default_language])){
			unlink($files[$default_language]['fp']);
			unset($files[$default_language]);
		}
	}
	$fns = PHP_EOL.'<br/>'.implode(PHP_EOL.'<br/>',array_column($files,'fp'));
	if($mfp)
		$fns = PHP_EOL.'<br/>'.$mfp.$fns;
	return sprintf(__('The database file provided has been split as requested. Number of multilingual strings found is %s. The result files are:%s','qtranslate'), $cnt, $fns);
}

function qtranxf_extract_languages($text,$lang2keep) {
	$blocks = qtranxf_get_language_blocks($text);
	$s = '';
	$current_language = false;
	$eol = false;
	foreach($blocks as $block) {
		// detect c-tags
		if(preg_match("#^<!--:([a-z]{2})-->$#ism", $block, $matches)) {
			$current_language = $matches[1];
			if(isset($lang2keep[$current_language])) { $s .= $block; $eol = true; }
			continue;
			// detect b-tags
		}elseif(preg_match("#^\[:([a-z]{2})\]$#ism", $block, $matches)) {
			$current_language = $matches[1];
			if(isset($lang2keep[$current_language])) { $s .= $block; $eol = true; }
			continue;
			// detect s-tags @since 3.3.6 swirly bracket encoding added
		}elseif(preg_match("#^\{:([a-z]{2})\}$#ism", $block, $matches)) {
			$current_language = $matches[1];
			if(isset($lang2keep[$current_language])) { $s .= $block; $eol = true; }
			continue;
		}
		switch($block){
			case '[:]':
			case '{:}':
			case '<!--:-->':
				if($eol) $s .= $block;
				$current_language = false;
				$eol = false;
				break;
			default:
				if($current_language){
					if(isset($lang2keep[$current_language])) $s .= $block;
					$current_language = false;
					$eol = true;
				}else{
					$s .= $block;
				}
				break;
		}
	}
	return $s;
}
