/*
Loaded for a page requested by the user on configuration
*/
new qTranslateX({
	addContentHooks: function(qtx)
	{
		var forms=document.getElementsByTagName('FORM');
		if(!forms.length) return false;
		var form=forms[0];
		qtx.addContentHooks(form);
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
