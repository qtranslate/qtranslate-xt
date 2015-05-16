/* loaded in 
	/wp-admin/nav-menus.php
*/
qTranslateConfig.js={
	addContentHooks: function(qtx)
	{
		var form=document.getElementById('update-nav-menu');
		if(!form) return false;

		addMenuItemHooks=function(li)
		{
			qtx.addContentHooksByClass('edit-menu-item-title',li);
			qtx.addContentHooksByClass('edit-menu-item-attr-title',li);
			qtx.addContentHooksByClass('[edit-menu-item-description',li);//must use '[:]' separator style

			qtx.addDisplayHooksByClass('menu-item-title',li);
			qtx.addDisplayHooksByClass('item-title',li);
			qtx.addDisplayHooksByTagInClass('link-to-original','A',li);
		}

		function addMenuPageHooks(qtx)
		{
			var items=document.getElementsByClassName('menu-item');
			for(var i=0; i<items.length; ++i)
			{
				var li=items[i];
				addMenuItemHooks(li);
			}
			var sst = document.getElementById('side-sortables');
			if(sst) qtx.addDisplayHooksByClass('menu-item-title',sst);
		}
		addMenuPageHooks(qtx);

		if(wpNavMenu){
			var wp_addMenuItemToBottom = wpNavMenu.addMenuItemToBottom;
			if( typeof wp_addMenuItemToBottom == 'function'){
				wpNavMenu.addMenuItemToBottom = function( menuMarkup, req ) {
					wp_addMenuItemToBottom( menuMarkup, req );
					var rx = /id="menu-item-(\d+)"/gi;
					while((matches = rx.exec(menuMarkup))){
						var id = 'menu-item-'+matches[1];
						var li = document.getElementById(id);
						if(li)
							addMenuItemHooks(li);
					}
				};
			}
		}

		//this.onTabSwitch(qtx.getActiveLanguage());
		this.onTabSwitch();
		this.langSwitchWrapAnchor=form;//causes buttons to be inserted in front of this form instead of the first form in div "wrap"

		return true;
	}
,
	onTabSwitch: function()
	{
		if(wpNavMenu){
			if( typeof wpNavMenu.refreshKeyboardAccessibility == 'function'){
				wpNavMenu.refreshKeyboardAccessibility();
			}
			if( typeof wpNavMenu.refreshAdvancedAccessibility == 'function'){
				wpNavMenu.refreshAdvancedAccessibility();
			}
		}
	}
};
