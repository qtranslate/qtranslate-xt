(function ($) {
    acf.registerFieldType(acf.models.FileField.extend({
        type: 'qtranslate_file',

        $control: function () {
            return this.$('.acf-file-uploader.current-language');
        },

        $input: function () {
            return this.$('.acf-file-uploader.current-language input[type="hidden"]');
        },

        render: function (attachment) {

            // vars
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

            // vars
            var val = attachment.id || '';

            // update val
            acf.val(this.$input(), val);

            // update class
            if (val) {
                this.$control().addClass('has-value');
            } else {
                this.$control().removeClass('has-value');
            }
        }
    }));


    acf.registerFieldType(acf.models.ImageField.extend({
        type: 'qtranslate_image',

        $control: function () {
            return this.$('.acf-image-uploader.current-language');
        },

        $input: function () {
            return this.$('.acf-image-uploader.current-language input[type="hidden"]');
        },

        render: function (attachment) {

            // vars
            attachment = this.validateAttachment(attachment);

            // update image
            this.$control().find('img').attr({
                src: attachment.url,
                alt: attachment.alt,
                title: attachment.title
            });

            // vars
            var val = attachment.id || '';

            // update val
            this.val(val);

            // update class
            if (val) {
                this.$control().addClass('has-value');
            } else {
                this.$control().removeClass('has-value');
            }
        }
    }));


    acf.registerFieldType(acf.models.PostObjectField.extend({
        type: 'qtranslate_post_object',

        $input: function () {
            return this.$('.acf-post-object.current-language select');
        },

        initialize: function () {
            var self = this;

            // vars
            var $select = this.$input();

            // inherit data
            this.inherit($select);

            // select2
            if (this.get('ui')) {

                // populate ajax_data (allowing custom attribute to already exist)
                var ajaxAction = this.get('ajax_action');
                if (!ajaxAction) {
                    ajaxAction = 'acf/fields/' + this.get('type') + '/query';
                }

                // select2
                this.select2 = [];
                this.$('.acf-post-object select').each(function () {
                    self.select2.push(acf.newSelect2($(this), {
                        field: self,
                        ajax: self.get('ajax'),
                        multiple: self.get('multiple'),
                        placeholder: self.get('placeholder'),
                        allowNull: self.get('allow_null'),
                        ajaxAction: ajaxAction,
                    }));
                });
            }
        },

        onRemove: function () {
            if (this.select2) {
                for (var i = 0; i < this.select2.length; i++) {
                    this.select2[i].destroy();
                }
            }
        }
    }));


    acf.registerFieldType(acf.models.UrlField.extend({
        type: 'qtranslate_url',

        $control: function () {
            return this.$('.acf-input-wrap.current-language');
        },

        $input: function () {
            return this.$('.acf-input-wrap.current-language input[type="url"]');
        }
    }));


    acf.registerFieldType(acf.models.WysiwygField.extend({
        type: 'qtranslate_wysiwyg',

        initializeEditor: function () {
            var self = this;
            this.$('.acf-editor-wrap').each(function () {
                var $wrap = $(this);
                var $textarea = $wrap.find('textarea');
                var args = {
                    tinymce: true,
                    quicktags: true,
                    toolbar: self.get('toolbar'),
                    mode: self.getMode(),
                    field: self
                };

                // generate new id
                var oldId = $textarea.attr('id');
                var newId = acf.uniqueId('acf-editor-');

                // rename
                acf.rename({
                    target: $wrap,
                    search: oldId,
                    replace: newId,
                    destructive: true
                });

                // update id
                self.set('id', newId, true);

                // initialize
                acf.tinymce.initialize(newId, args);
            });
        }
    }));
})(jQuery);
