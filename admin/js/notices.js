(function ($) {
    $(function () {
        $('.notice-dismiss, .qtranxs-notice-dismiss').each(
            function () {
                const q = $(this);
                const d = q.closest('div.is-dismissible');
                if (!d.length)
                    return;
                if (!q.hasClass('qtranxs-notice-dismiss')) {
                    const f = d.find('.qtranxs-notice-dismiss');
                    if (!f.length)
                        return;
                }
                let id = d.attr('id');
                if (!id)
                    return;
                id = id.replace('qtranxs-', '');
                const action = d.attr('action');
                q.on('click',
                    function () {
                        d.css('display', 'none');
                        $.post(ajaxurl, {action: 'qtranslate_admin_notice', notice_id: id, notice_action: action});
                    }
                );
            }
        );
    });
}(jQuery));
