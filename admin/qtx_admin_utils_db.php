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
			} break;
		case 'db_clean_terms': return gtranxf_db_clean_terms();
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

function qtranxf_convert_to_b($text) {
	$blocks = qtranxf_get_language_blocks($text);
	if( count($blocks) > 1 ){
		foreach($blocks as $key => $b){
			if(empty($b)) unset($blocks[$key]);
		}
	}
	if( count($blocks) <= 1 )
		return $text;

	$text='';
	$lang = false;
	$lang_closed = true;
	foreach($blocks as $block) {
		if(preg_match("#^<!--:([a-z]{2})-->$#ism", $block, $matches)) {
			$lang_closed = false;
			$lang = $matches[1];
			$text .= '[:'.$lang.']';
			continue;
		} elseif(preg_match("#^\[:([a-z]{2})\]$#ism", $block, $matches)) {
			$lang_closed = false;
			$lang = $matches[1];
			$text .= '[:'.$lang.']';
			continue;
		}
		switch($block){
			case '[:]':
			case '<!--:-->':
				$lang = false;
				break;
			default:
				if( !$lang && !$lang_closed ){
					$text .= '[:]';
					$lang_closed = true;
				}
				$text .= $block;
				break;
		}
	}
	$text .= '[:]';
	return $text;
}

function qtranxf_convert_to_b_no_closing($text) {
	$blocks = qtranxf_get_language_blocks($text);
	if( count($blocks) > 1 ){
		foreach($blocks as $key => $b){
			if(empty($b)) unset($blocks[$key]);
		}
	}
	if( count($blocks) > 1 ){
		$texts = qtranxf_split_blocks($blocks);
		$text = qtranxf_join_b_no_closing($texts);
	}
	return $text;
}

function qtranxf_convert_to_c($text) {
	$blocks = qtranxf_get_language_blocks($text);
	if( count($blocks) > 1 ){
		foreach($blocks as $key => $b){
			if(empty($b)) unset($blocks[$key]);
		}
	}
	if( count($blocks) > 1 ){
		$texts = qtranxf_split_blocks($blocks);
		$text = qtranxf_join_c($texts);
	}
	return $text;
}

function qtranxf_convert_to_b_deep($text) {
	if(is_array($text)) {
		foreach($text as $key => $t) {
			$text[$key] = qtranxf_convert_to_b_deep($t);
		}
		return $text;
	}

	if( is_object($text) || $text instanceof __PHP_Incomplete_Class ) {
		foreach(get_object_vars($text) as $key => $t) {
			$text->$key = qtranxf_convert_to_b_deep($t);
		}
		return $text;
	}

	if(!is_string($text) || empty($text))
		return $text;

	return qtranxf_convert_to_b($text);
}

function qtranxf_convert_to_b_no_closing_deep($text) {
	if(is_array($text)) {
		foreach($text as $key => $t) {
			$text[$key] = qtranxf_convert_to_b_no_closing_deep($t);
		}
		return $text;
	}

	if( is_object($text) || $text instanceof __PHP_Incomplete_Class ) {
		foreach(get_object_vars($text) as $key => $t) {
			$text->$key = qtranxf_convert_to_b_no_closing_deep($t);
		}
		return $text;
	}

	if(!is_string($text) || empty($text))
		return $text;

	return qtranxf_convert_to_b_no_closing($text);
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
					copy($dfp,$lfp);
					$lfh = fopen($lfp,'a+');
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

function gtranxf_db_clean_terms(){
	global $wpdb, $q_config;
	$errors = &$q_config['url_info']['errors'];
	$messages = &$q_config['url_info']['messages'];
	$wpdb->show_errors(); @set_time_limit(0);
	$result = $wpdb->get_results('SELECT * FROM '.$wpdb->terms);
	if(!$result){
		$errors[] = __('Could not fetch the list of terms from database.', 'qtranslate');
		return __('Nothing has been altered.', 'qtranslate');
	}
	qtranxf_term_admin_remove_filters();
	$default_langauge = $q_config['default_language'];
	$term_name_cur = $q_config['term_name'];
	$q_config['term_name'] = array();//to exclude any possible alternations of term names via filters.
	$term_name = array();
	$msgs = array();
	foreach($result as $row){
		$id = $row->term_id;
		$nm = $row->name;
		$term = get_term($id);
		if( ! ( $term instanceof WP_Term ) ){
			if($term instanceof WP_Error){
				$errs = $term->get_error_messages();
			}else{
				$errs = array( __('Term configuration is inconsistent.', 'qtranslate') );
			}
			//qtranxf_dbg_log('gtranxf_db_clean_terms: invalid term $id='.$id.', name='.$nm.' Error:', $errs);
			$messages[] = sprintf(__('Term "%s" (id=%d) cannot be loaded and is left untouched. Error message on load was:%s', 'qtranslate'), $nm, $id, '<br/>'.PHP_EOL.'"'.implode('"<br/>"'.PHP_EOL, $errs).'"') . '<br/>' . $msg;
			continue;
		}else{
			$taxonomy = $term->taxonomy;
		}
		if($taxonomy == 'nav_menu')
			continue;
		$ts = array();
		if(qtranxf_isMultilingual($nm)){
			$ts = qtranxf_split($nm);
			if(empty($ts[$default_langauge])){
				foreach( $q_config['enabled_languages'] as $lng ){
					$v = trim($ts[$lng]);
					$ts[$lng] = $v;
					if(empty($v)) continue;
					$ts[$default_langauge] = $v;
					break;
				}
			}
		}else if(isset($term_name_cur[$nm]) && is_array($term_name_cur[$nm]) && !empty($term_name_cur[$nm])){
			$ts = $term_name_cur[$nm];
		}else{
			continue;
		}
		$nm_cur = $nm;
		$ts_cur = empty($term_name_cur[$nm]) ? array() : $term_name_cur[$nm];

		foreach( $ts as $lng => $v){
			$val = trim($v);
			$val = addslashes($val);
			$val = apply_filters('pre_term_name',$val);
			$val = stripcslashes($val);
			$ts[$lng] = $val;
		}
		$ok = !empty($ts[$default_langauge]);
		if(!$ok){
			$ts[$default_langauge] = $nm_cur;
		}else{
			$ok = ($ts[$default_langauge] == $nm_cur);
		}
		if( !$ok ){
			$nm = $ts[$default_langauge];
			//qtranxf_dbg_log('gtranxf_db_clean_terms: term $id='.$id.', name='.$nm_cur.' is replaced with ', $nm);
			wp_update_term( $id, $taxonomy, array('name' => $nm) );
		}
		$term_name[$nm] = $ts;
		if($ok && !empty($ts_cur)){
			$ok = qtranxf_array_compare($ts,$ts_cur);
		}
		if($ok)
			$ok = ($nm == $nm_cur);
		if($ok)
			continue;
		//report the change
		$ts_old = array();
		foreach($ts_cur as $lng => $val){
			$ts_old[] = $lng.' => "'.esc_html($val).'"';
		}
		$ts_new = array();
		foreach($ts as $lng => $val){
			$ts_new[] = $lng.' => "'.esc_html($val).'"';
		}
		$msgs[] = sprintf(__('Term "%s" (id=%d) has been modified from:%sto:%s', 'qtranslate'), esc_html($nm), $id
		, '<br/>'.PHP_EOL . '"' . esc_html($nm_cur) . '" => { ' . implode(', ', $ts_old) . ' }<br/>'.PHP_EOL
		, '<br/>'.PHP_EOL . '"' . esc_html($nm    ) . '" => { ' . implode(', ', $ts_new) . ' }<br/>'.PHP_EOL
		);
	}

	if(!qtranxf_array_compare($term_name_cur,$term_name)){
		//qtranxf_dbg_log('gtranxf_db_clean_terms: old $term_name: ', $term_name_cur);
		//qtranxf_dbg_log('gtranxf_db_clean_terms: new $term_name: ', $term_name);
		update_option('qtranslate_term_name', $term_name);
	}
	$q_config['term_name'] = $term_name;
	qtranxf_term_admin_add_filters();
	if(empty($msgs)){
		$msg = __('No term has been modified. All terms are already in a consistent state.', 'qtranslate');
	}else{
		$msg = '<ol>'.PHP_EOL.'<li>'.join( '</li>'.PHP_EOL.'<li>', $msgs).'</li>'.PHP_EOL.'</ol>' .PHP_EOL;
		$msg .= __('Save this report for further analysis, if necessary.', 'qtranslate');
	}
	return __('Legacy term names have been cleaned up:', 'qtranslate') . '<br/>'.PHP_EOL . $msg .PHP_EOL;
}
