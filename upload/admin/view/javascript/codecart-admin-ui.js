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
/* CodeCart PRO RC91 product image tools: delegated swap + sortable fallback. */
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


/* CodeCart PRO RC91: lightweight SEO URL helper for product/article forms. */
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
