const $ = jQuery;

const $body = $('body');

/**
 * Sync language switchers for legacy fields not handled natively by qTranslate-XT
 */
export const syncLanguageSwitch = function (language) {
    const parent = $('.multi-language-field');
    parent.find('.current-language').removeClass('current-language');
    parent.find('[data-language="' + language + '"]').addClass('current-language');
};

wp.hooks.addAction('qtranx.languageSwitch', 'qtranx/acf/switch', function (language) {
    syncLanguageSwitch(language);
});

/**
 * Setup language switchers.
 */
$body.on('click', '.wp-switch-editor[data-language]', function () {
    const language = $(this).data('language');
    if (qTranx.hooks.switchActiveLanguage(language)) {
        const parent = $(this).parent('.multi-language-field');
        parent.find('input[data-language="' + language + '"], textarea[data-language="' + language + '"]').trigger("focus");
    }
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
