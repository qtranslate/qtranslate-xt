/* loaded in 
	/wp-admin/edit-tag.php
*/
new qTranslateX({
	addContentHooks: function(qtx)
	{
		var form = document.getElementById('addtag');//AjaxForm
		if(!form) return false;

		var h=qtx.addContentHookByIdB('tag-name',form);
		if(!h) return false;

		qtx.addContentHookByIdC('tag-description',form);

		qtranxj_ce('input', {name: 'qtrans_term_field_name', type: 'hidden', className: 'hidden', value: h.mlContentField.name }, form, true);

		var default_name=h.contents[qTranslateConfig.default_language];
		qtranxj_ce('input', {name: 'qtrans_term_field_default_name', type: 'hidden', className: 'hidden', value: default_name }, form, true);

		var theList=document.getElementById('the-list');

		hideQuickEdit=function(theList)
		{
			if(!theList) return;
			var rows=theList.getElementsByTagName('TR');
			for(var r=0; r<rows.length; r++)
			{
				var tr=rows[r];
				var td=tr.getElementsByTagName('TD')[0];
				var items=td.getElementsByClassName('inline');
				for(var i=0; i<items.length; ++i)
				{
					var e=items[i];
					e.style.display='none';
				}
			}
		}
		hideQuickEdit(theList);

		addDisplayHookTitle=function(theList)
		{
			if(!theList) return;
			var rows=theList.getElementsByTagName('TR');
			for(var r=0; r<rows.length; r++)
			{
				var tr=rows[r];
				qtx.addDisplayHooksByClass('row-title',tr);
				qtx.addDisplayHooksByClass('description',tr);
				//var td=tr.getElementsByTagName('TD')[0];
				//qtx.addDisplayHooksByClass('row-title',td);
			}
		}
		addDisplayHookTitle(theList);

		addDisplayHookTagCloud=function()
		{
			var tagClouds=document.getElementsByClassName('tagcloud');
			for(var i=0; i<tagClouds.length; ++i) {
				var tagCloud = tagClouds[i];
				var items=tagCloud.getElementsByTagName('A');
				for(var i=0; i<items.length; ++i) {
					var e=items[i];
					addDisplayHook(e);
				}
			}
		}
		addDisplayHookTagCloud();

		return true;
	}
});
