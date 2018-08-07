/* executed for
 /wp-admin/post.php
 /wp-admin/post-new.php
*/
jQuery(document).ready(
function($){
	var qtx = qTranslateConfig.js.get_qtx();
	//co('post.php: qtx: ',qtx);

	var post_name_field = document.getElementById('post_name');

	var set_post_name = function(lang)
	{
		if(!qtx.hasContentHook('post_name')) return;
		new_post_slug_field = $('#new-post-slug');
		if(new_post_slug_field.length){
			new_post_slug_field.attr('value',post_name_field.value);
		}else{
			//if(!editable_post_name_field) editable_post_name_field = $('#editable-post-name');
			//if(editable_post_name_field.length) editable_post_name_field.text(post_name_field.value);
			//if(!editable_post_name_full_field) editable_post_name_full_field = $('#editable-post-name-full');
			//if(editable_post_name_full_field.length) editable_post_name_full_field.text(post_name_field.value);

			$('#editable-post-name').text(post_name_field.value)
			$('#editable-post-name-full').text(post_name_field.value)
		}
	}

	var canSwitchLang = function(lang)
	{
		if(!qtx.hasContentHook('post_name'))
			return true;
		new_post_slug_field = $('#new-post-slug');
		//co('canSwitchLang: new_post_slug_field.length=', new_post_slug_field.length);
		if(new_post_slug_field.length){
			alert('Please, finish current slug editing before switching the active edit language.');
			//alert(qTranslateConfig.i18n_strings.finish_current_slug_editing);
			return false;
		}
		return true;
	}

	qtx.addLanguageSwitchBeforeListener(canSwitchLang);
	qtx.addLanguageSwitchAfterListener(set_post_name);
});
