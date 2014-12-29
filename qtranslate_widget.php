<?php // encoding: utf-8
/*
	Copyright 2014  qTranslate Team  (email : qTranslateTeam@gmail.com )

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

/* qTranslate-X Widget */

class qTranslateXWidget extends WP_Widget {
	function qTranslateXWidget() {
		$widget_ops = array('classname' => 'qtranxs-widget', 'description' => __('Allows your visitors to choose a Language.', 'qtranslate') );
		$this->WP_Widget('qtranslate', __('qTranslate Language Chooser', 'qtranslate'), $widget_ops);
	}
	
	function widget($args, $instance) {
		extract($args);
		
		echo $before_widget;
                $title = empty($instance['title']) ? __('Language', 'qtranslate') : apply_filters('widget_title', $instance['title']);
		$hide_title = empty($instance['hide-title']) ? false : 'on';
		$type = $instance['type'];
		if($type!='text'&&$type!='image'&&$type!='both'&&$type!='dropdown') $type='text';

		if($hide_title!='on') { echo $before_title . $title . $after_title; };
								qtranxf_generateLanguageSelectCode($type, $this->id);
		echo $after_widget;
	}
	
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = $new_instance['title'];
		if(isset($new_instance['hide-title'])) $instance['hide-title'] = $new_instance['hide-title'];
		$instance['type'] = $new_instance['type'];

		return $instance;
	}
	
	function form($instance) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'hide-title' => false, 'type' => 'text' ) );
		$title = $instance['title'];
		$hide_title = $instance['hide-title'];
		$type = $instance['type'];
?>
                <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'qtranslate'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
                <p><label for="<?php echo $this->get_field_id('hide-title'); ?>"><?php _e('Hide Title:', 'qtranslate'); ?> <input type="checkbox" id="<?php echo $this->get_field_id('hide-title'); ?>" name="<?php echo $this->get_field_name('hide-title'); ?>" <?php echo ($hide_title=='on')?'checked="checked"':''; ?>/></label></p>
                <p><?php _e('Display:', 'qtranslate'); ?></p>
                <p><label for="<?php echo $this->get_field_id('type'); ?>1"><input type="radio" name="<?php echo $this->get_field_name('type'); ?>" id="<?php echo $this->get_field_id('type'); ?>1" value="text"<?php echo ($type=='text')?' checked="checked"':'' ?>/> <?php _e('Text only', 'qtranslate'); ?></label></p>
                <p><label for="<?php echo $this->get_field_id('type'); ?>2"><input type="radio" name="<?php echo $this->get_field_name('type'); ?>" id="<?php echo $this->get_field_id('type'); ?>2" value="image"<?php echo ($type=='image')?' checked="checked"':'' ?>/> <?php _e('Image only', 'qtranslate'); ?></label></p>
                <p><label for="<?php echo $this->get_field_id('type'); ?>3"><input type="radio" name="<?php echo $this->get_field_name('type'); ?>" id="<?php echo $this->get_field_id('type'); ?>3" value="both"<?php echo ($type=='both')?' checked="checked"':'' ?>/> <?php _e('Text and Image', 'qtranslate'); ?></label></p>
                <p><label for="<?php echo $this->get_field_id('type'); ?>4"><input type="radio" name="<?php echo $this->get_field_name('type'); ?>" id="<?php echo $this->get_field_id('type'); ?>4" value="dropdown"<?php echo ($type=='dropdown')?' checked="checked"':'' ?>/> <?php _e('Dropdown Box', 'qtranslate'); ?></label></p>
<?php
	}
}

// Language Select Code for non-Widget users
function qtranxf_generateLanguageSelectCode($style='', $id='') {
	global $q_config;
	if($style=='') $style='text';
	if(is_bool($style)&&$style) $style='image';
	if(is_404()) $url = get_option('home'); else $url = '';
        if($id=='') $id = 'qtranslate';
	$id .= '-chooser';
	switch($style) {
		case 'image':
		case 'text':
		case 'dropdown':
												echo '<ul class="qtranxf_language_chooser" id="'.$id.'">';
												foreach(qtranxf_getSortedLanguages() as $language) {
				$classes = array('lang-'.$language);
				if($language == $q_config['language'])
					$classes[] = 'active';
																echo '<li class="'. implode(' ', $classes) .'"><a href="'.qtranxf_convertURL($url, $language, false, true).'"';
				// set hreflang
				echo ' hreflang="'.$language.'" title="'.$q_config['language_name'][$language].'"';
				if($style=='image')
																				//echo ' class="qtranxf_flag qtranxf_flag_'.$language.'"';
				echo '>';
				if($style=='image')
				{
					echo '<img src="'.trailingslashit(WP_CONTENT_URL).$q_config['flag_location'].$q_config['flag'][$language].'"></img>';
				}
				echo '<span';
				if($style=='image')
					echo ' style="display:none"';
				echo '>'.$q_config['language_name'][$language].'</span></a></li>';
			}
												echo "</ul><div class=\"qtranxf_widget_end\"></div>";
			if($style=='dropdown') {
				echo "<script type=\"text/javascript\">\n// <![CDATA[\r\n";
				echo "var lc = document.getElementById('".$id."');\n";
				echo "var s = document.createElement('select');\n";
																echo "s.id = 'qtranxs_select_".$id."';\n";
				echo "lc.parentNode.insertBefore(s,lc);";
				// create dropdown fields for each language
																foreach(qtranxf_getSortedLanguages() as $language) {
																				echo qtranxf_insertDropDownElement($language, qtranxf_convertURL($url, $language, false, true), $id);
				}
				// hide html language chooser text
				echo "s.onchange = function() { document.location.href = this.value;}\n";
				echo "lc.style.display='none';\n";
				echo "// ]]>\n</script>\n";
			}
			break;
		case 'both':
												echo '<ul class="qtranxf_language_chooser" id="'.$id.'">';
												foreach(qtranxf_getSortedLanguages() as $language) {
				echo '<li';
				if($language == $q_config['language'])
					echo ' class="active"';
																echo '><a href="'.qtranxf_convertURL($url, $language).'"';
																echo ' class="qtranxf_flag_'.$language.' qtranxf_flag_and_text" title="'.$q_config['language_name'][$language].'">';
				echo '<img src="'.trailingslashit(WP_CONTENT_URL).$q_config['flag_location'].$q_config['flag'][$language].'"></img>';
				echo '<span>'.$q_config['language_name'][$language].'</span></a></li>';
			}
												echo "</ul><div class=\"qtranxf_widget_end\"></div>";
			break;
	}
}

function qtranxf_widget_init() {
				register_widget('qTranslateXWidget');
}

?>
