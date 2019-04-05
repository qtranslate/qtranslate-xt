
jQuery(window).load(function() {

	// Only proceed if qTranslate is loaded
	if (typeof qTranslateConfig != 'object' || typeof qTranslateConfig.qtx != 'object') {
		return;
	}

	// Enable the language switching buttons
	qTranslateConfig.qtx.enableLanguageSwitchingButtons('block');


	// Ensure that translation of standard field types is enabled
	if (!window.acf_qtranslate_translate_standard_field_types) {
		return;
	}

	// Selectors for supported field types
	var field_types = [
		'.field_type-text input:text',
		'.field_type-textarea textarea',
		'.field_type-wysiwyg .wp-editor-area'
	].join(',');

	// Remove content hooks from ACF Fields
	jQuery('.acf_postbox .field').find('.qtranxs-translatable').each(function() {
		qTranslateConfig.qtx.removeContentHook(this);
	});

	var post_type = jQuery('#post_type').val();

	// Whitelist fields for translation
	function isTranslatableField(field){
		if (post_type === 'acf-field-group') {
			if (field.id.match(/acf-field-field_[a-z0-9]+_label/))         return true;
			if (field.id.match(/acf-field-field_[a-z0-9]+_instructions/))  return true;
			if (field.id.match(/acf-field-field_[a-z0-9]+_default_value/)) return true;
			return false;
		}
		return true;
	}

	// Watch and add content hooks when new fields are added
	jQuery(document).on('acf/setup_fields', function(e, new_field) {
		new_field = jQuery(new_field);
		new_field.find(field_types).not('.qtranxs-translatable').each(function() {
			var field = jQuery(this);

			// Skip over fields inside of ACF Repeater
			// and Flexible Content clone rows
			if (field.parents('.row-clone').length) {
				return;
			}

			if (!isTranslatableField(field)) return;

			qTranslateConfig.qtx.addContentHookC(this, field.closest('form').get(0));

			// Since ACFv4 doesn't update tinyMCEPreInit.mceInit so we
			// need to manully set it so that the translation hooks apply properly
			if (field.hasClass('wp-editor-area')) {
				if (typeof tinyMCEPreInit.mceInit[this.id] == 'undefined') {
					var mceInit = jQuery.extend({}, tinyMCEPreInit.mceInit.acf_settings);
					mceInit.id = this.id;
					tinyMCEPreInit.mceInit[this.id] = mceInit;
				}
			}
		});

		// Run in a setTimeout block to give the tinyMCE instance
		// enough time to initialize before setting the editor hooks
		setTimeout(function(){
			jQuery.each(tinyMCE.editors, function(i, ed){
				setEditorHooks(ed);
			});
		},50);
	});

	// Watch and remove content hooks when fields are removed
	jQuery('body').on('click', '.row .acf-button-remove', function() {
		var row = jQuery(this).closest('.row');
		row.find(field_types).filter('.qtranxs-translatable').each(function() {
			qTranslateConfig.qtx.removeContentHook(this);
		});
	});


	// Extracted from qTranslate-X
	// admin/js/common.js#L840
	function setEditorHooks(ed) {
		var id = ed.id;
		if (!id) return;
		var h=qTranslateConfig.qtx.hasContentHook(id);
		if(!h || h.mce) return;
		h.mce=ed;
		ed.getContainer().className += ' qtranxs-translatable';
		ed.getElement().className += ' qtranxs-translatable';
		var updateTinyMCEonInit = h.updateTinyMCEonInit;
		if (updateTinyMCEonInit == null) {
			var text_e = ed.getContent({format: 'html'}).replace(/\s+/g,'');
			var text_h = h.contentField.value.replace(/\s+/g,'');
			updateTinyMCEonInit = text_e != text_h;
		}
		if (updateTinyMCEonInit) {
			text = h.contentField.value;
			if (h.wpautop && window.switchEditors) {
				text = window.switchEditors.wpautop(text);
			}
			h.mce.setContent(text,{format: 'html'});
		}
		return h;
	}

});
