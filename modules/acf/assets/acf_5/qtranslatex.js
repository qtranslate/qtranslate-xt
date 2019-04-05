
(function(){

	var windowLoadCompleted = false;
	jQuery(window).load(function() {

		// Prevent from being triggered again
		if (windowLoadCompleted) {
			return;
		}

		windowLoadCompleted = true;

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
		var field_types = {
			text:      'input:text',
			textarea:  'textarea',
			wysiwyg:   '.wp-editor-area',
		};

		// Remove content hooks from ACF Fields
		jQuery('.acf-postbox .acf-field').find('.qtranxs-translatable').each(function() {
			qTranslateConfig.qtx.removeContentHook(this);
		});

		var post_type = jQuery('#post_type').val();

		// Whitelist fields for translation
		function isTranslatableField(field){
			if (post_type === 'acf-field-group') {
				if (field.id.match(/acf_fields-\d+-label/))         return true;
				if (field.id.match(/acf_fields-\d+-instructions/))  return true;
				if (field.id.match(/acf_fields-\d+-default_value/)) return true;
				return false;
			}
			return true;
		}

		// Setup field types
		jQuery.each(field_types, function(field_type, selector) {

			// Add content hooks for existing fields
			acf.get_fields({ type: field_type }).each(function() {
				var form = jQuery(this).closest('form').get(0);
				var field = jQuery(this).find(selector).get(0);
				if (!isTranslatableField(field)) return;
				qTranslateConfig.qtx.addContentHookC(field, form);
			});

			// Watch and add content hooks when new fields are added
			acf.add_action('append_field/type=' + field_type, function($el) {
				var form = $el.closest('form').get(0);
				var field = $el.find(selector).get(0);
				if (!isTranslatableField(field)) return;
				qTranslateConfig.qtx.addContentHookC(field, form);

				if (jQuery(field).hasClass('wp-editor-area')) {
					//qTranslateConfig.qtx.addContentHooksTinyMCE();

					// We must manually trigger load event so that the
					// loadTinyMceHooks function which calls setEditorHooks is executed
					var loadEvent = document.createEvent('UIEvents');
					loadEvent.initEvent('load',false,false,window);
					window.dispatchEvent(loadEvent);
				}

				// Run at higher integer priority than the default in case the ACF handlers
				// change the id of the underlying input
			}, 100);

		});

		//qTranslateConfig.qtx.addContentHooksTinyMCE();

		// Watch and remove content hooks when fields are removed
		// however ACF removes the elements from the DOM early so
		// we must hook into handler and perform updates there
		var repeaterFieldRemove;

		if (acf.models) {
			repeaterFieldRemove = acf.models.RepeaterField.prototype.remove;
		} else {
			repeaterFieldRemove = acf.fields.repeater.remove;
		}

		function repeaterRemove($el) {
			var row = ($el.$el || $el).closest('.acf-row'); // support old versions of ACF5PRO as well
			row.find(_.toArray(field_types).join(',')).filter('.qtranxs-translatable').each(function() {
				qTranslateConfig.qtx.removeContentHook(this);
			});
			// call the original handler
			repeaterFieldRemove.call(this, $el);
		};
	});

})();
