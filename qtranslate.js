qtranxj_split = function(text)
{
    var split_regex = /(<!--.*?-->)/gi;
    var lang_begin_regex = /<!--:([a-z]{2})-->/gi;
    var lang_end_regex = /<!--:-->/gi;
    var morenextpage_regex = /(<!--more-->|<!--nextpage-->)+$/gi;
    var matches = null;
    var result = new Object;
    var matched = false;
    for (var i=0; i<qTranslateConfig.enabled_languages.length; i++)
    {
        var lang=qTranslateConfig.enabled_languages[i];
        result[lang] = '';
    }
    var blocks = text.xsplit(split_regex);
    if(qtranxj_isArray(blocks))
    {
        for (var i = 0;i<blocks.length;i++)
        {
            if ((matches = lang_begin_regex.exec(blocks[i])) != null)
            {
                matched = matches[1];
            }
            else if(lang_end_regex.test(blocks[i]))
            {
                matched = false;
            }
            else
            {
                if(matched)
                {
                    result[matched] += blocks[i];
                }
                else
                {
                    for (var j=0; j<qTranslateConfig.enabled_languages.length; j++)
                    {
                        var lang=qTranslateConfig.enabled_languages[j];
                        result[lang] += blocks[i];
                    }
                }
            }
        }
    }
    for (var i = 0;i<result.length;i++) {
        result[i] = result[i].replace(morenextpage_regex,'');
    }
    return result;
}

qtranxj_join = function(texts)
{
    var text = '';
    for (var i=0; i<qTranslateConfig.enabled_languages.length; i++)
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

function qtranxj_get_cookie(cname)
{
    var nm = cname + "=";
    var ca = document.cookie.split(';');
    for(var i=0; i<ca.length; i++) {
        var c = ca[i];
        var p = c.indexOf(nm);
        if (p >= 0) return c.substring(p+nm.length,c.length);
    }
    return '';
}

String.prototype.xsplit = function(_regEx){
    // Most browsers can do this properly, so let them work, they'll do it faster
    if ('a~b'.split(/(~)/).length === 3) { return this.split(_regEx); }

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

function ce(tagName, props, pNode, isFirst)
{
    var el= document.createElement(tagName);
    if (props)
    {
        for (prop in props)
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
var cc=0;
function c(v){ ++cc; console.log('== '+cc+': '+v); }
function ct(v){ c(v); console.trace(); }
function ge(id){ return document.getElementById(id); }

var qTranslateX=function()
{
    var activeLanguage=qtranxj_get_cookie('qtranx_edit_language');
	if(!activeLanguage)
		activeLanguage=qTranslateConfig.default_language;

	function updateFusedValue(target, newValue, store, activeLanguage)
	{
		var morenextpage_regex = /(<!--more-->|<!--nextpage-->)+$/gi;
		store[activeLanguage] = newValue.replace(morenextpage_regex,'');
        target.value = qtranxj_join(store);
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

		build_translator=function(langF,langT)
		{
				var translator={};
				for (var key in qTranslateConfig.term_name){
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
		var nameFields={};
		updateNames=function(langFrom,langTo)
		{
			if(!theList) return;
			var rows=theList.getElementsByTagName('TR');
			//c('rows.length='+rows.length);
			for (var r=0; r<rows.length; r++)
			{
				var dnm, tr=rows[r];
				//c('tr.tagName='+tr.tagName);
				//c('tr.innerHTML='+tr.innerHTML);
				var td=tr.getElementsByTagName('TD')[0];
				var divs=td.getElementsByTagName('DIV');
				//c('divs.length='+divs.length);
				for (var d=0; d<divs.length; d++)
				{
					var e=divs[d];
					//c('e.className='+e.className);
					if(e.className!=='name')
							continue;
					dnm=e.innerHTML;
					break;
				}
				var nms=qTranslateConfig.term_name[dnm]||{};
				//c('nms.length='+nms.length);
				var nmFrom = nms[langFrom]||'';
				//c('dnm='+dnm+' nmFrom='+nmFrom);
				var nmTo = nms[langTo]||'';
				var items=td.getElementsByClassName('row-title');
				//c('items.length='+items.length);
				for (var i=0; i<items.length; i++)
				{
					var e=items[i];
					//c('e.tagName='+e.tagName+'\ntitle='+e.title+'\ne.innerHTML='+e.innerHTML);
					//e.title = e.title.replace(nmFrom,nmTo);
					if(nmFrom)
							e.innerHTML = e.innerHTML.replace(nmFrom,nmTo);
					else
							e.innerHTML += nmTo;
					//c('new e.innerHTML='+e.innerHTML);
				}
			}
		}
		updateNames(qTranslateConfig.default_language,activeLanguage);

		var tagCloud=document.getElementsByClassName('tagcloud')[0];
		updateTagCloud=function(langF,langT)
		{
			if(!tagCloud) return;
			var items=tagCloud.getElementsByTagName('A');
			//c('updateTagCloud: items.length='+items.length);
			if(!items.length) return;
			var translator=build_translator(langF,langT);
			for (var i=0; i<items.length; i++)
			{
					var e=items[i];
					var nmF=e.innerHTML;
					var nmT=translator[nmF];
					if(!nmT) continue;
					e.innerHTML=nmT;
			}
		}
		updateTagCloud(qTranslateConfig.default_language,activeLanguage);

		// Swap fields
		//c('nameField.name='+nameField.name+' nameField.value='+nameField.value);
		var newNameField=ce('input', {name: nameField.name, className: 'hidden', value: nameField.value}, form, true);
		nameField.name='';

		// Load text
		var names = qTranslateConfig.term_name[nameField.value] || {};
		if (activeLanguage !== qTranslateConfig.default_language){
			nameField.value=names[activeLanguage] || '';
		}

		editinline_activated=function()
		{
			c('editinline_activated:'+this.innerHTML);
			return true;
		}

		var editinlines=document.getElementsByClassName('editinline');
		c('editinlines.length='+editinlines.length);
		for (var i=0; i<editinlines.length; i++)
		{
			var e=editinlines[i];
			if(e.tagName!=='A') continue;
			e.addEventListener( 'click', editinline_activated);
		}

		onTabSwitch=function()
		{
			if(activeLanguage === this.lang) return;
			nameField.value=names[this.lang] || '';
			updateNames(activeLanguage,this.lang);
			updateTagCloud(activeLanguage,this.lang);
			activeLanguage = this.lang;
		}

		var langs=qTranslateConfig.enabled_languages;
		var newNameFields={};
		for (var i=0; i<langs.length; i++)
		{
			var lang=langs[i];
            newNameFields[lang]=ce('input', {name: 'qtranx_term_'+lang, className: 'hidden', value: name[lang] || ''}, form, true);
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
		var contentField=ge('content');
		var titleField=ge('title');
		if (!contentField || !titleField){
			//alert('qTranslate-X cannot hook into the post editor.\nPlease, report this incident to the developers.');
			return false;
		}
        var titles = qtranxj_split(titleField.value);
        var contents = qtranxj_split(contentField.value);

		var mlTitleField=ce(titleField.tagName, {name: titleField.name, className: 'hidden', value: titleField.value}, form, true);
		var mlContentField=ce(contentField.tagName, {name: contentField.name, className: 'hidden', value: contentField.value}, form, true);

		titleField.name+='_old';
		contentField.name+='_old';
		titleField.value=titles[activeLanguage];
		contentField.value=contents[activeLanguage];

		// Slug
		var realUrl;
		function setSlugLanguage(lang)
		{
			var slugPreview1=ge('view-post-btn');
			if (!slugPreview1 || !slugPreview1.children.length) return;
			var url=slugPreview1.children[0];
			if (!url.urlWasGet)
			{
				realUrl=ce('a', {href: url.href});
				url.urlWasGet=true;
			}
			var localizedUrl=ce('a', {href: realUrl.href});
			switch (qTranslateConfig.url_mode.toString())
			{
			case '1':
				var basePath=localizedUrl.host;
				if (localizedUrl.search){
						localizedUrl.search+="&lang="+lang;
				}else{
						localizedUrl.search="?lang="+lang;
				}
				break;
			case '2':
				var basePath=localizedUrl.host+'/'+lang;
				localizedUrl.pathname='/'+lang+localizedUrl.pathname;
				break;
			case '3':
				var basePath=lang+'.'+localizedUrl.host;
				localizedUrl.host=basePath;
				break;
			}
			basePath=localizedUrl.protocol+'//'+basePath+'/';
			var slugEl=ge('sample-permalink');
			var postName=ge('editable-post-name');
			if (slugEl && slugEl.childNodes.length)
			{
					if (postName)
					{
							slugEl.childNodes[0].nodeValue=basePath;
					}
					else
					{
							slugEl.childNodes[0].nodeValue=localizedUrl.href;
					}
			}


			if (slugPreview1 && slugPreview1.children.length)
			{
					slugPreview1.children[0].href=localizedUrl.href;
			}

			var slugPreview2=ge('preview-action');
			if (slugPreview2 && slugPreview2.children.length)
			{
					slugPreview2.children[0].href=localizedUrl.href;
			}
		}
		setSlugLanguage(activeLanguage);
		//ge('edit-slug-box').addEventListener('DOMSubtreeModified', function(){ setSlugLanguage(languageSwitch.getActiveLanguage()) } );

		onTabSwitch=function()
		{
			titleField.value=titles[this.lang];
			contentField.value=contents[this.lang];
			setSlugLanguage(this.lang);
			if (!window.tinyMCE) return;
			if (!tinyMCE.activeEditor) return;
			tinyMCE.activeEditor.setContent(contentField.value);
		}

		var hooksWereSet={};
		function setEditorHooks(e)
		{
			console.assert(e);
			var id = e.id;
			if (!id) return;
			if (hooksWereSet[id]) return;
			hooksWereSet[id]=true;
			e.getBody().addEventListener('blur',function(){ updateFusedValue(mlContentField, tinyMCE.activeEditor.getContent(), contents, languageSwitch.getActiveLanguage());});
		}

		// Add listeners for fields change
		titleField.onblur=function(){ updateFusedValue(mlTitleField, this.value, titles, languageSwitch.getActiveLanguage()); };
		contentField.onblur=function(){ updateFusedValue(mlContentField, this.value, contents, languageSwitch.getActiveLanguage()); };
		window.addEventListener('load', function(){
            if (!window.tinyMCE){
                alert('qTranslate-X error: !window.tinyMCE. Please report this incident to the developers.');
                return;
            }
            tinyMCE.autosave_ask_before_unload=false;
            if(tinyMCE.activeEditor){
                setEditorHooks(tinyMCE.activeEditor);
            }
            tinyMCEPreInit.mceInit.content.init_instance_callback=function(ed){ setEditorHooks(ed); }
		});
		return true;
	}
	setLangCookie=function()
	{
        document.cookie='qtranx_edit_language='+this.lang;
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
	default: return;
	}
    var langSwitchWrap=ce('ul', {className: 'qtranxs-lang-switch-wrap'});
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
		for (var i=0; i<onTabSwitch.length; i++)
		{
			onTabSwitch[i].call(this);
		}
	}

	for (var i=0; i<langs.length; i++)
	{
		var lang=langs[i];
        var tabSwitch=ce ('li', {lang: lang, className: 'qtranxs-lang-switch', onclick: switchTab }, target );
		ce('img', {src: '/wp-content/'+qTranslateConfig.flag_location+qTranslateConfig.flag[lang]}, tabSwitch);
		ce('span', {innerHTML: langNames[lang]}, tabSwitch);
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
