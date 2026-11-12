(function($){'use strict';

function applyAdminTheme(mode){mode=(mode==='dark'||mode==='light'||mode==='system')?mode:'light';var dark=mode==='dark'||(mode==='system'&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches);document.documentElement.classList.toggle('ccp-admin-dark',!!dark);document.documentElement.setAttribute('data-ccp-admin-theme',mode);var btn=document.getElementById('ccp-admin-theme-toggle');if(btn){btn.setAttribute('aria-pressed',dark?'true':'false');var icon=btn.querySelector('i.fa');if(icon){icon.classList.toggle('fa-sun',!!dark);icon.classList.toggle('fa-moon',!dark);}}}
function initAdminTheme(){var mode='light';try{mode=window.localStorage?localStorage.getItem('codecart_admin_theme')||'light':'light';}catch(e){}applyAdminTheme(mode);var btn=document.getElementById('ccp-admin-theme-toggle');if(btn&&!btn.dataset.ccpTheme){btn.dataset.ccpTheme='1';btn.addEventListener('click',function(e){e.preventDefault();var next=document.documentElement.classList.contains('ccp-admin-dark')?'light':'dark';try{if(window.localStorage)localStorage.setItem('codecart_admin_theme',next);}catch(err){}applyAdminTheme(next);});}if(window.matchMedia){var mq=window.matchMedia('(prefers-color-scheme: dark)');var handler=function(){var saved='light';try{saved=window.localStorage?localStorage.getItem('codecart_admin_theme')||'light':'light';}catch(e){}if(saved==='system')applyAdminTheme('system');};if(mq.addEventListener)mq.addEventListener('change',handler);else if(mq.addListener)mq.addListener(handler);}}
function textLength(value){return Array.from(String(value||'')).length;}
function currentRoute(){try{return String(new URL(window.location.href).searchParams.get('route')||'');}catch(e){var m=String(window.location.search||'').match(/[?&]route=([^&]+)/);return m?decodeURIComponent(m[1]):'';}}
var adminRoute=currentRoute();
function eligible(el){var n=String(el.name||'');if(!n)return false;var route=adminRoute;var allowed=[];if(route==='catalog/product'||route==='catalog/product/add'||route==='catalog/product/edit'){allowed=[/^product_description\[\d+\]\[(?:name|description|meta_title|meta_description|meta_keyword|tag)\]$/,/^product_attribute\[\d+\]\[product_attribute_description\]\[\d+\]\[text\]$/,/^(?:model|sku|upc|ean|jan|isbn|mpn)$/];}else if(route==='catalog/category'||route==='catalog/category/add'||route==='catalog/category/edit'){allowed=[/^category_description\[\d+\]\[(?:name|description|meta_title|meta_description|meta_keyword)\]$/];}else if(route==='catalog/manufacturer'||route==='catalog/manufacturer/add'||route==='catalog/manufacturer/edit'){allowed=[/^manufacturer_description\[\d+\]\[(?:name|description|meta_title|meta_description|meta_keyword)\]$/];}else if(route==='catalog/information'||route==='catalog/information/add'||route==='catalog/information/edit'){allowed=[/^information_description\[\d+\]\[(?:title|description|meta_title|meta_description|meta_keyword)\]$/];}else if(route==='blog/article'||route==='blog/article/add'||route==='blog/article/edit'){allowed=[/^article_description\[\d+\]\[(?:name|title|description|meta_title|meta_description|meta_keyword|tag)\]$/];}else if(route==='blog/category'||route==='blog/category/add'||route==='blog/category/edit'){allowed=[/^blog_category_description\[\d+\]\[(?:name|description|meta_title|meta_description|meta_keyword)\]$/];}else{return false;}for(var i=0;i<allowed.length;i++){if(allowed[i].test(n))return true;}return false;}
function attachCounter(el){if(!eligible(el)||el.dataset.ccpCounter)return;el.dataset.ccpCounter='1';var wrap=document.createElement('span');wrap.className='ccp-char-counter';el.parentNode.style.position=el.parentNode.style.position||'relative';el.parentNode.appendChild(wrap);function update(){var val=el.value||'';if($(el).data('summernote')){try{val=$('<div>').html($(el).summernote('code')).text();}catch(e){}}var max=parseInt(el.getAttribute('maxlength')||'0',10);var len=textLength(val);wrap.textContent=max?len+' / '+max:String(len);wrap.classList.toggle('is-over',!!max&&len>max);}el.addEventListener('input',update);el.addEventListener('change',update);$(el).on('summernote.change',update);update();}
function normalizePrice(v){v=String(v||'').trim().replace(',','.');if(!/^-?\d+(?:\.\d+)?$/.test(v))return v;var p=v.split('.');if(!p[1])return p[0]+'.00';var frac=p[1].replace(/0+$/,'');if(frac.length<=2)return p[0]+'.'+(frac+'00').slice(0,2);return p[0]+'.'+p[1].slice(0,4);}
function attachPrice(el){if(adminRoute!=='catalog/product'&&adminRoute!=='catalog/product/add'&&adminRoute!=='catalog/product/edit')return;var n=String(el.name||'');if(!/(^price$|\[price\]$|price_prefix$)/.test(n)||el.dataset.ccpPrice)return;el.dataset.ccpPrice='1';var fmt=function(){el.value=normalizePrice(el.value);};el.addEventListener('blur',fmt);fmt();}
function scan(node){$(node||document).find('input[type="text"][name],textarea[name]').addBack('input[type="text"][name],textarea[name]').each(function(){attachCounter(this);attachPrice(this);});}
$(function(){initAdminTheme();var dynamic=/^(?:catalog\/(?:product|category|manufacturer|information)|blog\/(?:article|category))(?:\/(?:add|edit))?$/.test(adminRoute);if(dynamic){scan(document);new MutationObserver(function(ms){ms.forEach(function(m){Array.from(m.addedNodes||[]).forEach(function(n){if(n.nodeType===1)scan(n);});});}).observe(document.body,{childList:true,subtree:true});}});
})(jQuery);
/* CodeCart PRO product image tools: delegated swap + sortable fallback. */
(function($){'use strict';
function isProductEditor(){var r='';try{r=String(new URL(window.location.href).searchParams.get('route')||'');}catch(e){}return r==='catalog/product'||r==='catalog/product/add'||r==='catalog/product/edit';}
function renumber(){ $('#images tbody input[name$="[sort_order]"]').each(function(i){this.value=i;}); }
function initSorting(){var body=document.querySelector('#images tbody');if(!body||body.getAttribute('data-codecart-sortable')==='1'||typeof window.Sortable==='undefined')return;body.setAttribute('data-codecart-sortable','1');new window.Sortable(body,{animation:140,handle:'.codecart-image-drag',ghostClass:'codecart-image-sort-ghost',onEnd:renumber});}
$(function(){if(!isProductEditor())return;
 $(document).off('click.ccpImageMainFallback','.codecart-image-main').on('click.ccpImageMainFallback','.codecart-image-main',function(e){e.preventDefault();var row=parseInt($(this).attr('data-image-row'),10);if(!isFinite(row))return;var $main=$('#input-image'),$other=$('#input-image'+row),$mainImg=$('#thumb-image img').first(),$otherImg=$('#thumb-image'+row+' img').first();if(!$main.length||!$other.length||!$other.val())return;var mainValue=$main.val(),otherValue=$other.val(),mainSrc=$mainImg.attr('src')||$mainImg.attr('data-placeholder')||'',otherSrc=$otherImg.attr('src')||$otherImg.attr('data-placeholder')||'';$main.val(otherValue);$other.val(mainValue);if($mainImg.length&&$otherImg.length){$mainImg.attr('src',otherSrc);$otherImg.attr('src',mainSrc);}renumber();});
 initSorting();
 $(document).on('shown.bs.tab','#form-product a[data-toggle="tab"]',initSorting);
 var body=document.querySelector('#images tbody');if(body&&window.MutationObserver){new MutationObserver(function(){initSorting();}).observe(body,{childList:true});}
});
})(jQuery);


/* CodeCart PRO lightweight SEO URL helper for product/article forms. */
(function($){
  'use strict';
  if(!$){return;}
  var map={
    'а':'a','б':'b','в':'v','г':'h','ґ':'g','д':'d','е':'e','є':'ye','ж':'zh','з':'z','и':'y','і':'i','ї':'yi','й':'y','к':'k','л':'l','м':'m','н':'n','о':'o','п':'p','р':'r','с':'s','т':'t','у':'u','ф':'f','х':'kh','ц':'ts','ч':'ch','ш':'sh','щ':'shch','ь':'','ю':'yu','я':'ya','ы':'y','э':'e','ё':'yo','ъ':''
  };
  function slugify(value){
    value=String(value||'').trim().toLowerCase();
    var out='';
    for(var i=0;i<value.length;i++){var ch=value.charAt(i);out+=Object.prototype.hasOwnProperty.call(map,ch)?map[ch]:ch;}
    if(out.normalize){out=out.normalize('NFKD').replace(/[\u0300-\u036f]/g,'');}
    return out.replace(/&(?:amp;)?/g,' and ').replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').replace(/-{2,}/g,'-').substring(0,180);
  }
  function updateSeoButton($button){$button.closest('.input-group-btn').removeClass('hidden');}
  $(document).on('click','.ccp-generate-seo-url',function(){
    var $button=$(this), languageId=$button.attr('data-language-id');
    var sourceSelector=$button.attr('data-source')||('#input-name'+languageId);var $source=$(sourceSelector).first();
    var selector=$button.attr('data-target');
    var $target=selector?$(selector):$button.closest('.input-group').find('input[type="text"]').first();
    var slug=slugify($source.val());
    if(slug){$target.val(slug).trigger('input').trigger('change');}
    updateSeoButton($button);
  });
  $(function(){
    $('.ccp-generate-seo-url').each(function(){updateSeoButton($(this));});
    $(document).on('input change','.ccp-seo-url-input',function(){var id='#'+this.id;$('.ccp-generate-seo-url[data-target="'+id+'"]').each(function(){updateSeoButton($(this));});});
  });
})(window.jQuery);


/* Shared admin feedback layer: toast notifications, readable AJAX errors, quick-save bar and unsaved-changes guard. */
(function($, window, document){'use strict';
var TOAST_LIMIT=4;
function i18n(key,fallback){var ui=window.CodeCartI18n||{};return ui[key]||fallback;}
function stack(){var el=document.getElementById('ccp-toast-stack');if(el)return el;el=document.createElement('div');el.id='ccp-toast-stack';el.className='ccp-toast-stack';el.setAttribute('aria-live','polite');document.body.appendChild(el);return el;}
function dismiss(node){if(!node||!node.parentNode)return;node.classList.remove('is-visible');window.setTimeout(function(){if(node.parentNode)node.parentNode.removeChild(node);},180);}
function toast(type,message,options){
	var text=String(message==null?'':message).trim();if(!text)return null;
	options=options||{};
	var kind=(type==='success'||type==='warning'||type==='info')?type:'danger';
	var host=stack();
	while(host.children.length>=TOAST_LIMIT){host.removeChild(host.firstChild);}
	var node=document.createElement('div');
	node.className='ccp-toast ccp-toast-'+kind;
	node.setAttribute('role',kind==='danger'||kind==='warning'?'alert':'status');
	var body=document.createElement('div');body.className='ccp-toast-body';
	if(options.title){var title=document.createElement('strong');title.textContent=options.title;body.appendChild(title);}
	var msg=document.createElement('span');msg.textContent=text;body.appendChild(msg);
	var close=document.createElement('button');close.type='button';close.className='ccp-toast-close';close.setAttribute('aria-label',i18n('ui_close','Close'));close.innerHTML='&times;';
	close.addEventListener('click',function(){dismiss(node);});
	node.appendChild(body);node.appendChild(close);host.appendChild(node);
	window.requestAnimationFrame(function(){node.classList.add('is-visible');});
	var ttl=options.duration!==undefined?options.duration:(kind==='success'||kind==='info'?4000:12000);
	if(ttl>0){window.setTimeout(function(){dismiss(node);},ttl);}
	return node;
}
function responseSummary(xhr){
	var raw=String((xhr&&xhr.responseText)||'');
	if(!raw)return '';
	if(/type=["']password["']/i.test(raw)&&/name=["']username["']/i.test(raw)){return i18n('ui_session_expired','Session expired. Sign in again.');}
	try{var data=JSON.parse(raw);if(data){var v=data.error||data.message||data.warning;if(typeof v==='string')return v;if(v&&typeof v==='object'){var first=Object.keys(v).map(function(k){return v[k];}).filter(function(s){return typeof s==='string';})[0];if(first)return first;}}}catch(e){}
	var plain=raw.replace(/<(script|style)[\s\S]*?<\/\1>/gi,' ').replace(/<[^>]+>/g,' ').replace(/\s+/g,' ').trim();
	return plain.length>200?plain.slice(0,200)+'…':plain;
}
function ajaxError(xhr,thrown){
	var status=xhr&&xhr.status?xhr.status:0;
	var reason=typeof thrown==='string'?thrown:((xhr&&xhr.statusText)||'');
	reason=String(reason).trim();if(reason.length>60||reason==='parsererror')reason='';
	var head=status?(status+' '+reason).trim():reason;
	var detail=responseSummary(xhr);
	if(window.console&&console.error){console.error('[CodeCart] AJAX '+head,xhr&&xhr.responseText);}
	return toast('danger',detail,{title:i18n('ui_request_failed','Request failed')+(head?' ('+head.trim()+')':'')});
}
window.CodeCartAdmin=$.extend(window.CodeCartAdmin||{},{toast:toast,ajaxError:ajaxError});

function pageRoute(){try{return String(new URL(window.location.href).searchParams.get('route')||'');}catch(e){return '';}}
function actionRoute(url){var m=String(url||'').match(/[?&]route=([^&]+)/);return m?decodeURIComponent(m[1]):'';}
function primarySave(){var $b=$('.page-header button[type="submit"][form^="form-"]').first();return $b.length?$b:$('.page-header button[type="submit"]').first();}

function initQuickSave(){
	var $save=primarySave();
	if(!$save.length||!window.IntersectionObserver)return;
	var anchor=$save.closest('.pull-right')[0]||$save[0];
	var bar=document.createElement('div');bar.id='ccp-savebar';bar.className='ccp-savebar';bar.hidden=true;
	var btn=document.createElement('button');btn.type='button';btn.className='btn btn-primary';
	var label=($save.attr('data-original-title')||$save.attr('title')||i18n('ui_save','Save'));
	btn.innerHTML='<i class="fa fa-save" aria-hidden="true"></i> ';
	btn.appendChild(document.createTextNode(label));
	btn.addEventListener('click',function(){$save[0].click();});
	bar.appendChild(btn);document.body.appendChild(bar);
	new IntersectionObserver(function(entries){bar.hidden=entries[0].isIntersecting;}).observe(anchor);
}

function snapshot(form){return $(form).find(':input').not(':password,:button,:submit,[type="file"],[data-ccp-no-guard]').filter('[name]').serialize();}
function initUnsavedGuard(){
	// List forms (row checkboxes for bulk actions) are not edits: selecting rows must not warn on leaving.
	var forms=$('#content form[id^="form-"]').filter(function(){return String(this.method||'').toLowerCase()==='post'&&!$(this).find('input[name^="selected"]').length;});
	if(!forms.length)return;
	var baseline=new Map(),submitting=false;
	function capture(){forms.each(function(){baseline.set(this,snapshot(this));});}
	function dirty(){var changed=false;forms.each(function(){if(baseline.has(this)&&baseline.get(this)!==snapshot(this))changed=true;});return changed;}
	if(document.readyState==='complete'){window.setTimeout(capture,800);}
	else{window.addEventListener('load',function(){window.setTimeout(capture,800);});}
	forms.on('submit',function(){submitting=true;window.setTimeout(function(){submitting=false;},4000);});
	$(document).ajaxSuccess(function(e,xhr,settings){
		if(String(settings.type||'').toUpperCase()!=='POST')return;
		var r=actionRoute(settings.url);if(!r)return;
		forms.each(function(){if(actionRoute(this.action)===r){baseline.set(this,snapshot(this));}});
	});
	window.addEventListener('beforeunload',function(e){
		if(submitting||!dirty())return;
		e.preventDefault();e.returnValue='';
	});
}

$(function(){
	var route=pageRoute();
	if(!route||route==='common/login')return;
	initQuickSave();
	initUnsavedGuard();
});
})(window.jQuery,window,document);


/* Quick navigation (Ctrl/Cmd+K): filters the links of the left menu, so extension menus and module pages are reachable without expanding groups. */
(function($, window, document){'use strict';
var MAX_RESULTS=40;
function i18n(key,fallback){var ui=window.CodeCartI18n||{};return ui[key]||fallback;}
function collect(){
	var items=[],seen={};
	$('#menu a[href]').each(function(){
		var href=this.getAttribute('href')||'';
		if(!href||href.charAt(0)==='#'||seen[this.href])return;
		var label=$.trim($(this).text().replace(/\s+/g,' '));
		if(!label)return;
		var path=[label],$li=$(this).closest('li');
		while(($li=$li.parent().closest('li')).length){var parent=$li.children('a').first();var text=$.trim(parent.text().replace(/\s+/g,' '));if(text)path.unshift(text);}
		seen[this.href]=1;
		items.push({href:this.href,label:label,path:path.join(' › '),haystack:path.join(' ').toLowerCase()});
	});
	return items;
}
function search(items,query){
	var tokens=query.toLowerCase().split(/\s+/).filter(Boolean);
	if(!tokens.length)return items.slice(0,MAX_RESULTS);
	var hits=[];
	items.forEach(function(item){
		for(var i=0;i<tokens.length;i++){if(item.haystack.indexOf(tokens[i])===-1)return;}
		var name=item.label.toLowerCase();
		hits.push({item:item,rank:name.indexOf(tokens[0])===0?0:(name.indexOf(tokens[0])>-1?1:2)});
	});
	hits.sort(function(a,b){return a.rank-b.rank;});
	return hits.slice(0,MAX_RESULTS).map(function(h){return h.item;});
}
function build(items){
	var root=document.createElement('div');root.id='ccp-palette';root.className='ccp-palette';root.hidden=true;
	root.innerHTML='<div class="ccp-palette-backdrop"></div><div class="ccp-palette-dialog" role="dialog" aria-modal="true"><input type="text" class="form-control ccp-palette-input" autocomplete="off" spellcheck="false" role="combobox" aria-expanded="true" aria-controls="ccp-palette-list" aria-autocomplete="list"><ul id="ccp-palette-list" class="ccp-palette-list" role="listbox"></ul></div>';
	document.body.appendChild(root);
	var dialog=root.querySelector('.ccp-palette-dialog'),input=root.querySelector('input'),list=root.querySelector('ul'),active=-1,shown=[],previous=null;
	dialog.setAttribute('aria-label',i18n('ui_quick_nav','Quick navigation'));
	input.setAttribute('placeholder',i18n('ui_quick_nav_placeholder','Go to a page'));
	function setActive(i){
		var nodes=list.children;if(!shown.length){active=-1;input.removeAttribute('aria-activedescendant');return;}
		active=(i+nodes.length)%nodes.length;
		for(var n=0;n<nodes.length;n++){nodes[n].classList.toggle('is-active',n===active);nodes[n].setAttribute('aria-selected',n===active?'true':'false');}
		input.setAttribute('aria-activedescendant',nodes[active].id);
		nodes[active].scrollIntoView({block:'nearest'});
	}
	function render(){
		shown=search(items,input.value);list.innerHTML='';
		if(!shown.length){var empty=document.createElement('li');empty.className='ccp-palette-empty';empty.textContent=i18n('ui_quick_nav_empty','Nothing found');list.appendChild(empty);setActive(-1);return;}
		shown.forEach(function(item,n){
			var li=document.createElement('li');li.id='ccp-palette-opt-'+n;li.setAttribute('role','option');
			var name=document.createElement('strong');name.textContent=item.label;
			var path=document.createElement('small');path.textContent=item.path;
			li.appendChild(name);if(item.path!==item.label)li.appendChild(path);
			li.addEventListener('mousemove',function(){if(active!==n)setActive(n);});
			li.addEventListener('click',function(e){go(n,e.ctrlKey||e.metaKey);});
			list.appendChild(li);
		});
		setActive(0);
	}
	function go(n,newTab){var item=shown[n];if(!item)return;close();if(newTab){window.open(item.href,'_blank','noopener');}else{window.location.href=item.href;}}
	function open(){previous=document.activeElement;root.hidden=false;input.value='';render();input.focus();}
	function close(){root.hidden=true;if(previous&&previous.focus)previous.focus();}
	input.addEventListener('input',render);
	input.addEventListener('keydown',function(e){
		if(e.key==='ArrowDown'){e.preventDefault();setActive(active+1);}
		else if(e.key==='ArrowUp'){e.preventDefault();setActive(active-1);}
		else if(e.key==='Enter'){e.preventDefault();if(active>-1)go(active,e.ctrlKey||e.metaKey);}
		else if(e.key==='Escape'){e.preventDefault();close();}
		else if(e.key==='Tab'){e.preventDefault();}
	});
	root.querySelector('.ccp-palette-backdrop').addEventListener('click',close);
	return {open:open,close:close,isOpen:function(){return !root.hidden;}};
}
$(function(){
	if(!document.getElementById('menu'))return;
	var palette=null;
	function toggle(){
		if(!palette)palette=build(collect());
		if(palette.isOpen()){palette.close();}else{palette.open();}
	}
	document.addEventListener('keydown',function(e){
		if((e.ctrlKey||e.metaKey)&&!e.altKey&&!e.shiftKey&&String(e.key).toLowerCase()==='k'){e.preventDefault();toggle();}
	});
	var anchor=document.querySelector('#header .ccp-admin-theme-menu');
	if(anchor&&anchor.parentNode){
		var li=document.createElement('li');li.className='ccp-palette-menu';
		var a=document.createElement('a');a.href='#';a.setAttribute('role','button');
		var label=i18n('ui_quick_nav','Quick navigation')+' (Ctrl+K)';
		a.title=label;a.setAttribute('aria-label',label);a.innerHTML='<i class="fa fa-compass" aria-hidden="true"></i>';
		a.addEventListener('click',function(e){e.preventDefault();toggle();});
		li.appendChild(a);anchor.parentNode.insertBefore(li,anchor);
	}
});
})(window.jQuery,window,document);
