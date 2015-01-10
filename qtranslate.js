/* this is the developer version of qtranslate.min.js before it is minimized */
/*
//debuging tools, do not check in
var cc=0;
function c(v){ ++cc; console.log('== '+cc+': '+v); }
function ct(v){ c(v); console.trace(); }
*/

qtranxj_split = function(text)
{
	var result = new Object;
	for(var i=0; i<qTranslateConfig.enabled_languages.length; ++i)
	{
		var lang=qTranslateConfig.enabled_languages[i];
		result[lang] = '';
	}
	var split_regex_c = /<!--:-->/gi;
	var blocks = text.xsplit(split_regex_c);
	//c('qtranxj_split: blocks='+blocks);
	//c('qtranxj_split: blocks.length='+blocks.length);
	if(!qtranxj_isArray(blocks))
		return result;
	var matches, lang_regex, lang;
	if(blocks.length>1){//there are matches
		lang_regex = /<!--:([a-z]{2})-->/gi;
		for(var i = 0;i<blocks.length;++i){
			var b=blocks[i];
			//c('blocks['+i+']='+b);
			if(!b.length) continue;
			matches = lang_regex.exec(b); lang_regex.lastIndex=0;
			//c('matches='+matches);
			if(matches==null) continue;
			lang = matches[1];
			result[lang] += b.substring(10);
			//c('text='+result[lang]);
		}
	}else{
		var split_regex_b = /(\[:[a-z]{2}\])/gi;
		blocks = text.xsplit(split_regex_b);
		if(!qtranxj_isArray(blocks))
			return result;
		if(blocks.length==1){//no language separator found, enter it to all languages
			var b=blocks[0];
			for(var j=0; j<qTranslateConfig.enabled_languages.length; ++j){
				var lang=qTranslateConfig.enabled_languages[j];
				result[lang] += b;
			}
		}else{
			lang_regex = /\[:([a-z]{2})\]/gi;
			lang = false;
			for(var i = 0;i<blocks.length;++i){
				var b=blocks[i];
				//c('blocks['+i+']='+b+'; lang='+lang);
				if(lang){
					result[lang] += b;
					lang = false;
				}else{
					matches = lang_regex.exec(b); lang_regex.lastIndex=0;
					if(matches==null) continue;
					lang = matches[1];
				}
			}
		}
	}
	return result;
}

/*
qtranxj_split = function(text)
{
	var result = new Object;
	for(var i=0; i<qTranslateConfig.enabled_languages.length; ++i)
	{
		var lang=qTranslateConfig.enabled_languages[i];
		result[lang] = '';
	}
	var split_regex_c = /(<!--.*?-->)/gi;
	var blocks = text.xsplit(split_regex_c);
	//c('qtranxj_split: blocks='+blocks);
	if(!qtranxj_isArray(blocks))
		return result;
	var lang_begin_regex, lang_end_regex;
	if(blocks.length>1){//there are matches, if the first block is empty
		lang_begin_regex = /<!--:([a-z]{2})-->/gi;
		lang_end_regex = /<!--:-->/gi;
	}else{
		var split_regex_b = /(\[:[a-z]{2}\])/gi;
		blocks = text.xsplit(split_regex_b);
		if(!qtranxj_isArray(blocks))
			return result;
		lang_begin_regex = /\[:([a-z]{2})\]/gi;
	}
	if(!blocks.length) return result;
	if(blocks.length==1){//no language separator found, enter it to all languages
			var b=blocks[0];
			for(var j=0; j<qTranslateConfig.enabled_languages.length; j++){
				var lang=qTranslateConfig.enabled_languages[j];
				result[lang] += b;
			}
	}else{
		var matches;
		var lang = false;
		for(var i = 0;i<blocks.length;++i){
			var b=blocks[i];
			//c('blocks['+i+']='+b);
			if(!b.length) continue;
			if ((matches = lang_begin_regex.exec(b)) != null){
				lang = matches[1];
			}else if(lang_end_regex && lang_end_regex.test(b)){
				lang = false;
			}else if(lang){
				result[lang] += b;
				lang = false;
			}//else skip garbage which tinyMCE adds sometimes
		}
	}
	var morenextpage_regex = /(<!--more-->|<!--nextpage-->)+$/gi;
	for(var i = 0;i<result.length;++i){
		result[i] = result[i].replace(morenextpage_regex,'');
	}
	return result;
}
*/
//"_c" stands for "comment"
qtranxj_join_c = function(texts)
{
	var text = '';
	for(var i=0; i<qTranslateConfig.enabled_languages.length; ++i)
	{
		var lang=qTranslateConfig.enabled_languages[i];
		var t = texts[lang];
		if ( !t || t=='' ) continue;
		text += '<!--:'+lang+'-->';
		text += t;
		text += '<!--:-->';
	}
	return text;
}

//"b" stands for "bracket"
qtranxj_join_b = function(texts)
{
	var text = '';
	for(var i=0; i<qTranslateConfig.enabled_languages.length; ++i)
	{
		var lang=qTranslateConfig.enabled_languages[i];
		var t = texts[lang];
		if ( !t || t=='' ) continue;
		text += '[:'+lang+']';
		text += t;
	}
	return text;
}

function qtranxj_get_cookie(cname)
{
	var nm = cname + "=";
	var ca = document.cookie.split(';');
	//c('ca='+ca);
	for(var i=0; i<ca.length; ++i){
		var s = ca[i];
		var sa = s.split('=');
		if(sa[0].trim()!=cname) continue;
		if(ca.length<2) continue;
		return sa[1].trim();
	}
	return '';
}

String.prototype.xsplit = function(_regEx){
	// Most browsers can do this properly, so let them work, they'll do it faster
	if ('a~b'.split(/(~)/).length === 3){ return this.split(_regEx); }

	if (!_regEx.global)
	{ _regEx = new RegExp(_regEx.source, 'g' + (_regEx.ignoreCase ? 'i' : '')); }

	// IE (and any other browser that can't capture the delimiter)
	// will, unfortunately, have to be slowed down
	var start = 0, arr=[];
	var result;
	while((result = _regEx.exec(this)) != null){
		arr.push(this.slice(start, result.index));
		if(result.length > 1) arr.push(result[1]);
		start = _regEx.lastIndex;
	}
	if(start < this.length) arr.push(this.slice(start));
	if(start == this.length) arr.push(''); //delim at the end
	return arr;
};

function qtranxj_isArray(obj){ return obj.constructor.toString().indexOf('Array') >= 0; }

function qtranxj_ce(tagName, props, pNode, isFirst)
{
	var el= document.createElement(tagName);
	if (props)
	{
		for(prop in props)
		{
			//try
			{
				el[prop]=props[prop];
			}
			//catch(err)
			{
				//Handle errors here
			}
		}
	}
	if (pNode)
	{
		if (isFirst && pNode.firstChild)
		{
			pNode.insertBefore(el, pNode.firstChild);
		}
		else
		{
			pNode.appendChild(el);
		}
	}
	return el;
}

var qTranslateX=function()
{
	function ge(id){ return document.getElementById(id); }

	var activeLanguage=qtranxj_get_cookie('wp_qtrans_edit_language');
	if(!activeLanguage)
		activeLanguage=qTranslateConfig.language;

	var contentHooks={};
	function updateFusedValueH(id,value)
	{
		var h=contentHooks[id];
		var lang=languageSwitch.getActiveLanguage();
		//var morenextpage_regex = /(<!--more-->|<!--nextpage-->)+$/gi;
		//h.contents[lang]=value.replace(morenextpage_regex,'');
		h.contents[lang]=value;
		if(h.separator==='<'){
			h.mlContentField.value = qtranxj_join_c(h.contents);
		}else{
			h.mlContentField.value = qtranxj_join_b(h.contents);
		}
		//updateFusedValueC(h.mlContentField, value, h.contents, languageSwitch.getActiveLanguage());
	}
	function addContentHook(inpField,form,separator)
	{
		if(!inpField) return;
		var h=contentHooks[inpField.id]={};
		h.contentField=inpField;
		h.contents=qtranxj_split(inpField.value);
		h.mlContentField=qtranxj_ce(inpField.tagName, {name: inpField.name, className: 'hidden', value: inpField.value}, form, true);
		if(!separator){
			if(inpField.tagName==='TEXTAREA')
				separator='<';
			else
				separator='[';
		}
		h.separator=separator;
		inpField.name+='_edit';
		inpField.value=h.contents[activeLanguage];
		//c('addContentHookC:inpField.value='+inpField.value);
		inpField.onblur=function(){ updateFusedValueH(this.id,this.value); }
	}
	function addContentHookC(inpField,form)
	{
		addContentHook(inpField,form,'<');
	}
	function addContentHookB(inpField,form)
	{
		addContentHook(inpField,form,'[');
	}
	function tagEdit()
	{
		// Get fields
		var isAjaxForm=!!ge('tag-name');
		var prefix, formId;
		if (isAjaxForm)
		{
			prefix='tag-';
			formId='addtag';
		}
		else
		{
			prefix='';
			formId='edittag';
		}
		var nameField=ge(prefix+'name');
		var form=ge(formId);
		if(!form || !nameField){
			//alert('qTranslate-X cannot hook into the tag editor.\nPlease, report this incident to the developers.');
			return false;
		}

		var adminLanguage=qTranslateConfig.language;

		build_translator=function(langF,langT)
		{
				var translator={};
				for(var key in qTranslateConfig.term_name){
					var nms = qTranslateConfig.term_name[key];
					var nmF=nms[langF];
					var nmT=nms[langT];
					if(!nmF || !nmT){
							var nmD=nms[qTranslateConfig.default_language];
							if(!nmD) continue;
							if(!nmF) nmF=nmD+'('+qTranslateConfig.default_language+')';
							if(!nmT) nmT=nmD+'('+qTranslateConfig.default_language+')';
					}
					translator[nmF]=nmT;
				}
				return translator;
		}

		var theList=ge('the-list');
		hideQuickEdit=function()
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
		hideQuickEdit();

		//var nameFields={};
		updateNames=function(langF,langT)
		{
			if(!theList) return;
			var rows=theList.getElementsByTagName('TR');
			for(var r=0; r<rows.length; r++)
			{
				var dnm, tr=rows[r];
				var td=tr.getElementsByTagName('TD')[0];
				var divs=td.getElementsByTagName('DIV');
				for(var d=0; d<divs.length; d++)
				{
					var e=divs[d];
					if(e.className!=='name')
							continue;
					dnm=e.innerHTML;
					break;
				}
				if(adminLanguage!=qTranslateConfig.default_language){
					var translator=build_translator(adminLanguage,qTranslateConfig.default_language);
					dnm=translator[dnm];
				}
				var nms=qTranslateConfig.term_name[dnm]||{};
				var nmF = nms[langF]||'';
				var nmT = nms[langT]||'';
				var items=td.getElementsByClassName('row-title');
				for(var i=0; i<items.length; ++i)
				{
					var e=items[i];
					if(nmF)
						e.innerHTML = e.innerHTML.replace(nmF,nmT);
					else
						e.innerHTML += nmT;
				}
			}
		}

		var tagCloud=document.getElementsByClassName('tagcloud')[0];
		updateTagCloud=function(langF,langT)
		{
			if(!tagCloud) return;
			var items=tagCloud.getElementsByTagName('A');
			if(!items.length) return;
			var translator=build_translator(langF,langT);
			for(var i=0; i<items.length; ++i)
			{
					var e=items[i];
					var nmF=e.innerHTML;
					var nmT=translator[nmF];
					if(!nmT) continue;
					e.innerHTML=nmT;
			}
		}

		updateNamesAndTagCloud=function(langF,langT)
		{
			updateNames(langF,langT);
			updateTagCloud(langF,langT);
		}
		if(adminLanguage!==activeLanguage)
			updateNamesAndTagCloud(adminLanguage,activeLanguage);

		// Swap fields
		var newNameField=qtranxj_ce('input', {name: nameField.name, className: 'hidden', value: nameField.value}, form, true);
		nameField.name='';

		// Load text
		var names = qTranslateConfig.term_name[nameField.value] || {};
		if (activeLanguage !== qTranslateConfig.default_language){
			nameField.value=names[activeLanguage] || '';
		}

		editinline_activated=function()
		{
			//c('editinline_activated:'+this.innerHTML);
			return true;
		}

		var editinlines=document.getElementsByClassName('editinline');
		//c('editinlines.length='+editinlines.length);
		for(var i=0; i<editinlines.length; ++i)
		{
			var e=editinlines[i];
			if(e.tagName!=='A') continue;
			e.addEventListener( 'click', editinline_activated);
		}

		onTabSwitch=function()
		{
			if(activeLanguage === this.lang) return;
			nameField.value=names[this.lang] || '';
			updateNamesAndTagCloud(activeLanguage,this.lang);
			activeLanguage = this.lang;
		}

		var langs=qTranslateConfig.enabled_languages;
		var newNameFields={};
		for(var i=0; i<langs.length; ++i)
		{
			var lang=langs[i];
			newNameFields[lang]=qtranxj_ce('input', {name: 'qtranx_term_'+lang, className: 'hidden', value: name[lang] || ''}, form, true);
		}
		// Add listeners for fields change
		nameField.onblur=function()
		{
			var lang=languageSwitch.getActiveLanguage();
			newNameFields[lang].value=this.value;
			names[lang]=this.value;
			if (lang === qTranslateConfig.default_language)
			{
					newNameField.value=this.value;
			}
		};
		return true;
	}

	function postEdit()
	{
		var form=ge('post');
		if(!form) return false;

		var titleField=ge('title');
		if(titleField) addContentHookC(titleField,form);

		var wpEditorAreas=form.getElementsByClassName('wp-editor-area');
		for(var i=0; i<wpEditorAreas.length; ++i){
			var wpEditorArea=wpEditorAreas[i];
			addContentHookC(wpEditorArea,form);
		}

		var captionField=ge('attachment_caption');
		if(captionField) addContentHookC(captionField,form);

		//c('qTranslateConfig.custom_fields.length='+qTranslateConfig.custom_fields.length);
		for(var i=0; i<qTranslateConfig.custom_fields.length; ++i){
			var id=qTranslateConfig.custom_fields[i];
			var sep;
			if(id.indexOf('<')==0 || id.indexOf('[')==0){
				sep=id.substring(0,1);
				id=id.substring(1);
			}
			var f=ge(id);
			addContentHook(f,form,sep);
		}

		for(var i=0; i<qTranslateConfig.custom_field_classes.length; ++i){
			var nm=qTranslateConfig.custom_field_classes[i];
			var sep;
			if(nm.indexOf('<')==0 || nm.indexOf('[')==0){
				sep=nm.substring(0,1);
				nm=nm.substring(1);
			}
			var fields=form.getElementsByClassName(nm);
			for(var j=0; j<fields.length; ++j){
				f=fields[j];
				addContentHook(f,form,sep);
			}
		}

		var alttextField=ge('attachment_alt');
		if(alttextField) addContentHookB(alttextField,form);

		//var post_name_field=ge('post_name');
		//if (post_name_field) addContentHookB(post_name_field,form);

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
			}
		}

		var btnViewPostA;//a node of 'View Page/Post' link.
		var origUrl, langUrl;
		var slugSamplePermalink;//'sample-permalink' node
		var origSamplePermalink;
		function setSlugLanguage(lang)
		{
			if(!btnViewPostA){
				var btnViewPost=ge('view-post-btn');
				if (!btnViewPost || !btnViewPost.children.length) return;
				btnViewPostA=btnViewPost.children[0];
				if(btnViewPostA.tagName != 'A') return;
				origUrl=btnViewPostA.href;
				langUrl=qtranxj_ce('a',{});
			}

			langUrl.href=origUrl;
			convertURL(langUrl,lang);
			btnViewPostA.href=langUrl.href;

			var btnPreviewAction=ge('preview-action');
			if (btnPreviewAction && btnPreviewAction.children.length)
			{
				btnPreviewAction.children[0].href=langUrl.href;
			}

			if(qTranslateConfig.url_mode!=1){//!QTX_URL_QUERY
				if(!slugSamplePermalink){
					var slugEl=ge('sample-permalink');
					if (slugEl && slugEl.childNodes.length){
						slugSamplePermalink=slugEl.childNodes[0];//span
						origSamplePermalink=slugSamplePermalink.nodeValue;
						//var slugEdit=ge('editable-post-name');
					}
				}
				if(slugSamplePermalink){
					langUrl.href=origSamplePermalink;
					convertURL(langUrl,lang);
					slugSamplePermalink.nodeValue=langUrl.href;
				}
			}
		}
		setSlugLanguage(activeLanguage);

		onTabSwitch=function()
		{
			for(var key in contentHooks){
				var h=contentHooks[key];
				h.contentField.value=h.contents[this.lang];
			}
			if(alttextField){
				alttextField.value=alttexts[this.lang];
			}
			setSlugLanguage(this.lang);
			if (!window.tinyMCE) return;
			for(var i=0; i<tinyMCE.editors.length; ++i){
				var ed=tinyMCE.editors[i];
				var h=contentHooks[ed.id];
				if(!h) continue;
				ed.setContent(h.contentField.value);//, {format: 'raw'}
			}
		}

		function setEditorHooks(e)
		{
			//window.onbeforeunload = function(){};
			var id = e.id;
			//c('setEditorHooks: id='+id);
			if (!id) return;
			var h=contentHooks[id];
			if(h.mce) return;
			h.mce=e;
			e.getBody().addEventListener('blur',function(){ updateFusedValueH(e.id, e.getContent());});
			//c('h.contentField.value='+h.contentField.value);
			//e.setContent(h.contentField.value);
		}

		// Add listeners for fields change
		window.addEventListener('load', function(){
				if (!window.tinyMCE){
					//alert('qTranslate-X error: !window.tinyMCE. Please report this incident to the developers.');
					return;
				}
				for(var i=0; i<tinyMCE.editors.length; ++i){
					var ed=tinyMCE.editors[i];
					setEditorHooks(ed);
				}
				for(var key in contentHooks){
					var h=contentHooks[key];
					if(h.mce) continue;
					if(h.contentField.tagName!=='TEXTAREA') continue;
					tinyMCEPreInit.mceInit[key].init_instance_callback=function(ed){ setEditorHooks(ed); }
				}
		});
		return true;
	}

	function optionsEdit()
	{
		var forms=document.getElementsByTagName('FORM');
		if(!forms.length) return false;
		var form=forms[0];

		addContentHookB(ge('blogname'),form);
		addContentHookB(ge('blogdescription'),form);

		onTabSwitch=function()
		{
			for(var key in contentHooks){
				var h=contentHooks[key];
				h.contentField.value=h.contents[this.lang];
			}
		}

		return true;
	}

	setLangCookie=function()
	{
		document.cookie='wp_qtrans_edit_language='+this.lang;
	}

	var matches = location.pathname.match(/(\/wp-admin\/([^\/]*))$/);
	switch(matches && matches[1])
	{
		case "/wp-admin/post.php":
		case "/wp-admin/post-new.php":
			if(!postEdit()) return;
			break;
		case "/wp-admin/edit-tags.php":
			if(!tagEdit()) return;
			break;
		case "/wp-admin/options-general.php":
			if(location.search.indexOf('page=')>=0) return;
			if(!optionsEdit()) return;
			break;
		default: return;
	}

	var langSwitchWrap=qtranxj_ce('ul', {className: 'qtranxs-lang-switch-wrap'});
	{
		//var header=w.getElementsByTagName('h2')[0];
		//header.parentNode.insertBefore(langSwitchWrap, header.nextElementSibling);
		var w=document.getElementsByClassName('wrap')[0];
		var f=w.getElementsByTagName('form')[0];
		f.parentNode.insertBefore(langSwitchWrap, f);
		languageSwitch=new LanguageSwitch(langSwitchWrap,activeLanguage);
		languageSwitch.onSwitch(onTabSwitch);
		languageSwitch.onSwitch(setLangCookie);
	}
}

function LanguageSwitch(target,initial_language)
{
	var langs=qTranslateConfig.enabled_languages, langNames=qTranslateConfig.language_name, activeLanguage=initial_language;
	var tabSwitches={};
	var onTabSwitch=[];
	function switchTab()
	{
		var tabSwitch=this;
		if (!tabSwitch.lang){
			alert('qTranslate-X: This should not have happened: Please, report this incident to the developers: !tabSwitch.lang');
			return;
		}
		if ( activeLanguage === tabSwitch.lang ){
			return;
		}
		if (activeLanguage)
		{
			tabSwitches[activeLanguage].classList.remove('active');
		}
		activeLanguage=tabSwitch.lang;
		tabSwitch.classList.add('active');
		for(var i=0; i<onTabSwitch.length; ++i)
		{
			onTabSwitch[i].call(this);
		}
	}
	location.pathname.indexOf();
	for(var i=0; i<langs.length; ++i)
	{
		//var flags_location=qTranslateConfig.WP_CONTENT_URL+qTranslateConfig.flag_location;
		var flag_location=qTranslateConfig.flag_location;
		var lang=langs[i];
		var tabSwitch=qtranxj_ce ('li', {lang: lang, className: 'qtranxs-lang-switch', onclick: switchTab }, target );
		qtranxj_ce('img', {src: flag_location+qTranslateConfig.flag[lang]}, tabSwitch);
		qtranxj_ce('span', {innerHTML: langNames[lang]}, tabSwitch);
		tabSwitches[lang]=tabSwitch;
		if ( activeLanguage == lang )
			tabSwitch.classList.add('active');
	}
	this.getActiveLanguage=function()
	{
		return activeLanguage;
	}
	this.onSwitch=function(callback)
	{
		if (typeof callback==='function')
		{
				onTabSwitch.push(callback);
		}
	}
}

new qTranslateX;
