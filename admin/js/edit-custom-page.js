/*
Loaded for a page requested by the user on configuration
*/
new qTranslateX({
	addContentHooks: function(qtx)
	{
		var form = qtx.getWrapForm();
		if(!form) return false;
		qtx.addContentHooks(form);
		this.langSwitchWrapAnchor=form;
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
