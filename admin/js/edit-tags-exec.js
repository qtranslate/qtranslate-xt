/* executed for 
 /wp-admin/edit-tags.php (without action=edit)
*/

jQuery(function ($) {
	var qtx = qTranslateConfig.js.get_qtx();

	var addDisplayHook = function (i, o) {
		qtx.addDisplayHook(o);
	};

	var updateRow = function (r) {
		var j = $(r);
		j.find('.row-title, .description').each(addDisplayHook);
		j.find('td.name span.inline').css('display', 'none');
	};

	var the_list = $('#the-list');
	//co('the_list.children: ', the_list.children());
	var rcnt = $('#the-list > tr').length;

	var onRowAdd = function () {
		var trs = the_list.children();
		if (rcnt == trs.length)
			return false;
		var ok = rcnt > trs.length;
		rcnt = trs.length;
		if (ok)
			return false;
		for (var i = 0; i < trs.length; ++i) {
			var r = trs[i];
			updateRow(r);
		}
		return false;
	};

	the_list.each(function (i, o) {
		$(o).bind("DOMSubtreeModified", onRowAdd);
	});

	//remove "Quick Edit" links for now
	$('#the-list > tr > td.name span.inline').css('display', 'none');
})(jQuery);
