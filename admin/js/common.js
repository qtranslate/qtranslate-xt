/*
//debuging tools, do not check in
*/
var cc=0;
function c(v){ ++cc; console.log('== '+cc+': '+v); }
function ct(v){ c(v); console.trace(); }
function co(t,o){ ++cc; console.log('== '+cc+': '+t+': %o',o); }

qtranxj_split = function(text,keep_neutral_text)
{
	var result = new Object;
	for(var i=0; i<qTranslateConfig.enabled_languages.length; ++i)
	{
		var lang=qTranslateConfig.enabled_languages[i];
		result[lang] = '';
	}
	var split_regex = /(<!--:[a-z]{2}-->|<!--:-->|\[:[a-z]{2}\])/gi;
	var blocks = text.xsplit(split_regex);
	//c('qtranxj_split: blocks='+blocks);
	//c('qtranxj_split: blocks.length='+blocks.length);
	if(!qtranxj_isArray(blocks))
		return result;
	if(blocks.length==1){//no language separator found, enter it to all languages
		var b=blocks[0];
		for(var j=0; j<qTranslateConfig.enabled_languages.length; ++j){
			var lang=qTranslateConfig.enabled_languages[j];
			result[lang] += b;
		}
		return result;
	}
	var clang_regex=/<!--:([a-z]{2})-->/gi;
	var c_end_regex=/<!--:-->/g;
	var blang_regex=/\[:([a-z]{2})\]/gi;
	lang = false;
	for(var i = 0;i<blocks.length;++i){
		var b=blocks[i];
		//c('blocks['+i+']='+b);
		if(!b.length) continue;
		matches = clang_regex.exec(b); clang_regex.lastIndex=0;
		if(matches!=null){
			lang = matches[1];
			continue;
		}
		matches = c_end_regex.exec(b); c_end_regex.lastIndex=0;
		if(matches!=null){
			lang = false;
			continue;
		}
		matches = blang_regex.exec(b); blang_regex.lastIndex=0;
		if(matches!=null){
			lang = matches[1];
			continue;
		}
		if(lang){
			result[lang] += b;
			lang = false;
		}else if(keep_neutral_text){
			for(var key in result){
				result[key] += b;
			}
		}
	}
	return result;
}

qtranxj_allthesame = function(texts)
{
	if(qTranslateConfig.enabled_languages.length==0) return '';
	var text = '';
	//take first not empty
	for(var i=0; i<qTranslateConfig.enabled_languages.length; ++i)
	{
		var lang=qTranslateConfig.enabled_languages[i];
		var t = texts[lang];
		if ( !t || t=='' ) continue;
		text = t;
		break;
	}
	for(var i=0; i<qTranslateConfig.enabled_languages.length; ++i)
	{
		var lang=qTranslateConfig.enabled_languages[i];
		var t = texts[lang];
		if ( !t || t=='' ) continue;
		if(t!=text) return null;
	}
	return text;
}

//"_c" stands for "comment"
qtranxj_join_c = function(texts)
{
	var text = qtranxj_allthesame(texts);
	if(text!=null) return text;
	text='';
	for(var i=0; i<qTranslateConfig.enabled_languages.length; ++i)
	{
		var lang=qTranslateConfig.enabled_languages[i];
		var t = texts[lang];
		if ( !t || t=='' ) continue;
		text += '<!--:'+lang+'-->';
		text += t;
		text += '<!--:-->';
	}
	//c('qtranxj_join_c:text:'+text);
	return text;
}

//"b" stands for "bracket"
qtranxj_join_b = function(texts)
{
	var text = qtranxj_allthesame(texts);
	if(text!=null) return text;
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

var qTranslateX=function(pg)
{
	this.ge=function(id){ return document.getElementById(id); }

	isLanguageEnabled=function(lang)
	{
		for(var i=0; i<qTranslateConfig.enabled_languages.length; ++i){
			if(qTranslateConfig.enabled_languages[i]==lang) return true;
		}
		return false;
	}

	var initialLanguage=qtranxj_get_cookie('wp_qtrans_edit_language');
	if(!initialLanguage || !isLanguageEnabled(initialLanguage))
		initialLanguage=qTranslateConfig.language;

	this.getInitialLanguage=function() { return initialLanguage; }

	var displayHooks=[];
	var contentHooks={};

	updateFusedValueH=function(id,value)
	{
		var h=contentHooks[id];
		var lang=languageSwitch.getActiveLanguage();
		var text=value.trim();
		c('updateFusedValueH['+id+']text:'+text);
		h.contents[lang]=text;
		if(h.separator==='<'){
			h.mlContentField.value = qtranxj_join_c(h.contents);
		}else{
			h.mlContentField.value = qtranxj_join_b(h.contents);
		}
	}

	addContentHook=function(inpField,form,separator)
	{
		if(!inpField) return false;
		var h=contentHooks[inpField.id]={};
		h.contentField=inpField;
		c('addContentHook:inpField.value='+inpField.value);
		h.contents=qtranxj_split(inpField.value,false);//inpField.tagName
		h.mlContentField=qtranxj_ce('input', {name: inpField.name, type: 'hidden', className: 'hidden', value: inpField.value}, form, true);
		if(!separator){
			if(inpField.tagName==='TEXTAREA')
				separator='<';
			else
				separator='[';
		}
		h.separator=separator;
		inpField.name='edit-'+inpField.name;
		var text = h.contents[initialLanguage];
		inpField.value=text;
		//c('addContentHook:inpField.value='+inpField.value);
		inpField.onblur=function(){ updateFusedValueH(this.id,this.value); }
		if(inpField.tagName==='TEXTAREA'){
			c('addContentHook:inpField.value='+inpField.value);
			for(var lang in h.contents){
				c('addContentHook:h.contents['+lang+']:'+h.contents[lang]);
			}
		}
		if(window.tinyMCE){//never fired yet
			for(var i=0; i<tinyMCE.editors.length; ++i){
				var ed=tinyMCE.editors[i];
				if(ed.id != inpField.id) continue;
				c('addContentHook:updateTinyMCE');
				updateTinyMCE(ed,text);
			}
		}
		return h;
	}
	this.addContentHookC=function(inpField,form) { return addContentHook(inpField,form,'<'); }
	this.addContentHookB=function(inpField,form) { return addContentHook(inpField,form,'['); }

	this.addContentHookById=function(id,form,sep) { return addContentHook(this.ge(id),form,sep); }
	this.addContentHookByIdName=function(nm,form)
	{
		var sep;
		if(nm.indexOf('<')==0 || nm.indexOf('[')==0){
			sep=nm.substring(0,1);
			nm=nm.substring(1);
		}
		return this.addContentHookById(nm,form,sep);
	}
	this.addContentHookByIdC=function(id,form) { return this.addContentHookById(id,form,'<'); }
	this.addContentHookByIdB=function(id,form) { return this.addContentHookById(id,form,'['); }

	addDisplayHook=function(elem)
	{
		if(!elem) return false;
		var h={};
		h.elem=elem;
		var content = elem.innerHTML.replace(/&lt;!--:([a-z]{2}|)--&gt;/gi,'<!--:$1-->');//un-escape language HTML
		//c('addDisplayHook: innerHTML='+elem.innerHTML);
		//c('addDisplayHook: content='+content);
		h.contents=qtranxj_split(content,true);
		elem.innerHTML=h.contents[initialLanguage];
		displayHooks.push(h);
		return true;
	}

	setLangCookie=function(lang) { document.cookie='wp_qtrans_edit_language='+lang; }

/*
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
*/

	updateTinyMCE=function(ed,text)
	{
		c('updateTinyMCE: text:'+text);
		if(!text.match(/^</)){
			text='<p>'+text+'</p>';
			c('updateTinyMCE: updated text:'+text);
		}
		/*
		if(window.switchEditors){
			//text = window.switchEditors.pre_wpautop( text );
			text = window.switchEditors.wpautop(text);//does format 'raw' takes care of it?
			c('updateTinyMCE:wpautop:'+text);
		}
		*/
		ed.setContent(text,{format: 'raw'});//do we need 'raw'?
	}

	onTabSwitch=function()
	{
		setLangCookie(this.lang);
		for(var i=0; i<displayHooks.length; ++i){
			var h=displayHooks[i];
			h.elem.innerHTML=h.contents[this.lang];
		}
		for(var key in contentHooks){
			var h=contentHooks[key];
			h.contentField.value=h.contents[this.lang];
			c('onTabSwitch: h['+key+'].contentField.value:'+h.contentField.value);
		}
		if (!window.tinyMCE) return;
		for(var i=0; i<tinyMCE.editors.length; ++i){
			var ed=tinyMCE.editors[i];
			var h=contentHooks[ed.id];
			if(!h) continue;
			updateTinyMCE(ed,h.contentField.value);
		}
	}

	var qtx=this;
	onTabSwitchCustom=function()
	{
		//co('onTabSwitch: this',this);
		//co('onTabSwitch: pg',pg);
		//co('onTabSwitch: qtx',qtx);
		pg.onTabSwitch(this.lang,qtx);
	}

	this.addDisplayHooksByClass=function(nm,form)
	{
		var elems=form.getElementsByClassName(nm);
		//c('addDisplayHooksByClass: elems.length='+elems.length);
		for(var i=0; i<elems.length; ++i){
			var e=elems[i];
			addDisplayHook(e);
		}
	}

	this.addContentHooksByClass=function(nm,form)
	{
		var sep;
		if(nm.indexOf('<')==0 || nm.indexOf('[')==0){
			sep=nm.substring(0,1);
			nm=nm.substring(1);
		}
		var fields=form.getElementsByClassName(nm);
		//c('addContentHooksByClass: fields.length='+fields.length);
		for(var i=0; i<fields.length; ++i){
			var f=fields[i];
			addContentHook(f,form,sep);
		}
	}

	// adds custom hooks from configuration
	this.addContentHooks=function(form)
	{
		//c('qTranslateConfig.custom_fields.length='+qTranslateConfig.custom_fields.length);
		for(var i=0; i<qTranslateConfig.custom_fields.length; ++i){
			var nm=qTranslateConfig.custom_fields[i];
			this.addContentHookByIdName(nm,form);
		}
		for(var i=0; i<qTranslateConfig.custom_field_classes.length; ++i){
			var nm=qTranslateConfig.custom_field_classes[i];
			this.addContentHooksByClass(nm,form);
		}
	}

	this.addContentHooksTinyMCE=function()
	{
		function setEditorHooks(e)
		{
			//window.onbeforeunload = function(){};
			var id = e.id;
			//c('setEditorHooks: id='+id);
			if (!id) return;
			var h=contentHooks[id];
			if(!h) return;
			if(h.mce) return;
			h.mce=e;
			e.getBody().addEventListener('blur',function(){
					var text=e.getContent({format : 'raw'});
					c('tinymce: onblur: text:'+text);
					if(text.match(/^<p[^>]*>(\s|&nbsp;|<br[^>]*>)*<\/p>$/)){
						text='';//workaround, need to learn how tinymce works ...
						c('tinymce: onblur: empty text');
					}else{
						var matches = text.match(/^<p>\s*(.*)\s*<\/p>$/);
						if(matches){
							text=matches[1];
							c('tinymce: onblur: removed plain <p>:'+text);
						}
					}
					updateFusedValueH(e.id,text);
				});
			c('setEditorHooks: id='+id);
			c('h.contentField.value='+h.contentField.value);
			var text=e.getContent({format : 'raw'});
			c('setEditorHooks: getContent(text):'+text);
			//updateTinyMCE(e,text);//it does it on its own?
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
	}

	if(!pg.addContentHooks(this))
		return;
	{
		var langSwitchWrap=qtranxj_ce('ul', {className: 'qtranxs-lang-switch-wrap'});
		//var header=w.getElementsByTagName('h2')[0];
		//header.parentNode.insertBefore(langSwitchWrap, header.nextElementSibling);
		var w=document.getElementsByClassName('wrap')[0];
		var f=pg.form;
		if(!f)
			f=w.getElementsByTagName('form')[0];
		f.parentNode.insertBefore(langSwitchWrap, f);
		languageSwitch=new qtranxj_LanguageSwitch(langSwitchWrap,initialLanguage);
		languageSwitch.onSwitch(onTabSwitch);
		if(pg.onTabSwitch)
			languageSwitch.onSwitch(onTabSwitchCustom);
	}
}

function qtranxj_LanguageSwitch(target,initial_language)
{
	var langs=qTranslateConfig.enabled_languages, langNames=qTranslateConfig.language_name, activeLanguage=initial_language;
	var tabSwitches={};
	var onTabSwitchFunctions=[];
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
		for(var i=0; i<onTabSwitchFunctions.length; ++i)
		{
			onTabSwitchFunctions[i].call(this);
		}
	}
	//location.pathname.indexOf();
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
	this.getActiveLanguage=function() { return activeLanguage; }
	this.onSwitch=function(callback)
	{
		if (typeof callback==='function')
		{
			onTabSwitchFunctions.push(callback);
		}
	}
}
//new qTranslateX;
