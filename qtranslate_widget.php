<?php
if ( !defined( 'ABSPATH' ) ) exit;

define('QTX_WIDGET_CSS',
'.qtranxs_widget ul { margin: 0; }
.qtranxs_widget ul li
{
display: inline; /* horizontal list, use "list-item" or other appropriate value for vertical list */
list-style-type: none; /* use "initial" or other to enable bullets */
margin: 0 5px 0 0; /* adjust spacing between items */
opacity: 0.5;
-o-transition: 1s ease opacity;
-moz-transition: 1s ease opacity;
-webkit-transition: 1s ease opacity;
transition: 1s ease opacity;
}
/* .qtranxs_widget ul li span { margin: 0 5px 0 0; } */ /* other way to control spacing */
.qtranxs_widget ul li.active { opacity: 0.8; }
.qtranxs_widget ul li:hover { opacity: 1; }
.qtranxs_widget img { box-shadow: none; vertical-align: middle; }
.qtranxs_flag { height:12px; width:18px; display:block; }
.qtranxs_flag_and_text { padding-left:20px; }
.qtranxs_flag span { display:none; }
');

/* qTranslate-X Widget */

class qTranslateXWidget extends WP_Widget {

	function qTranslateXWidget() {
		$widget_ops = array('classname' => 'qtranxs_widget', 'description' => __('Allows your visitors to choose a Language.', 'qtranslate') );
		$this->WP_Widget('qtranslate', __('qTranslate Language Chooser', 'qtranslate'), $widget_ops);
	}

	function widget($args, $instance) {
		extract($args);
		//qtranxf_dbg_log('widget: $this: ',$this);
		//qtranxf_dbg_log('widget: $instance: ',$instance);
		if(!isset($instance['widget-css-off'])){
			echo '<style type="text/css">'.PHP_EOL;
			echo empty($instance['widget-css']) ? QTX_WIDGET_CSS : $instance['widget-css'];
			echo '</style>'.PHP_EOL;
		}
		echo $before_widget;
		if(empty($instance['hide-title'])) {
			$title = $instance['title'];
			if(empty($title))
				$title=__('Language', 'qtranslate');
			if(empty($instance['hide-title-colon']))
				$title .= ':';
			$title=apply_filters('qtranslate_widget_title',$title,$this);
			echo $before_title . $title . $after_title;
		}
		$type = $instance['type'];
		if($type!='text'&&$type!='image'&&$type!='both'&&$type!='dropdown') $type='text';
		qtranxf_generateLanguageSelectCode($type, $this->id);
		echo $after_widget;
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		//qtranxf_dbg_log('update: $new_instance: ',$new_instance);
		//qtranxf_dbg_log('update: $old_instance: ',$old_instance);
		$instance['title'] = $new_instance['title'];

		if(isset($new_instance['hide-title'])) $instance['hide-title'] = true;
		else unset($instance['hide-title']);

		if(isset($new_instance['hide-title-colon'])) $instance['hide-title-colon'] = true;
		else unset($instance['hide-title-colon']);

		$instance['type'] = $new_instance['type'];

		if(isset($new_instance['widget-css-on'])) unset($instance['widget-css-off']);
		else $instance['widget-css-off'] = true;

		$instance['widget-css'] = $new_instance['widget-css'];

		return $instance;
	}

	function form($instance) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'type' => 'text', 'widget-css' => QTX_WIDGET_CSS ) );
		$title = $instance['title'];
		$hide_title = isset($instance['hide-title']) && $instance['hide-title'] !== false;
		$hide_title_colon = isset($instance['hide-title-colon']);
		$type = $instance['type'];
		$widget_css_on = !isset($instance['widget-css-off']);
		$widget_css = $instance['widget-css'];
		if(empty($widget_css)) $widget_css=QTX_WIDGET_CSS;
?>
<p><label for="<?php echo $this->get_field_id('title') ?>"><?php _e('Title:', 'qtranslate') ?> <input class="widefat" id="<?php echo $this->get_field_id('title') ?>" name="<?php echo $this->get_field_name('title') ?>" type="text" value="<?php echo esc_attr($title) ?>" /></label></p>
<p><label for="<?php echo $this->get_field_id('hide-title') ?>"><?php _e('Hide Title:', 'qtranslate') ?> <input type="checkbox" id="<?php echo $this->get_field_id('hide-title') ?>" name="<?php echo $this->get_field_name('hide-title') ?>" <?php checked($hide_title) ?>/></label></p>
<p><label for="<?php echo $this->get_field_id('hide-title-colon') ?>"><?php _e('Hide Title Colon:', 'qtranslate') ?> <input type="checkbox" id="<?php echo $this->get_field_id('hide-title-colon') ?>" name="<?php echo $this->get_field_name('hide-title-colon') ?>" <?php checked($hide_title_colon) ?>/></label></p>
<p><?php _e('Display:', 'qtranslate') ?></p>
<p><label for="<?php echo $this->get_field_id('type') ?>1"><input type="radio" name="<?php echo $this->get_field_name('type') ?>" id="<?php echo $this->get_field_id('type') ?>1" value="text"<?php echo ($type=='text')?' checked="checked"':'' ?>/> <?php _e('Text only', 'qtranslate') ?></label></p>
<p><label for="<?php echo $this->get_field_id('type') ?>2"><input type="radio" name="<?php echo $this->get_field_name('type') ?>" id="<?php echo $this->get_field_id('type') ?>2" value="image"<?php echo ($type=='image')?' checked="checked"':'' ?>/> <?php _e('Image only', 'qtranslate') ?></label></p>
<p><label for="<?php echo $this->get_field_id('type') ?>3"><input type="radio" name="<?php echo $this->get_field_name('type') ?>" id="<?php echo $this->get_field_id('type') ?>3" value="both"<?php echo ($type=='both')?' checked="checked"':'' ?>/> <?php _e('Text and Image', 'qtranslate') ?></label></p>
<p><label for="<?php echo $this->get_field_id('type') ?>4"><input type="radio" name="<?php echo $this->get_field_name('type') ?>" id="<?php echo $this->get_field_id('type') ?>4" value="dropdown"<?php echo ($type=='dropdown')?' checked="checked"':'' ?>/> <?php _e('Dropdown Box', 'qtranslate') ?></label></p>
<p><label for="<?php echo $this->get_field_id('widget-css') ?>"><input type="checkbox" id="<?php echo $this->get_field_id('widget-css-on') ?>" name="<?php echo $this->get_field_name('widget-css-on') ?>" <?php checked($widget_css_on) ?>/><?php echo __('Widget CSS:', 'qtranslate') ?></label><br/><textarea class="widefat" rows="6" name="<?php echo $this->get_field_name('widget-css') ?>" id="<?php echo $this->get_field_id('widget-css') ?>"><?php echo esc_attr($widget_css) ?></textarea><br/><small><?php echo __('To reset to default, clear the text.', 'qtranslate').' '.__('To disable this inline CSS, clear the check box.', 'qtranslate').' '.sprintf(__('Other common CSS block for flag classes "%s" is loaded in the head of HTML and can be controlled with option "%s".', 'qtranslate'), 'qtranxs_flag_xx', __('Head inline CSS','qtranslate')) ?></small></p>
<?php
/*

*/
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
	$flag_location=qtranxf_flag_location();
	switch($style) {
		case 'image':
		case 'text':
		case 'dropdown':
			echo PHP_EOL.'<ul class="qtranxs_language_chooser" id="'.$id.'">'.PHP_EOL;
			foreach(qtranxf_getSortedLanguages() as $language) {
				$classes = array('lang-'.$language);
				if($language == $q_config['language']) $classes[] = 'active';
				echo '<li class="'. implode(' ', $classes) .'"><a href="'.qtranxf_convertURL($url, $language, false, true).'"';
				//echo '<li'; if($language == $q_config['language']) echo ' class="active"';
				//echo '><a href="'.qtranxf_convertURL($url, $language, false, true).'"';
				// set hreflang
				echo ' hreflang="'.$language.'"';
				echo ' title="'.$q_config['language_name'][$language].'"';
				if($style=='image')
					echo ' class="qtranxs_image qtranxs_image_'.$language.'"';
				//	echo ' class="qtranxs_flag qtranxs_flag_'.$language.'"';
				elseif($style=='text')
					echo ' class="qtranxs_text qtranxs_text_'.$language.'"';
				echo '>';
				if($style=='image') echo '<img src="'.$flag_location.$q_config['flag'][$language].'" alt="'.$q_config['language_name'][$language].'" />';
				echo '<span';
				if($style=='image') echo ' style="display:none"';
				echo '>'.$q_config['language_name'][$language].'</span>';
				echo '</a></li>'.PHP_EOL;
			}
			echo '</ul><div class="qtranxs_widget_end"></div>'.PHP_EOL;
			if($style=='dropdown') {
				echo '<script type="text/javascript">'.PHP_EOL.'// <![CDATA['.PHP_EOL;
				echo "var lc = document.getElementById('".$id."');".PHP_EOL;
				echo "var s = document.createElement('select');".PHP_EOL;
				echo "s.id = 'qtranxs_select_".$id."';".PHP_EOL;
				echo "lc.parentNode.insertBefore(s,lc);".PHP_EOL;
				// create dropdown fields for each language
				foreach(qtranxf_getSortedLanguages() as $language) {
					echo qtranxf_insertDropDownElement($language, qtranxf_convertURL($url, $language, false, true), $id);
				}
				// hide html language chooser text
				echo "s.onchange = function() { document.location.href = this.value;}".PHP_EOL;
				echo "lc.style.display='none';".PHP_EOL;
				echo '// ]]>'.PHP_EOL.'</script>'.PHP_EOL;
			}
			break;
		case 'both':
			echo PHP_EOL.'<ul class="qtranxs_language_chooser" id="'.$id.'">'.PHP_EOL;
			foreach(qtranxf_getSortedLanguages() as $language) {
				echo '<li';
				if($language == $q_config['language'])
					echo ' class="active"';
				echo '><a href="'.qtranxf_convertURL($url, $language, false, true).'"';
				echo ' class="qtranxs_flag_'.$language.' qtranxs_flag_and_text" title="'.$q_config['language_name'][$language].'">';
				//echo '<img src="'.$flag_location.$q_config['flag'][$language].'"></img>';
				echo '<span>'.$q_config['language_name'][$language].'</span></a></li>'.PHP_EOL;
			}
			echo '</ul><div class="qtranxs_widget_end"></div>'.PHP_EOL;
			break;
	}
}

function qtranxf_widget_init() {
	register_widget('qTranslateXWidget');
	do_action('qtranslate_widget_init');
}
