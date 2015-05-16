/* loaded in 
	/wp-admin/post.php
	/wp-admin/post-new.php
*/
qTranslateConfig.js={

	addContentHooks: function addContentHooks(qtx)
	{
		var form=document.getElementById('post');
		if(!form) return false;

		qtx.addContentHookByIdC('title');
		qtx.addContentHookByIdC('excerpt');

		var wpEditorAreas=form.getElementsByClassName('wp-editor-area');
		for(var i=0; i<wpEditorAreas.length; ++i){
			var wpEditorArea=wpEditorAreas[i];
			qtx.addContentHookC(wpEditorArea);
		}

		qtx.addContentHookByIdC('attachment_caption');
		qtx.addContentHookByIdB('attachment_alt');

		qtx.addCustomContentHooks();

		qtx.addDisplayHooksByClass('gallery_caption',form);

		// Slug
		function convertURL(url,lang)
		{
			switch (qTranslateConfig.url_mode.toString())
			{
			case '1':
				if (url.search){
					url.search+="&lang="+lang;
				}else{
					url.search="?lang="+lang;
				}
				break;
			case '2':
				var homepath=qTranslateConfig.url_info_home;
				var i=url.pathname.indexOf(homepath);
				url.pathname=homepath+lang+url.pathname.substring(i+homepath.length-1);
				break;
			case '3':
				url.host=lang+'.'+url.host;
				break;
			case '4':
				url.host=qTranslateConfig.domains[lang];
				break;
			}
		}

		var btnViewPostA;//a node of 'View Page/Post' link.
		var origUrl, langUrl;
		var slugSamplePermalink;//'sample-permalink' node
		var origSamplePermalink;
		this.setSlugLanguage=function(lang)
		{
			if(!btnViewPostA){
				var btnViewPost=document.getElementById('view-post-btn');
				if (!btnViewPost || !btnViewPost.children.length) return;
				btnViewPostA=btnViewPost.children[0];
				if(btnViewPostA.tagName != 'A') return;
				origUrl=btnViewPostA.href;
				langUrl=qtranxj_ce('a',{});
			}

			langUrl.href=origUrl;
			convertURL(langUrl,lang);
			btnViewPostA.href=langUrl.href;

			var btnPreviewAction=document.getElementById('preview-action');
			if (btnPreviewAction && btnPreviewAction.children.length)
			{
				btnPreviewAction.children[0].href=langUrl.href;
			}

			if(qTranslateConfig.url_mode!=1){//!QTX_URL_QUERY
				if(!slugSamplePermalink){
					var slugEl=document.getElementById('sample-permalink');
					if (slugEl && slugEl.childNodes.length){
						slugSamplePermalink=slugEl.childNodes[0];//span
						origSamplePermalink=slugSamplePermalink.nodeValue;
						//var slugEdit=document.getElementById('editable-post-name');
					}
				}
				if(slugSamplePermalink){
					langUrl.href=origSamplePermalink;
					convertURL(langUrl,lang);
					slugSamplePermalink.nodeValue=langUrl.href;
				}
			}
		}
		this.setSlugLanguage(qtx.getActiveLanguage());

		//qtx.addContentHooksTinyMCE();// always called in the framework

		/**
		 * @since 3.2.4 Multiple sets of Language Switching Buttons
		 */
		if( !qTranslateConfig.page_config ) qTranslateConfig.page_config={};
		if( !qTranslateConfig.page_config.anchors)
			qTranslateConfig.page_config.anchors = ['post','postexcerpt'];//,'slugdiv'

		qtx.addLanguageSwitchAfterListener(this.setSlugLanguage);

		return true;
	}
};
