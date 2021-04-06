const $ = jQuery;

$(window).on('load', function () {
    const qtx = qTranslateConfig.js.get_qtx();

    qtx.enableLanguageSwitchingButtons('block');

    // Ensure that translation of standard field types is enabled
    if (!window.acf_qtranslate_translate_standard_field_types) {
        return;
    }

    // Selectors for supported field types
    const field_types = {
        text: 'input:text',
        textarea: 'textarea',
        wysiwyg: '.wp-editor-area',
    };

    // Remove content hooks from ACF Fields
    $('.acf-postbox .acf-field').find('.qtranxs-translatable').each(function () {
        qtx.removeContentHook(this);
    });

    const post_type = $('#post_type').val();

    // Whitelist fields for translation
    function isTranslatableField(field) {
        if (post_type === 'acf-field-group') {
            if (field.id.match(/acf_fields-\d+-label/)) return true;
            if (field.id.match(/acf_fields-\d+-instructions/)) return true;
            if (field.id.match(/acf_fields-\d+-default_value/)) return true;
            return false;
        }
        return true;
    }

    // Setup field types
    $.each(field_types, function (field_type, selector) {

        // Add content hooks for existing fields
        acf.get_fields({type: field_type}).each(function () {
            const form = $(this).closest('form').get(0);
            const field = $(this).find(selector).get(0);
            if (!isTranslatableField(field)) return;
            qtx.addContentHookC(field, form);
        });

        // Watch and add content hooks when new fields are added
        acf.add_action('append_field/type=' + field_type, function ($el) {
            const form = $el.closest('form').get(0);
            const field = $el.find(selector).get(0);
            if (!isTranslatableField(field)) return;
            qtx.addContentHookC(field, form);

            if ($(field).hasClass('wp-editor-area')) {
                // qtx.addContentHooksTinyMCE();

                // We must manually trigger load event so that the
                // loadTinyMceHooks function which calls setEditorHooks is executed
                const loadEvent = document.createEvent('UIEvents');
                loadEvent.initEvent('load', false, false, window);
                window.dispatchEvent(loadEvent);
            }

            // Run at higher integer priority than the default in case the ACF handlers
            // change the id of the underlying input
        }, 100);

    });

    // qtx.addContentHooksTinyMCE();

    // Watch and remove content hooks when fields are removed
    // however ACF removes the elements from the DOM early so
    // we must hook into handler and perform updates there
    const repeaterFieldRemove = acf.models ?
        repeaterFieldRemove = acf.models.RepeaterField.prototype.remove :
        repeaterFieldRemove = acf.fields.repeater.remove;

    function repeaterRemove($el) {
        const row = ($el.$el || $el).closest('.acf-row'); // support old versions of ACF5PRO as well
        row.find(_.toArray(field_types).join(',')).filter('.qtranxs-translatable').each(function () {
            qtx.removeContentHook(this);
        });
        // call the original handler
        repeaterFieldRemove.call(this, $el);
    }
});
