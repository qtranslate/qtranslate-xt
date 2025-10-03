/**
 * Extended fields for qTranslate.
 */
'use strict';
const $ = jQuery;

const registerExtendedFieldFile = function (fieldType) {
    acf.registerFieldType(acf.models.FileField.extend({
        type: fieldType,
        $control: function () {
            return this.$('.acf-file-uploader.current-language');
        },
        $input: function () {
            return this.$('.acf-file-uploader.current-language input[type="hidden"]');
        },
        render: function (attachment) {
            attachment = this.validateAttachment(attachment);
            // update image
            this.$control().find('img').attr({
                src: attachment.icon,
                alt: attachment.alt,
                title: attachment.title
            });
            // update elements
            this.$control().find('[data-name="title"]').text(attachment.title);
            this.$control().find('[data-name="filename"]').text(attachment.filename).attr('href', attachment.url);
            this.$control().find('[data-name="filesize"]').text(attachment.filesizeHumanReadable);

            const val = attachment.id || '';
            acf.val(this.$input(), val);
            if (val) {
                this.$control().addClass('has-value');
            } else {
                this.$control().removeClass('has-value');
            }
        }
    }));
};

const registerExtendedFieldImage = function (fieldType) {
    acf.registerFieldType(acf.models.ImageField.extend({
        type: fieldType,
        $control: function () {
            return this.$('.acf-image-uploader.current-language');
        },
        $input: function () {
            return this.$('.acf-image-uploader.current-language input[type="hidden"]');
        },
        render: function (attachment) {
            attachment = this.validateAttachment(attachment);
            // update image
            this.$control().find('img').attr({
                src: attachment.url,
                alt: attachment.alt,
                title: attachment.title
            });

            const val = attachment.id || '';
            this.val(val);
            if (val) {
                this.$control().addClass('has-value');
            } else {
                this.$control().removeClass('has-value');
            }
        }
    }));
};

const registerExtendedFieldPostObject = function (fieldType) {
    acf.registerFieldType(acf.models.PostObjectField.extend({
        type: fieldType,
        $control: function () {
            return this.$('.acf-post-object.current-language');
        },
        $input: function () {
            return this.$('.acf-post-object.current-language select');
        },
        initialize: function () {
            const self = this;
            const $select = this.$input();
            this.inherit($select);
            // select2
            if (this.get('ui')) {
                // populate ajax_data (allowing custom attribute to already exist)
                let ajaxAction = this.get('ajax_action');
                if (!ajaxAction) {
                    ajaxAction = 'acf/fields/' + this.get('type') + '/query';
                }
                this.select2 = [];
                this.$('.acf-post-object select').each(function () {
                    const $newSelect2 = acf.newSelect2($(this), {
                        field: self,
                        ajax: self.get('ajax'),
                        multiple: self.get('multiple'),
                        placeholder: self.get('placeholder'),
                        allowNull: self.get('allow_null'),
                        ajaxAction: ajaxAction,
                    });
                    self.select2.push($newSelect2);
                });
                // Set the "translatable" class to the proper UI element.
                // It can't be done in PHP because the select element doesn't exist yet.
                this.$('.acf-post-object span.select2-selection').addClass(qTranx.config.styles.translatable);
            }
        },
        onRemove: function () {
            if (this.select2) {
                for (let i = 0; i < this.select2.length; i++) {
                    this.select2[i].destroy();
                }
            }
        }
    }));
};

const registerExtendedFieldUrl = function (fieldType) {
    acf.registerFieldType(acf.models.UrlField.extend({
        type: fieldType,
        $control: function () {
            return this.$('.acf-input-wrap.current-language');
        },
        $input: function () {
            return this.$('.acf-input-wrap.current-language input[type="url"]');
        }
    }));
};

const registerExtendedFieldWysiwyg = function (fieldType) {
    acf.registerFieldType(acf.models.WysiwygField.extend({
        type: fieldType,
        $control: function () {
            return this.$('.acf-editor-wrap.current-language');
        },
        $input: function () {
            return this.$('.acf-editor-wrap.current-language textarea');
        },
        initializeEditor: function () {
            const self = this;
            this.$('.acf-editor-wrap').each(function () {
                const $wrap = $(this);
                const $textarea = $wrap.find('textarea');
                const args = {
                    tinymce: true,
                    quicktags: true,
                    toolbar: self.get('toolbar'),
                    mode: self.getMode(),
                    field: self
                };
                const oldId = $textarea.attr('id');
                const newId = acf.uniqueId('acf-editor-');
                acf.rename({
                    target: $wrap,
                    search: oldId,
                    replace: newId,
                    destructive: true
                });
                self.set('id', newId, true);
                acf.tinymce.initialize(newId, args);
            });
        }
    }));

    // The 'qtranslate_wysiwyg' field contains an editor wrapper for each language, already translated in each of those.
    // No hooks should be set, but we update tinymce CSS to show it's translatable. For textarea html it's already set.
    acf.addFilter('wysiwyg_tinymce_settings', function (mceInit, id, field) {
        if (field.type === fieldType) {
            const initCB = mceInit.init_instance_callback;
            mceInit.init_instance_callback = function (editor) {
                if (initCB !== undefined) {
                    initCB();
                }
                editor.getContentAreaContainer().classList.add(qTranx.config.styles.translatable);
            };
        }
        return mceInit;
    });
};

/**
 * Register extended fields.
 */
export const registerExtendedFields = function (fieldTypes) {
    for (const fieldType in fieldTypes) {
        switch (fieldType) {
            case 'qtranslate_file':
                registerExtendedFieldFile(fieldType);
                break;
            case 'qtranslate_image':
                registerExtendedFieldImage(fieldType);
                break;
            case 'qtranslate_post_object':
                registerExtendedFieldPostObject(fieldType);
                break;
            case 'qtranslate_url':
                registerExtendedFieldUrl(fieldType);
                break;
            case 'qtranslate_wysiwyg':
                registerExtendedFieldWysiwyg(fieldType);
                break;
            case 'qtranslate_text':
            case 'qtranslate_textarea':
                // Not needed?
                break;
            default:
                console.warn('[qTranslate-XT] Unknown extended ACF field: ', fieldType);
                break;
        }
    }
};
