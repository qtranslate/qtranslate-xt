/* loaded in 
	/wp-admin/edit-tag.php?action=edit
*/
qTranslateConfig.js={
	addContentHooks: function(qtx)
	{
		var prefix, form = document.getElementById('edittag');
		if(!form) return false;

		var h=qtx.addContentHookByIdB('name',form);
		if(!h) return false;

		qtranxj_ce('input', {name: 'qtrans_term_field_name', type: 'hidden', className: 'hidden', value: h.mlContentField.name }, form, true);

		var default_name=h.contents[qTranslateConfig.default_language];
		qtranxj_ce('input', {name: 'qtrans_term_field_default_name', type: 'hidden', className: 'hidden', value: default_name }, form, true);

		qtx.addContentHookByIdC('description',form);

		qtx.addDisplayHookById('parent');

		return true;
	}
};
