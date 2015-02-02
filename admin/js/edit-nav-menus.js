/* loaded in 
	/wp-admin/nav-menus.php
*/
qTranslateConfig.js={
	addContentHooks: function(qtx)
	{
		var form=document.getElementById('update-nav-menu');
		if(!form) return false;

		addMenuItemHooks=function(li,form)
		{
			//co('addMenuItemHooks: qtx',qtx);
			//co('addMenuItemHooks: form',form);
			//co('addMenuItemHooks: li',li);
			qtx.addContentHooksByClass('edit-menu-item-title',form,li);
			qtx.addContentHooksByClass('edit-menu-item-attr-title',form,li);
			qtx.addContentHooksByClass('[edit-menu-item-description',form,li);//must use '[:]' separator style

			qtx.addDisplayHooksByClass('menu-item-title',li);
			qtx.addDisplayHooksByTagInClass('link-to-original','A',li);
		}

		function addPageHooks(qtx,form)
		{
			var items=document.getElementsByClassName('menu-item');
			for(var i=0; i<items.length; ++i)
			{
				var li=items[i];
				addMenuItemHooks(li,form);
			}
			var sst = document.getElementById('side-sortables');
			if(sst) qtx.addDisplayHooksByClass('menu-item-title',sst);
		}
		addPageHooks(qtx,form);

		if(wpNavMenu){
			var wp_addMenuItemToBottom = wpNavMenu.addMenuItemToBottom;
			if( typeof wp_addMenuItemToBottom == 'function'){
				wpNavMenu.addMenuItemToBottom = function( menuMarkup, req ) {
					//co('menuMarkup: before',menuMarkup);
					//get id of default description, which gets broken due to line "$menu_item->description = apply_filters( 'nav_menu_description', wp_trim_words( $menu_item->post_content, 200 ) );" in /wp-includes/nav-menu.php
					var matches;
					var rxd = /(<textarea id="edit-menu-item-description.*>)(.*)(<\/textarea>)/gi;
					while((matches = rxd.exec(menuMarkup))){
						//co('matches',matches);
						menuMarkup = menuMarkup.replace(matches[0],matches[1]+matches[3]);
					}
					wp_addMenuItemToBottom( menuMarkup, req );
					//co('menuMarkup: after',menuMarkup);
					//co('req:',req);
					//co('addMenuItemToBottom: form',form);
					var rx = /id="menu-item-(\d+)"/gi;
					while((matches = rx.exec(menuMarkup))){
						//co('matches('+matches.length+')',matches);
						var id = 'menu-item-'+matches[1];
						//co('addMenuItemToBottom: id',id);
						var li = document.getElementById(id);
						//co('addMenuItemToBottom: li['+id+']',li);
						if(li)
							addMenuItemHooks(li,form);
					}
				};
			}
		}

		this.onTabSwitch(qtx.getActiveLanguage(),qtx);
		this.langSwitchWrapAnchor=form;//causes buttons to be inserted in front of this form instead of the first form in div "wrap"

		return true;
	}
,
	onTabSwitch: function(lang,qtx)
	{
		//c('onTabSwitch: lang='+lang);
		//co('onTabSwitch: qtx=',qtx);
		//co('onTabSwitch: wpNavMenu',wpNavMenu);
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
