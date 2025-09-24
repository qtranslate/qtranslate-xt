const $ = jQuery;

const $body = $('body');

/**
 * Sync language switchers for legacy fields not handled natively by qTranslate-XT
 */
const onLanguageSwitch = function (language) {
    const parent = $('.multi-language-field');
    parent.find('.current-language').removeClass('current-language');
    parent.find('[data-language="' + language + '"]').addClass('current-language');
    parent.find('input[data-language="' + language + '"], textarea[data-language="' + language + '"]');
};
wp.hooks.addAction('qtranx.languageSwitch', 'qtranx/acf/switch', function (language) {
    onLanguageSwitch(language);
});

/**
 * Setup language switchers.
 */
$body.on('click', '.wp-switch-editor[data-language]', function () {
    const parent = $(this).parent('.multi-language-field'), language = $(this).data('language');
    parent.find('.current-language').removeClass('current-language');
    parent.find('[data-language="' + language + '"]').addClass('current-language');
    parent.find('input[data-language="' + language + '"], textarea[data-language="' + language + '"]').focus();
    // TODO shouldn't we use qtx.switchActiveLanguage instead?
    $('.qtranxs-lang-switch[lang="' + language + '"]:first').trigger('click');
    // Prevent default behavior switching Visual Editor
    return false;
});

/**
 * Focus/blur fields.
 */
$body.on('focusin', '.multi-language-field input, .multi-language-field textarea', function () {
    $(this).parent('.multi-language-field').addClass('focused');
});

$body.on('focusout', '.multi-language-field input, .multi-language-field textarea', function () {
    $(this).parent('.multi-language-field').removeClass('focused');
});

/**
 * Keep the selected editor in sync across languages.
 */
$body.on('click', '.wp-editor-tabs .wp-switch-editor', function () {
    const parent = $(this).parents('.multi-language-field'),
        editor = $(this).hasClass('switch-tmce') ? 'tmce' : 'html';
    parent.find('.wp-editor-tabs .wp-switch-editor.switch-' + editor).not(this).each(function () {
        const id = $(this).attr('data-wp-editor-id');
        if (id) {
            window.switchEditors.go(id, editor);
        }
    });
});

wp.hooks.addAction('qtranx.load', 'qtranx/acf/switch', function () {
    if (!qTranx.config.isEditorModeLSB())
        return;
    // select the edit tab from active language
    const language = qTranx.hooks.getActiveLanguage();
    if (language) {
        // show the correct ACF fields
        onLanguageSwitch(language);
        // sync the switch editors
        const $mlFields = $('.multi-language-field');
        $mlFields.find('.current-language').removeClass('current-language');
        $mlFields.find('[data-language="' + language + '"]').addClass('current-language');
    }
});
