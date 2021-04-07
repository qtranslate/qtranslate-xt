const $ = jQuery;

$(function () {
    $('.notice-dismiss, .qtranxs-notice-dismiss').each(
        function () {
            const $notice = $(this);
            const $div = $notice.closest('div.is-dismissible');
            if (!$div.length)
                return;
            if (!$notice.hasClass('qtranxs-notice-dismiss')) {
                const $dismiss = $div.find('.qtranxs-notice-dismiss');
                if (!$dismiss.length)
                    return;
            }
            let id = $div.attr('id');
            if (!id)
                return;
            id = id.replace('qtranxs-', '');
            const action = $div.attr('action');
            $notice.on('click',
                function () {
                    $div.css('display', 'none');
                    $.post(ajaxurl, {action: 'qtranslate_admin_notice', notice_id: id, notice_action: action});
                }
            );
        }
    );
});
