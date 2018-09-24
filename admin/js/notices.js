(function ($) {
	$('.notice-dismiss, .qtranxs-notice-dismiss').each(
		function () {
			var q = $(this);
			var d = q.closest('div.is-dismissible');
			if (!d.length)
				return;
			if (!q.hasClass('qtranxs-notice-dismiss')) {
				var f = d.find('.qtranxs-notice-dismiss');
				if (!f.length)
					return;
			}
			var id = d.attr('id');
			if (!id)
				return;
			id = id.replace('qtranxs-', '');
			var action = d.attr('action');
			q.on('click',
				function () {
					d.css('display', 'none');
					$.post(ajaxurl, {action: 'qtranslate_admin_notice', notice_id: id, notice_action: action});
				}
			);
		}
	);
})(jQuery);
