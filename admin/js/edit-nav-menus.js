/* loaded in 
	/wp-admin/nav-menus.php
*/
new qTranslateX({
	addContentHooks: function(qtx)
	{
		//return false;
		var form=document.getElementById('update-nav-menu');
		if(!form) return false;

		qtx.addContentHooksByClass('edit-menu-item-title',form);
		qtx.addContentHooksByClass('edit-menu-item-attr-title',form);
		qtx.addContentHooksByClass('[edit-menu-item-description',form);//must use '[:]' separator style

		qtx.addDisplayHooksByClass('menu-item-title',form);
		qtx.addDisplayHooksByClass('link-to-original',form);

		//qtx.addContentHooks(form);
		this.langSwitchWrapAnchor=form;//causes buttons to be inserted in front of this form instead of the first form in div "wrap"
		return true;
	}
/*
,
	onTabSwitch: function(lang,qtx)
	{
		//implement additional actions on tab click, if applicable.
	}
*/
});
