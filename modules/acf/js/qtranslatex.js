const $ = jQuery;

$(window).on('load', function () {
    const qtx = qTranslateConfig.js.get_qtx();

    qtx.enableLanguageSwitchingButtons('block');

    // Ensure that translation of standard field types is enabled
    if (!window.acf_qtranslate_translate_standard_field_types) {
        return;
    }

    const postType = $('#post_type').val();
    if (postType === 'acf-field-group') {
        const isTranslatableSetting = function (element) {
            // Numerical id for existing field, 'field_<alphanum>' for new field being added.
            return element.id.match(/acf_fields-(\d+|field_[a-z0-9]+)-(label|instructions|default_value)/);
        }
        // Click on "Edit" or "Add" opens the settings for that field.
        acf.addAction('open_field_object', function (settingField) {
            // When a field is edited or created, it contains many "settingFields" to set label, name, ...
            // They are given as .acf-field but the hooks must be set on the child elements like input and texts.
            settingField.$el.find('input:text, textarea').each(function () {
                const element = this;
                if (!qtx.hasContentHook(element) && isTranslatableSetting(element)) {
                    qtx.addContentHookC(element);
                }
            });
        });

        return;
    }

    // Add hooks for translatable standard fields, defined as field type -> selector.
    const fieldTypes = {
        text: 'input:text',
        textarea: 'textarea',
        wysiwyg: '.wp-editor-area',  // TODO: fix wysiwyg #1186
    };
    $.each(fieldTypes, function (fieldType, selector) {
        acf.findFields({type: fieldType}).each(function () {
            // The hooks must be set on the child elements found by the selector, assuming a single one by field.
            $(this).find(selector).each(function () {
                if (!qtx.hasContentHook(this)) {
                    qtx.addContentHookC(this);
                }
            });
        });
    });

    // Add display hooks for translatable settings.
    const displaySelector = '.acf-label > label, .acf-label > p.description, .acf-input > p.description';
    acf.findFields().each(function () {
        $(this).find(displaySelector).each(function () {
            if (!qtx.hasContentHook(this)) {
                qtx.addDisplayHook(this);
            }
        });
    });

    // Watch and remove content hooks when fields are removed
    // however ACF removes the elements from the DOM early so
    // we must hook into handler and perform updates there
    // TODO: fix RepeaterField #882
    // const repeaterFieldRemove = acf.models ?
    //     acf.models.RepeaterField.prototype.remove :
    //     acf.fields.repeater.remove;

    // TODO: who is supposed to call repeaterRemove and when?!
    // function repeaterRemove($el) {
    //     const row = ($el.$el || $el).closest('.acf-row'); // support old versions of ACF5PRO as well
    //     row.find(_.toArray(field_types).join(',')).filter('.qtranxs-translatable').each(function () {
    //         qtx.removeContentHook(this);
    //     });
    //     // call the original handler
    //     repeaterFieldRemove.call(this, $el);
    // }
});
