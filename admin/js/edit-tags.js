/* loaded in 
	/wp-admin/edit-tag.php
*/
qTranslateConfig.js={
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

		qtx.addDisplayHookById('parent');

		var theList=document.getElementById('the-list');

		hideQuickEditRow=function(tr)
		{
			var tds=tr.getElementsByTagName('TD');
			if(!tds.length) return;
			var td=tds[0];
			var items=td.getElementsByClassName('inline');
			for(var i=0; i<items.length; ++i)
			{
				var e=items[i];
				e.style.display='none';
			}
		}

		addDisplayHookRows=function(theList)
		{
			addDisplayHookRows.running=true;
			if(!theList) return;
			var rows=theList.getElementsByTagName('TR');
			for(var r=0; r<rows.length; r++)
			{
				var tr=rows[r];
				//qtx.addDisplayHooksByClass('check-column',tr);
				qtx.addDisplayHooksByClass('row-title',tr);
				qtx.addDisplayHooksByClass('description',tr);
				qtx.addDisplayHooksByClass('slug',tr);
				hideQuickEditRow(tr);
			}
			addDisplayHookRows.running=false;
		}
		addDisplayHookRows(theList);

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

		var submit_button = document.getElementById('submit');
		if(submit_button){
			submit_button.addEventListener("click",function(){
					setTimeout(function(){window.location.reload();},800);
					//addDisplayHookRows(theList);//does not work, because the updates on theList has not yet propagated
				});
		}

		return true;
	}
};
