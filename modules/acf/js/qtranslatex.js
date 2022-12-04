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
                    qtx.addContentHookB(element);
                }
            });
        });

        return;
    }

    // Add hooks for translatable standard fields, defined as field type -> selector.
    const fieldTypes = {
        text: 'input:text',
        textarea: 'textarea', // only regular textarea, not wysiwyg editors (.wp-editor-area).
    };
    $.each(fieldTypes, function (fieldType, selector) {
        acf.findFields({type: fieldType}).each(function () {
            // The hooks must be set on the child elements found by the selector, assuming a single one by field.
            $(this).find(selector).each(function () {
                if (!qtx.hasContentHook(this)) {
                    qtx.addContentHookB(this);
                }
            });
        });
    });

    // The wysiwyg editor must be handled later than the usual sequence, because ACF are destroying some HTML fields:
    // See https://github.com/AdvancedCustomFields/acf/issues/767
    // If the usual content hooks are created before, the references point to HTML objects becoming detached from the doc.
    acf.addFilter('wysiwyg_tinymce_settings', function (mceInit, id, field) {
        if (field.type === 'wysiwyg') {
            // In this filter the elements with new ID have been created, so we can finally create the content hooks.
            const newFieldTextArea = field.$input()[0];
            qtx.addContentHookB(newFieldTextArea);
            // Link the init CB for the visual mode (HTML -> tinymce).
            // Note: wysiwyg_tinymce_init event is not triggered if the Visual Mode is selected later.
            const initCB = mceInit.init_instance_callback;
            mceInit.init_instance_callback = function (editor) {
                if (initCB !== undefined) {
                    initCB();
                }
                qtx.attachEditorHook(editor);
            };
        }
        return mceInit;
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
