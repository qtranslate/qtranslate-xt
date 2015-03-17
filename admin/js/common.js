/*
// debugging tools, do not check in
var cc=0;
function c(v){ ++cc; console.log('== '+cc+': '+v); }
function ct(v){ c(v); console.trace(); }
function co(t,o){ ++cc; console.log('== '+cc+': '+t+'%o',o); }
*/

/**
 * since 3.2.7
 */
qtranxj_get_split_blocks = function(text)
{
	//var split_regex = /(<!--:[a-z]{2}-->|<!--:-->|\[:[a-z]{2}\]|\[:\]|\{:[a-z]{2}\}|\{:\})/gi;
	var split_regex = /(<!--:[a-z]{2}-->|<!--:-->|\[:[a-z]{2}\]|\[:\])/gi;
	return text.xsplit(split_regex);
}

/**
 * since 3.2.7
 */
qtranxj_split = function(text)
{
	var blocks = qtranxj_get_split_blocks(text);
	return qtranxj_split_blocks(blocks);
}

/**
 * since 3.1-b1 - closing tag [:]
 */
qtranxj_split_blocks = function(blocks)
{
	var result = new Object;
	for(var i=0; i<qTranslateConfig.enabled_languages.length; ++i)
	{
		var lang=qTranslateConfig.enabled_languages[i];
		result[lang] = '';
	}
	//if(!qtranxj_isArray(blocks))//since 3.2.7
	if(!blocks || !blocks.length)
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
	var blang_regex=/\[:([a-z]{2})\]/gi;
	//var slang_regex=/\{:([a-z]{2})\}/gi; //maybe later we will need it?
	var lang = false;
	var matches;
	for(var i = 0;i<blocks.length;++i){
		var b=blocks[i];
		//c('blocks['+i+']='+b);
		if(!b.length) continue;
		matches = clang_regex.exec(b); clang_regex.lastIndex=0;
		if(matches!=null){
			lang = matches[1];
			continue;
		}
		matches = blang_regex.exec(b); blang_regex.lastIndex=0;
		if(matches!=null){
			lang = matches[1];
			continue;
		}
		//matches = slang_regex.exec(b); slang_regex.lastIndex=0;
		//if(matches!=null){
		//	lang = matches[1];
		//	continue;
		//}
		if( b == '<!--:-->' || b == '[:]' ){// || b == '{:}' ){
			lang = false;
			continue;
		}
		if(lang){
			result[lang] += b;
			lang = false;
		}else{//keep neutral text
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
	if ( text=='' ) return text;
	for(var i=0; i<qTranslateConfig.enabled_languages.length; ++i)
	{
		var lang=qTranslateConfig.enabled_languages[i];
		var t = texts[lang];
		if ( t == text ) continue;
		return null;
	}
	return text;
}

/**
 * "_c" stands for "comment"
 * since 3.1-b1 - no _c any more
 */
qtranxj_join_c = function(texts)
{
	return qtranxj_join_b(texts);
/*
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
*/
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
	if( text != '' ) text += '[:]';
	return text;
}

/*
 * "s" stands for 'squiggly bracket'
 * Introduced, because some plugins, like [WordPress SEO](https://wordpress.org/plugins/wordpress-seo/),
 * remove '[:]' treating them as shortcodes.
 * since 3.2.7
 *
qtranxj_join_s = function(texts)
{
	var text = qtranxj_allthesame(texts);
	if(text!=null) return text;
	var text = '';
	for(var i=0; i<qTranslateConfig.enabled_languages.length; ++i)
	{
		var lang=qTranslateConfig.enabled_languages[i];
		var t = texts[lang];
		if ( !t || t=='' ) continue;
		text += '{:'+lang+'}';
		text += t;
	}
	if( text != '' ) text += '{:}';
	return text;
}
*/

/**
 * since 3.1-b1
 */
qtranxj_join_byline = function(texts)
{
	var text = qtranxj_allthesame(texts);
	if(text!=null) return text;
	var lines;
	for(var lang in texts){
		texts[lang] = texts[lang].split('\n');
	}
	var text = '';
	for(var i=0; true; ++i){
		var ln;
		for(var lang in texts){
			if ( texts[lang].length() <= i ) continue;
			var t = texts[lang][i];
			if ( !t || t=='' ) continue;
			ln[lang] = t;
		}
		if( !ln ) break;
		text += qtranxj_join_b(ln);
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

//Since 3.2.7 removed: function qtranxj_isArray(obj){ return obj.constructor.toString().indexOf('Array') >= 0; }

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

	qTranslateConfig.activeLanguage = qtranxj_get_cookie('qtrans_edit_language');
	if(!qTranslateConfig.activeLanguage || !isLanguageEnabled(qTranslateConfig.activeLanguage))
		qTranslateConfig.activeLanguage = qTranslateConfig.language;

	this.getActiveLanguage=function() { return qTranslateConfig.activeLanguage; }

	var contentHooks={};

	updateFusedValueHooked=function(h)
	{
		switch(h.separator){
			case '<': h.mlContentField.value = qtranxj_join_c(h.contents); break;
			case 'byline': h.mlContentField.value = qtranxj_join_byline(h.contents); break;
			case '[':
			default: h.mlContentField.value = qtranxj_join_b(h.contents); break;
		}
/*
		if(h.separator==='<'){
			h.mlContentField.value = qtranxj_join_c(h.contents);
		}else{
			h.mlContentField.value = qtranxj_join_b(h.contents);
		}
*/
		//c('updateFusedValueHooked['+h.mce.id+'] text:'+h.mlContentField.value);
	}

	updateFusedValueH=function(id,value)
	{
		var h=contentHooks[id];
		var text=value.trim();
		//c('updateFusedValueH['+id+'] lang='+h.lang+'; text:'+text);
		h.contents[h.lang]=text;
		updateFusedValueHooked(h);
	}

	addContentHook=function(inpField,form,separator)
	{
		//co('addContentHook: inpField:',inpField);
		if( !inpField ) return false;
		if( !inpField.name ) return false;
		//if( typeof inpField.value !== 'string' ) return false;
		switch(inpField.tagName){
			case 'TEXTAREA':
			case 'INPUT': break;
			default: return false;
		}
		if(!inpField.id){
			inpField.id = inpField.tagName;
			if(form.id) inpField.id += form.id;
			if(inpField.name) inpField.id += inpField.name;
		}
		if(contentHooks[inpField.id]) return true;
		var h=contentHooks[inpField.id]={};
		//h.id=inpField.id;
		h.contentField=inpField;
		//c('addContentHook: inpField.value='+inpField.value);
		h.contents=qtranxj_split(inpField.value);//keep neutral text from older times, just in case.
		                        //inpField.tagName
		h.mlContentField=qtranxj_ce('input', {name: inpField.name, type: 'hidden', className: 'hidden', value: inpField.value}, form);
		if(!separator){
			if(inpField.tagName==='TEXTAREA')
				separator='<';
			else
				separator='[';//since 3.1 we get rid of <:> encoding
		}
		h.separator=separator;
		inpField.name='edit-'+inpField.name;
		h.lang=qTranslateConfig.activeLanguage;
		var text = h.contents[h.lang];
		inpField.value=text;
		//c('addContentHook['+inpField.id+']['+h.lang+']: inpField.value='+inpField.value);
		inpField.onblur=function(){ updateFusedValueH(this.id,this.value); }

		/**
		 * Highlighting the translatable fields
		 * @since 3.2-b3
		*/
		inpField.className += ' qtranxs-translatable';

		if(window.tinyMCE){//never fired yet
			for(var i=0; i<tinyMCE.editors.length; ++i){
				var ed=tinyMCE.editors[i];
				if(ed.id != inpField.id) continue;
				//c('addContentHook:updateTinyMCE');
				h.mce=ed;
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

	this.removeContentHook=function(inpField)
	{
		if( !inpField ) return false;
		if( !inpField.id ) return false;
		if( !contentHooks[inpField.id] ) return false;
		var h=contentHooks[inpField.id];
		inpField.onblur = function(){};
		inpField.name=inpField.name.replace(/^edit-/,'');
		inpField.value=h.mlContentField.value;
		jQuery(inpField).removeClass('qtranxs-translatable');
		jQuery(h.mlContentField).remove();
		delete contentHooks[inpField.id];
		return true;
	};

	/**
	 * @since 3.2.7
	 */
	var displayHookNodes=[];
	addDisplayHookNode=function(nd)
	{
		if(!nd.nodeValue) return 0;
		var blocks = qtranxj_get_split_blocks(nd.nodeValue);
		if( !blocks || !blocks.length || blocks.length == 1 ) return 0;
		var h={};
		h.nd=nd;
		//co('addDisplayHookNode: nd=',nd);
		//c('addDisplayHookNode: nodeValue: "'+nd.nodeValue+'"');
		//c('addDisplayHookNode: content='+content);
		h.contents = qtranxj_split_blocks(blocks);
		nd.nodeValue=h.contents[qTranslateConfig.activeLanguage];
		displayHookNodes.push(h);
		return 1;
	}

	/**
	 * @since 3.2.7 switched to use of nodeValue instead of innerHTML.
	 */
	addDisplayHook=function(elem)
	{
		//co('addDisplayHook: elem=',elem);
		if(!elem || !elem.tagName) return 0;
		switch(elem.tagName){
			case 'TEXTAREA':
			case 'INPUT': return 0;
			default: break;
		}
		var cnt = 0;
		if(elem.childNodes && elem.childNodes.length){
			for(var i = 0; i < elem.childNodes.length; ++i){
				var nd = elem.childNodes[i];
				switch(nd.nodeType){//http://www.w3.org/TR/REC-DOM-Level-1/level-one-core.html#ID-1950641247
					case 1://ELEMENT_NODE
						cnt += addDisplayHook(nd);//recursive call
						break;
					case 2://ATTRIBUTE_NODE
						//co('addDisplayHook: ATTRIBUTE_NODE: ',nd);
					case 3://TEXT_NODE
						cnt += addDisplayHookNode(nd);
						break;
					default: break;
				}
			}
		}
		return cnt;
	}

/*
	var displayHooks=[];
	addDisplayHook=function(elem)
	{
		//co('addDisplayHook: elem=',elem);
		if(!elem || !elem.tagName) return 0;
		switch(elem.tagName){
			case 'TEXTAREA':
			case 'INPUT': return 0;
			default: break;
		}
		var cnt = 0;
		if(elem.innerHTML){
			var h={};
			h.elem=elem;
			var content = elem.innerHTML.replace(/&lt;!--:([a-z]{2}|)--&gt;/gi,'<!--:$1-->');//un-escape language HTML
			var blocks = qtranxj_get_split_blocks(content);
			if( blocks && blocks.length && blocks.length > 1 ){
				//co('addDisplayHook: elem=',elem);
				//c('addDisplayHook: innerHTML='+elem.innerHTML);
				//c('addDisplayHook: outterHTML='+elem.outerHTML);
				//c('addDisplayHook: textContent='+elem.textContent);
				//c('addDisplayHook: nodeValue='+elem.nodeValue);
				//c('addDisplayHook: content='+content);
				h.contents = qtranxj_split_blocks(blocks);
				//h.contents = qtranxj_split(content);
				elem.innerHTML=h.contents[qTranslateConfig.activeLanguage];
				if(elem.value){
					var value = elem.value.replace(/&lt;!--:([a-z]{2}|)--&gt;/gi,'<!--:$1-->');//un-escape language HTML
					if(value != ''){
						//h.values=qtranxj_split(value);
						var blocks = qtranxj_get_split_blocks(value);
						if( blocks && blocks.length && blocks.length > 1){
							h.values = qtranxj_split_blocks(blocks);
							elem.value = h.values[qTranslateConfig.activeLanguage];
						}
					}
				}
				displayHooks.push(h);
				cnt = 1;
			}
		}
		if(elem.children && elem.children.length){
			for(var i = 0; i < elem.children.length; ++i){
				var nd = elem.children[i];
				//c('addDisplayHook: nodeType='+nd.nodeType+'; nodeName='+nd.nodeName+'; nodeValue='+nd.nodeValue);
				cnt += addDisplayHook(nd);//recursive call
			}
		}
		return cnt;
	}
*/
	this.addDisplayHookById=function(id) { return addDisplayHook(this.ge(id)); }

	setLangCookie=function(lang) { document.cookie='qtrans_edit_language='+lang; }

	updateTinyMCE=function(ed,text)
	{
		//c('updateTinyMCE: text:'+text);
		if(window.switchEditors){
			//text = window.switchEditors.pre_wpautop( text );
			text = window.switchEditors.wpautop(text);
			//c('updateTinyMCE:wpautop:'+text);
		}
		ed.setContent(text,{format: 'html'});
	}

	onTabSwitch=function()
	{
		setLangCookie(this.lang);
		/*
		for(var i=0; i<displayHooks.length; ++i){
			var h=displayHooks[i];
			h.elem.innerHTML=h.contents[this.lang];
			if(h.values)
				h.elem.value=h.values[this.lang];
		}*/
		for(var i=0; i<displayHookNodes.length; ++i){
			var h=displayHookNodes[i];
			h.nd.nodeValue = h.contents[this.lang];
		}
		for(var key in contentHooks){
			var h=contentHooks[key];
			var mce = h.mce && !h.mce.hidden;
			if(mce){
				//c('onTabSwitch: h['+key+'].contentField.value before save:'+h.contentField.value);
				h.mce.save({format: 'html'});
				h.contents[h.lang] = h.contentField.value;
			}
			h.lang = this.lang;
			var value = h.contents[this.lang];
			if(h.contentField.placeholder && value != ''){//since 3.2.7
				h.contentField.placeholder='';
			}
			h.contentField.value = value;
			//c('onTabSwitch: h['+key+'].contentField.value:'+h.contentField.value);
			if(mce){
				updateTinyMCE(h.mce,h.contentField.value);
			}
		}
	}

	qTranslateConfig.qtx = this;
	onTabSwitchCustom=function()
	{
		//co('onTabSwitch: this',this);
		//co('onTabSwitch: qtx',qTranslateConfig.qtx);
		pg.onTabSwitch(this.lang,qTranslateConfig.qtx);
	}

	addDisplayHooks=function(elems)
	{
		//c('addDisplayHooks: elems.length='+elems.length);
		for(var i=0; i<elems.length; ++i){
			var e=elems[i];
			//co('addDisplayHooks: e=',e);
			//co('addDisplayHooks: e.tagName=',e.tagName);
			addDisplayHook(e);
		}
	}

	this.addDisplayHooksByClass=function(nm,container)
	{
		//co('addDisplayHooksByClass: container:',container);
		var elems=container.getElementsByClassName(nm);
		//co('addDisplayHooksByClass: elems('+nm+'):',elems);
		//co('addDisplayHooksByClass: elems.length=',elems.length);
		addDisplayHooks(elems);
	}

	this.addDisplayHooksByTagInClass=function(nm,tag,container)
	{
		var elems=container.getElementsByClassName(nm);
		//c('addDisplayHooksByClass: elems.length='+elems.length);
		for(var i=0; i<elems.length; ++i){
			var elem=elems[i];
			var items=elem.getElementsByTagName(tag);
			addDisplayHooks(items);
		}
	}

	/**
	 * @since 3.1-b2
	*/
	addContentFieldHooks=function(fields,form,sep)
	{
		for(var i=0; i<fields.length; ++i){
			var f=fields[i];
			//if(sep=='[') //co('addContentHooksByClass: f: ',f);
			addContentHook(f,form,sep);
		}
	}

	addContentHooksByClassName=function(nm,form,container,sep)
	{
		if(!container) container=form;
		var fields=container.getElementsByClassName(nm);
		//if(sep=='[') //c('addContentHooksByClass: fields.length='+fields.length);
		addContentFieldHooks(fields,form,sep);
	}

	this.addContentHooksByClass=function(nm,form,container)
	{
		var sep;
		if(nm.indexOf('<')==0 || nm.indexOf('[')==0){
			sep=nm.substring(0,1);
			nm=nm.substring(1);
		}
		addContentHooksByClassName(nm,form,container,sep);
	}

	/**
	 * adds custom hooks from configuration
	 * @since 3.1-b2 - renamed to addCustomContentHooks, since addContentHooks used in qTranslateConfig.js
	 * @since 3.0 - addContentHooks
	*/
	this.addCustomContentHooks=function(form)
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

	/**
	 * Parses custom page configuration, loaded in qtranxf_load_admin_page_config.
	 * @since 3.1-b2
	*/
	this.addPageHooks=function(page_config_forms)
	{
		for(var p=0; p < page_config_forms.length; ++p){
			var frm = page_config_forms[p];
			var form;
			if(frm.form){
				form = document.getElementById(frm.form.id);
			}else{
				form = this.getWrapForm();
			}
			//co('form=',form);
			//c('frm.fields.length='+frm.fields.length);
			for(var f=0; f < frm.fields.length; ++f){
				var fld = frm.fields[f];
				//co('fld=',fld);
				//c('encode='+fld.encode);
				//c('id='+fld.id);
				//c('class='+fld.class);
				var containers=[];
				if(fld.container_id){
					var container = document.getElementById(fld.container_id);
					if(container) containers.push(container);
				}else if(fld.container_class){
					containers = document.getElementsByClassName(fld.container_class);
				}else if(form){
					containers.push(form);
				}
				var sep = fld.encode;
				switch( sep ){
					case 'display':
						if(fld.id) addDisplayHook(document.getElementById(fld.id));
						else if(fld.class){
							//c('addPageHooks: display: class='+fld.class+'; fld.tag='+fld.tag);
							//c('class='+fld.class+'; containers.length='+containers.length);
							for(var i=0; i < containers.length; ++i){
								var container = containers[i];
								var fields=container.getElementsByClassName(fld.class);
								for(var j=0; j<fields.length; ++j){
									var field=fields[j];
									//c('field.tagName='+field.tagName);
									if(fld.tag && fld.tag != field.tagName) continue;
									addDisplayHook(field);
								}
								//this.addDisplayHooksByClass(fld.class,container);
							}
						}else if(fld.tag){
							//c('tag='+fld.tag+'; containers.length='+containers.length);
							for(var i=0; i < containers.length; ++i){
								var container = containers[i];
								//co('container=',container);
								var elems=container.getElementsByTagName(fld.tag);
								//co('elems=',elems);
								addDisplayHooks(elems);
							}
						}else{
							continue;
						}
						break;
					case '[':
					case '<':
					case 'byline':
					default:
						if(!form) continue;
						if(fld.id) this.addContentHookById(fld.id,form,sep);
						else if(fld.class){
							for(var i=0; i < containers.length; ++i){
								var container = containers[i];
								var fields=container.getElementsByClassName(fld.class);
								for(var j=0; j<fields.length; ++j){
									var field=fields[j];
									if(fld.tag && fld.tag != field.tagName) continue;
									if(fld.name && (!field.name || fld.name != field.name)) continue;
									addContentHook(field,form,sep);
								}
								//addContentHooksByClassName(fld.class,form,container,sep);
							}
						}else if(fld.tag){
							for(var i=0; i < containers.length; ++i){
								var container = containers[i];
								var fields=container.getElementsByTagName(fld.tag);
								for(var j=0; j<fields.length; ++j){
									var field=fields[j];
									if(fld.name && (!field.name || fld.name != field.name)) continue;
									addContentHook(field,form,sep);
								}
							}
						}else{
							continue;
						}
						break;
				}
			}
		}
	}

	this.addContentHooksTinyMCE=function()
	{
		function setEditorHooks(ed)
		{
			var id = ed.id;
			//c('setEditorHooks: id='+id);
			//ct('setEditorHooks: id='+id);
			if (!id) return;
			var h=contentHooks[id];
			if(!h) return;
			if(h.mce) return;
			h.mce=ed;
			ed.getBody().addEventListener('blur',function(){
					var h=contentHooks[ed.id];
					//c('blur: h['+ed.id+'].contentField.value before save:'+h.contentField.value);
					ed.save();
					//c('blur: h['+ed.id+'].contentField.value  after save:'+h.contentField.value);
					h.contents[h.lang] = h.contentField.value;
					updateFusedValueHooked(h);
				});

			/**
			 * Highlighting the translatable fields
			 * @since 3.2-b3
			*/
			ed.getContainer().className += ' qtranxs-translatable';
			ed.getElement().className += ' qtranxs-translatable';

			return h;
		}

		// Add listeners for fields change
		window.addEventListener('load', function(){
				if (!window.tinyMCE){
					//alert('qTranslate-X error: !window.tinyMCE. Please report this incident to the developers.');
					return;
				}
				for(var i=0; i<tinyMCE.editors.length; ++i){
					var ed=tinyMCE.editors[i];
					var h=setEditorHooks(ed);
					if(!h) continue;
					//c('addEventListener: id='+ed.id);
					//c('h.contentField.value='+h.contentField.value);
					updateTinyMCE(ed,h.contentField.value);
				}
				for(var key in contentHooks){
					var h=contentHooks[key];
					if(h.mce) continue;
					if(h.contentField.tagName!=='TEXTAREA') continue;
					if(tinyMCEPreInit.mceInit[key]){
						tinyMCEPreInit.mceInit[key].init_instance_callback=function(ed){ setEditorHooks(ed); }
					}
				}
		});
	}

	this.getWrapForm=function(){
		var wraps = document.getElementsByClassName('wrap');
		for(var i=0; i < wraps.length; ++i){
			var w = wraps[i];
			var forms = w.getElementsByTagName('form');
			if(forms.length) return forms[0];
		}
		var forms = document.getElementsByTagName('form');
		if(forms.length === 1)
			return forms[0];
		for(var i=0; i < forms.length; ++i){
			var f = forms[i];
			wraps = f.getElementsByClassName('wrap');
			if(wraps.length) return f;
		}
		return null;
	}

	this.getFormWrap=function(){
		var forms = document.getElementsByTagName('form');
		for(var i=0; i < forms.length; ++i){
			var f = forms[i];
			var wraps = f.getElementsByClassName('wrap');
			if(wraps.length) return wraps[0];
		}
		var wraps = document.getElementsByClassName('wrap');
		for(var i=0; i < wraps.length; ++i){
			var w = wraps[i];
			forms = w.getElementsByTagName('form');
			if(forms.length) return w;
		}
		return null;
	}

	if( typeof(pg.addContentHooks) == "function")
		pg.addContentHooks(this);

	if( qTranslateConfig.page_config && qTranslateConfig.page_config.forms)
		this.addPageHooks(qTranslateConfig.page_config.forms);

	//if(!displayHooks.length){
	if(!displayHookNodes.length){
		var ok = false;
		for(var key in contentHooks){ ok = true; break; }
		if(!ok)
			return;
	}

	{
		var anchors=[];
		if(qTranslateConfig.page_config && qTranslateConfig.page_config.anchors){
			for(var i=0; i < qTranslateConfig.page_config.anchors.length; ++i){
				var anchor = qTranslateConfig.page_config.anchors[i];
				var f = document.getElementById(anchor);
				if(f) anchors.push(f);
			}
		}
		if(!anchors.length){
			var f=pg.langSwitchWrapAnchor;
			if(!f){
				f = this.getWrapForm();
			}
			if(!f){
				f = this.getWrapForm();
				//var w = document.getElementsByClassName('wrap')[0];
				//f = w.getElementsByTagName('form')[0];
			}
			if(f) anchors.push(f);
		}
		for(var i=0; i < anchors.length; ++i){
			var anchor = anchors[i];
			var langSwitchWrap=qtranxj_ce('ul', {className: 'qtranxs-lang-switch-wrap'});
			//var header=w.getElementsByTagName('h2')[0];
			//header.parentNode.insertBefore(langSwitchWrap, header.nextElementSibling);
			anchor.parentNode.insertBefore( langSwitchWrap, anchor );
			var languageSwitch = new qtranxj_LanguageSwitch(langSwitchWrap);
			//languageSwitch.onSwitch(onTabSwitch);
			//if(pg.onTabSwitch)
			//	languageSwitch.onSwitch(onTabSwitchCustom);
		}
		/**
		 * @since 3.2.4 Synchronization of multiple sets of Language Switching Buttons
		 */
		qTranslateConfig.onTabSwitchFunctions=[];
		qTranslateConfig.onTabSwitchFunctions.push(onTabSwitch);
		if(pg.onTabSwitch)
			qTranslateConfig.onTabSwitchFunctions.push(onTabSwitchCustom);
	}
}

/**
 * @since 3.2.4 Multiple sets of Language Switching Buttons
 */
function qtranxj_LanguageSwitch(langSwitchWrap)
{
	var langs=qTranslateConfig.enabled_languages, langNames=qTranslateConfig.language_name;
	//var tabSwitches={};
	if(!qTranslateConfig.tabSwitches) qTranslateConfig.tabSwitches={};
	//var onTabSwitchFunctions=[];
	function switchTab()
	{
		var tabSwitch=this;
		if (!tabSwitch.lang){
			alert('qTranslate-X: This should not have happened: Please, report this incident to the developers: !tabSwitch.lang');
			return;
		}
		if ( qTranslateConfig.activeLanguage === tabSwitch.lang ){
			return;
		}
		if (qTranslateConfig.activeLanguage)
		{
			var tabSwitches = qTranslateConfig.tabSwitches[qTranslateConfig.activeLanguage];
			for(var i=0; i < tabSwitches.length; ++i){
				tabSwitches[i].classList.remove('active');
			}
			//tabSwitches[qTranslateConfig.activeLanguage].classList.remove('active');
		}
		qTranslateConfig.activeLanguage=tabSwitch.lang;
		{
			var tabSwitches = qTranslateConfig.tabSwitches[qTranslateConfig.activeLanguage];
			for(var i=0; i < tabSwitches.length; ++i){
				tabSwitches[i].classList.add('active');
			}
			//tabSwitch.classList.add('active');
		}
		var onTabSwitchFunctions = qTranslateConfig.onTabSwitchFunctions;
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
		var tabSwitch=qtranxj_ce ('li', {lang: lang, className: 'qtranxs-lang-switch', onclick: switchTab }, langSwitchWrap );
		qtranxj_ce('img', {src: flag_location+qTranslateConfig.flag[lang]}, tabSwitch);
		qtranxj_ce('span', {innerHTML: langNames[lang]}, tabSwitch);
		if ( qTranslateConfig.activeLanguage == lang )
			tabSwitch.classList.add('active');
		if(!qTranslateConfig.tabSwitches[lang]) qTranslateConfig.tabSwitches[lang] = [];
		qTranslateConfig.tabSwitches[lang].push(tabSwitch);
		//tabSwitches[lang]=tabSwitch;
	}
	//this.onSwitch=function(callback)
	//{
	//	if (typeof callback==='function')
	//	{
	//		onTabSwitchFunctions.push(callback);
	//	}
	//}
}

/**
 * qTranslateX instance is saved in global variable qTranslateConfig.qtx,
 * which can be used by theme or plugins to dynamically change content hooks.
 */
jQuery(document).ready(function($){ new qTranslateX(qTranslateConfig.js); });
