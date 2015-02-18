/*
Loaded for a page requested by the user on configuration
*/
qTranslateConfig.js={
	addContentHooks: function(qtx)
	{
		var form = qtx.getWrapForm();
		if(!form) return false;
		qtx.addCustomContentHooks(form);
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
};
